<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\PatientAssignment;
use App\Models\User;
use App\Events\DoctorAssignedAlert;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ConsoleController extends Controller
{
    public function index()
    {
        $doctors = User::role('médico')->get();
        // All patients for the "Add New" dropdown
        $patients = Patient::orderBy('last_name')->get();

        // Active Patients
        $activePatients = Patient::whereHas('assignments', function ($q) {
                $q->whereDate('created_at', Carbon::today())
                  ->where('status', 'in_progress');
            })
            ->with(['assignments' => function ($q) {
                $q->whereDate('created_at', Carbon::today())->orderBy('id', 'asc');
            }, 'assignments.doctor'])
            ->get();
            
        // Filter active assignments by the active session_id only
        $activePatients->each(function($patient) {
            $activeSessionId = $patient->assignments->where('status', 'in_progress')->first()->session_id ?? null;
            if ($activeSessionId) {
                $patient->setRelation('assignments', $patient->assignments->where('session_id', $activeSessionId)->values());
            }
        });

        // Completed patients today
        $finishedPatients = Patient::whereHas('assignments', function ($q) {
                $q->whereDate('started_at', Carbon::today());
            })
            ->whereDoesntHave('assignments', function ($q) {
                $q->whereDate('started_at', Carbon::today())
                  ->where('status', 'in_progress');
            })
            ->with(['assignments' => function ($q) {
                $q->whereDate('started_at', Carbon::today())->orderBy('started_at', 'asc');
            }, 'assignments.doctor'])
            ->get();
            
        // Filter finished to only show their latest session block
        $finishedPatients->each(function($patient) {
            $latestSessionId = $patient->assignments->sortByDesc('started_at')->first()->session_id ?? null;
            if ($latestSessionId) {
                $patient->setRelation('assignments', $patient->assignments->where('session_id', $latestSessionId)->values());
            }
        });

        return view('console.index', compact('doctors', 'patients', 'activePatients', 'finishedPatients'));
    }

    public function store(Request $request)
    {
        // Support array inputs for sequential assignments
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'event_types' => 'required|array|min:1',
            'event_types.*' => 'required|string|max:100',
            'doctor_ids' => 'nullable|array',
            'doctor_ids.*' => 'nullable|exists:users,id',
            'notes' => 'nullable|string'
        ]);

        $patient = Patient::findOrFail($validated['patient_id']);

        // Check if patient already has an active assignment today
        $active = $patient->assignments()
            ->whereDate('started_at', Carbon::today())
            ->whereIn('status', ['in_progress', 'pending'])
            ->first();

        // If active, we don't start a duplicate. We redirect back with error.
        if ($active) {
            return back()->with('error', 'El paciente ya se encuentra en un estado activo o en cola en la clínica.');
        }

        $sessionId = (string) \Illuminate\Support\Str::uuid();
        $firstAssignedDoctor = null;
        $firstEventType = null;

        foreach ($validated['event_types'] as $index => $eventType) {
            $doctorId = $validated['doctor_ids'][$index] ?? null;
            $isFirst = ($index === 0);
            
            $patient->assignments()->create([
                'session_id' => $sessionId,
                'event_type' => $eventType,
                'doctor_id' => $doctorId,
                'notes' => $isFirst ? ($validated['notes'] ?? null) : null, // Solo anotaciones en el primero
                'status' => $isFirst ? 'in_progress' : 'pending',
                'started_at' => $isFirst ? Carbon::now() : null
            ]);

            if ($isFirst) {
                $firstAssignedDoctor = $doctorId;
                $firstEventType = $eventType;
            }
        }

        try {
            broadcast(new \App\Events\PatientArrived($patient, \Illuminate\Support\Facades\Auth::id()))->toOthers();
            
            if ($firstAssignedDoctor) {
                $fullName = trim($patient->first_name . ' ' . $patient->last_name);
                broadcast(new DoctorAssignedAlert($firstAssignedDoctor, $fullName, $firstEventType))->toOthers();
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Broadcast error: ' . $e->getMessage());
        }

        return back()->with('success', 'Secuencia de pasos volcada a la Consola de Espera correctamente.');
    }

    public function append(Request $request, Patient $patient)
    {
        $validated = $request->validate([
            'event_types' => 'required|array|min:1',
            'event_types.*' => 'required|string|max:100',
            'doctor_ids' => 'nullable|array',
            'doctor_ids.*' => 'nullable|exists:users,id',
        ]);

        // Get the active session_id for today
        $activeAssignment = $patient->assignments()
            ->whereDate('created_at', Carbon::today())
            ->whereIn('status', ['in_progress', 'pending', 'completed'])
            ->latest('id')
            ->first();

        if (!$activeAssignment || !$activeAssignment->session_id) {
            return back()->with('error', 'El paciente no tiene un flujo activo hoy al que agregar pasos.');
        }

        $sessionId = $activeAssignment->session_id;

        foreach ($validated['event_types'] as $index => $eventType) {
            $doctorId = $validated['doctor_ids'][$index] ?? null;
            
            $patient->assignments()->create([
                'session_id' => $sessionId,
                'event_type' => $eventType,
                'doctor_id' => $doctorId,
                'status' => 'pending',
                'started_at' => null
            ]);
        }

        return back()->with('success', 'Nuevos pasos agregados al flujo.');
    }

    public function transition(Request $request, Patient $patient)
    {
        // 1. Terminar proceso activo actual
        $active = $patient->assignments()
            ->whereDate('started_at', Carbon::today())
            ->where('status', 'in_progress')
            ->first();

        if ($active) {
            $active->update([
                'status' => 'completed',
                'ended_at' => Carbon::now()
            ]);
        }

        // Si se seleccionó "Alta" o "Finalizar Clínica", eliminar pendientes y salir.
        if ($request->next_step === 'ALTA' || $request->next_step === 'FINALIZAR') {
            $patient->assignments()
                ->where('status', 'pending')
                ->whereDate('created_at', Carbon::today())
                ->delete();
                
            return back()->with('success', 'Flujo cerrado. Paciente dado de alta de la cola.');
        }
        
        // 2. Buscar si ya existe un paso pendiente
        $nextPending = $patient->assignments()
            ->whereDate('created_at', Carbon::today())
            ->where('status', 'pending')
            ->orderBy('id', 'asc')
            ->first();

        $newDoctorId = $active ? $active->doctor_id : null; 
        
        if ($nextPending && ($request->next_step === 'AUTO' || $nextPending->event_type === $request->next_step)) {
            // Activar el paso pendiente
            $nextPending->update([
                'status' => 'in_progress',
                'started_at' => Carbon::now()
            ]);
            $stepType = $nextPending->event_type;
            $newDoctorId = $nextPending->doctor_id ?? $newDoctorId;
        } else {
            // Crear el nuevo paso manualmente
            $patient->assignments()->create([
                'session_id' => $active ? $active->session_id : (string) \Illuminate\Support\Str::uuid(),
                'event_type' => $request->next_step,
                'doctor_id' => $newDoctorId,
                'notes' => null,
                'status' => 'in_progress',
                'started_at' => Carbon::now()
            ]);
            $stepType = $request->next_step;
        }

        try {
            if ($newDoctorId) {
                $fullName = trim($patient->first_name . ' ' . $patient->last_name);
                broadcast(new DoctorAssignedAlert($newDoctorId, $fullName, $stepType))->toOthers();
            }

            if ($stepType === 'Atención Médica') {
                $doctorName = $newDoctorId ? User::find($newDoctorId)?->name : null;
                broadcast(new \App\Events\PatientEnteredConsultorio($patient, $doctorName, \Illuminate\Support\Facades\Auth::id()))->toOthers();
            }
        } catch (\Exception $e) {}

        return back()->with('success', 'Paciente avanzado a: ' . $stepType);
    }
    
    public function finishAll(Request $request)
    {
        // Close all "in_progress" assignments strictly.
        PatientAssignment::where('status', 'in_progress')
            ->whereDate('started_at', Carbon::today())
            ->update([
                'status' => 'completed',
                'ended_at' => Carbon::now()
            ]);
            
        return back()->with('success', 'Se ha forzado la finalización de toda la Sala de Espera.');
    }
}
