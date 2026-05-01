@extends('layouts.admin')

@section('title', 'Módulo de Configuración')
@section('subtitle', 'Opciones globales del sistema y apariencia')

@section('content')
<div class="row">
    <div class="col-lg-6">
        <div class="modern-card p-4">
            <h5 class="fw-bold mb-4"><i class="bi bi-display me-2 text-primary"></i>Apariencia del Sistema</h5>
            
            <div class="d-flex justify-content-between align-items-center p-3 border rounded-4 mb-3" style="background: rgba(0,0,0,0.02);">
                <div>
                    <strong class="d-block text-dark">Luminosidad (Modo Oscuro)</strong>
                    <small class="text-muted">Activa esta opción para proteger tu vista en guardias nocturnas.</small>
                </div>
                <!-- Big Toggle Switch using Bootstrap Forms -->
                <div class="form-check form-switch fs-3 m-0">
                    <input class="form-check-input cursor-pointer" type="checkbox" role="switch" id="masterThemeToggle" onchange="toggleThemeGlobal(); updateSwitchUI();">
                </div>
            </div>
            
            <div class="alert alert-info border-0 bg-info bg-opacity-10 text-dark rounded-4 p-3 mt-4" role="alert">
                <i class="bi bi-info-circle-fill text-info fs-4 me-2 align-middle"></i>
                Dato: También podés alternar el tema en cualquier momento usando el botón de <strong>Luna/Sol</strong> ubicado en la parte superior derecha de tu pantalla principal.
            </div>
        </div>
    </div>
    
    <div class="col-lg-6">
        <div class="modern-card p-4">
            <h5 class="fw-bold mb-4 text-dark"><i class="bi bi-database-fill-down me-2 text-primary"></i>Gestión de Base de Datos</h5>
            
            <div class="d-flex flex-column p-4 border rounded-4 bg-light mb-3" style="border: 1px solid rgba(0,0,0,0.05) !important;">
                <div class="d-flex align-items-center mb-3">
                    <div class="text-white rounded-circle d-flex align-items-center justify-content-center shadow-sm me-3" style="width: 50px; height: 50px; background: linear-gradient(135deg, #20c997 0%, #198754 100%);">
                        <i class="bi bi-file-earmark-excel-fill fs-4"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold text-dark mb-1">Exportar Pacientes a Excel</h6>
                        <small class="text-muted d-block" style="line-height: 1.3;">Descarga un extracto consolidado de pacientes, historia clínica y cirugías.</small>
                    </div>
                </div>
                <a href="{{ route('config.export.patients') }}" class="btn text-white fw-bold p-2 shadow-sm" style="background: linear-gradient(135deg, #20c997 0%, #198754 100%); border: none;">
                    <i class="bi bi-download me-1"></i> Descargar Base (.CSV)
                </a>
            </div>
            
            <div class="text-center py-4 mt-2 opacity-50">
                <i class="bi bi-tools fs-1 mb-3 d-block"></i>
                <p class="mb-0 small">Más módulos de backoffice llegarán en próximos updates.</p>
            </div>
        </div>
    </div>
</div>

