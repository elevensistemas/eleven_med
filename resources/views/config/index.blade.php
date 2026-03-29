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
            <h5 class="fw-bold mb-4 text-muted"><i class="bi bi-database me-2"></i>Otras Opciones Generales</h5>
            <div class="text-center py-5 opacity-50">
                <i class="bi bi-tools fs-1 mb-3 d-block"></i>
                <p class="mb-0">Más opciones de configuración se agregarán aquí en futuras actualizaciones del sistema.</p>
            </div>
        </div>
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
