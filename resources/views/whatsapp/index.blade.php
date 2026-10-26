@extends('layouts.admin')

@section('title', 'Vinculación de WhatsApp')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-11 col-xl-10">
        
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-4">
            <div>
                <h4 class="fw-bold text-dark mb-1">
                    <i class="bi bi-whatsapp text-success me-2"></i> Vinculación de WhatsApp y Envíos
                </h4>
                <p class="text-muted small mb-0">Vincula la línea oficial de la clínica escaneando el código QR para enviar recordatorios automáticos.</p>
            </div>
            
            <div id="statusBadgeContainer">
                @if($serviceStatus === 'connected')
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill fs-6">
                        <i class="bi bi-check-circle-fill me-1"></i> Línea Conectada
                    </span>
                @elseif($serviceStatus === 'offline')
                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-3 py-2 rounded-pill fs-6">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i> Servicio en Segundo Plano Offline
                    </span>
                @else
                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-3 py-2 rounded-pill fs-6">
                        <i class="bi bi-phone me-1"></i> Sin Línea Vinculada
                    </span>
                @endif
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success border-0 rounded-4 d-flex align-items-center shadow-sm mb-4">
                <i class="bi bi-check-circle-fill fs-4 me-3 text-success"></i>
                <div class="fw-medium">{{ session('success') }}</div>
            </div>
        @endif

        <div class="card border-0 shadow-sm rounded-4 modern-card">
            <div class="card-body p-4 p-md-5">
                
                <div class="row g-4">
                    
                    <!-- Left Column: Vinculación QR / Estado de la Línea -->
                    <div class="col-lg-6 border-end-lg pe-lg-4">
                        <h5 class="fw-bold mb-1 text-primary">
                            <i class="bi bi-qr-code-scan me-2"></i> 1. Vinculación de Línea Telefónica
                        </h5>
                        <p class="text-muted small mb-4">Escanea el código QR con el WhatsApp de la clínica para enlazar la línea sin intermediarios.</p>

                        <!-- Panel: Conectado -->
                        <div id="panelConnected" class="{{ $serviceStatus === 'connected' ? '' : 'd-none' }}">
                            <div class="p-4 rounded-4 bg-light border border-success-subtle text-center mb-4">
                                <div class="avatar-icon bg-success text-white rounded-circle mx-auto mb-3 shadow-sm d-flex align-items-center justify-content-center" style="width: 64px; height: 64px;">
                                    <i class="bi bi-whatsapp fs-2"></i>
                                </div>
                                <h6 class="text-muted text-uppercase fw-bold mb-1" style="font-size: 0.75rem; letter-spacing: 0.5px;">Número Activo</h6>
                                <h4 class="fw-bold text-dark mb-2" id="connectedPhoneNumber">
                                    {{ $connectedPhone ?? 'Cargando número...' }}
                                </h4>
                                <span class="badge bg-success text-white px-3 py-1 rounded-pill small mb-3">
                                    <i class="bi bi-broadcast me-1"></i> En Línea y Listo para Enviar
                                </span>
                                <p class="text-muted small mb-4">
                                    Los recordatorios programados se emitirán directamente desde esta cuenta de WhatsApp.
                                </p>
                                
                                <div class="d-flex flex-wrap justify-content-center gap-2">
                                    <button type="button" class="btn btn-outline-primary btn-sm rounded-3 px-3" data-bs-toggle="collapse" data-bs-target="#testMessageCollapse">
                                        <i class="bi bi-send me-1"></i> Enviar Mensaje de Prueba
                                    </button>
                                    <button type="button" class="btn btn-outline-danger btn-sm rounded-3 px-3" id="btnDisconnect">
                                        <i class="bi bi-box-arrow-right me-1"></i> Desvincular Línea
                                    </button>
                                </div>
                            </div>

                            <!-- Desplegable Mensaje de Prueba -->
                            <div class="collapse mb-4" id="testMessageCollapse">
                                <div class="card card-body border rounded-4 bg-white shadow-sm p-3">
                                    <h6 class="fw-bold small mb-2 text-primary"><i class="bi bi-chat-dots me-1"></i> Prueba Rápida de Envío</h6>
                                    <div class="mb-2">
                                        <label class="form-label small text-muted mb-1">Teléfono destino (ej: +5491123456789)</label>
                                        <input type="text" id="testPhone" class="form-control form-control-sm rounded-3" placeholder="+54 9 11 ...">
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label small text-muted mb-1">Mensaje de prueba</label>
                                        <input type="text" id="testMessage" class="form-control form-control-sm rounded-3" value="¡Hola! Este es un mensaje de prueba desde Eleven Med.">
                                    </div>
                                    <div class="d-flex justify-content-end">
                                        <button type="button" id="btnSendTest" class="btn btn-success btn-sm rounded-3 px-3">
                                            <i class="bi bi-send-fill me-1"></i> Enviar Ahora
                                        </button>
                                    </div>
                                    <div id="testResultMsg" class="small mt-2 d-none"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Panel: Desconectado / Generar QR -->
                        <div id="panelDisconnected" class="{{ ($serviceStatus !== 'connected' && $serviceStatus !== 'offline') ? '' : 'd-none' }}">
                            
                            <div class="instruction-box p-3 rounded-4 bg-light border mb-4">
                                <h6 class="fw-bold small text-dark mb-2"><i class="bi bi-info-circle-fill text-primary me-1"></i> Pasos para vincular:</h6>
                                <ol class="ps-3 mb-0 text-muted small" style="line-height: 1.6;">
                                    <li>Abre la aplicación de <strong>WhatsApp</strong> en el teléfono móvil.</li>
                                    <li>Toca en <strong>Menú (⋮)</strong> o <strong>Ajustes</strong> y selecciona <strong>Dispositivos vinculados</strong>.</li>
                                    <li>Toca en <strong>Vincular un dispositivo</strong> y apunta tu cámara a este código QR.</li>
                                </ol>
                            </div>

                            <div class="qr-container-wrapper text-center p-4 rounded-4 border bg-white shadow-sm mb-4 position-relative">
                                
                                <!-- Estado Inicial: Botón Generar QR -->
                                <div id="qrInitialState" class="{{ $serviceStatus === 'qr_ready' ? 'd-none' : '' }}">
                                    <div class="mb-3 text-muted">
                                        <i class="bi bi-qr-code fs-1 opacity-50"></i>
                                    </div>
                                    <p class="text-muted small mb-3">Haz clic para generar el código QR de vinculación.</p>
                                    <button type="button" id="btnGenerateQr" class="btn btn-primary fw-bold px-4 py-2 rounded-3 shadow-sm">
                                        <i class="bi bi-qr-code me-2"></i> Generar Código QR
                                    </button>
                                </div>

                                <!-- Spinner de carga -->
                                <div id="qrLoadingState" class="d-none py-4">
                                    <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
                                        <span class="visually-hidden">Cargando...</span>
                                    </div>
                                    <p class="text-muted small mb-0 fw-medium">Conectando con WhatsApp y generando código QR seguro...</p>
                                </div>

                                <!-- Imagen del QR y auto-refresco -->
                                <div id="qrActiveState" class="{{ $serviceStatus === 'qr_ready' ? '' : 'd-none' }}">
                                    <div class="qr-image-box d-inline-block p-2 rounded-3 border bg-white shadow-sm mb-3">
                                        <img id="qrImageElement" src="" alt="WhatsApp QR Code" class="img-fluid" style="width: 260px; height: 260px; object-fit: contain;">
                                    </div>
                                    
                                    <div class="d-flex align-items-center justify-content-center gap-2 text-primary small mb-3">
                                        <div class="spinner-grow spinner-grow-sm text-success" role="status"></div>
                                        <span class="fw-semibold">Esperando escaneo con tu teléfono...</span>
                                    </div>

                                    <div>
                                        <button type="button" id="btnRefreshQr" class="btn btn-outline-secondary btn-sm rounded-3">
                                            <i class="bi bi-arrow-clockwise me-1"></i> Actualizar QR
                                        </button>
                                    </div>
                                </div>

                            </div>
                        </div>

                        <!-- Panel: Servicio Offline -->
                        <div id="panelOffline" class="{{ $serviceStatus === 'offline' ? '' : 'd-none' }}">
                            <div class="alert alert-warning border-0 rounded-4 p-4 shadow-sm mb-4">
                                <div class="d-flex align-items-start gap-3">
                                    <i class="bi bi-exclamation-triangle-fill fs-3 text-warning"></i>
                                    <div>
                                        <h6 class="fw-bold mb-1">Servicio de WhatsApp no detectado</h6>
                                        <p class="small text-muted mb-2">
                                            El microservicio encargado de la conexión con WhatsApp no está corriendo o la URL configurada no responde.
                                        </p>
                                        <div class="small bg-white p-2 rounded-3 border text-dark mb-3">
                                            <strong>En desarrollo local:</strong> Ejecuta <code>arrancar_servidores.bat</code> o en la terminal: <code>cd whatsapp-service && node server.js</code>.
                                        </div>
                                        <button type="button" id="btnRetryService" class="btn btn-warning btn-sm fw-bold px-3 rounded-3">
                                            <i class="bi bi-arrow-clockwise me-1"></i> Reintentar Conexión
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Configuración Avanzada / URL del Microservicio (Opcional) -->
                        <div class="accordion accordion-flush mb-3" id="accordionAdvanced">
                            <div class="accordion-item border-0 bg-transparent">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed px-0 py-2 bg-transparent text-muted small" type="button" data-bs-toggle="collapse" data-bs-target="#collapseAdvanced">
                                        <i class="bi bi-gear me-1"></i> Opciones avanzadas de conexión (Servidor de WhatsApp)
                                    </button>
                                </h2>
                                <div id="collapseAdvanced" class="accordion-collapse collapse" data-bs-parent="#accordionAdvanced">
                                    <div class="accordion-body px-0 pt-2 pb-0">
                                        <div class="p-3 rounded-3 bg-light border small">
                                            <div class="mb-2">
                                                <label class="form-label fw-bold text-muted mb-1" style="font-size: 0.75rem;">URL del Microservicio (Local o Cloud):</label>
                                                <input type="url" name="api_url" form="formWhatsappSettings" class="form-control form-control-sm rounded-2" value="{{ old('api_url', $config->api_url ?? config('services.whatsapp.url')) }}" placeholder="http://127.0.0.1:3001">
                                                <span class="form-text" style="font-size: 0.7rem;">Por defecto se conecta a <code>{{ config('services.whatsapp.url') ?? 'http://127.0.0.1:3001' }}</code>. Puedes apuntarlo a tu VPS o servidor en la nube.</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Right Column: Reglas de Envío Automático -->
                    <div class="col-lg-6 ps-lg-4">
                        <form id="formWhatsappSettings" action="{{ route('whatsapp.store') }}" method="POST">
                            @csrf

                            <h5 class="fw-bold mb-1 text-primary">
                                <i class="bi bi-clock-history me-2"></i> 2. Reglas de Envío Automático
                            </h5>
                            <p class="text-muted small mb-4">Configura en qué momento el servidor enviará los recordatorios a los pacientes.</p>

                            <div class="row g-3 mb-3">
                                <div class="col-sm-6">
                                    <label class="form-label fw-bold small">¿Cuántos días antes?</label>
                                    <select name="reminder_days_before" class="form-select rounded-3 shadow-none">
                                        <option value="1" {{ old('reminder_days_before', $config->reminder_days_before) == 1 ? 'selected' : '' }}>1 día antes</option>
                                        <option value="2" {{ old('reminder_days_before', $config->reminder_days_before) == 2 ? 'selected' : '' }}>2 días antes</option>
                                        <option value="3" {{ old('reminder_days_before', $config->reminder_days_before) == 3 ? 'selected' : '' }}>3 días antes</option>
                                        <option value="7" {{ old('reminder_days_before', $config->reminder_days_before) == 7 ? 'selected' : '' }}>1 semana antes</option>
                                    </select>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label fw-bold small">¿A qué hora?</label>
                                    <input type="time" name="reminder_time" class="form-control rounded-3 shadow-none" value="{{ old('reminder_time', substr($config->reminder_time, 0, 5)) }}" required>
                                    <small class="text-muted" style="font-size: 0.7rem;">Hora de disparo programado</small>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold small">Plantilla del Mensaje de Recordatorio</label>
                                <textarea id="messageTemplate" name="message_template" rows="5" class="form-control rounded-3 shadow-none" placeholder="Hola {nombre}, te recordamos tu turno...">{{ old('message_template', $config->message_template) }}</textarea>
                                
                                <div class="mt-2">
                                    <span class="text-muted small fw-medium me-1" style="font-size: 0.75rem;">Variables (haz clic para insertar):</span>
                                    <div class="d-flex flex-wrap gap-1 mt-1">
                                        <button type="button" class="btn btn-sm btn-light border py-0 px-2 rounded-2 var-btn" style="font-size: 0.75rem;" data-var="{nombre}"><code>{nombre}</code></button>
                                        <button type="button" class="btn btn-sm btn-light border py-0 px-2 rounded-2 var-btn" style="font-size: 0.75rem;" data-var="{fecha_turno}"><code>{fecha_turno}</code></button>
                                        <button type="button" class="btn btn-sm btn-light border py-0 px-2 rounded-2 var-btn" style="font-size: 0.75rem;" data-var="{hora_turno}"><code>{hora_turno}</code></button>
                                        <button type="button" class="btn btn-sm btn-light border py-0 px-2 rounded-2 var-btn" style="font-size: 0.75rem;" data-var="{medico}"><code>{medico}</code></button>
                                        <button type="button" class="btn btn-sm btn-light border py-0 px-2 rounded-2 var-btn" style="font-size: 0.75rem;" data-var="{clinica}"><code>{clinica}</code></button>
                                    </div>
                                </div>
                            </div>

                            <div class="p-3 rounded-4 bg-light border d-flex flex-column flex-sm-row justify-content-between align-items-center gap-3">
                                <div class="form-check form-switch fs-5 mb-0 d-flex align-items-center gap-2">
                                    <input class="form-check-input mt-0 shadow-sm" type="checkbox" name="is_active" role="switch" id="whatsappSwitch" value="1" {{ old('is_active', $config->is_active) ? 'checked' : '' }} style="height: 24px; width: 48px; cursor: pointer;">
                                    <label class="form-check-label fw-bold ms-2 text-dark fs-6" for="whatsappSwitch">Activar Envío Automático</label>
                                </div>
                                
                                <button type="submit" class="btn btn-success fw-bold px-4 py-2 shadow-sm rounded-3 w-100 w-sm-auto">
                                    <i class="bi bi-save me-2"></i> Guardar Reglas
                                </button>
                            </div>
                        </form>
                    </div>

                </div>

            </div>
        </div>

        <div class="text-center mt-4">
            <small class="text-muted">
                <i class="bi bi-shield-check text-success me-1"></i> Conexión segura punto a punto. La sesión se mantiene sincronizada con el dispositivo oficial de la clínica.
            </small>
        </div>

    </div>
