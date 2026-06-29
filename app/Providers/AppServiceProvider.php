<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Phase 20: Global Omnibar Injector
        \Illuminate\Support\Facades\View::composer('layouts.admin', function ($view) {
            // 1. Pacientes en Espera (Status: 'esp')
            $waitingAssignments = collect();
            // Check if DB is ready to avoid migration errors
            if (\Illuminate\Support\Facades\Schema::hasTable('clinical_assignments')) {
                $waitingAssignments = \App\Models\ClinicalAssignment::with('patient', 'doctor')
                                        ->where('status', 'esp')
                                        ->orderBy('start_time', 'asc')
                                        ->get();
            }

            // 2. Últimos Pacientes Buscados
            $recentPatients = session()->get('recent_patients', []);

            // 3. Pacientes Consultados en los últimos 14 días
            $consultedPatients = collect();
            if (\Illuminate\Support\Facades\Schema::hasTable('visits') && \Illuminate\Support\Facades\Schema::hasTable('patients') && \Illuminate\Support\Facades\Auth::check()) {
                $latestVisits = \App\Models\Visit::select('patient_id', \Illuminate\Support\Facades\DB::raw('MAX(created_at) as last_visited_at'))
                    ->where('doctor_id', \Illuminate\Support\Facades\Auth::id())
                    ->where('created_at', '>=', \Carbon\Carbon::now()->subDays(14))
                    ->groupBy('patient_id')
                    ->orderBy('last_visited_at', 'desc')
                    ->get();

                if ($latestVisits->isNotEmpty()) {
                    $patientIds = $latestVisits->pluck('patient_id')->toArray();
                    $patients = \App\Models\Patient::whereIn('id', $patientIds)->get()->keyBy('id');

                    $consultedPatients = $latestVisits->map(function ($v) use ($patients) {
                        $patient = $patients->get($v->patient_id);
                        if ($patient) {
                            return [
                                'id' => $patient->id,
                                'name' => $patient->first_name . ' ' . $patient->last_name,
                                'dni' => $patient->dni,
                                'last_visited_at' => \Carbon\Carbon::parse($v->last_visited_at),
                            ];
                        }
                        return null;
                    })->filter()->values();
                }
            }

            $view->with(compact('waitingAssignments', 'recentPatients', 'consultedPatients'));
        });
    }
}
