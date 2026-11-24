@extends('layouts.admin')

@section('title', 'Configurar Horarios de Agendas (Médicas y Técnicas)')
@section('subtitle', 'Define días y horarios independientes para cada Médico/a y Técnico/a (si una persona cumple dos funciones, cada función tiene su propia agenda)')

@section('content')

@if(session('success'))
    <div class="alert alert-success border-0 bg-success bg-opacity-10 text-success rounded-4 mb-4" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
    </div>
@endif

<div class="row g-4 mb-4">
    <!-- PANEL PRINCIPAL: PATRÓN SEMANAL PERSONALIZADO POR DÍA Y FUNCIÓN -->
    <div class="col-12">
        <div class="modern-card p-4 shadow-sm border-0 rounded-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 pb-3 border-bottom gap-2">
                <div>
                    <h5 class="fw-bold text-dark mb-1"><i class="bi bi-calendar-week text-primary me-2"></i> 1. Horarios de Agenda por Profesional / Técnico y Función</h5>
                    <p class="text-muted small mb-0">
                        Seleccione la agenda a configurar (<strong>Agenda Médica</strong> o <strong>Agenda Técnica / Estudios</strong>). Si un integrante tiene ambos roles (ej: Recepcionista + Técnica, o Médica + Técnica), cada función maneja su propia agenda independiente.
                    </p>
                </div>
                <a href="{{ route('users.index') }}" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold">
                    <i class="bi bi-people me-1"></i> Asignar Rol Técnico/Médico en Usuarios
                </a>
            </div>

            <form action="{{ route('agenda.config.store') }}" method="POST" id="weeklyScheduleForm">
                @csrf
                <input type="hidden" name="doctor_id" id="configDoctorIdHidden">
                <input type="hidden" name="agenda_type" id="configAgendaTypeHidden" value="medica">

                <div class="row g-3 mb-4 align-items-end bg-light p-3 rounded-4 border">
                    <div class="col-lg-4 col-md-6">
                        <label class="form-label text-muted small fw-bold">Profesional y Tipo de Agenda *</label>
                        <select name="agenda_target" id="configDoctorSelect" class="form-control bg-white border shadow-none form-select fw-semibold" required>
                            <option value="">-- Seleccionar Médico o Técnico/a --</option>
                            @php
                                $medUnits = collect($agendaUnits ?? [])->where('agenda_type', 'medica');
                                $tecUnits = collect($agendaUnits ?? [])->where('agenda_type', 'tecnica');
                            @endphp
                            @if($medUnits->count() > 0)
                                <optgroup label="🩺 Agendas Médicas (Consultorio)">
                                    @foreach($medUnits as $u)
                                        <option value="{{ $u['target_key'] }}" data-label="{{ $u['agenda_label'] }}">{{ $u['short_option'] }}</option>
                                    @endforeach
                                </optgroup>
                            @endif
                            @if($tecUnits->count() > 0)
                                <optgroup label="🔬 Agendas Técnicas / Estudios (Campos Visuales, OCT, etc.)">
                                    @foreach($tecUnits as $u)
                                        <option value="{{ $u['target_key'] }}" data-label="{{ $u['agenda_label'] }}">{{ $u['short_option'] }}</option>
                                    @endforeach
                                </optgroup>
                            @endif
                        </select>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <label class="form-label text-muted small fw-bold">Especialidad / Estudio (Etiqueta)</label>
                        <input type="text" name="agenda_label" id="configAgendaLabelInput" class="form-control bg-white border shadow-none" placeholder="Ej: Campos Visuales, OCT, Consultorio...">
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <label class="form-label text-muted small fw-bold">Duración de Cada Turno *</label>
                        <select name="appointment_duration" id="appointmentDurationSelect" class="form-control bg-white border shadow-none form-select" required>
                            <option value="10">Cada 10 minutos</option>
                            <option value="15">Cada 15 minutos</option>
                            <option value="20" selected>Cada 20 minutos (Estándar)</option>
                            <option value="30">Cada 30 minutos</option>
                            <option value="45">Cada 45 minutos</option>
                            <option value="60">Cada 60 minutos (Estudios largos)</option>
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-6 text-md-end">
                        <button type="submit" class="btn btn-primary rounded-pill px-3 py-2 fw-bold shadow-sm w-100" style="background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%); border:none;">
                            <i class="bi bi-save me-1"></i> Guardar
                        </button>
                    </div>
                </div>

                <!-- TABLA / CARDS DE CONFIGURACIÓN DIARIA -->
                @php
                    $daysList = [
                        1 => ['name' => 'Lunes', 'short' => 'Lun', 'icon' => 'calendar-date'],
                        2 => ['name' => 'Martes', 'short' => 'Mar', 'icon' => 'calendar-date'],
                        3 => ['name' => 'Miércoles', 'short' => 'Mié', 'icon' => 'calendar-date'],
                        4 => ['name' => 'Jueves', 'short' => 'Jue', 'icon' => 'calendar-date'],
                        5 => ['name' => 'Viernes', 'short' => 'Vie', 'icon' => 'calendar-date'],
                        6 => ['name' => 'Sábado', 'short' => 'Sáb', 'icon' => 'calendar-date'],
                    ];
                @endphp

                <div class="d-flex flex-column gap-3 mb-4">
                    @foreach($daysList as $dayNum => $dayMeta)
                    <div class="card border rounded-4 shadow-sm p-3 day-schedule-card" id="cardDay_{{ $dayNum }}">
                        <div class="row align-items-center g-3">
                            <!-- Toggle del Día -->
                            <div class="col-lg-2 col-md-3 col-12 border-end pe-3">
                                <div class="form-check form-switch d-flex align-items-center gap-2">
                                    <input class="form-check-input fs-5 day-active-checkbox" type="checkbox" name="days[{{ $dayNum }}][active]" value="1" id="dayActive_{{ $dayNum }}" checked onchange="toggleDayRow({{ $dayNum }})">
                                    <label class="form-check-label fw-bold text-dark fs-6" for="dayActive_{{ $dayNum }}">
                                        {{ $dayMeta['name'] }}
                                    </label>
                                </div>
                                <small class="text-muted d-block ms-4" id="dayStatusBadge_{{ $dayNum }}">Atiende</small>
                            </div>

                            <!-- Turno Mañana -->
                            <div class="col-lg-4 col-md-4 col-12 border-end pe-3">
                                <div class="p-2 rounded-3 bg-light border h-100 shift-box" id="morningBox_{{ $dayNum }}">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <div class="form-check form-switch mb-0">
                                            <input class="form-check-input shift-toggle" type="checkbox" name="days[{{ $dayNum }}][morning_active]" value="1" id="morningActive_{{ $dayNum }}" checked onchange="toggleShiftInputs({{ $dayNum }}, 'morning')">
                                            <label class="form-check-label fw-bold text-dark small" for="morningActive_{{ $dayNum }}">
                                                <i class="bi bi-sunrise text-warning me-1"></i> Mañana
                                            </label>
                                        </div>
                                        <span class="badge bg-white text-secondary border small shift-state-badge" id="morningBadge_{{ $dayNum }}">Activo</span>
                                    </div>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <label class="text-muted" style="font-size: 0.72rem;">Desde</label>
                                            <input type="time" name="days[{{ $dayNum }}][morning_start]" id="morningStart_{{ $dayNum }}" value="08:00" class="form-control form-control-sm border shadow-none bg-white">
                                        </div>
                                        <div class="col-6">
                                            <label class="text-muted" style="font-size: 0.72rem;">Hasta</label>
                                            <input type="time" name="days[{{ $dayNum }}][morning_end]" id="morningEnd_{{ $dayNum }}" value="12:00" class="form-control form-control-sm border shadow-none bg-white">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Turno Tarde -->
                            <div class="col-lg-4 col-md-4 col-12 border-end pe-3">
                                <div class="p-2 rounded-3 bg-light border h-100 shift-box" id="afternoonBox_{{ $dayNum }}">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <div class="form-check form-switch mb-0">
                                            <input class="form-check-input shift-toggle" type="checkbox" name="days[{{ $dayNum }}][afternoon_active]" value="1" id="afternoonActive_{{ $dayNum }}" checked onchange="toggleShiftInputs({{ $dayNum }}, 'afternoon')">
                                            <label class="form-check-label fw-bold text-dark small" for="afternoonActive_{{ $dayNum }}">
                                                <i class="bi bi-sunset text-danger me-1"></i> Tarde
                                            </label>
                                        </div>
                                        <span class="badge bg-white text-secondary border small shift-state-badge" id="afternoonBadge_{{ $dayNum }}">Activo</span>
                                    </div>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <label class="text-muted" style="font-size: 0.72rem;">Desde</label>
                                            <input type="time" name="days[{{ $dayNum }}][afternoon_start]" id="afternoonStart_{{ $dayNum }}" value="16:00" class="form-control form-control-sm border shadow-none bg-white">
                                        </div>
                                        <div class="col-6">
                                            <label class="text-muted" style="font-size: 0.72rem;">Hasta</label>
                                            <input type="time" name="days[{{ $dayNum }}][afternoon_end]" id="afternoonEnd_{{ $dayNum }}" value="20:00" class="form-control form-control-sm border shadow-none bg-white">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Acciones rápidas -->
                            <div class="col-lg-2 col-md-1 col-12 text-center text-lg-end">
                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 shadow-none text-nowrap" onclick="copyDayToAll({{ $dayNum }})" title="Copiar este horario a los demás días">
                                    <i class="bi bi-copy me-1"></i> Copiar a todos
                                </button>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                    <button type="button" class="btn btn-outline-danger rounded-pill px-4 fw-bold shadow-sm d-none" id="btnDeleteConfig" onclick="deleteConfig()">
                        <i class="bi bi-trash me-2"></i> Eliminar Patrón de esta Agenda
                    </button>
                    <button type="submit" class="btn btn-primary rounded-pill px-5 py-2 fw-bold shadow-sm ms-auto" style="background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%); border:none;">
                        <i class="bi bi-save me-2"></i> Guardar Todos los Horarios
                    </button>
                </div>
            </form>

            <form id="deleteConfigForm" method="POST" action="">
                @csrf
                @method('DELETE')
                <input type="hidden" name="agenda_type" id="deleteConfigAgendaType" value="medica">
            </form>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- PANEL SECUNDARIO: BLOQUEOS DE EXCEPCION Y VACACIONES -->
    <div class="col-lg-5">
        <div class="modern-card p-4 h-100 shadow-sm border-0 rounded-4">
            <h5 class="fw-bold text-dark mb-2"><i class="bi bi-shield-lock text-primary me-2"></i> 2. Bloqueos de Turnos / Vacaciones</h5>
            <p class="text-muted small mb-4">Bloquee días completos, vacaciones o franjas horarias de una Agenda Médica o Técnica específica.</p>
            
            <form action="{{ route('agenda.blocks.store') }}" method="POST">
                @csrf
                <div class="mb-4">
                    <label class="form-label text-dark small fw-bold mb-2">Agenda Afectada (Médica o Técnica) *</label>
                    <select name="agenda_target" class="form-select border rounded-3 py-2.5 px-3 shadow-none bg-white fw-semibold" required>
                        <option value="">-- Seleccionar Agenda --</option>
                        @foreach($agendaUnits ?? [] as $u)
                            <option value="{{ $u['target_key'] }}">{{ $u['short_option'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-4">
                    <label class="form-label text-dark small fw-bold mb-2">Modalidad de Bloqueo *</label>
                    <div class="d-flex gap-2">
                        <input type="radio" class="btn-check" name="block_mode" id="settingsBlockAllDay" value="all_day" checked onchange="toggleSettingsBlockTimeRow()">
                        <label class="btn btn-outline-primary rounded-3 flex-fill py-2 fw-bold" for="settingsBlockAllDay">
                            <i class="bi bi-calendar-range me-1"></i> Día(s) Completo(s) / Vacaciones
                        </label>

                        <input type="radio" class="btn-check" name="block_mode" id="settingsBlockTimeRange" value="time_range" onchange="toggleSettingsBlockTimeRow()">
                        <label class="btn btn-outline-primary rounded-3 flex-fill py-2 fw-bold" for="settingsBlockTimeRange">
                            <i class="bi bi-clock me-1"></i> Franja Horaria
                        </label>
                    </div>
                </div>

                <div class="p-3 rounded-4 bg-light border mb-4">
                    <div class="mb-3">
                        <label class="form-label text-dark small fw-bold mb-1"><i class="bi bi-calendar-event text-primary me-1"></i> Fecha Desde *</label>
                        <input type="date" name="start_date" id="settingsBlockStartDate" value="{{ date('Y-m-d') }}" class="form-control form-control-lg bg-white border rounded-3 shadow-none fs-6" required onchange="if(!document.getElementById('settingsBlockEndDate').value || document.getElementById('settingsBlockEndDate').value < this.value) document.getElementById('settingsBlockEndDate').value = this.value;">
                    </div>
                    <div>
                        <label class="form-label text-dark small fw-bold mb-1"><i class="bi bi-calendar-check text-primary me-1"></i> Fecha Hasta (inclusive) *</label>
                        <input type="date" name="end_date" id="settingsBlockEndDate" value="{{ date('Y-m-d') }}" class="form-control form-control-lg bg-white border rounded-3 shadow-none fs-6" required>
                        <small class="text-muted d-block mt-1" style="font-size: 0.75rem;">Si es un solo día, deje la misma fecha en ambos campos.</small>
                    </div>
                </div>

                <div class="p-3 rounded-4 bg-warning bg-opacity-10 border border-warning border-opacity-50 mb-4 d-none" id="settingsBlockTimeRow">
                    <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-1">
                        <span class="small fw-bold text-dark"><i class="bi bi-alarm me-1"></i> Horario a bloquear:</span>
                        <div class="d-flex gap-1">
                            <button type="button" class="btn btn-sm btn-white bg-white border rounded-pill px-2.5 py-0.5 fw-semibold" style="font-size: 0.74rem;" onclick="document.getElementById('settingsBlockStartTime').value='08:00'; document.getElementById('settingsBlockEndTime').value='13:00';">Mañana (08-13)</button>
                            <button type="button" class="btn btn-sm btn-white bg-white border rounded-pill px-2.5 py-0.5 fw-semibold" style="font-size: 0.74rem;" onclick="document.getElementById('settingsBlockStartTime').value='14:00'; document.getElementById('settingsBlockEndTime').value='20:00';">Tarde (14-20)</button>
                        </div>
                    </div>
                    <div class="row g-3 mt-1">
                        <div class="col-sm-6">
                            <label class="form-label text-dark small fw-bold mb-1">Hora Inicio</label>
                            <input type="time" name="start_time" id="settingsBlockStartTime" value="08:00" class="form-control form-control-lg bg-white border rounded-3 shadow-none fs-6">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label text-dark small fw-bold mb-1">Hora Fin</label>
                            <input type="time" name="end_time" id="settingsBlockEndTime" value="13:00" class="form-control form-control-lg bg-white border rounded-3 shadow-none fs-6">
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label text-dark small fw-bold mb-2">Motivo del Bloqueo *</label>
                    <input type="text" name="reason" class="form-control bg-white border rounded-3 py-2.5 px-3 shadow-none" placeholder="Ej: Vacaciones, Quirófano, Congreso, Mantenimiento equipo..." required>
                </div>

                <button type="submit" class="btn btn-warning text-dark w-100 py-2.5 rounded-pill fw-bold shadow-sm fs-6">
                    <i class="bi bi-lock-fill me-2"></i> Registrar Bloqueo
                </button>
            </form>
        </div>
    </div>

    <!-- LISTADO DE BLOQUEOS EXISTENTES -->
    <div class="col-lg-7">
        <div class="modern-card p-4 h-100 shadow-sm border-0 rounded-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <div>
                    <h5 class="fw-bold text-dark mb-1"><i class="bi bi-list-check text-primary me-2"></i> Bloqueos Activos y Futuros</h5>
                    <small class="text-muted">Al anular un bloqueo se rehabilitan todos los horarios habituales de esa agenda.</small>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr class="text-muted small">
                            <th>Profesional / Agenda</th>
                            <th>Desde</th>
                            <th>Hasta</th>
                            <th>Motivo</th>
                            <th class="text-end">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($blocks as $block)
                            <tr>
                                <td>
                                    <div class="fw-bold text-dark">{{ $block->doctor->name ?? 'General' }}</div>
                                    @if(($block->agenda_type ?? 'medica') === 'tecnica')
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill" style="font-size: 0.7rem;">Agenda Técnica / Estudios</span>
                                    @else
                                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill" style="font-size: 0.7rem;">Agenda Médica</span>
                                    @endif
                                </td>
                                <td>{{ $block->start_datetime->format('d/m/Y H:i') }}</td>
                                <td>{{ $block->end_datetime->format('d/m/Y H:i') }}</td>
                                <td><span class="badge bg-warning bg-opacity-25 text-dark border">{{ $block->reason }}</span></td>
                                <td class="text-end">
                                    <form action="{{ route('agenda.blocks.destroy', $block) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-3 fw-bold shadow-none" onclick="return confirm('¿Anular este bloqueo y rehabilitar la agenda?');">
                                            <i class="bi bi-unlock-fill me-1"></i> Desbloquear
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <i class="bi bi-calendar-check fs-1 text-muted opacity-25 d-block mb-3"></i>
                                    <span class="fw-bold text-muted">Sin Bloqueos Pendientes</span>
                                    <p class="small text-muted mb-0">No hay ausencias programadas para ninguna agenda.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script>
    function toggleSettingsBlockTimeRow() {
        const isTimeRange = document.getElementById('settingsBlockTimeRange')?.checked;
        const row = document.getElementById('settingsBlockTimeRow');
        if (row) {
            if (isTimeRange) row.classList.remove('d-none');
            else row.classList.add('d-none');
        }
    }

    function toggleDayRow(dayNum) {
        const active = document.getElementById(`dayActive_${dayNum}`).checked;
        const card = document.getElementById(`cardDay_${dayNum}`);
        const badge = document.getElementById(`dayStatusBadge_${dayNum}`);
        
        if (active) {
            card.classList.remove('opacity-50');
            badge.innerText = 'Atiende';
            badge.className = 'text-success fw-bold d-block ms-4 small';
        } else {
            card.classList.add('opacity-50');
            badge.innerText = 'No atiende';
            badge.className = 'text-muted d-block ms-4 small';
        }
    }

    function toggleShiftInputs(dayNum, shift) {
        const active = document.getElementById(`${shift}Active_${dayNum}`).checked;
        const startInput = document.getElementById(`${shift}Start_${dayNum}`);
        const endInput = document.getElementById(`${shift}End_${dayNum}`);
        const badge = document.getElementById(`${shift}Badge_${dayNum}`);
        const box = document.getElementById(`${shift}Box_${dayNum}`);

        if (active) {
            startInput.removeAttribute('disabled');
            endInput.removeAttribute('disabled');
            badge.innerText = 'Activo';
            badge.className = 'badge bg-success bg-opacity-10 text-success border border-success small shift-state-badge';
            box.classList.remove('opacity-50');
        } else {
            startInput.setAttribute('disabled', 'true');
            endInput.setAttribute('disabled', 'true');
            badge.innerText = 'Anulado';
            badge.className = 'badge bg-secondary bg-opacity-10 text-secondary border small shift-state-badge';
            box.classList.add('opacity-50');
        }
    }

    function copyDayToAll(sourceDay) {
        const isMorningActive = document.getElementById(`morningActive_${sourceDay}`).checked;
        const morningStart = document.getElementById(`morningStart_${sourceDay}`).value;
        const morningEnd = document.getElementById(`morningEnd_${sourceDay}`).value;

        const isAfternoonActive = document.getElementById(`afternoonActive_${sourceDay}`).checked;
        const afternoonStart = document.getElementById(`afternoonStart_${sourceDay}`).value;
        const afternoonEnd = document.getElementById(`afternoonEnd_${sourceDay}`).value;

        for (let d = 1; d <= 6; d++) {
            if (d === sourceDay) continue;
            if (d <= 5) {
                document.getElementById(`dayActive_${d}`).checked = true;
                toggleDayRow(d);
            }

            document.getElementById(`morningActive_${d}`).checked = isMorningActive;
            document.getElementById(`morningStart_${d}`).value = morningStart;
            document.getElementById(`morningEnd_${d}`).value = morningEnd;
            toggleShiftInputs(d, 'morning');

            document.getElementById(`afternoonActive_${d}`).checked = isAfternoonActive;
            document.getElementById(`afternoonStart_${d}`).value = afternoonStart;
            document.getElementById(`afternoonEnd_${d}`).value = afternoonEnd;
            toggleShiftInputs(d, 'afternoon');
        }

        alert('Los horarios del ' + (document.querySelector(`label[for="dayActive_${sourceDay}"]`).innerText.trim()) + ' se copiaron a los demás días.');
    }

    document.addEventListener('DOMContentLoaded', () => {
        const docSelect = document.getElementById('configDoctorSelect');
        const deleteBtn = document.getElementById('btnDeleteConfig');
        const deleteForm = document.getElementById('deleteConfigForm');
        const labelInput = document.getElementById('configAgendaLabelInput');

        for (let d = 1; d <= 6; d++) {
            if (d === 6) {
                document.getElementById(`dayActive_${d}`).checked = false;
            }
            toggleDayRow(d);
            toggleShiftInputs(d, 'morning');
            toggleShiftInputs(d, 'afternoon');
        }

        docSelect.addEventListener('change', function() {
            const rawVal = this.value;
            deleteBtn.classList.add('d-none');

            if (!rawVal || !rawVal.includes('|')) return;
            const [docId, agendaType] = rawVal.split('|');
            document.getElementById('configDoctorIdHidden').value = docId;
            document.getElementById('configAgendaTypeHidden').value = agendaType;

            const selOpt = this.options[this.selectedIndex];
            labelInput.value = selOpt ? (selOpt.getAttribute('data-label') || '') : '';
            labelInput.placeholder = agendaType === 'tecnica'
                ? 'Ej: Campos Visuales, OCT, Biometría...'
                : 'Ej: Consultorio General, Retina, Glaucoma...';

            axios.get(`/api/agenda/config/${docId}?agenda_type=${encodeURIComponent(agendaType)}`)
                .then(res => {
                    if (res.data.exists) {
                        const config = res.data.config;
                        const workingDays = config.working_days || {};

                        if (config.agenda_label !== undefined && config.agenda_label !== null) {
                            labelInput.value = config.agenda_label;
                        }
                        if (config.appointment_duration) {
                            document.getElementById('appointmentDurationSelect').value = config.appointment_duration;
                        }

                        for (let d = 1; d <= 6; d++) {
                            const dayData = workingDays[d] || workingDays[String(d)] || null;

                            if (dayData && typeof dayData === 'object' && dayData.active !== undefined) {
                                document.getElementById(`dayActive_${d}`).checked = Boolean(dayData.active);
                                toggleDayRow(d);

                                const mActive = Boolean(dayData.morning_active);
                                document.getElementById(`morningActive_${d}`).checked = mActive;
                                if (dayData.morning_start) document.getElementById(`morningStart_${d}`).value = dayData.morning_start.substring(0, 5);
                                if (dayData.morning_end) document.getElementById(`morningEnd_${d}`).value = dayData.morning_end.substring(0, 5);
                                toggleShiftInputs(d, 'morning');

                                const aActive = Boolean(dayData.afternoon_active);
                                document.getElementById(`afternoonActive_${d}`).checked = aActive;
                                if (dayData.afternoon_start) document.getElementById(`afternoonStart_${d}`).value = dayData.afternoon_start.substring(0, 5);
                                if (dayData.afternoon_end) document.getElementById(`afternoonEnd_${d}`).value = dayData.afternoon_end.substring(0, 5);
                                toggleShiftInputs(d, 'afternoon');
                            } else if (Array.isArray(workingDays) && (workingDays.includes(String(d)) || workingDays.includes(d))) {
                                document.getElementById(`dayActive_${d}`).checked = true;
                                toggleDayRow(d);

                                document.getElementById(`morningActive_${d}`).checked = true;
                                if (config.shift_1_start) document.getElementById(`morningStart_${d}`).value = config.shift_1_start.substring(0, 5);
                                if (config.shift_1_end) document.getElementById(`morningEnd_${d}`).value = config.shift_1_end.substring(0, 5);
                                toggleShiftInputs(d, 'morning');

                                const hasShift2 = Boolean(config.shift_2_start && config.shift_2_end);
                                document.getElementById(`afternoonActive_${d}`).checked = hasShift2;
                                if (config.shift_2_start) document.getElementById(`afternoonStart_${d}`).value = config.shift_2_start.substring(0, 5);
                                if (config.shift_2_end) document.getElementById(`afternoonEnd_${d}`).value = config.shift_2_end.substring(0, 5);
                                toggleShiftInputs(d, 'afternoon');
                            } else {
                                document.getElementById(`dayActive_${d}`).checked = false;
                                toggleDayRow(d);
                            }
                        }

                        deleteBtn.classList.remove('d-none');
                        deleteForm.action = `/agenda/settings/config/${docId}`;
                        document.getElementById('deleteConfigAgendaType').value = agendaType;
                    } else {
                        for (let d = 1; d <= 6; d++) {
                            document.getElementById(`dayActive_${d}`).checked = (d <= 5);
                            toggleDayRow(d);
                            document.getElementById(`morningActive_${d}`).checked = true;
                            document.getElementById(`morningStart_${d}`).value = '08:00';
                            document.getElementById(`morningEnd_${d}`).value = '12:00';
                            toggleShiftInputs(d, 'morning');
                            document.getElementById(`afternoonActive_${d}`).checked = true;
                            document.getElementById(`afternoonStart_${d}`).value = '16:00';
                            document.getElementById(`afternoonEnd_${d}`).value = '20:00';
                            toggleShiftInputs(d, 'afternoon');
                        }
                    }
                })
                .catch(err => console.error("Error fetching config", err));
        });
    });

    function deleteConfig() {
        if (confirm("¿Estás seguro de eliminar todo el patrón de horarios de esta agenda? Las demás agendas no se verán afectadas.")) {
            document.getElementById('deleteConfigForm').submit();
        }
    }
</script>
@endsection
