@extends('layouts.admin')

@section('title', 'Consola Pacientes (Flujo Interactivo)')
@section('subtitle', 'Línea de tiempo unificada por paciente')

@section('content')

<style>
/* Custom Styles Phase 15 - State Machine */
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
                <div class="bg-warning bg-opacity-10 p-2 rounded-circle text-warning">
                    <i class="bi bi-person-fill fs-3"></i>
                </div>
                <div>
                    <h6 class="mb-0 fw-bold text-dark fs-5">{{ mb_strtolower($patient->last_name) }}, {{ mb_strtolower($patient->first_name) }}</h6>
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
                                <td class="ps-5 text-secondary">{{ $a->started_at->format('H:i') }}</td>
                                <td class="text-secondary">{{ $a->ended_at ? $a->ended_at->format('H:i') : '' }}</td>
                                <td>
                                    <span class="fw-bold text-dark">{{ $a->event_type }}</span>
                                </td>
                                <td>
                                    @if($a->status === 'in_progress')
                                        <div class="text-dark fw-bold flash-timer" data-start="{{ $a->started_at->toIso8601String() }}">
                                            <span class="live-dot"></span> 0 min
                                        </div>
                                    @else
                                        @php
                                            $diff = $a->started_at->diff($a->ended_at);
                                            $timeStr = $diff->h > 0 ? $diff->h . 'hr ' . $diff->i . ' m' : $diff->i . ' min.';
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
                            <option value="Ingreso (espera)">Ingreso (espera)</option>
                            <option value="Dilatación" {{ $currentStatus == 'Ingreso (espera)' ? 'selected' : '' }}>Dilatación</option>
                            <option value="Estudios Visuales">Estudios Visuales</option>
                            <option value="Atención Médica" {{ $currentStatus == 'Dilatación' ? 'selected' : '' }}>Atención Médica</option>
                            <option value="Recepción">Recepción (Trámites)</option>
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
@empty
    <div class="text-center py-5">
        <div class="opacity-25 mb-3">
            <i class="bi bi-layout-text-window-reverse" style="font-size: 5rem;"></i>
        </div>
        <h4 class="fw-bold text-muted">Sala de Espera Despejada</h4>
        <p class="text-muted">Ningún paciente ha ingresado aún. Pulsa el botón superior para ingresar al primer paciente del día.</p>
    </div>
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
    <div class="patient-card" style="background:#fcfcfc; border-color:#eee; filter:grayscale(100%); opacity:0.8;">
        <div class="patient-header" style="background:#f9f9f9; border-color:#eee;">
            <div class="d-flex align-items-center gap-3" style="width: 25%;">
                <div class="bg-secondary bg-opacity-10 p-2 rounded-circle text-secondary">
                    <i class="bi bi-person-check-fill fs-3"></i>
                </div>
                <div>
                    <h6 class="mb-0 fw-bold text-dark fs-5">{{ mb_strtolower($patient->last_name) }}, {{ mb_strtolower($patient->first_name) }}</h6>
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
        <div class="px-4 py-2" style="background:#fff;">
            <small class="text-muted fw-bold">Recorrido:</small> 
            @foreach($sortedAssigns as $a)
                <span class="badge bg-light text-dark border me-1">{{ $a->event_type }} ({{ formatMins($a->started_at->diffInMinutes($a->ended_at)) }})</span>
            @endforeach
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
                        <select name="event_type" class="form-select bg-light border-0 shadow-none py-2" required>
                            <option value="Ingreso (espera)" selected>Ingreso (espera)</option>
                            <option value="Recepción">Recepción Inmediata</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">Médico Referencia (Opcional)</label>
                        <select name="doctor_id" class="form-select bg-light border-0 shadow-none py-2">
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
</script>

@endsection
