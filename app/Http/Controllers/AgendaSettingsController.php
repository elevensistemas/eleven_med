<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\ScheduleBlock;
use App\Models\DoctorConfiguration;
use App\Models\DoctorSchedule;
use Spatie\Permission\Models\Role;
use Illuminate\Http\Request;

class AgendaSettingsController extends Controller
{
    protected function normalizeAgendaType(?string $type): string
    {
        return ($type === 'tecnica') ? 'tecnica' : 'medica';
    }

    protected function scopeAgendaType($query, string $agendaType)
    {
        if ($agendaType === 'tecnica') {
            return $query->where('agenda_type', 'tecnica');
        }
        return $query->where(function ($q) {
            $q->where('agenda_type', 'medica')->orWhereNull('agenda_type');
        });
    }

    /**
     * Construye el listado de agendas configurables separadas por función (Médica vs Técnica/Estudios).
     */
    public static function buildAgendaUnits(): array
    {
        try {
            Role::firstOrCreate(['name' => 'técnico', 'guard_name' => 'web']);
        } catch (\Throwable $e) {
            // Silencioso
        }

        $medicos = User::role('médico')->orderBy('name')->get();
        $tecnicos = User::role('técnico')->orderBy('name')->get();
        $configs = DoctorConfiguration::all();

        $units = [];

        foreach ($medicos as $doc) {
            $cfg = $configs->first(function ($c) use ($doc) {
                return (int)$c->doctor_id === (int)$doc->id && (($c->agenda_type ?? 'medica') === 'medica');
            });
            $customLabel = $cfg && !empty($cfg->agenda_label) ? $cfg->agenda_label : 'Consultorio Médico';
            $units[] = [
                'uid' => $doc->id . '_medica',
                'target_key' => $doc->id . '|medica',
                'doctor_id' => $doc->id,
                'agenda_type' => 'medica',
                'person_name' => $doc->name,
                'agenda_label' => $cfg->agenda_label ?? '',
                'badge' => 'Agenda Médica',
                'display_title' => 'Agenda Médica — ' . $doc->name . (!empty($cfg->agenda_label) ? ' (' . $cfg->agenda_label . ')' : ''),
                'short_option' => $doc->name . ' — Agenda Médica' . (!empty($cfg->agenda_label) ? ' (' . $cfg->agenda_label . ')' : ''),
                'subtitle' => $customLabel,
                'has_config' => (bool)$cfg,
            ];
        }

        foreach ($tecnicos as $tec) {
            $cfg = $configs->first(function ($c) use ($tec) {
                return (int)$c->doctor_id === (int)$tec->id && (($c->agenda_type ?? 'medica') === 'tecnica');
            });
            $customLabel = $cfg && !empty($cfg->agenda_label) ? $cfg->agenda_label : 'Estudios / Campos Visuales';
            $units[] = [
                'uid' => $tec->id . '_tecnica',
                'target_key' => $tec->id . '|tecnica',
                'doctor_id' => $tec->id,
                'agenda_type' => 'tecnica',
                'person_name' => $tec->name,
                'agenda_label' => $cfg->agenda_label ?? '',
                'badge' => 'Agenda Técnica / Estudios',
                'display_title' => 'Agenda Técnica — ' . $tec->name . ' (' . $customLabel . ')',
                'short_option' => $tec->name . ' — Agenda Técnica / Estudios (' . $customLabel . ')',
                'subtitle' => $customLabel,
                'has_config' => (bool)$cfg,
            ];
        }

        return $units;
    }

    /**
     * Display the Agenda Settings screen.
     */
    public function index()
    {
        $agendaUnits = self::buildAgendaUnits();
        $doctors = User::role('médico')->get();

        $blocks = ScheduleBlock::with('doctor')
            ->where('end_datetime', '>=', now()->subDays(30)->startOfDay())
            ->orderBy('start_datetime', 'asc')
            ->get();

        return view('agenda.settings', compact('doctors', 'agendaUnits', 'blocks'));
    }

