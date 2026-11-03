@extends('layouts.admin')

@section('title', 'Consola Pacientes (Sala de Espera)')
@section('subtitle', 'Flujo interactivo y control de tiempos en vivo')

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
    transition: all 0.2s ease;
}
.patient-card:hover {
    box-shadow: 0 6px 20px rgba(0,0,0,0.06);
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
    font-weight: 700;
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

.hover-primary:hover {
    color: #0d6efd !important;
    text-decoration: underline !important;
}
</style>

<!-- Top Action Bar -->
@php
    if (!function_exists('formatMins')) {
        function formatMins($mins) {
            $mins = max(0, floor($mins));
            if ($mins < 60) return $mins . ' min';
            $hrs = floor($mins / 60);
            $rem = $mins % 60;
            return $hrs . ' hr ' . ($rem > 0 ? $rem . ' min' : '');
        }
    }
@endphp
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
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

<!-- Barra de Ordenamiento (Sala de Espera) -->
<div class="card border-0 shadow-sm rounded-4 mb-4 bg-white p-3" style="border: 1px solid rgba(0,0,0,0.06) !important;">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <span class="fw-bold text-dark small text-uppercase" style="letter-spacing: 0.5px;">
                <i class="bi bi-filter-circle text-primary me-1"></i> Ordenar por:
            </span>
            <select id="consoleSortField" class="form-select form-select-sm bg-light border-0 shadow-none fw-semibold rounded-pill px-3 py-1" style="width: auto; cursor: pointer;">
                <option value="llegada" selected>Horario de Llegada (Default)</option>
                <option value="espera">Tiempo de Espera</option>
                <option value="dilatacion">Tiempo de Dilatación</option>
                <option value="agenda">Horario de Agenda (Turno)</option>
                <option value="cobertura">Cobertura Médica</option>
                <option value="nombre">Nombre de Paciente (A-Z)</option>
            </select>
            <button id="consoleSortDirectionBtn" class="btn btn-sm btn-light border rounded-pill px-3 fw-bold text-secondary shadow-sm" title="Alternar Ascendente / Descendente">
                <i class="bi bi-sort-down fs-6 me-1" id="consoleSortDirectionIcon"></i>
                <span id="consoleSortDirectionText">Ascendente</span>
            </button>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-3 py-2 fw-bold" id="activePatientsCountBadge">
                {{ $activePatients->count() }} paciente(s) en espera
            </span>
        </div>
    </div>
</div>

<!-- Contenedor de Pacientes Activos -->
<div id="activePatientsContainer">
@forelse($activePatients as $patient)
    @php
        $sortedAssigns = $patient->assignments->sortBy('started_at');
        $firstEvent = $sortedAssigns->first();
        $llegada = ($firstEvent && $firstEvent->started_at) ? $firstEvent->started_at->format('H:i') : '--:--';
        $llegadaTimestamp = ($firstEvent && $firstEvent->started_at) ? $firstEvent->started_at->timestamp : 0;
        
        $activeEvent = $patient->assignments->where('status', 'in_progress')->first();
        $currentStatus = $activeEvent ? $activeEvent->event_type : 'Terminado';
        
        // Sumar minutos por categoría
        $totals = ['Dilatación' => 0, 'Ingreso (espera)' => 0];
        foreach($patient->assignments as $a) {
            if ($a->started_at) {
                $end = $a->ended_at ?? \Carbon\Carbon::now();
                $diff = max(0, $a->started_at->diffInMinutes($end));
                if(isset($totals[$a->event_type])) { $totals[$a->event_type] += $diff; }
            }
        }

        // Datos de turno / agenda hoy
        $todayAppt = $patient->appointments ? $patient->appointments->where('date', \Carbon\Carbon::today()->toDateString())->first() : null;
        $horarioAgenda = $todayAppt ? substr($todayAppt->time, 0, 5) : null;
        $agendaMinutes = 99999;
        if ($horarioAgenda) {
            $parts = explode(':', $horarioAgenda);
            $agendaMinutes = ((int)$parts[0] * 60) + (int)($parts[1] ?? 0);
        }

        // Leyendas de secretaria
        $notesRecepcion = $firstEvent ? trim($firstEvent->notes ?? '') : '';
        $notesAgenda = $todayAppt ? trim(($todayAppt->notes ?: $todayAppt->reason) ?? '') : '';

        // Tiempos calculados reales de inicio
        $totalDiaMins = ($firstEvent && $firstEvent->started_at) ? max(0, $firstEvent->started_at->diffInMinutes(now())) : 0;
        $totalDiaStr = $totalDiaMins < 60 ? $totalDiaMins . ' min' : floor($totalDiaMins/60) . ' hr ' . ($totalDiaMins%60) . ' min';
    @endphp

    <div class="patient-card active-patient-card"
         data-llegada="{{ $llegadaTimestamp }}"
         data-espera="{{ $totals['Ingreso (espera)'] }}"
         data-dilatacion="{{ $totals['Dilatación'] }}"
         data-agenda="{{ $agendaMinutes }}"
         data-cobertura="{{ strtolower($patient->obra_social ?? 'particular') }}"
         data-nombre="{{ strtolower($patient->last_name . ' ' . $patient->first_name) }}">
        
        <!-- Header -->
        <div class="patient-header">
            <div class="d-flex align-items-center gap-3" style="width: 32%;">
                @if($patient->photo_path)
                    <img src="{{ Storage::url($patient->photo_path) }}" alt="Foto" class="rounded-circle object-fit-cover shadow-sm" style="width: 50px; height: 50px; border: 2px solid var(--bs-warning);">
                @else
                    <div class="bg-warning bg-opacity-10 p-2 rounded-circle text-warning d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                        <i class="bi bi-person-fill fs-3"></i>
                    </div>
                @endif
                <div>
                    <!-- Click en el nombre del paciente para ir directo a su Historia Clínica / Consulta -->
                    <h6 class="mb-1 fw-bold text-dark fs-5 text-capitalize d-flex align-items-center">
                        <a href="{{ route('patients.show', $patient) }}" class="text-dark text-decoration-none hover-primary fw-bold" title="Abrir Historia Clínica de {{ $patient->first_name }} {{ $patient->last_name }}">
                            {{ mb_strtolower($patient->last_name) }}, {{ mb_strtolower($patient->first_name) }}
                            <i class="bi bi-box-arrow-up-right text-primary opacity-50 ms-2" style="font-size: 0.95rem;"></i>
                        </a>
                        <button class="btn btn-sm btn-link p-0 ms-2" title="Agregar más pasos a este paciente" data-bs-toggle="modal" data-bs-target="#addMoreAssignmentsModal-{{ $patient->id }}">
                            <i class="bi bi-person-plus-fill" style="color: #7e448b; font-size: 1.3rem;"></i>
                        </button>
                    </h6>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span class="badge bg-white text-dark border px-2 py-1 shadow-none" style="font-size: 0.75rem;">
                            <i class="bi bi-shield-check text-primary me-1"></i>Cobertura: <strong>{{ $patient->obra_social ?? 'Particular' }}</strong>{{ $patient->plan ? ' - ' . $patient->plan : '' }}
                        </span>
                        @if($horarioAgenda)
                            <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 px-2 py-1" style="font-size: 0.75rem;">
                                <i class="bi bi-calendar-event me-1"></i>Turno: <strong>{{ $horarioAgenda }} hs</strong>
                            </span>
                        @endif
                    </div>
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
                    @if($firstEvent && $firstEvent->started_at)
                        <span class="badge bg-dark px-3 py-1 rounded-pill total-day-timer" data-start="{{ $firstEvent->started_at->toIso8601String() }}">Lleva: {{ $totalDiaStr }}</span>
                    @else
                        <span class="badge bg-dark px-3 py-1 rounded-pill">0 min</span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Leyendas / Observaciones de la Secretaria -->
        <div class="w-100 px-4 py-2 bg-warning bg-opacity-10 border-top border-bottom border-warning border-opacity-25 d-flex align-items-center justify-content-between gap-2 flex-wrap" style="font-size: 0.85rem;">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="badge bg-warning text-dark fw-bold"><i class="bi bi-sticky-fill me-1"></i> Observaciones Internas:</span>
                @if($notesRecepcion || $notesAgenda)
                    @if($notesRecepcion)
                        <span class="text-dark"><strong>Recepción:</strong> <span id="notesRecepcionSpan_{{ $patient->id }}">{{ $notesRecepcion }}</span></span>
                    @endif
                    @if($notesRecepcion && $notesAgenda)
                        <span class="text-muted">|</span>
                    @endif
                    @if($notesAgenda)
                        <span class="text-dark"><strong>Turno:</strong> {{ $notesAgenda }}</span>
                    @endif
                @else
                    <span class="text-muted fst-italic" id="notesRecepcionSpan_{{ $patient->id }}">Sin observaciones registradas por secretaría</span>
                @endif
            </div>
            <button type="button" class="btn btn-sm btn-outline-dark rounded-pill px-3 py-1 shadow-none fw-bold" onclick="openEditConsoleNotesModal({{ $patient->id }}, '{{ addslashes($patient->first_name . ' ' . $patient->last_name) }}', '{{ addslashes($notesRecepcion ?: $notesAgenda) }}')" style="font-size: 0.75rem;">
                <i class="bi bi-pencil-fill me-1"></i> {{ ($notesRecepcion || $notesAgenda) ? 'Modificar' : '+ Agregar Observación' }}
            </button>
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
                                        @php
                                            $currMins = $a->started_at ? max(0, $a->started_at->diffInMinutes(now())) : 0;
                                            $currStr = $currMins < 60 ? $currMins . ' min' : floor($currMins/60) . ' hr ' . ($currMins%60) . ' min';
                                        @endphp
                                        <div class="text-dark fw-bold flash-timer" data-start="{{ $a->started_at ? $a->started_at->toIso8601String() : '' }}">
                                            <span class="live-dot"></span> {{ $currStr }}
                                        </div>
                                    @elseif($a->status === 'pending')
                                        <span class="text-muted fw-bold"><i class="bi bi-hourglass-split"></i> En cola</span>
                                    @else
                                        @php
                                            $diff = ($a->started_at && $a->ended_at) ? $a->started_at->diff($a->ended_at) : null;
                                            $timeStr = $diff ? ($diff->h > 0 ? $diff->h . ' hr ' . $diff->i . ' min' : $diff->i . ' min') : '--';
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
                            @php
                                $hasPending = $patient->assignments->where('status', 'pending')->count() > 0;
                            @endphp
                            @if($hasPending)
                                <option value="AUTO" class="fw-bold text-primary">Siguiente paso en la cola</option>
                            @else
                                <option value="" disabled {{ !in_array($currentStatus, ['Ingreso (espera)', 'Dilatación']) ? 'selected' : '' }}>Seleccione siguiente paso...</option>
                            @endif
                            <option value="Ingreso (espera)" {{ $currentStatus == 'Ingreso (espera)' ? 'disabled' : '' }}>Ingreso (espera)</option>
                            <option value="Dilatación" {{ $currentStatus == 'Ingreso (espera)' && !$hasPending ? 'selected' : ($currentStatus == 'Dilatación' ? 'disabled' : '') }}>Dilatación</option>
                            <option value="Estudios Visuales" {{ $currentStatus == 'Estudios Visuales' ? 'disabled' : '' }}>Estudios Visuales</option>
                            <option value="Atención Médica" {{ $currentStatus == 'Dilatación' && !$hasPending ? 'selected' : ($currentStatus == 'Atención Médica' ? 'disabled' : '') }}>Atención Médica</option>
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
                    <div class="modal-body pt-3">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold mb-0 text-dark">Nuevos Pasos a la Cola</h6>
                            <button type="button" class="btn btn-outline-primary btn-sm rounded-pill" onclick="addAppendRow({{ $patient->id }})">
                                <i class="bi bi-plus-circle me-1"></i> Añadir Otro Paso
                            </button>
                        </div>
                        
                        <div id="appendContainer-{{ $patient->id }}">
                            <div class="row gx-3 align-items-end mb-3">
                                <div class="col-md-5">
                                    <label class="form-label text-muted small fw-bold">Estado</label>
                                    <select name="event_types[]" class="form-select border-secondary border-opacity-25 shadow-sm" required>
                                        <option value="Ingreso (espera)">Ingreso (espera)</option>
                                        <option value="Dilatación">Dilatación</option>
                                        <option value="Estudios Visuales">Estudios Visuales</option>
                                        <option value="Atención Médica" selected>Atención Médica</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-muted small fw-bold">Médico Referencia</label>
                                    <select name="doctor_ids[]" class="form-select border-secondary border-opacity-25 shadow-sm">
                                        <option value="">Cualquier médico / Staff</option>
                                        @foreach($doctors as $d)
                                            <option value="{{ $d->id }}">{{ $d->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-1 text-center">
                                    <!-- No delete for first row -->
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0 pb-4 px-4">
                        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold" style="background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%); border:none;">
                            Guardar Nuevos Pasos
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@empty
    <div class="text-center py-5 empty-waiting-room" style="min-height: 400px; display: flex; flex-direction: column; align-items: center; justify-content: center; background-image: url('{{ asset('images/empty_waiting_room.png') }}'); background-size: contain; background-position: center; background-repeat: no-repeat; border-radius: 1.5rem; position: relative;">
        <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(255,255,255,0.6); border-radius: 1.5rem;" class="bg-overlay-light"></div>
        <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(17, 24, 39, 0.8); border-radius: 1.5rem; display: none;" class="bg-overlay-dark"></div>
        
        <div style="position: relative; z-index: 2; padding: 2rem; background: rgba(255,255,255,0.95); border-radius: 1.5rem; box-shadow: 0 10px 30px rgba(0,0,0,0.08); max-width: 500px; backdrop-filter: blur(5px);" class="empty-state-card">
            <div class="d-inline-flex align-items-center justify-content-center p-3 rounded-circle mb-3 shadow-sm" style="background-color: #f8fafc; border: 1px solid #e2e8f0;">
                <i class="bi bi-clock-history fs-1" style="color: var(--primary-color);"></i>
            </div>
            <h4 class="fw-bold" style="color: #2c3e50;">Sala de Espera Despejada</h4>
            <p class="text-muted mb-4" style="line-height: 1.6;">No hay pacientes aguardando ni en proceso en este momento. Utiliza el botón superior para dar ingreso a un paciente.</p>
            <button class="btn btn-primary rounded-pill px-4 py-2 shadow-sm fw-bold d-inline-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#newArrivalModal" style="background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%); border: none;">
                <i class="bi bi-person-plus-fill"></i> Ingresar Primer Paciente
            </button>
        </div>
    </div>
@endforelse
</div> <!-- Fin #activePatientsContainer -->

<!-- Historial (Dados de Alta Hoy) -->
@if($finishedPatients->count() > 0)
    <div class="mt-5 mb-4 d-flex align-items-center gap-3 opacity-75">
        <h5 class="fw-bold text-muted mb-0"><i class="bi bi-archive-fill me-2"></i> Historial (Dados de Alta Hoy)</h5>
        <div class="flex-grow-1 border-bottom"></div>
    </div>

    @foreach($finishedPatients as $patient)
    @php
        $sortedAssigns = $patient->assignments->sortBy('started_at');
        $firstEvent = $sortedAssigns->first();
        $llegada = ($firstEvent && $firstEvent->started_at) ? $firstEvent->started_at->format('H:i') : '--:--';
        $lastEvent = $sortedAssigns->last();
        $salida = $lastEvent && $lastEvent->ended_at ? $lastEvent->ended_at->format('H:i') : '--:--';
        
        // Sumar minutos por categoría
        $totals = ['Dilatación' => 0, 'Ingreso (espera)' => 0];
        $totalMinutes = 0;
        foreach($patient->assignments as $a) {
            if ($a->started_at) {
                $end = $a->ended_at ?? \Carbon\Carbon::now();
                $diff = max(0, $a->started_at->diffInMinutes($end));
                $totalMinutes += $diff;
                if(isset($totals[$a->event_type])) { $totals[$a->event_type] += $diff; }
            }
        }
    @endphp
    <div class="patient-card history-card" style="margin-bottom: 16px;">
        <div class="patient-header">
            <div class="d-flex align-items-center gap-3" style="width: 32%;">
                @if($patient->photo_path)
                    <img src="{{ Storage::url($patient->photo_path) }}" alt="Foto" class="rounded-circle object-fit-cover shadow-sm" style="width: 50px; height: 50px; border: 2px solid var(--bs-secondary); filter: grayscale(50%);">
                @else
                    <div class="bg-secondary bg-opacity-10 p-2 rounded-circle text-secondary d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                        <i class="bi bi-person-check-fill fs-3"></i>
                    </div>
                @endif
                <div>
                    <h6 class="mb-1 fw-bold text-dark fs-5 text-capitalize">
                        <a href="{{ route('patients.show', $patient) }}" class="text-dark text-decoration-none hover-primary fw-bold" title="Abrir Historia Clínica de {{ $patient->first_name }} {{ $patient->last_name }}">
                            {{ mb_strtolower($patient->last_name) }}, {{ mb_strtolower($patient->first_name) }}
                            <i class="bi bi-box-arrow-up-right text-secondary opacity-50 ms-2" style="font-size: 0.9rem;"></i>
                        </a>
                    </h6>
                    <small class="text-muted text-uppercase" style="font-size:0.75rem;">
                        <i class="bi bi-shield-check me-1"></i>Cobertura: {{ $patient->obra_social ?? 'Particular' }} | Alta Diaria
                    </small>
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
                <small class="fw-bold me-2">Recorrido:</small>
                @foreach($sortedAssigns as $idx => $step)
                    <span class="badge rounded-pill me-1 {{ $step->status == 'completed' ? 'border border-secondary bg-white text-secondary' : 'bg-secondary' }}">
                        {{ $step->event_type }}
                        @if($step->doctor) ({{ $step->doctor->name }}) @endif
                    </span>
                    @if(!$loop->last) <i class="bi bi-chevron-right text-muted small me-1"></i> @endif
                @endforeach
            </div>
            <div>
                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-3 py-1 fw-bold">
                    <i class="bi bi-check2-all me-1"></i> Finalizado
                </span>
            </div>
        </div>
    </div>
    @endforeach
@endif

<!-- Modal para Registrar Nuevo Ingreso a la Clínica -->
<div class="modal fade" id="newArrivalModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <div class="modal-header border-0 bg-light rounded-top-4 pb-2">
                <h5 class="modal-title fw-bold text-dark"><i class="bi bi-person-plus-fill text-primary me-2"></i> Ingreso a Sala de Espera</h5>
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

                    <div class="mb-4">
                        <label class="form-label text-muted small fw-bold">Médico Referencia (Opcional)</label>
                        <select name="doctor_ids[]" class="form-select bg-light border-0 shadow-none py-2">
                            <option value="" selected>Sin especificar aún</option>
                            @foreach($doctors as $d)
                                <option value="{{ $d->id }}">{{ $d->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold"><i class="bi bi-sticky-fill text-warning me-1"></i> Observaciones Internas / Comentario del Momento (Opcional)</label>
                        <textarea name="notes" rows="2" class="form-control bg-light border-0 shadow-none rounded-3" placeholder="Ej: Trae estudios de otra clínica, paciente en silla de ruedas, urgencia..."></textarea>
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
    // Motores de Cronometraje en Vivo (Nunca se resetean al navegar)
    function updateAllTimers() {
        const now = new Date();
        
        // Timer de Evento Activo (Fila de tabla)
        document.querySelectorAll('.flash-timer').forEach(el => {
            const startStr = el.getAttribute('data-start');
            if(startStr) {
                const start = new Date(startStr);
                const diffMins = Math.max(0, Math.floor((now - start) / 60000));
                
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
                const diffMins = Math.max(0, Math.floor((now - start) / 60000));
                
                let timeStr = diffMins + ' min';
                if (diffMins >= 60) {
                     let hrs = Math.floor(diffMins / 60);
                     let rem = diffMins % 60;
                     timeStr = hrs + ' hr' + (rem > 0 ? ' ' + rem + ' min' : '');
                }
                
                el.innerHTML = `Lleva: ${timeStr}`;
            }
        });
    }

    // Inicializar timers INMEDIATAMENTE sin esperar intervalo
    updateAllTimers();
    // Actualizar periódicamente cada 5 segundos
    setInterval(updateAllTimers, 5000);

    // Sistema de Ordenamiento de la Sala de Espera
    function initConsoleSorting() {
        const sortFieldSelect = document.getElementById('consoleSortField');
        const sortDirBtn = document.getElementById('consoleSortDirectionBtn');
        const sortDirIcon = document.getElementById('consoleSortDirectionIcon');
        const sortDirText = document.getElementById('consoleSortDirectionText');
        const container = document.getElementById('activePatientsContainer');

        if (!sortFieldSelect || !sortDirBtn || !container) return;

        // Recuperar preferencia de orden guardada en localStorage
        let sortField = localStorage.getItem('console_sort_field') || 'llegada';
        let sortDir = localStorage.getItem('console_sort_dir') || 'asc';

        sortFieldSelect.value = sortField;
        updateDirectionUI();

        function updateDirectionUI() {
            if (sortDir === 'asc') {
                sortDirIcon.className = 'bi bi-sort-down fs-6 me-1';
                sortDirText.innerText = 'Ascendente';
            } else {
                sortDirIcon.className = 'bi bi-sort-up fs-6 me-1';
                sortDirText.innerText = 'Descendente';
            }
        }

        function applySorting() {
            const cards = Array.from(container.querySelectorAll('.active-patient-card'));
            if (cards.length === 0) return;

            cards.sort((a, b) => {
                let valA, valB;
                switch(sortField) {
                    case 'llegada':
                        valA = parseInt(a.getAttribute('data-llegada')) || 0;
                        valB = parseInt(b.getAttribute('data-llegada')) || 0;
                        return sortDir === 'asc' ? (valA - valB) : (valB - valA);

                    case 'espera':
                        valA = parseFloat(a.getAttribute('data-espera')) || 0;
                        valB = parseFloat(b.getAttribute('data-espera')) || 0;
                        return sortDir === 'asc' ? (valA - valB) : (valB - valA);

                    case 'dilatacion':
                        valA = parseFloat(a.getAttribute('data-dilatacion')) || 0;
                        valB = parseFloat(b.getAttribute('data-dilatacion')) || 0;
                        return sortDir === 'asc' ? (valA - valB) : (valB - valA);

                    case 'agenda':
                        valA = parseInt(a.getAttribute('data-agenda')) || 99999;
                        valB = parseInt(b.getAttribute('data-agenda')) || 99999;
                        return sortDir === 'asc' ? (valA - valB) : (valB - valA);

                    case 'cobertura':
                        valA = (a.getAttribute('data-cobertura') || '').trim();
                        valB = (b.getAttribute('data-cobertura') || '').trim();
                        return sortDir === 'asc' ? valA.localeCompare(valB) : valB.localeCompare(valA);

                    case 'nombre':
                        valA = (a.getAttribute('data-nombre') || '').trim();
                        valB = (b.getAttribute('data-nombre') || '').trim();
                        return sortDir === 'asc' ? valA.localeCompare(valB) : valB.localeCompare(valA);

                    default:
                        return 0;
                }
            });

            cards.forEach(card => container.appendChild(card));
        }

        sortFieldSelect.addEventListener('change', function() {
            sortField = this.value;
            localStorage.setItem('console_sort_field', sortField);
            applySorting();
        });

        sortDirBtn.addEventListener('click', function() {
            sortDir = (sortDir === 'asc') ? 'desc' : 'asc';
            localStorage.setItem('console_sort_dir', sortDir);
            updateDirectionUI();
            applySorting();
        });

        applySorting();
    }

    document.addEventListener('DOMContentLoaded', function() {
        initConsoleSorting();
    });

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

<!-- Modal: Editar Observaciones Internas en Sala de Espera -->
<div class="modal fade" id="editConsoleNotesModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 rounded-4 shadow">
        <div class="modal-header border-bottom-0 pb-0">
            <div>
                <h5 class="modal-title fw-bold text-dark"><i class="bi bi-sticky-fill text-warning me-2"></i>Observaciones Internas / Comentario</h5>
                <small class="text-muted fw-bold" id="modalEditNotesPatientName">Paciente: --</small>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form id="editConsoleNotesForm" onsubmit="submitConsoleNotesQuick(event)">
            @csrf
            <input type="hidden" id="modalEditNotesPatientId">
            <div class="modal-body p-4">
                <label class="form-label text-muted small fw-bold">Anotaciones de Recepción / Comentario del momento</label>
                <textarea id="modalEditNotesInput" rows="3" class="form-control bg-light border-0 shadow-none rounded-3 py-2" placeholder="Ej: Trae estudios de otra clínica, urgencia, paciente en silla de ruedas, etc..."></textarea>
                <small class="text-muted d-block mt-2">Esta observación aparecerá destacada en la fila de sala de espera para el médico y secretaría.</small>
            </div>
            <div class="modal-footer border-top-0 pt-0 pe-4">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" id="btnSaveConsoleNotes" class="btn btn-warning rounded-pill px-4 text-dark fw-bold">Guardar Observación</button>
            </div>
        </form>
    </div>
  </div>
</div>

<script>
    function openEditConsoleNotesModal(patientId, patientName, currentNotes) {
        document.getElementById('modalEditNotesPatientId').value = patientId;
        document.getElementById('modalEditNotesPatientName').innerText = `Paciente: ${patientName}`;
        document.getElementById('modalEditNotesInput').value = currentNotes || '';
        const modal = new bootstrap.Modal(document.getElementById('editConsoleNotesModal'));
        modal.show();
    }

    function submitConsoleNotesQuick(e) {
        e.preventDefault();
        const patientId = document.getElementById('modalEditNotesPatientId').value;
        const notesVal = document.getElementById('modalEditNotesInput').value;
        const btn = document.getElementById('btnSaveConsoleNotes');
        btn.disabled = true;
        btn.innerText = 'Guardando...';

        axios.post(`/console/assignments/${patientId}/notes`, {
            notes: notesVal
        }, {
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') }
        })
        .then(res => {
            const span = document.getElementById(`notesRecepcionSpan_${patientId}`);
            if (span) {
                span.innerText = notesVal || 'Sin observaciones registradas por secretaría';
                span.className = notesVal ? 'text-dark fw-bold' : 'text-muted fst-italic';
            }
            const modalEl = document.getElementById('editConsoleNotesModal');
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) modal.hide();
            btn.disabled = false;
            btn.innerText = 'Guardar Observación';
            if (typeof spawnToast === 'function') {
                spawnToast('Observaciones Actualizadas', 'Comentario interno guardado.', 'check-circle-fill', 'success text-white');
            }
        })
        .catch(err => {
            alert('Error al guardar la observación interna.');
            btn.disabled = false;
            btn.innerText = 'Guardar Observación';
        });
    }
</script>
@endsection
