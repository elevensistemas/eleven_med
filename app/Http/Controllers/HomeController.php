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

        // 3. KPI: Patients attended today
        $attendedToday = \App\Models\Appointment::whereDate('date', $now->toDateString())
                                      ->whereIn('status', ['completed', 'confirmed', 'in_progress'])
                                      ->count();

        // 4. CHART: Patients by Obra Social (Doughnut)
        $patientsByObraSocial = \App\Models\Patient::selectRaw('COALESCE(obra_social, "Particular") as os_name, count(*) as count')
                                ->groupBy('os_name')
                                ->orderByDesc('count')
                                ->limit(5)
                                ->get();
                                
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
            'patientsByObraSocial', 'annualData'
        ));
    }
}
