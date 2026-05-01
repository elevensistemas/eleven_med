@extends('layouts.admin')

@section('title', 'Consola Pacientes (Flujo Interactivo)')
@section('subtitle', 'Línea de tiempo unificada por paciente')

@section('content')

<style>
/* Background Image for Console */
body {
    background-image: url('{{ asset("images/console_bg.png") }}');
    background-size: cover;
    background-position: center;
    background-attachment: fixed;
    background-repeat: no-repeat;
    position: relative;
}
body::before {
    content: '';
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background-color: rgba(241, 245, 249, 0.88); /* Light mode gray/white overlay */
    z-index: -1;
}
body.theme-dark::before {
    background-color: rgba(15, 23, 42, 0.88); /* Dark mode dark blue/gray overlay */
}

/* Custom Styles Phase 15 - State Machine */
.history-card {
    background: #fcfcfc;
    border-color: #eee;
    filter: grayscale(100%);
    opacity: 0.8;
}
.history-card .patient-header {
    background: #f9f9f9;
    border-color: #eee;
}
.theme-dark .history-card {
    background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%) !important;
    border-color: #3b5998 !important;
    filter: none !important;
    opacity: 0.9 !important;
}
.theme-dark .history-card .patient-header {
    background: rgba(0,0,0,0.15) !important;
    border-color: rgba(255,255,255,0.1) !important;
}
.theme-dark .history-card h6.text-dark,
.theme-dark .history-card .text-muted,
.theme-dark .history-card .text-dark {
    color: #f8f9fa !important;
}