<!-- Obras Sociales Row -->
<style>
    .os-card {
        background: #f8faff;
        transition: all 0.2s ease;
    }
    .os-card-text {
        color: #212529;
    }
    .theme-dark .os-card {
        background: linear-gradient(135deg, #1e2640 0%, #2a3456 100%) !important;
        border-color: #3b4566 !important;
    }
    .theme-dark .os-card-text {
        color: #ffffff !important;
    }
    .theme-dark .os-card-badge {
        background-color: rgba(255,255,255,0.1) !important;
        color: #d1d5db !important;
    }
    .theme-dark .os-card-btn {
        background-color: rgba(220, 53, 69, 0.1) !important;
        color: #ff6b6b !important;
    }
    .theme-dark .os-card-btn:hover {
        background-color: #dc3545 !important;
        color: #fff !important;
    }
</style>
<div class="row mt-4">
    <div class="col-lg-12">
        <div class="modern-card p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="fw-bold text-dark mb-0"><i class="bi bi-hospital me-2 text-primary"></i>Gestión de Obras Sociales</h5>
                <button class="btn btn-primary rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#newObraSocialModal" style="background: linear-gradient(135deg, #5e6ad2 0%, #7e448b 100%); border:none;">
                    <i class="bi bi-plus-lg me-1"></i> Nueva Cobertura
                </button>
            </div>

            <div class="row g-3">
                @forelse($obrasSociales as $os)
                <div class="col-md-3">
                    <div class="card border border-light shadow-sm rounded-4 h-100 os-card">
                        <div class="card-body d-flex justify-content-between align-items-center p-3">
                            <div class="d-flex align-items-center gap-2">
                                <span style="display:inline-block; width:14px; height:14px; border-radius:50%; background-color:{{ $os->color ?? '#5e6ad2' }}; box-shadow: 0 2px 4px rgba(0,0,0,0.1);"></span>
                                <span class="fw-bold os-card-text" style="font-size: 0.95rem;">{{ $os->name }}</span>
                            </div>
                            @if(!in_array(strtolower($os->name), ['particular', 'osde']))
                            <form action="{{ route('config.obras_sociales.destroy', $os) }}" method="POST" onsubmit="return confirm('¿Seguro que deseas eliminar esta obra social?');" class="m-0">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-light text-danger rounded-circle p-1 os-card-btn" title="Eliminar"><i class="bi bi-trash"></i></button>
                            </form>
                            @else
                                <span class="badge bg-secondary bg-opacity-10 text-secondary os-card-badge" style="font-size:0.65rem;">Sistema</span>
                            @endif
                        </div>
                    </div>
                </div>
                @empty
                <div class="col-12">
                    <div class="text-center py-4 text-muted border rounded-4 bg-light">
                        <i class="bi bi-info-circle fs-3 d-block mb-2"></i>
                        Aún no hay obras sociales registradas.
                    </div>
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<!-- Gestión de Memoria IA Row -->
<div class="row mt-4">
    <div class="col-lg-12">
        <div class="modern-card p-4 border-start border-4 border-info">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="fw-bold text-dark mb-0"><i class="bi bi-robot me-2 text-info"></i>Gestión de Memoria IA (RAG)</h5>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="--bs-table-border-color: #cbd5e1; border-bottom: 2px solid #cbd5e1;">
                    <thead class="bg-light">
                        <tr>
                            <th scope="col" class="py-3 ps-4 text-muted text-uppercase fw-bold" style="font-size: 0.75rem; letter-spacing: 0.5px;">Médico / Usuario</th>
                            <th scope="col" class="py-3 text-muted text-uppercase fw-bold" style="font-size: 0.75rem; letter-spacing: 0.5px;">Tamaño Archivo</th>
                            <th scope="col" class="py-3 text-muted text-uppercase fw-bold" style="font-size: 0.75rem; letter-spacing: 0.5px;">Última Modificación</th>
                            <th scope="col" class="py-3 text-end pe-4 text-muted text-uppercase fw-bold" style="font-size: 0.75rem; letter-spacing: 0.5px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($aiMemories ?? [] as $memory)
                        <tr class="border-bottom">
                            <td class="ps-4 py-3 fw-bold text-dark">
                                <i class="bi bi-person-circle text-muted me-2"></i> {{ $memory['user']->name }}
                            </td>
                            <td class="py-3 text-muted small">{{ $memory['size'] }}</td>
                            <td class="py-3 text-muted small">{{ $memory['last_modified'] }}</td>
                            <td class="py-3 text-end pe-4">
                                <button type="button" class="btn btn-sm btn-light text-primary rounded-pill px-3 fw-bold me-2" data-bs-toggle="modal" data-bs-target="#viewMemoryModal{{ $memory['user']->id }}">
                                    <i class="bi bi-eye"></i> Ver Cuaderno
                                </button>
                                <form action="{{ route('config.ai_memory.clear', $memory['user']) }}" method="POST" onsubmit="return confirm('¿Seguro que deseas formatear la memoria de la IA para este usuario? Olvidará todas sus reglas.');" class="d-inline m-0">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-light text-danger rounded-pill px-3 fw-bold" title="Limpiar Memoria"><i class="bi bi-eraser"></i> Limpiar</button>
                                </form>
                            </td>
                        </tr>
                        
                        <!-- Modal Ver Memoria -->
                        <div class="modal fade" id="viewMemoryModal{{ $memory['user']->id }}" tabindex="-1">
                            <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
                                <div class="modal-content border-0 rounded-4 shadow">
                                    <div class="modal-header border-bottom-0 pb-0">
                                        <h5 class="modal-title fw-bold">Memoria y Chat de IA: {{ $memory['user']->name }}</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body p-0 mt-3">
                                        <ul class="nav nav-tabs px-3 border-bottom-0" id="memoryTabs{{ $memory['user']->id }}" role="tablist">
                                            <li class="nav-item" role="presentation">
                                                <button class="nav-link active fw-bold text-dark border-0 rounded-top bg-light" id="rag-tab-{{ $memory['user']->id }}" data-bs-toggle="tab" data-bs-target="#rag-{{ $memory['user']->id }}" type="button" role="tab">Hechos Guardados (RAG)</button>
                                            </li>
                                            <li class="nav-item" role="presentation">
                                                <button class="nav-link fw-bold text-muted border-0 bg-transparent" id="chat-tab-{{ $memory['user']->id }}" data-bs-toggle="tab" data-bs-target="#chat-{{ $memory['user']->id }}" type="button" role="tab">Historial de Chat</button>
                                            </li>
                                        </ul>
                                        <div class="tab-content bg-light p-3" id="memoryTabContent{{ $memory['user']->id }}" style="min-height: 300px;">
                                            <div class="tab-pane fade show active" id="rag-{{ $memory['user']->id }}" role="tabpanel">
                                                <div class="bg-dark text-light p-3 rounded-3 font-monospace" style="white-space: pre-wrap; font-size: 0.85rem; max-height: 400px; overflow-y: auto;">{{ $memory['content'] }}</div>
                                            </div>
                                            <div class="tab-pane fade" id="chat-{{ $memory['user']->id }}" role="tabpanel">
                                                <div class="chat-history-container d-flex flex-column gap-2" style="max-height: 400px; overflow-y: auto;">
                                                    @if(empty($memory['chat_history']))
                                                        <div class="text-center text-muted py-4">No hay historial de chat registrado en el servidor para este usuario.</div>
                                                    @else
                                                        @foreach($memory['chat_history'] as $msg)
                                                            <div class="d-flex {{ $msg['role'] == 'user' ? 'justify-content-end' : 'justify-content-start' }}">
                                                                <div class="p-2 rounded-3 {{ $msg['role'] == 'user' ? 'bg-primary text-white text-end' : 'bg-white border text-dark' }}" style="max-width: 80%; font-size: 0.9rem;">
                                                                    <strong>{{ $msg['role'] == 'user' ? 'Usuario:' : 'IA:' }}</strong><br>
                                                                    {{ Str::limit(strip_tags($msg['content']), 300) }}
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer border-top-0 pt-0 bg-light rounded-bottom-4">
                                        <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cerrar</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center py-4 text-muted">
                                <i class="bi bi-robot fs-3 d-block mb-2 opacity-50"></i>
                                Ningún médico le ha enseñado reglas al asistente IA todavía.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Nueva Obra Social -->
<div class="modal fade" id="newObraSocialModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('config.obras_sociales.store') }}" method="POST" class="modal-content border-0 rounded-4 shadow">
            @csrf
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold">Agregar Obra Social</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">Nombre de la Cobertura / Prepaga *</label>
                    <input type="text" name="name" class="form-control bg-light border-0 shadow-none" placeholder="Ej: Swiss Medical" required>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">Color Identificatorio</label>
                    <div class="d-flex align-items-center gap-3">
                        <input type="color" name="color" class="form-control form-control-color border-0 p-1 shadow-sm rounded-circle" value="#5e6ad2" title="Elegir color" style="width: 45px; height: 45px; cursor: pointer; background: none;">
                        <small class="text-muted">Se usará para destacar esta obra social en la lista de pacientes.</small>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary rounded-pill px-4" style="background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%); border:none;">Guardar</button>
            </div>
        </form>
    </div>
</div>


<script>
    function updateSwitchUI() {
        // Only run after DOM is fully loaded or from the callback
        const toggle = document.getElementById('masterThemeToggle');
        if (toggle) {
            toggle.checked = document.body.classList.contains('theme-dark');
        }
    }

    // Call it right away internally
    document.addEventListener('DOMContentLoaded', updateSwitchUI);
</script>
@endsection
