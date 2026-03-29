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

            $view->with(compact('waitingAssignments', 'recentPatients'));
        });
    }
}
