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
        // Auto-finalizar asignaciones colgadas de días anteriores para que no distorsionen los cronómetros
        PatientAssignment::whereDate('created_at', '<', Carbon::today())
            ->whereIn('status', ['in_progress', 'pending'])
            ->update([
                'status' => 'completed',
                'ended_at' => Carbon::now()
            ]);

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
            }, 'assignments.doctor', 'appointments' => function ($q) {
                $q->whereDate('date', \Carbon\Carbon::today())->orderBy('time', 'asc');
            }])
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
            }, 'assignments.doctor', 'appointments' => function ($q) {
                $q->whereDate('date', \Carbon\Carbon::today())->orderBy('time', 'asc');
            }])
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
            'scheduled_time' => 'nullable|string|max:10',
            'notes' => 'nullable|string'
        ]);

        $patient = Patient::findOrFail($validated['patient_id']);

        // Si se indicó horario de turno y el paciente no tenía turno agendado hoy, registrarlo para que figure en Sala y permita ordenar
        if (!empty($validated['scheduled_time'])) {
            $hasTodayAppt = $patient->appointments()->whereDate('date', Carbon::today())->exists();
            if (!$hasTodayAppt) {
                $defaultDocId = $validated['doctor_ids'][0] ?? User::role('médico')->value('id') ?? \Illuminate\Support\Facades\Auth::id();
                \App\Models\Appointment::create([
                    'patient_id' => $patient->id,
                    'doctor_id' => $defaultDocId,
                    'date' => Carbon::today()->toDateString(),
                    'time' => $validated['scheduled_time'],
                    'duration_minutes' => 15,
                    'status' => 'confirmed',
                    'reason' => $validated['notes'] ?? 'Ingreso directo en Sala',
                    'created_by' => \Illuminate\Support\Facades\Auth::id(),
                ]);
            }
        }

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
        $todayInProgress = $patient->assignments()
            ->whereDate('created_at', Carbon::today())
            ->where('status', 'in_progress')
            ->get();

        $active = $todayInProgress->first();
        $sessionId = $active ? $active->session_id : (string) \Illuminate\Support\Str::uuid();
        $newDoctorId = $active ? $active->doctor_id : null;

        // Si se seleccionó "Alta" o "Finalizar Clínica", detener TODOS los cronómetros paralelos y salir.
        if ($request->next_step === 'ALTA' || $request->next_step === 'FINALIZAR') {
            $patient->assignments()
                ->whereDate('created_at', Carbon::today())
                ->where('status', 'in_progress')
                ->update([
                    'status' => 'completed',
                    'ended_at' => Carbon::now()
                ]);

            $patient->assignments()
                ->where('status', 'pending')
                ->whereDate('created_at', Carbon::today())
                ->delete();

            // Marcar turno de hoy como atendido
            $patient->appointments()
                ->whereDate('date', Carbon::today())
                ->whereIn('status', ['pending', 'confirmed', 'in_progress'])
                ->update(['status' => 'completed']);

            return back()->with('success', 'Alta médica registrada. Todos los cronómetros del paciente fueron detenidos.');
        }

        if ($request->next_step === 'AUTO') {
            $nextPending = $patient->assignments()
                ->whereDate('created_at', Carbon::today())
                ->where('status', 'pending')
                ->orderBy('id', 'asc')
                ->first();

            if (!$nextPending) {
                return back()->with('error', 'No hay un siguiente paso en la cola para este paciente. Active una actividad manualmente.');
            }
            $targetStep = $nextPending->event_type;
        } else {
            $targetStep = $request->next_step;
        }

        // Si ya está corriendo exactamente esa actividad en paralelo, avisar
        if ($todayInProgress->where('event_type', $targetStep)->count() > 0) {
            return back()->with('info', 'El cronómetro de "' . $targetStep . '" ya se encuentra corriendo en paralelo.');
        }

        // REGLA CLAVE: Al iniciar cualquier actividad clínica, SOLO se frena la Espera Inicial (y Aguardando pasivo).
        // Las demás actividades (Dilatación, Atención Médica, Estudios) siguen corriendo en paralelo hasta que se detengan individualmente o se dé el Alta.
        if ($targetStep !== 'Ingreso (espera)') {
            $patient->assignments()
                ->whereDate('created_at', Carbon::today())
                ->where('status', 'in_progress')
                ->whereIn('event_type', ['Ingreso (espera)', 'Aguardando'])
                ->update([
                    'status' => 'completed',
                    'ended_at' => Carbon::now()
                ]);
        }

        // Buscar si existe en cola pendiente ese paso
        $nextPending = $patient->assignments()
            ->whereDate('created_at', Carbon::today())
            ->where('status', 'pending')
            ->where('event_type', $targetStep)
            ->orderBy('id', 'asc')
            ->first();

        if ($nextPending) {
            $nextPending->update([
                'status' => 'in_progress',
                'started_at' => Carbon::now()
            ]);
            $stepType = $nextPending->event_type;
            $newDoctorId = $nextPending->doctor_id ?? $newDoctorId;
        } else {
            $patient->assignments()->create([
                'session_id' => $sessionId,
                'event_type' => $targetStep,
                'doctor_id' => $newDoctorId,
                'notes' => null,
                'status' => 'in_progress',
                'started_at' => Carbon::now()
            ]);
            $stepType = $targetStep;
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

        return back()->with('success', 'Cronómetro iniciado en paralelo: ' . $stepType);
    }

    /**
     * Inicia o detiene individualmente un cronómetro de actividad en paralelo
     * (Atención Médica, Dilatación, Estudios Visuales) sin frenar los demás.
     */
    public function toggleActivity(Request $request, Patient $patient)
    {
        $validated = $request->validate([
            'event_type' => 'required|string|in:Atención Médica,Dilatación,Estudios Visuales',
            'action' => 'required|string|in:start,stop',
        ]);

        $eventType = $validated['event_type'];
        $action = $validated['action'];

        $todayAssigns = $patient->assignments()
            ->whereDate('created_at', Carbon::today())
            ->orderBy('id', 'desc')
            ->get();

        $activeRow = $todayAssigns->where('status', 'in_progress')->first() ?? $todayAssigns->first();
        $sessionId = $activeRow?->session_id ?: (string) \Illuminate\Support\Str::uuid();
        $doctorId = $activeRow?->doctor_id;

        if ($action === 'start') {
            // Frenar únicamente Espera Inicial o estado pasivo Aguardando
            $patient->assignments()
                ->whereDate('created_at', Carbon::today())
                ->where('status', 'in_progress')
                ->whereIn('event_type', ['Ingreso (espera)', 'Aguardando'])
                ->update([
                    'status' => 'completed',
                    'ended_at' => Carbon::now()
                ]);

            // Verificar si esta actividad ya está corriendo
            $alreadyRunning = $patient->assignments()
                ->whereDate('created_at', Carbon::today())
                ->where('session_id', $sessionId)
                ->where('event_type', $eventType)
                ->where('status', 'in_progress')
                ->first();

            if (!$alreadyRunning) {
                $pendingMatch = $patient->assignments()
                    ->whereDate('created_at', Carbon::today())
                    ->where('session_id', $sessionId)
                    ->where('event_type', $eventType)
                    ->where('status', 'pending')
                    ->first();

                if ($pendingMatch) {
                    $pendingMatch->update([
                        'status' => 'in_progress',
                        'started_at' => Carbon::now(),
                    ]);
                } else {
                    $patient->assignments()->create([
                        'session_id' => $sessionId,
                        'event_type' => $eventType,
                        'doctor_id' => $doctorId,
                        'status' => 'in_progress',
                        'started_at' => Carbon::now(),
                    ]);
                }
            }

            return back()->with('success', "Cronómetro de {$eventType} iniciado (corre en paralelo).");
        }

        // Acción: STOP (detener solo esta actividad individual)
        $patient->assignments()
            ->whereDate('created_at', Carbon::today())
            ->where('session_id', $sessionId)
            ->where('event_type', $eventType)
            ->where('status', 'in_progress')
            ->update([
                'status' => 'completed',
                'ended_at' => Carbon::now(),
            ]);

        // Si no quedó ninguna otra actividad en curso, mantener al paciente en Sala ("Aguardando")
        // sin sumar a Espera Inicial, hasta que se inicie otra actividad o se le dé el Alta.
        $remainingActive = $patient->assignments()
            ->whereDate('created_at', Carbon::today())
            ->where('session_id', $sessionId)
            ->where('status', 'in_progress')
            ->count();

        if ($remainingActive === 0) {
            $patient->assignments()->create([
                'session_id' => $sessionId,
                'event_type' => 'Aguardando',
                'doctor_id' => $doctorId,
                'status' => 'in_progress',
                'started_at' => Carbon::now(),
            ]);
        }

        return back()->with('success', "Cronómetro de {$eventType} detenido individualmente.");
    }
    
    /**
     * Update internal notes / comments of the moment for a patient in Sala de Espera.
     */
    public function updateNotes(Request $request, Patient $patient)
    {
        $validated = $request->validate([
            'notes' => 'nullable|string',
        ]);

        $notes = $validated['notes'] ?? null;

        // Actualizar la asignación activa o primera de la sesión de hoy
        $assignment = $patient->assignments()
            ->whereDate('created_at', Carbon::today())
            ->orderBy('id', 'asc')
            ->first();

        if ($assignment) {
            $assignment->notes = $notes;
            $assignment->save();
        }

        // Si el paciente tiene turno agendado hoy, sincronizar también la nota del turno
        $todayAppt = $patient->appointments()->whereDate('date', Carbon::today())->first();
        if ($todayAppt) {
            $todayAppt->notes = $notes;
            $todayAppt->save();
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'notes' => $notes,
                'message' => 'Observaciones internas actualizadas correctamente.'
            ]);
        }

        return back()->with('success', 'Observaciones internas actualizadas.');
    }

    public function finishAll(Request $request)
    {
        // Close all "in_progress" assignments strictly and clear pending queue.
        PatientAssignment::where('status', 'in_progress')
            ->whereDate('created_at', Carbon::today())
            ->update([
                'status' => 'completed',
                'ended_at' => Carbon::now()
            ]);

        PatientAssignment::where('status', 'pending')
            ->whereDate('created_at', Carbon::today())
            ->delete();
            
        return back()->with('success', 'Se ha finalizado toda la Sala de Espera y detenido todos los cronómetros.');
    }
}
