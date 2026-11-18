@extends('layouts.admin')

@section('title', 'Agenda Médica Central')
@section('subtitle', 'Visión simultánea de todas las agendas de la clínica')

@section('content')
<style>
/* Phase 18 Vanilla Calendar Styles */
.cal-day-header { font-weight: bold; text-align: center; font-size: 0.85rem; color: #7a8291; padding-bottom: 5px; text-transform: uppercase; letter-spacing: 0.5px; }
.cal-day {
    aspect-ratio: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.2s;
    font-size: 0.82rem;
    color: #444;
}
.cal-day.empty { visibility: hidden; }
.cal-day.unavailable { color: #8893a3; cursor: default; font-weight: 500; }
.cal-day.available { 
    font-weight: 800;
    box-shadow: 0 4px 10px rgba(32, 201, 151, 0.2); 
    
    --cal-fill-color: rgba(32, 201, 151, 0.85);
    --cal-bg-color: rgba(232, 245, 233, 0.6);
    --cal-border-color: rgba(32, 201, 151, 0.5);
    --cal-text-color: #198754;
    --cal-text-filled: #ffffff;
    
    background: linear-gradient(to top, var(--cal-fill-color) var(--fill-percentage, 0%), var(--cal-bg-color) var(--fill-percentage, 0%));
    border: 2px solid var(--cal-border-color);
    color: var(--cal-text-color);
}
.cal-day.available[data-filled="true"] {
    color: var(--cal-text-filled);
}
.cal-day.available:hover { 
    transform: scale(1.15); 
    box-shadow: 0 6px 14px rgba(25, 135, 84, 0.4); 
    filter: brightness(1.1);
}
.cal-day.full { 
    background: linear-gradient(135deg, #ff6b6b 0%, #dc3545 100%); 
    color: #ffffff; 
    border: none; 
    box-shadow: 0 4px 10px rgba(220, 53, 69, 0.3);
    font-weight: 700;
}
.cal-day.full:hover { 
    background: linear-gradient(135deg, #dc3545 0%, #b02a37 100%); 
    color: #fff; 
    transform: scale(1.15); 
}
.cal-day.partial-block {
    background: linear-gradient(135deg, #f3e8ff 0%, #d8b4fe 100%);
    color: #581c87;
    border: 2px solid #9333ea;
    font-weight: 800;
    box-shadow: 0 3px 8px rgba(147, 51, 234, 0.22);
}
.cal-day.partial-block:hover {
    transform: scale(1.15);
    box-shadow: 0 6px 14px rgba(147, 51, 234, 0.38);
}
body.theme-dark .cal-day.partial-block {
    background: linear-gradient(135deg, rgba(147, 51, 234, 0.35) 0%, rgba(126, 34, 206, 0.55) 100%) !important;
    color: #e9d5ff !important;
    border-color: #c084fc !important;
}
.cal-day.selected-day { box-shadow: 0 0 0 4px rgba(94, 106, 210, 0.6); transform: scale(1.1); }

/* Timeline Slots */
.slot-card { 
    display: flex; 
    align-items: center; 
    padding: 8px 12px; 
    border-radius: 8px; 
    margin-bottom: 6px; 
    border: 1px solid #f0f0f0; 
    transition: all 0.2s; 
    background: #fff; 
}
.slot-time { width: 60px; font-weight: 700; color: #555; font-size: 0.95rem; }
.slot-free { border-left: 4px solid #198754; cursor: pointer; }
.slot-free:hover { background: #f4fbf6; border-color: #198754; }
.slot-booked { border-left: 4px solid #198754; background: #f4fbf6; }
.slot-extra { 
    margin-left: 24px !important; 
    border-left: 5px solid #f59e0b !important; 
    background: #fffdf5 !important;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(245, 158, 11, 0.12);
}
.slot-blocked { border-left: 4px solid #fd7e14; background: #fffaf5; }

/* Dark mode fixes */
body.theme-dark .agenda-timeline-col { background-color: transparent !important; }
body.theme-dark .agenda-timeline-header { background-color: #111827 !important; border-color: rgba(255,255,255,0.05) !important; }
body.theme-dark .agenda-express-footer { background-color: #111827 !important; border-color: rgba(255,255,255,0.05) !important; border-top-color: rgba(255,255,255,0.05) !important; }
body.theme-dark .slot-card { background-color: #1e293b !important; border-color: rgba(255,255,255,0.05) !important; }
body.theme-dark .slot-booked { background-color: rgba(25, 135, 84, 0.1) !important; border-left-color: #198754 !important; }
body.theme-dark .slot-extra { background-color: rgba(13, 110, 253, 0.1) !important; border-left-color: #0d6efd !important; }
body.theme-dark .slot-blocked { background-color: rgba(253, 126, 20, 0.1) !important; border-left-color: #fd7e14 !important; }
body.theme-dark .slot-free:hover { background-color: rgba(25, 135, 84, 0.15) !important; }
body.theme-dark .slot-noshow { background-color: rgba(255,255,255,0.05) !important; border-left-color: #555 !important; }

/* Dark Mode Celeste Calendar Fix */
body.theme-dark .cal-day.available {
    --cal-fill-color: rgba(13, 202, 240, 0.85) !important;
    --cal-bg-color: rgba(13, 202, 240, 0.1) !important;
    --cal-border-color: rgba(13, 202, 240, 0.5) !important;
    --cal-text-color: #0dcaf0 !important;
    --cal-text-filled: #ffffff !important;
    box-shadow: 0 4px 10px rgba(13, 202, 240, 0.2);
}
body.theme-dark .cal-day.available:hover {
    box-shadow: 0 6px 14px rgba(13, 202, 240, 0.4); 
}
</style>

<!-- Leyenda Global -->
<div class="d-flex align-items-center justify-content-end flex-wrap gap-3 mb-4">
    <div class="d-flex align-items-center gap-2">
        <div style="width:16px; height:16px; border-radius:50%; background: linear-gradient(135deg, #20c997 0%, #198754 100%); box-shadow: 0 2px 6px rgba(32, 201, 151, 0.4);"></div> 
        <small class="text-muted fw-bold text-uppercase" style="letter-spacing: 0.5px; font-size: 0.74rem;">Día Habilitado</small>
    </div>
    <div class="d-flex align-items-center gap-2">
        <div style="width:16px; height:16px; border-radius:50%; background: linear-gradient(135deg, #f3e8ff 0%, #d8b4fe 100%); border: 2px solid #9333ea;"></div> 
        <small class="text-muted fw-bold text-uppercase" style="letter-spacing: 0.5px; font-size: 0.74rem;">Parcialmente Bloqueado</small>
    </div>
    <div class="d-flex align-items-center gap-2">
        <div style="width:16px; height:16px; border-radius:50%; background: #fff3cd; border: 2px solid #ffc107;"></div> 
        <small class="text-muted fw-bold text-uppercase" style="letter-spacing: 0.5px; font-size: 0.74rem;">Día Bloqueado</small>
    </div>
    <div class="d-flex align-items-center gap-2">
        <div style="width:16px; height:16px; border-radius:50%; background: linear-gradient(135deg, #ff6b6b 0%, #dc3545 100%); box-shadow: 0 2px 6px rgba(220, 53, 69, 0.3);"></div> 
        <small class="text-muted fw-bold text-uppercase" style="letter-spacing: 0.5px; font-size: 0.74rem;">Día Saturado</small>
    </div>
    <div class="d-flex align-items-center gap-2">
        <div style="width:16px; height:16px; border-radius:50%; background-color: transparent; border: 2px solid #ccc;"></div> 
        <small class="text-muted fw-bold text-uppercase" style="letter-spacing: 0.5px; font-size: 0.74rem;">Sin Consultorio Habitual</small>
    </div>
</div>

<!-- Iterar por Cada Médico Renderizando Agendas Múltiples -->
@foreach($doctors as $doc)
<div class="modern-card p-0 mb-4 shadow-sm overflow-hidden" style="border: 2px solid rgba(94, 106, 210, 0.15); border-radius: 20px; box-shadow: 0 10px 40px rgba(94, 106, 210, 0.08) !important;">
    <div class="px-4 py-3 d-flex align-items-center justify-content-between flex-wrap gap-2" style="background: linear-gradient(90deg, rgba(94, 106, 210, 0.08) 0%, transparent 100%); border-bottom: 2px solid rgba(94, 106, 210, 0.1);">
        <h6 class="fw-bold mb-0 text-dark d-flex align-items-center" style="font-size: 1.1rem;">
            <div class="rounded-circle text-white d-inline-flex align-items-center justify-content-center me-3 shadow-sm" style="width: 36px; height: 36px; background: linear-gradient(135deg, #5e6ad2 0%, #7e448b 100%);">
                <i class="bi bi-person-fill" style="font-size: 1.1rem;"></i>
            </div>
            Agenda de {{ $doc->name }}
        </h6>
        <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-outline-warning btn-sm rounded-pill px-3 fw-bold text-dark shadow-sm" onclick="openDirectBlockModal({{ $doc->id }}, '{{ addslashes($doc->name) }}')">
                <i class="bi bi-shield-lock-fill me-1"></i> Bloquear Días / Franja
            </button>
        </div>
    </div>
    
    <div class="row g-0">
        <!-- Columna Izquierda: Mini Calendario JS -->
        <div class="col-lg-5 p-4 border-end" style="border-right-width: 2px !important; border-color: rgba(94, 106, 210, 0.1) !important;">
            <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom" style="border-color: rgba(94, 106, 210, 0.1) !important;">
                <button class="btn btn-sm btn-light border-0 rounded-circle shadow-sm" style="width:34px; height:34px;" onclick="changeMonth({{ $doc->id }}, -1)"><i class="bi bi-chevron-left"></i></button>
                <h6 id="monthYearLabel_{{ $doc->id }}" class="mb-0 fw-bold text-dark text-capitalize" style="font-size: 1.05rem;"></h6>
                <button class="btn btn-sm btn-light border-0 rounded-circle shadow-sm" style="width:34px; height:34px;" onclick="changeMonth({{ $doc->id }}, 1)"><i class="bi bi-chevron-right"></i></button>
            </div>
            
            <div class="d-grid mb-2" style="grid-template-columns: repeat(7, 1fr);">
                <div class="cal-day-header">Dom</div>
                <div class="cal-day-header">Lun</div>
                <div class="cal-day-header">Mar</div>
                <div class="cal-day-header">Mié</div>
                <div class="cal-day-header">Jue</div>
                <div class="cal-day-header">Vie</div>
                <div class="cal-day-header">Sáb</div>
            </div>
            <div id="miniCalendarGrid_{{ $doc->id }}" class="d-grid" style="grid-template-columns: repeat(7, 1fr); gap: 4px;">
                <!-- Días inyectados -->
            </div>
        </div>

        <!-- Columna Derecha: Timeline Diario -->
        <div class="col-lg-7 d-flex flex-column agenda-timeline-col" style="background: #fafafa; min-height:320px;">
            <div class="px-3 py-2 bg-white border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2 shadow-sm z-1 agenda-timeline-header">
                <div>
                    <h6 id="selectedDateLabel_{{ $doc->id }}" class="fw-bold mb-0 text-dark">Agenda Diaria</h6>
                    <small class="text-muted" style="font-size: 0.75rem;" id="selectedDateSubLabel_{{ $doc->id }}">Seleccione cualquier día en el calendario</small>
                </div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <button class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold d-none" id="specialOpenBtn_{{ $doc->id }}" onclick="openSpecialOpeningModal({{ $doc->id }})" title="Habilitar una franja excepcional este día (ej: 2 hs)">
                        <i class="bi bi-clock-history me-1"></i> Habilitar Franja (ej. 2 hs)
                    </button>
                    <button class="btn btn-sm btn-outline-warning rounded-pill px-3 fw-bold d-none text-dark" id="blockAgendaBtn_{{ $doc->id }}" onclick="openBlockModal({{ $doc->id }})">
                        <i class="bi bi-shield-lock-fill me-1"></i> Bloquear Agenda
                    </button>
                    <div id="dateLoadingSpinner_{{ $doc->id }}" class="spinner-border text-primary spinner-border-sm d-none" role="status"></div>
                </div>
            </div>
            
            <div id="slotsContainer_{{ $doc->id }}" class="p-3 flex-grow-1 overflow-auto" style="max-height: 360px;">
                <!-- Estado vacío -->
                <div class="text-center py-4 opacity-50 text-muted">
                    <i class="bi bi-calendar-x" style="font-size: 4rem;"></i>
                    <h6 class="mt-3 text-dark">Sin selección</h6>
                    <small>Haga clic en un día del calendario para ver horarios, desbloquear turnos o habilitar una franja especial.</small>
                </div>
            </div>

            <!-- Listado rápido de Bloqueos Activos del Médico (abajo a la derecha) -->
            <div id="activeBlocksStrip_{{ $doc->id }}" class="px-3 py-2 bg-warning bg-opacity-10 border-top d-none" style="font-size: 0.76rem;"></div>
            
            <!-- Phase 17: Express Nearest Slot Footer (Moved inside doctor card) -->
            <div class="mt-auto bg-white border-top p-3 d-flex align-items-center justify-content-between shadow-sm z-1 agenda-express-footer" style="border-left: 4px solid #198754;">
                <div class="d-flex flex-column">
                    <span class="text-success small fw-bold text-uppercase d-flex align-items-center mb-1" style="letter-spacing: 0.5px;"><i class="bi bi-lightning-charge-fill me-2"></i>Próximo turno libre</span>
                    <span id="nearestSlotText_{{ $doc->id }}" class="text-dark fw-bold" style="font-size: 0.95rem;">
                         <span class="spinner-border spinner-border-sm text-success me-2"></span> Buscando disponibilidad...
                    </span>
                </div>
                <button class="btn btn-sm btn-success rounded-pill shadow-sm px-3 d-none fw-bold" id="nearestSlotBtn_{{ $doc->id }}"><i class="bi bi-calendar-plus me-1"></i> Asignar</button>
            </div>
        </div>
    </div>
</div>
@endforeach

<!-- Modal: Generar Turno (Reutilizado) -->
<div class="modal fade" id="newAppointmentModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 rounded-4 shadow">
        <div class="modal-header border-bottom-0 pb-0">
            <h5 class="modal-title fw-bold"><i class="bi bi-calendar-event text-primary me-2"></i>Agendar Paciente</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form action="{{ route('agenda.store') }}" method="POST" id="newAppointmentForm">
            @csrf
            <!-- Oculto el doctor -->
            <input type="hidden" name="doctor_id" id="formDoctorSelect">
            
            <div class="modal-body p-4">
                <div class="row g-3">
                    <div class="col-md-12 position-relative">
                        <label class="form-label text-muted small fw-bold">Buscar Paciente Existente *</label>
                        <!-- Hidden input for the final selected patient ID -->
                        <input type="hidden" name="patient_id" id="modalSelectedPatientId" required>
                        
                        <!-- Search Mode Interface -->
                        <div id="modalPatientSearchMode">
                            <div class="position-relative">
                                <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                                <input type="text" id="modalPatientSearchInput" class="form-control bg-light border-0 shadow-sm ps-5" style="border-radius: 12px; height: 50px;" placeholder="Tipee Apellido o DNI aquí..." autocomplete="off">
                            </div>
                            <div id="modalPatientSearchResults" class="list-group position-absolute w-100 shadow mt-1 p-0 rounded-3 d-none z-3" style="max-height: 250px; overflow-y: auto;">
                                <!-- Live Search Items Inject Here -->
                            </div>
                        </div>

                        <!-- Selected Mode Interface -->
                        <div id="modalPatientSelectedMode" class="d-none mt-2">
                            <div class="card bg-success bg-opacity-10 border-0 rounded-4 p-3 d-flex flex-row align-items-center justify-content-between shadow-sm" style="border: 2px solid rgba(40, 167, 69, 0.2) !important;">
                                <div class="d-flex align-items-center">
                                    <div class="rounded-circle bg-white text-success d-flex align-items-center justify-content-center shadow-sm me-3" style="width: 45px; height: 45px;">
                                        <i class="bi bi-person-check-fill fs-4"></i>
                                    </div>
                                    <div>
                                        <h6 class="fw-bold mb-0 text-dark" id="modalSelectedPatientName">--</h6>
                                        <small class="text-muted" id="modalSelectedPatientDni">--</small>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-sm btn-light border-0 text-danger rounded-circle shadow-sm" onclick="clearModalPatientSelection(true)" title="Cambiar Paciente"><i class="bi bi-x-lg"></i></button>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label text-muted small fw-bold">Fecha del Turno *</label>
                        <input type="date" name="date" class="form-control bg-light border-0 shadow-none" id="formDateSelect" required readonly>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label text-muted small fw-bold">Horario de Inicio *</label>
                        <input type="time" name="time" class="form-control bg-light border-0 shadow-none" id="formTimeSelect" required>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label text-muted small fw-bold">Duración Estimada (Minutos)</label>
                        <input type="number" name="duration_minutes" class="form-control bg-light border-0 shadow-none" id="formDurationSelect" required readonly>
                    </div>

                    <div class="col-md-12">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label text-muted small fw-bold mb-0">Motivo / Notas rápidas</label>
                            <span class="text-muted small" style="font-size: 0.72rem;">Presets rápidos:</span>
                        </div>
                        <div class="d-flex flex-wrap gap-1 mb-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill py-0 px-2" style="font-size: 0.72rem;" onclick="setAppointmentReasonPreset('Control')">Control</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill py-0 px-2" style="font-size: 0.72rem;" onclick="setAppointmentReasonPreset('Anteojos / Refracción')">Anteojos</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill py-0 px-2" style="font-size: 0.72rem;" onclick="setAppointmentReasonPreset('Fondo de Ojo')">Fondo de Ojo</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill py-0 px-2" style="font-size: 0.72rem;" onclick="setAppointmentReasonPreset('Consulta Quirúrgica (QX)')">Consulta QX</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill py-0 px-2" style="font-size: 0.72rem;" onclick="setAppointmentReasonPreset('Estudios Visuales')">Estudio</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill py-0 px-2" style="font-size: 0.72rem;" onclick="setAppointmentReasonPreset('Urgencia')">Urgencia</button>
                        </div>
                        <input type="text" name="reason" id="appointmentReasonInput" class="form-control bg-light border-0 shadow-none" placeholder="Ej: Control, Anteojos, Fondo de ojo...">
                    </div>
                    <div class="col-md-12">
                        <input type="hidden" name="send_whatsapp_present" value="1">
                        <div class="form-check form-switch p-2 px-3 rounded-3 bg-success bg-opacity-10 border border-success border-opacity-25 d-flex align-items-center justify-content-between">
                            <label class="form-check-label fw-bold text-dark small mb-0" for="newApptSendWhatsapp">
                                <i class="bi bi-whatsapp text-success me-1"></i> Enviar confirmación por WhatsApp al instante (según reglas activas)
                            </label>
                            <input class="form-check-input ms-2" type="checkbox" name="send_whatsapp" id="newApptSendWhatsapp" value="1" checked>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top-0 pt-0 pe-4 flex-nowrap">
                <div class="d-flex gap-2 ms-auto">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" id="submitAppointmentBtn" class="btn btn-primary rounded-pill px-4 text-nowrap" style="background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%); border:none;" disabled><i class="bi bi-check2-circle me-1"></i> Confirmar y Agendar</button>
                </div>
            </div>
        </form>
    </div>
  </div>
</div>

<!-- Modal: Bloquear Horario / Agenda / Vacaciones (Diseño Amplio y Claro) -->
<div class="modal fade" id="blockAgendaModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 rounded-4 shadow-lg">
        <div class="modal-header border-bottom pb-3 px-4 pt-4">
            <div>
                <h5 class="modal-title fw-bold text-dark"><i class="bi bi-shield-lock-fill text-warning me-2"></i>Bloquear Turnos / Días / Vacaciones</h5>
                <small class="text-muted fw-bold" id="blockDoctorNameLabel">Médico: --</small>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form action="{{ route('api.agenda.blocks.store') }}" method="POST" id="blockAgendaForm">
            @csrf
            <input type="hidden" name="doctor_id" id="blockDoctorId">
            
            <div class="modal-body p-4">
                <div class="row g-4">
                    <div class="col-md-12">
                        <label class="form-label text-dark small fw-bold mb-2">Tipo de Bloqueo *</label>
                        <select name="block_type" id="blockTypeSelect" class="form-select form-select-lg bg-white border rounded-3 shadow-none fs-6 fw-semibold" required>
                            <option value="vacation">🌴 Vacaciones / Rango de días (Desde - Hasta)</option>
                            <option value="day">⛔ Día completo (Anular un día entero)</option>
                            <option value="range">⏰ Franja horaria (ej: Anular Mañana o Tarde)</option>
                            <option value="slot">🚫 Turno único (Un horario puntual)</option>
                        </select>
                    </div>

                    <!-- Fecha Desde -->
                    <div class="col-md-6" id="blockStartDateCol">
                        <div class="p-3 rounded-4 bg-light border h-100">
                            <label class="form-label text-dark small fw-bold mb-2" id="blockStartDateLabel"><i class="bi bi-calendar-event text-primary me-1"></i> Fecha Desde *</label>
                            <input type="date" name="date" class="form-control form-control-lg bg-white border rounded-3 shadow-none fs-6" id="blockDate" required>
                        </div>
                    </div>

                    <!-- Fecha Hasta (Vacaciones o Rango de Días) -->
                    <div class="col-md-6" id="blockEndDateCol">
                        <div class="p-3 rounded-4 bg-light border h-100">
                            <label class="form-label text-dark small fw-bold mb-2"><i class="bi bi-calendar-check text-primary me-1"></i> Fecha Hasta (inclusive) *</label>
                            <input type="date" name="end_date" class="form-control form-control-lg bg-white border rounded-3 shadow-none fs-6" id="blockEndDate">
                        </div>
                    </div>

                    <!-- Atajos rápidos para franjas horarias -->
                    <div class="col-12 time-fields d-none">
                        <div class="p-3 rounded-4 bg-warning bg-opacity-10 border border-warning border-opacity-50">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                                <span class="fw-bold text-dark small"><i class="bi bi-alarm me-1"></i> Seleccione el horario o use un atajo rápido:</span>
                                <div class="d-flex gap-2 flex-wrap">
                                    <button type="button" class="btn btn-sm btn-white bg-white border rounded-pill px-3 fw-bold shadow-sm" onclick="document.getElementById('blockStartTime').value='08:00'; document.getElementById('blockEndTime').value='13:00';">
                                        🌅 Mañana (08:00 a 13:00)
                                    </button>
                                    <button type="button" class="btn btn-sm btn-white bg-white border rounded-pill px-3 fw-bold shadow-sm" onclick="document.getElementById('blockStartTime').value='14:00'; document.getElementById('blockEndTime').value='20:00';">
                                        🌇 Tarde (14:00 a 20:00)
                                    </button>
                                </div>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label text-dark small fw-bold mb-1">Hora de Inicio *</label>
                                    <input type="time" name="start_time" class="form-control form-control-lg bg-white border rounded-3 shadow-none fs-6" id="blockStartTime">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-dark small fw-bold mb-1">Hora de Fin *</label>
                                    <input type="time" name="end_time" class="form-control form-control-lg bg-white border rounded-3 shadow-none fs-6" id="blockEndTime">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label text-dark small fw-bold mb-2">Motivo del Bloqueo *</label>
                        <input type="text" name="reason" id="blockReasonInput" class="form-control form-control-lg bg-white border rounded-3 shadow-none fs-6" placeholder="Ej: Vacaciones, Quirófano, Congreso, Licencia..." required>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top pt-3 pb-4 px-4">
                <button type="button" class="btn btn-light border rounded-pill px-4 py-2 fw-semibold" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" id="submitBlockBtn" class="btn btn-warning rounded-pill px-5 py-2 text-dark fw-bold shadow-sm"><i class="bi bi-lock-fill me-1"></i> Aplicar Bloqueo</button>
            </div>
        </form>
    </div>
  </div>
</div>

<!-- Modal: Reprogramar Turno Directo (Horarios Libres Desplegados + Opción Sobreturno) -->
<div class="modal fade" id="rescheduleAppointmentModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 rounded-4 shadow-lg">
        <div class="modal-header border-bottom pb-2 px-4 pt-3">
            <div>
                <h5 class="modal-title fw-bold text-dark"><i class="bi bi-arrow-repeat text-primary me-2"></i>Reprogramar Turno</h5>
                <small class="text-muted fw-bold" id="reschedulePatientLabel">Paciente: --</small>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form id="rescheduleAppointmentForm" onsubmit="submitRescheduleAppointment(event)">
            @csrf
            <input type="hidden" id="rescheduleAppointmentId">
            <input type="hidden" id="rescheduleOriginalDate">
            <input type="hidden" id="rescheduleOriginalDoctorId">
            <div class="modal-body p-4">
                <div class="alert alert-info border-0 rounded-3 small py-2 mb-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span><i class="bi bi-info-circle me-1"></i> Turno actual: <strong id="rescheduleCurrentDetails">--</strong></span>
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" id="rescheduleIsOverbooked" onchange="toggleRescheduleOverbookMode()">
                        <label class="form-check-label fw-bold text-dark" for="rescheduleIsOverbooked">
                            <i class="bi bi-lightning-charge-fill text-warning me-1"></i> Dar como Sobreturno
                        </label>
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label text-dark small fw-bold mb-1">Profesional</label>
                        <select id="rescheduleDoctorId" class="form-select bg-white border rounded-3 shadow-none" onchange="loadRescheduleFreeSlots()">
                            @foreach($doctors as $doc)
                                <option value="{{ $doc->id }}">{{ $doc->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-dark small fw-bold mb-1">Nueva Fecha *</label>
                        <input type="date" id="rescheduleNewDate" class="form-control bg-white border rounded-3 shadow-none fw-bold" required onchange="loadRescheduleFreeSlots()" oninput="loadRescheduleFreeSlots()">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-dark small fw-bold mb-1">Horario Elegido *</label>
                        <input type="time" id="rescheduleNewTime" class="form-control bg-white border rounded-3 shadow-none fw-bold text-primary" required>
                    </div>

                    <!-- Horarios Libres Desplegados Automáticamente -->
                    <div class="col-md-12" id="rescheduleFreeSlotsWrap">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label text-success small fw-bold mb-0">
                                <i class="bi bi-calendar-check-fill me-1"></i> Horarios Libres Disponibles en este Día (clic para elegir):
                            </label>
                            <small class="text-muted" id="rescheduleSlotsSummaryLabel"></small>
                        </div>
                        <div id="rescheduleFreeSlotsContainer" class="d-flex flex-wrap gap-2 p-3 rounded-3 bg-light border" style="max-height: 170px; overflow-y: auto;">
                            <span class="text-muted small">Seleccione una fecha para ver los horarios disponibles desplegados...</span>
                        </div>
                    </div>

                    <!-- Sección de Sobreturno en la misma ventana -->
                    <div class="col-md-12" id="rescheduleOverbookWrap">
                        <div class="p-3 rounded-3 border" style="background: #fffdf5; border-color: rgba(245, 158, 11, 0.4) !important;">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                                <span class="small fw-bold text-dark">
                                    <i class="bi bi-lightning-charge-fill text-warning me-1"></i> Opción Sobreturno (clic en cualquier horario libre u ocupado, o escriba hora manual arriba):
                                </span>
                                <span class="badge bg-warning bg-opacity-25 text-dark border border-warning" id="rescheduleOverbookBadge">Modo Turno Regular</span>
                            </div>
                            <div id="rescheduleOverbookSlotsContainer" class="d-flex flex-wrap gap-2" style="max-height: 130px; overflow-y: auto;">
                                <span class="text-muted small">Cargando horarios de sobreturno...</span>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label text-dark small fw-bold mb-1">Motivo / Nota (Opcional)</label>
                        <input type="text" id="rescheduleReason" class="form-control bg-white border rounded-3 shadow-none" placeholder="Ej: Solicitado por paciente, reprogramación...">
                    </div>
                    <div class="col-md-12">
                        <div class="form-check form-switch p-2 px-3 rounded-3 bg-success bg-opacity-10 border border-success border-opacity-25 d-flex align-items-center justify-content-between">
                            <label class="form-check-label fw-bold text-dark small mb-0" for="rescheduleSendWhatsapp">
                                <i class="bi bi-whatsapp text-success me-1"></i> Avisar reprogramación por WhatsApp al paciente
                            </label>
                            <input class="form-check-input ms-2" type="checkbox" id="rescheduleSendWhatsapp" value="1" checked>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top pt-3 pe-4">
                <button type="button" class="btn btn-light border rounded-pill px-4" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" id="btnSubmitReschedule" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm" style="background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%); border:none;">
                    <i class="bi bi-check2-circle me-1"></i> Confirmar Reprogramación
                </button>
            </div>
        </form>
    </div>
  </div>
</div>

<!-- Modal: Habilitar Franja Especial / Desbloquear Franja en Día Bloqueado (ej: Atender 2 hs) -->
<div class="modal fade" id="specialOpeningModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 rounded-4 shadow-lg">
        <div class="modal-header border-bottom pb-2 px-4 pt-3">
            <div>
                <h5 class="modal-title fw-bold text-dark" id="specialModalTitle"><i class="bi bi-clock-history text-primary me-2"></i>Habilitar Franja Horaria en este Día</h5>
                <small class="text-muted fw-bold" id="specialModalSubLabel">Seleccione desde y hasta qué hora desea atender</small>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form id="specialOpeningForm" onsubmit="submitSpecialOpening(event)">
            @csrf
            <input type="hidden" id="specialDoctorId">
            <input type="hidden" id="specialDate">
            <input type="hidden" id="specialActionMode" value="special_open">
            <div class="modal-body p-4">
                <div class="d-flex gap-2 flex-wrap mb-3">
                    <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold" onclick="document.getElementById('specialStartTime').value='09:00'; document.getElementById('specialEndTime').value='11:00';">2 hs Mañana (09 a 11)</button>
                    <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold" onclick="document.getElementById('specialStartTime').value='10:00'; document.getElementById('specialEndTime').value='12:00';">2 hs Mediodía (10 a 12)</button>
                    <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold" onclick="document.getElementById('specialStartTime').value='16:00'; document.getElementById('specialEndTime').value='18:00';">2 hs Tarde (16 a 18)</button>
                </div>
                <div class="row g-3">
                    <div class="col-6">
                        <label class="form-label text-dark small fw-bold mb-1">Hora Desde *</label>
                        <input type="time" id="specialStartTime" class="form-control form-control-lg bg-white border rounded-3 shadow-none" value="10:00" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label text-dark small fw-bold mb-1">Hora Hasta *</label>
                        <input type="time" id="specialEndTime" class="form-control form-control-lg bg-white border rounded-3 shadow-none" value="12:00" required>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top pt-3 pe-4">
                <button type="button" class="btn btn-light border rounded-pill px-4" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" id="btnSubmitSpecialOpening" class="btn btn-success rounded-pill px-4 fw-bold shadow-sm">
                    <i class="bi bi-unlock-fill me-1"></i> Habilitar Horarios
                </button>
            </div>
        </form>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script>
    // Phase 21: Modal Asíncrono Live Search Integration
    const modalPatientSearchInput = document.getElementById('modalPatientSearchInput');
    const modalPatientSearchResults = document.getElementById('modalPatientSearchResults');
    const modalSelectedPatientId = document.getElementById('modalSelectedPatientId');
    const modalPatientSearchMode = document.getElementById('modalPatientSearchMode');
    const modalPatientSelectedMode = document.getElementById('modalPatientSelectedMode');
    const modalSelectedPatientName = document.getElementById('modalSelectedPatientName');
    const modalSelectedPatientDni = document.getElementById('modalSelectedPatientDni');
    const submitAppointmentBtn = document.getElementById('submitAppointmentBtn');
    let searchDebounceTimeout = null;

    if (modalPatientSearchInput) {
        modalPatientSearchInput.addEventListener('input', function() {
            clearTimeout(searchDebounceTimeout);
            const q = this.value;

            if (q.length < 2) {
                modalPatientSearchResults.classList.add('d-none');
                return;
            }

            searchDebounceTimeout = setTimeout(() => {
                modalPatientSearchResults.innerHTML = '<div class="list-group-item text-center text-muted small py-3"><div class="spinner-border spinner-border-sm text-primary me-2"></div>Buscando base de datos...</div>';
                modalPatientSearchResults.classList.remove('d-none');

                axios.get(`/api/patients/search?q=${encodeURIComponent(q)}`)
                    .then(res => {
                        const patients = res.data;
                        if (patients.length === 0) {
                            modalPatientSearchResults.innerHTML = '<div class="list-group-item text-center text-muted small py-3 border-0 shadow-sm"><i class="bi bi-x-circle d-block fs-4 text-danger mb-2"></i>Paciente no encontrado</div>';
                            return;
                        }

                        modalPatientSearchResults.innerHTML = '';
                        patients.forEach(pt => {
                            const el = document.createElement('a');
                            el.href = '#';
                            el.className = 'list-group-item list-group-item-action d-flex align-items-center gap-3 py-3 border-bottom';
                            el.innerHTML = `
                                <div class="rounded-circle bg-light d-flex align-items-center justify-content-center text-primary" style="width: 40px; height: 40px;"><i class="bi bi-person"></i></div>
                                <div class="d-flex flex-column">
                                    <span class="fw-bold text-dark mb-0">${pt.last_name.toUpperCase()}, ${pt.first_name}</span>
                                    <small class="text-muted" style="font-size: 0.75rem;"><i class="bi bi-card-heading me-1"></i> DNI: ${pt.dni}</small>
                                </div>
                            `;
                            el.onclick = (e) => {
                                e.preventDefault();
                                selectPatientFromApi(pt.id, pt.first_name, pt.last_name, pt.dni);
                            };
                            modalPatientSearchResults.appendChild(el);
                        });
                    })
                    .catch(err => {
                        modalPatientSearchResults.innerHTML = '<div class="list-group-item text-center text-danger small py-3">Error de conexión con la base de datos</div>';
                    });
            }, 300);
        });
    }

    function selectPatientFromApi(id, fname, lname, dni) {
        modalSelectedPatientId.value = id;
        modalSelectedPatientName.textContent = `${lname.toUpperCase()}, ${fname}`;
        modalSelectedPatientDni.textContent = `DNI: ${dni}`;
        
        modalPatientSearchMode.classList.add('d-none');
        modalPatientSelectedMode.classList.remove('d-none');
        
        submitAppointmentBtn.removeAttribute('disabled');
        modalPatientSearchResults.classList.add('d-none');
    }

    const urlParams = new URLSearchParams(window.location.search);
    const preselectPatientId = urlParams.get('preselect_patient_id');
    const preselectPatientFname = urlParams.get('preselect_patient_fname');
    const preselectPatientLname = urlParams.get('preselect_patient_lname');
    const preselectPatientDni = urlParams.get('preselect_patient_dni');

    function clearModalPatientSelection(forceClear = false) {
        if (preselectPatientId && !forceClear) {
            selectPatientFromApi(preselectPatientId, preselectPatientFname, preselectPatientLname, preselectPatientDni);
            return;
        }

        modalSelectedPatientId.value = '';
        modalPatientSearchInput.value = '';
        
        modalPatientSelectedMode.classList.add('d-none');
        modalPatientSearchMode.classList.remove('d-none');
        
        submitAppointmentBtn.setAttribute('disabled', 'true');
        if (modalPatientSearchInput) modalPatientSearchInput.focus();
    }

    // Reset when modal closes to keep clean state
    const newAptModal = document.getElementById('newAppointmentModal');
    if (newAptModal) {
        newAptModal.addEventListener('hidden.bs.modal', event => {
            clearModalPatientSelection();
        });
        newAptModal.addEventListener('shown.bs.modal', event => {
            if(modalPatientSearchInput && modalPatientSearchMode.classList.contains('d-none') === false) {
                modalPatientSearchInput.focus();
            }
        });
    }

    // Phase 18: Multi-Agenda State Engine
    const doctorStates = {};

    @foreach($doctors as $doc)
        doctorStates[{{ $doc->id }}] = {
            currentDate: new Date(),
            selectedDateStr: null
        };
    @endforeach

    // Formatters
    const monthFormatter = new Intl.DateTimeFormat('es-AR', { month: 'long', year: 'numeric' });
    const fullDateFormatter = new Intl.DateTimeFormat('es-AR', { weekday: 'long', day: 'numeric', month: 'long' });

    function changeMonth(doctorId, dir) {
        doctorStates[doctorId].currentDate.setMonth(doctorStates[doctorId].currentDate.getMonth() + dir);
        renderCalendar(doctorId);
    }

    function renderCalendar(doctorId) {
        const state = doctorStates[doctorId];
        const year = state.currentDate.getFullYear();
        const month = state.currentDate.getMonth() + 1;

        document.getElementById(`monthYearLabel_${doctorId}`).innerText = monthFormatter.format(state.currentDate);

        axios.get(`{{ route('api.agenda.availability') }}?doctor_id=${doctorId}&year=${year}&month=${month}`)
            .then(res => {
                drawGrid(doctorId, year, month, res.data);
            });
    }

    function drawGrid(doctorId, year, month, availabilityMap) {
        const grid = document.getElementById(`miniCalendarGrid_${doctorId}`);
        grid.innerHTML = '';
        const state = doctorStates[doctorId];

        const firstDay = new Date(year, month - 1, 1);
        const lastDay = new Date(year, month, 0);
        
        for (let i = 0; i < firstDay.getDay(); i++) {
            grid.innerHTML += `<div class="cal-day empty"></div>`;
        }

        for (let day = 1; day <= lastDay.getDate(); day++) {
            const dateStr = `${year}-${String(month).padStart(2,'0')}-${String(day).padStart(2,'0')}`;
            const dayData = availabilityMap[dateStr] || { status: 'unavailable', percentage: 0 };
            
            const status = typeof dayData === 'object' ? dayData.status : dayData;
            const percentage = typeof dayData === 'object' ? dayData.percentage : 0;
            const isBlocked = typeof dayData === 'object' ? Boolean(dayData.is_blocked) : (status === 'blocked');
            const blockReason = typeof dayData === 'object' ? (dayData.reason || 'Bloqueado') : 'Bloqueado';
            
            let extraClass = '';
            const div = document.createElement('div');

            if (status === 'partial_block') {
                extraClass = 'partial-block';
                const freeCnt = dayData.available_slots ?? 0;
                const blkCnt = dayData.blocked_slots ?? 0;
                div.title = `Parcialmente Bloqueado (${freeCnt} hab. / ${blkCnt} bloq.) - ${blockReason}`;
            } else if (status === 'blocked' || isBlocked) {
                extraClass = 'blocked';
                div.title = `Día Bloqueado: ${blockReason}`;
                div.style.background = '#fff3cd';
                div.style.color = '#856404';
                div.style.border = '2px solid #ffc107';
                div.style.fontWeight = 'bold';
            } else if (status === 'unavailable') {
                extraClass = 'unavailable opacity-50';
                div.title = 'Sin consultorio habitual (clic para habilitar franja especial)';
                div.style.cursor = 'pointer';
            } else if (status === 'full') {
                extraClass = 'full';
                div.title = 'Agenda Llena (100%)';
            } else if (status === 'available') {
                extraClass = 'available';
                div.title = `Disponible (Ocupado: ${percentage}%)`;
                
                div.style.setProperty('--fill-percentage', `${percentage}%`);
                if (percentage >= 50) {
                    div.setAttribute('data-filled', 'true');
                }
            }

            if (dateStr === state.selectedDateStr) extraClass += ' selected-day';

            div.className = `cal-day ${extraClass}`;
            div.innerText = day;
            
            // Todos los días son clickeables (incluso días libres por si desea abrir 2 hs excepcionales)
            div.onclick = (e) => loadDaySlots(doctorId, dateStr, e);

            grid.appendChild(div);
        }
    }

    function renderActiveBlocksStrip(doctorId, activeBlocks, currentDateStr) {
        const strip = document.getElementById(`activeBlocksStrip_${doctorId}`);
        if (!strip) return;
        if (!activeBlocks || activeBlocks.length === 0) {
            strip.classList.add('d-none');
            strip.innerHTML = '';
            return;
        }
        strip.classList.remove('d-none');
        const itemsHtml = activeBlocks.map(b => `
            <span class="badge bg-white text-dark border shadow-sm d-inline-flex align-items-center gap-2 px-2 py-1">
                <span><i class="bi bi-lock-fill text-warning me-1"></i><strong>${b.reason}:</strong> ${b.label}</span>
                <button type="button" class="btn btn-link btn-sm p-0 text-danger fw-bold text-decoration-none" title="Anular este bloqueo completo y rehabilitar días habituales" onclick="unblockEntireBlock(${b.id}, ${doctorId}, '${currentDateStr || b.start_date}')">
                    <i class="bi bi-x-circle-fill"></i> Desbloquear
                </button>
            </span>
        `).join(' ');
        strip.innerHTML = `<div class="d-flex align-items-center flex-wrap gap-2"><span class="fw-bold text-dark"><i class="bi bi-shield-lock me-1"></i>Bloqueos activos:</span> ${itemsHtml}</div>`;
    }

    function loadDaySlots(doctorId, dateStr, e) {
        doctorStates[doctorId].selectedDateStr = dateStr;
        
        const grid = document.getElementById(`miniCalendarGrid_${doctorId}`);
        if (grid) {
            grid.querySelectorAll('.cal-day').forEach(el => el.classList.remove('selected-day'));
        }
        if (e && e.target) {
             e.target.classList.add('selected-day');
        }

        const blockBtn = document.getElementById(`blockAgendaBtn_${doctorId}`);
        if (blockBtn) blockBtn.classList.remove('d-none');
        const specialBtn = document.getElementById(`specialOpenBtn_${doctorId}`);
        if (specialBtn) specialBtn.classList.remove('d-none');

        const [y, m, d] = dateStr.split('-');
        const dateObj = new Date(y, m - 1, d);
        
        document.getElementById(`selectedDateLabel_${doctorId}`).innerText = fullDateFormatter.format(dateObj);
        document.getElementById(`selectedDateSubLabel_${doctorId}`).innerText = "Cargando tiempos...";
        document.getElementById(`dateLoadingSpinner_${doctorId}`).classList.remove('d-none');
        
        const slotsContainer = document.getElementById(`slotsContainer_${doctorId}`);
        slotsContainer.innerHTML = '';

        axios.get(`{{ route('api.agenda.slots') }}?doctor_id=${doctorId}&date=${dateStr}`)
            .then(res => {
                document.getElementById(`dateLoadingSpinner_${doctorId}`).classList.add('d-none');
                renderActiveBlocksStrip(doctorId, res.data.active_doctor_blocks || [], dateStr);
                
                if (res.data.slots.length === 0) {
                     document.getElementById(`selectedDateSubLabel_${doctorId}`).innerText = res.data.message || "Día libre / Sin horario habitual.";
                     let emptyHtml = '';
                     if (res.data.full_day_block) {
                         const blk = res.data.full_day_block;
                         emptyHtml += `
                         <div class="alert alert-warning border-0 rounded-4 p-3 d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3 shadow-sm" style="background: rgba(255, 193, 7, 0.2); border-left: 5px solid #ffc107 !important;">
                             <div>
                                 <h6 class="fw-bold mb-1 text-dark"><i class="bi bi-shield-lock-fill text-warning me-2"></i> Período Bloqueado (${blk.reason})</h6>
                                 <span class="text-dark small">${blk.start} al ${blk.end}</span>
                             </div>
                             <button class="btn btn-sm btn-danger rounded-pill px-3 fw-bold shadow-sm" onclick="unblockEntireBlock(${blk.id}, ${doctorId}, '${dateStr}')">
                                 <i class="bi bi-unlock-fill me-1"></i> Anular Bloqueo
                             </button>
                         </div>`;
                     }
                     emptyHtml += `
                        <div class="text-center py-4 text-muted">
                            <i class="bi bi-calendar-plus fs-1 text-primary opacity-50 d-block mb-2"></i>
                            <h6 class="text-dark fw-bold">${res.data.message || 'Día libre / Sin consultorio habitual'}</h6>
                            <p class="small mb-3">Si desea atender excepcionalmente en este día (por ejemplo 2 horas), habilite una franja horaria:</p>
                            <button type="button" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm" onclick="openSpecialOpeningModal(${doctorId}, '${dateStr}', 'special_open')">
                                <i class="bi bi-clock-fill me-1"></i> Habilitar Horario Especial (ej. 2 hs)
                            </button>
                        </div>`;
                     slotsContainer.innerHTML = emptyHtml;
                     return;
                }

                let totalLibres = 0;
                let totalExtras = 0;
                let totalOcupados = 0;
                let totalBloqueados = 0;

                res.data.slots.forEach(slot => {
                    if (slot.status === 'free') {
                        totalLibres++;
                    } else if (slot.status === 'blocked') {
                        totalBloqueados++;
                    } else if (slot.status === 'booked') {
                        totalOcupados++;
                        if (slot.is_extra) totalExtras++;
                    }
                });

                document.getElementById(`selectedDateSubLabel_${doctorId}`).innerHTML = 
                    `<span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1 me-1"><i class="bi bi-calendar-check me-1"></i>Libres: ${totalLibres}</span> ` +
                    (totalBloqueados > 0 ? `<span class="badge bg-warning bg-opacity-25 text-dark border border-warning px-2 py-1 me-1"><i class="bi bi-lock-fill me-1"></i>Bloq: ${totalBloqueados}</span> ` : '') +
                    `<span class="badge bg-warning bg-opacity-25 text-dark border border-warning border-opacity-50 px-2 py-1 me-1"><i class="bi bi-lightning-charge-fill text-warning me-1"></i>Sobreturnos: ${totalExtras}</span> ` +
                    `<span class="badge bg-light text-dark border px-2 py-1"><i class="bi bi-people-fill me-1"></i>Ocupados: ${totalOcupados}</span>`;

                let html = '';

                // Banner de Día Completamente Bloqueado (con desbloqueo del día, de una franja de ej. 2 hs, o de todo el período)
                if (res.data.full_day_block) {
                    const blk = res.data.full_day_block;
                    html += `
                    <div class="alert alert-warning border-0 rounded-4 p-3 mb-3 shadow-sm" style="background: rgba(255, 193, 7, 0.2); border-left: 5px solid #ffc107 !important;">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <div>
                                <h6 class="fw-bold mb-1 text-dark"><i class="bi bi-shield-lock-fill text-warning me-2 fs-5"></i> DÍA BLOQUEADO — Horario habitual visible abajo</h6>
                                <span class="text-dark small"><strong>Motivo:</strong> ${blk.reason} (${blk.start} al ${blk.end}) · Puede liberar todo el día o habilitar solo algunos horarios abajo.</span>
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                <button type="button" class="btn btn-sm btn-primary rounded-pill px-3 fw-bold shadow-sm" onclick="openSpecialOpeningModal(${doctorId}, '${dateStr}', 'unblock_range')">
                                    <i class="bi bi-clock me-1"></i> Habilitar Franja (ej. 2 hs)
                                </button>
                                <button type="button" class="btn btn-sm btn-success rounded-pill px-3 fw-bold shadow-sm" onclick="unblockDayOnly(${doctorId}, '${dateStr}')">
                                    <i class="bi bi-unlock-fill me-1"></i> Desbloquear Todo el Día
                                </button>
                                ${blk.is_multi_day ? `
                                <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3 fw-bold bg-white" onclick="unblockEntireBlock(${blk.id}, ${doctorId}, '${dateStr}')">
                                    <i class="bi bi-trash me-1"></i> Anular Período (${blk.start}-${blk.end})
                                </button>` : ''}
                            </div>
                        </div>
                    </div>`;
                } else if (res.data.partial_day_block) {
                    const blk = res.data.partial_day_block;
                    html += `
                    <div class="alert border-0 rounded-4 p-3 mb-3 shadow-sm" style="background: #f3e8ff; border-left: 5px solid #9333ea !important;">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <div>
                                <h6 class="fw-bold mb-1" style="color:#581c87;"><i class="bi bi-circle-half me-2"></i> DÍA PARCIALMENTE BLOQUEADO</h6>
                                <span class="text-dark small"><strong>Motivo:</strong> ${blk.reason} · <strong>${blk.free_slots}</strong> horarios habilitados y <strong>${blk.blocked_slots}</strong> bloqueados.</span>
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                <button type="button" class="btn btn-sm btn-outline-primary bg-white rounded-pill px-3 fw-bold" onclick="openSpecialOpeningModal(${doctorId}, '${dateStr}', 'unblock_range')">
                                    <i class="bi bi-clock me-1"></i> Habilitar Otra Franja
                                </button>
                                <button type="button" class="btn btn-sm btn-success rounded-pill px-3 fw-bold shadow-sm" onclick="unblockDayOnly(${doctorId}, '${dateStr}')">
                                    <i class="bi bi-unlock-fill me-1"></i> Desbloquear Todo el Día
                                </button>
                            </div>
                        </div>
                    </div>`;
                }

                if (res.data.special_openings && res.data.special_openings.length > 0) {
                    html += `
                    <div class="alert alert-info border-0 rounded-3 py-2 px-3 mb-2 d-flex justify-content-between align-items-center flex-wrap gap-2 small">
                        <span><i class="bi bi-stars text-primary me-1"></i> <strong>Horario especial habilitado en esta fecha</strong></span>
                        <button type="button" class="btn btn-sm btn-outline-danger rounded-pill py-0 px-2 fw-bold" style="font-size:0.72rem;" onclick="removeSpecialOpening(${doctorId}, '${dateStr}')">
                            Quitar horario especial
                        </button>
                    </div>`;
                }

                // Barra de referencias
                html += `
                <div class="d-flex gap-2 mb-3 justify-content-start flex-wrap bg-light p-2 rounded-3 border" style="font-size: 0.75rem;">
                    <span class="text-muted fw-bold text-uppercase me-2 align-self-center" style="font-size: 0.65rem;">Referencias:</span>
                    <span class="badge border bg-white text-success px-2 py-1"><span class="d-inline-block rounded-circle me-1" style="width:8px; height:8px; background:#198754;"></span> Turno Asignado</span>
                    <span class="badge border bg-white text-primary px-2 py-1"><span class="d-inline-block rounded-circle me-1" style="width:8px; height:8px; background:#0d6efd;"></span> Sobreturno</span>
                    <span class="badge border bg-white text-warning px-2 py-1"><span class="d-inline-block rounded-circle me-1" style="width:8px; height:8px; background:#fd7e14;"></span> Bloqueado</span>
                </div>`;
                
                res.data.slots.forEach(slot => {
                    const isPast = slot.is_past;

                    if (slot.status === 'booked') {
                        let sobreTurnoBtn = !isPast ? `
                            <button class="btn btn-sm text-primary fw-bold px-3 py-1 border-0 me-2" onclick="openBookingFromExpress(${doctorId}, '${dateStr}', '${slot.time}', ${res.data.slot_duration})" title="Añadir sobreturno en este horario" style="font-size:0.7rem; border-radius: 20px; background: rgba(94, 106, 210, 0.1);">
                                + Sobreturno
                            </button>` : `<span class="badge bg-secondary bg-opacity-10 text-secondary border me-2">Expirado</span>`;

                        const escapedName = (slot.patient_name || '').replace(/'/g, "\\'");
                        const escapedNotes = ((slot.notes || '') + (slot.reason ? ' ' + slot.reason : '')).replace(/'/g, "\\'");

                        let darEntradaBtn = `
                            <button class="btn btn-sm btn-outline-success fw-bold px-2 py-1 border-0 me-2" onclick="openDarEntradaModal(${slot.patient_id}, '${escapedName}', ${doctorId}, '${escapedNotes}')" title="Dar entrada a Sala de Espera" style="font-size:0.7rem; border-radius: 20px; background: rgba(25, 135, 84, 0.1);">
                                <i class="bi bi-box-arrow-in-right me-1"></i> Entrada
                            </button>`;

                        let actionsHtml = `
                            ${darEntradaBtn}
                            ${sobreTurnoBtn}
                            <button class="btn btn-sm btn-light p-1 border-0 rounded-circle shadow-none" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-three-dots-vertical fs-5 text-muted"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3 mt-1">
                                <li><button class="dropdown-item py-2 text-success fw-bold" onclick="openDarEntradaModal(${slot.patient_id}, '${escapedName}', ${doctorId}, '${escapedNotes}')"><i class="bi bi-box-arrow-in-right me-2"></i> Dar Entrada (Sala de Espera)</button></li>
                                <li><button class="dropdown-item py-2 text-primary fw-bold" onclick="openRescheduleModal(${slot.appointment_id}, '${escapedName}', '${dateStr}', '${slot.time}', ${doctorId})"><i class="bi bi-arrow-repeat me-2"></i> Reprogramar Turno</button></li>
                                <li><button class="dropdown-item py-2 text-success fw-bold" onclick="sendAppointmentWhatsappNow(${slot.appointment_id})"><i class="bi bi-whatsapp me-2"></i> Enviar Recordatorio WhatsApp</button></li>
                                <li><a class="dropdown-item py-2 text-dark fw-bold" href="/patients/${slot.patient_id}"><i class="bi bi-folder2-open me-2 text-primary"></i> Abrir Historia Clínica</a></li>
                                <li><button class="dropdown-item py-2 text-warning text-dark fw-bold" onclick="markAsNoShow(${slot.appointment_id}, ${doctorId}, '${dateStr}')"><i class="bi bi-person-x-fill me-2"></i> Marcar Ausencia</button></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><button class="dropdown-item py-2 text-danger fw-bold" onclick="cancelAppointment(${slot.appointment_id}, ${doctorId}, '${dateStr}')"><i class="bi bi-trash me-2"></i> Eliminar Turno</button></li>
                            </ul>`;

                        const isExtra = slot.is_extra || false;
                        const cardClass = isExtra ? 'slot-extra' : 'slot-booked';
                        const timeColorClass = isExtra ? 'text-warning' : 'text-success';
                        const statusText = isExtra ? '<i class="bi bi-lightning-charge-fill me-1"></i>SOBRETURNO' : 'Asignado';
                        const statusColorClass = isExtra ? 'bg-warning bg-opacity-25 text-dark border border-warning' : 'bg-success bg-opacity-10 text-success';
                        const iconClass = isExtra ? 'bi-lightning-charge-fill text-warning' : 'bi-person-check-fill text-primary';

                        html += `
                        <div class="slot-card ${cardClass} flex-column align-items-stretch mb-2 ${isPast ? 'opacity-75' : ''}">
                            <div class="d-flex align-items-center justify-content-between w-100">
                                <div class="d-flex align-items-center gap-3 pe-2">
                                    <span class="slot-time d-block ${timeColorClass}">${slot.time}</span>
                                    <div>
                                        <h6 class="mb-0 fw-bold">
                                            <a href="/patients/${slot.patient_id}" class="text-dark text-decoration-none" title="Abrir Historia Clínica de ${slot.patient_name}">
                                                <i class="bi ${iconClass} text-primary me-1"></i> ${slot.patient_name}
                                            </a>
                                        </h6>
                                        <div class="d-flex flex-wrap align-items-center gap-2 mt-1" style="font-size: 0.78rem;">
                                            ${slot.patient_dni ? `<span class="text-muted"><i class="bi bi-card-text me-1"></i>DNI: <strong>${slot.patient_dni}</strong></span>` : ''}
                                            ${slot.patient_phone ? `<span class="text-muted"><i class="bi bi-telephone me-1"></i>${slot.patient_phone}</span>` : ''}
                                            ${slot.patient_os ? `<span class="badge bg-light text-primary border"><i class="bi bi-hospital me-1"></i>${slot.patient_os} ${slot.patient_affiliate ? '(' + slot.patient_affiliate + ')' : ''}</span>` : ''}
                                            <span class="badge ${isExtra ? 'bg-primary' : 'bg-success'} bg-opacity-10 ${statusColorClass}">${statusText}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center dropdown">
                                    ${actionsHtml}
                                </div>
                            </div>
                            ${(slot.reason || slot.notes) ? `
                            <div class="mt-2 pt-1 border-top d-flex flex-column gap-1" style="font-size: 0.75rem;">
                                ${slot.reason ? `<div><span class="text-secondary fw-bold">Motivo:</span> <span class="text-dark">${slot.reason}</span></div>` : ''}
                                ${slot.notes ? `<div><span class="text-primary fw-bold"><i class="bi bi-chat-left-dots me-1"></i>Secretaría:</span> <span class="text-muted fst-italic">${slot.notes}</span></div>` : ''}
                            </div>
                            ` : ''}
                        </div>`;
                    } else if (slot.status === 'no_show') {
                        html += `
                        <div class="slot-card slot-noshow flex-row justify-content-between mb-2" style="border-left: 4px solid #ccc; background: #fafafa;">
                            <div class="d-flex align-items-center gap-3 pe-2">
                                <span class="slot-time d-block text-muted" style="text-decoration: line-through;">${slot.time}</span>
                                <div>
                                    <h6 class="mb-0 fw-bold text-muted" style="text-decoration: line-through;"><i class="bi bi-person-x-fill me-1"></i> ${slot.patient_name}</h6>
                                    <div class="d-flex gap-2 align-items-center mt-1">
                                        <span class="badge border border-secondary text-secondary rounded-pill" style="font-size: 0.7rem;">Ausente</span>
                                        ${slot.patient_dni ? `<small class="text-muted">DNI: ${slot.patient_dni}</small>` : ''}
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex align-items-center dropdown">
                                <button class="btn btn-sm btn-light p-1 border-0 rounded-circle shadow-none" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bi bi-three-dots-vertical fs-5 text-muted"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3 mt-1">
                                    <li><button class="dropdown-item py-2 text-primary fw-bold" onclick="openRescheduleModal(${slot.appointment_id}, '${(slot.patient_name || '').replace(/'/g, "\\'")}', '${dateStr}', '${slot.time}', ${doctorId})"><i class="bi bi-arrow-repeat me-2"></i> Reprogramar Turno</button></li>
                                    <li><a class="dropdown-item py-2 text-dark fw-bold" href="/patients/${slot.patient_id}"><i class="bi bi-folder2-open me-2 text-primary"></i> Abrir HC</a></li>
                                    <li><button class="dropdown-item py-2 text-danger fw-bold" onclick="cancelAppointment(${slot.appointment_id}, ${doctorId}, '${dateStr}')"><i class="bi bi-trash me-2"></i> Eliminar Turno</button></li>
                                </ul>
                            </div>
                        </div>`;
                    } else if (slot.status === 'blocked') {
                        const escapedReason = (slot.reason || 'Bloqueado').replace(/'/g, "\\'");
                        html += `
                        <div class="slot-card slot-blocked justify-content-between position-relative pe-2 mb-2">
                            <div class="d-flex align-items-center gap-3 flex-grow-1">
                                <span class="slot-time d-block text-warning">${slot.time}</span>
                                <div>
                                    <h6 class="mb-0 text-warning fw-bold"><i class="bi bi-lock-fill me-1"></i> Horario Bloqueado</h6>
                                    <small class="text-muted">${slot.reason} ${slot.conflict_patient ? ' - <strong>Conflicto:</strong> ' + slot.conflict_patient : ''}</small>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-1 dropdown z-2">
                                <button class="btn btn-sm btn-outline-success rounded-pill px-3 shadow-none fw-bold" onclick="unblockSingleSlot(${doctorId}, '${dateStr}', '${slot.time}', ${res.data.slot_duration})" style="font-size:0.72rem;" title="Desbloquear únicamente el horario ${slot.time}">
                                    <i class="bi bi-unlock-fill me-1"></i> Desbloquear ${slot.time}
                                </button>
                                <button class="btn btn-sm btn-light p-1 border-0 rounded-circle shadow-none" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Más opciones sobre este horario bloqueado">
                                    <i class="bi bi-three-dots-vertical fs-5 text-muted"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3 mt-1">
                                    <li><button class="dropdown-item py-2 text-success fw-bold" onclick="unblockSingleSlot(${doctorId}, '${dateStr}', '${slot.time}', ${res.data.slot_duration})"><i class="bi bi-unlock-fill me-2"></i> Desbloquear solo este horario (${slot.time})</button></li>
                                    <li><button class="dropdown-item py-2 text-primary fw-bold" onclick="openSpecialOpeningModal(${doctorId}, '${dateStr}', 'unblock_range', '${slot.time}')"><i class="bi bi-clock me-2"></i> Habilitar franja desde ${slot.time} (ej. 2 hs)</button></li>
                                    <li><button class="dropdown-item py-2 text-dark fw-bold" onclick="unblockAndBookSlot(${doctorId}, '${dateStr}', '${slot.time}', ${res.data.slot_duration})"><i class="bi bi-calendar-plus me-2 text-success"></i> Liberar y asignar turno en ${slot.time}</button></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><button class="dropdown-item py-2 text-warning text-dark fw-bold" onclick="unblockDayOnly(${doctorId}, '${dateStr}')"><i class="bi bi-calendar-check me-2"></i> Desbloquear todo este día</button></li>
                                    ${slot.block_id ? `<li><button class="dropdown-item py-2 text-danger fw-bold" onclick="unblockEntireBlock(${slot.block_id}, ${doctorId}, '${dateStr}')"><i class="bi bi-trash me-2"></i> Anular todo el bloqueo (${escapedReason})</button></li>` : ''}
                                </ul>
                            </div>
                        </div>`;
                    } else {
                        if (!isPast) {
                            html += `
                            <div class="slot-card slot-free justify-content-between position-relative pe-2 mb-2">
                                <div class="d-flex align-items-center gap-3 flex-grow-1" onclick="openBookingFromExpress(${doctorId}, '${dateStr}', '${slot.time}', ${res.data.slot_duration})">
                                    <span class="slot-time d-block text-success">${slot.time}</span>
                                    <div>
                                        <h6 class="mb-0 text-success fw-bold">Turno Libre ${slot.is_special ? '<span class="badge bg-info bg-opacity-10 text-primary border ms-1" style="font-size:0.65rem;">Franja Especial</span>' : ''}</h6>
                                        <small class="text-muted">Clic para asignar un paciente</small>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center dropdown z-2">
                                    <button class="btn btn-sm text-primary fw-bold px-3 py-1 border-0 me-2" onclick="openBookingFromExpress(${doctorId}, '${dateStr}', '${slot.time}', ${res.data.slot_duration})" title="Añadir sobreturno en este horario" style="font-size:0.7rem; border-radius: 20px; background: rgba(94, 106, 210, 0.1);">
                                        + Sobreturno
                                    </button>
                                    <button class="btn btn-sm text-warning fw-bold px-3 py-1 border-0 me-2" onclick="quickBlockSlot(${doctorId}, '${dateStr}', '${slot.time}', ${res.data.slot_duration})" title="Bloquear este turno" style="font-size:0.7rem; border-radius: 20px; background: rgba(253, 126, 20, 0.1);">
                                        <i class="bi bi-lock-fill"></i> Bloquear
                                    </button>
                                    <button class="btn btn-sm btn-success rounded-circle shadow-sm" style="width:30px; height:30px; padding:0;" onclick="openBookingFromExpress(${doctorId}, '${dateStr}', '${slot.time}', ${res.data.slot_duration})">
                                        <i class="bi bi-plus text-white"></i>
                                    </button>
                                </div>
                            </div>`;
                        } else {
                            html += `
                            <div class="slot-card justify-content-between position-relative pe-2 mb-2" style="background: #f8f9fa; border: 1px dashed #ced4da; opacity: 0.6; cursor: not-allowed;">
                                <div class="d-flex align-items-center gap-3 flex-grow-1">
                                    <span class="slot-time d-block text-muted">${slot.time}</span>
                                    <div>
                                        <h6 class="mb-0 text-muted fw-bold">No Disponible</h6>
                                        <small class="text-muted">Horario expirado</small>
                                    </div>
                                </div>
                            </div>`;
                        }
                    }
                });

                slotsContainer.innerHTML = html;
            });
    }
    // Phase 17: Express Slots Loader (Integrated into Doctor Cards)
    function loadNearestSlots() {
        // Pre-cleaning UI to show we are searching
        const allDocIds = Object.keys(doctorStates);
        allDocIds.forEach(id => {
            const textEl = document.getElementById(`nearestSlotText_${id}`);
            const btnEl = document.getElementById(`nearestSlotBtn_${id}`);
            if (textEl) textEl.innerHTML = `<span class="spinner-border spinner-border-sm text-success me-2"></span> Buscando disponibilidad...`;
            if (btnEl) btnEl.classList.add('d-none');
        });

        axios.get(`{{ route('api.agenda.nearest') }}`)
            .then(res => {
                // If the entire system has 0 slots
                if(res.data.length === 0) {
                    allDocIds.forEach(id => {
                        const textEl = document.getElementById(`nearestSlotText_${id}`);
                        if (textEl) textEl.innerHTML = `<i class="bi bi-x-circle text-danger me-1"></i> Agenda Saturada (2+ meses)`;
                    });
                    return;
                }
                
                // Track which doctors got a slot
                const foundDocs = new Set();
                
                res.data.forEach(slot => {
                    foundDocs.add(String(slot.doctor_id));
                    const textEl = document.getElementById(`nearestSlotText_${slot.doctor_id}`);
                    const btnEl = document.getElementById(`nearestSlotBtn_${slot.doctor_id}`);
                    
                    if (textEl && btnEl) {
                        textEl.innerHTML = `${slot.formatted_date} <span class="ms-1" style="color: #666; font-size: 0.9em;">| ${slot.time} hs</span>`;
                        btnEl.classList.remove('d-none');
                        // Bind click to open assignment modal dynamically
                        btnEl.onclick = function() {
                            openBookingFromExpress(slot.doctor_id, slot.date, slot.time, slot.slot_duration);
                        };
                    }
                });

                // Loop over all doctors, if they have no slots block it
                allDocIds.forEach(id => {
                    if (!foundDocs.has(String(id))) {
                        const textEl = document.getElementById(`nearestSlotText_${id}`);
                        if (textEl) textEl.innerHTML = `<i class="bi bi-x-circle text-danger me-1"></i> Totalmente ocupado/a o inactivo`;
                    }
                });
            });
    }

    function openBookingFromExpress(doctorId, date, time, duration) {
        document.getElementById('formDoctorSelect').value = doctorId;
        document.getElementById('formDateSelect').value = date;
        document.getElementById('formTimeSelect').value = time;
        document.getElementById('formDurationSelect').value = duration;
        
        const modal = new bootstrap.Modal(document.getElementById('newAppointmentModal'));
        modal.show();
    }

    // Interceptar form de guardado de turnos
    document.getElementById('newAppointmentForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const btn = document.getElementById('submitAppointmentBtn');
        const originalText = btn.innerHTML;
        
        btn.setAttribute('disabled', 'true');
        btn.innerHTML = '<i class="spinner-border spinner-border-sm me-1"></i> Guardando...';

        const formData = new FormData(this);
        const data = Object.fromEntries(formData.entries());

        axios.post(this.action, data)
            .then(res => {
                const modalEl = document.getElementById('newAppointmentModal');
                const modal = bootstrap.Modal.getInstance(modalEl);
                if(modal) modal.hide();

                const doctorId = data.doctor_id;
                const dateStr = data.date;

                // 1. Refrescar el mini-calendario (por si el día ahora está 'full' / rojo)
                renderCalendar(doctorId);

                // 2. Refrescar la columna derecha de horarios del doctor y fecha seleccionada
                loadDaySlots(doctorId, dateStr);

                // 3. Volver a buscar próximos libres disponibles a nivel general
                if (typeof loadNearestSlots === 'function') {
                    loadNearestSlots();
                }

                // Limpiar el estado del botón y modal para futuros usos
                clearModalPatientSelection();
                btn.removeAttribute('disabled');
                btn.innerHTML = originalText;
            })
            .catch(err => {
                alert('Ocurrió un error al intentar guardar el turno. Verifique los datos.');
                btn.removeAttribute('disabled');
                btn.innerHTML = originalText;
            });
    });

    function cancelAppointment(id, doctorId, dateStr) {
        if(!confirm('¿Estás seguro de que deseas eliminar este turno y liberar el horario?')) return;
        
        axios.delete(`/api/appointments/${id}`, {
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') }
        })
        .then(res => {
            renderCalendar(doctorId);
            loadDaySlots(doctorId, dateStr);
            if (typeof loadNearestSlots === 'function') loadNearestSlots();
        })
        .catch(err => alert('Ocurrió un error al intentar eliminar el turno. Verifique su conexión.'));
    }

    function markAsNoShow(id, doctorId, dateStr) {
        if(!confirm('¿Confirmar que el paciente NO se presentó a su turno (Ausencia)?')) return;
        
        axios.patch(`/api/appointments/${id}/no-show`, {}, {
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') }
        })
        .then(res => {
            renderCalendar(doctorId);
            loadDaySlots(doctorId, dateStr);
            spawnToast('Turno Actualizado', 'El paciente fue marcado como ausente.', 'exclamation-triangle-fill', 'warning text-dark');
        })
        .catch(err => alert('Ocurrió un error al registrar la ausencia.'));
    }

    // Lógica para alternar inputs de fecha y hora en el modal de bloqueo
    const blockTypeSelect = document.getElementById('blockTypeSelect');
    const timeFields = document.querySelectorAll('.time-fields');
    const blockStartDateCol = document.getElementById('blockStartDateCol');
    const blockStartDateLabel = document.getElementById('blockStartDateLabel');
    const blockEndDateCol = document.getElementById('blockEndDateCol');
    const blockStartTime = document.getElementById('blockStartTime');
    const blockEndTime = document.getElementById('blockEndTime');
    const blockEndDate = document.getElementById('blockEndDate');
    const blockReasonInput = document.getElementById('blockReasonInput');

    if (blockTypeSelect) {
        blockTypeSelect.addEventListener('change', function() {
            const val = this.value;

            if (val === 'vacation') {
                blockEndDateCol.classList.remove('d-none');
                blockEndDate.setAttribute('required', 'true');
                blockStartDateLabel.innerText = 'Fecha Desde *';
                timeFields.forEach(el => el.classList.add('d-none'));
                blockStartTime.removeAttribute('required');
                blockEndTime.removeAttribute('required');
                blockReasonInput.placeholder = 'Ej: Vacaciones de Verano / Invierno';
            } else if (val === 'day') {
                blockEndDateCol.classList.add('d-none');
                blockEndDate.removeAttribute('required');
                blockStartDateLabel.innerText = 'Fecha *';
                timeFields.forEach(el => el.classList.add('d-none'));
                blockStartTime.removeAttribute('required');
                blockEndTime.removeAttribute('required');
                blockReasonInput.placeholder = 'Ej: Anulación de día, Congreso, Licencia médica';
            } else if (val === 'range') {
                blockEndDateCol.classList.remove('d-none');
                blockEndDate.removeAttribute('required');
                blockStartDateLabel.innerText = 'Fecha Desde *';
                timeFields.forEach(el => el.classList.remove('d-none'));
                blockStartTime.setAttribute('required', 'true');
                blockEndTime.setAttribute('required', 'true');
                blockReasonInput.placeholder = 'Ej: Mañana bloqueada por cirugías';
            } else { // slot
                blockEndDateCol.classList.add('d-none');
                blockEndDate.removeAttribute('required');
                blockStartDateLabel.innerText = 'Fecha *';
                timeFields.forEach(el => el.classList.remove('d-none'));
                blockStartTime.setAttribute('required', 'true');
                blockEndTime.setAttribute('required', 'true');
                blockReasonInput.placeholder = 'Ej: Turno bloqueado por trámite';
            }
        });
    }

    function openDirectBlockModal(doctorId, doctorName) {
        document.getElementById('blockDoctorId').value = doctorId;
        document.getElementById('blockDoctorNameLabel').innerText = `Médico: ${doctorName}`;
        
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('blockDate').value = today;
        document.getElementById('blockEndDate').value = today;
        document.getElementById('blockTypeSelect').value = 'vacation';
        document.getElementById('blockTypeSelect').dispatchEvent(new Event('change'));
        
        const modal = new bootstrap.Modal(document.getElementById('blockAgendaModal'));
        modal.show();
    }

    function quickBlockSlot(doctorId, date, time, duration) {
        document.getElementById('blockDoctorId').value = doctorId;
        document.getElementById('blockDate').value = date;
        document.getElementById('blockTypeSelect').value = 'slot';
        
        const [hours, minutes] = time.split(':');
        const start = new Date();
        start.setHours(parseInt(hours), parseInt(minutes), 0, 0);
        const end = new Date(start.getTime() + duration * 60000);
        
        const startTimeStr = time;
        const endTimeStr = `${String(end.getHours()).padStart(2, '0')}:${String(end.getMinutes()).padStart(2, '0')}`;
        
        document.getElementById('blockStartTime').value = startTimeStr;
        document.getElementById('blockEndTime').value = endTimeStr;
        
        blockTypeSelect.dispatchEvent(new Event('change'));
        
        const modal = new bootstrap.Modal(document.getElementById('blockAgendaModal'));
        modal.show();
    }

    function openBlockModal(doctorId) {
        const dateStr = doctorStates[doctorId].selectedDateStr;
        if (!dateStr) return;
        
        document.getElementById('blockDoctorId').value = doctorId;
        document.getElementById('blockDate').value = dateStr;
        document.getElementById('blockEndDate').value = dateStr;
        document.getElementById('blockTypeSelect').value = 'day';
        document.getElementById('blockStartTime').value = '';
        document.getElementById('blockEndTime').value = '';
        
        blockTypeSelect.dispatchEvent(new Event('change'));
        
        const modal = new bootstrap.Modal(document.getElementById('blockAgendaModal'));
        modal.show();
    }

    // Interceptar form de guardado de bloqueos
    const blockAgendaForm = document.getElementById('blockAgendaForm');
    if (blockAgendaForm) {
        blockAgendaForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = document.getElementById('submitBlockBtn');
            const originalText = btn.innerHTML;
            
            btn.setAttribute('disabled', 'true');
            btn.innerHTML = '<i class="spinner-border spinner-border-sm me-1"></i> Bloqueando...';

            const formData = new FormData(this);
            const data = Object.fromEntries(formData.entries());

            axios.post(this.action, data)
                .then(res => {
                    const modalEl = document.getElementById('blockAgendaModal');
                    const modal = bootstrap.Modal.getInstance(modalEl);
                    if (modal) modal.hide();

                    const doctorId = data.doctor_id;
                    const dateStr = data.date;

                    // Refresh
                    renderCalendar(doctorId);
                    loadDaySlots(doctorId, dateStr);
                    if (typeof loadNearestSlots === 'function') loadNearestSlots();

                    btn.removeAttribute('disabled');
                    btn.innerHTML = originalText;
                    this.reset();
                })
                .catch(err => {
                    alert(err.response?.data?.message || 'Ocurrió un error al intentar bloquear el horario.');
                    btn.removeAttribute('disabled');
                    btn.innerHTML = originalText;
                });
        });
    }

    function csrfHeader() {
        return { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') };
    }

    function refreshDoctorAgendaUI(doctorId, dateStr) {
        renderCalendar(doctorId);
        if (dateStr) loadDaySlots(doctorId, dateStr);
        if (typeof loadNearestSlots === 'function') loadNearestSlots();
    }

    function unblockSingleSlot(doctorId, dateStr, timeStr, duration) {
        axios.post(`{{ route('api.agenda.blocks.unblock_range') }}`, {
            doctor_id: doctorId,
            date: dateStr,
            mode: 'slot',
            start_time: timeStr,
            duration: duration || 20
        }, { headers: csrfHeader() })
        .then(() => {
            if (typeof spawnToast === 'function') {
                spawnToast('Horario Habilitado', `Se desbloqueó el horario ${timeStr} hs.`, 'unlock-fill', 'success text-white');
            }
            refreshDoctorAgendaUI(doctorId, dateStr);
        })
        .catch(err => alert(err.response?.data?.message || 'Error al desbloquear el horario.'));
    }

    function unblockAndBookSlot(doctorId, dateStr, timeStr, duration) {
        axios.post(`{{ route('api.agenda.blocks.unblock_range') }}`, {
            doctor_id: doctorId,
            date: dateStr,
            mode: 'slot',
            start_time: timeStr,
            duration: duration || 20
        }, { headers: csrfHeader() })
        .then(() => {
            refreshDoctorAgendaUI(doctorId, dateStr);
            openBookingFromExpress(doctorId, dateStr, timeStr, duration || 20);
        })
        .catch(err => alert(err.response?.data?.message || 'Error al liberar el horario.'));
    }

    function unblockDayOnly(doctorId, dateStr) {
        if (!confirm('¿Desbloquear todos los horarios habituales de ESTE DÍA? (Los demás días bloqueados del período se mantendrán intactos).')) return;
        axios.post(`{{ route('api.agenda.blocks.unblock_range') }}`, {
            doctor_id: doctorId,
            date: dateStr,
            mode: 'day'
        }, { headers: csrfHeader() })
        .then(() => {
            if (typeof spawnToast === 'function') {
                spawnToast('Día Habilitado', 'Se habilitaron todos los horarios de este día.', 'check-circle-fill', 'success text-white');
            }
            refreshDoctorAgendaUI(doctorId, dateStr);
        })
        .catch(err => alert(err.response?.data?.message || 'Error al desbloquear el día.'));
    }

    function unblockEntireBlock(blockId, doctorId, dateStr) {
        if (!confirm('¿Anular este bloqueo completo y rehabilitar todos los días y horarios habituales del médico?')) return;
        axios.delete(`/api/agenda/blocks/${blockId}`, { headers: csrfHeader() })
        .then(() => {
            if (typeof spawnToast === 'function') {
                spawnToast('Bloqueo Anulado', 'Toda la agenda habitual fue rehabilitada.', 'unlock-fill', 'success text-white');
            }
            refreshDoctorAgendaUI(doctorId, dateStr || doctorStates[doctorId]?.selectedDateStr);
        })
        .catch(() => alert('Ocurrió un error al intentar anular el bloqueo.'));
    }

    function unblockSlot(blockId, doctorId, dateStr) {
        unblockEntireBlock(blockId, doctorId, dateStr);
    }

    function openSpecialOpeningModal(doctorId, dateStr, actionMode = 'special_open', defaultStart = '10:00') {
        const targetDate = dateStr || doctorStates[doctorId]?.selectedDateStr;
        if (!targetDate) {
            alert('Seleccione primero un día en el calendario.');
            return;
        }
        document.getElementById('specialDoctorId').value = doctorId;
        document.getElementById('specialDate').value = targetDate;
        document.getElementById('specialActionMode').value = actionMode;
        document.getElementById('specialStartTime').value = defaultStart;

        const [sh, sm] = defaultStart.split(':').map(n => parseInt(n, 10));
        const endH = Math.min(23, (isNaN(sh) ? 10 : sh) + 2);
        document.getElementById('specialEndTime').value = `${String(endH).padStart(2, '0')}:${String(isNaN(sm) ? 0 : sm).padStart(2, '0')}`;

        const [y, m, d] = targetDate.split('-');
        document.getElementById('specialModalSubLabel').innerText = `Fecha: ${d}/${m}/${y}`;
        document.getElementById('specialModalTitle').innerHTML = actionMode === 'unblock_range'
            ? '<i class="bi bi-unlock-fill text-success me-2"></i>Desbloquear Franja Horaria (ej: 2 hs)'
            : '<i class="bi bi-clock-history text-primary me-2"></i>Habilitar Horario Especial en Día Libre';

        bootstrap.Modal.getOrCreateInstance(document.getElementById('specialOpeningModal')).show();
    }

    function submitSpecialOpening(e) {
        e.preventDefault();
        const doctorId = parseInt(document.getElementById('specialDoctorId').value, 10);
        const dateStr = document.getElementById('specialDate').value;
        const mode = document.getElementById('specialActionMode').value;
        const startTime = document.getElementById('specialStartTime').value;
        const endTime = document.getElementById('specialEndTime').value;
        const btn = document.getElementById('btnSubmitSpecialOpening');

        btn.disabled = true;
        const req = (mode === 'unblock_range')
            ? axios.post(`{{ route('api.agenda.blocks.unblock_range') }}`, {
                doctor_id: doctorId,
                date: dateStr,
                mode: 'time_range',
                start_time: startTime,
                end_time: endTime
              }, { headers: csrfHeader() })
            : axios.post(`{{ route('api.agenda.special_opening.store') }}`, {
                doctor_id: doctorId,
                date: dateStr,
                start_time: startTime,
                end_time: endTime
              }, { headers: csrfHeader() });

        req.then(() => {
            btn.disabled = false;
            const modal = bootstrap.Modal.getInstance(document.getElementById('specialOpeningModal'));
            if (modal) modal.hide();
            if (typeof spawnToast === 'function') {
                spawnToast('Franja Habilitada', `Horarios de ${startTime} a ${endTime} hs habilitados.`, 'check-circle-fill', 'success text-white');
            }
            refreshDoctorAgendaUI(doctorId, dateStr);
        }).catch(err => {
            btn.disabled = false;
            alert(err.response?.data?.message || 'Verifique que la hora fin sea posterior a la de inicio.');
        });
    }

    function removeSpecialOpening(doctorId, dateStr) {
        if (!confirm('¿Quitar el horario especial habilitado en esta fecha?')) return;
        axios.delete(`{{ route('api.agenda.special_opening.destroy') }}`, {
            headers: csrfHeader(),
            data: { doctor_id: doctorId, date: dateStr }
        }).then(() => {
            refreshDoctorAgendaUI(doctorId, dateStr);
        }).catch(() => alert('Error al quitar horario especial.'));
    }

    // Init Call
    document.addEventListener('DOMContentLoaded', () => {
        // Init all calendars
        Object.keys(doctorStates).forEach(doctorId => renderCalendar(doctorId));
        
        // Init nearest Express Slots (Phase 17)
        loadNearestSlots(); 

        // Phase 17: Patient Habits Hook
        const patientSelect = document.querySelector('select[name="patient_id"]');
        const reasonInput = document.querySelector('input[name="reason"]');
        
        if (patientSelect) {
            patientSelect.addEventListener('change', function() {
                const patId = this.value;
                if (!patId) return;
                
                reasonInput.placeholder = "Analizando historial...";
                axios.get(`/api/patients/${patId}/habits`)
                    .then(res => {
                        reasonInput.placeholder = "Ej: Control";
                        if (res.data.message) {
                            reasonInput.value = res.data.message + (reasonInput.value ? " | " + reasonInput.value : "");
                        }
                    });
            });
        }

        // Initialize preselected patient if present
        clearModalPatientSelection();
    });
</script>

<!-- Modal: Dar Entrada a Sala de Espera desde Agenda -->
<div class="modal fade" id="darEntradaModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 rounded-4 shadow">
        <div class="modal-header border-bottom-0 pb-0">
            <div>
                <h5 class="modal-title fw-bold text-dark"><i class="bi bi-box-arrow-in-right text-success me-2"></i>Dar Entrada al Turno</h5>
                <small class="text-muted fw-bold" id="darEntradaPatientLabel">Paciente: --</small>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form action="{{ route('console.assignments.store') }}" method="POST">
            @csrf
            <input type="hidden" name="patient_id" id="darEntradaPatientId">
            <input type="hidden" name="doctor_ids[]" id="darEntradaDoctorId">
            <input type="hidden" name="event_types[]" value="Ingreso (espera)">
            
            <div class="modal-body p-4">
                <div class="alert alert-info border-0 rounded-3 small p-3 mb-3">
                    <i class="bi bi-info-circle me-1"></i> El paciente ingresará a la <strong>Sala de Espera</strong> e iniciará el conteo de tiempos de llegada y espera.
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold"><i class="bi bi-sticky-fill text-warning me-1"></i> Observaciones Internas / Comentario del Momento</label>
                    <textarea name="notes" id="darEntradaNotes" rows="3" class="form-control bg-light border-0 shadow-none rounded-3" placeholder="Ej: Trae estudios, control post-quirúrgico, urgencia..."></textarea>
                    <small class="text-muted" style="font-size:0.75rem;">Esta leyenda aparecerá destacada en Sala de Espera para el profesional.</small>
                </div>
            </div>
            <div class="modal-footer border-top-0 pt-0 pe-4">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-success rounded-pill px-4 fw-bold shadow-sm">
                    <i class="bi bi-check2-circle me-1"></i> Asentar Llegada y Dar Entrada
                </button>
            </div>
        </form>
    </div>
  </div>
</div>

<script>
    function openDarEntradaModal(patientId, patientName, doctorId, initialNotes) {
        document.getElementById('darEntradaPatientId').value = patientId;
        document.getElementById('darEntradaDoctorId').value = doctorId || '';
        document.getElementById('darEntradaPatientLabel').innerText = `Paciente: ${patientName}`;
        document.getElementById('darEntradaNotes').value = initialNotes || '';
        const modal = new bootstrap.Modal(document.getElementById('darEntradaModal'));
        modal.show();
    }

    function setAppointmentReasonPreset(text) {
        const input = document.getElementById('appointmentReasonInput');
        if (input) {
            input.value = text;
            input.focus();
        }
    }

    function toggleRescheduleOverbookMode() {
        const isOver = document.getElementById('rescheduleIsOverbooked')?.checked;
        const badge = document.getElementById('rescheduleOverbookBadge');
        if (badge) {
            if (isOver) {
                badge.className = 'badge bg-warning text-dark fw-bold';
                badge.innerHTML = '<i class="bi bi-lightning-charge-fill me-1"></i>Se agendará como SOBRETURNO';
            } else {
                badge.className = 'badge bg-light text-secondary border';
                badge.innerText = 'Modo Turno Regular';
            }
        }
    }

    function pickRescheduleSlot(timeStr, asOverbook) {
        document.getElementById('rescheduleNewTime').value = timeStr;
        const overChk = document.getElementById('rescheduleIsOverbooked');
        if (overChk) {
            overChk.checked = Boolean(asOverbook);
            toggleRescheduleOverbookMode();
        }
        document.querySelectorAll('#rescheduleFreeSlotsContainer .resched-slot-btn').forEach(b => {
            const active = (!asOverbook && b.getAttribute('data-time') === timeStr);
            b.className = active
                ? 'btn btn-sm btn-success rounded-pill py-1 px-3 fw-bold shadow-sm resched-slot-btn'
                : 'btn btn-sm btn-outline-success rounded-pill py-1 px-3 fw-bold resched-slot-btn';
        });
        document.querySelectorAll('#rescheduleOverbookSlotsContainer .resched-over-btn').forEach(b => {
            const active = (asOverbook && b.getAttribute('data-time') === timeStr);
            b.className = active
                ? 'btn btn-sm btn-warning text-dark rounded-pill py-1 px-3 fw-bold shadow-sm resched-over-btn'
                : 'btn btn-sm btn-outline-warning text-dark bg-white rounded-pill py-1 px-3 fw-semibold resched-over-btn';
        });
    }

    function openRescheduleModal(appointmentId, patientName, currentDate, currentTime, doctorId) {
        document.getElementById('rescheduleAppointmentId').value = appointmentId;
        document.getElementById('rescheduleOriginalDate').value = currentDate;
        document.getElementById('rescheduleOriginalDoctorId').value = doctorId;
        document.getElementById('rescheduleDoctorId').value = doctorId;
        document.getElementById('reschedulePatientLabel').innerText = `Paciente: ${patientName}`;

        const [cy, cm, cd] = (currentDate || '').split('-');
        const niceDate = (cy && cm && cd) ? `${cd}/${cm}/${cy}` : currentDate;
        document.getElementById('rescheduleCurrentDetails').innerText = `${niceDate} a las ${currentTime} hs`;
        document.getElementById('rescheduleNewDate').value = currentDate;
        document.getElementById('rescheduleNewTime').value = currentTime;
        document.getElementById('rescheduleReason').value = '';
        const overChk = document.getElementById('rescheduleIsOverbooked');
        if (overChk) overChk.checked = false;
        toggleRescheduleOverbookMode();
        
        loadRescheduleFreeSlots();

        const modalEl = document.getElementById('rescheduleAppointmentModal');
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
    }

    function loadRescheduleFreeSlots() {
        const docId = document.getElementById('rescheduleDoctorId').value;
        const dateVal = document.getElementById('rescheduleNewDate').value;
        const box = document.getElementById('rescheduleFreeSlotsContainer');
        const overBox = document.getElementById('rescheduleOverbookSlotsContainer');
        const sumLabel = document.getElementById('rescheduleSlotsSummaryLabel');
        if (!box || !docId || !dateVal) return;

        box.innerHTML = '<span class="text-muted small"><span class="spinner-border spinner-border-sm me-1"></span> Cargando horarios disponibles del día...</span>';
        if (overBox) overBox.innerHTML = '<span class="text-muted small">Consultando...</span>';

        axios.get(`{{ route('api.agenda.slots') }}?doctor_id=${docId}&date=${dateVal}`)
            .then(res => {
                const slots = (res.data && res.data.slots) ? res.data.slots : [];
                const freeSlots = slots.filter(s => s.status === 'free' && !s.is_past);

                // Agrupar todos los horarios no bloqueados para opción de Sobreturno
                const overMap = new Map();
                slots.forEach(s => {
                    if (s.status === 'blocked') return;
                    if (!overMap.has(s.time)) {
                        overMap.set(s.time, { time: s.time, count: s.status === 'booked' ? 1 : 0 });
                    } else if (s.status === 'booked') {
                        overMap.get(s.time).count++;
                    }
                });
                const overSlots = Array.from(overMap.values());

                if (sumLabel) {
                    sumLabel.innerText = `${freeSlots.length} libre(s) · ${overSlots.length} horario(s) p/ sobreturno`;
                }

                if (freeSlots.length === 0) {
                    box.innerHTML = '<span class="text-muted small"><i class="bi bi-exclamation-circle me-1"></i>No quedan turnos libres regulares en esta fecha. Use un horario de <strong>Sobreturno</strong> abajo o escriba la hora manualmente arriba.</span>';
                } else {
                    // Auto-seleccionar el primer horario libre si la fecha cambió respecto de la original
                    const origDate = document.getElementById('rescheduleOriginalDate').value;
                    if (dateVal !== origDate && freeSlots.length > 0) {
                        document.getElementById('rescheduleNewTime').value = freeSlots[0].time;
                    }
                    const currentChosen = document.getElementById('rescheduleNewTime').value;
                    box.innerHTML = freeSlots.map(s => {
                        const isSel = (s.time === currentChosen);
                        return `
                        <button type="button" data-time="${s.time}"
                                class="btn btn-sm ${isSel ? 'btn-success shadow-sm' : 'btn-outline-success'} rounded-pill py-1 px-3 fw-bold resched-slot-btn"
                                style="font-size: 0.8rem;"
                                onclick="pickRescheduleSlot('${s.time}', false)">
                            <i class="bi bi-clock me-1"></i>${s.time}
                        </button>`;
                    }).join('');
                }

                if (overBox) {
                    if (overSlots.length === 0) {
                        overBox.innerHTML = '<span class="text-muted small">Día sin grilla regular o bloqueado: tilde "Dar como Sobreturno" arriba y elija cualquier horario manualmente.</span>';
                    } else {
                        overBox.innerHTML = overSlots.map(os => `
                            <button type="button" data-time="${os.time}"
                                    class="btn btn-sm btn-outline-warning text-dark bg-white rounded-pill py-1 px-3 fw-semibold resched-over-btn"
                                    style="font-size: 0.78rem;"
                                    onclick="pickRescheduleSlot('${os.time}', true)"
                                    title="${os.count > 0 ? os.count + ' turno(s) ya asignado(s) en este horario' : 'Horario libre (forzar como sobreturno)'}">
                                <i class="bi bi-lightning-charge-fill text-warning me-1"></i>${os.time} ${os.count > 0 ? '<span class="badge bg-warning bg-opacity-25 text-dark ms-1">' + os.count + ' ocup.</span>' : '<span class="text-muted small ms-1">(libre)</span>'}
                            </button>
                        `).join('');
                    }
                }
            })
            .catch(() => {
                box.innerHTML = '<span class="text-muted small">Puede ingresar cualquier horario manualmente arriba.</span>';
            });
    }

    function submitRescheduleAppointment(e) {
        e.preventDefault();
        const apptId = document.getElementById('rescheduleAppointmentId').value;
        const origDoctorId = parseInt(document.getElementById('rescheduleOriginalDoctorId').value, 10);
        const origDate = document.getElementById('rescheduleOriginalDate').value;
        const doctorId = parseInt(document.getElementById('rescheduleDoctorId').value, 10);
        const newDate = document.getElementById('rescheduleNewDate').value;
        const newTime = document.getElementById('rescheduleNewTime').value;
        const reason = document.getElementById('rescheduleReason').value;
        const isOverbooked = Boolean(document.getElementById('rescheduleIsOverbooked')?.checked);
        const sendWhatsapp = Boolean(document.getElementById('rescheduleSendWhatsapp')?.checked);
        const btn = document.getElementById('btnSubmitReschedule');
        
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Reprogramando...';
        
        axios.post(`{{ url('api/appointments') }}/${apptId}/reschedule`, {
            date: newDate,
            time: newTime,
            doctor_id: doctorId,
            reason: reason,
            is_overbooked: isOverbooked,
            send_whatsapp: sendWhatsapp
        }, {
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') }
        })
        .then(res => {
            const modalEl = document.getElementById('rescheduleAppointmentModal');
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) modal.hide();
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check2-circle me-1"></i> Confirmar Reprogramación';
            
            if (typeof spawnToast === 'function') {
                spawnToast('Turno Reprogramado', 'El turno fue trasladado correctamente.', 'check-circle-fill', 'success text-white');
            } else {
                alert('Turno reprogramado exitosamente.');
            }
            
            // Refrescar mini-calendario y grilla horaria sin riesgo de errores JS
            try {
                if (origDoctorId && origDoctorId !== doctorId && doctorStates[origDoctorId]) {
                    renderCalendar(origDoctorId);
                    if (origDate) loadDaySlots(origDoctorId, origDate);
                }
                if (doctorStates[doctorId]) {
                    const [ny, nm, nd] = newDate.split('-');
                    if (ny && nm && nd) {
                        doctorStates[doctorId].currentDate = new Date(parseInt(ny, 10), parseInt(nm, 10) - 1, parseInt(nd, 10));
                    }
                    renderCalendar(doctorId);
                    loadDaySlots(doctorId, newDate);
                }
                if (typeof loadNearestSlots === 'function') {
                    loadNearestSlots();
                }
            } catch (uiErr) {
                console.warn('Refresh UI post-reschedule:', uiErr);
            }
        })
        .catch(err => {
            const msg = err.response?.data?.message
                || (err.response?.data?.errors ? Object.values(err.response.data.errors).flat().join(' ') : null)
                || 'Error al reprogramar el turno. Verifique la fecha y el horario.';
            alert(msg);
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check2-circle me-1"></i> Confirmar Reprogramación';
        });
    }

    function sendAppointmentWhatsappNow(appointmentId) {
        axios.post(`{{ url('api/appointments') }}/${appointmentId}/send-whatsapp`, {}, {
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') }
        })
        .then(res => {
            if (typeof spawnToast === 'function') {
                spawnToast('WhatsApp Encolado', res.data.message || 'Recordatorio enviado.', 'whatsapp', 'success text-white');
            } else {
                alert(res.data.message || 'Recordatorio enviado.');
            }
        })
        .catch(err => {
            alert(err.response?.data?.message || 'No se pudo enviar el recordatorio por WhatsApp.');
        });
    }
</script>
@endsection