/* Fixes for dark mode white areas and requested light blue route bar */
.history-route-bar { background: #e0f2fe; border-top: 1px solid #bae6fd; }
body.theme-dark .history-route-bar { background: #1e3a8a !important; border-top-color: #1e40af !important; color: #bfdbfe !important; }
body.theme-dark .history-route-bar .badge { background: #0f172a !important; color: #e2e8f0 !important; border-color: #334155 !important; }

body.theme-dark .inner-table,
body.theme-dark .transition-form { background: #1e293b !important; border-color: #334155 !important; }
body.theme-dark .inner-table th { background: #0f172a !important; border-color: #334155 !important; color: #94a3b8 !important; }
body.theme-dark .inner-table td { border-color: #334155 !important; color: #f8f9fa !important; }
body.theme-dark .transition-form select { background: #0f172a !important; color: #f8f9fa !important; border-color: #334155 !important; }
body.theme-dark .transition-form small.text-dark { color: #f8f9fa !important; }

.patient-card {
    background: #FFFCF2; /* Clear yellowish requested */
    border: 1px solid #F6E2B4;
    border-radius: 12px;
    margin-bottom: 24px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.03);
    overflow: hidden;
}
.patient-header {
    padding: 15px 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid rgba(246, 226, 180, 0.5);
    background: #FFF9E6;
}
.patient-header-pill {
    font-weight: 600;
    color: #8D701C;
    font-size: 0.95rem;
}
.inner-table {
    background: #FFF;
    margin: 0;
    font-size: 0.9rem;
}
.inner-table th { background: #FAFAFA; font-weight: 600; text-transform: uppercase; border-bottom: 1px solid #eee; font-size:0.8rem; color:#888;}
.inner-table td { vertical-align: middle; border-bottom: 1px solid #f9f9f9; padding: 12px 8px; }
.transition-form { background: #fdfdfd; border-left: 1px solid #eee; padding: 15px; }

.btn-play { 
    background: #8e95ae; 
    color: #fff; 
    border-radius: 50%; 
    width: 45px; 
    height: 45px; 
    display: flex; 
    align-items: center; 
    justify-content: center; 
    padding-left: 4px; 
    border: none; 
    transition: 0.2s;
}
.btn-play:hover { background: #5E6AD2; transform: scale(1.1); box-shadow: 0 4px 10px rgba(94, 106, 210, 0.3); }

/* Blinking dot for live timer */
@keyframes blink { 50% { opacity: 0; } }
.live-dot { height: 8px; width: 8px; background-color: #dc3545; border-radius: 50%; display: inline-block; animation: blink 1s step-start infinite; margin-right: 5px; }
</style>

<!-- Top Action Bar -->
@php
    if (!function_exists('formatMins')) {
        function formatMins($mins) {
            $mins = floor($mins);
            if ($mins < 60) return $mins . ' min';
            $hrs = floor($mins / 60);
            $rem = $mins % 60;
            return $hrs . ' hr ' . ($rem > 0 ? $rem . ' min' : '');
        }
    }
@endphp
<div class="d-flex justify-content-between align-items-center mb-4">
    <button class="btn btn-primary rounded-pill px-4 py-2 shadow-sm fs-5 fw-bold d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#newArrivalModal" style="background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%); border:none;">
        <i class="bi bi-plus-lg text-white"></i> Ingresar Nuevo Paciente
    </button>
    <form action="{{ route('console.finishAll') }}" method="POST" onsubmit="return confirm('¿Estás seguro de FINALIZAR Y CERRAR la línea de tiempo de TODOS los pacientes activos en la cola?');">
        @csrf
        <button type="submit" class="btn btn-danger rounded-3 px-4 py-2 shadow-sm opacity-75 hover-opacity-100">
            Finalizar toda la cola
        </button>
    </form>
</div>

<!-- Loop de Pacientes Activos -->
@forelse($activePatients as $patient)
    @php
        $sortedAssigns = $patient->assignments->sortBy('started_at');
        $firstEvent = $sortedAssigns->first();
        $llegada = $firstEvent ? $firstEvent->started_at->format('H:i') : '--:--';
        $activeEvent = $patient->assignments->where('status', 'in_progress')->first();
        $currentStatus = $activeEvent ? $activeEvent->event_type : 'Terminado';
        
        // Sumar minutos por categoría
        $totals = ['Dilatación' => 0, 'Ingreso (espera)' => 0];
        foreach($patient->assignments as $a) {
            $end = $a->ended_at ?? \Carbon\Carbon::now();
            $diff = $a->started_at->diffInMinutes($end);
            if(isset($totals[$a->event_type])) { $totals[$a->event_type] += $diff; }
        }
    @endphp

    <div class="patient-card">
        <!-- Header -->
        <div class="patient-header">
            <div class="d-flex align-items-center gap-3" style="width: 25%;">
                @if($patient->photo_path)
                    <img src="{{ Storage::url($patient->photo_path) }}" alt="Foto" class="rounded-circle object-fit-cover shadow-sm" style="width: 50px; height: 50px; border: 2px solid var(--bs-warning);">
                @else
                    <div class="bg-warning bg-opacity-10 p-2 rounded-circle text-warning d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                        <i class="bi bi-person-fill fs-3"></i>
                    </div>
                @endif
                <div>
                    <h6 class="mb-0 fw-bold text-dark fs-5 text-capitalize d-flex align-items-center">
                        {{ mb_strtolower($patient->last_name) }}, {{ mb_strtolower($patient->first_name) }}
                        <button class="btn btn-sm btn-link p-0 ms-2" title="Agregar más pasos a este paciente" data-bs-toggle="modal" data-bs-target="#addMoreAssignmentsModal-{{ $patient->id }}">
                            <i class="bi bi-person-plus-fill" style="color: #7e448b; font-size: 1.3rem;"></i>
                        </button>
                    </h6>
                    <small class="text-muted text-uppercase" style="font-size:0.75rem;">OS: {{ $patient->obra_social ?? 'Particular' }}</small>
                </div>
            </div>
            
            <div class="d-flex justify-content-between align-items-center flex-grow-1 px-4">
                <div class="text-center">
                    <small class="text-muted d-block text-uppercase" style="font-size:0.7rem;">Llegada</small>
                    <span class="patient-header-pill">{{ $llegada }}</span>
                </div>
                <div class="text-center">
                    <small class="text-muted d-block text-uppercase" style="font-size:0.7rem;">Estado Central</small>
                    <span class="badge bg-warning bg-opacity-25 text-dark fw-bold border border-warning px-3 py-1 rounded-pill">{{ $currentStatus }}</span>
                </div>
                <div class="text-center">
                    <small class="text-muted d-block text-uppercase" style="font-size:0.7rem;">Espera Inicial</small>
                    <span class="patient-header-pill">{{ formatMins($totals['Ingreso (espera)']) }}</span>
                </div>
                <div class="text-center">
                    <small class="text-muted d-block text-uppercase" style="font-size:0.7rem;">Dilatación</small>
                    <span class="patient-header-pill text-danger">{{ formatMins($totals['Dilatación']) }}</span>
                </div>
                <div class="text-center">
                    <small class="text-muted d-block text-uppercase" style="font-size:0.7rem;">Total Día</small>
                    @if($firstEvent)
                        <span class="badge bg-dark px-3 py-1 rounded-pill total-day-timer" data-start="{{ $firstEvent->started_at->toIso8601String() }}">0'</span>
                    @else
                        <span class="badge bg-dark px-3 py-1 rounded-pill">0'</span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Body -->
        <div class="d-flex w-100">
            <!-- Journey Internal Table -->
            <div class="flex-grow-1">
                <table class="table inner-table mb-0">
                    <thead>
                        <tr>
                            <th class="ps-5">Ingreso</th>
                            <th>Fin</th>
                            <th>Estado</th>
                            <th>Transcurrido</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($sortedAssigns as $a)
                            <tr>
                                <td class="ps-5 text-secondary">{{ $a->started_at ? $a->started_at->format('H:i') : '--:--' }}</td>
                                <td class="text-secondary">{{ $a->ended_at ? $a->ended_at->format('H:i') : '' }}</td>
                                <td>
                                    <span class="fw-bold text-dark">{{ $a->event_type }}</span>
                                </td>
                                <td>
                                    @if($a->status === 'in_progress')
                                        <div class="text-dark fw-bold flash-timer" data-start="{{ $a->started_at ? $a->started_at->toIso8601String() : '' }}">
                                            <span class="live-dot"></span> 0 min
                                        </div>
                                    @elseif($a->status === 'pending')
                                        <span class="text-muted fw-bold"><i class="bi bi-hourglass-split"></i> En cola</span>
                                    @else
                                        @php
                                            $diff = ($a->started_at && $a->ended_at) ? $a->started_at->diff($a->ended_at) : null;
                                            $timeStr = $diff ? ($diff->h > 0 ? $diff->h . 'hr ' . $diff->i . ' m' : $diff->i . ' min.') : '--';
                                        @endphp
                                        <span class="text-muted">{{ $timeStr }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Action Transition Right Side -->
            <div class="transition-form d-flex align-items-center justify-content-center" style="min-width: 320px;">
                @if($activeEvent)
                <form action="{{ route('console.assignments.transition', $patient) }}" method="POST" class="d-flex align-items-center gap-3 w-100 m-0">
                    @csrf
                    <div class="flex-grow-1">
                        <small class="text-dark fw-bold d-block mb-1" style="font-size:0.8rem;">Pendiente</small>
                        <select name="next_step" class="form-select bg-white border border-secondary border-opacity-25 shadow-sm py-2 text-dark" required>
                            <option value="AUTO" class="fw-bold text-primary">Siguiente paso en la cola</option>
                            <option value="Ingreso (espera)">Ingreso (espera)</option>
                            <option value="Dilatación" {{ $currentStatus == 'Ingreso (espera)' ? 'selected' : '' }}>Dilatación</option>
                            <option value="Estudios Visuales">Estudios Visuales</option>
                            <option value="Atención Médica" {{ $currentStatus == 'Dilatación' ? 'selected' : '' }}>Atención Médica</option>
                            <option value="FINALIZAR" class="text-danger fw-bold">✓ Alta Final</option>
                        </select>
                    </div>
                    <div>
                        <button type="submit" class="btn-play shadow mt-3" title="Terminar actual e Iniciar seleccionado">
                            <i class="bi bi-play-fill fs-3"></i>
                        </button>
                    </div>
                </form>
                @else
                    <div class="text-center text-muted">
                        <i class="bi bi-check-circle-fill fs-3 text-success mb-2 d-block"></i>
                        <span class="fw-bold">Flujo Finalizado</span>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Modal Append Assignment for {{ $patient->first_name }} -->
    <div class="modal fade" id="addMoreAssignmentsModal-{{ $patient->id }}" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 rounded-4 shadow-lg">
                <div class="modal-header border-0 bg-light rounded-top-4 pb-2">
                    <h5 class="modal-title fw-bold text-dark"><i class="bi bi-person-plus-fill" style="color: #7e448b;"></i> Agregar Pasos al Flujo - {{ $patient->first_name }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('console.assignments.append', $patient) }}" method="POST">
                    @csrf
                    <input type="hidden" name="patient_id" value="{{ $patient->id }}">
                    <div class="modal-body pt-4">
                        <div class="d-flex justify-content-between align-items-end mb-3">
                            <h6 class="fw-bold mb-0 text-dark">Nuevos Pasos a la Cola</h6>
                            <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold" onclick="addAppendRow({{ $patient->id }})">
                                <i class="bi bi-plus-circle-fill"></i> Agregar Asignación
                            </button>
                        </div>
                        <div id="appendContainer-{{ $patient->id }}">
                            <div class="row gx-3 align-items-end mb-3" id="append-row-{{ $patient->id }}-0">
                                <div class="col-md-5">
                                    <label class="form-label text-muted small fw-bold">Tipo</label>
                                    <select name="event_types[]" class="form-select border-secondary border-opacity-25 shadow-sm" required>
                                        <option value="Ingreso (espera)">Ingreso (espera)</option>
                                        <option value="Dilatación">Dilatación</option>
                                        <option value="Estudios Visuales">Estudios Visuales</option>
                                        <option value="Atención Médica" selected>Atención Médica</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-muted small fw-bold">Médico</label>
                                    <select name="doctor_ids[]" class="form-select border-secondary border-opacity-25 shadow-sm">
                                        <option value="">Cualquier médico / Staff</option>
                                        @foreach($doctors as $d)
                                            <option value="{{ $d->id }}">{{ $d->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-1 text-center">
                                    <button type="button" class="btn btn-link text-danger p-0 mt-4" style="font-size: 1.2rem;" disabled>
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0 pb-4 px-4 d-flex">
                        <button type="submit" class="btn btn-primary w-100 py-2 rounded-pill fw-bold" style="background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%); border:none;">
                            Agregar a la Cola <i class="bi bi-send-fill ms-1"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@empty
    <div class="text-center py-5 empty-waiting-room" style="min-height: 400px; display: flex; flex-direction: column; align-items: center; justify-content: center; background-image: url('{{ asset('images/empty_waiting_room.png') }}'); background-size: contain; background-position: center; background-repeat: no-repeat; border-radius: 1.5rem; position: relative;">
        <!-- Semi-transparent overlay to ensure text readability in both light and dark modes -->
        <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(255,255,255,0.6); border-radius: 1.5rem;" class="bg-overlay-light"></div>
        <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(17, 24, 39, 0.8); border-radius: 1.5rem; display: none;" class="bg-overlay-dark"></div>
        
        <div style="position: relative; z-index: 2; padding: 2rem; background: rgba(255,255,255,0.95); border-radius: 1.5rem; box-shadow: 0 10px 30px rgba(0,0,0,0.08); max-width: 500px; backdrop-filter: blur(5px);" class="empty-state-card">
            <div class="mb-3">
                <i class="bi bi-door-open" style="font-size: 3.5rem; color: #5e6ad2;"></i>
            </div>
            <h4 class="fw-bold" style="color: #2c3e50;">Sala de Espera Despejada</h4>
            <p class="text-muted mb-0">Ningún paciente ha ingresado aún. Pulsa el botón superior para ingresar al primer paciente del día.</p>
        </div>
    </div>

    <style>
        body.theme-dark .bg-overlay-light { display: none !important; }
        body.theme-dark .bg-overlay-dark { display: block !important; }
        body.theme-dark .empty-state-card { background: rgba(30, 41, 59, 0.95) !important; box-shadow: 0 10px 30px rgba(0,0,0,0.5) !important; }
        body.theme-dark .empty-state-card h4 { color: #f8f9fa !important; }
        body.theme-dark .empty-state-card p.text-muted { color: #adb5bd !important; }
    </style>
@endforelse

@if($finishedPatients->count() > 0)
    <div class="mt-5 mb-4 d-flex align-items-center gap-3 opacity-75">
        <h5 class="fw-bold text-muted mb-0"><i class="bi bi-archive-fill me-2"></i> Historial (Dados de Alta Hoy)</h5>
        <div class="flex-grow-1 border-bottom"></div>
    </div>

    @foreach($finishedPatients as $patient)
    @php
        $sortedAssigns = $patient->assignments->sortBy('started_at');
        $firstEvent = $sortedAssigns->first();
        $llegada = $firstEvent ? $firstEvent->started_at->format('H:i') : '--:--';
        $lastEvent = $sortedAssigns->last();
        $salida = $lastEvent && $lastEvent->ended_at ? $lastEvent->ended_at->format('H:i') : '--:--';
        
        // Sumar minutos por categoría
        $totals = ['Dilatación' => 0, 'Ingreso (espera)' => 0];
        $totalMinutes = 0;
        foreach($patient->assignments as $a) {
            $end = $a->ended_at ?? \Carbon\Carbon::now();
            $diff = $a->started_at->diffInMinutes($end);
            $totalMinutes += $diff;
            if(isset($totals[$a->event_type])) { $totals[$a->event_type] += $diff; }
        }
    @endphp
    <div class="patient-card history-card" style="margin-bottom: 16px;">
        <div class="patient-header">
            <div class="d-flex align-items-center gap-3" style="width: 25%;">
                @if($patient->photo_path)
                    <img src="{{ Storage::url($patient->photo_path) }}" alt="Foto" class="rounded-circle object-fit-cover shadow-sm" style="width: 50px; height: 50px; border: 2px solid var(--bs-secondary); filter: grayscale(50%);">
                @else
                    <div class="bg-secondary bg-opacity-10 p-2 rounded-circle text-secondary d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                        <i class="bi bi-person-check-fill fs-3"></i>
                    </div>
                @endif
                <div>
                    <h6 class="mb-0 fw-bold text-dark fs-5 text-capitalize">{{ mb_strtolower($patient->last_name) }}, {{ mb_strtolower($patient->first_name) }}</h6>
                    <small class="text-muted text-uppercase" style="font-size:0.75rem;">Alta Diaria</small>
                </div>
            </div>
            <div class="d-flex justify-content-between align-items-center flex-grow-1 px-4">
                <div class="text-center">
                    <small class="text-muted d-block text-uppercase" style="font-size:0.7rem;">Ingresó</small>
                    <span class="patient-header-pill text-muted">{{ $llegada }}</span>
                </div>
                <div class="text-center">
                    <small class="text-muted d-block text-uppercase" style="font-size:0.7rem;">Salió</small>
                    <span class="patient-header-pill text-muted">{{ $salida }}</span>
                </div>
                <div class="text-center">
                    <small class="text-muted d-block text-uppercase" style="font-size:0.7rem;">Dilatación</small>
                    <span class="patient-header-pill text-muted">{{ formatMins($totals['Dilatación']) }}</span>
                </div>
                <div class="text-center">
                    <small class="text-muted d-block text-uppercase" style="font-size:0.7rem;">Tiempo Total</small>
                    <span class="badge bg-secondary px-3 py-1 rounded-pill">{{ formatMins($totalMinutes) }}</span>
                </div>
            </div>
        </div>
        <div class="px-4 py-2 history-route-bar d-flex justify-content-between align-items-center">
            <div>
                <small class="text-muted fw-bold">Recorrido:</small> 
                @foreach($sortedAssigns as $a)
                    <span class="badge bg-light text-dark border me-1">{{ $a->event_type }} ({{ formatMins($a->started_at->diffInMinutes($a->ended_at)) }})</span>
                @endforeach
            </div>
            <button class="btn btn-sm btn-link text-decoration-none text-muted" data-bs-toggle="collapse" data-bs-target="#history-table-{{ $patient->id }}">
                <i class="bi bi-plus-circle-fill"></i> Ver detalles
            </button>
        </div>
        <div class="collapse" id="history-table-{{ $patient->id }}">
            <div class="d-flex w-100">
                <div class="flex-grow-1">
                    <table class="table inner-table mb-0 border-top">
                        <thead>
                            <tr>
                                <th class="ps-5">Ingreso</th>
                                <th>Fin</th>
                                <th>Estado</th>
                                <th>Transcurrido</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($sortedAssigns as $a)
                                <tr>
                                    <td class="ps-5 text-secondary">{{ $a->started_at ? $a->started_at->format('H:i') : '--:--' }}</td>
                                    <td class="text-secondary">{{ $a->ended_at ? $a->ended_at->format('H:i') : '' }}</td>
                                    <td>
                                        <span class="fw-bold text-dark">{{ $a->event_type }}</span>
                                    </td>
                                    <td>
                                        @php
                                            $diff = ($a->started_at && $a->ended_at) ? $a->started_at->diff($a->ended_at) : null;
                                            $timeStr = $diff ? ($diff->h > 0 ? $diff->h . 'hr ' . $diff->i . ' m' : $diff->i . ' min.') : '--';
                                        @endphp
                                        <span class="text-muted">{{ $timeStr }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @endforeach
@endif


<!-- Modal Ingresar Nuevo Paciente (Llegada) -->
<div class="modal fade" id="newArrivalModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <div class="modal-header border-0 bg-light rounded-top-4 pb-2">
                <h5 class="modal-title fw-bold text-dark"><i class="bi bi-person-plus-fill text-primary me-2"></i> Ingreso a Clínica</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('console.assignments.store') }}" method="POST">
                @csrf
                <div class="modal-body pt-4">
                    <div class="mb-4">
                        <label class="form-label text-muted small fw-bold">Buscar Paciente en Sistema</label>
                        <select name="patient_id" class="form-select bg-light border-0 shadow-none py-2" required>
                            <option value="" disabled selected>Escribe para buscar...</option>
                            @foreach($patients as $p)
                                <option value="{{ $p->id }}">{{ mb_strtoupper($p->last_name) }}, {{ $p->first_name }} (DNI: {{ $p->dni }})</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label text-muted small fw-bold">Estado Inicial</label>
                        <select name="event_types[]" class="form-select bg-light border-0 shadow-none py-2" required>
                            <option value="Ingreso (espera)" selected>Ingreso (espera)</option>
                            <option value="Dilatación">Dilatación</option>
                            <option value="Estudios Visuales">Estudios Visuales</option>
                            <option value="Atención Médica">Atención Médica</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">Médico Referencia (Opcional)</label>
                        <select name="doctor_ids[]" class="form-select bg-light border-0 shadow-none py-2">
                            <option value="" selected>Sin especificar aún</option>
                            @foreach($doctors as $d)
                                <option value="{{ $d->id }}">{{ $d->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 pb-4 px-4 d-flex">
                    <button type="submit" class="btn btn-primary w-100 py-2 rounded-pill fw-bold" style="background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%); border:none;">
                        Asentar Llegada <i class="bi bi-arrow-right ms-1"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Motores de Cronometraje en Vivo
    setInterval(() => {
        const now = new Date();
        
        // Timer de Evento Activo (Fila de tabla)
        document.querySelectorAll('.flash-timer').forEach(el => {
            const startStr = el.getAttribute('data-start');
            if(startStr) {
                const start = new Date(startStr);
                const diffMins = Math.floor((now - start) / 60000);
                
                let timeStr = diffMins + ' min';
                if (diffMins >= 60) {
                     let hrs = Math.floor(diffMins / 60);
                     let rem = diffMins % 60;
                     timeStr = hrs + ' hr' + (rem > 0 ? ' ' + rem + ' min' : '');
                }

                el.innerHTML = `<span class="live-dot"></span> ${timeStr}`;
            }
        });

        // Timer de Total Día (Cabecera negra)
        document.querySelectorAll('.total-day-timer').forEach(el => {
            const startStr = el.getAttribute('data-start');
            if(startStr) {
                const start = new Date(startStr);
                const diffMins = Math.floor((now - start) / 60000);
                
                let timeStr = diffMins + ' min';
                if (diffMins >= 60) {
                     let hrs = Math.floor(diffMins / 60);
                     let rem = diffMins % 60;
                     timeStr = hrs + ' hr' + (rem > 0 ? ' ' + rem + ' min' : '');
                }
                
                el.innerHTML = `Lleva: ${timeStr}`;
            }
        });
        
    }, 10000); // Actualiza cada 10 segs para no sobrecargar

    // Logic for dynamic row addition in Append Assignment Modal
    let appendCounters = {};
    function addAppendRow(pId) {
        if(!appendCounters[pId]) appendCounters[pId] = 0;
        appendCounters[pId]++;
        
        const rowId = `append-row-${pId}-${appendCounters[pId]}`;
        const html = `
            <div class="row gx-3 align-items-end mb-3" id="${rowId}">
                <div class="col-md-5">
                    <select name="event_types[]" class="form-select border-secondary border-opacity-25 shadow-sm" required>
                        <option value="Ingreso (espera)">Ingreso (espera)</option>
                        <option value="Dilatación">Dilatación</option>
                        <option value="Estudios Visuales">Estudios Visuales</option>
                        <option value="Atención Médica">Atención Médica</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <select name="doctor_ids[]" class="form-select border-secondary border-opacity-25 shadow-sm">
                        <option value="">Cualquier médico / Staff</option>
                        @foreach($doctors as $d)
                            <option value="{{ $d->id }}">{{ $d->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1 text-center">
                    <button type="button" class="btn btn-link text-danger p-0 mt-1" style="font-size: 1.2rem;" onclick="document.getElementById('${rowId}').remove()">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
        `;
        document.getElementById('appendContainer-' + pId).insertAdjacentHTML('beforeend', html);
    }
</script>

@endsection
