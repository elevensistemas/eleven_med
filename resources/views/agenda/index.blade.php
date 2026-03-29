@extends('layouts.admin')

@section('title', 'Agenda Médica Central')
@section('subtitle', 'Visión simultánea de todas las agendas de la clínica')

@section('content')
<style>
/* Phase 18 Vanilla Calendar Styles */
.cal-day-header { font-weight: bold; text-align: center; font-size: 0.8rem; color: #a0a0a0; padding-bottom: 5px; }
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
.cal-day.unavailable { color: #ccc; cursor: default; }
.cal-day.available { background-color: rgba(40, 167, 69, 0.1); color: #198754; border: 1px solid rgba(40, 167, 69, 0.3); }
.cal-day.available:hover { background-color: #198754; color: #fff; transform: scale(1.1); }
.cal-day.full { background-color: rgba(220, 53, 69, 0.15); color: #dc3545; border: 1px solid rgba(220, 53, 69, 0.5); }
.cal-day.full:hover { background-color: #dc3545; color: #fff; transform: scale(1.1); }
.cal-day.selected-day { box-shadow: 0 0 0 3px rgba(94, 106, 210, 0.5); transform: scale(1.1); }

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
.slot-booked { border-left: 4px solid #dc3545; background: #fffcfc; }
</style>

<!-- Leyenda Global -->
<div class="d-flex align-items-center justify-content-end gap-3 mb-4">
    <div class="d-flex align-items-center gap-2">
        <div style="width:16px; height:16px; border-radius:50%; background-color: rgba(40, 167, 69, 0.1); border: 1px solid rgba(40, 167, 69, 0.3);"></div> 
        <small class="text-muted fw-bold">Día Disponible</small>
    </div>
    <div class="d-flex align-items-center gap-2">
        <div style="width:16px; height:16px; border-radius:50%; background-color: rgba(220, 53, 69, 0.15); border: 1px solid rgba(220, 53, 69, 0.5);"></div> 
        <small class="text-muted fw-bold">Día Saturado</small>
    </div>
    <div class="d-flex align-items-center gap-2">
        <div style="width:16px; height:16px; border-radius:50%; background-color: transparent; border: 1px solid #ccc;"></div> 
        <small class="text-muted fw-bold">Ausencia</small>
    </div>
</div>

<!-- Iterar por Cada Médico Renderizando Agendas Múltiples -->
@foreach($doctors as $doc)
<div class="modern-card p-0 mb-3 shadow-sm border overflow-hidden">
    <div class="bg-light px-3 py-2 border-bottom d-flex align-items-center justify-content-between">
        <h6 class="fw-bold mb-0 text-dark d-flex align-items-center">
            <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px;">
                <i class="bi bi-person-fill" style="font-size: 1rem;"></i>
            </div>
            Agenda de {{ $doc->name }}
        </h6>
        <button class="btn btn-outline-primary btn-sm rounded-pill px-3 shadow-sm d-none" data-bs-toggle="modal" data-bs-target="#newAppointmentModal" onclick="document.getElementById('formDoctorSelect').value={{ $doc->id }}">
            + Forzar Turno Manual
        </button>
    </div>
    
    <div class="row g-0">
        <!-- Columna Izquierda: Mini Calendario JS -->
        <div class="col-lg-5 p-3 border-end">
            <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                <button class="btn btn-sm btn-light border-0 rounded-circle shadow-sm" style="width:30px; height:30px;" onclick="changeMonth({{ $doc->id }}, -1)"><i class="bi bi-chevron-left"></i></button>
                <h6 id="monthYearLabel_{{ $doc->id }}" class="mb-0 fw-bold text-dark text-capitalize"></h6>
                <button class="btn btn-sm btn-light border-0 rounded-circle shadow-sm" style="width:30px; height:30px;" onclick="changeMonth({{ $doc->id }}, 1)"><i class="bi bi-chevron-right"></i></button>
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
        <div class="col-lg-7 d-flex flex-column" style="background: #fafafa; min-height:280px;">
            <div class="px-3 py-2 bg-white border-bottom d-flex justify-content-between align-items-center shadow-sm z-1">
                <div>
                    <h6 id="selectedDateLabel_{{ $doc->id }}" class="fw-bold mb-0 text-dark">Agenda Diaria</h6>
                    <small class="text-muted" style="font-size: 0.75rem;" id="selectedDateSubLabel_{{ $doc->id }}">Seleccione un día en el calendario</small>
                </div>
                <div id="dateLoadingSpinner_{{ $doc->id }}" class="spinner-border text-primary spinner-border-sm d-none" role="status"></div>
            </div>
            
            <div id="slotsContainer_{{ $doc->id }}" class="p-3 flex-grow-1 overflow-auto" style="max-height: 280px;">
                <!-- Estado vacío -->
                <div class="text-center py-4 opacity-50">
                    <i class="bi bi-calendar-x" style="font-size: 4rem;"></i>
                    <h6 class="mt-3">Sin selección</h6>
                    <small>Haga clic en un día verde para ver los horarios.</small>
                </div>
            </div>
            
            <!-- Phase 17: Express Nearest Slot Footer (Moved inside doctor card) -->
            <div class="mt-auto bg-white border-top p-3 d-flex align-items-center justify-content-between shadow-sm z-1" style="border-left: 4px solid #198754;">
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
                                <button type="button" class="btn btn-sm btn-light border-0 text-danger rounded-circle shadow-sm" onclick="clearModalPatientSelection()" title="Cambiar Paciente"><i class="bi bi-x-lg"></i></button>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label text-muted small fw-bold">Fecha del Turno *</label>
                        <input type="date" name="date" class="form-control bg-light border-0 shadow-none" id="formDateSelect" required readonly>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label text-muted small fw-bold">Horario de Inicio *</label>
                        <input type="time" name="time" class="form-control bg-light border-0 shadow-none" id="formTimeSelect" required readonly>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label text-muted small fw-bold">Duración Estimada (Minutos)</label>
                        <input type="number" name="duration_minutes" class="form-control bg-light border-0 shadow-none" id="formDurationSelect" required readonly>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label text-muted small fw-bold">Motivo / Notas rápidas</label>
                        <input type="text" name="reason" class="form-control bg-light border-0 shadow-none" placeholder="Ej: Control">
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

    function clearModalPatientSelection() {
        modalSelectedPatientId.value = '';
        modalPatientSearchInput.value = '';
        
        modalPatientSelectedMode.classList.add('d-none');
        modalPatientSearchMode.classList.remove('d-none');
        
        submitAppointmentBtn.setAttribute('disabled', 'true');
        modalPatientSearchInput.focus();
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
            const status = availabilityMap[dateStr] || 'unavailable';
            
            let extraClass = '';
            if (status === 'available') extraClass = 'available';
            if (status === 'full') extraClass = 'full';
            if (status === 'unavailable') extraClass = 'unavailable';
            if (dateStr === state.selectedDateStr) extraClass += ' selected-day';

            const div = document.createElement('div');
            div.className = `cal-day ${extraClass}`;
            div.innerText = day;
            
            if (status === 'available' || status === 'full') {
                div.onclick = (e) => loadDaySlots(doctorId, dateStr, e);
            }

            grid.appendChild(div);
        }
    }

    function loadDaySlots(doctorId, dateStr, e) {
        doctorStates[doctorId].selectedDateStr = dateStr;
        
        // Remove class from siblings in THIS specific grid
        const grid = document.getElementById(`miniCalendarGrid_${doctorId}`);
        grid.querySelectorAll('.cal-day').forEach(el => el.classList.remove('selected-day'));
        if (e && e.target) {
             e.target.classList.add('selected-day');
        }

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
                
                if(res.data.slots.length === 0) {
                     document.getElementById(`selectedDateSubLabel_${doctorId}`).innerText = res.data.message || "Sin horarios";
                     return;
                }

                document.getElementById(`selectedDateSubLabel_${doctorId}`).innerText = res.data.slots.length + " fracciones encontradas";
                let html = '';
                
                res.data.slots.forEach(slot => {
                    if (slot.status === 'booked') {
                        html += `
                        <div class="slot-card slot-booked flex-row justify-content-between">
                            <div class="d-flex align-items-center gap-3">
                                <span class="slot-time d-block text-danger">${slot.time}</span>
                                <div>
                                    <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-person-lock me-1"></i> ${slot.patient_name}</h6>
                                    <small class="text-danger">Ocupado</small>
                                </div>
                            </div>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-light p-1 border-0 rounded-circle shadow-none" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bi bi-three-dots-vertical fs-5 text-muted"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3 mt-1">
                                    <li><button class="dropdown-item py-2 text-danger fw-bold" onclick="cancelAppointment(${slot.appointment_id}, ${doctorId}, '${dateStr}')"><i class="bi bi-trash me-2"></i> Eliminar Turno</button></li>
                                </ul>
                            </div>
                        </div>`;
                    } else {
                        html += `
                        <div class="slot-card slot-free gap-3 justify-content-between" onclick="openBookingFromExpress(${doctorId}, '${dateStr}', '${slot.time}', ${res.data.slot_duration})">
                            <div class="d-flex align-items-center gap-3">
                                <span class="slot-time d-block text-success">${slot.time}</span>
                                <div>
                                    <h6 class="mb-0 text-success fw-bold">Turno Libre</h6>
                                    <small class="text-muted">Clic para asignar un paciente</small>
                                </div>
                            </div>
                            <button class="btn btn-sm btn-success rounded-circle shadow-sm" style="width:30px; height:30px; padding:0;"><i class="bi bi-plus text-white"></i></button>
                        </div>`;
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
    });
</script>

@endsection
