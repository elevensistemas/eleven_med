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
            'reason' => 'nullable|string',
            'is_overbooked' => 'nullable|boolean',
        ]);

        // Validation for past date (allow same-day appointments cleanly)
        $appointmentDate = \Carbon\Carbon::parse($request->date)->startOfDay();
        if ($appointmentDate < now()->startOfDay()) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede agendar un turno en una fecha pasada.'
            ], 422);
        }

        $normalizedTime = \Carbon\Carbon::parse($request->time)->format('H:i:s');
        $conflictExists = Appointment::where('doctor_id', $validated['doctor_id'])
            ->whereDate('date', $validated['date'])
            ->where('time', $normalizedTime)
            ->whereIn('status', ['pending', 'confirmed'])
            ->exists();

        $validated['time'] = $normalizedTime;
        $validated['is_overbooked'] = $conflictExists || $request->boolean('is_overbooked');
        $validated['created_by'] = \Auth::id();
        $validated['status'] = 'pending';

        $appointment = Appointment::create($validated);

        // Envío automático por WhatsApp al agendar (si está habilitado en reglas y en el formulario)
        $shouldSendWa = $request->has('send_whatsapp_present')
            ? $request->boolean('send_whatsapp')
            : true;

        $waQueued = false;
        if ($shouldSendWa) {
            try {
                $waRes = \App\Services\WhatsappDispatcher::dispatchForAppointment($appointment, 'instant_new');
                $waQueued = !empty($waRes['queued']);
            } catch (\Throwable $e) {
                // No interrumpir el agendado ante fallas externas
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Turno agendado correctamente' . ($waQueued ? ' (Confirmación enviada por WhatsApp)' : ''),
            'whatsapp_queued' => $waQueued,
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
     * Helper: Resuelve los intervalos horarios (habituales + aperturas especiales) de un médico en una fecha.
     */
    protected function buildDayIntervals(string $dateString, int $dayOfWeek, $config, $workingDays, $scheduleMap = null, $doctorSchedule = null): array
    {
        $intervals = [];

        if (is_array($workingDays)) {
            if (isset($workingDays[$dayOfWeek]) && is_array($workingDays[$dayOfWeek])) {
                $dayCfg = $workingDays[$dayOfWeek];
                if (!empty($dayCfg['active'])) {
                    if (!empty($dayCfg['morning_active']) && !empty($dayCfg['morning_start']) && !empty($dayCfg['morning_end'])) {
                        $intervals[] = [
                            'start' => Carbon::parse($dateString . ' ' . $dayCfg['morning_start']),
                            'end' => Carbon::parse($dateString . ' ' . $dayCfg['morning_end']),
                            'shift' => 'Mañana',
                            'is_special' => false,
                        ];
                    }
                    if (!empty($dayCfg['afternoon_active']) && !empty($dayCfg['afternoon_start']) && !empty($dayCfg['afternoon_end'])) {
                        $intervals[] = [
                            'start' => Carbon::parse($dateString . ' ' . $dayCfg['afternoon_start']),
                            'end' => Carbon::parse($dateString . ' ' . $dayCfg['afternoon_end']),
                            'shift' => 'Tarde',
                            'is_special' => false,
                        ];
                    }
                }
            } elseif (in_array((string)$dayOfWeek, $workingDays, true) || in_array($dayOfWeek, $workingDays, true)) {
                if ($config && $config->shift_1_start && $config->shift_1_end) {
                    $intervals[] = [
                        'start' => Carbon::parse($dateString . ' ' . $config->shift_1_start),
                        'end' => Carbon::parse($dateString . ' ' . $config->shift_1_end),
                        'shift' => 'Mañana',
                        'is_special' => false,
                    ];
                }
                if ($config && $config->shift_2_start && $config->shift_2_end) {
                    $intervals[] = [
                        'start' => Carbon::parse($dateString . ' ' . $config->shift_2_start),
                        'end' => Carbon::parse($dateString . ' ' . $config->shift_2_end),
                        'shift' => 'Tarde',
                        'is_special' => false,
                    ];
                }
            }
        } elseif ($scheduleMap && isset($scheduleMap[$dayOfWeek])) {
            $sch = $scheduleMap[$dayOfWeek];
            $intervals[] = [
                'start' => Carbon::parse($dateString . ' ' . $sch->start_time),
                'end' => Carbon::parse($dateString . ' ' . $sch->end_time),
                'shift' => 'General',
                'is_special' => false,
            ];
        } elseif ($doctorSchedule) {
            $intervals[] = [
                'start' => Carbon::parse($dateString . ' ' . $doctorSchedule->start_time),
                'end' => Carbon::parse($dateString . ' ' . $doctorSchedule->end_time),
                'shift' => 'General',
                'is_special' => false,
            ];
        }

        // Aperturas excepcionales para un día puntual (ej: día libre donde el médico habilita 2 hs)
        if (is_array($workingDays) && !empty($workingDays['special_openings'][$dateString]) && is_array($workingDays['special_openings'][$dateString])) {
            foreach ($workingDays['special_openings'][$dateString] as $sp) {
                if (!empty($sp['start']) && !empty($sp['end']) && $sp['start'] < $sp['end']) {
                    $intervals[] = [
                        'start' => Carbon::parse($dateString . ' ' . $sp['start']),
                        'end' => Carbon::parse($dateString . ' ' . $sp['end']),
                        'shift' => 'Especial',
                        'is_special' => true,
                    ];
                }
            }
        }

        return $intervals;
    }

    /**
     * Helper: Recorta / divide bloqueos superpuestos para liberar un intervalo [unlockStart, unlockEnd].
     */
    protected function carveOutBlocks(int $doctorId, Carbon $unlockStart, Carbon $unlockEnd, bool $fullDayMode = false): int
    {
        $overlapping = \App\Models\ScheduleBlock::where('doctor_id', $doctorId)
            ->where('start_datetime', '<', $unlockEnd)
            ->where('end_datetime', '>', $unlockStart)
            ->get();

        $modified = 0;

        foreach ($overlapping as $block) {
            $bStart = $block->start_datetime->copy();
            $bEnd = $block->end_datetime->copy();
            $reason = $block->reason;

            if ($bStart->gte($unlockStart) && $bEnd->lte($unlockEnd)) {
                // El bloqueo cae íntegramente dentro del rango a desbloquear
                $block->delete();
                $modified++;
            } elseif ($bStart->lt($unlockStart) && $bEnd->gt($unlockEnd)) {
                // El rango a desbloquear parte el bloqueo en dos mitades (antes y después)
                $leftEnd = $fullDayMode
                    ? $unlockStart->copy()->subDay()->endOfDay()
                    : $unlockStart->copy();
                $rightStart = $fullDayMode
                    ? $unlockEnd->copy()->addDay()->startOfDay()
                    : $unlockEnd->copy();

                if ($leftEnd->gt($bStart)) {
                    $block->update(['end_datetime' => $leftEnd]);
                } else {
                    $block->delete();
                }

                if ($bEnd->gt($rightStart)) {
                    \App\Models\ScheduleBlock::create([
                        'doctor_id' => $doctorId,
                        'start_datetime' => $rightStart,
                        'end_datetime' => $bEnd,
                        'reason' => $reason,
                    ]);
                }
                $modified++;
            } elseif ($bStart->lt($unlockStart)) {
                // Recortar borde derecho del bloqueo
                $newEnd = $fullDayMode
                    ? $unlockStart->copy()->subDay()->endOfDay()
                    : $unlockStart->copy();
                if ($newEnd->gt($bStart)) {
                    $block->update(['end_datetime' => $newEnd]);
                } else {
                    $block->delete();
                }
                $modified++;
            } else {
                // Recortar borde izquierdo del bloqueo
                $newStart = $fullDayMode
                    ? $unlockEnd->copy()->addDay()->startOfDay()
                    : $unlockEnd->copy();
                if ($bEnd->gt($newStart)) {
                    $block->update(['start_datetime' => $newStart]);
                } else {
                    $block->delete();
                }
                $modified++;
            }
        }

        return $modified;
    }

    /**
     * Devuelve el status de cada día del mes para el mini-calendario.
     * Status posibles: 'available' (verde), 'full' (rojo), 'partial_block' (violeta), 'blocked' (amarillo), 'unavailable' (gris)
     */
    public function getMonthAvailability(Request $request)
    {
        $doctorId = $request->doctor_id;
        $year = $request->year;
        $month = str_pad($request->month, 2, '0', STR_PAD_LEFT);

        if (!$doctorId) return response()->json([]);

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

        $appointments = Appointment::where('doctor_id', $doctorId)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->whereIn('status', ['pending', 'confirmed'])
            ->get()
            ->groupBy('date');

        $blocks = \App\Models\ScheduleBlock::where('doctor_id', $doctorId)
            ->where(function($q) use ($startDate, $endDate) {
                $q->where('start_datetime', '<=', $endDate->toDateString() . ' 23:59:59')
                  ->where('end_datetime', '>=', $startDate->toDateString() . ' 00:00:00');
            })->get();

        $availability = [];

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $currentDate = Carbon::createFromDate($year, $month, $day);
            $dateString = $currentDate->format('Y-m-d');
            $dayOfWeek = $currentDate->dayOfWeek;

            $slotDuration = $defaultDuration;
            if (!$config && isset($scheduleMap[$dayOfWeek])) {
                $slotDuration = $scheduleMap[$dayOfWeek]->slot_duration_minutes ?: $defaultDuration;
            }

            $intervals = $this->buildDayIntervals($dateString, $dayOfWeek, $config, $workingDays, $scheduleMap, null);

            $dayBlocks = $blocks->filter(function($b) use ($dateString) {
                return $b->start_datetime->format('Y-m-d') <= $dateString && $b->end_datetime->format('Y-m-d') >= $dateString;
            });

            $bookedCount = isset($appointments[$dateString]) ? $appointments[$dateString]->count() : 0;

            if (empty($intervals)) {
                // Día en que el médico habitualmente NO atiende (no marcar como amarillo bloqueado aunque cruce vacaciones)
                $availability[$dateString] = [
                    'status' => $bookedCount > 0 ? 'available' : 'unavailable',
                    'percentage' => $bookedCount > 0 ? 100 : 0,
                    'is_blocked' => false,
                    'reason' => null,
                ];
                continue;
            }

            $seenTimes = [];
            $totalSlotsPossible = 0;
            $blockedSlotsCount = 0;
            $firstBlockReason = null;

            foreach ($intervals as $interval) {
                $tempStart = clone $interval['start'];
                $tempEndLimit = clone $interval['end'];

                while ($tempStart < $tempEndLimit) {
                    $tKey = $tempStart->format('H:i');
                    if (isset($seenTimes[$tKey])) {
                        $tempStart->addMinutes($slotDuration);
                        continue;
                    }
                    $seenTimes[$tKey] = true;

                    $tempEnd = (clone $tempStart)->addMinutes($slotDuration);
                    $totalSlotsPossible++;

                    foreach ($dayBlocks as $block) {
                        if ($block->start_datetime < $tempEnd && $block->end_datetime > $tempStart) {
                            $blockedSlotsCount++;
                            if (!$firstBlockReason) {
                                $firstBlockReason = $block->reason ?: 'Bloqueado';
                            }
                            break;
                        }
                    }

                    $tempStart->addMinutes($slotDuration);
                }
            }

            $availableSlotsPossible = max(0, $totalSlotsPossible - $blockedSlotsCount);

            if ($totalSlotsPossible > 0 && $availableSlotsPossible <= 0) {
                // 100% de los turnos habituales bloqueados
                $availability[$dateString] = [
                    'status' => 'blocked',
                    'percentage' => 0,
                    'is_blocked' => true,
                    'reason' => $firstBlockReason ?: 'Día Bloqueado',
                    'available_slots' => 0,
                    'blocked_slots' => $blockedSlotsCount,
                    'booked_slots' => $bookedCount,
                ];
            } elseif ($blockedSlotsCount > 0 && $availableSlotsPossible > 0) {
                // Día PARCIALMENTE bloqueado (algunos horarios bloqueados y otros habilitados)
                $percentage = min(100, round(($bookedCount / $availableSlotsPossible) * 100));
                $availability[$dateString] = [
                    'status' => 'partial_block',
                    'percentage' => $percentage,
                    'is_blocked' => false,
                    'reason' => $firstBlockReason ?: 'Bloqueo Parcial',
                    'available_slots' => $availableSlotsPossible,
                    'blocked_slots' => $blockedSlotsCount,
                    'booked_slots' => $bookedCount,
                ];
            } else {
                $status = ($bookedCount >= $availableSlotsPossible && $availableSlotsPossible > 0) ? 'full' : 'available';
                $percentage = $availableSlotsPossible > 0 ? min(100, round(($bookedCount / $availableSlotsPossible) * 100)) : 0;

                $availability[$dateString] = [
                    'status' => $status,
                    'percentage' => $percentage,
                    'is_blocked' => false,
                    'available_slots' => $availableSlotsPossible,
                    'blocked_slots' => 0,
                    'booked_slots' => $bookedCount,
                ];
            }
        }

        return response()->json($availability);
    }

    /**
     * Return list of exact time slots for a specific day, keeping habitual slots visible even when blocked.
     */
    public function getDaySlots(Request $request)
    {
        $doctorId = (int)$request->doctor_id;
        $date = Carbon::parse($request->date);
        $dateStr = $date->format('Y-m-d');
        $dayOfWeek = $date->dayOfWeek;

        // 1. Configuración del médico
        $config = \App\Models\DoctorConfiguration::where('doctor_id', $doctorId)->first();
        $doctorSchedule = \App\Models\DoctorSchedule::where('doctor_id', $doctorId)->where('day_of_week', $dayOfWeek)->first();
        $slotDuration = $config->appointment_duration ?? ($doctorSchedule->slot_duration_minutes ?? 20);
        $workingDays = $config ? $config->working_days : null;

        // 2. Intervalos habituales + aperturas especiales del día
        $intervals = $this->buildDayIntervals($dateStr, $dayOfWeek, $config, $workingDays, null, $doctorSchedule);
        $specialOpenings = (is_array($workingDays) && !empty($workingDays['special_openings'][$dateStr]))
            ? array_values($workingDays['special_openings'][$dateStr])
            : [];

        // 3. Bloqueos que toquen este día
        $blocks = \App\Models\ScheduleBlock::where('doctor_id', $doctorId)
            ->where(function($q) use ($dateStr) {
                $q->where('start_datetime', '<=', $dateStr . ' 23:59:59')
                  ->where('end_datetime', '>=', $dateStr . ' 00:00:00');
            })
            ->orderBy('start_datetime')
            ->get();

        // Bloqueos activos / futuros del médico (para mostrar también en el panel inferior derecho de la agenda)
        $activeDoctorBlocks = \App\Models\ScheduleBlock::where('doctor_id', $doctorId)
            ->where('end_datetime', '>=', now()->startOfDay())
            ->orderBy('start_datetime')
            ->limit(15)
            ->get()
            ->map(function($b) {
                $sameDay = $b->start_datetime->format('Y-m-d') === $b->end_datetime->format('Y-m-d');
                return [
                    'id' => $b->id,
                    'reason' => $b->reason ?: 'Bloqueado',
                    'start_date' => $b->start_datetime->format('Y-m-d'),
                    'label' => $sameDay
                        ? ($b->start_datetime->format('d/m/Y') . ' (' . $b->start_datetime->format('H:i') . '-' . $b->end_datetime->format('H:i') . ')')
                        : ($b->start_datetime->format('d/m/Y') . ' al ' . $b->end_datetime->format('d/m/Y')),
                ];
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
        $seenSlotTimes = [];
        $renderedAppointmentIds = [];
        $now = Carbon::now();
        $blockedSlotsCount = 0;
        $totalRegularSlots = 0;

        foreach ($intervals as $interval) {
            $start = clone $interval['start'];
            $end = clone $interval['end'];

            while ($start < $end) {
                $timeString = $start->format('H:i:00');
                $timeDisplay = $start->format('H:i');

                if (isset($seenSlotTimes[$timeDisplay])) {
                    $start->addMinutes($slotDuration);
                    continue;
                }
                $seenSlotTimes[$timeDisplay] = true;

                $nextInterval = (clone $start)->addMinutes($slotDuration);
                $isPast = $start < $now;
                $totalRegularSlots++;

                // Verificar si este turno cae dentro de un bloqueo
                $isBlocked = false;
                $blockReason = '';
                $blockId = null;
                $blockIsMultiDay = false;
                $blockSpanLabel = '';

                foreach ($blocks as $block) {
                    if ($block->start_datetime < $nextInterval && $block->end_datetime > $start) {
                        $isBlocked = true;
                        $blockReason = $block->reason ?: 'Bloqueado';
                        $blockId = $block->id;
                        $blockIsMultiDay = $block->start_datetime->format('Y-m-d') !== $block->end_datetime->format('Y-m-d');
                        $blockSpanLabel = $blockIsMultiDay
                            ? ($block->start_datetime->format('d/m') . ' al ' . $block->end_datetime->format('d/m'))
                            : ($block->start_datetime->format('H:i') . ' a ' . $block->end_datetime->format('H:i'));
                        break;
                    }
                }

                if ($isBlocked) {
                    $blockedSlotsCount++;
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
                        'block_is_multi_day' => $blockIsMultiDay,
                        'block_span_label' => $blockSpanLabel,
                        'conflict_patient' => $conflictPatient,
                        'is_past' => $isPast,
                        'is_special' => !empty($interval['is_special']),
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
                            'patient_dni' => $p->dni ?? '',
                            'patient_phone' => $p->phone ?? '',
                            'patient_os' => $p->obra_social ?? '',
                            'patient_affiliate' => $p->affiliate_number ?? '',
                            'reason' => $app->reason ?? '',
                            'notes' => $app->notes ?? '',
                            'appointment_id' => $app->id,
                            'is_extra' => $isExtra,
                            'is_past' => $isPast,
                            'is_special' => !empty($interval['is_special']),
                        ];
                    }
                } else {
                    $slots[] = [
                        'time' => $timeDisplay,
                        'status' => 'free',
                        'is_past' => $isPast,
                        'is_special' => !empty($interval['is_special']),
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
                        'patient_dni' => $p->dni ?? '',
                        'patient_phone' => $p->phone ?? '',
                        'patient_os' => $p->obra_social ?? '',
                        'patient_affiliate' => $p->affiliate_number ?? '',
                        'reason' => $app->reason ?? '',
                        'notes' => $app->notes ?? '',
                        'appointment_id' => $app->id,
                        'is_extra' => true,
                        'is_past' => $appTime < $now,
                        'is_special' => false,
                    ];
                }
            }
        }

        usort($slots, function($a, $b) {
            return strcmp($a['time'], $b['time']);
        });

        $primaryBlock = $blocks->first();
        $isFullyBlocked = ($totalRegularSlots > 0 && $blockedSlotsCount >= $totalRegularSlots)
            || ($totalRegularSlots === 0 && $primaryBlock !== null);
        $isPartiallyBlocked = ($totalRegularSlots > 0 && $blockedSlotsCount > 0 && $blockedSlotsCount < $totalRegularSlots);

        $blockSummary = $primaryBlock ? [
            'id' => $primaryBlock->id,
            'reason' => $primaryBlock->reason ?: 'Bloqueo de Agenda',
            'start' => $primaryBlock->start_datetime->format('d/m/Y'),
            'end' => $primaryBlock->end_datetime->format('d/m/Y'),
            'is_multi_day' => $primaryBlock->start_datetime->format('Y-m-d') !== $primaryBlock->end_datetime->format('Y-m-d'),
            'blocked_slots' => $blockedSlotsCount,
            'free_slots' => max(0, $totalRegularSlots - $blockedSlotsCount),
        ] : null;

        return response()->json([
            'slots' => $slots,
            'slot_duration' => $slotDuration,
            'full_day_block' => $isFullyBlocked ? $blockSummary : null,
            'partial_day_block' => $isPartiallyBlocked ? $blockSummary : null,
            'special_openings' => $specialOpenings,
            'active_doctor_blocks' => $activeDoctorBlocks,
            'intervals_count' => count($intervals),
            'message' => empty($intervals) ? 'Día libre / Sin horario habitual configurado.' : ''
        ]);
    }

    public function getNearestSlots()
    {
        $doctors = User::role('médico')->with('schedules')->get();
        $results = [];

        foreach ($doctors as $doctor) {
            $config = \App\Models\DoctorConfiguration::where('doctor_id', $doctor->id)->first();
            if (!$config && $doctor->schedules->isEmpty()) continue;

            $scheduleMap = $doctor->schedules->keyBy('day_of_week');
            $workingDays = $config ? $config->working_days : null;
            $slotDuration = $config->appointment_duration ?? 20;

            $blocks = \App\Models\ScheduleBlock::where('doctor_id', $doctor->id)
                ->where('end_datetime', '>=', now())
                ->get();

            $found = null;
            $currentDate = Carbon::today();
            $now = Carbon::now();

            for ($i = 0; $i < 60; $i++) {
                $targetDate = $currentDate->copy()->addDays($i);
                $dayOfWeek = $targetDate->dayOfWeek;
                $dateStr = $targetDate->format('Y-m-d');

                $intervals = $this->buildDayIntervals($dateStr, $dayOfWeek, $config, $workingDays, $scheduleMap, null);
                if (empty($intervals)) continue;

                $bookedAppts = Appointment::where('doctor_id', $doctor->id)
                    ->whereDate('date', $dateStr)
                    ->whereIn('status', ['pending', 'confirmed'])
                    ->pluck('time')
                    ->map(fn($t) => Carbon::parse($t)->format('H:i:00'))
                    ->toArray();

                foreach ($intervals as $iv) {
                    $start = clone $iv['start'];
                    $end = clone $iv['end'];
                    $dur = (!$config && isset($scheduleMap[$dayOfWeek])) ? ($scheduleMap[$dayOfWeek]->slot_duration_minutes ?: $slotDuration) : $slotDuration;

                    while ($start < $end) {
                        $next = (clone $start)->addMinutes($dur);
                        $timeStr = $start->format('H:i:00');

                        $isBlocked = false;
                        foreach ($blocks as $b) {
                            if ($b->start_datetime < $next && $b->end_datetime > $start) {
                                $isBlocked = true;
                                break;
                            }
                        }

                        if (!$isBlocked && $start > $now && !in_array($timeStr, $bookedAppts)) {
                            $found = [
                                'doctor_id' => $doctor->id,
                                'doctor_name' => $doctor->name,
                                'date' => $dateStr,
                                'time' => $start->format('H:i'),
                                'slot_duration' => $dur,
                                'formatted_date' => $targetDate->locale('es')->isoFormat('dddd D \d\e MMMM, YYYY')
                            ];
                            break 3;
                        }
                        $start->addMinutes($dur);
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
     * Reprogramar un turno médico existente a una nueva fecha/hora (normal o sobreturno)
     */
    public function reschedule(Request $request, Appointment $appointment)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'time' => 'required|string',
            'doctor_id' => 'nullable|exists:users,id',
            'reason' => 'nullable|string|max:500',
            'is_overbooked' => 'nullable|boolean',
        ]);

        $oldDateFormatted = Carbon::parse($appointment->date)->format('d/m/Y');
        $oldTime = substr((string)$appointment->time, 0, 5);

        $targetDoctorId = !empty($validated['doctor_id']) ? (int)$validated['doctor_id'] : (int)$appointment->doctor_id;
        $normalizedTime = Carbon::parse($validated['time'])->format('H:i:s');

        $conflictExists = Appointment::where('doctor_id', $targetDoctorId)
            ->whereDate('date', $validated['date'])
            ->where('time', $normalizedTime)
            ->where('id', '!=', $appointment->id)
            ->whereIn('status', ['pending', 'confirmed'])
            ->exists();

        $appointment->date = $validated['date'];
        $appointment->time = $normalizedTime;
        $appointment->doctor_id = $targetDoctorId;
        $appointment->is_overbooked = $conflictExists || $request->boolean('is_overbooked');

        $note = "Reprogramado del {$oldDateFormatted} {$oldTime} hs.";
        $appointment->notes = trim(($appointment->notes ? $appointment->notes . ' | ' : '') . $note);
        if (!empty($validated['reason'])) {
            $appointment->reason = $validated['reason'];
        }
        $appointment->status = 'pending';
        $appointment->save();

        $shouldSendWa = $request->has('send_whatsapp') ? $request->boolean('send_whatsapp') : true;
        $waQueued = false;
        if ($shouldSendWa) {
            try {
                $waRes = \App\Services\WhatsappDispatcher::dispatchForAppointment($appointment, 'instant_reschedule');
                $waQueued = !empty($waRes['queued']);
            } catch (\Throwable $e) {
                // Silencioso
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Turno reprogramado exitosamente' . ($waQueued ? ' y notificado por WhatsApp.' : '.'),
            'whatsapp_queued' => $waQueued,
            'appointment' => $appointment
        ]);
    }

    /**
     * Enviar recordatorio puntual por WhatsApp desde el menú de un turno en la Agenda.
     */
    public function sendWhatsappNow(Appointment $appointment)
    {
        $res = \App\Services\WhatsappDispatcher::dispatchForAppointment($appointment, 'reminder', true);
        if (!empty($res['queued'])) {
            return response()->json([
                'success' => true,
                'message' => 'Recordatorio enviado a la cola de WhatsApp del consultorio.'
            ]);
        }
        return response()->json([
            'success' => false,
            'message' => 'El paciente no tiene un número de celular válido cargado en su ficha.'
        ], 422);
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

            if (Carbon::parse($startDatetime) >= Carbon::parse($endDatetime)) {
                return response()->json(['success' => false, 'message' => 'La fecha fin debe ser igual o posterior a la de inicio.'], 422);
            }

            $block = \App\Models\ScheduleBlock::create([
                'doctor_id' => $validated['doctor_id'],
                'start_datetime' => $startDatetime,
                'end_datetime' => $endDatetime,
                'reason' => $validated['reason'],
            ]);

            return response()->json(['success' => true, 'message' => 'Período bloqueado exitosamente.', 'block' => $block]);
        } elseif ($validated['block_type'] === 'day') {
            $block = \App\Models\ScheduleBlock::create([
                'doctor_id' => $validated['doctor_id'],
                'start_datetime' => $date . ' 00:00:00',
                'end_datetime' => $date . ' 23:59:59',
                'reason' => $validated['reason'],
            ]);

            return response()->json(['success' => true, 'message' => 'Día bloqueado exitosamente.', 'block' => $block]);
        } else {
            $startTime = !empty($validated['start_time']) ? substr($validated['start_time'], 0, 5) : '00:00';
            $endTime = !empty($validated['end_time']) ? substr($validated['end_time'], 0, 5) : '23:59';
            if ($startTime >= $endTime) {
                return response()->json(['success' => false, 'message' => 'La hora de fin debe ser posterior a la de inicio.'], 422);
            }

            $endDate = !empty($validated['end_date']) && $validated['end_date'] >= $date ? $validated['end_date'] : $date;
            $cursor = Carbon::parse($date)->startOfDay();
            $endLimit = Carbon::parse($endDate)->startOfDay();
            $lastBlock = null;

            while ($cursor->lte($endLimit)) {
                $dStr = $cursor->format('Y-m-d');
                $lastBlock = \App\Models\ScheduleBlock::create([
                    'doctor_id' => $validated['doctor_id'],
                    'start_datetime' => $dStr . ' ' . $startTime . ':00',
                    'end_datetime' => $dStr . ' ' . $endTime . ':00',
                    'reason' => $validated['reason'],
                ]);
                $cursor->addDay();
            }

            return response()->json(['success' => true, 'message' => 'Franja horaria bloqueada exitosamente.', 'block' => $lastBlock]);
        }
    }

    /**
     * Desbloqueo selectivo: un horario puntual, una franja horaria, un solo día dentro de vacaciones, o todo el bloqueo.
     */
    public function unblockRange(Request $request)
    {
        $validated = $request->validate([
            'doctor_id' => 'required|exists:users,id',
            'date' => 'required|date',
            'mode' => 'required|string|in:slot,time_range,day,all_block',
            'start_time' => 'nullable|string',
            'end_time' => 'nullable|string',
            'duration' => 'nullable|integer',
            'block_id' => 'nullable|integer',
        ]);

        $doctorId = (int)$validated['doctor_id'];
        $dateStr = Carbon::parse($validated['date'])->format('Y-m-d');
        $mode = $validated['mode'];

        if ($mode === 'all_block' && !empty($validated['block_id'])) {
            \App\Models\ScheduleBlock::where('id', $validated['block_id'])->where('doctor_id', $doctorId)->delete();
            return response()->json(['success' => true, 'message' => 'Período de bloqueo anulado por completo.']);
        }

        if ($mode === 'day') {
            $unlockStart = Carbon::parse($dateStr . ' 00:00:00');
            $unlockEnd = Carbon::parse($dateStr . ' 23:59:59');
            $this->carveOutBlocks($doctorId, $unlockStart, $unlockEnd, true);
            return response()->json(['success' => true, 'message' => 'Día desbloqueado correctamente.']);
        }

        if ($mode === 'slot') {
            $sTime = substr($validated['start_time'] ?? '08:00', 0, 5);
            $dur = (int)($validated['duration'] ?? 20);
            if ($dur <= 0) $dur = 20;
            $unlockStart = Carbon::parse($dateStr . ' ' . $sTime . ':00');
            $unlockEnd = (clone $unlockStart)->addMinutes($dur);
            $this->carveOutBlocks($doctorId, $unlockStart, $unlockEnd, false);
            return response()->json(['success' => true, 'message' => "Horario {$sTime} desbloqueado correctamente."]);
        }

        // mode === 'time_range'
        $sTime = substr($validated['start_time'] ?? '08:00', 0, 5);
        $eTime = substr($validated['end_time'] ?? '12:00', 0, 5);
        if ($sTime >= $eTime) {
            return response()->json(['success' => false, 'message' => 'La hora fin debe ser mayor a la hora inicio.'], 422);
        }
        $unlockStart = Carbon::parse($dateStr . ' ' . $sTime . ':00');
        $unlockEnd = Carbon::parse($dateStr . ' ' . $eTime . ':00');
        $this->carveOutBlocks($doctorId, $unlockStart, $unlockEnd, false);

        return response()->json(['success' => true, 'message' => "Franja {$sTime} a {$eTime} habilitada correctamente."]);
    }

    /**
     * Habilitar una franja horaria excepcional en un día libre o parcialmente bloqueado (ej: atender solo 2 hs).
     */
    public function storeSpecialOpening(Request $request)
    {
        $validated = $request->validate([
            'doctor_id' => 'required|exists:users,id',
            'date' => 'required|date',
            'start_time' => 'required|string',
            'end_time' => 'required|string',
        ]);

        $doctorId = (int)$validated['doctor_id'];
        $dateStr = Carbon::parse($validated['date'])->format('Y-m-d');
        $sTime = substr($validated['start_time'], 0, 5);
        $eTime = substr($validated['end_time'], 0, 5);

        if ($sTime >= $eTime) {
            return response()->json(['success' => false, 'message' => 'La hora de fin debe ser posterior a la hora de inicio.'], 422);
        }

        $config = \App\Models\DoctorConfiguration::firstOrCreate(
            ['doctor_id' => $doctorId],
            [
                'working_days' => [],
                'shift_1_start' => '08:00',
                'shift_1_end' => '12:00',
                'appointment_duration' => 20,
            ]
        );

        $workingDays = is_array($config->working_days) ? $config->working_days : [];
        if (!isset($workingDays['special_openings']) || !is_array($workingDays['special_openings'])) {
            $workingDays['special_openings'] = [];
        }
        if (!isset($workingDays['special_openings'][$dateStr]) || !is_array($workingDays['special_openings'][$dateStr])) {
            $workingDays['special_openings'][$dateStr] = [];
        }

        $workingDays['special_openings'][$dateStr][] = [
            'start' => $sTime,
            'end' => $eTime,
        ];

        $config->working_days = $workingDays;
        $config->save();

        // Si había un bloqueo en esa misma ventana horaria, recortarlo automáticamente
        $this->carveOutBlocks(
            $doctorId,
            Carbon::parse($dateStr . ' ' . $sTime . ':00'),
            Carbon::parse($dateStr . ' ' . $eTime . ':00'),
            false
        );

        return response()->json([
            'success' => true,
            'message' => "Franja especial ({$sTime} a {$eTime}) habilitada en este día."
        ]);
    }

    /**
     * Quitar las aperturas especiales de una fecha puntual.
     */
    public function removeSpecialOpening(Request $request)
    {
        $validated = $request->validate([
            'doctor_id' => 'required|exists:users,id',
            'date' => 'required|date',
        ]);

        $doctorId = (int)$validated['doctor_id'];
        $dateStr = Carbon::parse($validated['date'])->format('Y-m-d');
        $config = \App\Models\DoctorConfiguration::where('doctor_id', $doctorId)->first();

        if ($config && is_array($config->working_days) && isset($config->working_days['special_openings'][$dateStr])) {
            $workingDays = $config->working_days;
            unset($workingDays['special_openings'][$dateStr]);
            $config->working_days = $workingDays;
            $config->save();
        }

        return response()->json(['success' => true, 'message' => 'Horario especial quitado.']);
    }

    public function destroyBlock(\App\Models\ScheduleBlock $block)
    {
        $block->delete();

        return response()->json([
            'success' => true,
            'message' => 'Bloqueo anulado y agenda rehabilitada correctamente.'
        ]);
    }
}
