<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AppointmentController extends Controller
{
    /**
     * Shows the Medical Agenda Calendar UI
     */
    public function index()
    {
        $doctors = User::role('médico')->get();
        // Useful for the quick-add form
        $patients = Patient::select('id', 'first_name', 'last_name', 'dni')->get();
        
        return view('agenda.index', compact('doctors', 'patients'));
    }

    /**
     * API Endpoint for FullCalendar to fetch events.
     */
    public function getEvents(Request $request)
    {
        $start = Carbon::parse($request->start)->toDateString();
        $end = Carbon::parse($request->end)->toDateString();
        $doctorId = $request->doctor_id;

        // 1. Fetch Normal Appointments
        $query = Appointment::with(['patient', 'doctor'])
            ->whereBetween('date', [$start, $end]);

        if ($doctorId) {
            $query->where('doctor_id', $doctorId);
        }

        $appointments = $query->get();

        $events = $appointments->map(function ($appointment) {
            $startDateTime = $appointment->date . 'T' . $appointment->time;
            $endDateTime = Carbon::parse($startDateTime)->addMinutes($appointment->duration_minutes)->toIso8601String();

            // Color coding logic based on status
            $color = '#5E6AD2'; // Default primary
            if ($appointment->status === 'confirmed') $color = '#28a745';
            if ($appointment->status === 'cancelled') $color = '#dc3545';
            if ($appointment->status === 'no_show') $color = '#6c757d';

            return [
                'id' => 'evt_' . $appointment->id,
                'title' => substr($appointment->patient->first_name, 0, 1) . '. ' . $appointment->patient->last_name,
                'start' => $startDateTime,
                'end' => $endDateTime,
                'color' => $color,
                'extendedProps' => [
                    'doctor' => 'Dr. '.$appointment->doctor->name,
                    'status' => $appointment->status,
                    'reason' => $appointment->reason,
                    'isBackground' => false
                ]
            ];
        })->toArray();

        // 2. Saturated Days Logic (Ex: 8 appointments = fully booked MVP)
        if ($doctorId) {
            $counts = $appointments->groupBy('date')->map->count();
            foreach ($counts as $date => $count) {
                if ($count >= 6) { // 6 turns is considered 'saturated' for MVP
                    $events[] = [
                        'start' => $date,
                        'display' => 'background',
                        'backgroundColor' => 'rgba(220, 53, 69, 0.15)', // Reddish
                        'extendedProps' => [
                            'isBackground' => true,
                            'reason' => 'Agenda Saturada'
                        ]
                    ];
                }
            }
        }

        // 3. Fetch ScheduleBlocks (Vacations, Conferences)
        if ($doctorId) {
            $blocks = \App\Models\ScheduleBlock::where('doctor_id', $doctorId)
                        ->where(function($q) use ($start, $end) {
                            $q->whereBetween('start_datetime', [$start, $end])
                              ->orWhereBetween('end_datetime', [$start, $end]);
                        })->get();
                        
            foreach ($blocks as $block) {
                $events[] = [
                    'id' => 'blk_' . $block->id,
                    'title' => $block->reason,
                    'start' => $block->start_datetime,
                    'end' => $block->end_datetime,
                    'display' => 'background',
                    'backgroundColor' => 'rgba(108, 117, 125, 0.3)', // Grey out
                    'extendedProps' => [
                        'isBackground' => true,
                        'reason' => $block->reason
                    ]
                ];
            }
        }

        return response()->json($events);
    }

    /**
     * Store new appointment via AJAX
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'required|exists:users,id',
            'date' => 'required|date',
            'time' => 'required',
            'duration_minutes' => 'integer',
            'reason' => 'nullable|string'
        ]);

        // Validation for past date and time
        $appointmentDateTime = \Carbon\Carbon::parse($request->date . ' ' . $request->time);
        if ($appointmentDateTime < now()) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede agendar un turno en una fecha u hora pasada.'
            ], 422);
        }

        $validated['created_by'] = \Auth::id();
        $validated['status'] = 'pending';

        $appointment = Appointment::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Turno agendado correctamente',
            'appointment' => $appointment
        ]);
    }

    /**
     * Delete an appointment via AJAX
     */
    public function destroy(Appointment $appointment)
    {
        $appointment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Turno eliminado correctamente'
        ]);
    }

    /**
     * Devuelve el status de cada día del mes para el mini-calendario.
     * Status posbiles: 'available' (verde), 'full' (rosa), 'unavailable' (gris)
     */
    public function getMonthAvailability(Request $request)
    {
        $doctorId = $request->doctor_id;
        $year = $request->year;
        $month = str_pad($request->month, 2, '0', STR_PAD_LEFT);

        if (!$doctorId) return response()->json([]);

        // Obtener configuración del médico
        $config = \App\Models\DoctorConfiguration::where('doctor_id', $doctorId)->first();
        $schedules = \App\Models\DoctorSchedule::where('doctor_id', $doctorId)->get();

        if (!$config && $schedules->isEmpty()) {
            return response()->json([]);
        }

        $scheduleMap = $schedules->keyBy('day_of_week');
        $workingDays = $config ? $config->working_days : null;
        $defaultDuration = $config->appointment_duration ?? 15;

        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $daysInMonth = $startDate->daysInMonth;
        $endDate = $startDate->copy()->endOfMonth();

        // Cargar todos los turnos del doctor en ese mes
        $appointments = Appointment::where('doctor_id', $doctorId)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->whereIn('status', ['pending', 'confirmed'])
            ->get()
            ->groupBy('date');

        // Cargar todos los bloqueos del doctor en este mes
        $blocks = \App\Models\ScheduleBlock::where('doctor_id', $doctorId)
            ->where(function($q) use ($startDate, $endDate) {
                $q->whereBetween('start_datetime', [$startDate->toDateString() . ' 00:00:00', $endDate->toDateString() . ' 23:59:59'])
                  ->orWhereBetween('end_datetime', [$startDate->toDateString() . ' 00:00:00', $endDate->toDateString() . ' 23:59:59'])
                  ->orWhere(function($qi) use ($startDate, $endDate) {
                      $qi->where('start_datetime', '<=', $startDate->toDateString() . ' 00:00:00')
                         ->where('end_datetime', '>=', $endDate->toDateString() . ' 23:59:59');
                  });
            })->get();

        $availability = [];

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $currentDate = Carbon::createFromDate($year, $month, $day);
            $dateString = $currentDate->format('Y-m-d');
            $dayOfWeek = $currentDate->dayOfWeek; // 0 (Sun) to 6 (Sat)

            // Determinar si este día está activo y sus intervalos
            $intervals = [];
            $slotDuration = $defaultDuration;

            if (is_array($workingDays)) {
                if (isset($workingDays[$dayOfWeek]) && is_array($workingDays[$dayOfWeek])) {
                    $dayCfg = $workingDays[$dayOfWeek];
                    if (!empty($dayCfg['active'])) {
                        if (!empty($dayCfg['morning_active']) && !empty($dayCfg['morning_start']) && !empty($dayCfg['morning_end'])) {
                            $intervals[] = [
                                'start' => Carbon::parse($dateString . ' ' . $dayCfg['morning_start']),
                                'end' => Carbon::parse($dateString . ' ' . $dayCfg['morning_end']),
                            ];
                        }
                        if (!empty($dayCfg['afternoon_active']) && !empty($dayCfg['afternoon_start']) && !empty($dayCfg['afternoon_end'])) {
                            $intervals[] = [
                                'start' => Carbon::parse($dateString . ' ' . $dayCfg['afternoon_start']),
                                'end' => Carbon::parse($dateString . ' ' . $dayCfg['afternoon_end']),
                            ];
                        }
                    }
                } elseif (in_array((string)$dayOfWeek, $workingDays) || in_array($dayOfWeek, $workingDays)) {
                    // Legacy array
                    if ($config->shift_1_start && $config->shift_1_end) {
                        $intervals[] = [
                            'start' => Carbon::parse($dateString . ' ' . $config->shift_1_start),
                            'end' => Carbon::parse($dateString . ' ' . $config->shift_1_end),
                        ];
                    }
                    if ($config->shift_2_start && $config->shift_2_end) {
                        $intervals[] = [
                            'start' => Carbon::parse($dateString . ' ' . $config->shift_2_start),
                            'end' => Carbon::parse($dateString . ' ' . $config->shift_2_end),
                        ];
                    }
                }
            } elseif (isset($scheduleMap[$dayOfWeek])) {
                $sch = $scheduleMap[$dayOfWeek];
                $slotDuration = $sch->slot_duration_minutes ?: $defaultDuration;
                $intervals[] = [
                    'start' => Carbon::parse($dateString . ' ' . $sch->start_time),
                    'end' => Carbon::parse($dateString . ' ' . $sch->end_time),
                ];
            }

            // Filtrar bloqueos que afecten a este día
            $dayBlocks = $blocks->filter(function($b) use ($dateString) {
                return $b->start_datetime->format('Y-m-d') <= $dateString && $b->end_datetime->format('Y-m-d') >= $dateString;
            });

            // Verificar si hay bloqueo de día completo o vacaciones para este día
            $isDayFullyBlocked = false;
            $fullBlockReason = null;
            foreach ($dayBlocks as $block) {
                $bStart = $block->start_datetime->format('Y-m-d H:i');
                $bEnd = $block->end_datetime->format('Y-m-d H:i');
                if ($bStart <= ($dateString . ' 00:05') && $bEnd >= ($dateString . ' 23:50')) {
                    $isDayFullyBlocked = true;
                    $fullBlockReason = $block->reason ?: 'Bloqueado';
                    break;
                }
            }

            if (empty($intervals)) {
                $availability[$dateString] = [
                    'status' => 'unavailable',
                    'percentage' => 0,
                    'is_blocked' => $isDayFullyBlocked,
                    'reason' => $fullBlockReason,
                ];
            } elseif ($isDayFullyBlocked) {
                $availability[$dateString] = [
                    'status' => 'blocked',
                    'percentage' => 0,
                    'is_blocked' => true,
                    'reason' => $fullBlockReason,
                ];
            } else {
                $totalSlotsPossible = 0;
                $blockedSlotsCount = 0;

                foreach ($intervals as $interval) {
                    $tempStart = clone $interval['start'];
                    $tempEndLimit = clone $interval['end'];

                    while ($tempStart < $tempEndLimit) {
                        $tempEnd = (clone $tempStart)->addMinutes($slotDuration);
                        $totalSlotsPossible++;

                        foreach ($dayBlocks as $block) {
                            if ($block->start_datetime < $tempEnd && $block->end_datetime > $tempStart) {
                                $blockedSlotsCount++;
                                break;
                            }
                        }

                        $tempStart->addMinutes($slotDuration);
                    }
                }

                $availableSlotsPossible = max(0, $totalSlotsPossible - $blockedSlotsCount);
                $bookedCount = isset($appointments[$dateString]) ? $appointments[$dateString]->count() : 0;

                if ($availableSlotsPossible <= 0) {
                    $status = 'blocked';
                    $percentage = 0;
                } else {
                    $status = 'available';
                    if ($bookedCount >= $availableSlotsPossible) {
                        $status = 'full';
                    }
                    $percentage = min(100, round(($bookedCount / $availableSlotsPossible) * 100));
                }

                $availability[$dateString] = [
                    'status' => $status,
                    'percentage' => $percentage,
                    'is_blocked' => false,
                    'available_slots' => $availableSlotsPossible,
                    'booked_slots' => $bookedCount,
                ];
            }
        }

        return response()->json($availability);
    }

    /**
     * Return list of exact time slots for a specific day, marking booked ones with full patient details.
     */
    public function getDaySlots(Request $request)
    {
        $doctorId = $request->doctor_id;
        $date = Carbon::parse($request->date);
        $dateStr = $date->format('Y-m-d');
        $dayOfWeek = $date->dayOfWeek; // 0=Sun, 1=Mon, ..., 6=Sat

        // 1. Configuración del médico
        $config = \App\Models\DoctorConfiguration::where('doctor_id', $doctorId)->first();
        $doctorSchedule = \App\Models\DoctorSchedule::where('doctor_id', $doctorId)->where('day_of_week', $dayOfWeek)->first();
        $slotDuration = $config->appointment_duration ?? ($doctorSchedule->slot_duration_minutes ?? 15);

        // 2. Determinar intervalos de atención del día
        $intervals = [];
        $workingDays = $config ? $config->working_days : null;

        if (is_array($workingDays)) {
            if (isset($workingDays[$dayOfWeek]) && is_array($workingDays[$dayOfWeek])) {
                $dayCfg = $workingDays[$dayOfWeek];
                if (!empty($dayCfg['active'])) {
                    if (!empty($dayCfg['morning_active']) && !empty($dayCfg['morning_start']) && !empty($dayCfg['morning_end'])) {
                        $intervals[] = [
                            'start' => Carbon::parse($dateStr . ' ' . $dayCfg['morning_start']),
                            'end' => Carbon::parse($dateStr . ' ' . $dayCfg['morning_end']),
                            'shift' => 'Mañana'
                        ];
                    }
                    if (!empty($dayCfg['afternoon_active']) && !empty($dayCfg['afternoon_start']) && !empty($dayCfg['afternoon_end'])) {
                        $intervals[] = [
                            'start' => Carbon::parse($dateStr . ' ' . $dayCfg['afternoon_start']),
                            'end' => Carbon::parse($dateStr . ' ' . $dayCfg['afternoon_end']),
                            'shift' => 'Tarde'
                        ];
                    }
                }
            } elseif (in_array((string)$dayOfWeek, $workingDays) || in_array($dayOfWeek, $workingDays)) {
                if ($config->shift_1_start && $config->shift_1_end) {
                    $intervals[] = [
                        'start' => Carbon::parse($dateStr . ' ' . $config->shift_1_start),
                        'end' => Carbon::parse($dateStr . ' ' . $config->shift_1_end),
                        'shift' => 'Mañana'
                    ];
                }
                if ($config->shift_2_start && $config->shift_2_end) {
                    $intervals[] = [
                        'start' => Carbon::parse($dateStr . ' ' . $config->shift_2_start),
                        'end' => Carbon::parse($dateStr . ' ' . $config->shift_2_end),
                        'shift' => 'Tarde'
                    ];
                }
            }
        } elseif ($doctorSchedule) {
            $intervals[] = [
                'start' => Carbon::parse($dateStr . ' ' . $doctorSchedule->start_time),
                'end' => Carbon::parse($dateStr . ' ' . $doctorSchedule->end_time),
                'shift' => 'General'
            ];
        }

        // 3. Bloqueos que toquen este día
        $blocks = \App\Models\ScheduleBlock::where('doctor_id', $doctorId)
            ->where(function($q) use ($dateStr) {
                $q->where('start_datetime', '<=', $dateStr . ' 23:59:59')
                  ->where('end_datetime', '>=', $dateStr . ' 00:00:00');
            })->get();

        // Detectar si hay un bloqueo general de día completo o vacaciones
        $fullDayBlock = $blocks->first(function($b) use ($dateStr) {
            $bStart = $b->start_datetime->format('Y-m-d H:i');
            $bEnd = $b->end_datetime->format('Y-m-d H:i');
            return ($bStart <= $dateStr . ' 00:05') && ($bEnd >= $dateStr . ' 23:50');
        });

        // 4. Turnos agendados con datos completos del paciente
        $appointments = Appointment::with('patient')->where('doctor_id', $doctorId)
            ->whereDate('date', $dateStr)
            ->whereIn('status', ['pending', 'confirmed', 'no_show'])
            ->orderBy('time')
            ->get()
            ->groupBy(function($item) {
                return Carbon::parse($item->time)->format('H:i:00');
            });

        $slots = [];
        $renderedAppointmentIds = [];
        $now = Carbon::now();

        foreach ($intervals as $interval) {
            $start = clone $interval['start'];
            $end = clone $interval['end'];

            while ($start < $end) {
                $timeString = $start->format('H:i:00');
                $timeDisplay = $start->format('H:i');
                $nextInterval = (clone $start)->addMinutes($slotDuration);
                $isPast = $start < $now;

                // Verificar si este turno cae dentro de un bloqueo
                $isBlocked = false;
                $blockReason = '';
                $blockId = null;

                foreach ($blocks as $block) {
                    if ($block->start_datetime < $nextInterval && $block->end_datetime > $start) {
                        $isBlocked = true;
                        $blockReason = $block->reason ?: 'Bloqueado';
                        $blockId = $block->id;
                        break;
                    }
                }

                if ($isBlocked) {
                    $conflictPatient = null;
                    if (isset($appointments[$timeString])) {
                        $app = $appointments[$timeString]->first();
                        if ($app && $app->patient) {
                            $conflictPatient = collect([$app->patient->last_name, $app->patient->first_name])->filter()->join(', ');
                        }
                    }
                    $slots[] = [
                        'time' => $timeDisplay,
                        'status' => 'blocked',
                        'reason' => $blockReason,
                        'block_id' => $blockId,
                        'conflict_patient' => $conflictPatient,
                        'is_past' => $isPast
                    ];
                } elseif (isset($appointments[$timeString])) {
                    foreach ($appointments[$timeString] as $idx => $app) {
                        $renderedAppointmentIds[] = $app->id;
                        $p = $app->patient;
                        $patientName = $p ? collect([$p->last_name, $p->first_name])->filter()->join(', ') : 'Paciente';
                        $isExtra = $idx > 0 || (bool)$app->is_overbooked;

                        $slots[] = [
                            'time' => $timeDisplay,
                            'status' => $app->status === 'no_show' ? 'no_show' : 'booked',
                            'patient_id' => $app->patient_id,
                            'patient_name' => $patientName,
                            'patient_dni' => $p->document_number ?? '',
                            'patient_phone' => $p->phone ?? '',
                            'patient_os' => $p->health_insurance ?? '',
                            'patient_affiliate' => $p->affiliate_number ?? '',
                            'reason' => $app->reason ?? '',
                            'notes' => $app->notes ?? '',
                            'appointment_id' => $app->id,
                            'is_extra' => $isExtra,
                            'is_past' => $isPast
                        ];
                    }
                } else {
                    $slots[] = [
                        'time' => $timeDisplay,
                        'status' => 'free',
                        'is_past' => $isPast
                    ];
                }

                $start->addMinutes($slotDuration);
            }
        }

        // 5. Turnos fuera de los intervalos regulares (sobreturnos en horarios atípicos)
        foreach ($appointments as $tStr => $appGroup) {
            foreach ($appGroup as $app) {
                if (!in_array($app->id, $renderedAppointmentIds)) {
                    $p = $app->patient;
                    $patientName = $p ? collect([$p->last_name, $p->first_name])->filter()->join(', ') : 'Paciente';
                    $appTime = Carbon::parse($dateStr . ' ' . $app->time);

                    $slots[] = [
                        'time' => $appTime->format('H:i'),
                        'status' => $app->status === 'no_show' ? 'no_show' : 'booked',
                        'patient_id' => $app->patient_id,
                        'patient_name' => $patientName,
                        'patient_dni' => $p->document_number ?? '',
                        'patient_phone' => $p->phone ?? '',
                        'patient_os' => $p->health_insurance ?? '',
                        'patient_affiliate' => $p->affiliate_number ?? '',
                        'reason' => $app->reason ?? '',
                        'notes' => $app->notes ?? '',
                        'appointment_id' => $app->id,
                        'is_extra' => true,
                        'is_past' => $appTime < $now
                    ];
                }
            }
        }

        // Ordenar cronológicamente
        usort($slots, function($a, $b) {
            return strcmp($a['time'], $b['time']);
        });

        return response()->json([
            'slots' => $slots,
            'slot_duration' => $slotDuration,
            'full_day_block' => $fullDayBlock ? [
                'id' => $fullDayBlock->id,
                'reason' => $fullDayBlock->reason ?: 'Día Bloqueado / Vacaciones',
                'start' => $fullDayBlock->start_datetime->format('d/m/Y'),
                'end' => $fullDayBlock->end_datetime->format('d/m/Y'),
            ] : null,
            'intervals_count' => count($intervals),
            'message' => empty($intervals) ? 'El profesional no tiene horario configurado para este día.' : ''
        ]);
    }

        public function getNearestSlots()
    {
        $doctors = User::role('médico')->with('schedules')->get();
        $results = [];

        foreach ($doctors as $doctor) {
            if ($doctor->schedules->isEmpty()) continue;
            
            $scheduleMap = $doctor->schedules->keyBy('day_of_week');
            
            // Limitamos a 60 días al futuro
            $found = null;
            $currentDate = Carbon::today();
            $now = Carbon::now();
            
            for ($i = 0; $i < 60; $i++) {
                $targetDate = $currentDate->copy()->addDays($i);
                $dayOfWeek = $targetDate->dayOfWeek;
                
                if (isset($scheduleMap[$dayOfWeek])) {
                    $sch = $scheduleMap[$dayOfWeek];
                    $dateStr = $targetDate->format('Y-m-d');
                    
                    // Buscar turnos reales en este día
                    $bookedAppts = Appointment::where('doctor_id', $doctor->id)
                        ->whereDate('date', $dateStr)
                        ->whereIn('status', ['pending', 'confirmed'])
                        ->pluck('time', 'time')
                        ->map(fn($t) => Carbon::parse($t)->format('H:i:00'))
                        ->toArray();

                    $start = Carbon::parse($dateStr . ' ' . $sch->start_time);
                    $end = Carbon::parse($dateStr . ' ' . $sch->end_time);

                    while ($start < $end) {
                        $timeStr = $start->format('H:i:00');
                        // No podemos dar un turno de hace minutos atrás en el día de HOY
                        if ($start > $now && !in_array($timeStr, $bookedAppts)) {
                            $found = [
                                'doctor_id' => $doctor->id,
                                'doctor_name' => $doctor->name,
                                'date' => $dateStr,
                                'time' => $start->format('H:i'),
                                'slot_duration' => $sch->slot_duration_minutes,
                                'formatted_date' => $targetDate->locale('es')->isoFormat('dddd D \d\e MMMM, YYYY') // Ej: Lunes 2 de Marzo, 2026
                            ];
                            break 2; // Romper ambos while y for loops para saltar al sig. doctor
                        }
                        $start->addMinutes($sch->slot_duration_minutes);
                    }
                }
            }

            if ($found) {
                $results[] = $found;
            }
        }

        return response()->json($results);
    }

    /**
     * Marcar un turno como ausente (no show)
     */
    public function markNoShow(Appointment $appointment)
    {
        $appointment->update([
            'status' => 'no_show'
        ]);

        return response()->json(['success' => true, 'message' => 'Turno marcado como ausente.']);
    }

    /**
     * Store a new ScheduleBlock via AJAX
     */
    public function storeBlock(Request $request)
    {
        $validated = $request->validate([
            'doctor_id' => 'required|exists:users,id',
            'date' => 'required|date',
            'end_date' => 'nullable|date',
            'block_type' => 'required|string|in:day,vacation,range_days,range,slot',
            'start_time' => 'nullable',
            'end_time' => 'nullable',
            'reason' => 'required|string|max:255',
        ]);

        $date = $validated['date'];

        if ($validated['block_type'] === 'vacation' || $validated['block_type'] === 'range_days') {
            $endDate = !empty($validated['end_date']) ? $validated['end_date'] : $date;
            $startDatetime = $date . ' 00:00:00';
            $endDatetime = $endDate . ' 23:59:59';
        } elseif ($validated['block_type'] === 'day') {
            $startDatetime = $date . ' 00:00:00';
            $endDatetime = $date . ' 23:59:59';
        } else {
            $startTime = !empty($validated['start_time']) ? $validated['start_time'] : '00:00';
            $endTime = !empty($validated['end_time']) ? $validated['end_time'] : '23:59';
            $startDatetime = $date . ' ' . $startTime . ':00';
            $endDatetime = $date . ' ' . $endTime . ':00';
        }

        if (Carbon::parse($startDatetime) >= Carbon::parse($endDatetime)) {
            return response()->json([
                'success' => false,
                'message' => 'La fecha u hora de fin debe ser posterior a la de inicio.'
            ], 422);
        }

        $block = \App\Models\ScheduleBlock::create([
            'doctor_id' => $validated['doctor_id'],
            'start_datetime' => $startDatetime,
            'end_datetime' => $endDatetime,
            'reason' => $validated['reason'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Bloqueo registrado exitosamente.',
            'block' => $block
        ]);
    }

        public function destroyBlock(\App\Models\ScheduleBlock $block)
    {
        $block->delete();

        return response()->json([
            'success' => true,
            'message' => 'Horario desbloqueado correctamente.'
        ]);
    }
}
