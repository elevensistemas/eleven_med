@extends('layouts.admin')

@section('title', 'Configuración de WhatsApp')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold text-dark mb-0"><i class="bi bi-whatsapp text-success me-2"></i> Configuración de WhatsApp API</h4>
        </div>

        <div class="card border-0 shadow-sm rounded-4 modern-card">
            <div class="card-body p-4 p-md-5">
                
                @if(session('success'))
                    <div class="alert alert-success border-0 rounded-3 d-flex align-items-center shadow-sm">
                        <i class="bi bi-check-circle-fill fs-4 me-3"></i>
                        <div>{{ session('success') }}</div>
                    </div>
                @endif

                <form action="{{ route('whatsapp.store') }}" method="POST">
                    @csrf
                    
                    <div class="row g-4">
                        <!-- Left Column: Credenciales -->
                        <div class="col-md-6 border-end-md pe-md-4">
                            <h5 class="fw-bold mb-3 text-primary"><i class="bi bi-key me-2"></i> 1. Credenciales de Conexión</h5>
                            <p class="text-muted small mb-4">Ingresa los datos del proveedor API para conectar la clínica con WhatsApp.</p>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold small">API URL</label>
                                <input type="url" name="api_url" class="form-control" value="{{ old('api_url', $config->api_url) }}" placeholder="https://api.tu-proveedor.com" style="border-radius: 8px;">
                                @error('api_url') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold small">Instance ID / Número de Teléfono</label>
                                <input type="text" name="instance_id" class="form-control" value="{{ old('instance_id', $config->instance_id) }}" placeholder="Ej: instance_12345" style="border-radius: 8px;">
                                @error('instance_id') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold small">Token de Seguridad (Bearer)</label>
                                <div class="input-group">
                                    <span class="input-group-text border-end-0 bg-transparent" style="border-radius: 8px 0 0 8px;"><i class="bi bi-lock"></i></span>
                                    <input type="password" name="token" class="form-control border-start-0" value="{{ old('token', $config->token) }}" placeholder="••••••••••••" style="border-radius: 0 8px 8px 0;">
                                </div>
                                @error('token') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <!-- Right Column: Reglas de Envío -->
                        <div class="col-md-6 ps-md-4">
                            <h5 class="fw-bold mb-3 text-primary"><i class="bi bi-clock-history me-2"></i> 2. Reglas de Envío Automático</h5>
                            <p class="text-muted small mb-4">Configura en qué momento el servidor enviará los mensajes a los pacientes.</p>

                            <div class="row">
                                <div class="col-6 mb-3">
                                    <label class="form-label fw-bold small">¿Cuántos días antes?</label>
                                    <select name="reminder_days_before" class="form-select" style="border-radius: 8px;">
                                        <option value="1" {{ old('reminder_days_before', $config->reminder_days_before) == 1 ? 'selected' : '' }}>1 día antes</option>
                                        <option value="2" {{ old('reminder_days_before', $config->reminder_days_before) == 2 ? 'selected' : '' }}>2 días antes</option>
                                        <option value="3" {{ old('reminder_days_before', $config->reminder_days_before) == 3 ? 'selected' : '' }}>3 días antes</option>
                                        <option value="7" {{ old('reminder_days_before', $config->reminder_days_before) == 7 ? 'selected' : '' }}>1 semana antes</option>
                                    </select>
                                </div>
                                <div class="col-6 mb-3">
                                    <label class="form-label fw-bold small">¿A qué hora?</label>
                                    <input type="time" name="reminder_time" class="form-control" value="{{ old('reminder_time', substr($config->reminder_time, 0, 5)) }}" required style="border-radius: 8px;">
                                    <small class="text-muted" style="font-size: 0.7rem;">Hora de disparo masivo</small>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold small">Plantilla del Mensaje</label>
                                <textarea name="message_template" rows="4" class="form-control" placeholder="Hola {nombre}, te recordamos tu turno..." style="border-radius: 8px;">{{ old('message_template', $config->message_template) }}</textarea>
                                <div class="form-text small" style="font-size: 0.75rem;">
                                    Variables disponibles: <br>
                                    <code>{nombre}</code>, <code>{fecha_turno}</code>, <code>{hora_turno}</code>, <code>{medico}</code>, <code>{clinica}</code>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4" style="border-color: rgba(0,0,0,0.05);">

                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center bg-light p-3 rounded-4 border">
                        <div class="form-check form-switch fs-5 mb-3 mb-md-0 d-flex align-items-center gap-2">
                            <input class="form-check-input mt-0 shadow-sm" type="checkbox" name="is_active" role="switch" id="whatsappSwitch" value="1" {{ old('is_active', $config->is_active) ? 'checked' : '' }} style="height: 24px; width: 48px; cursor: pointer;">
                            <label class="form-check-label fw-bold ms-2 text-dark" for="whatsappSwitch">Activar Envío Automático (Cron)</label>
                        </div>
                        <button type="submit" class="btn btn-success fw-bold px-4 py-2 shadow-sm" style="border-radius: 8px;">
                            <i class="bi bi-save me-2"></i> Guardar Configuración
                        </button>
                    </div>

                </form>

            </div>
        </div>
        
        <div class="text-center mt-4">
            <small class="text-muted"><i class="bi bi-info-circle text-primary"></i> Las tareas programadas (Cron Jobs) deben estar configuradas en el servidor Windows/Linux para que los envíos automáticos funcionen.</small>
        </div>
    </div>
</div>

<style>
    .border-end-md { border-right: 1px solid #f0f0f0; }
    @media (max-width: 768px) {
        .border-end-md { border-right: none; }
    }
</style>
@endsection