</div>

<style>
    .border-end-lg { border-right: 1px solid #f1f5f9; }
    @media (max-width: 991px) {
        .border-end-lg { border-right: none; }
    }
    .var-btn:hover {
        background-color: #e2e8f0;
        cursor: pointer;
    }
</style>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const btnGenerateQr = document.getElementById('btnGenerateQr');
    const btnRefreshQr = document.getElementById('btnRefreshQr');
    const btnDisconnect = document.getElementById('btnDisconnect');
    const btnRetryService = document.getElementById('btnRetryService');
    const btnSendTest = document.getElementById('btnSendTest');

    const panelConnected = document.getElementById('panelConnected');
    const panelDisconnected = document.getElementById('panelDisconnected');
    const panelOffline = document.getElementById('panelOffline');

    const qrInitialState = document.getElementById('qrInitialState');
    const qrLoadingState = document.getElementById('qrLoadingState');
    const qrActiveState = document.getElementById('qrActiveState');
    const qrImageElement = document.getElementById('qrImageElement');
    const connectedPhoneNumber = document.getElementById('connectedPhoneNumber');
    const statusBadgeContainer = document.getElementById('statusBadgeContainer');

    let pollInterval = null;

    // Insertar variables en plantilla
    document.querySelectorAll('.var-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const varText = this.getAttribute('data-var');
            const textarea = document.getElementById('messageTemplate');
            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            const text = textarea.value;
            textarea.value = text.substring(0, start) + varText + text.substring(end);
            textarea.focus();
            textarea.selectionStart = textarea.selectionEnd = start + varText.length;
        });
    });

    // Actualizar badges visuales
    function updateBadge(status) {
        if (!statusBadgeContainer) return;
        if (status === 'connected') {
            statusBadgeContainer.innerHTML = `
                <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill fs-6 animate__animated animate__fadeIn">
                    <i class="bi bi-check-circle-fill me-1"></i> Línea Conectada
                </span>
            `;
        } else if (status === 'offline') {
            statusBadgeContainer.innerHTML = `
                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-3 py-2 rounded-pill fs-6">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i> Servicio en Segundo Plano Offline
                </span>
            `;
        } else {
            statusBadgeContainer.innerHTML = `
                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-3 py-2 rounded-pill fs-6">
                    <i class="bi bi-phone me-1"></i> Sin Línea Vinculada
                </span>
            `;
        }
    }

    // Consultar estado periódico mientras espera escaneo
    function startPolling() {
        if (pollInterval) clearInterval(pollInterval);

        pollInterval = setInterval(async () => {
            try {
                const response = await fetch("{{ route('whatsapp.status') }}");
                const data = await response.json();

                if (data.status === 'connected') {
                    clearInterval(pollInterval);
                    pollInterval = null;
                    showConnected(data.phone || 'Línea Conectada');
                } else if (data.status === 'offline') {
                    clearInterval(pollInterval);
                    pollInterval = null;
                    showOffline();
                }
            } catch (e) {
                console.error("Error polling WhatsApp status:", e);
            }
        }, 2500);
    }

    function showConnected(phone) {
        panelConnected.classList.remove('d-none');
        panelDisconnected.classList.add('d-none');
        panelOffline.classList.add('d-none');
        if (connectedPhoneNumber) {
            connectedPhoneNumber.textContent = phone;
        }
        updateBadge('connected');
    }

    function showDisconnected() {
        panelConnected.classList.add('d-none');
        panelDisconnected.classList.remove('d-none');
        panelOffline.classList.add('d-none');
        qrInitialState.classList.remove('d-none');
        qrLoadingState.classList.add('d-none');
        qrActiveState.classList.add('d-none');
        updateBadge('disconnected');
    }

    function showOffline() {
        panelConnected.classList.add('d-none');
        panelDisconnected.classList.add('d-none');
        panelOffline.classList.remove('d-none');
        updateBadge('offline');
    }

    // Obtener QR
    async function fetchQrCode() {
        qrInitialState.classList.add('d-none');
        qrActiveState.classList.add('d-none');
        qrLoadingState.classList.remove('d-none');

        try {
            const response = await fetch("{{ route('whatsapp.qr') }}");
            const data = await response.json();

            if (data.status === 'connected') {
                showConnected(data.phone || 'Línea Conectada');
                return;
            }

            if (data.qr) {
                qrImageElement.src = data.qr;
                qrLoadingState.classList.add('d-none');
                qrActiveState.classList.remove('d-none');
                startPolling();
            } else {
                // Si aún no está listo el QR, reintentar en 2 segundos
                setTimeout(fetchQrCode, 2000);
            }
        } catch (err) {
            console.error("Error obteniendo QR:", err);
            qrLoadingState.classList.add('d-none');
            showOffline();
        }
    }

    if (btnGenerateQr) {
        btnGenerateQr.addEventListener('click', fetchQrCode);
    }

    if (btnRefreshQr) {
        btnRefreshQr.addEventListener('click', fetchQrCode);
    }

    if (btnRetryService) {
        btnRetryService.addEventListener('click', function() {
            location.reload();
        });
    }

    // Desvincular línea
    if (btnDisconnect) {
        btnDisconnect.addEventListener('click', async function () {
            if (!confirm('¿Estás seguro de que deseas desvincular esta línea telefónica de WhatsApp?')) {
                return;
            }

            btnDisconnect.disabled = true;
            btnDisconnect.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Desvinculando...';

            try {
                const response = await fetch("{{ route('whatsapp.disconnect') }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json'
                    }
                });

                const data = await response.json();
                if (data.success || data.status === 'disconnected') {
                    showDisconnected();
                } else {
                    alert(data.error || 'No se pudo desvincular la línea.');
                }
            } catch (err) {
                console.error("Error desconectando:", err);
                alert('Ocurrió un error al contactar al servidor.');
            } finally {
                btnDisconnect.disabled = false;
                btnDisconnect.innerHTML = '<i class="bi bi-box-arrow-right me-1"></i> Desvincular Línea';
            }
        });
    }

    // Envío de prueba
    if (btnSendTest) {
        btnSendTest.addEventListener('click', async function () {
            const phone = document.getElementById('testPhone').value.trim();
            const message = document.getElementById('testMessage').value.trim();
            const resultMsg = document.getElementById('testResultMsg');

            if (!phone || !message) {
                alert('Por favor, ingresa el número de teléfono y el mensaje.');
                return;
            }

            btnSendTest.disabled = true;
            btnSendTest.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Enviando...';
            resultMsg.className = 'small mt-2 d-none';

            try {
                const response = await fetch("{{ route('whatsapp.testSend') }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ phone, message })
                });

                const data = await response.json();
                resultMsg.classList.remove('d-none');

                if (data.success) {
                    resultMsg.className = 'small mt-2 text-success fw-bold';
                    resultMsg.innerHTML = '<i class="bi bi-check-circle me-1"></i> ' + (data.message || 'Mensaje enviado.');
                } else {
                    resultMsg.className = 'small mt-2 text-danger fw-bold';
                    resultMsg.innerHTML = '<i class="bi bi-exclamation-circle me-1"></i> ' + (data.error || 'No se pudo enviar.');
                }
            } catch (err) {
                resultMsg.className = 'small mt-2 text-danger fw-bold';
                resultMsg.innerHTML = '<i class="bi bi-exclamation-circle me-1"></i> Error de conexión al enviar.';
            } finally {
                btnSendTest.disabled = false;
                btnSendTest.innerHTML = '<i class="bi bi-send-fill me-1"></i> Enviar Ahora';
            }
        });
    }
});
</script>
@endpush
@endsection
