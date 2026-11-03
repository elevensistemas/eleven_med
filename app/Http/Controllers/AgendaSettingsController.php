<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\ScheduleBlock;
use Illuminate\Http\Request;

class AgendaSettingsController extends Controller
{
    /**
     * Display the Agenda Settings screen.
     */
    public function index()
    {
        // Get doctors to populate the select drop-down
        $doctors = User::role('médico')->get();
        
        // Include upcoming existing blocks, eager load doctor info
        $blocks = ScheduleBlock::with('doctor')
                    ->where('end_datetime', '>=', now()->toDateString())
                    ->orderBy('start_datetime', 'asc')
                    ->get();
                    
        return view('agenda.settings', compact('doctors', 'blocks'));
    }

    /**
     * Store a new ScheduleBlock for a doctor.
     */
    public function storeBlock(Request $request)
    {
        $request->validate([
            'doctor_id' => 'required|exists:users,id',
            'start_datetime' => 'required|date',
            'end_datetime' => 'required|date|after_or_equal:start_datetime',
            'reason' => 'required|string|max:255',
        ]);

        ScheduleBlock::create([
            'doctor_id' => $request->doctor_id,
            'start_datetime' => $request->start_datetime,
            'end_datetime' => $request->end_datetime,
            'reason' => $request->reason,
        ]);

        return redirect()->route('agenda.settings')->with('success', 'El bloque de disponibilidad fue configurado exitosamente.');
    }

    /**
     * Delete an existing ScheduleBlock.
     */
    public function destroyBlock(ScheduleBlock $block)
    {
        $block->delete();
        
        return redirect()->route('agenda.settings')->with('success', 'Bloque horario eliminado. La agenda vuelve a estar libre.');
    }

    /**
     * Phase 19: Get Doctor Configuration
     */
    public function getConfig($doctorId)
    {
        $config = \App\Models\DoctorConfiguration::where('doctor_id', $doctorId)->first();
        if (!$config) {
            return response()->json(['exists' => false]);
        }
        return response()->json([
            'exists' => true,
            'config' => $config
        ]);
    }

    /**
     * Phase 19: Destroy Doctor Configuration and its schedules
     */
    public function destroyConfig($doctorId)
    {
        \App\Models\DoctorConfiguration::where('doctor_id', $doctorId)->delete();
        \App\Models\DoctorSchedule::where('doctor_id', $doctorId)->delete();

        return redirect()->route('agenda.settings')->with('success', 'Patrón de agenda eliminado completamente. El calendario ha sido liberado.');
    }

