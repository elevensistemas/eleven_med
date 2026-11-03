@extends('layouts.admin')

@section('title', 'Configurar Horarios de Agenda')
@section('subtitle', 'Define días y horarios específicos de atención para cada profesional (mañana y tarde independientes)')

@section('content')

@if(session('success'))
    <div class="alert alert-success border-0 bg-success bg-opacity-10 text-success rounded-4 mb-4" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
    </div>
@endif

<div class="row g-4 mb-4">
    <!-- PANEL PRINCIPAL: PATRÓN SEMANAL PERSONALIZADO POR DÍA -->
    <div class="col-12">
        <div class="modern-card p-4 shadow-sm border-0 rounded-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 pb-3 border-bottom">
                <div>
                    <h5 class="fw-bold text-dark mb-1"><i class="bi bi-calendar-week text-primary me-2"></i> 1. Horarios de Agenda por Día de la Semana</h5>
                    <p class="text-muted small mb-0">Configura qué días atiende el profesional y sus horarios específicos. Puedes activar o anular el turno mañana o tarde según sus actividades (ej: cirugías, docencia o consultorio).</p>
                </div>
            </div>

            <form action="{{ route('agenda.config.store') }}" method="POST" id="weeklyScheduleForm">
                @csrf
                <div class="row g-3 mb-4 align-items-end bg-light p-3 rounded-4 border">
                    <div class="col-md-5">
                        <label class="form-label text-muted small fw-bold">Profesional Médico *</label>
                        <select name="doctor_id" id="configDoctorSelect" class="form-control bg-white border shadow-none form-select" required>
                            <option value="">-- Seleccionar Médico --</option>
                            @foreach($doctors as $doc)
                                <option value="{{ $doc->id }}">{{ $doc->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-muted small fw-bold">Duración de Cada Consulta *</label>
                        <select name="appointment_duration" id="appointmentDurationSelect" class="form-control bg-white border shadow-none form-select" required>
                            <option value="15">Cada 15 minutos</option>
                            <option value="20" selected>Cada 20 minutos (Estándar)</option>
                            <option value="30">Cada 30 minutos</option>
                            <option value="45">Cada 45 minutos</option>
                            <option value="60">Cada 60 minutos (Estudios / Pre-quirúrgico)</option>
                        </select>
                    </div>
                    <div class="col-md-3 text-md-end">
                        <button type="submit" class="btn btn-primary rounded-pill px-4 py-2 fw-bold shadow-sm w-100" style="background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%); border:none;">
                            <i class="bi bi-save me-2"></i> Guardar Horarios
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
                        <i class="bi bi-trash me-2"></i> Eliminar Patrón de este Médico
                    </button>
                    <button type="submit" class="btn btn-primary rounded-pill px-5 py-2 fw-bold shadow-sm ms-auto" style="background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%); border:none;">
                        <i class="bi bi-save me-2"></i> Guardar Todos los Horarios
                    </button>
                </div>
            </form>

            <form id="deleteConfigForm" method="POST" action="">
                @csrf
                @method('DELETE')
            </form>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- PANEL SECUNDARIO: BLOQUEOS DE EXCEPCION Y VACACIONES -->
    <div class="col-lg-5">
        <div class="modern-card p-4 h-100 shadow-sm border-0 rounded-4">
            <h5 class="fw-bold text-dark mb-4"><i class="bi bi-shield-lock text-primary me-2"></i> 2. Bloqueos de Agenda / Vacaciones</h5>
            <p class="text-muted small mb-4">Bloquea temporalmente a un profesional por vacaciones, congresos médicos, días de cirugía o licencias.</p>
            
            <form action="{{ route('agenda.blocks.store') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">Médico Afectado *</label>
                    <select name="doctor_id" class="form-control bg-light border-0 shadow-none form-select" required>
                        <option value="">-- Seleccionar Médico --</option>
                        @foreach($doctors as $doc)
                            <option value="{{ $doc->id }}">{{ $doc->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label text-muted small fw-bold">Fecha / Hora Inicio *</label>
                        <input type="datetime-local" name="start_datetime" class="form-control bg-light border-0 shadow-none" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small fw-bold">Fecha / Hora Fin *</label>
                        <input type="datetime-local" name="end_datetime" class="form-control bg-light border-0 shadow-none" required>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label text-muted small fw-bold">Motivo del Bloqueo *</label>
                    <input type="text" name="reason" class="form-control bg-light border-0 shadow-none" placeholder="Ej: Vacaciones, Quirófano, Congreso..." required>
                </div>

                <button type="submit" class="btn btn-outline-dark w-100 py-2 rounded-pill fw-bold shadow-sm">
                    <i class="bi bi-lock-fill me-2"></i> Registrar Bloqueo
                </button>
            </form>
        </div>
    </div>

    <!-- LISTADO DE BLOQUEOS EXISTENTES -->
    <div class="col-lg-7">
        <div class="modern-card p-4 h-100 shadow-sm border-0 rounded-4">
            <h5 class="fw-bold text-dark mb-4"><i class="bi bi-list-check text-primary me-2"></i> Bloqueos Activos y Futuros</h5>
            
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr class="text-muted small">
                            <th>Profesional</th>
                            <th>Desde</th>
                            <th>Hasta</th>
                            <th>Motivo</th>
                            <th class="text-end">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($blocks as $block)
                            <tr>
                                <td class="fw-bold text-dark">{{ $block->doctor->name ?? 'General' }}</td>
                                <td>{{ $block->start_datetime->format('d/m/Y H:i') }}</td>
                                <td>{{ $block->end_datetime->format('d/m/Y H:i') }}</td>
                                <td><span class="badge bg-warning bg-opacity-25 text-dark border">{{ $block->reason }}</span></td>
                                <td class="text-end">
                                    <form action="{{ route('agenda.blocks.destroy', $block) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-light text-danger rounded-pill px-3 shadow-none" onclick="return confirm('¿Quitar bloqueo y liberar agenda?');">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <i class="bi bi-calendar-check fs-1 text-muted opacity-25 d-block mb-3"></i>
                                    <span class="fw-bold text-muted">Sin Bloqueos Pendientes</span>
                                    <p class="small text-muted mb-0">No hay ausencias programadas para ningún médico.</p>
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
            
            // Only apply to days currently active or apply to weekdays 1-5
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

        // Init toggles for default state
        for (let d = 1; d <= 6; d++) {
            if (d === 6) {
                // Sábado inactive by default
                document.getElementById(`dayActive_${d}`).checked = false;
            }
            toggleDayRow(d);
            toggleShiftInputs(d, 'morning');
            toggleShiftInputs(d, 'afternoon');
        }

        docSelect.addEventListener('change', function() {
            const docId = this.value;
            deleteBtn.classList.add('d-none');

            if (!docId) return;

            axios.get(`/api/agenda/config/${docId}`)
                .then(res => {
                    if (res.data.exists) {
                        const config = res.data.config;
                        const workingDays = config.working_days || {};

                        // Check duration
                        if (config.appointment_duration) {
                            document.getElementById('appointmentDurationSelect').value = config.appointment_duration;
                        }

                        // If array with day keys 1 to 6
                        for (let d = 1; d <= 6; d++) {
                            const dayData = workingDays[d] || workingDays[String(d)] || null;

                            if (dayData && typeof dayData === 'object' && dayData.active !== undefined) {
                                document.getElementById(`dayActive_${d}`).checked = Boolean(dayData.active);
                                toggleDayRow(d);

                                // Morning
                                const mActive = Boolean(dayData.morning_active);
                                document.getElementById(`morningActive_${d}`).checked = mActive;
                                if (dayData.morning_start) document.getElementById(`morningStart_${d}`).value = dayData.morning_start.substring(0, 5);
                                if (dayData.morning_end) document.getElementById(`morningEnd_${d}`).value = dayData.morning_end.substring(0, 5);
                                toggleShiftInputs(d, 'morning');

                                // Afternoon
                                const aActive = Boolean(dayData.afternoon_active);
                                document.getElementById(`afternoonActive_${d}`).checked = aActive;
                                if (dayData.afternoon_start) document.getElementById(`afternoonStart_${d}`).value = dayData.afternoon_start.substring(0, 5);
                                if (dayData.afternoon_end) document.getElementById(`afternoonEnd_${d}`).value = dayData.afternoon_end.substring(0, 5);
                                toggleShiftInputs(d, 'afternoon');
                            } else if (Array.isArray(workingDays) && (workingDays.includes(String(d)) || workingDays.includes(d))) {
                                // Legacy array of days
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

                        // Show delete button
                        deleteBtn.classList.remove('d-none');
                        deleteForm.action = `/agenda/settings/config/${docId}`;
                    } else {
                        // Reset to defaults
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
        if (confirm("¿Estás seguro de eliminar todo el patrón de horarios de este profesional? Desactivarás su agenda completamente.")) {
            document.getElementById('deleteConfigForm').submit();
        }
    }
</script>
@endsection