    /**
     * Store a new ScheduleBlock for a doctor/técnico agenda.
     */
    public function storeBlock(Request $request)
    {
        if ($request->filled('agenda_target') && str_contains($request->input('agenda_target'), '|')) {
            [$dId, $aType] = explode('|', $request->input('agenda_target'), 2);
            $request->merge([
                'doctor_id' => (int)$dId,
                'agenda_type' => $this->normalizeAgendaType($aType),
            ]);
        }

        $agendaType = $this->normalizeAgendaType($request->input('agenda_type', 'medica'));

        if ($request->filled('start_date')) {
            $request->validate([
                'doctor_id' => 'required|exists:users,id',
                'start_date' => 'required|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
                'block_mode' => 'nullable|string|in:all_day,time_range',
                'start_time' => 'nullable|string',
                'end_time' => 'nullable|string',
                'reason' => 'required|string|max:255',
            ]);

            $startDate = $request->input('start_date');
            $endDate = $request->filled('end_date') ? $request->input('end_date') : $startDate;

            if ($request->input('block_mode') === 'time_range') {
                $sTime = $request->filled('start_time') ? substr($request->input('start_time'), 0, 5) . ':00' : '08:00:00';
                $eTime = $request->filled('end_time') ? substr($request->input('end_time'), 0, 5) . ':00' : '20:00:00';

                $cursor = \Illuminate\Support\Carbon::parse($startDate)->startOfDay();
                $endLimit = \Illuminate\Support\Carbon::parse($endDate)->startOfDay();

                while ($cursor->lte($endLimit)) {
                    $dStr = $cursor->format('Y-m-d');
                    if ($sTime < $eTime) {
                        ScheduleBlock::create([
                            'doctor_id' => $request->doctor_id,
                            'agenda_type' => $agendaType,
                            'start_datetime' => $dStr . ' ' . $sTime,
                            'end_datetime' => $dStr . ' ' . $eTime,
                            'reason' => $request->reason,
                        ]);
                    }
                    $cursor->addDay();
                }
            } else {
                ScheduleBlock::create([
                    'doctor_id' => $request->doctor_id,
                    'agenda_type' => $agendaType,
                    'start_datetime' => $startDate . ' 00:00:00',
                    'end_datetime' => $endDate . ' 23:59:59',
                    'reason' => $request->reason,
                ]);
            }

            return redirect()->route('agenda.settings')->with('success', 'El bloqueo de agenda fue registrado exitosamente.');
        }

        $request->validate([
            'doctor_id' => 'required|exists:users,id',
            'start_datetime' => 'required|date',
            'end_datetime' => 'required|date|after_or_equal:start_datetime',
            'reason' => 'required|string|max:255',
        ]);

        ScheduleBlock::create([
            'doctor_id' => $request->doctor_id,
            'agenda_type' => $agendaType,
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

        return redirect()->route('agenda.settings')->with('success', 'Bloqueo eliminado. Todos los días y horarios habituales vuelven a estar habilitados.');
    }

    /**
     * Delete all active/recent ScheduleBlocks of a doctor/técnico agenda at once.
     */
    public function destroyDoctorBlocks(Request $request, User $doctor)
    {
        $q = ScheduleBlock::where('doctor_id', $doctor->id)
            ->where('end_datetime', '>=', now()->subDays(30)->startOfDay());

        if ($request->filled('agenda_type')) {
            $this->scopeAgendaType($q, $this->normalizeAgendaType($request->input('agenda_type')));
        }
        $q->delete();

        return redirect()->route('agenda.settings')->with('success', 'Se liberaron los bloqueos de ' . $doctor->name . '. Su agenda habitual quedó totalmente habilitada.');
    }

    /**
     * Get Doctor / Técnico Configuration scoped by agenda_type.
     */
    public function getConfig(Request $request, $doctorId)
    {
        $agendaType = $this->normalizeAgendaType($request->query('agenda_type', 'medica'));
        $q = DoctorConfiguration::where('doctor_id', $doctorId);
        $this->scopeAgendaType($q, $agendaType);
        $config = $q->first();

        if (!$config) {
            return response()->json(['exists' => false, 'agenda_type' => $agendaType]);
        }
        return response()->json([
            'exists' => true,
            'config' => $config
        ]);
    }

    /**
     * Destroy Doctor / Técnico Configuration and its schedules scoped by agenda_type.
     */
    public function destroyConfig(Request $request, $doctorId)
    {
        $agendaType = $this->normalizeAgendaType($request->input('agenda_type', 'medica'));

        $cfgQ = DoctorConfiguration::where('doctor_id', $doctorId);
        $this->scopeAgendaType($cfgQ, $agendaType)->delete();

        $schQ = DoctorSchedule::where('doctor_id', $doctorId);
        $this->scopeAgendaType($schQ, $agendaType)->delete();

        return redirect()->route('agenda.settings')->with('success', 'Patrón de agenda eliminado completamente. El calendario ha sido liberado.');
    }

    /**
     * Store or update the base Weekly Configuration for a Medical or Technical Agenda.
     */
    public function storeConfig(Request $request)
    {
        if ($request->filled('agenda_target') && str_contains($request->input('agenda_target'), '|')) {
            [$dId, $aType] = explode('|', $request->input('agenda_target'), 2);
            $request->merge([
                'doctor_id' => (int)$dId,
                'agenda_type' => $this->normalizeAgendaType($aType),
            ]);
        }

        $request->validate([
            'doctor_id' => 'required|exists:users,id',
            'agenda_type' => 'nullable|string|in:medica,tecnica',
            'agenda_label' => 'nullable|string|max:120',
            'appointment_duration' => 'required|integer',
            'days' => 'nullable|array',
            'working_days' => 'nullable|array',
        ]);

        $doctorId = (int)$request->doctor_id;
        $agendaType = $this->normalizeAgendaType($request->input('agenda_type', 'medica'));
        $agendaLabel = trim((string)$request->input('agenda_label', '')) ?: null;
        $appointmentDuration = (int)$request->appointment_duration;
        $daysData = $request->input('days');

        $formattedWorkingDays = [];
        $minMorningStart = null;
        $maxMorningEnd = null;
        $minAfternoonStart = null;
        $maxAfternoonEnd = null;

        if (is_array($daysData)) {
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

        // Buscar configuración existente para (doctor_id, agenda_type)
        $existingQ = DoctorConfiguration::where('doctor_id', $doctorId);
        $this->scopeAgendaType($existingQ, $agendaType);
        $existingCfg = $existingQ->first();

        if ($existingCfg && is_array($existingCfg->working_days) && !empty($existingCfg->working_days['special_openings'])) {
            $formattedWorkingDays['special_openings'] = $existingCfg->working_days['special_openings'];
        }

        $payload = [
            'doctor_id' => $doctorId,
            'agenda_type' => $agendaType,
            'agenda_label' => $agendaLabel,
            'working_days' => $formattedWorkingDays,
            'shift_1_start' => $minMorningStart ?: '08:00',
            'shift_1_end' => $maxMorningEnd ?: '12:00',
            'shift_2_start' => $minAfternoonStart,
            'shift_2_end' => $maxAfternoonEnd,
            'appointment_duration' => $appointmentDuration
        ];

        if ($existingCfg) {
            $existingCfg->update($payload);
        } else {
            DoctorConfiguration::create($payload);
        }

        // Sincronizar con DoctorSchedule para esa agenda específica
        $delSchQ = DoctorSchedule::where('doctor_id', $doctorId);
        $this->scopeAgendaType($delSchQ, $agendaType)->delete();

        foreach ($formattedWorkingDays as $dayOfWeek => $dayData) {
            if (!is_numeric($dayOfWeek)) continue;
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
                    DoctorSchedule::create([
                        'doctor_id' => $doctorId,
                        'agenda_type' => $agendaType,
                        'day_of_week' => (int)$dayOfWeek,
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'slot_duration_minutes' => $appointmentDuration
                    ]);
                }
            }
        }

        return redirect()->route('agenda.settings')->with('success', 'Los horarios semanales de la agenda (' . ($agendaType === 'tecnica' ? 'Técnica / Estudios' : 'Médica') . ') se han guardado correctamente.');
    }
}