    /**
     * Store or update the base Doctor Configuration for the week.
     */
    public function storeConfig(Request $request)
    {
        $request->validate([
            'doctor_id' => 'required|exists:users,id',
            'appointment_duration' => 'required|integer',
            'days' => 'nullable|array',
            'working_days' => 'nullable|array',
        ]);

        $doctorId = $request->doctor_id;
        $appointmentDuration = (int)$request->appointment_duration;
        $daysData = $request->input('days');

        $formattedWorkingDays = [];
        $minMorningStart = null;
        $maxMorningEnd = null;
        $minAfternoonStart = null;
        $maxAfternoonEnd = null;

        if (is_array($daysData)) {
            // Configuración detallada por cada día (Lunes a Sábado)
            foreach (['1', '2', '3', '4', '5', '6'] as $dayKey) {
                $intKey = (int)$dayKey;
                $d = $daysData[$dayKey] ?? [];
                $isActive = !empty($d['active']);
                $mActive = $isActive && !empty($d['morning_active']);
                $aActive = $isActive && !empty($d['afternoon_active']);

                $mStart = ($mActive && !empty($d['morning_start'])) ? $d['morning_start'] : null;
                $mEnd = ($mActive && !empty($d['morning_end'])) ? $d['morning_end'] : null;
                $aStart = ($aActive && !empty($d['afternoon_start'])) ? $d['afternoon_start'] : null;
                $aEnd = ($aActive && !empty($d['afternoon_end'])) ? $d['afternoon_end'] : null;

                // Si se marcó el día como activo pero no se tildó ningún turno, no tiene horarios
                $hasAnyShift = ($mActive && $mStart && $mEnd) || ($aActive && $aStart && $aEnd);

                $formattedWorkingDays[$intKey] = [
                    'active' => $isActive && $hasAnyShift,
                    'morning_active' => $mActive && $mStart && $mEnd,
                    'morning_start' => $mStart,
                    'morning_end' => $mEnd,
                    'afternoon_active' => $aActive && $aStart && $aEnd,
                    'afternoon_start' => $aStart,
                    'afternoon_end' => $aEnd,
                ];

                if ($mStart && (!$minMorningStart || $mStart < $minMorningStart)) $minMorningStart = $mStart;
                if ($mEnd && (!$maxMorningEnd || $mEnd > $maxMorningEnd)) $maxMorningEnd = $mEnd;
                if ($aStart && (!$minAfternoonStart || $aStart < $minAfternoonStart)) $minAfternoonStart = $aStart;
                if ($aEnd && (!$maxAfternoonEnd || $aEnd > $maxAfternoonEnd)) $maxAfternoonEnd = $aEnd;
            }
        } elseif ($request->has('working_days')) {
            // Formato simple legacy
            $workingDays = $request->input('working_days', []);
            $shift1Start = $request->input('shift_1_start', '08:00');
            $shift1End = $request->input('shift_1_end', '12:00');
            $shift2Start = $request->input('shift_2_start');
            $shift2End = $request->input('shift_2_end');

            foreach (['1', '2', '3', '4', '5', '6'] as $dayKey) {
                $intKey = (int)$dayKey;
                $isActive = in_array($dayKey, $workingDays);
                $formattedWorkingDays[$intKey] = [
                    'active' => $isActive,
                    'morning_active' => $isActive,
                    'morning_start' => $shift1Start,
                    'morning_end' => $shift1End,
                    'afternoon_active' => $isActive && !empty($shift2Start) && !empty($shift2End),
                    'afternoon_start' => $shift2Start,
                    'afternoon_end' => $shift2End,
                ];
            }
            $minMorningStart = $shift1Start;
            $maxMorningEnd = $shift1End;
            $minAfternoonStart = $shift2Start;
            $maxAfternoonEnd = $shift2End;
        }

        // Guardar en DoctorConfiguration
        \App\Models\DoctorConfiguration::updateOrCreate(
            ['doctor_id' => $doctorId],
            [
                'working_days' => $formattedWorkingDays,
                'shift_1_start' => $minMorningStart ?: '08:00',
                'shift_1_end' => $maxMorningEnd ?: '12:00',
                'shift_2_start' => $minAfternoonStart,
                'shift_2_end' => $maxAfternoonEnd,
                'appointment_duration' => $appointmentDuration
            ]
        );

        // Sincronizar con DoctorSchedule para compatibilidad
        \App\Models\DoctorSchedule::where('doctor_id', $doctorId)->delete();

        foreach ($formattedWorkingDays as $dayOfWeek => $dayData) {
            if (!empty($dayData['active'])) {
                $startTime = null;
                $endTime = null;

                if (!empty($dayData['morning_active']) && !empty($dayData['morning_start'])) {
                    $startTime = $dayData['morning_start'];
                    $endTime = !empty($dayData['morning_end']) ? $dayData['morning_end'] : null;
                }
                if (!empty($dayData['afternoon_active']) && !empty($dayData['afternoon_end'])) {
                    if (!$startTime) {
                        $startTime = $dayData['afternoon_start'];
                    }
                    $endTime = $dayData['afternoon_end'];
                }

                if ($startTime && $endTime) {
                    \App\Models\DoctorSchedule::create([
                        'doctor_id' => $doctorId,
                        'day_of_week' => (int)$dayOfWeek,
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'slot_duration_minutes' => $appointmentDuration
                    ]);
                }
            }
        }

        return redirect()->route('agenda.settings')->with('success', 'Los horarios semanales personalizados por día se han guardado y sincronizado correctamente con la agenda.');
    }
}