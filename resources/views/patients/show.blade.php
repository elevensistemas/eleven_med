@extends('layouts.admin')

@section('title', 'Historia Clínica')
@section('subtitle', 'Visualización detallada y Gestión Médica')

@section('content')
<style>
/* Estilos Específicos Eleven Med - Rediseño Moderno */
.patient-container {
    background-color: #ffffff;
    border-radius: 1.5rem;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.05);
    overflow: hidden;
    min-height: calc(100vh - 120px);
}
.patient-inner-sidebar {
    background-color: #f8f9fc;
    border-right: 1px solid rgba(0,0,0,0.03);
}
.sidebar-link {
    color: #555c6e;
    font-size: 0.88rem;
    font-weight: 500;
    padding: 10px 14px;
    border-radius: 0.8rem;
    margin-bottom: 6px;
    display: flex;
    align-items: center;
    transition: all 0.3s ease;
    text-decoration: none;
    white-space: nowrap;
}
.sidebar-link i {
    width: 22px;
    font-size: 1.1rem;
    margin-right: 8px;
    text-align: center;
    transition: transform 0.2s ease;
}
.sidebar-link:hover {
    background-color: rgba(13, 110, 253, 0.08); /* Modern Blue */
    color: #0d6efd;
    font-weight: 600;
}
.sidebar-link:hover i {
    transform: translateX(3px);
}
.sidebar-link.text-danger:hover {
    background-color: rgba(220, 53, 69, 0.08);
    color: #dc3545 !important;
}

.antecedentes-box {
    background-color: rgba(255,255,255,0.8);
    border: 1px solid rgba(0,0,0,0.06);
    min-height: 80px;
    padding: 12px;
    font-size: 0.85rem;
    color: #555;
    border-radius: 0.8rem;
    white-space: pre-line;
    line-height: 1.4;
}

/* Gradient Buttons */
.btn-gradient-warning {
    background: linear-gradient(135deg, #FF9900 0%, #FF5500 100%);
    border: none;
    color: white !important;
    transition: transform 0.2s, box-shadow 0.2s;
}
.btn-gradient-warning:hover {
    transform: translateY(-2px);
    box-shadow: 0 0.5rem 1rem rgba(255, 85, 0, 0.25);
}
.btn-gradient-primary {
    background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
    border: none;
    transition: transform 0.2s, box-shadow 0.2s;
}
.btn-gradient-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 0.5rem 1rem rgba(13, 110, 253, 0.25);
    color: white;
}

