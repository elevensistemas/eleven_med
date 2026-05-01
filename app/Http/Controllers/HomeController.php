<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the advanced application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
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

        $doctors = \App\Models\User::role('médico')->get();
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

                $start = \Carbon\Carbon::parse($dateStr . ' ' . $schedule->start_time);
                $end = \Carbon\Carbon::parse($dateStr . ' ' . $schedule->end_time);

                while ($start <= $end) {
                    $timeString = $start->format('H:i:00');
                    $nextInterval = (clone $start)->addMinutes($schedule->slot_duration_minutes);

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

                    $start->addMinutes($schedule->slot_duration_minutes);
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
        $attendedToday = $totalBookedToday;

        // 4. CHART: Patients by Obra Social (Doughnut)
        $patientsByObraSocial = \App\Models\Patient::selectRaw('COALESCE(obra_social, "Particular") as os_name, count(*) as count')
                                ->groupBy('os_name')
                                ->orderByDesc('count')
                                ->limit(5)
                                ->get()
                                ->map(function($item) {
                                    if ($item->os_name === 'Particular') {
                                        $item->color = '#198754';
                                    } else {
                                        $osList = \App\Models\ObraSocial::all();
                                        $item->color = '#5e6ad2'; // Default blue
                                        foreach ($osList as $os) {
                                            if (stripos($item->os_name, $os->name) !== false) {
                                                $item->color = $os->color;
                                                break;
                                            }
                                        }
                                    }
                                    return $item;
                                });
                                
        // 5. CHART: Monthly Attendances (Bar) Year-to-Date
        // Fetch appointments per month for current year
        $monthlyAppointments = \App\Models\Appointment::selectRaw('MONTH(date) as month, count(*) as total')
                                ->whereYear('date', $now->year)
                                ->whereIn('status', ['completed', 'confirmed', 'in_progress'])
                                ->groupBy('month')
                                ->orderBy('month')
                                ->get()
                                ->keyBy('month');
                                
        // Build an array of 12 months filling 0 where no data
        $annualData = [];
        $monthNames = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
        for ($i = 1; $i <= 12; $i++) {
            $annualData[] = [
                'month' => $monthNames[$i-1],
                'total' => isset($monthlyAppointments[$i]) ? $monthlyAppointments[$i]->total : 0
            ];
        }

        return view('home', compact(
            'totalPatients', 'newPatientsThisMonth', 'growth', 'attendedToday',
            'totalFreeToday', 'totalExtraToday', 'totalBookedToday',
            'patientsByObraSocial', 'annualData'
        ));
    }
}
