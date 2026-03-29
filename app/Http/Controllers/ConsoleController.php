<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\PatientAssignment;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ConsoleController extends Controller
{
    public function index()
    {
        $doctors = User::role('médico')->get();
        // All patients for the "Add New" dropdown
        $patients = Patient::orderBy('last_name')->get();

        // Active Patients: Patients who have assignments TODAY.
        // We load their assignments of today.
        $activePatients = Patient::whereHas('assignments', function ($q) {
                $q->whereDate('started_at', Carbon::today())
                  ->where('status', 'in_progress'); // Must have an active step
            })
            ->with(['assignments' => function ($q) {
                $q->whereDate('started_at', Carbon::today())->orderBy('started_at', 'asc');
            }, 'assignments.doctor'])
            ->get();
            
        // Completed patients today (for the history section / bottom list if we want it)
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

        return view('console.index', compact('doctors', 'patients', 'activePatients', 'finishedPatients'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'event_type' => 'required|string|max:100',
            'doctor_id' => 'nullable|exists:users,id',
            'notes' => 'nullable|string'
        ]);

        $patient = Patient::findOrFail($validated['patient_id']);

        // Check if patient already has an active assignment today
        $active = $patient->assignments()
            ->whereDate('started_at', Carbon::today())
            ->where('status', 'in_progress')
            ->first();

        // If active, we don't start a duplicate. We redirect back with error.
        if ($active) {
            return back()->with('error', 'El paciente ya se encuentra en un estado activo en la clínica.');
        }

        $patient->assignments()->create([
            'event_type' => $validated['event_type'],
            'doctor_id' => $validated['doctor_id'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'status' => 'in_progress',
            'started_at' => Carbon::now()
        ]);

        try {
            broadcast(new \App\Events\PatientArrived($patient))->toOthers();
        } catch (\Exception $e) {
            // Silently ignore broadcasting errors if Reverb is down, so the app remains stable
        }

        return back()->with('success', 'Paciente volcado a la Consola de Espera correctamente.');
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

        // Si se seleccionó "Alta" o "Finalizar Clínica", no creamos otro paso.
        if ($request->next_step === 'ALTA' || $request->next_step === 'FINALIZAR') {
            return back()->with('success', 'Flujo cerrado. Paciente dado de alta de la cola.');
        }
        
        // 2. Crear el nuevo paso
        $patient->assignments()->create([
            'event_type' => $request->next_step,
            'doctor_id' => $active ? $active->doctor_id : null, // keep same doctor unless specified
            'notes' => null,
            'status' => 'in_progress',
            'started_at' => Carbon::now()
        ]);

        return back()->with('success', 'Paciente avanzado a: ' . $request->next_step);
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
