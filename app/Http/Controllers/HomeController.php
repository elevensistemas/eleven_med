<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;

class HomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $now = now();

        // 1. KPI: Total Patients
        $totalPatients = \App\Models\Patient::count();

        // 2. KPI: New patients this month vs last month
        $newPatientsThisMonth = \App\Models\Patient::whereMonth('created_at', $now->month)
            ->whereYear('created_at', $now->year)->count();

        $newPatientsLastMonth = \App\Models\Patient::whereMonth('created_at', $now->copy()->subMonth()->month)
            ->whereYear('created_at', $now->copy()->subMonth()->year)->count();

        $growth = $newPatientsLastMonth > 0 ? (($newPatientsThisMonth - $newPatientsLastMonth) / $newPatientsLastMonth) * 100 : 100;

        // 3. KPI: Patients attended today / Slots calculation
        $dateStr = $now->toDateString();
        $totalFreeToday = 0;
        $totalExtraToday = 0;
        $totalBookedToday = 0;

        $doctors = \App\Models\User::role('médico')->orderBy('name')->get();
        $staffFilterList = \App\Models\User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['médico', 'técnico']);
        })->orderBy('name')->get();

        foreach ($doctors as $doctor) {
            $schedule = \App\Models\DoctorSchedule::where('doctor_id', $doctor->id)
                ->where('day_of_week', $now->dayOfWeek)
                ->first();

            if ($schedule) {
                $appointments = \App\Models\Appointment::where('doctor_id', $doctor->id)
                    ->whereDate('date', $dateStr)
                    ->whereIn('status', ['pending', 'confirmed', 'in_progress', 'completed'])
                    ->get()
                    ->groupBy('time')
                    ->toArray();

                $start = Carbon::parse($dateStr . ' ' . $schedule->start_time);
                $end = Carbon::parse($dateStr . ' ' . $schedule->end_time);

                while ($start <= $end) {
                    $timeString = $start->format('H:i:00');
                    $nextInterval = (clone $start)->addMinutes($schedule->slot_duration_minutes ?: 20);

                    if (isset($appointments[$timeString])) {
                        $totalBookedToday += count($appointments[$timeString]);
                        unset($appointments[$timeString]);
                    } else {
                        if ($start < $end) {
                            $totalFreeToday++;
                        }
                    }

                    if ($start < $end) {
                        foreach ($appointments as $tStr => $apps) {
                            if ($tStr > $timeString && $tStr < $nextInterval->format('H:i:00')) {
                                $totalExtraToday += count($apps);
                                $totalBookedToday += count($apps);
                                unset($appointments[$tStr]);
                            }
                        }
                    }

                    $start->addMinutes($schedule->slot_duration_minutes ?: 20);
                }

                foreach ($appointments as $tStr => $apps) {
                    $totalExtraToday += count($apps);
                    $totalBookedToday += count($apps);
                }
            } else {
                $appointmentsCount = \App\Models\Appointment::where('doctor_id', $doctor->id)
                    ->whereDate('date', $dateStr)
                    ->whereIn('status', ['pending', 'confirmed', 'in_progress', 'completed'])
                    ->count();
                $totalExtraToday += $appointmentsCount;
                $totalBookedToday += $appointmentsCount;
            }
        }

        $seenToday = \App\Models\Visit::whereDate('created_at', $now->toDateString())->count();
        if ($seenToday === 0) {
            $seenToday = \App\Models\PatientAssignment::whereDate('ended_at', $now->toDateString())->where('status', 'completed')->distinct('patient_id')->count();
        }
        $seenLast14Days = \App\Models\Visit::where('created_at', '>=', $now->copy()->subDays(14)->startOfDay())->count();
        if ($seenLast14Days === 0) {
            $seenLast14Days = \App\Models\PatientAssignment::where('ended_at', '>=', $now->copy()->subDays(14)->startOfDay())->where('status', 'completed')->distinct('patient_id')->count();
        }
        $attendedToday = $seenToday > 0 ? $seenToday : $totalBookedToday;

        // 4. CHART: Patients by Obra Social (Doughnut)
        $osListAll = \App\Models\ObraSocial::all();
        $patientsByObraSocial = \App\Models\Patient::selectRaw('COALESCE(NULLIF(obra_social, ""), "Particular") as os_name, count(*) as count')
            ->groupBy('os_name')
            ->orderByDesc('count')
            ->limit(6)
            ->get()
            ->map(function ($item) use ($osListAll) {
                if (strcasecmp($item->os_name, 'Particular') === 0) {
                    $item->color = '#198754';
                } else {
                    $item->color = '#5e6ad2';
                    foreach ($osListAll as $os) {
                        if (stripos($item->os_name, $os->name) !== false) {
                            $item->color = $os->color;
                            break;
                        }
                    }
                }
                return $item;
            });

        // 5. Available Years (from real clinical Visits & Studies)
        $minVisitDate = \App\Models\Visit::min('created_at');
        $minYear = $minVisitDate ? Carbon::parse($minVisitDate)->year : $now->year;
        if ($minYear < 2015) $minYear = 2015;
        $availableYears = range($now->year, $minYear);

        // Initial Cartesian Payload (Current Year, All Months, All Doctors)
        $initialAnalytics = $this->computeAnalyticsData($now->year, 'all', 'all');
        $annualData = [];
        foreach ($initialAnalytics['labels'] as $idx => $lbl) {
            $annualData[] = [
                'month' => $lbl,
                'total' => $initialAnalytics['visits_series'][$idx] ?? 0,
            ];
        }

        return view('home', compact(
            'totalPatients', 'newPatientsThisMonth', 'growth', 'attendedToday',
            'seenToday', 'seenLast14Days',
            'totalFreeToday', 'totalExtraToday', 'totalBookedToday',
            'patientsByObraSocial', 'annualData',
            'doctors', 'staffFilterList', 'availableYears', 'initialAnalytics'
        ));
    }

    /**
     * Endpoint AJAX para el Dashboard Analítico en Ejes Cartesianos (Consultas + Estudios por Mes/Año/Médico).
     */
    public function chartData(Request $request)
    {
        $year = (int)$request->query('year', now()->year);
        if ($year < 2000 || $year > 2100) $year = now()->year;

        $month = $request->query('month', 'all'); // 'all' o 1..12
        $doctorId = $request->query('doctor_id', 'all'); // 'all' o ID usuario

        return response()->json($this->computeAnalyticsData($year, $month, $doctorId));
    }

    protected function computeAnalyticsData(int $year, $month, $doctorId): array
    {
        $monthNames = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
        $isSpecificMonth = is_numeric($month) && (int)$month >= 1 && (int)$month <= 12;
        $monthInt = $isSpecificMonth ? (int)$month : null;
        $docInt = (is_numeric($doctorId) && (int)$doctorId > 0) ? (int)$doctorId : null;

        // 1. Consultas Reales (Tabla visits)
        $visitQ = \App\Models\Visit::whereYear('created_at', $year);
        if ($monthInt) $visitQ->whereMonth('created_at', $monthInt);
        if ($docInt) $visitQ->where('doctor_id', $docInt);

        // 2. Estudios Realizados (Tabla patient_studies + prácticas en Sala de Espera)
        $studyQ = \App\Models\PatientStudy::with(['patient:id,first_name,last_name,dni,code', 'uploader:id,name'])
            ->whereYear('created_at', $year);
        if ($monthInt) $studyQ->whereMonth('created_at', $monthInt);
        if ($docInt) $studyQ->where('uploaded_by', $docInt);

        $labels = [];
        $visitsSeries = [];
        $studiesSeries = [];

        if ($monthInt) {
            $daysInMonth = Carbon::createFromDate($year, $monthInt, 1)->daysInMonth;
            $vByDay = (clone $visitQ)->selectRaw('DAY(created_at) as d, count(*) as total')
                ->groupBy('d')->pluck('total', 'd');
            $sByDay = (clone $studyQ)->selectRaw('DAY(created_at) as d, count(*) as total')
                ->groupBy('d')->pluck('total', 'd');

            for ($d = 1; $d <= $daysInMonth; $d++) {
                $labels[] = str_pad((string)$d, 2, '0', STR_PAD_LEFT) . '/' . str_pad((string)$monthInt, 2, '0', STR_PAD_LEFT);
                $visitsSeries[] = (int)($vByDay[$d] ?? 0);
                $studiesSeries[] = (int)($sByDay[$d] ?? 0);
            }
        } else {
            $vByMonth = (clone $visitQ)->selectRaw('MONTH(created_at) as m, count(*) as total')
                ->groupBy('m')->pluck('total', 'm');
            $sByMonth = (clone $studyQ)->selectRaw('MONTH(created_at) as m, count(*) as total')
                ->groupBy('m')->pluck('total', 'm');

            for ($m = 1; $m <= 12; $m++) {
                $labels[] = $monthNames[$m - 1];
                $visitsSeries[] = (int)($vByMonth[$m] ?? 0);
                $studiesSeries[] = (int)($sByMonth[$m] ?? 0);
            }
        }

        // Desglose de Estudios por Tipo + Planilla reciente
        $studiesList = (clone $studyQ)->orderByDesc('created_at')->limit(100)->get();
        $studiesByType = (clone $studyQ)
            ->selectRaw('COALESCE(NULLIF(study_type, ""), "Estudio General") as tipo, count(*) as total')
            ->groupBy('tipo')
            ->orderByDesc('total')
            ->pluck('total', 'tipo')
            ->toArray();

        // Sumar también eventos de "Estudios Visuales" registrados en Sala de Espera en ese período si aplica
        $assignStudiesQ = \App\Models\PatientAssignment::with(['patient:id,first_name,last_name,dni,code', 'doctor:id,name'])
            ->where('event_type', 'Estudios Visuales')
            ->whereYear('created_at', $year);
        if ($monthInt) $assignStudiesQ->whereMonth('created_at', $monthInt);
        if ($docInt) $assignStudiesQ->where('doctor_id', $docInt);
        $assignStudiesCount = (clone $assignStudiesQ)->count();

        $studiesTable = [];
        foreach ($studiesList as $st) {
            $pt = $st->patient;
            $studiesTable[] = [
                'date' => $st->created_at ? $st->created_at->format('d/m/Y H:i') : '-',
                'patient_id' => $st->patient_id,
                'patient_name' => $pt ? ($pt->last_name . ', ' . $pt->first_name) : 'Paciente',
                'patient_number' => $pt ? $pt->patient_number : (string)$st->patient_id,
                'study_type' => $st->study_type ?: 'Estudio Adjunto',
                'detail' => $st->notes ?: ($st->original_name ?: '-'),
                'professional' => $st->uploader->name ?? 'Staff',
                'source' => 'Archivo HC',
            ];
        }

        if (count($studiesTable) < 50 && $assignStudiesCount > 0) {
            foreach ($assignStudiesQ->orderByDesc('created_at')->limit(30)->get() as $as) {
                $pt = $as->patient;
                $studiesTable[] = [
                    'date' => $as->created_at ? $as->created_at->format('d/m/Y H:i') : '-',
                    'patient_id' => $as->patient_id,
                    'patient_name' => $pt ? ($pt->last_name . ', ' . $pt->first_name) : 'Paciente',
                    'patient_number' => $pt ? $pt->patient_number : (string)$as->patient_id,
                    'study_type' => 'Estudio en Consultorio',
                    'detail' => $as->notes ?: 'Circuito de Estudios Visuales',
                    'professional' => $as->doctor->name ?? 'Staff Técnico',
                    'source' => 'Sala Estudios',
                ];
            }
        }

        return [
            'year' => $year,
            'month' => $monthInt ?: 'all',
            'doctor_id' => $docInt ?: 'all',
            'labels' => $labels,
            'visits_series' => $visitsSeries,
            'studies_series' => $studiesSeries,
            'total_visits' => array_sum($visitsSeries),
            'total_studies' => array_sum($studiesSeries) + $assignStudiesCount,
            'studies_by_type' => $studiesByType,
            'studies_table' => $studiesTable,
        ];
    }
}