/* Modern Tabs */
.modern-tabs {
    border-bottom: 0;
    gap: 0.2rem;
}
.modern-tabs .nav-link {
    color: #6c757d;
    border: none;
    background: transparent;
    padding: 8px 16px;
    font-size: 0.9rem;
    font-weight: 600;
    border-radius: 50rem; /* Pill shape */
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.modern-tabs .nav-link:hover {
    background-color: rgba(13, 110, 253, 0.05);
    color: #0d6efd;
}
.modern-tabs .nav-link.active {
    background-color: #0d6efd;
    color: #ffffff;
    box-shadow: 0 4px 6px rgba(13, 110, 253, 0.2);
}

/* Reusing cards logic inside panes */
.data-card {
    background: #fff;
    border: 1px solid rgba(0,0,0,0.05);
    border-radius: 0.8rem;
    padding: 1rem 1.2rem;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
}
.value-badge {
    background-color: #f8f9fc;
    border: 1px solid rgba(0,0,0,0.05);
    color: #333;
    padding: 0.2rem 0.6rem;
    border-radius: 4px;
    font-weight: 700;
    font-size: 0.85rem;
}
</style>

<div class="row g-0 patient-container">
    <!-- Inner Sidebar (Izquierda) -->
    <div class="col-md-4 col-lg-3 col-xl-2 patient-inner-sidebar p-3 d-flex flex-column">
        <div class="d-flex flex-column align-items-center text-center mb-4 pb-2 border-bottom border-light">
            <div class="icon-box rounded-circle shadow-sm bg-primary bg-gradient text-white d-flex align-items-center justify-content-center mb-3" id="patientAvatarBox" style="width: 70px; height: 70px; font-size: 2rem; cursor: pointer; overflow: hidden; position: relative;" onclick="document.getElementById('patientPhotoInput').click()" onmouseover="document.getElementById('avatarOverlay').style.opacity=1" onmouseout="document.getElementById('avatarOverlay').style.opacity=0">
                @if($patient->photo_path)
                    <img src="{{ asset('storage/' . $patient->photo_path) }}" alt="Foto" style="width: 100%; height: 100%; object-fit: cover;" id="patientAvatarImage">
                    <i class="bi bi-person-fill" id="patientAvatarIcon" style="display: none;"></i>
                @else
                    <img src="" alt="Foto" style="width: 100%; height: 100%; object-fit: cover; display: none;" id="patientAvatarImage">
                    <i class="bi bi-person-fill" id="patientAvatarIcon"></i>
                @endif
                <div id="avatarOverlay" class="text-white w-100 h-100 d-flex align-items-center justify-content-center" style="position: absolute; top:0; left:0; background: rgba(0,0,0,0.4); opacity: 0; transition: 0.2s; font-size: 1.2rem;">
                    <i class="bi bi-camera"></i>
                </div>
            </div>
            <input type="file" id="patientPhotoInput" accept="image/*" hidden>
            <h5 class="fw-bold mb-2 text-dark" style="font-size: 1.1rem; line-height: 1.3; letter-spacing: -0.3px;">{{ $patient->first_name }} {{ $patient->last_name }}</h5>
            <span class="badge bg-white text-secondary border px-3 py-2 rounded-pill shadow-sm mb-2"><i class="bi bi-card-text me-1"></i> D.N.I: {{ $patient->dni }}</span>
        </div>

        <div class="mb-5">
            <span class="text-uppercase text-primary fw-bold small ms-2 mb-2 d-block" style="letter-spacing: 0.5px;">Antecedentes</span>
            <div class="antecedentes-box shadow-sm">
                {{ $patient->medical_notes ?? 'Sin antecedentes registrados.' }}
            </div>
        </div>

        <nav class="nav flex-column gap-1 flex-grow-1">
            <a href="{{ route('patients.index') }}" class="sidebar-link"><i class="bi bi-arrow-left"></i> Volver a Lista</a>
            <a href="{{ route('patient.visits.create', $patient) }}" class="sidebar-link"><i class="bi bi-person-plus"></i> Nueva Visita</a>
            <button class="sidebar-link border-0 w-100 text-start bg-transparent" onclick="activateBottomTab('cirugias-tab')"><i class="bi bi-bandaid"></i> Cirugías</button>
            <button class="sidebar-link border-0 w-100 text-start bg-transparent" onclick="activateBottomTab('asignacion-tab')"><i class="bi bi-person-badge"></i> Asignar Médico</button>
            <button class="sidebar-link border-0 w-100 text-start bg-transparent" data-bs-toggle="modal" data-bs-target="#uploadStudyModal"><i class="bi bi-cloud-arrow-up"></i> Subir Estudio</button>
            <a href="{{ route('patients.edit', $patient) }}" class="sidebar-link"><i class="bi bi-pencil"></i> Editar Perfil</a>
            <button class="sidebar-link border-0 w-100 text-start bg-transparent" onclick="activateBottomTab('comentarios-tab')"><i class="bi bi-chat-left-text"></i> Comentarios</button>
            <button class="sidebar-link border-0 w-100 text-start bg-transparent" onclick="activateBottomTab('historial-tab')"><i class="bi bi-journal-medical"></i> Historia Clínica</button>
            <button class="sidebar-link border-0 w-100 text-start bg-transparent" onclick="alert('Módulo de Recetas en construcción.')"><i class="bi bi-file-earmark-medical"></i> Generar Receta</button>
            
            <div class="mt-auto pt-4">
                <form action="{{ route('patients.destroy', $patient) }}" method="POST">
                    @csrf @method('DELETE')
                    <button type="submit" class="sidebar-link text-danger border-0 w-100 text-start bg-transparent" onclick="return confirm('¿Está seguro de eliminar este paciente de forma permanente?');">
                        <i class="bi bi-trash"></i> Eliminar Paciente
                    </button>
                </form>
            </div>
        </nav>
    </div>

    <!-- Contenido Principal (Derecha) -->
    <div class="col-md-8 col-lg-9 col-xl-10 p-3 p-xl-4 bg-white">
        <!-- HEADER TOP -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold text-dark mb-0 m-0 p-0">Tablero del Paciente</h5>
            <a href="{{ route('agenda.index') }}" class="btn btn-gradient-warning rounded-pill px-4 fw-bold shadow-sm d-flex align-items-center gap-2 py-1 text-decoration-none">
                <i class="bi bi-calendar-plus"></i> Dar turno
            </a>
        </div>

        <!-- Panel Superior (Top Tabs) -->
        <ul class="nav modern-tabs mb-3" id="topTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="informacion-tab" data-bs-toggle="tab" data-bs-target="#informacion" type="button" role="tab"><i class="bi bi-info-circle me-1"></i> Información</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="otros-tab" data-bs-toggle="tab" data-bs-target="#otros" type="button" role="tab"><i class="bi bi-three-dots me-1"></i> Otros Datos</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="ultimavisita-tab" data-bs-toggle="tab" data-bs-target="#ultimavisita" type="button" role="tab"><i class="bi bi-clock-history me-1"></i> Última Visita</button>
            </li>
        </ul>

        <div class="tab-content mb-4" id="topTabsContent">
            <!-- Pestaña: Información -->
            <div class="tab-pane fade show active" id="informacion" role="tabpanel">
                <div class="data-card bg-light border-0">
                    <div class="row g-2 align-items-center" style="font-size: 0.9rem;">
                        <div class="col-md-4 d-flex justify-content-between border-end pe-3">
                            <span class="text-muted fw-bold">O. Social:</span>
                            <span class="fw-bold text-dark">{{ $patient->obra_social ?? 'PARTICULAR' }}</span>
                        </div>
                        <div class="col-md-4 d-flex justify-content-between border-end pe-3 ps-3">
                            <span class="text-muted fw-bold">Plan:</span>
                            <span class="fw-bold text-dark">{{ $patient->plan ?? '-' }}</span>
                        </div>
                        <div class="col-md-4 d-flex justify-content-between ps-3">
                            <span class="text-muted fw-bold">Nro Afiliado:</span>
                            <span class="fw-bold text-dark">{{ $patient->affiliate_number ?? '-' }}</span>
                        </div>
                        
                        <div class="col-12 m-0 p-0"><hr class="my-1 border-light"></div>
                        
                        <div class="col-md-4 d-flex justify-content-between border-end pe-3">
                            <span class="text-muted fw-bold">Nacimiento:</span>
                            <span class="fw-bold text-dark">{{ $patient->date_of_birth ? \Carbon\Carbon::parse($patient->date_of_birth)->format('d M, Y') : '-' }}</span>
                        </div>
                        <div class="col-md-4 d-flex justify-content-between border-end pe-3 ps-3">
                            <span class="text-muted fw-bold">Recomendó:</span>
                            <span class="fw-bold text-dark">-</span>
                        </div>
                        <div class="col-md-4 d-flex justify-content-between ps-3">
                            <span class="text-muted fw-bold">C. I.V.A.:</span>
                            <span class="fw-bold text-dark text-capitalize">{{ $patient->iva_condition ?? 'Consumidor Final' }}</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Pestaña: Otros -->
            <div class="tab-pane fade" id="otros" role="tabpanel">
                <div class="data-card border-0 py-2">
                    <div class="row g-3">
                        <div class="col-md-4 d-flex flex-column">
                            <span class="text-muted small fw-bold">Teléfono</span>
                            <span class="fw-bold text-dark d-flex align-items-center">
                                {{ $patient->phone ?? 'S/D' }}
                                @if($patient->phone)
                                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $patient->phone) }}" target="_blank" class="ms-2 text-success text-decoration-none transition-all" title="Enviar WhatsApp" style="transition: transform 0.2s; display: inline-block;" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
                                        <i class="bi bi-whatsapp fs-5 drop-shadow-sm"></i>
                                    </a>
                                @endif
                            </span>
                        </div>
                        <div class="col-md-4 d-flex flex-column border-start ps-3">
                            <span class="text-muted small fw-bold">Correo</span>
                            <span class="fw-bold text-dark">{{ $patient->email ?? 'S/D' }}</span>
                        </div>
                        <div class="col-md-4 d-flex flex-column border-start ps-3">
                            <span class="text-muted small fw-bold">Profesión</span>
                            <span class="fw-bold text-dark">{{ $patient->profession ?? 'S/D' }}</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Pestaña: Última Visita -->
            <div class="tab-pane fade" id="ultimavisita" role="tabpanel">
                <div class="data-card bg-primary bg-opacity-10 border-primary border-opacity-25 pb-0">
                    @if($patient->visits->count() > 0)
                        @php $lastVisit = $patient->visits->first(); @endphp
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center pb-4">
                            <div class="d-flex align-items-start gap-3">
                                <div class="bg-white p-3 rounded-circle shadow-sm text-primary"><i class="bi bi-clock-history fs-3"></i></div>
                                <div>
                                    <h5 class="fw-bold text-primary mb-1">Consulta del {{ $lastVisit->created_at->format('d/m/Y - H:i') }}</h5>
                                    <p class="mb-1 text-dark"><span class="fw-bold text-muted">Dr. Tratante:</span> {{ $lastVisit->doctor->name ?? 'Staff' }}</p>
                                    <p class="mb-0 text-dark"><span class="fw-bold text-muted">Motivo:</span> {{ $lastVisit->motivo_consulta ?? 'Control' }}</p>
                                </div>
                            </div>
                            <button class="btn btn-primary rounded-pill px-4 shadow-sm mt-3 mt-md-0 fw-bold" onclick="activateBottomTab('historial-tab')">Ir al Historial <i class="bi bi-arrow-right ms-1"></i></button>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="bi bi-info-circle text-primary fs-2 opacity-50 mb-2 d-block"></i>
                            <h6 class="fw-bold text-primary">No hay última visita</h6>
                            <p class="text-muted small mb-0">No se ha cargado la evolución clínica de este paciente.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <h5 class="fw-bold text-dark mb-3"><i class="bi bi-grid text-muted me-2"></i> Módulos Clínicos</h5>
        
        <!-- Panel Inferior (Bottom Tabs) -->
        <ul class="nav modern-tabs mb-4 overflow-x-auto flex-nowrap pb-2" id="bottomTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="asignacion-tab" data-bs-toggle="tab" data-bs-target="#asignacion">Asignación</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="diagnostico-tab" data-bs-toggle="tab" data-bs-target="#diagnostico">Diagnóstico</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="estudios-tab" data-bs-toggle="tab" data-bs-target="#estudios">Estudios</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="comentarios-tab" data-bs-toggle="tab" data-bs-target="#comentarios">Comentarios</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="cirugias-tab" data-bs-toggle="tab" data-bs-target="#cirugias">Cirugías</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="turnos-tab" data-bs-toggle="tab" data-bs-target="#turnos">Turnos</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="sobreturnos-tab" data-bs-toggle="tab" data-bs-target="#sobreturnos">Sobreturnos</button>
            </li>
            <li class="nav-item ms-auto" role="presentation">
                <button class="nav-link text-white rounded-pill px-4 shadow-sm" style="background: linear-gradient(135deg, var(--bs-primary) 0%, #6610f2 100%);" id="historial-tab" data-bs-toggle="tab" data-bs-target="#historial"><i class="bi bi-journal-medical me-1"></i> Historia Clínica</button>
            </li>
        </ul>

        <div class="tab-content pb-4" id="bottomTabsContent">
            
            <!-- Tab: Asignación (Consola V2) -->
            <div class="tab-pane fade show active" id="asignacion" role="tabpanel">
                
                @if(session('error'))
                    <div class="alert alert-danger bg-danger bg-opacity-10 text-danger border-0 rounded-4 shadow-sm mb-4"><i class="bi bi-exclamation-triangle-fill"></i> {{ session('error') }}</div>
                @endif

                <!-- Formulario de Ingreso a Consola -->
                <div class="bg-white rounded-4 shadow-sm p-4 mb-4" style="border: 1px solid rgba(0,0,0,0.05);">
                    <div class="d-flex align-items-center gap-3 mb-4 border-bottom pb-3">
                        <div class="bg-primary bg-gradient text-white rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 45px; height: 45px;"><i class="bi bi-person-add fs-4"></i></div>
                        <h5 class="fw-bold text-dark mb-0 fs-5">Volcar Paciente a Sala de Espera (Consola)</h5>
                    </div>

                    <form action="{{ route('console.assignments.store') }}" method="POST">
                        @csrf
                        <input type="hidden" name="patient_id" value="{{ $patient->id }}">
                        
                        <div class="row g-4 mb-4">
                            <div class="col-md-6">
                                <label class="fw-bold text-dark mb-2">Motivo / Práctica (Tipo)</label>
                                <select name="event_type" class="form-select form-select-lg bg-light border-0 shadow-none text-dark" required>
                                    <option value="" disabled selected>Seleccione el flujo...</option>
                                    <option value="Ingreso (espera)">Ingreso (espera)</option>
                                    <option value="Dilatación">Dilatación</option>
                                    <option value="Estudios Visuales">Estudios Visuales</option>
                                    <option value="Atención Médica">Atención Médica</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="fw-bold text-dark mb-2">Médico Destino <span class="text-muted small fw-normal">(Opcional)</span></label>
                                <select name="doctor_id" class="form-select form-select-lg bg-light border-0 shadow-none text-dark">
                                    <option value="">Cualquier médico disponible / Staff</option>
                                    @foreach($doctors as $doc)
                                        <option value="{{ $doc->id }}">Dr. {{ $doc->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="mb-4 text-start">
                            <label class="fw-bold text-dark mb-2">Observaciones Internas <span class="text-muted small fw-normal">(Visibles en la Consola Global)</span></label>
                            <textarea name="notes" rows="2" class="form-control bg-light border-0 shadow-none text-dark" placeholder="Detalles extra como dilatación, paciente particular, urgencias..."></textarea>
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary rounded-pill px-5 shadow-sm fw-bold d-flex align-items-center gap-2 py-2" style="background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%); font-size: 1.05rem;">
                                Ingresar a Cola <i class="bi bi-send-fill text-white shadow-sm ms-2"></i>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Resumen Diario (En Curso vs Finalizados) -->
                @php
                    $todayAssignments = collect($patient->assignments ?? [])->where('started_at', '>=', \Carbon\Carbon::today())->sortBy('started_at');
                    $enCurso = $todayAssignments->where('status', 'in_progress');
                    $finalizados = $todayAssignments->where('status', 'completed');
                @endphp

                <div class="row g-4 pb-4">
                    <!-- EN CURSO -->
                    <div class="col-lg-6">
                        <div class="card border-0 rounded-4 overflow-hidden h-100 shadow-sm" style="border: 1px solid rgba(32, 201, 151, 0.2) !important;">
                            <div class="card-header border-0 fw-bold d-flex align-items-center gap-2" style="background-color: #20c997 !important; color: #fff; padding: 12px 20px;">
                                <i class="bi bi-play-fill fs-5"></i> En curso
                            </div>
                            <div class="card-body p-0 bg-white">
                                @if($enCurso->count() > 0)
                                    <div class="table-responsive">
                                        <table class="table mb-0 align-middle">
                                            <thead class="text-muted" style="font-size: 0.8rem; background: #fff !important; border-bottom: 2px solid rgba(0,0,0,0.05);">
                                                <tr>
                                                    <th class="ps-4 fw-bold border-0">Inicio</th>
                                                    <th class="fw-bold border-0">Evento</th>
                                                    <th class="fw-bold border-0 text-center">Estado/Médico</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($enCurso as $asg)
                                                <tr>
                                                    <td class="ps-4 fw-medium text-dark">{{ $asg->started_at->format('H:i') }}</td>
                                                    <td class="fw-bold text-dark">{{ $asg->event_type }}</td>
                                                    <td class="text-center"><span class="badge bg-light text-dark border">{{ $asg->doctor->name ?? 'En Espera' }}</span></td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="text-center py-5 text-muted small bg-light bg-opacity-50">
                                        Sin eventos en curso
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- FINALIZADOS HOY -->
                    <div class="col-lg-6">
                        <div class="card border-0 rounded-4 overflow-hidden h-100 shadow-sm" style="border: 1px solid rgba(0, 0, 0, 0.05) !important;">
                            <div class="card-header text-dark border-bottom fw-bold d-flex align-items-center gap-2" style="background-color: #f8f9fa; padding: 12px 20px;">
                                <i class="bi bi-check-circle text-secondary fs-5"></i> Finalizados hoy
                            </div>
                            <div class="card-body p-0 bg-white">
                                @if($finalizados->count() > 0)
                                    <div class="table-responsive">
                                        <table class="table mb-0 align-middle">
                                            <thead class="text-muted" style="font-size: 0.8rem; background: #fff !important; border-bottom: 2px solid rgba(0,0,0,0.05);">
                                                <tr>
                                                    <th class="ps-4 fw-bold border-0">Inicio</th>
                                                    <th class="fw-bold border-0">Fin</th>
                                                    <th class="fw-bold border-0">Evento</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($finalizados as $asg)
                                                <tr>
                                                    <td class="ps-4 fw-medium text-muted">{{ $asg->started_at->format('H:i') }}</td>
                                                    <td class="fw-medium text-muted">{{ $asg->ended_at ? $asg->ended_at->format('H:i') : '-' }}</td>
                                                    <td class="fw-bold text-secondary">{{ $asg->event_type }}</td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="text-center py-5 text-muted small bg-light bg-opacity-50">
                                        Sin eventos finalizados
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab: Diagnóstico -->
            <div class="tab-pane fade" id="diagnostico" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom border-light">
                    <h5 class="fw-bold text-dark mb-0"><i class="bi bi-clipboard2-pulse text-primary me-2"></i> Evoluciones y Diagnósticos</h5>
                </div>
                
                @if($patient->visits->count() > 0)
                    <div class="d-flex flex-column gap-3">
                        @foreach($patient->visits as $visit)
                            <div class="card border-0 shadow-sm rounded-4 overflow-hidden" style="border: 1px solid rgba(0,0,0,0.05) !important;">
                                <!-- Header Strip -->
                                <div class="px-4 py-3 d-flex justify-content-between align-items-center bg-light border-bottom border-secondary border-opacity-10">
                                    <div class="d-flex align-items-center gap-3">
                                        <span class="badge {{ $loop->first ? 'bg-primary bg-gradient text-white shadow-sm' : 'bg-secondary bg-opacity-10 text-secondary' }} rounded-pill px-3 py-2 fw-bold" style="font-size: 0.9rem;"># {{ $visit->id }}</span>
                                        <span class="text-muted fw-bold"><i class="bi bi-calendar3 me-1"></i> {{ $visit->created_at->format('d M Y - H:i') }}</span>
                                    </div>
                                    <div class="d-flex align-items-center gap-3">
                                        <span class="fw-bold text-dark"><i class="bi bi-person-fill text-primary opacity-75 me-1"></i> {{ $visit->doctor->name ?? 'Staff' }}</span>
                                        @if($loop->first)
                                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 rounded-pill px-3 py-1"><i class="bi bi-lock-fill me-1"></i> Cerrada</span>
                                        @endif
                                    </div>
                                </div>
                                
                                <!-- Content Area: Compact layout like legacy but Glassmorphism -->
                                <div class="card-body px-4 py-4 bg-white">
                                    <div class="row align-items-start gy-4 position-relative">
                                        <!-- Row 1 Left -->
                                        <div class="col-md-6 d-flex align-items-start gap-3">
                                            <div class="bg-primary bg-opacity-10 text-primary rounded-3 p-2 d-flex align-items-center justify-content-center mt-1" style="width: 32px; height: 32px;"><i class="bi bi-lungs fs-5"></i></div>
                                            <div class="text-dark fw-medium lh-sm" style="font-size: 0.95rem; white-space: pre-line; padding-top: 0.35rem;">{{ $visit->diagnostico ?? '-' }}</div>
                                        </div>
                                        
                                        <!-- Row 1 Right -->
                                        <div class="col-md-6 d-flex align-items-start gap-3 border-md-start ps-md-4">
                                            <div class="bg-info bg-opacity-10 text-info rounded-3 p-2 d-flex align-items-center justify-content-center mt-1" style="width: 32px; height: 32px;"><i class="bi bi-grid-3x3 fs-5"></i></div>
                                            <div class="text-dark fw-medium lh-sm" style="font-size: 0.95rem; white-space: pre-line; padding-top: 0.35rem;">{{ $visit->tratamiento_oftalmologico ?? '-' }}</div>
                                        </div>
                                        
                                        <!-- Row 2 Left -->
                                        <div class="col-md-6 d-flex align-items-start gap-3">
                                            <div class="bg-secondary bg-opacity-10 text-secondary rounded-3 p-2 d-flex align-items-center justify-content-center mt-1" style="width: 32px; height: 32px;"><i class="bi bi-list-ul fs-5"></i></div>
                                            <div class="text-dark fw-medium lh-sm" style="font-size: 0.95rem; line-height: 1.4; padding-top: 0.35rem;">-</div>
                                        </div>
                                        
                                        <!-- Row 2 Right -->
                                        <div class="col-md-5 d-flex align-items-start gap-3 border-md-start ps-md-4">
                                            <div class="bg-warning bg-opacity-10 text-warning rounded-3 p-2 d-flex align-items-center justify-content-center mt-1" style="width: 32px; height: 32px;"><i class="bi bi-file-earmark-text fs-5"></i></div>
                                            <div class="text-dark fw-medium lh-sm w-100" style="font-size: 0.95rem; white-space: pre-line; padding-top: 0.35rem;">{{ $visit->motivo_consulta ?? '-' }}</div>
                                        </div>

                                        <!-- Actions -->
                                        <div class="col-md-1 position-absolute bottom-0 end-0 d-flex justify-content-end align-items-end pe-4 pb-2">
                                            <button class="btn btn-sm btn-light text-danger rounded-circle p-2 shadow-sm border border-secondary border-opacity-10 me-2" title="Eliminar"><i class="bi bi-trash text-danger"></i></button>
                                            <button class="btn btn-sm btn-light text-secondary rounded-circle p-2 shadow-sm border border-secondary border-opacity-10" title="Registro Bloqueado"><i class="bi bi-lock-fill"></i></button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="py-5 text-center bg-light rounded-4" style="border: 2px dashed #dee2e6;">
                        <i class="bi bi-clipboard2-x fs-1 text-danger opacity-50 mb-3 d-block"></i>
                        <h5 class="fw-bold">Sin diagnósticos</h5>
                        <p class="text-muted mb-0">No se encontraron visitas registradas en el sistema.</p>
                    </div>
                @endif
            </div>

            <!-- Tab: Estudios -->
            <div class="tab-pane fade" id="estudios" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold text-dark mb-0"><i class="bi bi-images text-primary me-2"></i> Archivo Clínico & Estudios</h5>
                    <div class="d-flex gap-3 align-items-center">
                        <select id="sortStudiesSelect" class="form-select border-0 bg-light fw-bold text-secondary shadow-none rounded-pill px-3" style="width: auto;">
                            <option value="desc">Más recientes</option>
                            <option value="asc">Más antiguos</option>
                        </select>
                        <button class="btn btn-gradient-primary rounded-pill px-4 d-flex align-items-center gap-2 text-white" data-bs-toggle="modal" data-bs-target="#uploadStudyModal">
                            <i class="bi bi-cloud-arrow-up"></i> Cargar Estudio
                        </button>
                    </div>
                </div>

                <div class="row g-4" id="studiesGrid">
                    @forelse($patient->studies as $study)
                        <div class="col-md-6 col-xl-4 study-card-item" data-date="{{ $study->created_at->timestamp }}">
                            <div class="card bg-white border-0 shadow-sm rounded-4 h-100 overflow-hidden" style="border: 1px solid rgba(0,0,0,0.05) !important;">
                                @if(in_array(strtolower($study->file_type), ['jpg', 'jpeg', 'png', 'webp']))
                                    <div style="height: 180px; background-image: url('{{ Storage::url($study->file_path) }}'); background-size: cover; background-position: center;" class="border-bottom"></div>
                                @else
                                    <div style="height: 180px;" class="bg-light d-flex align-items-center justify-content-center border-bottom text-muted">
                                        <i class="bi bi-file-earmark-pdf-fill" style="font-size: 5rem; color: #dc3545;"></i>
                                    </div>
                                @endif
                                
                                <div class="card-body p-4">
                                    <h6 class="fw-bold text-dark text-truncate mb-2" title="{{ $study->original_name }}">{{ $study->original_name }}</h6>
                                    <span class="badge bg-primary bg-opacity-10 text-primary py-1 px-3 rounded-pill border border-primary border-opacity-25 mb-3">{{ $study->study_type ?? 'Documento General' }}</span>
                                    
                                    <p class="small text-muted mb-4" style="font-size: 0.85rem;">
                                        Subido el {{ $study->created_at->format('d/m/Y') }}
                                        @if($study->notes) <br><br><i>"{{ Str::limit($study->notes, 60) }}"</i> @endif
                                    </p>
                                    
                                    <div class="d-flex justify-content-between pt-3 border-top border-light">
                                        <a href="{{ Storage::url($study->file_path) }}" target="_blank" class="btn btn-sm btn-light text-primary rounded-pill px-3 fw-bold"><i class="bi bi-eye"></i> Ver</a>
                                        <form action="{{ route('studies.destroy', $study) }}" method="POST">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-light text-danger rounded-pill px-3 fw-bold" onclick="return confirm('¿Borrar este estudio permanentemente?');"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12 py-5 text-center bg-light rounded-4" style="border: 2px dashed #dee2e6;">
                            <div class="bg-white rounded-circle shadow-sm d-inline-flex justify-content-center align-items-center mb-3" style="width: 80px; height: 80px;">
                                <i class="bi bi-folder-x fs-1 text-muted opacity-50"></i>
                            </div>
                            <h5 class="fw-bold text-dark">No hay estudios en ficha</h5>
                            <p class="text-muted">Utiliza el botón superior para incorporar OCTs o PDFs.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Tab: Comentarios -->
            <div class="tab-pane fade" id="comentarios" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom border-light">
                    <h5 class="fw-bold text-dark mb-0"><i class="bi bi-chat-left-dots text-primary me-2"></i> Libreta de Comentarios</h5>
                </div>
                
                <form action="#" method="POST" class="mb-5 bg-light p-3 rounded-4" style="border: 1px solid rgba(0,0,0,0.05);">
                    @csrf
                    <div class="d-flex gap-3">
                        <textarea class="form-control border-0 shadow-sm" rows="2" placeholder="Escribir un nuevo comentario publico o privado sobre el paciente..."></textarea>
                        <button class="btn btn-primary rounded-3 px-4 fw-bold shadow-sm d-flex flex-column justify-content-center align-items-center" type="button"><i class="bi bi-send-fill mb-1"></i> Enviar</button>
                    </div>
                </form>

                @if($patient->comments && $patient->comments->count() > 0)
                    <div class="d-flex flex-column gap-3">
                        @foreach($patient->comments as $comment)
                            <div class="bg-white p-3 rounded-4 shadow-sm border-start border-4 border-primary" style="border: 1px solid rgba(0,0,0,0.05);">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="fw-bold text-dark d-flex align-items-center gap-2"><div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width:24px;height:24px;font-size:10px;"><i class="bi bi-person-fill"></i></div> {{ $comment->user->name ?? 'Sistema' }}</span>
                                    <span class="small text-muted">{{ $comment->created_at->format('d/m/Y H:i') }}</span>
                                </div>
                                <p class="mb-0 text-dark" style="white-space: pre-line;">{{ $comment->body }}</p>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="py-5 text-center bg-light rounded-4 mt-4" style="border: 2px dashed #dee2e6;">
                        <i class="bi bi-chat-left-dots fs-1 text-secondary opacity-50 mb-3 d-block"></i>
                        <h5 class="fw-bold text-dark">Aún no hay comentarios</h5>
                        <p class="text-muted">Utilizá la caja superior para dejar asentado una nota sobre el paciente.</p>
                    </div>
                @endif
            </div>

            <!-- Tab: Cirugías -->
            <div class="tab-pane fade" id="cirugias" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
                    <h5 class="fw-bold text-dark mb-0"><i class="bi bi-heart-pulse text-danger me-2"></i> Módulo Quirúrgico Integral</h5>
                </div>
                
                <div class="row g-4 mb-5">
                    <!-- OD Box -->
                    <div class="col-md-6">
                        <div class="card border-0 bg-primary bg-opacity-10 shadow-sm rounded-4 h-100">
                            <div class="card-header bg-transparent border-0 py-3 pt-4 px-4">
                                <h6 class="fw-bold text-primary mb-0"><i class="bi bi-eye-fill me-2 bg-primary text-white p-2 rounded-circle shadow-sm"></i> Quirófano OD</h6>
                            </div>
                            <div class="card-body p-4 pt-2">
                                <form action="{{ route('patient.surgeries.store', $patient) }}" method="POST" class="bg-white p-4 rounded-4 shadow-sm border" style="border-color: rgba(126, 68, 139, 0.1) !important;">
                                    @csrf
                                    <input type="hidden" name="eye" value="OD">
                                    <div class="mb-3">
                                        <label class="form-label text-muted small fw-bold">Fecha de Cirugía <span class="text-danger">*</span></label>
                                        <input type="date" name="surgery_date" class="form-control bg-light border-0 shadow-none rounded-3" required>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label text-muted small fw-bold">Plan / LIO / Notas <span class="text-danger">*</span></label>
                                        <textarea name="notes" rows="4" class="form-control bg-light border-0 shadow-none rounded-3" required placeholder="Procedimiento, lente utilizada..."></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-primary w-100 fw-bold rounded-pill shadow-sm"><i class="bi bi-save2 me-2"></i> Guardar Operación OD</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- OI Box -->
                    <div class="col-md-6">
                        <div class="card border-0 bg-success bg-opacity-10 shadow-sm rounded-4 h-100">
                            <div class="card-header bg-transparent border-0 py-3 pt-4 px-4">
                                <h6 class="fw-bold text-success mb-0"><i class="bi bi-eye-fill me-2 bg-success text-white p-2 rounded-circle shadow-sm"></i> Quirófano OI</h6>
                            </div>
                            <div class="card-body p-4 pt-2">
                                <form action="{{ route('patient.surgeries.store', $patient) }}" method="POST" class="bg-white p-4 rounded-4 shadow-sm border" style="border-color: rgba(25, 135, 84, 0.1) !important;">
                                    @csrf
                                    <input type="hidden" name="eye" value="OI">
                                    <div class="mb-3">
                                        <label class="form-label text-muted small fw-bold">Fecha de Cirugía <span class="text-danger">*</span></label>
                                        <input type="date" name="surgery_date" class="form-control bg-light border-0 shadow-none rounded-3" required>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label text-muted small fw-bold">Plan / LIO / Notas <span class="text-danger">*</span></label>
                                        <textarea name="notes" rows="4" class="form-control bg-light border-0 shadow-none rounded-3" required placeholder="Procedimiento, lente utilizada..."></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-success w-100 fw-bold rounded-pill shadow-sm"><i class="bi bi-save2 me-2"></i> Guardar Operación OI</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <h5 class="fw-bold text-dark mb-4"><i class="bi bi-clock-history text-muted me-2"></i>Historial Quirúrgico</h5>
                @if($patient->surgeries->count() > 0)
                    <div class="timeline position-relative" style="padding-left: 2.5rem;">
                        <div class="position-absolute h-100 border-start border-2 border-primary border-opacity-25" style="left: 0.8rem; top: 0;"></div>
                        @foreach($patient->surgeries as $surg)
                            @php 
                                $color = 'secondary';
                                if($surg->eye == 'OD') $color = 'primary';
                                if($surg->eye == 'OI') $color = 'success';
                            @endphp
                            <div class="position-relative mb-5">
                                <div class="position-absolute bg-{{ $color }} rounded-circle shadow-sm" style="width: 1.4rem; height: 1.4rem; left: -2.4rem; top: 0.2rem; border: 4px solid #fff;"></div>
                                <div class="bg-white p-4 rounded-4 shadow-sm" style="border: 1px solid rgba(0,0,0,0.05);">
                                    <div class="d-flex justify-content-between mb-3 border-bottom pb-3">
                                        <h6 class="fw-bold text-dark mb-0 fs-5 d-flex align-items-center gap-2">Cirugía Completada <span class="badge bg-{{ $color }} rounded-pill px-3">{{ $surg->eye }}</span></h6>
                                        <div class="d-flex align-items-center gap-3">
                                            <span class="text-muted fw-bold bg-light px-3 py-1 rounded-pill"><i class="bi bi-calendar me-1"></i> {{ $surg->surgery_date->format('d/m/Y') }}</span>
                                            <form action="{{ route('surgeries.destroy', $surg) }}" method="POST">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-light text-danger rounded-circle p-2 shadow-sm" onclick="return confirm('¿ELIMINAR registro quirúrgico permanentemente?');" title="Borrar Cirugía"><i class="bi bi-trash"></i></button>
                                            </form>
                                        </div>
                                    </div>
                                    <div class="p-3 bg-light rounded-3 text-dark fs-6" style="white-space: pre-line;">{{ $surg->notes }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="py-5 text-center bg-light rounded-4" style="border: 2px dashed #dee2e6;">
                        <div class="bg-white rounded-circle shadow-sm d-inline-flex justify-content-center align-items-center mb-3" style="width: 80px; height: 80px;">
                            <i class="bi bi-bandaid fs-1 text-muted opacity-50"></i>
                        </div>
                        <h5 class="fw-bold">Sin operaciones documentadas</h5>
                        <p class="text-muted mb-0">Registrá los procedimientos quirúgicos de los ojos arriba.</p>
                    </div>
                @endif
            </div>

            <!-- Tab: Turnos -->
            <div class="tab-pane fade" id="turnos" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom border-light">
                    <h5 class="fw-bold text-dark mb-0"><i class="bi bi-calendar-check text-primary me-2"></i> Historial de Turnos</h5>
                </div>
                
                @php $turnosNormales = $patient->appointments ? $patient->appointments->where('is_overbooked', false) : collect(); @endphp
                
                @if($turnosNormales->count() > 0)
                    <div class="table-responsive bg-white rounded-4 shadow-sm" style="border: 1px solid rgba(0,0,0,0.05);">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-muted small fw-bold text-uppercase">
                                <tr>
                                    <th class="ps-4">Fecha/Hora</th>
                                    <th>Profesional</th>
                                    <th>Motivo</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($turnosNormales as $appt)
                                    <tr>
                                        <td class="ps-4">
                                            <div class="fw-bold text-dark">{{ \Carbon\Carbon::parse($appt->date)->format('d/m/Y') }}</div>
                                            <div class="small text-muted"><i class="bi bi-clock me-1"></i>{{ \Carbon\Carbon::parse($appt->time)->format('H:i') }} hs</div>
                                        </td>
                                        <td>
                                            <span class="d-flex align-items-center gap-2"><div class="bg-primary bg-opacity-10 text-primary p-2 rounded-circle" style="width:28px; height:28px; display:flex; align-items:center; justify-content:center;"><i class="bi bi-person-fill fs-6"></i></div> {{ $appt->doctor->name ?? '-' }}</span>
                                        </td>
                                        <td class="text-secondary">{{ Str::limit($appt->reason ?? '-', 35) }}</td>
                                        <td>
                                            @php
                                                $statusColor = match($appt->status) {
                                                    'completed' => 'success',
                                                    'cancelled' => 'danger',
                                                    'confirmed' => 'primary',
                                                    default => 'warning'
                                                };
                                            @endphp
                                            <span class="badge bg-{{ $statusColor }} bg-opacity-10 text-{{ $statusColor }} border border-{{ $statusColor }} border-opacity-25 rounded-pill px-3">{{ ucfirst($appt->status) }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="py-5 text-center bg-light rounded-4" style="border: 2px dashed #dee2e6;">
                        <i class="bi bi-calendar-x fs-1 text-secondary opacity-50 mb-3 d-block"></i>
                        <h5 class="fw-bold text-dark">Sin turnos normales</h5>
                        <p class="text-muted">El paciente no registra turnos en el sistema.</p>
                    </div>
                @endif
            </div>

            <!-- Tab: Sobreturnos -->
            <div class="tab-pane fade" id="sobreturnos" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom border-light">
                    <h5 class="fw-bold text-dark mb-0"><i class="bi bi-hourglass-split text-danger me-2"></i> Historial de Sobreturnos</h5>
                </div>
                
                @php $sobreTurnos = $patient->appointments ? $patient->appointments->where('is_overbooked', true) : collect(); @endphp
                
                @if($sobreTurnos->count() > 0)
                    <div class="table-responsive bg-white rounded-4 shadow-sm" style="border: 1px solid rgba(0,0,0,0.05);">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-muted small fw-bold text-uppercase">
                                <tr>
                                    <th class="ps-4">Fecha/Hora</th>
                                    <th>Profesional</th>
                                    <th>Motivo</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($sobreTurnos as $appt)
                                    <tr>
                                        <td class="ps-4">
                                            <div class="fw-bold text-dark">{{ \Carbon\Carbon::parse($appt->date)->format('d/m/Y') }}</div>
                                            <div class="small text-danger fw-bold"><i class="bi bi-clock-fill me-1"></i>{{ \Carbon\Carbon::parse($appt->time)->format('H:i') }} hs</div>
                                        </td>
                                        <td>
                                            <span class="d-flex align-items-center gap-2"><div class="bg-danger bg-opacity-10 text-danger p-2 rounded-circle" style="width:28px; height:28px; display:flex; align-items:center; justify-content:center;"><i class="bi bi-person-fill fs-6"></i></div> {{ $appt->doctor->name ?? '-' }}</span>
                                        </td>
                                        <td class="text-secondary">{{ Str::limit($appt->reason ?? 'Sobreturno Forzado', 35) }}</td>
                                        <td>
                                            @php
                                                $statusColor = match($appt->status) {
                                                    'completed' => 'success',
                                                    'cancelled' => 'danger',
                                                    'confirmed' => 'primary',
                                                    default => 'warning'
                                                };
                                            @endphp
                                            <span class="badge bg-{{ $statusColor }} bg-opacity-10 text-{{ $statusColor }} border border-{{ $statusColor }} border-opacity-25 rounded-pill px-3">{{ ucfirst($appt->status) }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="py-5 text-center bg-light rounded-4" style="border: 2px dashed #dee2e6;">
                        <i class="bi bi-shield-check fs-1 text-secondary opacity-50 mb-3 d-block"></i>
                        <h5 class="fw-bold text-dark">Sin sobreturnos forzados</h5>
                        <p class="text-muted">El paciente no registra citas dadas sobre agenda colapsada.</p>
                    </div>
                @endif
            </div>

            <!-- Tab: Historia Clínica -->
            <div class="tab-pane fade" id="historial" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom border-light">
                    <h5 class="fw-bold text-dark mb-0"><i class="bi bi-journal-medical text-primary me-2"></i> Historia Clínica</h5>
                    <div class="d-flex gap-2">
                        @if($patient->visits->count() > 0)
                        <a href="{{ route('patients.history.print', $patient) }}" target="_blank" class="btn btn-dark rounded-pill px-3 shadow-sm text-white d-flex align-items-center gap-2 fw-medium" title="Vista para Imprimir / Pantalla Completa" style="font-size: 0.85rem;">
                            <i class="bi bi-printer"></i> Imprimir Full
                        </a>
                        <button class="btn btn-outline-secondary rounded-pill px-3 fw-bold d-flex align-items-center gap-2 transition-all shadow-sm" style="font-size: 0.85rem;" onclick="toggleHistorialOrder()" id="btnOrderHistorial">
                            <i class="bi bi-sort-down fs-5"></i> <span id="textOrderHistorial">Más recientes primero</span>
                        </button>
                        @endif
                        <a href="{{ route('patient.visits.create', $patient) }}" class="btn btn-gradient-primary rounded-pill px-4 shadow-sm text-white d-flex align-items-center gap-2">
                            <i class="bi bi-plus-lg"></i> Cargar Nueva Evolución
                        </a>
                    </div>
                </div>

                @if($patient->visits->count() > 0)
                    <!-- UI Tipo Documento (Legacy style + Modern) -->
                    <div class="bg-white rounded-4 shadow-sm p-4 p-md-5 mx-0" style="border: 1px solid rgba(0,0,0,0.05);">
                        <!-- Cabecera de Documento -->
                        <div class="row w-100 mb-5 pb-4" style="border-bottom: 2px solid rgba(0,0,0,0.05);">
                            <div class="col-8">
                                <h4 class="fw-bold text-dark mb-1 d-flex align-items-center"><i class="bi bi-suit-spade text-primary opacity-50 me-2 fs-3 d-none"></i>Paciente: {{ strtolower($patient->first_name . ' ' . $patient->last_name) }}</h4>
                                <p class="text-muted mb-0 fw-bold fs-6">D.N.I.: {{ $patient->dni }}</p>
                            </div>
                            <div class="col-4 border-start ps-4">
                                @if($patient->medical_notes)
                                    <h6 class="fw-bold text-dark mb-1">Antecedentes:</h6>
                                    <p class="text-secondary small mb-0" style="white-space: pre-line; line-height: 1.4;">{{ $patient->medical_notes }}</p>
                                @endif
                            </div>
                        </div>

                        <!-- Contenedor Flex para ordenar -->
                        <div id="visitasListContainer" class="d-flex flex-column gap-5">
                            @foreach($patient->visits as $visit)
                            <div class="visit-item">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <h6 class="fw-bold text-dark mb-0" style="font-size: 1.05rem;"><i class="bi bi-record-circle text-primary me-2"></i> Visita: {{ $visit->created_at->format('d-M-Y') }}</h6>
                                    <h6 class="text-secondary mb-0 fw-bold" style="font-size: 1.05rem;">Médico: {{ $visit->doctor->name ?? 'Staff' }}</h6>
                                </div>
                                <hr class="my-3 border-secondary opacity-10" style="border-width: 2px;">
                                
                                <div class="px-2" style="font-size: 0.95rem;">
                                    @if($visit->motivo_consulta)
                                        <div class="row align-items-start mb-3">
                                            <div class="col-3 col-md-2 text-dark font-monospace fw-bold" style="letter-spacing:-0.5px;">Motivo:</div>
                                            <div class="col-9 col-md-10 text-secondary" style="white-space: pre-line; padding-top:2px;">{{ $visit->motivo_consulta }}</div>
                                        </div>
                                    @endif

                                    @if($visit->diagnostico)
                                        <div class="row align-items-start mb-3">
                                            <div class="col-3 col-md-2 text-dark font-monospace fw-bold" style="letter-spacing:-0.5px;">Diagnóstico:</div>
                                            <div class="col-9 col-md-10 text-secondary" style="white-space: pre-line; padding-top:2px;">{{ $visit->diagnostico }}</div>
                                        </div>
                                    @endif

                                    @if($visit->tratamiento_oftalmologico || $visit->av_od_lejos || $visit->av_oi_lejos || $visit->av_od_cerca || $visit->av_oi_cerca)
                                        <div class="row align-items-start mb-3">
                                            <div class="col-3 col-md-2 text-dark font-monospace fw-bold" style="letter-spacing:-0.5px;">Tratamiento:</div>
                                            <div class="col-9 col-md-10 text-secondary">
                                                @if($visit->tratamiento_oftalmologico)
                                                    <div style="white-space: pre-line; margin-bottom: 1.5rem; padding-top:2px;">{{ $visit->tratamiento_oftalmologico }}</div>
                                                @endif
                                                
                                                @if($visit->av_od_lejos || $visit->av_oi_lejos || $visit->av_od_cerca || $visit->av_oi_cerca)
                                                    <div class="row mt-3 mb-1 font-monospace">
                                                        <div class="col-12 col-lg-8 offset-lg-4">
                                                            @if($visit->av_od_lejos)<div class="row mb-1"><div class="col-6 text-end text-dark fw-bold">OD Lejos:</div><div class="col-6">{{ $visit->av_od_lejos }}</div></div>@endif
                                                            @if($visit->av_oi_lejos)<div class="row mb-1"><div class="col-6 text-end text-dark fw-bold">OI Lejos:</div><div class="col-6">{{ $visit->av_oi_lejos }}</div></div>@endif
                                                            @if($visit->av_od_cerca)<div class="row mb-1"><div class="col-6 text-end text-dark fw-bold">OD Cerca:</div><div class="col-6">{{ $visit->av_od_cerca }}</div></div>@endif
                                                            @if($visit->av_oi_cerca)<div class="row mb-1"><div class="col-6 text-end text-dark fw-bold">OI Cerca:</div><div class="col-6">{{ $visit->av_oi_cerca }}</div></div>@endif
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endif

                                    @if($visit->pio || $visit->bmc || $visit->obi || $visit->otros_examen || $visit->antecedentes_oftalmologicos)
                                        <div class="row align-items-start mb-2 mt-4 pt-3 border-top border-light">
                                            <div class="col-3 col-md-2 text-dark font-monospace fw-bold" style="letter-spacing:-0.5px;">Examen Gral:</div>
                                            <div class="col-9 col-md-10 text-secondary" style="padding-top:2px;">
                                                @if($visit->antecedentes_oftalmologicos)<p class="mb-1"><span class="fw-bold text-dark">Ant. Oftalm.:</span> {{ $visit->antecedentes_oftalmologicos }}</p>@endif
                                                @if($visit->pio)<p class="mb-1"><span class="fw-bold text-dark">PIO:</span> {{ $visit->pio }}</p>@endif
                                                @if($visit->bmc)<p class="mb-1"><span class="fw-bold text-dark">BMC:</span> {{ $visit->bmc }}</p>@endif
                                                @if($visit->obi)<p class="mb-1"><span class="fw-bold text-dark">OBI:</span> {{ $visit->obi }}</p>@endif
                                                @if($visit->otros_examen)<p class="mb-1"><span class="fw-bold text-dark">Otros:</span> <span style="white-space: pre-line;">{{ $visit->otros_examen }}</span></p>@endif
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="py-5 text-center bg-light rounded-4" style="border: 2px dashed #dee2e6;">
                        <div class="bg-white rounded-circle shadow-sm d-inline-flex justify-content-center align-items-center mb-3" style="width: 80px; height: 80px;">
                            <i class="bi bi-journal-x fs-1 text-primary opacity-50"></i>
                        </div>
                        <h5 class="fw-bold text-dark">La Ficha Clínica está vacía</h5>
                        <p class="text-muted mb-0">Haz click en "Cargar Nueva Evolución" para comenzar la atención oftalmológica.</p>
                    </div>
                @endif
            </div>

        </div> <!-- Fin Bottom Tabs Content -->

    </div>
</div>

<!-- Modal para subir estudios -->
<div class="modal fade" id="uploadStudyModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 rounded-4 shadow-lg">
      <div class="modal-header bg-light border-0 py-3 px-4 rounded-top-4">
        <h5 class="modal-title fw-bold text-primary"><i class="bi bi-cloud-arrow-up me-2"></i> Adjuntar Archivo Clínico</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="{{ route('patient.studies.store', $patient) }}" method="POST" enctype="multipart/form-data">
          @csrf
          <div class="modal-body p-4">
              <div class="mb-4">
                  <label class="form-label fw-bold text-dark">Explorar Archivo (JPG, PNG, PDF) <span class="text-danger">*</span></label>
                  <input class="form-control form-control-lg bg-light border-0 shadow-none rounded-3" type="file" name="study_file" required accept=".jpg,.jpeg,.png,.webp,.pdf">
              </div>
              <div class="mb-4">
                  <label class="form-label fw-bold text-dark">Clasificación del Estudio</label>
                  <input class="form-control bg-light border-0 shadow-none rounded-3" type="text" name="study_type" placeholder="Ej: Tomografía, OCT, Examen Prequirúrgico...">
              </div>
              <div class="mb-2">
                  <label class="form-label fw-bold text-dark">Notas / Observaciones</label>
                  <textarea class="form-control bg-light border-0 shadow-none rounded-3" name="notes" rows="3" placeholder="Comentarios anexos sobre el estudio..."></textarea>
              </div>
          </div>
          <div class="modal-footer border-top-0 pt-0 pb-4 px-4">
            <button type="button" class="btn btn-light fw-bold px-4 rounded-pill" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-gradient-primary text-white fw-bold px-4 rounded-pill d-flex align-items-center gap-2"><i class="bi bi-upload"></i> Subir a Expediente</button>
          </div>
      </form>
    </div>
  </div>
</div>

<script>
// Función para activar programáticamente las solapas (tabs)
function activateBottomTab(tabId) {
    const triggerEl = document.querySelector('#' + tabId);
    if (triggerEl) {
        const tab = new bootstrap.Tab(triggerEl);
        tab.show();
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Alarma para la redirección desde otros lugares si viene un tab
    @if(session('active_tab'))
        var triggerEl = document.querySelector('#{{ session("active_tab") }}-tab');
        if (triggerEl) {
            var tab = new bootstrap.Tab(triggerEl);
            tab.show();
        }
    @endif

    // Ordenamiento dinámico de los estudios
    const sortSelect = document.getElementById('sortStudiesSelect');
    const grid = document.getElementById('studiesGrid');
    
    if (sortSelect && grid) {
        sortSelect.addEventListener('change', function() {
            const items = Array.from(grid.querySelectorAll('.study-card-item'));
            if (items.length === 0) return;
            const direction = this.value;
            items.sort((a, b) => {
                const dateA = parseInt(a.getAttribute('data-date'));
                const dateB = parseInt(b.getAttribute('data-date'));
                return direction === 'asc' ? dateA - dateB : dateB - dateA;
            });
            items.forEach(item => grid.appendChild(item));
        });
    }
});

// Sorting Documento Clínico JS
function toggleHistorialOrder() {
    const container = document.getElementById('visitasListContainer');
    const btnText = document.getElementById('textOrderHistorial');
    const btnIcon = document.querySelector('#btnOrderHistorial i');
    if (!container) return;

    if (container.classList.contains('flex-column')) {
        // Switch to Descending View (Ascending Temporal = Viejas arriba, Nuevas abajo)
        container.classList.remove('flex-column');
        container.classList.add('flex-column-reverse');
        btnText.innerText = "Más antiguas primero";
        btnIcon.className = "bi bi-sort-up fs-5";
    } else {
        // Switch to Default View (Nuevas arriba)
        container.classList.remove('flex-column-reverse');
        container.classList.add('flex-column');
        btnText.innerText = "Más recientes primero";
        btnIcon.className = "bi bi-sort-down fs-5";
    }
}

// ----- Patient Photo Upload Logic -----
document.getElementById('patientPhotoInput').addEventListener('change', function() {
    if (!this.files || !this.files[0]) return;
    
    let file = this.files[0];
    
    // Show loading UI on overlay
    const overlay = document.getElementById('avatarOverlay');
    overlay.innerHTML = '<div class="spinner-border text-light spinner-border-sm" role="status"></div>';
    overlay.style.opacity = 1;

    let img = new Image();
    let reader = new FileReader();
    
    reader.onload = function(e) {
        img.src = e.target.result;
    }
    
    reader.onerror = function() {
        alert('Error al leer el archivo local.');
        resetOverlay();
    }
    
    img.onload = function() {
        try {
            let canvas = document.createElement('canvas');
            let ctx = canvas.getContext('2d');
            
            // Max dimensions
            const MAX_WIDTH = 800;
            const MAX_HEIGHT = 800;
            let width = img.width;
            let height = img.height;
            
            if (width > height) {
                if (width > MAX_WIDTH) {
                    height *= MAX_WIDTH / width;
                    width = MAX_WIDTH;
                }
            } else {
                if (height > MAX_HEIGHT) {
                    width *= MAX_HEIGHT / height;
                    height = MAX_HEIGHT;
                }
            }
            
            canvas.width = width;
            canvas.height = height;
            ctx.drawImage(img, 0, 0, width, height);
            
            canvas.toBlob(function(blob) {
                if (!blob) {
                    alert('Error: No se pudo comprimir la imagen (blob nulo).');
                    resetOverlay();
                    return;
                }

                let formData = new FormData();
                formData.append('photo', blob, 'photo.jpg');
                
                fetch('{{ route("patients.uploadPhoto", $patient) }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                })
                .then(response => {
                    if(!response.ok) {
                        return response.json().then(err => { throw err; }).catch(e => {
                            throw new Error('Error HTTP: ' + response.status);
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    if(data.success) {
                        // Cache bust the image URL to force reload
                        document.getElementById('patientAvatarImage').src = data.photo_url + '?t=' + new Date().getTime();
                        document.getElementById('patientAvatarImage').style.display = 'block';
                        document.getElementById('patientAvatarIcon').style.display = 'none';
                        // Opcional: mostrar un mini aviso de éxito
                        // alert('¡Foto guardada con éxito!');
                    } else {
                        alert('Error al guardar: ' + (data.message || 'Intente nuevamente.'));
                    }
                })
                .catch(error => {
                    console.error(error);
                    let errorMsg = 'Error al subir la imagen al servidor.';
                    if(error && error.errors && error.errors.photo) {
                        errorMsg = error.errors.photo[0];
                    } else if (error.message) {
                        errorMsg = error.message;
                    }
                    alert(errorMsg);
                })
                .finally(() => {
                    resetOverlay();
                });
            }, 'image/jpeg', 0.8);
        } catch (e) {
            console.error(e);
            alert('Error procesando la imagen en el navegador.');
            resetOverlay();
        }
    };
    
    img.onerror = function() {
        alert('El formato de imagen seleccionado no es soportado o está corrupto (ej: HEIC). Por favor, usa JPG o PNG.');
        resetOverlay();
    };
    
    function resetOverlay() {
        overlay.innerHTML = '<i class="bi bi-camera"></i>';
        overlay.style.opacity = 0;
        document.getElementById('patientPhotoInput').value = '';
    }

    reader.readAsDataURL(file);
});
</script>
@endsection
