@extends('layouts.admin')

@section('title', 'Crear Agenda Semanal')
@section('subtitle', 'Define el patrón de trabajo habitual y excepciones de tus profesionales')

@section('content')

@if(session('success'))
    <div class="alert alert-success border-0 bg-success bg-opacity-10 text-success rounded-4 mb-4" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
    </div>
@endif

<div class="row g-4 mb-4">
    <!-- PANEL PRINCIPAL: PATRÓN SEMANAL -->
    <div class="col-12">
        <div class="modern-card p-4">
            <h5 class="fw-bold text-dark mb-4"><i class="bi bi-calendar-week text-primary me-2"></i> 1. Establecer Patrón de Trabajo Semanal</h5>
            <p class="text-muted small mb-4">Selecciona un profesional para definir qué días trabaja habitualmente y en qué horarios. Esto restringirá los turnos que se le pueden asignar en el almanaque principal.</p>

            <form action="{{ route('agenda.config.store') }}" method="POST">
                @csrf
                <div class="row g-4">
                    <!-- Columna Izquierda: Médico y Días -->
                    <div class="col-lg-5 border-end pe-lg-4">
                        <div class="mb-4">
                            <label class="form-label text-muted small fw-bold">Especialista Mapeado *</label>
                            <select name="doctor_id" id="configDoctorSelect" class="form-control bg-light border-0 shadow-none form-select" required>
                                <option value="">-- Seleccionar Médico --</option>
                                @foreach($doctors as $doc)
                                    <option value="{{ $doc->id }}">{{ $doc->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label text-muted small fw-bold d-block mb-3">Días Operativos en la Clínica *</label>
                            <!-- Switch Checkboxes para los días -->
                            <div class="d-flex flex-wrap gap-2">
                                @foreach(['1' => 'Lunes', '2' => 'Martes', '3' => 'Miércoles', '4' => 'Jueves', '5' => 'Viernes', '6' => 'Sábado'] as $val => $day)
                                <div class="btn-group" role="group">
                                    <input type="checkbox" class="btn-check" name="working_days[]" id="daybtn{{ $val }}" value="{{ $val }}" autocomplete="off">
                                    <label class="btn btn-outline-primary rounded-pill px-3 shadow-none fw-medium" for="daybtn{{ $val }}">{{ mb_substr($day, 0, 3, 'UTF-8') }}</label>
                                </div>
                                @endforeach
                            </div>
                        </div>

                        <div>
                            <label class="form-label text-muted small fw-bold">Duración Estándar de cada Consulta *</label>
                            <select name="appointment_duration" class="form-control bg-light border-0 shadow-none form-select" required>
                                <option value="15">Cada 15 minutos (Alta rotación)</option>
                                <option value="20" selected>Cada 20 minutos (Estándar Clínico)</option>
                                <option value="30">Cada 30 minutos (Extendido)</option>
                                <option value="60">Cada 60 minutos (Pre-Quirúrgico pleno)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Columna Derecha: Turnos -->
                    <div class="col-lg-7 ps-lg-4">
                        <!-- Turno Mañana -->
                        <div class="p-3 bg-light rounded-4 border mb-3">
                            <div class="d-flex align-items-center mb-3">
                                <i class="bi bi-sunrise text-warning fs-5 me-2"></i> 
                                <span class="fw-bold text-dark">Turno Mañana (Principal)</span>
                            </div>
                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="text-muted small">Hora de Inicio</label>
                                    <input type="time" name="shift_1_start" class="form-control border-0 shadow-none" required>
                                </div>
                                <div class="col-6">
                                    <label class="text-muted small">Hora de Fin</label>
                                    <input type="time" name="shift_1_end" class="form-control border-0 shadow-none" required>
                                </div>
                            </div>
                        </div>

                        <!-- Turno Tarde -->
                        <div class="p-3 bg-light rounded-4 border">
                            <div class="d-flex align-items-center mb-3">
                                <i class="bi bi-sunset text-danger fs-5 me-2"></i> 
                                <span class="fw-bold text-dark">Turno Tarde (Opcional - Partido)</span>
                                <small class="ms-auto text-muted">Dejar vacío si no aplica</small>
                            </div>
                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="text-muted small">Hora de Inicio</label>
                                    <input type="time" name="shift_2_start" class="form-control border-0 shadow-none">
                                </div>
                                <div class="col-6">
                                    <label class="text-muted small">Hora de Fin</label>
                                    <input type="time" name="shift_2_end" class="form-control border-0 shadow-none">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-4 text-end d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-outline-danger rounded-pill px-4 fw-bold shadow-sm d-none" id="btnDeleteConfig" onclick="deleteConfig()">
                                <i class="bi bi-trash me-2"></i> Eliminar
                            </button>
                            <button type="submit" class="btn btn-primary rounded-pill px-5 py-2 fw-bold shadow-sm" style="background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%); border:none;">
                                <i class="bi bi-save me-2"></i> Guardar Patrón Base
                            </button>
                        </div>
                    </div>
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
    <!-- PANEL SECUNDARIO: BLOQUEOS DE EXCEPCION -->
    <div class="col-lg-5">
        <div class="modern-card p-4 h-100">
            <h5 class="fw-bold text-dark mb-4"><i class="bi bi-shield-lock text-primary me-2"></i> 2. Excepciones & Bloqueos</h5>
            <p class="text-muted small mb-4">Utiliza esto para bloquear temporalmente a un médico por vacaciones, congresos o enfermedad fuera de su rango normal.</p>
            
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
                        <label class="form-label text-muted small fw-bold">Inicio *</label>
                        <input type="datetime-local" name="start_datetime" class="form-control bg-light border-0 shadow-none" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small fw-bold">Fin *</label>
                        <input type="datetime-local" name="end_datetime" class="form-control bg-light border-0 shadow-none" required>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label text-muted small fw-bold">Motivo *</label>
                    <select name="reason" class="form-control bg-light border-0 shadow-none form-select" required>
                        <option value="Vacaciones">Vacaciones</option>
                        <option value="Congreso Médico">Congreso Médico</option>
                        <option value="Día Quirúrgico">Día Quirúrgico (Fuera de Consultorio)</option>
                        <option value="Licencia por Enfermedad">Licencia Enfermedad</option>
                        <option value="Otro">Otro (Especificar en notas)</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-outline-dark w-100 py-3 rounded-pill fw-bold shadow-sm">
                    Aplicar Bloqueo Temporal
                </button>
            </form>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="modern-card p-4 h-100">
            <h5 class="fw-bold text-dark mb-4"><i class="bi bi-airplane-engines text-danger me-2"></i> Ausencias Programadas Activas</h5>

            <div class="table-responsive">
                <table class="table table-hover align-middle border-light">
                    <thead class="bg-light">
                        <tr>
                            <th class="py-3 text-muted small text-uppercase">Médico</th>
                            <th class="py-3 text-muted small text-uppercase">Periodo</th>
                            <th class="py-3 text-muted small text-uppercase">Motivo</th>
                            <th class="py-3 text-muted small text-uppercase text-end">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($blocks as $block)
                            <tr>
                                <td class="fw-bold text-dark py-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width:32px; height:32px; font-size: 0.8rem;">
                                            <i class="bi bi-person-fill"></i>
                                        </div>
                                        <span>{{ $block->doctor->name }}</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <span class="text-dark fw-medium" style="font-size:0.9rem;">{{ \Carbon\Carbon::parse($block->start_datetime)->format('d/m/y H:i') }}</span>
                                        <span class="text-muted" style="font-size:0.8rem;">a {{ \Carbon\Carbon::parse($block->end_datetime)->format('d/m/y H:i') }}</span>
                                    </div>
                                </td>
                                <td><span class="badge bg-secondary bg-opacity-10 text-secondary">{{ $block->reason }}</span></td>
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
                                <td colspan="4" class="text-center py-5">
                                    <i class="bi bi-calendar-check fs-1 text-muted opacity-25 d-block mb-3"></i>
                                    <span class="fw-bold text-muted">Agenda 100% Libre</span>
                                    <p class="small text-muted mb-0">No hay ausencias prolongadas cargadas para la planilla activa.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const docSelect = document.getElementById('configDoctorSelect');
        const deleteBtn = document.getElementById('btnDeleteConfig');
        const deleteForm = document.getElementById('deleteConfigForm');

        docSelect.addEventListener('change', function() {
            const docId = this.value;
            
            // Restablecer formulario preventivamente
            document.querySelectorAll('input[name="working_days[]"]').forEach(cb => cb.checked = false);
            document.querySelector('input[name="shift_1_start"]').value = '';
            document.querySelector('input[name="shift_1_end"]').value = '';
            document.querySelector('input[name="shift_2_start"]').value = '';
            document.querySelector('input[name="shift_2_end"]').value = '';
            deleteBtn.classList.add('d-none');

            if(!docId) return;

            // Phase 19: Obtención de Patrón Existente
            axios.get(`/api/agenda/config/${docId}`)
                .then(res => {
                    if (res.data.exists) {
                        const config = res.data.config;
                        
                        // Set days (json parsed natively by axios if array)
                        const days = config.working_days || [];
                        days.forEach(day => {
                            const cb = document.getElementById('daybtn' + day);
                            if(cb) cb.checked = true;
                        });

                        // Set shifts
                        document.querySelector('input[name="shift_1_start"]').value = config.shift_1_start ? config.shift_1_start.substring(0, 5) : '';
                        document.querySelector('input[name="shift_1_end"]').value = config.shift_1_end ? config.shift_1_end.substring(0, 5) : '';
                        document.querySelector('input[name="shift_2_start"]').value = config.shift_2_start ? config.shift_2_start.substring(0, 5) : '';
                        document.querySelector('input[name="shift_2_end"]').value = config.shift_2_end ? config.shift_2_end.substring(0, 5) : '';
                        
                        // Set duration
                        document.querySelector('select[name="appointment_duration"]').value = config.appointment_duration;

                        // Mostrar botón borrar
                        deleteBtn.classList.remove('d-none');
                        deleteForm.action = `/agenda/settings/config/${docId}`;
                    }
                })
                .catch(err => console.error("Error fetching config", err));
        });
    });

    function deleteConfig() {
        if(confirm("¿Estás seguro de eliminar todo el patrón de horarios de este profesional? Desactivarás su agenda completamente.")) {
            document.getElementById('deleteConfigForm').submit();
        }
    }
</script>
@endsection
