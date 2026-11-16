<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

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
        // Global Omnibar Injector: Los 3 Relojes Superiores con Persistencia de 14 Días
        View::composer(['layouts.admin', 'home'], function ($view) {
            $since14d = Carbon::now()->subDays(14)->startOfDay();

            $waitingAssignments = collect();
            $attendedPatients14d = collect();
            $consultedPatients = collect();
            $recentPatients = collect();

            if (!Auth::check() || !Schema::hasTable('patients')) {
                $view->with(compact('waitingAssignments', 'attendedPatients14d', 'recentPatients', 'consultedPatients'));
                return;
            }

            // -----------------------------------------------------------------
            // RELOJ 3: Pacientes en Sala de Espera HOY + Atendidos (Últimos 14 días)
            // -----------------------------------------------------------------
            if (Schema::hasTable('patient_assignments')) {
                // Activos hoy en sala de espera
                $activeRows = \App\Models\PatientAssignment::with(['patient', 'doctor'])
                    ->whereDate('created_at', Carbon::today())
                    ->whereIn('status', ['in_progress', 'pending'])
                    ->orderBy('started_at', 'asc')
                    ->get();

                // Agrupar por paciente para no duplicar si tiene actividades en paralelo
                $waitingAssignments = $activeRows->groupBy('patient_id')->map(function ($group) {
                    return $group->first();
                })->values();

                // Todos los pacientes que pasaron por atención / sala en los últimos 14 días
                $assign14d = \App\Models\PatientAssignment::select(
                        'patient_id',
                        DB::raw('MAX(COALESCE(ended_at, started_at, created_at)) as last_at')
                    )
                    ->where('created_at', '>=', $since14d)
                    ->groupBy('patient_id')
                    ->orderBy('last_at', 'desc')
                    ->get();

                if ($assign14d->isNotEmpty()) {
                    $pMap = \App\Models\Patient::whereIn('id', $assign14d->pluck('patient_id'))->get()->keyBy('id');
                    $attendedPatients14d = $assign14d->map(function ($row) use ($pMap) {
                        $p = $pMap->get($row->patient_id);
                        if (!$p) return null;
                        return [
                            'id' => $p->id,
                            'name' => $p->last_name . ', ' . $p->first_name,
                            'dni' => $p->dni,
                            'date' => Carbon::parse($row->last_at),
                        ];
                    })->filter()->values();
                }
            }

            // -----------------------------------------------------------------
            // RELOJ 2: Consultas / Evoluciones Clínicas (Últimos 14 días)
            // -----------------------------------------------------------------
            if (Schema::hasTable('visits')) {
                $latestVisits = \App\Models\Visit::select(
                        'patient_id',
                        DB::raw('MAX(COALESCE(updated_at, created_at)) as last_visited_at')
                    )
                    ->where(function ($q) use ($since14d) {
                        $q->where('created_at', '>=', $since14d)
                          ->orWhere('updated_at', '>=', $since14d);
                    })
                    ->groupBy('patient_id')
                    ->orderBy('last_visited_at', 'desc')
                    ->get();

                if ($latestVisits->isNotEmpty()) {
                    $pMap = \App\Models\Patient::whereIn('id', $latestVisits->pluck('patient_id'))->get()->keyBy('id');
                    $consultedPatients = $latestVisits->map(function ($v) use ($pMap) {
                        $p = $pMap->get($v->patient_id);
                        if (!$p) return null;
                        return [
                            'id' => $p->id,
                            'name' => $p->last_name . ', ' . $p->first_name,
                            'dni' => $p->dni,
                            'last_visited_at' => Carbon::parse($v->last_visited_at),
                        ];
                    })->filter()->values();
                }
            }

            // -----------------------------------------------------------------
            // RELOJ 1: Fichas Vistas / Revisadas o Modificadas (Últimos 14 días)
            // -----------------------------------------------------------------
            $viewsMap = []; // patient_id => Carbon timestamp

            // A) Leer registro persistente de aperturas de fichas en disco
            $logPath = storage_path('app/recent_patient_views.json');
            if (file_exists($logPath)) {
                $decoded = json_decode(@file_get_contents($logPath), true);
                if (is_array($decoded)) {
                    foreach ($decoded as $item) {
                        if (!empty($item['id']) && !empty($item['viewed_at'])) {
                            $dt = Carbon::parse($item['viewed_at']);
                            if ($dt->greaterThanOrEqualTo($since14d)) {
                                $pid = (int)$item['id'];
                                if (!isset($viewsMap[$pid]) || $dt->gt($viewsMap[$pid])) {
                                    $viewsMap[$pid] = $dt;
                                }
                            }
                        }
                    }
                }
            }

            // B) Combinar con actividad reciente real en BD (fichas actualizadas, consultas o atenciones en 14d)
            $recentDbPatients = \App\Models\Patient::where('updated_at', '>=', $since14d)
                ->orderBy('updated_at', 'desc')
                ->limit(100)
                ->get(['id', 'updated_at']);

            foreach ($recentDbPatients as $rp) {
                $dt = Carbon::parse($rp->updated_at);
                if (!isset($viewsMap[$rp->id]) || $dt->gt($viewsMap[$rp->id])) {
                    $viewsMap[$rp->id] = $dt;
                }
            }

            foreach ($consultedPatients as $cp) {
                $pid = $cp['id'];
                $dt = $cp['last_visited_at'];
                if (!isset($viewsMap[$pid]) || $dt->gt($viewsMap[$pid])) {
                    $viewsMap[$pid] = $dt;
                }
            }

            foreach ($attendedPatients14d as $ap) {
                $pid = $ap['id'];
                $dt = $ap['date'];
                if (!isset($viewsMap[$pid]) || $dt->gt($viewsMap[$pid])) {
                    $viewsMap[$pid] = $dt;
                }
            }

            if (!empty($viewsMap)) {
                // Ordenar cronológicamente descendente
                uasort($viewsMap, fn($a, $b) => $b->timestamp <=> $a->timestamp);
                $pIds = array_keys($viewsMap);
                $pModels = \App\Models\Patient::whereIn('id', $pIds)->get()->keyBy('id');

                $list = [];
                foreach ($viewsMap as $pid => $dt) {
                    $p = $pModels->get($pid);
                    if ($p) {
                        $list[] = [
                            'id' => $p->id,
                            'name' => $p->last_name . ', ' . $p->first_name,
                            'dni' => $p->dni,
                            'viewed_at' => $dt,
                        ];
                    }
                }
                $recentPatients = collect($list);
            }

            $view->with(compact('waitingAssignments', 'attendedPatients14d', 'recentPatients', 'consultedPatients'));
        });
    }
}
