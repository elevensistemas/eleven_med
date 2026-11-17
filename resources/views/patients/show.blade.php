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
    background: linear-gradient(180deg, #f8f9fc 0%, #ffffff 100%);
    border-right: 1px solid rgba(0,0,0,0.04);
}
.sidebar-link {
    background-color: #ffffff;
    border: 1px solid rgba(0,0,0,0.05) !important;
    box-shadow: 0 2px 4px rgba(0,0,0,0.02);
    color: #555c6e;
    font-size: 0.88rem;
    font-weight: 600;
    padding: 12px 16px;
    border-radius: 0.8rem;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    transition: all 0.3s ease;
    text-decoration: none;
    white-space: normal;
    line-height: 1.2;
}
.sidebar-link i {
    width: 22px;
    font-size: 1.1rem;
    margin-right: 8px;
    text-align: center;
    transition: transform 0.2s ease;
}
.sidebar-link:hover, .sidebar-link.active {
    background-color: rgba(94, 106, 210, 0.08); /* Vibrant Primary */
    color: #5e6ad2;
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
    background: linear-gradient(135deg, #5e6ad2 0%, #7e448b 100%);
    border: none;
    transition: transform 0.2s, box-shadow 0.2s;
    color: white !important;
}
.btn-gradient-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 0.5rem 1rem rgba(94, 106, 210, 0.3);
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
    background-color: rgba(94, 106, 210, 0.05);
    color: #5e6ad2;
}
.modern-tabs .nav-link.active {
    background: linear-gradient(135deg, #5e6ad2 0%, #7e448b 100%);
    color: #ffffff !important;
    box-shadow: 0 4px 10px rgba(94, 106, 210, 0.25);
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

/* Sidebar Nav Container */
.sidebar-nav-container {
    background-color: rgba(255, 255, 255, 0.6);
    border: 1px solid rgba(94, 106, 210, 0.2);
    border-radius: 1rem;
    padding: 12px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.02);
}

/* Dark Mode Styles for Patient Profile */
body.theme-dark .patient-container {
    background-color: transparent !important;
    box-shadow: none !important;
}
body.theme-dark .patient-inner-sidebar {
    background: linear-gradient(180deg, #1e3c72 0%, #0b1121 100%) !important;
    border-right: 1px solid rgba(255, 255, 255, 0.05) !important;
}
body.theme-dark .sidebar-link {
    background-color: rgba(255, 255, 255, 0.05) !important;
    border-color: rgba(255, 255, 255, 0.1) !important;
    color: #a1ecff !important;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2) !important;
}
body.theme-dark .sidebar-link:hover, body.theme-dark .sidebar-link.active {
    background-color: rgba(94, 106, 210, 0.3) !important;
    color: #ffffff !important;
}
body.theme-dark .antecedentes-box {
    background-color: rgba(255, 255, 255, 0.05) !important;
    border-color: rgba(255, 255, 255, 0.1) !important;
    color: #e0e0e0 !important;
}
body.theme-dark .modern-tabs .nav-link {
    color: #a1ecff !important;
}
body.theme-dark .modern-tabs .nav-link:hover {
    background-color: rgba(94, 106, 210, 0.2) !important;
}
body.theme-dark .modern-tabs .nav-link.active {
    background: linear-gradient(135deg, #5e6ad2 0%, #7e448b 100%) !important;
    color: #ffffff !important;
}
body.theme-dark .data-card {
    background: #111827 !important;
    border-color: rgba(255, 255, 255, 0.08) !important;
}
body.theme-dark .value-badge {
    background-color: rgba(255, 255, 255, 0.05) !important;
    border-color: rgba(255, 255, 255, 0.1) !important;
    color: #a1ecff !important;
}
body.theme-dark .sidebar-nav-container {
    background-color: rgba(0, 0, 0, 0.2) !important;
    border-color: rgba(255, 255, 255, 0.05) !important;
}
</style>

<div class="row g-0 patient-container">
    <!-- Inner Sidebar (Izquierda) -->
    <div class="col-md-4 col-lg-3 col-xl-2 patient-inner-sidebar p-3 d-flex flex-column">
        <div class="d-flex flex-column align-items-center text-center mb-4 pb-2 border-bottom border-light">
            <div class="icon-box rounded-circle shadow-sm text-white d-flex align-items-center justify-content-center mb-3" id="patientAvatarBox" style="width: 70px; height: 70px; font-size: 2rem; cursor: pointer; overflow: hidden; position: relative; background: linear-gradient(135deg, #5e6ad2 0%, #7e448b 100%);" data-bs-toggle="modal" data-bs-target="#photoOptionsModal" onmouseover="document.getElementById('avatarOverlay').style.opacity=1" onmouseout="document.getElementById('avatarOverlay').style.opacity=0">
                @if($patient->photo_path)
                    @if(Str::startsWith($patient->photo_path, 'uploads/'))
                        <img src="{{ asset($patient->photo_path) }}" alt="Foto" style="width: 100%; height: 100%; object-fit: cover;" id="patientAvatarImage">
                    @else
                        <img src="{{ asset('storage/' . $patient->photo_path) }}" alt="Foto" style="width: 100%; height: 100%; object-fit: cover;" id="patientAvatarImage">
                    @endif
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
            @if($patient->photo_path)
            <form action="{{ route('patients.removePhoto', $patient) }}" method="POST" class="mb-2">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm btn-link text-danger text-decoration-none p-0" style="font-size: 0.8rem;"><i class="bi bi-trash me-1"></i>Quitar foto</button>
            </form>
            @endif
            <h5 class="fw-bold mb-2 text-dark" style="font-size: 1.1rem; line-height: 1.3; letter-spacing: -0.3px;">{{ $patient->first_name }} {{ $patient->last_name }}</h5>
            <span class="badge bg-white text-secondary border px-3 py-2 rounded-pill shadow-sm mb-1"><i class="bi bi-card-text me-1"></i> D.N.I: {{ $patient->dni }}</span>
            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-3 py-1 rounded-pill fw-bold mb-2" style="font-size: 0.85rem;" title="Número de Carpeta correlativo institucional"><i class="bi bi-folder2-open me-1"></i> Carpeta #{{ $patient->code ?: sprintf("%06d", $patient->id) }}</span>
        </div>

        <div class="mb-4">
            <div class="d-flex justify-content-between align-items-center ms-1 mb-1">
                <span class="text-uppercase text-primary fw-bold" style="letter-spacing: 0.4px; font-size: 0.75rem;"><i class="bi bi-journal-text me-1"></i>Comentarios Médicos</span>
                <small id="saveMedicalNotesFeedback" class="text-muted fst-italic" style="font-size: 0.68rem;">Solo consultorio</small>
            </div>
            <textarea id="sidebarMedicalNotesInput" rows="2" class="form-control bg-white border shadow-sm rounded-3 auto-expand mb-1" style="font-size: 0.82rem; min-height: 54px; resize: none; overflow-y: hidden;" placeholder="Comentarios médicos internos (no salen impresos)...">{{ $patient->medical_notes }}</textarea>
            <button type="button" onclick="saveMedicalNotesQuick({{ $patient->id }})" class="btn btn-sm btn-outline-primary rounded-pill w-100 py-0.5 fw-bold" style="font-size: 0.74rem;">
                <i class="bi bi-check2 me-1"></i> Guardar Comentario
            </button>
        </div>

        <nav class="nav flex-column gap-1 flex-grow-1 sidebar-nav-container">
            <a href="{{ route('patients.index') }}" class="sidebar-link"><i class="bi bi-arrow-left"></i> Volver a Lista</a>
            <a href="{{ route('patient.visits.create', $patient) }}" class="sidebar-link"><i class="bi bi-person-plus"></i> Nueva Visita</a>
            <button class="sidebar-link border-0 w-100 text-start bg-transparent" onclick="activateBottomTab('cirugias-tab')"><i class="bi bi-bandaid"></i> Cirugías</button>
            <button class="sidebar-link border-0 w-100 text-start bg-transparent" onclick="activateBottomTab('asignacion-tab')"><i class="bi bi-person-badge"></i> Asignar Médico</button>
            <button class="sidebar-link border-0 w-100 text-start bg-transparent" data-bs-toggle="modal" data-bs-target="#uploadStudyModal"><i class="bi bi-cloud-arrow-up"></i> Subir Estudio</button>
            <a href="{{ route('patients.edit', $patient) }}" class="sidebar-link"><i class="bi bi-pencil"></i> Editar Perfil</a>
            <button class="sidebar-link border-0 w-100 text-start bg-transparent" onclick="activateBottomTab('comentarios-tab')"><i class="bi bi-chat-left-text"></i> Comentarios</button>
            <button class="sidebar-link border-0 w-100 text-start bg-transparent" onclick="activateBottomTab('historial-tab')"><i class="bi bi-journal-medical"></i> Historia Clínica</button>
            <button class="sidebar-link border-0 w-100 text-start bg-transparent" onclick="alert('Módulo de Recetas en construcción.')"><i class="bi bi-file-earmark-medical"></i> Generar Receta</button>
            <button class="sidebar-link border-0 w-100 text-start" style="background: linear-gradient(135deg, rgba(94,106,210,0.1) 0%, rgba(126,68,139,0.1) 100%); color: #7e448b;" data-bs-toggle="modal" data-bs-target="#aiAnalysisModal"><i class="bi bi-stars text-warning"></i> Asistente IA</button>
            
            @if(auth()->user()->hasRole('administrador'))
            <div class="mt-auto pt-4">
                <form action="{{ route('patients.destroy', $patient) }}" method="POST">
                    @csrf @method('DELETE')
                    <button type="submit" class="sidebar-link text-danger border-0 w-100 text-start bg-transparent" onclick="return confirm('¿Está seguro de eliminar este paciente de forma permanente? Esta acción es irreversible y requiere privilegios de administrador.');">
                        <i class="bi bi-trash"></i> Eliminar Paciente
                    </button>
                </form>
            </div>
            @endif
        </nav>
    </div>

    <!-- Contenido Principal (Derecha) -->
    <div class="col-md-8 col-lg-9 col-xl-10 p-3 p-xl-4 bg-white">
        <!-- HEADER TOP -->
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <h5 class="fw-bold text-dark mb-0">{{ $patient->last_name }}, {{ $patient->first_name }}</h5>
                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-3 py-1 rounded-pill fw-bold" style="font-size: 0.85rem;"><i class="bi bi-folder2-open me-1"></i> Carpeta: #{{ $patient->code ?: sprintf("%06d", $patient->id) }}</span>
                <span class="badge bg-light text-secondary border px-3 py-1 rounded-pill fw-bold" style="font-size: 0.85rem;"><i class="bi bi-card-text me-1"></i> DNI: {{ $patient->dni }}</span>
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <!-- Botón El Famoso Ojito para desplegar/scrollear todas las consultas previas arriba -->
                <button type="button" class="btn rounded-pill px-3 py-1 fw-bold shadow-sm d-flex align-items-center gap-2 text-white border-0" style="background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); font-size: 0.88rem;" onclick="toggleFichaOjitoTab()" title="Ver y scrollear todas las consultas previas debajo del encabezado">
                    <i class="bi bi-eye-fill"></i> Consultas Previas ({{ $patient->visits->count() }})
                </button>
                <a href="{{ route('patient.visits.create', $patient) }}" class="btn btn-gradient-primary rounded-pill px-3 py-1 fw-bold shadow-sm d-flex align-items-center gap-1 text-decoration-none" style="font-size: 0.88rem;">
                    <i class="bi bi-plus-lg"></i> Nueva Consulta
                </a>
                <a href="{{ route('agenda.index') }}?preselect_patient_id={{ $patient->id }}&preselect_patient_fname={{ urlencode($patient->first_name) }}&preselect_patient_lname={{ urlencode($patient->last_name) }}&preselect_patient_dni={{ $patient->dni }}" class="btn btn-gradient-warning rounded-pill px-3 py-1 fw-bold shadow-sm d-flex align-items-center gap-2 text-decoration-none" style="font-size: 0.88rem;">
                    <i class="bi bi-calendar-plus"></i> Dar turno
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success bg-success bg-opacity-10 text-success border-0 rounded-4 shadow-sm mb-4 py-2 px-3"><i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}</div>
        @endif
        @if(session('error') && !request()->is('console*') && !session()->has('error_handled_elsewhere'))
            <div class="alert alert-danger bg-danger bg-opacity-10 text-danger border-0 rounded-4 shadow-sm mb-4 py-2 px-3"><i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}</div>
        @endif
        @if(session('info'))
            <div class="alert alert-info bg-info bg-opacity-10 text-info border-0 rounded-4 shadow-sm mb-4 py-2 px-3"><i class="bi bi-info-circle-fill me-2"></i> {{ session('info') }}</div>
        @endif

        @php
            $activeOpenVisit = $patient->visits->where('is_open', true)->first();
        @endphp
        @if($activeOpenVisit)
            <div class="alert alert-success bg-success bg-opacity-10 border-0 rounded-4 shadow-sm mb-4 d-flex justify-content-between align-items-center flex-wrap gap-2 py-3 px-4" style="border-left: 5px solid #198754 !important;">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;"><i class="bi bi-unlock-fill fs-5"></i></div>
                    <div>
                        <h6 class="fw-bold text-success mb-1">Hay una consulta médica abierta para este paciente</h6>
                        <p class="text-muted small mb-0">Iniciada por Dr. {{ $activeOpenVisit->doctor->name ?? 'Staff' }} el {{ $activeOpenVisit->created_at->format('d/m/Y a las H:i') }}.</p>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('visits.edit', $activeOpenVisit) }}" class="btn btn-success rounded-pill px-4 fw-bold shadow-sm text-white border-0 d-flex align-items-center gap-1 btn-sm">
                        <i class="bi bi-pencil-fill"></i> Continuar Consulta
                    </a>
                    <form action="{{ route('visits.toggleStatus', $activeOpenVisit) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger rounded-pill px-4 fw-bold shadow-sm btn-sm" onclick="return confirm('¿Está seguro de cerrar esta consulta?');">
                            <i class="bi bi-lock-fill"></i> Cerrar Consulta
                        </button>
                    </form>
                </div>
            </div>
        @endif

        <!-- Panel Superior (Top Tabs) -->
        <ul class="nav modern-tabs mb-3" id="topTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="informacion-tab" data-bs-toggle="tab" data-bs-target="#informacion" type="button" role="tab"><i class="bi bi-info-circle me-1"></i> Información</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="otros-tab" data-bs-toggle="tab" data-bs-target="#otros" type="button" role="tab"><i class="bi bi-three-dots me-1"></i> Otros Datos</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="ultimavisita-tab" data-bs-toggle="tab" data-bs-target="#ultimavisita" type="button" role="tab"><i class="bi bi-eye-fill me-1"></i> Consultas Previas / HC ({{ $patient->visits->count() }})</button>
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
                            <span class="text-muted fw-bold">Derivó / Recomendó:</span>
                            <span class="fw-bold text-dark d-flex align-items-center gap-1">
                                <span id="displayRecommendationText">{{ $patient->recommendation ?: "Sin especificar" }}</span>
                                <button type="button" class="btn btn-sm btn-link p-0 text-primary ms-1" onclick="openEditRecommendationModal()" title="Editar derivación / recomendación">
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                            </span>
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
            
            <!-- Pestaña: El Ojito - Todas las Consultas Previas y Cirugías Scrolleables debajo del Encabezado -->
            <div class="tab-pane fade" id="ultimavisita" role="tabpanel">
                <div class="card border rounded-4 shadow-sm overflow-hidden" style="border: 1.5px solid rgba(30, 60, 114, 0.25) !important; background-color: #f8fafc;">
                    <div class="card-header bg-white border-bottom py-2 px-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span class="badge rounded-pill px-3 py-1 text-white" style="background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);">
                                <i class="bi bi-eye-fill me-1"></i> Visor Completo de Consultas Previas ({{ $patient->visits->count() }})
                            </span>
                            <small class="text-muted fw-semibold">Scrollee aquí todas las consultas (PIO, anteojos, antecedentes, medicación y cirugías).</small>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <input type="text" class="form-control form-control-sm bg-light border rounded-pill px-3 shadow-none" style="width: 190px; font-size: 0.8rem;" placeholder="Buscar en consultas..." oninput="filterFichaTopHistory(this.value)">
                            <a href="{{ route('patient.visits.create', $patient) }}" class="btn btn-sm btn-primary rounded-pill px-3 fw-bold">
                                <i class="bi bi-plus-lg"></i> Nueva Consulta
                            </a>
                        </div>
                    </div>

                    <div class="card-body p-3" style="max-height: 390px; overflow-y: auto;" id="fichaTopHistoryViewport">
                        @php
                            $fichaTopTimeline = collect();
                            foreach ($patient->visits as $vItem) {
                                $fichaTopTimeline->push([
                                    'type' => 'visit',
                                    'timestamp' => $vItem->created_at ? $vItem->created_at->timestamp : 0,
                                    'item' => $vItem,
                                ]);
                            }
                            if ($patient->surgeries) {
                                foreach ($patient->surgeries as $surg) {
                                    $sTs = $surg->surgery_date
                                        ? \Carbon\Carbon::parse($surg->surgery_date->format('Y-m-d') . ' 12:00:00')->timestamp
                                        : ($surg->created_at ? $surg->created_at->timestamp : 0);
                                    $fichaTopTimeline->push([
                                        'type' => 'surgery',
                                        'timestamp' => $sTs,
                                        'item' => $surg,
                                    ]);
                                }
                            }
                            $fichaTopTimeline = $fichaTopTimeline->sortByDesc('timestamp')->values();
                            $seenLatestVisit = false;
                        @endphp

                        @if($patient->surgeries && $patient->surgeries->count() > 0)
                            <div class="mb-3 p-2 px-3 rounded-3 bg-danger bg-opacity-10 border border-danger border-opacity-25">
                                <div class="fw-bold text-danger small text-uppercase mb-1"><i class="bi bi-bandaid-fill me-1"></i> Resumen de Cirugías ({{ $patient->surgeries->count() }}) — También intercaladas cronológicamente abajo</div>
                                <div class="d-flex flex-wrap gap-2">
                                    @foreach($patient->surgeries->sortByDesc('surgery_date') as $surg)
                                        <span class="badge bg-white text-dark border shadow-sm px-3 py-2 text-wrap text-start" style="font-size: 0.8rem; font-weight: 500;">
                                            <strong class="{{ $surg->eye === 'OD' ? 'text-danger' : 'text-primary' }}">Ojo {{ $surg->eye }}</strong>
                                            ({{ $surg->surgery_date ? $surg->surgery_date->format('d/m/Y') : '-' }}):
                                            <span class="fw-bold">{{ $surg->notes }}</span>
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if($fichaTopTimeline->count() > 0)
                            <div class="d-flex flex-column gap-3" id="fichaTopVisitsList">
                                @foreach($fichaTopTimeline as $entry)
                                    @if($entry['type'] === 'surgery')
                                        @php $surg = $entry['item']; @endphp
                                        <div class="card rounded-3 shadow-sm ficha-top-visit-card"
                                             style="background: linear-gradient(90deg, #fff5f5 0%, #ffffff 100%); border: 2px solid {{ $surg->eye === 'OD' ? '#dc3545' : '#0d6efd' }} !important; border-left-width: 6px !important;">
                                            <div class="card-body py-2 px-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                                    <span class="badge {{ $surg->eye === 'OD' ? 'bg-danger' : 'bg-primary' }} rounded-pill px-3 py-1 fw-bold">
                                                        <i class="bi bi-bandaid-fill me-1"></i> CIRUGÍA OJO {{ $surg->eye }} — {{ $surg->surgery_date ? $surg->surgery_date->format('d/m/Y') : '-' }}
                                                    </span>
                                                    <span class="fw-bold text-dark" style="font-size: 0.9rem;">{{ $surg->notes }}</span>
                                                </div>
                                                @if($surg->doctor)
                                                    <span class="small fw-semibold text-muted"><i class="bi bi-person-badge me-1"></i> Cirujano: Dr. {{ $surg->doctor->name }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    @else
                                        @php
                                            $vItem = $entry['item'];
                                            $isFirstVisitCard = !$seenLatestVisit;
                                            $seenLatestVisit = true;
                                        @endphp
                                        <div class="card border rounded-3 shadow-sm bg-white ficha-top-visit-card">
                                            <div class="card-header bg-light border-bottom py-2 px-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                                    <span class="badge {{ $isFirstVisitCard ? 'bg-primary' : 'bg-secondary' }} rounded-pill px-3 py-1">
                                                        <i class="bi bi-calendar-check me-1"></i> {{ $vItem->created_at->format('d/m/Y - H:i') }}
                                                    </span>
                                                    <span class="small fw-bold text-dark"><i class="bi bi-person-badge text-primary me-1"></i> Dr. {{ $vItem->doctor->name ?? 'Staff' }}</span>
                                                    @if($vItem->is_open)
                                                        <span class="badge bg-success bg-opacity-10 text-success border border-success rounded-pill">Abierta</span>
                                                    @endif
                                                </div>
                                                <div class="d-flex align-items-center gap-2">
                                                    <a href="{{ route('visits.edit', $vItem) }}" class="btn btn-sm btn-outline-primary rounded-pill px-3 py-0.5 fw-bold" style="font-size: 0.76rem;">
                                                        <i class="bi bi-pencil-square me-1"></i> {{ $vItem->is_open ? 'Continuar' : 'Modificar' }}
                                                    </a>
                                                    <form action="{{ route('visits.toggleStatus', $vItem) }}" method="POST" class="d-inline m-0">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-outline-{{ $vItem->is_open ? 'danger' : 'secondary' }} rounded-pill px-2 py-0.5" style="font-size: 0.75rem;" title="{{ $vItem->is_open ? 'Cerrar consulta' : 'Reabrir consulta' }}">
                                                            <i class="bi {{ $vItem->is_open ? 'bi-lock-fill' : 'bi-unlock-fill' }}"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                            <div class="card-body p-3 small">
                                                <div class="row g-2">
                                                    @if($vItem->motivo_consulta)
                                                        <div class="col-md-6"><div class="p-2 rounded bg-light h-100"><strong class="text-primary d-block">Motivo:</strong><div class="text-dark" style="white-space: pre-line;">{{ $vItem->motivo_consulta }}</div></div></div>
                                                    @endif
                                                    @if($vItem->diagnostico)
                                                        <div class="col-md-6"><div class="p-2 rounded bg-light h-100"><strong class="text-danger d-block">Diagnóstico:</strong><div class="text-dark fw-bold" style="white-space: pre-line;">{{ $vItem->diagnostico }}</div></div></div>
                                                    @endif
                                                    @if($vItem->av_od_lejos || $vItem->av_oi_lejos || $vItem->av_od_cerca || $vItem->av_oi_cerca)
                                                        <div class="col-md-6">
                                                            <div class="p-2 rounded border border-primary border-opacity-25 bg-primary bg-opacity-10 h-100">
                                                                <strong class="text-primary d-block mb-1"><i class="bi bi-eyeglasses me-1"></i> Agudeza Visual / Anteojos:</strong>
                                                                <div class="text-dark font-monospace" style="font-size: 0.82rem;">
                                                                    <div><strong>Lejos:</strong> <span class="text-danger fw-bold">OD:</span> {{ $vItem->av_od_lejos ?: '-' }} | <span class="text-primary fw-bold">OI:</span> {{ $vItem->av_oi_lejos ?: '-' }}</div>
                                                                    <div><strong>Cerca:</strong> <span class="text-danger fw-bold">OD:</span> {{ $vItem->av_od_cerca ?: '-' }} | <span class="text-primary fw-bold">OI:</span> {{ $vItem->av_oi_cerca ?: '-' }}</div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endif
                                                    @if($vItem->pio || $vItem->bmc || $vItem->obi || $vItem->otros_examen)
                                                        <div class="col-md-6">
                                                            <div class="p-2 rounded border bg-white h-100">
                                                                <strong class="text-secondary d-block mb-1"><i class="bi bi-activity me-1"></i> PIO / BMC / OBI:</strong>
                                                                <div class="text-dark" style="font-size: 0.83rem;">
                                                                    @if($vItem->pio) <span class="badge bg-warning bg-opacity-25 text-dark border border-warning me-1">PIO: {{ $vItem->pio }}</span> @endif
                                                                    @if($vItem->bmc) <div><strong>BMC:</strong> {{ $vItem->bmc }}</div> @endif
                                                                    @if($vItem->obi) <div><strong>OBI:</strong> {{ $vItem->obi }}</div> @endif
                                                                    @if($vItem->otros_examen) <div><strong>Otros:</strong> {{ $vItem->otros_examen }}</div> @endif
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endif
                                                    @if($vItem->antecedentes_oftalmologicos || $vItem->tratamiento_oftalmologico || $vItem->antecedentes_generales || $vItem->tratamientos_generales)
                                                        <div class="col-12">
                                                            <div class="p-2 rounded bg-light border-start border-4 border-success">
                                                                <div class="row g-2">
                                                                    @if($vItem->antecedentes_oftalmologicos) <div class="col-md-6"><strong>Ant. Oftalmológicos:</strong> <span style="white-space: pre-line;">{{ $vItem->antecedentes_oftalmologicos }}</span></div> @endif
                                                                    @if($vItem->tratamiento_oftalmologico) <div class="col-md-6"><strong class="text-success">Medicación / Trat. Oftalmológico:</strong> <span class="fw-semibold" style="white-space: pre-line;">{{ $vItem->tratamiento_oftalmologico }}</span></div> @endif
                                                                    @if($vItem->antecedentes_generales) <div class="col-md-6"><strong>Ant. Generales:</strong> <span style="white-space: pre-line;">{{ $vItem->antecedentes_generales }}</span></div> @endif
                                                                    @if($vItem->tratamientos_generales) <div class="col-md-6"><strong>Trat. Generales:</strong> <span style="white-space: pre-line;">{{ $vItem->tratamientos_generales }}</span></div> @endif
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-4">
                                <i class="bi bi-info-circle text-primary fs-2 opacity-50 mb-2 d-block"></i>
                                <h6 class="fw-bold text-primary">No hay consultas previas</h6>
                                <p class="text-muted small mb-0">Aún no se ha cargado ninguna evolución clínica para este paciente.</p>
                            </div>
                        @endif
                    </div>
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

                <div class="bg-white rounded-4 shadow-sm p-4 mb-4" style="border: 1px solid rgba(0,0,0,0.05);">
                    <div class="d-flex align-items-center gap-3 mb-4 border-bottom pb-3">
                        <div class="text-white rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 45px; height: 45px; background: linear-gradient(135deg, #5e6ad2 0%, #7e448b 100%);"><i class="bi bi-person-add fs-4"></i></div>
                        <h5 class="fw-bold text-dark mb-0 fs-5">Volcar Paciente a Sala de Espera (Consola)</h5>
                    </div>
                    
                    <form action="{{ route('console.assignments.store') }}" method="POST" id="assignmentForm">
                        @csrf
                        <input type="hidden" name="patient_id" value="{{ $patient->id }}">
                        
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold text-dark mb-0">Pasos del Flujo</h6>
                            <button type="button" class="btn btn-sm btn-outline-primary rounded-pill fw-bold px-3 shadow-sm" onclick="addAssignmentRow()">
                                <i class="bi bi-plus-circle-fill me-1"></i> Agregar Asignación
                            </button>
                        </div>

                        <!-- Header titles -->
                        <div class="row g-2 mb-2 px-2">
                            <div class="col-md-6 text-center text-muted fw-bold small">Tipo</div>
                            <div class="col-md-5 text-center text-muted fw-bold small">Médico</div>
                            <div class="col-md-1"></div>
                        </div>

                        <div id="assignmentRowsContainer" class="d-flex flex-column gap-2 mb-4">
                            <!-- Template Row -->
                            <div class="row g-2 align-items-center assignment-row px-2">
                                <div class="col-md-6">
                                    <select name="event_types[]" class="form-select bg-light border border-secondary border-opacity-10 shadow-none text-dark" required>
                                        <option value="Ingreso (espera)">Ingreso (espera)</option>
                                        <option value="Dilatación">Dilatación</option>
                                        <option value="Estudios Visuales">Estudios Visuales</option>
                                        <option value="Atención Médica">Atención Médica</option>
                                    </select>
                                </div>
                                <div class="col-md-5">
                                    <select name="doctor_ids[]" class="form-select bg-light border border-secondary border-opacity-10 shadow-none text-dark">
                                        <option value="">Cualquier médico / Staff</option>
                                        @foreach($doctors as $doc)
                                            <option value="{{ $doc->id }}">Dr. {{ $doc->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-1 text-end">
                                    <button type="button" class="btn btn-light text-danger rounded-circle p-2 shadow-sm border border-secondary border-opacity-10" onclick="removeAssignmentRow(this)" title="Quitar" style="display:none;">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4 text-start">
                            <textarea name="notes" rows="2" class="form-control bg-light border border-secondary border-opacity-10 shadow-none text-dark" placeholder="Observaciones Internas (Detalles extra...)"></textarea>
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary rounded-pill px-5 shadow-sm fw-bold d-flex align-items-center gap-2 py-2 border-0" style="background: linear-gradient(135deg, #5e6ad2 0%, #7e448b 100%); font-size: 1.05rem;">
                                Ingresar a Cola <i class="bi bi-send-fill text-white shadow-sm ms-2"></i>
                            </button>
                        </div>
                    </form>
                    <script>
                        function addAssignmentRow() {
                            const container = document.getElementById('assignmentRowsContainer');
                            const rowTemplate = container.firstElementChild.cloneNode(true);
                            rowTemplate.querySelector('button').style.display = 'block'; // Show delete button
                            // Show delete button on first row if there's more than one
                            container.firstElementChild.querySelector('button').style.display = 'block';
                            container.appendChild(rowTemplate);
                        }
                        function removeAssignmentRow(btn) {
                            const container = document.getElementById('assignmentRowsContainer');
                            if (container.children.length > 1) {
                                btn.closest('.assignment-row').remove();
                            }
                            if (container.children.length === 1) {
                                container.firstElementChild.querySelector('button').style.display = 'none';
                            }
                        }
                    </script>
                </div>

                <!-- Resumen Diario (En Curso vs Finalizados) -->
                @php
                    $todayAssignments = collect($patient->assignments ?? [])->filter(function($item) {
                        return $item->created_at >= \Carbon\Carbon::today();
                    })->sortBy('id');
                    
                    $enCurso = $todayAssignments->whereIn('status', ['in_progress', 'pending']);
                    $finalizados = $todayAssignments->where('status', 'completed');
                @endphp

                <div class="row g-4 pb-4">
                    <!-- EN CURSO -->
                    <div class="col-lg-6">
                        <div class="card border-0 rounded-4 overflow-hidden h-100 shadow-sm" style="border: 1px solid rgba(32, 201, 151, 0.2) !important;">
                            <div class="card-header border-0 fw-bold d-flex align-items-center gap-2" style="background-color: #20c997 !important; color: #fff; padding: 12px 20px;">
                                <i class="bi bi-play-fill fs-5"></i> En curso / Cola
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
                                                <tr style="{{ $asg->status === 'pending' ? 'opacity: 0.6;' : '' }}">
                                                    <td class="ps-4 fw-medium text-dark">{{ $asg->started_at ? $asg->started_at->format('H:i') : '--:--' }}</td>
                                                    <td class="fw-bold text-dark">{{ $asg->event_type }}</td>
                                                    <td class="text-center">
                                                        @if($asg->status === 'in_progress')
                                                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">Activo</span>
                                                        @else
                                                            <span class="badge bg-secondary bg-opacity-10 text-secondary border">En Cola</span>
                                                        @endif
                                                        <br><small class="text-muted" style="font-size:0.7rem;">{{ $asg->doctor->name ?? 'En Espera' }}</small>
                                                    </td>
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
                    <button id="sortDiagnosticosBtn" class="btn btn-light fw-bold text-secondary rounded-pill shadow-sm px-3" style="font-size: 0.9rem;">
                        <i class="bi bi-sort-down me-1"></i> Más recientes
                    </button>
                </div>
                
                @if($patient->visits->count() > 0)
                    <div class="d-flex flex-column gap-3" id="diagnosticosGrid">
                        @foreach($patient->visits as $visit)
                            <div class="card border-0 shadow-sm rounded-4 overflow-hidden diagnostico-item" data-date="{{ $visit->created_at->timestamp }}" style="border: 1px solid rgba(0,0,0,0.05) !important; {{ $visit->is_open ? 'border-left: 5px solid #198754 !important;' : '' }}">
                                <!-- Header Strip -->
                                <div class="px-4 py-3 d-flex justify-content-between align-items-center {{ $visit->is_open ? 'bg-success bg-opacity-10' : 'bg-light' }} border-bottom border-secondary border-opacity-10">
                                    <div class="d-flex align-items-center gap-3">
                                        <span class="badge {{ $visit->is_open ? 'bg-success text-white shadow-sm' : 'bg-secondary bg-opacity-10 text-secondary' }} rounded-pill px-3 py-2 fw-bold" style="font-size: 0.9rem;"># {{ $visit->id }}</span>
                                        <span class="text-muted fw-bold"><i class="bi bi-calendar3 me-1"></i> {{ $visit->created_at->format('d M Y - H:i') }}</span>
                                    </div>
                                    <div class="d-flex align-items-center gap-3">
                                        <span class="fw-bold text-dark"><i class="bi bi-person-fill text-primary opacity-75 me-1"></i> {{ $visit->doctor->name ?? 'Staff' }}</span>
                                        @if($visit->is_open)
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-3 py-1"><i class="bi bi-unlock-fill me-1"></i> Abierta</span>
                                        @else
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary border rounded-pill px-3 py-1"><i class="bi bi-lock-fill me-1"></i> Cerrada</span>
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
                                            <!-- Actions -->
                                            <div class="col-md-2 position-absolute bottom-0 end-0 d-flex justify-content-end align-items-end pe-4 pb-2 gap-2">
                                                <a href="{{ route('visits.edit', $visit) }}" class="btn btn-sm btn-light text-primary rounded-circle p-2 shadow-sm border border-secondary border-opacity-10" title="{{ $visit->is_open ? 'Continuar consulta' : 'Modificar consulta' }}">
                                                    <i class="bi {{ $visit->is_open ? 'bi-pencil-fill' : 'bi-pencil-square' }}"></i>
                                                </a>
                                                <form action="{{ route('visits.toggleStatus', $visit) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-light {{ $visit->is_open ? 'text-success font-weight-bold' : 'text-danger' }} rounded-circle p-2 shadow-sm border border-secondary border-opacity-10" title="{{ $visit->is_open ? 'Cerrar consulta' : 'Reabrir consulta' }}">
                                                        <i class="bi {{ $visit->is_open ? 'bi-unlock-fill' : 'bi-lock-fill' }}"></i>
                                                    </button>
                                                </form>
                                            </div>
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
                        <button id="sortStudiesBtn" class="btn btn-light fw-bold text-secondary rounded-pill shadow-sm px-3" style="font-size: 0.9rem;">
                            <i class="bi bi-sort-down me-1"></i> Más recientes
                        </button>
                        <button class="btn btn-gradient-primary rounded-pill px-4 d-flex align-items-center gap-2 text-white" data-bs-toggle="modal" data-bs-target="#uploadStudyModal">
                            <i class="bi bi-cloud-arrow-up"></i> Cargar Estudio
                        </button>
                    </div>
                </div>

                <div class="row g-3" id="studiesGrid">
                    @forelse($patient->studies as $study)
                        <div class="col-md-4 col-xl-3 study-card-item" data-date="{{ $study->created_at->timestamp }}">
                            <div class="card bg-white border-0 shadow-sm rounded-4 h-100 overflow-hidden" style="border: 1px solid rgba(0,0,0,0.06) !important;">
                                @if(in_array(strtolower($study->file_type), ['jpg', 'jpeg', 'png', 'webp']))
                                    <div style="height: 125px; background-image: url('{{ route('studies.show', $study) }}'); background-size: cover; background-position: center;" class="border-bottom position-relative">
                                        <span class="badge bg-dark bg-opacity-75 text-white position-absolute bottom-0 end-0 m-2" style="font-size: 0.68rem;">
                                            <i class="bi bi-calendar3 me-1"></i>{{ $study->created_at->format('d/m/Y') }}
                                        </span>
                                    </div>
                                @else
                                    <div style="height: 125px;" class="bg-light d-flex align-items-center justify-content-center border-bottom text-muted position-relative">
                                        <i class="bi bi-file-earmark-pdf-fill" style="font-size: 3.5rem; color: #dc3545;"></i>
                                        <span class="badge bg-dark bg-opacity-75 text-white position-absolute bottom-0 end-0 m-2" style="font-size: 0.68rem;">
                                            <i class="bi bi-calendar3 me-1"></i>{{ $study->created_at->format('d/m/Y') }}
                                        </span>
                                    </div>
                                @endif
                                
                                <div class="card-body p-3 d-flex flex-column justify-content-between">
                                    <div>
                                        <h6 class="fw-bold text-dark text-truncate mb-1" style="font-size: 0.9rem;" title="{{ $study->original_name }}">{{ $study->original_name }}</h6>
                                        <span class="badge bg-primary bg-opacity-10 text-primary py-0.5 px-2 rounded-pill border border-primary border-opacity-25 mb-2" style="font-size: 0.72rem;">{{ $study->study_type ?? 'Documento General' }}</span>
                                        
                                        @if($study->notes)
                                            <p class="small text-muted mb-2 fst-italic" style="font-size: 0.78rem; line-height: 1.2;">
                                                "{{ Str::limit($study->notes, 50) }}"
                                            </p>
                                        @endif
                                    </div>
                                    
                                    <div class="d-flex justify-content-between align-items-center pt-2 border-top border-light mt-2">
                                        <div class="d-flex align-items-center gap-1">
                                            <a href="{{ route('studies.show', $study) }}" target="_blank" class="btn btn-sm btn-light text-primary rounded-pill px-2.5 py-1 fw-bold" style="font-size: 0.75rem;" title="Ver estudio"><i class="bi bi-eye"></i></a>
                                            <a href="{{ route('studies.download', $study) }}" class="btn btn-sm btn-light text-secondary rounded-pill px-2 py-1 fw-bold" style="font-size: 0.75rem;" title="Descargar"><i class="bi bi-download"></i></a>
                                            <button type="button" class="btn btn-sm btn-light text-dark rounded-pill px-2 py-1 fw-bold" style="font-size: 0.75rem;" onclick="openEditStudyModal({{ $study->id }}, '{{ addslashes($study->original_name) }}', '{{ addslashes($study->study_type ?? '') }}', '{{ $study->created_at->format('Y-m-d') }}', '{{ addslashes($study->notes ?? '') }}')" title="Editar datos del estudio">
                                                <i class="bi bi-pencil-fill text-warning"></i>
                                            </button>
                                        </div>
                                        <form action="{{ route('studies.destroy', $study) }}" method="POST" class="m-0">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-light text-danger rounded-pill px-2 py-1 fw-bold" style="font-size: 0.75rem;" onclick="return confirm('¿Borrar este estudio permanentemente?');" title="Eliminar estudio"><i class="bi bi-trash"></i></button>
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
                    <button id="sortComentariosBtn" class="btn btn-light fw-bold text-secondary rounded-pill shadow-sm px-3" style="font-size: 0.9rem;">
                        <i class="bi bi-sort-down me-1"></i> Más recientes
                    </button>
                </div>
                
                <form action="{{ route('patient.comments.store', $patient) }}" method="POST" class="mb-5 bg-light p-3 rounded-4" style="border: 1px solid rgba(0,0,0,0.05);">
                    @csrf
                    <div class="d-flex gap-3">
                        <textarea name="body" required class="form-control border-0 shadow-sm" rows="2" placeholder="Escribir un nuevo comentario publico o privado sobre el paciente..."></textarea>
                        <button class="btn btn-primary rounded-3 px-4 fw-bold shadow-sm d-flex flex-column justify-content-center align-items-center" type="submit"><i class="bi bi-save2 mb-1"></i> Guardar</button>
                    </div>
                </form>

                @if($patient->comments && $patient->comments->count() > 0)
                    <div class="d-flex flex-column gap-3" id="comentariosGrid">
                        @foreach($patient->comments as $comment)
                            <div class="bg-white p-3 rounded-4 shadow-sm border-start border-4 border-primary comentario-item" data-date="{{ $comment->created_at->timestamp }}" style="border: 1px solid rgba(0,0,0,0.05);">
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
                <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3 flex-wrap gap-2">
                    <h5 class="fw-bold text-dark mb-0"><i class="bi bi-bandaid text-danger me-2"></i> Cirugías</h5>
                    <div class="d-flex gap-2 align-items-center flex-wrap">
                        <!-- Filtro por ojo -->
                        <div class="btn-group btn-group-sm shadow-sm" role="group" aria-label="Filtro de ojo">
                            <button type="button" class="btn btn-secondary active cirugia-filter-eye-btn" data-filter-eye="all">Todos</button>
                            <button type="button" class="btn btn-outline-secondary cirugia-filter-eye-btn" data-filter-eye="OD">Solo OD</button>
                            <button type="button" class="btn btn-outline-secondary cirugia-filter-eye-btn" data-filter-eye="OI">Solo OI</button>
                        </div>
                        <!-- Botón Orden Asc / Desc -->
                        <button id="sortCirugiasSpartanBtn" class="btn btn-light border fw-bold text-secondary rounded-pill shadow-sm px-3 btn-sm">
                            <i class="bi bi-sort-down me-1" id="sortCirugiasSpartanIcon"></i> <span id="sortCirugiasSpartanText">Más recientes primero</span>
                        </button>
                    </div>
                </div>
                
                <h6 class="fw-bold text-muted text-uppercase mb-3" style="font-size: 0.82rem; letter-spacing: 0.5px;">
                    <i class="bi bi-list-task me-1"></i> Resumen de Cirugías
                </h6>

                @if($patient->surgeries->count() > 0)
                    <div class="bg-white rounded-3 border shadow-sm mb-5 p-2" id="cirugiasSpartanContainer">
                        <div class="d-flex flex-column gap-1" id="cirugiasSpartanList">
                            @foreach($patient->surgeries->sortByDesc('surgery_date') as $surg)
                                <div class="cirugia-spartan-row d-flex justify-content-between align-items-center py-2 px-3 rounded-2 border-bottom hover-bg-light transition-all"
                                     data-eye="{{ $surg->eye }}"
                                     data-date="{{ $surg->surgery_date ? $surg->surgery_date->timestamp : 0 }}">
                                    <div class="d-flex align-items-center gap-2 flex-grow-1 flex-wrap me-2">
                                        <span class="badge {{ $surg->eye == 'OD' ? 'bg-primary' : ($surg->eye == 'OI' ? 'bg-success' : 'bg-secondary') }} px-2 py-1" style="font-size: 0.8rem;">
                                            {{ $surg->eye }}
                                        </span>
                                        <span class="fw-semibold text-dark">{{ $surg->notes }}</span>
                                        <span class="text-muted fw-bold">---</span>
                                        <span class="text-secondary font-monospace small"><i class="bi bi-calendar3 me-1"></i>{{ $surg->surgery_date ? $surg->surgery_date->format('d/m/Y') : '-' }}</span>
                                        @if($surg->doctor)
                                            <span class="text-muted small">({{ $surg->doctor->name }})</span>
                                        @endif
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <form action="{{ route('surgeries.destroy', $surg) }}" method="POST" class="d-inline m-0">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-light text-danger rounded-circle p-1 px-2 border-0 shadow-none" onclick="return confirm('¿ELIMINAR este registro quirúrgico del historial?');" title="Borrar Cirugía">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="py-4 text-center bg-light rounded-3 mb-5 border border-dashed">
                        <p class="text-muted mb-0 small">Sin cirugías previas registradas para este paciente.</p>
                    </div>
                @endif
                
                <h6 class="fw-bold text-muted text-uppercase mb-2" style="font-size: 0.82rem; letter-spacing: 0.5px;">
                    <i class="bi bi-plus-circle me-1"></i> Cargar Nuevas Cirugías (1 renglón auto-expandible)
                </h6>
                <form action="{{ route('patient.surgeries.store', $patient) }}" method="POST">
                    @csrf
                    <div class="row g-3 mb-3">
                        <!-- OD Box Compacta -->
                        <div class="col-md-6">
                            <div class="card border-0 bg-primary bg-opacity-10 shadow-sm rounded-4">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                                        <span class="fw-bold text-primary d-flex align-items-center gap-2">
                                            <span class="badge bg-primary rounded-pill px-2 py-1">OD</span> Cirugía Ojo Derecho
                                        </span>
                                        <div class="d-flex align-items-center gap-1">
                                            <small class="text-muted fw-bold">Fecha:</small>
                                            <input type="date" name="od_date" value="{{ date('Y-m-d') }}" class="form-control form-control-sm bg-white border shadow-none rounded-3" style="width: 145px;">
                                        </div>
                                    </div>
                                    <textarea name="od_notes" rows="1" class="form-control bg-white border shadow-none rounded-3 auto-expand" style="min-height: 38px; resize: none; overflow-y: hidden;" placeholder="Procedimiento OD, tipo de cirugía, LIO utilizada... (se expande al escribir)"></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- OI Box Compacta -->
                        <div class="col-md-6">
                            <div class="card border-0 bg-success bg-opacity-10 shadow-sm rounded-4">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                                        <span class="fw-bold text-success d-flex align-items-center gap-2">
                                            <span class="badge bg-success rounded-pill px-2 py-1">OI</span> Cirugía Ojo Izquierdo
                                        </span>
                                        <div class="d-flex align-items-center gap-1">
                                            <small class="text-muted fw-bold">Fecha:</small>
                                            <input type="date" name="oi_date" value="{{ date('Y-m-d') }}" class="form-control form-control-sm bg-white border shadow-none rounded-3" style="width: 145px;">
                                        </div>
                                    </div>
                                    <textarea name="oi_notes" rows="1" class="form-control bg-white border shadow-none rounded-3 auto-expand" style="min-height: 38px; resize: none; overflow-y: hidden;" placeholder="Procedimiento OI, tipo de cirugía, LIO utilizada... (se expande al escribir)"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end mt-2">
                        <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm d-flex align-items-center gap-2 py-2 border-0" style="background: linear-gradient(135deg, #5e6ad2 0%, #7e448b 100%); font-size: 0.95rem;">
                            <i class="bi bi-save2 me-1"></i> Guardar Cirugías
                        </button>
                    </div>
                </form>
            </div>

            <!-- Tab: Turnos y Asignaciones -->
            <div class="tab-pane fade" id="turnos" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom border-light flex-wrap gap-2">
                    <div>
                        <h5 class="fw-bold text-dark mb-1"><i class="bi bi-calendar-check text-primary me-2"></i> Historial Completo de Turnos y Concurrencias</h5>
                        <small class="text-muted">Turnos programados, sobreturnos, asistencias y ausencias registradas.</small>
                    </div>
                    <a href="{{ route('agenda.index') }}?preselect_patient_id={{ $patient->id }}&preselect_patient_fname={{ urlencode($patient->first_name) }}&preselect_patient_lname={{ urlencode($patient->last_name) }}&preselect_patient_dni={{ $patient->dni }}" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm d-flex align-items-center gap-2 btn-sm">
                        <i class="bi bi-calendar-plus"></i> Agendar Nuevo Turno
                    </a>
                </div>
                
                @php 
                    $allAppts = $patient->appointments ? $patient->appointments->sortByDesc('date') : collect();
                    $todayStr = now()->toDateString();
                    $futuros = $allAppts->filter(fn($a) => $a->date >= $todayStr && $a->status !== 'cancelled');
                    $pasados = $allAppts->filter(fn($a) => $a->date < $todayStr && $a->status === 'completed');
                    $ausencias = $allAppts->filter(fn($a) => $a->status === 'no_show' || $a->status === 'cancelled');
                @endphp

                <!-- Tarjetas de Resumen Rápido -->
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="card border-0 rounded-4 p-3 shadow-sm bg-primary bg-opacity-10 border-start border-4 border-primary">
                            <span class="text-muted small fw-bold text-uppercase" style="font-size:0.75rem;">Turnos Próximos / Futuros</span>
                            <h4 class="fw-bold text-primary mb-0 mt-1">{{ $futuros->count() }}</h4>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0 rounded-4 p-3 shadow-sm bg-success bg-opacity-10 border-start border-4 border-success">
                            <span class="text-muted small fw-bold text-uppercase" style="font-size:0.75rem;">Turnos Concurridos / Atendidos</span>
                            <h4 class="fw-bold text-success mb-0 mt-1">{{ $pasados->count() }}</h4>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0 rounded-4 p-3 shadow-sm bg-danger bg-opacity-10 border-start border-4 border-danger">
                            <span class="text-muted small fw-bold text-uppercase" style="font-size:0.75rem;">No Concurrencias / Cancelaciones</span>
                            <h4 class="fw-bold text-danger mb-0 mt-1">{{ $ausencias->count() }}</h4>
                        </div>
                    </div>
                </div>
                
                @if($allAppts->count() > 0)
                    <div class="table-responsive bg-white rounded-4 shadow-sm" style="border: 1px solid rgba(0,0,0,0.05);">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-muted small fw-bold text-uppercase">
                                <tr>
                                    <th class="ps-4">Fecha / Hora</th>
                                    <th>Tipo</th>
                                    <th>Profesional</th>
                                    <th>Motivo / Especialidad</th>
                                    <th>Estado / Concurrencia</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($allAppts as $appt)
                                    @php
                                        $isFuture = ($appt->date >= $todayStr);
                                    @endphp
                                    <tr class="{{ $isFuture ? 'table-primary bg-opacity-10' : '' }}">
                                        <td class="ps-4">
                                            <div class="fw-bold {{ $isFuture ? 'text-primary' : 'text-dark' }}">{{ \Carbon\Carbon::parse($appt->date)->format('d/m/Y') }}</div>
                                            <div class="small text-muted"><i class="bi bi-clock me-1"></i>{{ \Carbon\Carbon::parse($appt->time)->format('H:i') }} hs</div>
                                        </td>
                                        <td>
                                            @if($appt->is_overbooked)
                                                <span class="badge bg-warning bg-opacity-25 text-dark border border-warning rounded-pill px-3 py-1 fw-bold" style="font-size:0.75rem;">
                                                    <i class="bi bi-exclamation-circle me-1"></i> Sobreturno
                                                </span>
                                            @else
                                                <span class="badge bg-light text-secondary border rounded-pill px-3 py-1" style="font-size:0.75rem;">
                                                    Turno Regular
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="d-flex align-items-center gap-2">
                                                <div class="bg-primary bg-opacity-10 text-primary p-2 rounded-circle" style="width:28px; height:28px; display:flex; align-items:center; justify-content:center;">
                                                    <i class="bi bi-person-fill fs-6"></i>
                                                </div> 
                                                {{ $appt->doctor->name ?? '-' }}
                                            </span>
                                        </td>
                                        <td class="text-secondary">
                                            <span class="fw-medium text-dark">{{ $appt->reason ?? 'Consulta' }}</span>
                                            @if($appt->notes)
                                                <small class="d-block text-muted" style="font-size:0.75rem;">{{ $appt->notes }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            @if($appt->status === 'completed')
                                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-3 py-1 fw-bold">
                                                    <i class="bi bi-check-circle-fill me-1"></i> Atendido
                                                </span>
                                            @elseif($appt->status === 'no_show')
                                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 rounded-pill px-3 py-1 fw-bold">
                                                    <i class="bi bi-x-circle-fill me-1"></i> No Concurrió
                                                </span>
                                            @elseif($appt->status === 'cancelled')
                                                <span class="badge bg-secondary bg-opacity-10 text-secondary border rounded-pill px-3 py-1 fw-bold">
                                                    <i class="bi bi-slash-circle me-1"></i> Cancelado
                                                </span>
                                            @elseif($isFuture)
                                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-3 py-1 fw-bold">
                                                    <i class="bi bi-calendar-event me-1"></i> Próximo Turno
                                                </span>
                                            @else
                                                <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 rounded-pill px-3 py-1 fw-bold">
                                                    {{ ucfirst($appt->status) }}
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="py-5 text-center bg-light rounded-4" style="border: 2px dashed #dee2e6;">
                        <i class="bi bi-calendar-x fs-1 text-secondary opacity-50 mb-3 d-block"></i>
                        <h5 class="fw-bold text-dark">Sin turnos registrados</h5>
                        <p class="text-muted">El paciente no registra turnos pasados ni futuros en el sistema.</p>
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
                @php
                    $timelineItems = collect();

                    foreach($patient->visits as $visit) {
                        $timelineItems->push((object)[
                            'type' => 'visit',
                            'date' => $visit->created_at,
                            'data' => $visit,
                        ]);
                    }

                    foreach($patient->surgeries as $surg) {
                        $surgDate = $surg->surgery_date ? \Carbon\Carbon::parse($surg->surgery_date)->endOfDay() : $surg->created_at;
                        $timelineItems->push((object)[
                            'type' => 'surgery',
                            'date' => $surgDate,
                            'data' => $surg,
                        ]);
                    }

                    // Por default el último siempre arriba (más recientes primero)
                    $timelineItems = $timelineItems->sortByDesc('date');
                @endphp

                <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom border-light">
                    <h5 class="fw-bold text-dark mb-0"><i class="bi bi-journal-medical text-primary me-2"></i> Historia Clínica</h5>
                    <div class="d-flex gap-2">
                        @if($timelineItems->count() > 0)
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

                @if($timelineItems->count() > 0)
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
                                    <h6 class="fw-bold text-dark mb-1">Comentarios Médicos <span class="badge bg-warning bg-opacity-25 text-dark border border-warning" style="font-size:0.62rem;">Solo Consultorio</span></h6>
                                    <p class="text-secondary small mb-0" style="white-space: pre-line; line-height: 1.4;">{{ $patient->medical_notes }}</p>
                                @endif
                            </div>
                        </div>

                        <!-- Contenedor Flex para ordenar -->
                        <div id="visitasListContainer" class="d-flex flex-column gap-5">
                            @foreach($timelineItems as $item)
                                @if($item->type === 'visit')
                                    @php $visit = $item->data; @endphp
                                    <div class="visit-item p-3 rounded-4 mb-4" style="{{ $visit->is_open ? 'background-color: rgba(25, 135, 84, 0.03); border-left: 5px solid #198754; border-top: 1px solid rgba(0,0,0,0.05); border-right: 1px solid rgba(0,0,0,0.05); border-bottom: 1px solid rgba(0,0,0,0.05);' : 'border-left: 1px solid rgba(0,0,0,0.05);' }}">
                                        <div class="d-flex justify-content-between align-items-center mb-1 flex-wrap gap-2">
                                            <h6 class="fw-bold text-dark mb-0" style="font-size: 1.05rem;">
                                                <i class="bi bi-record-circle {{ $visit->is_open ? 'text-success' : 'text-primary' }} me-2"></i> 
                                                Visita: {{ $visit->created_at->format('d-M-Y') }}
                                                @if($visit->is_open)
                                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-2 py-1 ms-2" style="font-size: 0.8rem;"><i class="bi bi-unlock-fill me-1"></i> Abierta</span>
                                                @else
                                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border rounded-pill px-2 py-1 ms-2" style="font-size: 0.8rem;"><i class="bi bi-lock-fill me-1"></i> Cerrada</span>
                                                @endif
                                            </h6>
                                            <div class="d-flex align-items-center gap-2">
                                                <h6 class="text-secondary mb-0 fw-bold me-3" style="font-size: 1.05rem;">Médico: {{ $visit->doctor->name ?? 'Staff' }}</h6>
                                                <a href="{{ route('visits.edit', $visit) }}" class="btn btn-sm btn-outline-primary rounded-pill fw-bold px-3 py-1 d-flex align-items-center gap-1" style="font-size: 0.8rem;">
                                                    <i class="bi {{ $visit->is_open ? 'bi-pencil-fill' : 'bi-pencil-square' }}"></i> {{ $visit->is_open ? 'Continuar' : 'Modificar' }}
                                                </a>
                                                <form action="{{ route('visits.toggleStatus', $visit) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-{{ $visit->is_open ? 'success' : 'danger' }} rounded-pill fw-bold px-3 py-1 d-flex align-items-center gap-1" style="font-size: 0.8rem;" title="{{ $visit->is_open ? 'Cerrar consulta' : 'Reabrir consulta' }}">
                                                        <i class="bi {{ $visit->is_open ? 'bi-unlock-fill' : 'bi-lock-fill' }}"></i> {{ $visit->is_open ? 'Cerrar' : 'Reabrir' }}
                                                    </button>
                                                </form>
                                            </div>
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
                                @elseif($item->type === 'surgery')
                                    @php $surg = $item->data; @endphp
                                    <!-- Cirugía Mechada con Desplazamiento -->
                                    <div class="surgery-timeline-item p-3 rounded-4 mb-4" style="margin-left: 2.75rem; background-color: #fafbfc; border-left: 5px solid {{ $surg->eye == 'OD' ? '#0d6efd' : ($surg->eye == 'OI' ? '#198754' : '#6f42c1') }}; border-top: 1px solid rgba(0,0,0,0.06); border-right: 1px solid rgba(0,0,0,0.06); border-bottom: 1px solid rgba(0,0,0,0.06); box-shadow: 0 2px 6px rgba(0,0,0,0.02);">
                                        <div class="d-flex justify-content-between align-items-center mb-1 flex-wrap gap-2">
                                            <h6 class="fw-bold text-dark mb-0" style="font-size: 1.05rem;">
                                                <i class="bi bi-heart-pulse-fill text-danger me-2"></i>
                                                Cirugía: {{ $surg->surgery_date ? $surg->surgery_date->format('d-M-Y') : $surg->created_at->format('d-M-Y') }}
                                                <span class="badge {{ $surg->eye == 'OD' ? 'bg-primary' : ($surg->eye == 'OI' ? 'bg-success' : 'bg-secondary') }} rounded-pill px-3 py-1 ms-2" style="font-size: 0.8rem;">
                                                    Ojo {{ $surg->eye }}
                                                </span>
                                            </h6>
                                            <div class="d-flex align-items-center gap-2">
                                                <h6 class="text-secondary mb-0 fw-bold me-3" style="font-size: 1.05rem;">Cirujano: {{ $surg->doctor->name ?? 'Staff' }}</h6>
                                                <form action="{{ route('surgeries.destroy', $surg) }}" method="POST" class="d-inline m-0">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill fw-bold px-3 py-1 d-flex align-items-center gap-1" style="font-size: 0.8rem;" onclick="return confirm('¿ELIMINAR este registro quirúrgico de la historia clínica?');" title="Borrar Cirugía">
                                                        <i class="bi bi-trash"></i> Borrar
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                        <hr class="my-3 border-secondary opacity-10" style="border-width: 2px;">
                                        <div class="px-2" style="font-size: 0.95rem;">
                                            <div class="row align-items-start">
                                                <div class="col-3 col-md-2 text-dark font-monospace fw-bold" style="letter-spacing:-0.5px;">Procedimiento:</div>
                                                <div class="col-9 col-md-10 text-secondary" style="white-space: pre-line; padding-top:2px;">{{ $surg->notes }}</div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="py-5 text-center bg-light rounded-4" style="border: 2px dashed #dee2e6;">
                        <div class="bg-white rounded-circle shadow-sm d-inline-flex justify-content-center align-items-center mb-3" style="width: 80px; height: 80px;">
                            <i class="bi bi-journal-x fs-1 text-primary opacity-50"></i>
                        </div>
                        <h5 class="fw-bold text-dark">La Ficha Clínica está vacía</h5>
                        <p class="text-muted mb-0">Haz click en "Cargar Nueva Evolución" o registra una cirugía para comenzar el historial.</p>
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

<!-- Modal: Editar Estudio Médico -->
<div class="modal fade" id="editStudyModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 rounded-4 shadow">
        <div class="modal-header border-bottom-0 pb-0">
            <h5 class="modal-title fw-bold text-dark"><i class="bi bi-pencil-square text-primary me-2"></i>Editar Datos del Estudio</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form id="editStudyForm" method="POST" action="">
            @csrf
            @method('PUT')
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">Denominación / Título del Estudio *</label>
                    <input type="text" name="original_name" id="modalEditStudyName" class="form-control bg-light border-0 shadow-none" required>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label text-muted small fw-bold">Tipo de Estudio</label>
                        <select name="study_type" id="modalEditStudyType" class="form-control bg-light border-0 shadow-none form-select">
                            <option value="Documento General">Documento General</option>
                            <option value="OCT Macular / Papilar">OCT Macular / Papilar</option>
                            <option value="Campo Visual Computarizado">Campo Visual Computarizado</option>
                            <option value="Fondo de Ojo / Retinografía">Fondo de Ojo / Retinografía</option>
                            <option value="Ecografía Ocular">Ecografía Ocular</option>
                            <option value="Topografía Corneal">Topografía Corneal</option>
                            <option value="Paquimetría">Paquimetría</option>
                            <option value="Biometría / IOL Master">Biometría / IOL Master</option>
                            <option value="Laboratorio Quirúrgico">Laboratorio Quirúrgico</option>
                            <option value="Informe Quirúrgico">Informe Quirúrgico</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small fw-bold">Fecha del Estudio *</label>
                        <input type="date" name="study_date" id="modalEditStudyDate" class="form-control bg-light border-0 shadow-none" required>
                    </div>
                </div>
                <div class="mb-2">
                    <label class="form-label text-muted small fw-bold">Observaciones / Conclusiones del Estudio</label>
                    <textarea name="notes" id="modalEditStudyNotes" rows="3" class="form-control bg-light border-0 shadow-none rounded-3" placeholder="Ej: Espesor macular central normal, sin signos de edema..."></textarea>
                </div>
            </div>
            <div class="modal-footer border-top-0 pt-0 pe-4">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold" style="background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%); border:none;">
                    Guardar Cambios
                </button>
            </div>
        </form>
    </div>
  </div>
</div>

<script>
// Función para activar programáticamente las solapas (tabs) con scroll automático
function activateBottomTab(tabId) {
    const triggerEl = document.querySelector('#' + tabId);
    if (triggerEl) {
        const tab = new bootstrap.Tab(triggerEl);
        tab.show();
        setTimeout(() => {
            const target = (tabId === 'historial-tab') 
                ? (document.getElementById('visitasListContainer') || document.getElementById('bottomTabs'))
                : document.getElementById('bottomTabs');
            if (target) {
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }, 100);
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

    // Query params para redirección
    const urlParams = new URLSearchParams(window.location.search);
    const urlTab = urlParams.get('tab');
    if (urlTab) {
        const triggerEl = document.querySelector('#' + urlTab + '-tab');
        if (triggerEl) {
            const tab = new bootstrap.Tab(triggerEl);
            tab.show();
        }
    }

    // Helper function para ordenar divs o listas por fecha
    function bindSortLogic(btnId, gridId, itemClass) {
        const sortBtn = document.getElementById(btnId);
        const grid = document.getElementById(gridId);
        if (sortBtn && grid) {
            let direction = 'desc'; // initial state
            const updateSort = function() {
                const items = Array.from(grid.querySelectorAll('.' + itemClass));
                if (items.length === 0) return;
                
                items.sort((a, b) => {
                    const dateA = parseInt(a.getAttribute('data-date')) || 0;
                    const dateB = parseInt(b.getAttribute('data-date')) || 0;
                    return direction === 'asc' ? dateA - dateB : dateB - dateA;
                });
                items.forEach(item => grid.appendChild(item));
                
                if (direction === 'desc') {
                    sortBtn.innerHTML = '<i class="bi bi-sort-down me-1"></i> Más recientes';
                } else {
                    sortBtn.innerHTML = '<i class="bi bi-sort-up me-1"></i> Más antiguos';
                }
            };
            sortBtn.addEventListener('click', function(e) {
                e.preventDefault();
                direction = direction === 'desc' ? 'asc' : 'desc';
                updateSort();
            });
            updateSort(); // Inicializar el orden correcto
        }
    }

    bindSortLogic('sortStudiesBtn', 'studiesGrid', 'study-card-item');
    bindSortLogic('sortDiagnosticosBtn', 'diagnosticosGrid', 'diagnostico-item');
    bindSortLogic('sortComentariosBtn', 'comentariosGrid', 'comentario-item');
    bindSortLogic('sortCirugiasBtn', 'cirugiasGrid', 'cirugia-item');

    initCirugiasSpartan();

    document.querySelectorAll('textarea.auto-expand').forEach(ta => {
        const resizeTa = () => {
            ta.style.height = 'auto';
            ta.style.height = (ta.scrollHeight > 38 ? ta.scrollHeight + 4 : 38) + 'px';
        };
        ta.addEventListener('input', resizeTa);
        resizeTa();
    });
});

function toggleFichaOjitoTab() {
    const tabBtn = document.getElementById('ultimavisita-tab');
    const infoBtn = document.getElementById('informacion-tab');
    if (!tabBtn) return;
    if (tabBtn.classList.contains('active') && infoBtn) {
        new bootstrap.Tab(infoBtn).show();
    } else {
        new bootstrap.Tab(tabBtn).show();
        document.getElementById('topTabs')?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
}

function filterFichaTopHistory(term) {
    const q = (term || '').toLowerCase().trim();
    document.querySelectorAll('#fichaTopVisitsList .ficha-top-visit-card').forEach(card => {
        const text = card.innerText.toLowerCase();
        card.style.display = (!q || text.includes(q)) ? '' : 'none';
    });
}

function openEditStudyModal(studyId, originalName, studyType, studyDate, notes) {
    const form = document.getElementById('editStudyForm');
    form.action = `/studies/${studyId}`;
    document.getElementById('modalEditStudyName').value = originalName;
    document.getElementById('modalEditStudyType').value = studyType || 'Documento General';
    document.getElementById('modalEditStudyDate').value = studyDate;
    document.getElementById('modalEditStudyNotes').value = notes || '';
    const modal = new bootstrap.Modal(document.getElementById('editStudyModal'));
    modal.show();
}

// Lógica de Resumen Espartano de Cirugías
function initCirugiasSpartan() {
    const list = document.getElementById('cirugiasSpartanList');
    if (!list) return;

    let currentOrder = 'desc'; // Por default: últimas/más recientes arriba
    let currentFilter = 'all';  // 'all', 'OD', 'OI'

    const filterBtns = document.querySelectorAll('.cirugia-filter-eye-btn');
    const sortBtn = document.getElementById('sortCirugiasSpartanBtn');
    const sortIcon = document.getElementById('sortCirugiasSpartanIcon');
    const sortText = document.getElementById('sortCirugiasSpartanText');

    function applyFilterAndSort() {
        const rows = Array.from(list.querySelectorAll('.cirugia-spartan-row'));

        // Filtrado por ojo
        rows.forEach(row => {
            const eye = (row.getAttribute('data-eye') || '').toUpperCase().trim();
            const filter = currentFilter.toUpperCase().trim();
            if (filter === 'ALL' || eye === filter) {
                row.classList.remove('d-none');
                row.classList.add('d-flex');
                row.style.setProperty('display', 'flex', 'important');
            } else {
                row.classList.remove('d-flex');
                row.classList.add('d-none');
                row.style.setProperty('display', 'none', 'important');
            }
        });

        // Ordenamiento ascendente o descendente por fecha
        rows.sort((a, b) => {
            const dateA = parseInt(a.getAttribute('data-date')) || 0;
            const dateB = parseInt(b.getAttribute('data-date')) || 0;
            return currentOrder === 'asc' ? (dateA - dateB) : (dateB - dateA);
        });

        rows.forEach(row => list.appendChild(row));
    }

    if (sortBtn) {
        sortBtn.addEventListener('click', function() {
            if (currentOrder === 'desc') {
                currentOrder = 'asc';
                if (sortText) sortText.innerText = 'Más antiguas primero';
                if (sortIcon) sortIcon.className = 'bi bi-sort-up me-1';
            } else {
                currentOrder = 'desc';
                if (sortText) sortText.innerText = 'Más recientes primero';
                if (sortIcon) sortIcon.className = 'bi bi-sort-down me-1';
            }
            applyFilterAndSort();
        });
    }

    filterBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            filterBtns.forEach(b => {
                b.classList.remove('active', 'btn-secondary', 'text-white');
                b.classList.add('btn-outline-secondary');
            });

            this.classList.remove('btn-outline-secondary');
            this.classList.add('active', 'btn-secondary', 'text-white');
            currentFilter = this.getAttribute('data-filter-eye');
            applyFilterAndSort();
        });
    });

    applyFilterAndSort();
}

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
document.getElementById('patientPhotoInput')?.addEventListener('change', function() {
    if (!this.files || !this.files[0]) return;
    
    let file = this.files[0];
    
    // Show loading UI on overlay
    const overlay = document.getElementById('avatarOverlay');
    if (overlay) {
        overlay.innerHTML = '<div class="spinner-border text-light spinner-border-sm" role="status"></div>';
        overlay.style.opacity = 1;
    }

    let img = new Image();
    let reader = new FileReader();

    reader.onload = function(e) {
        img.src = e.target.result;
    };
    
    reader.onerror = function() {
        alert('Error al leer el archivo local.');
        resetOverlay();
    };
    
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
                        document.getElementById('patientAvatarImage').src = data.photo_url + '?t=' + new Date().getTime();
                        document.getElementById('patientAvatarImage').style.display = 'block';
                        const avatarIcon = document.getElementById('patientAvatarIcon');
                        if (avatarIcon) avatarIcon.style.display = 'none';
                        
                        // Update modal image too
                        const modalImg = document.getElementById('modalPatientAvatarImage');
                        if (modalImg) {
                            modalImg.src = data.photo_url;
                            modalImg.style.display = 'block';
                            const modalIcon = document.getElementById('modalPatientAvatarIcon');
                            if (modalIcon) modalIcon.style.display = 'none';
                        }
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
        if (overlay) {
            overlay.innerHTML = '<i class="bi bi-camera"></i>';
            overlay.style.opacity = 0;
        }
        const input = document.getElementById('patientPhotoInput');
        if (input) input.value = '';
    }

    reader.readAsDataURL(file);
});
</script>

<!-- Opciones de Avatar Modal -->
<div class="modal fade" id="photoOptionsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-body p-4 text-center">
                <div class="mb-4 position-relative d-inline-block w-100">
                    @if($patient->photo_path)
                        <img id="modalPatientAvatarImage" src="{{ Storage::url($patient->photo_path) }}" class="rounded-4 shadow-sm" style="width: 100%; max-width: 320px; aspect-ratio: 1; object-fit: cover; border: 6px solid #fff; box-shadow: 0 10px 30px rgba(0,0,0,0.1) !important;">
                        <i id="modalPatientAvatarIcon" class="bi bi-person-fill" style="display: none;"></i>
                    @else
                        <img id="modalPatientAvatarImage" src="" class="rounded-4 shadow-sm" style="width: 100%; max-width: 320px; aspect-ratio: 1; object-fit: cover; border: 6px solid #fff; box-shadow: 0 10px 30px rgba(0,0,0,0.1) !important; display: none;">
                        <div id="modalPatientAvatarIcon" class="rounded-4 shadow-sm bg-light text-primary d-flex align-items-center justify-content-center mx-auto" style="width: 100%; max-width: 320px; aspect-ratio: 1; border: 6px solid #fff; box-shadow: 0 10px 30px rgba(0,0,0,0.1) !important;">
                            <i class="bi bi-person-fill" style="font-size: 8rem;"></i>
                        </div>
                    @endif
                </div>
                <h6 class="fw-bold mb-4 text-dark fs-5">Opciones de Foto</h6>
                <div class="d-flex flex-column flex-sm-row justify-content-center gap-3">
                    <button class="btn btn-outline-primary rounded-pill fw-bold px-4 py-2" onclick="bootstrap.Modal.getInstance(document.getElementById('photoOptionsModal')).hide(); document.getElementById('patientPhotoInput').click();">
                        <i class="bi bi-folder2-open me-2"></i> Subir PC
                    </button>
                    <button class="btn btn-primary rounded-pill fw-bold px-4 py-2" style="background: linear-gradient(135deg, #5e6ad2 0%, #7e448b 100%); border:none;" onclick="bootstrap.Modal.getInstance(document.getElementById('photoOptionsModal')).hide(); openCameraModal();">
                        <i class="bi bi-camera-fill me-2"></i> Tomar Foto
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Cámara Modal (WebRTC) -->
<div class="modal fade" id="cameraModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold"><i class="bi bi-camera-video text-primary me-2"></i> Tomar Foto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" onclick="stopCamera()"></button>
            </div>
            <div class="modal-body text-center">
                <div class="mb-3">
                    <select id="cameraSelect" class="form-select bg-light border-0 shadow-none rounded-pill" onchange="startCamera(this.value)">
                        <option value="">Buscando cámaras...</option>
                    </select>
                </div>
                
                <div class="position-relative bg-dark rounded-4 overflow-hidden shadow-sm mx-auto d-flex align-items-center justify-content-center" style="width: 320px; height: 320px;">
                    <video id="cameraVideo" autoplay playsinline style="width: 100%; height: 100%; object-fit: cover;"></video>
                    <div class="position-absolute border border-3 border-white border-opacity-50 rounded-circle" style="width: 280px; height: 280px; pointer-events: none;"></div>
                </div>
                <canvas id="cameraCanvas" style="display:none;"></canvas>
            </div>
            <div class="modal-footer border-top-0 pt-0 d-flex justify-content-center">
                <button type="button" class="btn btn-danger rounded-pill px-5 shadow-sm fw-bold" onclick="takePhotoAndUpload()">
                    <i class="bi bi-camera me-1"></i> Capturar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    let currentStream = null;

    function openCameraModal() {
        new bootstrap.Modal(document.getElementById('cameraModal')).show();
        
        navigator.mediaDevices.enumerateDevices()
            .then(devices => {
                const videoDevices = devices.filter(device => device.kind === 'videoinput');
                const select = document.getElementById('cameraSelect');
                select.innerHTML = '';
                
                if (videoDevices.length === 0) {
                    select.innerHTML = '<option value="">No se detectaron cámaras</option>';
                    return;
                }

                videoDevices.forEach((device, index) => {
                    const option = document.createElement('option');
                    option.value = device.deviceId;
                    option.text = device.label || `Cámara ${index + 1}`;
                    select.appendChild(option);
                });

                startCamera(videoDevices[0].deviceId);
            })
            .catch(err => {
                console.error('Error listing devices:', err);
                alert('No se pudo acceder a las cámaras. Verifique los permisos del navegador.');
                bootstrap.Modal.getInstance(document.getElementById('cameraModal')).hide();
            });
    }

    function startCamera(deviceId) {
        if (currentStream) {
            currentStream.getTracks().forEach(track => track.stop());
        }

        const constraints = {
            video: {
                deviceId: deviceId ? { exact: deviceId } : undefined,
                width: { ideal: 640 },
                height: { ideal: 640 },
                facingMode: "user"
            }
        };

        navigator.mediaDevices.getUserMedia(constraints)
            .then(stream => {
                currentStream = stream;
                document.getElementById('cameraVideo').srcObject = stream;
            })
            .catch(err => {
                console.error('Error starting camera:', err);
                alert('Hubo un error al encender la cámara.');
            });
    }

    function stopCamera() {
        if (currentStream) {
            currentStream.getTracks().forEach(track => track.stop());
            currentStream = null;
        }
    }

    function takePhotoAndUpload() {
        const video = document.getElementById('cameraVideo');
        const canvas = document.getElementById('cameraCanvas');
        const context = canvas.getContext('2d');

        const size = Math.min(video.videoWidth, video.videoHeight);
        canvas.width = size;
        canvas.height = size;
        
        const startX = (video.videoWidth - size) / 2;
        const startY = (video.videoHeight - size) / 2;

        context.drawImage(video, startX, startY, size, size, 0, 0, size, size);

        const dataUrl = canvas.toDataURL('image/jpeg', 0.85);
        stopCamera();
        bootstrap.Modal.getInstance(document.getElementById('cameraModal')).hide();

        const formData = new FormData();
        formData.append('photo_base64', dataUrl);
        formData.append('_token', '{{ csrf_token() }}');

        fetch("{{ route('patients.uploadBase64Photo', $patient) }}", {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const img = document.getElementById('patientAvatarImage');
                img.src = data.photo_url;
                img.style.display = 'block';
                const avatarIcon = document.getElementById('patientAvatarIcon');
                if (avatarIcon) avatarIcon.style.display = 'none';
                
                const modalImg = document.getElementById('modalPatientAvatarImage');
                if (modalImg) {
                    modalImg.src = data.photo_url;
                    modalImg.style.display = 'block';
                    const modalIcon = document.getElementById('modalPatientAvatarIcon');
                    if (modalIcon) modalIcon.style.display = 'none';
                }
            } else {
                alert('Error al subir la foto: ' + data.message);
            }
        })
        .catch(err => {
            console.error('Upload Error:', err);
            alert('Fallo la subida de la foto de la cámara.');
        });
    }

    document.getElementById('cameraModal').addEventListener('hidden.bs.modal', function () {
        stopCamera();
    });
</script>

<!-- Modal Asistente Clínico IA -->
<div class="modal fade" id="aiAnalysisModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 rounded-4 shadow-lg" style="background: linear-gradient(180deg, #ffffff 0%, #f8f9fc 100%);">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2">
                    <div class="bg-primary bg-opacity-10 p-2 rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                        <i class="bi bi-robot text-primary fs-4"></i>
                    </div>
                    Ateneo IA (Groq)
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="text-center mb-4">
                    <p class="text-muted">El Asistente Clínico IA analizará el historial completo del paciente (visitas, cirugías, notas médicas y datos demográficos) para ofrecerte un resumen, pronóstico y recomendaciones.</p>
                    <button class="btn btn-primary rounded-pill px-5 py-2 fw-bold shadow-sm" id="btnGenerateAi" style="background: linear-gradient(135deg, #5e6ad2 0%, #7e448b 100%); border:none;" onclick="runAiAnalysis()">
                        <i class="bi bi-stars me-2"></i> Generar Análisis Clínico
                    </button>
                </div>
                
                <div id="aiLoading" class="text-center py-4 d-none">
                    <div class="spinner-grow text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <h6 class="fw-bold text-primary">Analizando historial médico...</h6>
                    <small class="text-muted">Consultando modelo de inteligencia artificial.</small>
                </div>

                <div id="aiResultContainer" class="d-none">
                    <div class="card border-0 shadow-sm rounded-4" style="background-color: rgba(94, 106, 210, 0.05); border: 1px solid rgba(94, 106, 210, 0.2) !important;">
                        <div class="card-body p-4 text-dark" id="aiResultText" style="white-space: pre-line; line-height: 1.6;">
                            <!-- El resultado de la IA va aquí -->
                        </div>
                        <div class="card-footer bg-transparent border-top-0 pt-0 text-end">
                            <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3 py-2 border border-success border-opacity-25"><i class="bi bi-check-circle-fill me-1"></i> Guardado en Comentarios</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function runAiAnalysis() {
        const btn = document.getElementById('btnGenerateAi');
        const loading = document.getElementById('aiLoading');
        const resultContainer = document.getElementById('aiResultContainer');
        const resultText = document.getElementById('aiResultText');

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Procesando...';
        loading.classList.remove('d-none');
        resultContainer.classList.add('d-none');

        fetch("{{ route('patients.analyze', $patient) }}", {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => response.json())
        .then(data => {
            loading.classList.add('d-none');
            btn.innerHTML = '<i class="bi bi-arrow-clockwise me-2"></i> Volver a Analizar';
            btn.disabled = false;

            if (data.success) {
                resultText.textContent = data.analysis;
                resultContainer.classList.remove('d-none');
            } else {
                alert('Error al generar el análisis: ' + data.message);
            }
        })
        .catch(err => {
            console.error('AI Error:', err);
            loading.classList.add('d-none');
            btn.innerHTML = '<i class="bi bi-stars me-2"></i> Generar Análisis Clínico';
            btn.disabled = false;
            alert('Fallo la conexión con el Asistente IA.');
        });
    }
</script>

<!-- Modal: Editar Derivado / Recomendado por -->
<div class="modal fade" id="editRecommendationModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 rounded-4 shadow">
        <div class="modal-header border-bottom-0 pb-0">
            <h5 class="modal-title fw-bold text-dark"><i class="bi bi-person-lines-fill text-primary me-2"></i>Derivado / Recomendado por</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form id="editRecommendationForm" onsubmit="submitRecommendationQuick(event, {{ $patient->id }})">
            @csrf
            <div class="modal-body p-4">
                <label class="form-label text-muted small fw-bold">Profesional o Institución que lo derivó o recomendó</label>
                <input type="text" id="modalRecommendationInput" class="form-control bg-light border-0 shadow-none rounded-3 py-2" value="{{ $patient->recommendation }}" placeholder="Ej: Dr. Pérez, Óptica San Martín, Familiar...">
                <small class="text-muted d-block mt-2">Esta información se conservará permanentemente en la ficha del paciente y estará visible al abrir cualquier consulta.</small>
            </div>
            <div class="modal-footer border-top-0 pt-0 pe-4">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" id="btnSaveRecommendation" class="btn btn-primary rounded-pill px-4 fw-bold">Guardar</button>
            </div>
        </form>
    </div>
  </div>
</div>

<script>
    function openEditRecommendationModal() {
        const modal = new bootstrap.Modal(document.getElementById('editRecommendationModal'));
        modal.show();
    }

    function submitRecommendationQuick(e, patientId) {
        e.preventDefault();
        const inputVal = document.getElementById('modalRecommendationInput').value;
        const btn = document.getElementById('btnSaveRecommendation');
        btn.disabled = true;
        btn.innerText = 'Guardando...';

        axios.post(`/patients/${patientId}/quick-update`, {
            recommendation: inputVal
        }, {
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') }
        })
        .then(res => {
            const displayEl = document.getElementById('displayRecommendationText');
            if (displayEl) displayEl.innerText = inputVal || 'Sin especificar';
            const modalEl = document.getElementById('editRecommendationModal');
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) modal.hide();
            btn.disabled = false;
            btn.innerText = 'Guardar';
            if (typeof spawnToast === 'function') {
                spawnToast('Guardado', 'Derivación / recomendación actualizada.', 'check-circle-fill', 'success text-white');
            }
        })
        .catch(err => {
            alert('Error al guardar la información.');
            btn.disabled = false;
            btn.innerText = 'Guardar';
        });
    }

    function saveMedicalNotesQuick(patientId) {
        const val = document.getElementById('sidebarMedicalNotesInput').value;
        const feedback = document.getElementById('saveMedicalNotesFeedback');
        feedback.innerText = 'Guardando...';
        feedback.className = 'text-primary fw-bold fst-normal';

        axios.post(`/patients/${patientId}/quick-update`, {
            medical_notes: val
        }, {
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') }
        })
        .then(res => {
            feedback.innerText = '¡Guardado con éxito!';
            feedback.className = 'text-success fw-bold fst-normal';
            setTimeout(() => {
                feedback.innerText = 'Solo consultorio';
                feedback.className = 'text-muted fst-italic';
            }, 3000);
        })
        .catch(err => {
            feedback.innerText = 'Error al guardar';
            feedback.className = 'text-danger fw-bold';
        });
    }
</script>
@endsection