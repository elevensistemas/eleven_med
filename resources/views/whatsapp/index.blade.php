@extends('layouts.admin')

@section('title', 'Notificaciones y Recordatorios de Turnos')
@section('subtitle', 'Configurá cuándo, cómo y a qué pacientes se envían confirmaciones y recordatorios por WhatsApp')

@section('content')
<div class="row justify-content-center">
    <div class="col-12">

        <!-- Encabezado Superior -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-4">
            <div>
                <h4 class="fw-bold text-dark mb-1">
                    <i class="bi bi-whatsapp text-success me-2"></i> Notificaciones y Recordatorios de Turnos
                </h4>
                <p class="text-muted small mb-0">Seleccioná qué avisos salen al instante, cuáles 24/48 hs antes, para qué agendas y supervisá la bandeja de envíos en vivo.</p>
            </div>

            <div id="statusBadgeContainer">
                @if($serviceStatus === 'connected')
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill fs-6">
                        <i class="bi bi-check-circle-fill me-1"></i> Línea Conectada {{ $connectedPhone ? '(' . $connectedPhone . ')' : '' }}
                    </span>
                @elseif($serviceStatus === 'offline')
                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-3 py-2 rounded-pill fs-6">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i> Conector PC Offline
                    </span>
                @else
                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-3 py-2 rounded-pill fs-6">
                        <i class="bi bi-phone me-1"></i> Esperando Escaneo QR
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

        <div class="row g-4 mb-4">
            <!-- COLUMNA IZQUIERDA (4 cols): VINCULACIÓN DE LÍNEA Y PRUEBA -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 modern-card h-100">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-1 text-primary">
                            <i class="bi bi-qr-code-scan me-2"></i> 1. Línea Vinculada
                        </h5>
                        <p class="text-muted small mb-3">Estado del celular enlazado con la PC del consultorio.</p>

                        <!-- Panel: Conectado -->
                        <div id="panelConnected" class="{{ $serviceStatus === 'connected' ? '' : 'd-none' }}">
                            <div class="p-4 rounded-4 bg-light border border-success-subtle text-center mb-3">
                                <div class="avatar-icon bg-success text-white rounded-circle mx-auto mb-2 shadow-sm d-flex align-items-center justify-content-center" style="width: 56px; height: 56px;">
                                    <i class="bi bi-whatsapp fs-3"></i>
                                </div>
                                <small class="text-muted text-uppercase fw-bold d-block" style="font-size: 0.72rem;">Número Activo del Consultorio</small>
                                <h5 class="fw-bold text-dark my-1" id="connectedPhoneNumber">
                                    {{ $connectedPhone ?? 'Conectado' }}
                                </h5>
                                <span class="badge bg-success text-white px-3 py-1 rounded-pill small mb-3">
                                    <i class="bi bi-broadcast me-1"></i> En Línea y Sincronizado
                                </span>

                                <div class="d-flex flex-wrap justify-content-center gap-2 mt-2">
                                    <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-3 fw-semibold" data-bs-toggle="collapse" data-bs-target="#testMessageCollapse">
                                        <i class="bi bi-send me-1"></i> Mensaje de Prueba
                                    </button>
                                    <button type="button" class="btn btn-outline-danger btn-sm rounded-pill px-3 fw-semibold" id="btnDisconnect">
                                        <i class="bi bi-box-arrow-right me-1"></i> Desvincular
                                    </button>
                                </div>
                            </div>

                            <!-- Desplegable Mensaje de Prueba -->
                            <div class="collapse mb-3" id="testMessageCollapse">
                                <div class="card card-body border rounded-4 bg-white shadow-sm p-3">
                                    <h6 class="fw-bold small mb-2 text-primary"><i class="bi bi-chat-dots me-1"></i> Prueba Rápida de Envío</h6>
                                    <div class="mb-2">
                                        <label class="form-label small text-muted mb-1">Celular destino (ej: 1130762223)</label>
                                        <input type="text" id="testPhone" class="form-control form-control-sm rounded-3" placeholder="11 ...">
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label small text-muted mb-1">Mensaje</label>
                                        <input type="text" id="testMessage" class="form-control form-control-sm rounded-3" value="¡Hola! Mensaje de prueba desde el Consultorio Dr. Cortalezzi.">
                                    </div>
                                    <div class="d-flex justify-content-end">
                                        <button type="button" id="btnSendTest" class="btn btn-success btn-sm rounded-pill px-3 fw-bold">
                                            <i class="bi bi-send-fill me-1"></i> Enviar Ahora
                                        </button>
                                    </div>
                                    <div id="testResultMsg" class="small mt-2 d-none"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Panel: Desconectado / Generar QR -->
                        <div id="panelDisconnected" class="{{ ($serviceStatus !== 'connected' && $serviceStatus !== 'offline') ? '' : 'd-none' }}">
                            <div class="qr-container-wrapper text-center p-3 rounded-4 border bg-white shadow-sm mb-3">
                                <div id="qrInitialState" class="{{ $serviceStatus === 'qr_ready' ? 'd-none' : '' }}">
                                    <i class="bi bi-qr-code fs-1 text-muted opacity-50 d-block mb-2"></i>
                                    <p class="text-muted small mb-3">Haga clic para mostrar el código QR de vinculación.</p>
                                    <button type="button" id="btnGenerateQr" class="btn btn-primary fw-bold px-4 py-2 rounded-pill shadow-sm">
                                        <i class="bi bi-qr-code me-1"></i> Generar Código QR
                                    </button>
                                </div>

                                <div id="qrLoadingState" class="d-none py-4">
                                    <div class="spinner-border text-primary mb-2" role="status"></div>
                                    <p class="text-muted small mb-0">Generando código QR seguro...</p>
                                </div>

                                <div id="qrActiveState" class="{{ $serviceStatus === 'qr_ready' ? '' : 'd-none' }}">
                                    <div class="d-inline-block p-2 rounded-3 border bg-white shadow-sm mb-2">
                                        <img id="qrImageElement" src="" alt="WhatsApp QR Code" class="img-fluid" style="width: 230px; height: 230px; object-fit: contain;">
                                    </div>
                                    <div class="small text-success fw-bold mb-2">Esperando escaneo desde el celular...</div>
                                    <button type="button" id="btnRefreshQr" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                                        <i class="bi bi-arrow-clockwise me-1"></i> Actualizar QR
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Panel: Conector Offline -->
                        <div id="panelOffline" class="{{ $serviceStatus === 'offline' ? '' : 'd-none' }}">
                            <div class="alert alert-warning border-0 rounded-4 p-3 shadow-sm mb-3">
                                <h6 class="fw-bold text-dark mb-1"><i class="bi bi-pc-display-horizontal text-primary me-1"></i> Conector en PC no detectado</h6>
                                <p class="small text-muted mb-2">Ejecute <code>Vincular_WhatsApp_Consultorio.bat</code> en la PC de recepción para activar los envíos.</p>
                                <div class="d-flex flex-wrap gap-2">
                                    <a href="{{ route('whatsapp.downloadConnector') }}" class="btn btn-primary btn-sm fw-bold rounded-pill px-3">
                                        <i class="bi bi-download me-1"></i> Descargar .ZIP (.BAT)
                                    </a>
                                    <button type="button" id="btnRetryService" class="btn btn-warning btn-sm fw-bold rounded-pill px-3">
                                        <i class="bi bi-arrow-clockwise me-1"></i> Verificar
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Descarga permanente del Conector -->
                        <div class="p-3 rounded-4 bg-light border d-flex justify-content-between align-items-center gap-2">
                            <div class="small">
                                <strong class="text-dark d-block"><i class="bi bi-laptop text-primary me-1"></i> Conector PC Consultorio</strong>
                                <span class="text-muted" style="font-size: 0.73rem;">Instalador autónomo con inicio automático.</span>
                            </div>
                            <a href="{{ route('whatsapp.downloadConnector') }}" class="btn btn-outline-primary btn-sm rounded-pill px-3 fw-bold text-nowrap">
                                <i class="bi bi-download me-1"></i> .ZIP
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- COLUMNA DERECHA (8 cols): MOTOR DE REGLAS (CUÁNDO, A QUIÉN Y CÓMO SE ENVÍA) -->
            <div class="col-lg-8">
                <form id="formWhatsappSettings" action="{{ route('whatsapp.store') }}" method="POST" class="card border-0 shadow-sm rounded-4 modern-card h-100">
                    @csrf
                    <div class="card-body p-4">

                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 pb-3 mb-3 border-bottom">
                            <div>
                                <h5 class="fw-bold mb-1 text-primary">
                                    <i class="bi bi-sliders2-vertical me-2"></i> 2. Configuración de Envíos de Turnos
                                </h5>
                                <p class="text-muted small mb-0">Combiná envíos al instante y recordatorios previos según tu preferencia.</p>
                            </div>
                            <div class="form-check form-switch fs-5 mb-0 d-flex align-items-center gap-2 bg-success bg-opacity-10 px-3 py-2 rounded-pill border border-success border-opacity-25">
                                <input class="form-check-input mt-0" type="checkbox" name="is_active" role="switch" id="whatsappSwitch" value="1" {{ !empty($rules['is_active']) ? 'checked' : '' }} style="cursor: pointer;">
                                <label class="form-check-label fw-bold text-success small" for="whatsappSwitch">Envíos Automáticos Activos</label>
                            </div>
                        </div>

                        <!-- BLOQUE A: ¿CUÁNDO ENVIAR? (COMBINABLES) -->
                        <h6 class="fw-bold text-dark small text-uppercase mb-2" style="letter-spacing: 0.5px;">
                            <i class="bi bi-clock-fill text-primary me-1"></i> A. ¿Cuándo enviar los mensajes? (Podés activar varios a la vez)
                        </h6>
                        <div class="row g-2 mb-4">
                            <!-- Disparador 1: Al instante al crear turno -->
                            <div class="col-md-6">
                                <label class="p-3 rounded-4 border bg-light d-flex align-items-start gap-3 h-100 w-100" style="cursor: pointer;">
                                    <input class="form-check-input fs-5 mt-1 flex-shrink-0" type="checkbox" name="send_on_create" value="1" {{ !empty($rules['send_on_create']) ? 'checked' : '' }}>
                                    <div>
                                        <span class="fw-bold text-dark d-block"><i class="bi bi-lightning-charge-fill text-warning me-1"></i> Enviar al instante al agendar</span>
                                        <small class="text-muted">Apenas se carga un turno nuevo en la Agenda, el paciente recibe la confirmación inmediata.</small>
                                    </div>
                                </label>
                            </div>

                            <!-- Disparador 2: Al instante al reprogramar -->
                            <div class="col-md-6">
                                <label class="p-3 rounded-4 border bg-light d-flex align-items-start gap-3 h-100 w-100" style="cursor: pointer;">
                                    <input class="form-check-input fs-5 mt-1 flex-shrink-0" type="checkbox" name="send_on_reschedule" value="1" {{ !empty($rules['send_on_reschedule']) ? 'checked' : '' }}>
                                    <div>
                                        <span class="fw-bold text-dark d-block"><i class="bi bi-arrow-repeat text-primary me-1"></i> Enviar al instante al reprogramar</span>
                                        <small class="text-muted">Si se traslada un turno de fecha u horario, avisa automáticamente los nuevos datos.</small>
                                    </div>
                                </label>
                            </div>

                            <!-- Disparador 3: Recordatorio previo 24 / 48 / 72 hs antes -->
                            <div class="col-12">
                                <div class="p-3 rounded-4 border bg-light">
                                    <div class="row g-3 align-items-center">
                                        <div class="col-md-5">
                                            <label class="d-flex align-items-start gap-3 mb-0" style="cursor: pointer;">
                                                <input class="form-check-input fs-5 mt-1 flex-shrink-0" type="checkbox" name="send_reminder_before" id="chkSendReminderBefore" value="1" {{ !empty($rules['send_reminder_before']) ? 'checked' : '' }}>
                                                <div>
                                                    <span class="fw-bold text-dark d-block"><i class="bi bi-bell-fill text-success me-1"></i> Recordatorio previo al turno</span>
                                                    <small class="text-muted">Reenvía recordatorio antes del día de atención.</small>
                                                </div>
                                            </label>
                                        </div>
                                        <div class="col-sm-6 col-md-4">
                                            <label class="form-label small fw-bold text-muted mb-1">Anticipación</label>
                                            <select name="reminder_days_before" class="form-select form-select-sm bg-white rounded-3 shadow-none fw-semibold">
                                                <option value="1" {{ (int)($rules['reminder_days_before'] ?? 1) === 1 ? 'selected' : '' }}>24 hs antes (1 día antes)</option>
                                                <option value="2" {{ (int)($rules['reminder_days_before'] ?? 1) === 2 ? 'selected' : '' }}>48 hs antes (2 días antes)</option>
                                                <option value="3" {{ (int)($rules['reminder_days_before'] ?? 1) === 3 ? 'selected' : '' }}>72 hs antes (3 días antes)</option>
                                                <option value="7" {{ (int)($rules['reminder_days_before'] ?? 1) === 7 ? 'selected' : '' }}>7 días antes (1 semana)</option>
                                            </select>
                                        </div>
                                        <div class="col-sm-6 col-md-3">
                                            <label class="form-label small fw-bold text-muted mb-1">Hora de disparo</label>
                                            <input type="time" name="reminder_time" class="form-control form-control-sm bg-white rounded-3 shadow-none fw-semibold" value="{{ $rules['reminder_time'] ?? '09:00' }}" required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- BLOQUE B: ¿A QUIÉN ENVIAR? -->
                        <h6 class="fw-bold text-dark small text-uppercase mb-2" style="letter-spacing: 0.5px;">
                            <i class="bi bi-people-fill text-primary me-1"></i> B. ¿A quiénes se les envía?
                        </h6>
                        <div class="p-3 rounded-4 border bg-light mb-4">
                            <div class="row g-3 align-items-center">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-muted mb-1">Agendas / Profesionales incluidos</label>
                                    <div class="d-flex gap-2">
                                        <input type="radio" class="btn-check" name="doctor_scope" id="scopeAllDocs" value="all" {{ ($rules['doctor_scope'] ?? 'all') === 'all' ? 'checked' : '' }} onchange="toggleDoctorScopeList()">
                                        <label class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold" for="scopeAllDocs">Todos los médicos</label>

                                        <input type="radio" class="btn-check" name="doctor_scope" id="scopeSelectedDocs" value="selected" {{ ($rules['doctor_scope'] ?? 'all') === 'selected' ? 'checked' : '' }} onchange="toggleDoctorScopeList()">
                                        <label class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold" for="scopeSelectedDocs">Elegir médicos</label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold text-muted mb-1">Nombre del Consultorio</label>
                                    <input type="text" name="clinic_name" class="form-control form-control-sm bg-white rounded-3 shadow-none" value="{{ $rules['clinic_name'] ?? 'Consultorio Dr. Cortalezzi' }}">
                                </div>
                                <div class="col-md-3">
                                    <div class="form-check form-switch mt-md-4">
                                        <input class="form-check-input" type="checkbox" name="include_overbooked" id="chkIncludeOverbooked" value="1" {{ !empty($rules['include_overbooked']) ? 'checked' : '' }}>
                                        <label class="form-check-label small fw-bold text-dark" for="chkIncludeOverbooked">Incluir Sobreturnos</label>
                                    </div>
                                </div>

                                <div class="col-12 {{ ($rules['doctor_scope'] ?? 'all') === 'selected' ? '' : 'd-none' }}" id="doctorCheckboxesWrap">
                                    <div class="p-2 px-3 rounded-3 bg-white border d-flex flex-wrap gap-3">
                                        @php $selectedDocIds = array_map('intval', $rules['doctor_ids'] ?? []); @endphp
                                        @foreach($doctors as $doc)
                                            <label class="form-check mb-0" style="cursor: pointer;">
                                                <input class="form-check-input" type="checkbox" name="doctor_ids[]" value="{{ $doc->id }}" {{ in_array((int)$doc->id, $selectedDocIds, true) ? 'checked' : '' }}>
                                                <span class="form-check-label small fw-semibold text-dark">{{ $doc->name }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- BLOQUE C: ¿CÓMO SE ENVÍA? (PLANTILLAS POR EVENTO) -->
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                            <h6 class="fw-bold text-dark small text-uppercase mb-0" style="letter-spacing: 0.5px;">
                                <i class="bi bi-chat-left-text-fill text-primary me-1"></i> C. Mensajes / Plantillas por Momento
                            </h6>
                            <div class="d-flex flex-wrap gap-1 align-items-center">
                                <span class="text-muted" style="font-size: 0.72rem;">Insertar variable:</span>
                                @foreach(['{nombre}', '{apellido}', '{fecha_turno}', '{hora_turno}', '{medico}', '{clinica}'] as $vTag)
                                    <button type="button" class="btn btn-sm btn-light border py-0 px-2 rounded-pill var-btn" style="font-size: 0.72rem;" data-var="{{ $vTag }}"><code>{{ $vTag }}</code></button>
                                @endforeach
                            </div>
                        </div>

                        <ul class="nav nav-pills gap-2 mb-2" id="waTemplateTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active rounded-pill py-1 px-3 small fw-bold" data-bs-toggle="pill" data-bs-target="#tabTplNew" type="button">
                                    <i class="bi bi-lightning-charge me-1"></i> 1. Al Agendar (Instante)
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link rounded-pill py-1 px-3 small fw-bold" data-bs-toggle="pill" data-bs-target="#tabTplResched" type="button">
                                    <i class="bi bi-arrow-repeat me-1"></i> 2. Al Reprogramar
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link rounded-pill py-1 px-3 small fw-bold" data-bs-toggle="pill" data-bs-target="#tabTplRem" type="button">
                                    <i class="bi bi-bell me-1"></i> 3. Recordatorio 24/48 hs antes
                                </button>
                            </li>
                        </ul>

                        <div class="tab-content mb-3">
                            <div class="tab-pane fade show active" id="tabTplNew" role="tabpanel">
                                <textarea name="template_instant_new" id="tplInstantNew" rows="3" class="form-control rounded-3 shadow-none wa-tpl-textarea">{{ old('template_instant_new', $rules['template_instant_new']) }}</textarea>
                            </div>
                            <div class="tab-pane fade" id="tabTplResched" role="tabpanel">
                                <textarea name="template_instant_reschedule" id="tplInstantReschedule" rows="3" class="form-control rounded-3 shadow-none wa-tpl-textarea">{{ old('template_instant_reschedule', $rules['template_instant_reschedule']) }}</textarea>
                            </div>
                            <div class="tab-pane fade" id="tabTplRem" role="tabpanel">
                                <textarea name="template_reminder" id="tplReminder" rows="3" class="form-control rounded-3 shadow-none wa-tpl-textarea">{{ old('template_reminder', $rules['template_reminder']) }}</textarea>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end pt-2 border-top">
                            <button type="submit" class="btn btn-success fw-bold px-5 py-2 shadow-sm rounded-pill">
                                <i class="bi bi-save me-2"></i> Guardar Configuración de Envíos
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- FILA INFERIOR: DISPARADOR MANUAL POR DÍA + BANDEJA DE SALIDA / HISTORIAL EN VIVO -->
        <div class="card border-0 shadow-sm rounded-4 modern-card">
            <div class="card-body p-4">
                <div class="row g-3 align-items-end pb-3 mb-3 border-bottom">
                    <div class="col-lg-5">
                        <h5 class="fw-bold text-dark mb-1">
                            <i class="bi bi-send-check-fill text-success me-2"></i> 3. Envío Manual por Fecha y Bandeja de Salida
                        </h5>
                        <p class="text-muted small mb-0">Podés disparar ahora mismo los recordatorios de un día puntual y ver en vivo cada mensaje enviado.</p>
                    </div>
                    <div class="col-lg-7">
                        <form id="manualBatchForm" onsubmit="submitManualBatch(event)" class="row g-2 align-items-end justify-content-lg-end">
                            <div class="col-sm-4">
                                <label class="form-label small fw-bold text-muted mb-1">Turnos del día</label>
                                <input type="date" id="batchTargetDate" class="form-control form-control-sm rounded-3 bg-light" value="{{ now()->addDay()->format('Y-m-d') }}" required>
                            </div>
                            <div class="col-sm-4">
                                <label class="form-label small fw-bold text-muted mb-1">Agenda / Médico</label>
                                <select id="batchDoctorId" class="form-select form-select-sm rounded-3 bg-light">
                                    <option value="all">Todos los profesionales</option>
                                    @foreach($doctors as $doc)
                                        <option value="{{ $doc->id }}">{{ $doc->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-sm-4">
                                <button type="submit" id="btnSendBatch" class="btn btn-primary btn-sm rounded-pill w-100 py-2 fw-bold shadow-sm">
                                    <i class="bi bi-whatsapp me-1"></i> Enviar Tanda de ese Día
                                </button>
                            </div>
                            <div class="col-12 d-flex justify-content-between align-items-center mt-1">
                                <label class="form-check mb-0 small text-muted" style="cursor: pointer;">
                                    <input type="checkbox" class="form-check-input" id="batchForceResend">
                                    <span>Reenviar aunque ya hayan recibido recordatorio previo</span>
                                </label>
                                <button type="button" class="btn btn-link btn-sm text-muted text-decoration-none p-0" onclick="clearWaHistory()">
                                    <i class="bi bi-trash3 me-1"></i> Limpiar historial
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 small">
                        <thead class="table-light text-muted">
                            <tr>
                                <th class="ps-3">Hora Envío</th>
                                <th>Evento</th>
                                <th>Paciente / Celular</th>
                                <th>Turno / Agenda</th>
                                <th>Mensaje</th>
                                <th class="text-end pe-3">Estado</th>
                            </tr>
                        </thead>
                        <tbody id="whatsappHistoryBody">
                            @forelse($history as $h)
                                <tr>
                                    <td class="ps-3 text-muted text-nowrap">{{ !empty($h['created_at']) ? \Carbon\Carbon::parse($h['created_at'])->format('d/m H:i') : '-' }}</td>
                                    <td>
                                        @php
                                            $tMap = [
                                                'instant_new' => ['⚡ Alta al Instante', 'primary'],
                                                'instant_reschedule' => ['🔄 Reprogramación', 'info text-dark'],
                                                'reminder' => ['⏰ Recordatorio', 'success'],
                                                'manual_batch' => ['📅 Tanda Manual', 'secondary'],
                                                'test' => ['🧪 Prueba', 'dark'],
                                            ];
                                            $tBadge = $tMap[$h['type'] ?? 'reminder'] ?? ['Mensaje', 'secondary'];
                                        @endphp
                                        <span class="badge bg-{{ $tBadge[1] }} bg-opacity-10 text-dark border rounded-pill px-2 py-1">{{ $tBadge[0] }}</span>
                                    </td>
                                    <td>
                                        <strong class="text-dark d-block">{{ $h['patient_name'] ?? '-' }}</strong>
                                        <span class="text-muted">{{ $h['phone'] ?? '-' }}</span>
                                    </td>
                                    <td>
                                        <span class="fw-semibold text-dark d-block">{{ $h['appointment_label'] ?? '-' }}</span>
                                        <span class="text-muted">{{ $h['doctor_name'] ?? '-' }}</span>
                                    </td>
                                    <td style="max-width: 280px;" class="text-truncate" title="{{ $h['message'] ?? '' }}">
                                        {{ $h['message'] ?? '' }}
                                    </td>
                                    <td class="text-end pe-3">
                                        @if(($h['status'] ?? '') === 'sent')
                                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1">
                                                <i class="bi bi-check-all me-1"></i> Entregado
                                            </span>
                                        @elseif(($h['status'] ?? '') === 'failed')
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-3 py-1" title="{{ $h['error'] ?? '' }}">
                                                <i class="bi bi-exclamation-circle me-1"></i> {{ \Illuminate\Support\Str::limit($h['error'] ?? 'Error', 24) }}
                                            </span>
                                        @else
                                            <span class="badge bg-warning-subtle text-dark border border-warning rounded-pill px-3 py-1">
                                                <i class="bi bi-hourglass-split me-1"></i> En cola...
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">
                                        <i class="bi bi-inbox fs-3 d-block mb-1 opacity-50"></i>
                                        Aún no hay envíos registrados en la bandeja.
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

@push('scripts')
<script>
function toggleDoctorScopeList() {
    const isSel = document.getElementById('scopeSelectedDocs')?.checked;
    const wrap = document.getElementById('doctorCheckboxesWrap');
    if (wrap) {
        if (isSel) wrap.classList.remove('d-none');
        else wrap.classList.add('d-none');
    }
}

async function refreshWaHistoryTable() {
    try {
        const res = await fetch("{{ route('whatsapp.history') }}");
        const data = await res.json();
        const tbody = document.getElementById('whatsappHistoryBody');
        if (!tbody || !Array.isArray(data.history)) return;

        if (data.history.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-muted"><i class="bi bi-inbox fs-3 d-block mb-1 opacity-50"></i>Aún no hay envíos registrados en la bandeja.</td></tr>';
            return;
        }

        const typeLabels = {
            instant_new: '⚡ Alta al Instante',
            instant_reschedule: '🔄 Reprogramación',
            reminder: '⏰ Recordatorio',
            manual_batch: '📅 Tanda Manual',
            test: '🧪 Prueba'
        };

        tbody.innerHTML = data.history.map(h => {
            const dt = h.created_at ? new Date(h.created_at) : null;
            const dtStr = dt && !isNaN(dt)
                ? `${String(dt.getDate()).padStart(2,'0')}/${String(dt.getMonth()+1).padStart(2,'0')} ${String(dt.getHours()).padStart(2,'0')}:${String(dt.getMinutes()).padStart(2,'0')}`
                : '-';
            const statusHtml = h.status === 'sent'
                ? '<span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1"><i class="bi bi-check-all me-1"></i> Entregado</span>'
                : (h.status === 'failed'
                    ? `<span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-3 py-1" title="${(h.error || '').replace(/"/g, '&quot;')}"><i class="bi bi-exclamation-circle me-1"></i> ${(h.error || 'Error').substring(0, 24)}</span>`
                    : '<span class="badge bg-warning-subtle text-dark border border-warning rounded-pill px-3 py-1"><i class="bi bi-hourglass-split me-1"></i> En cola...</span>');

            const safeMsg = (h.message || '').replace(/</g, '&lt;').replace(/"/g, '&quot;');
            return `<tr>
                <td class="ps-3 text-muted text-nowrap">${dtStr}</td>
                <td><span class="badge bg-light text-dark border rounded-pill px-2 py-1">${typeLabels[h.type] || 'Mensaje'}</span></td>
                <td><strong class="text-dark d-block">${h.patient_name || '-'}</strong><span class="text-muted">${h.phone || '-'}</span></td>
                <td><span class="fw-semibold text-dark d-block">${h.appointment_label || '-'}</span><span class="text-muted">${h.doctor_name || '-'}</span></td>
                <td style="max-width:280px;" class="text-truncate" title="${safeMsg}">${safeMsg}</td>
                <td class="text-end pe-3">${statusHtml}</td>
            </tr>`;
        }).join('');
    } catch (_) {}
}

async function submitManualBatch(e) {
    e.preventDefault();
    const btn = document.getElementById('btnSendBatch');
    const targetDate = document.getElementById('batchTargetDate').value;
    const doctorId = document.getElementById('batchDoctorId').value;
    const forceResend = document.getElementById('batchForceResend').checked;

    btn.disabled = true;
    const orig = btn.innerHTML;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Encolando...';

    try {
        const res = await fetch("{{ route('whatsapp.sendBatch') }}", {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ target_date: targetDate, doctor_id: doctorId, force_resend: forceResend })
        });
        const data = await res.json();
        alert(data.message || (data.success ? 'Tanda encolada.' : 'No se encontraron turnos.'));
        refreshWaHistoryTable();
    } catch (err) {
        alert('Error al enviar la tanda de recordatorios.');
    } finally {
        btn.disabled = false;
        btn.innerHTML = orig;
    }
}

async function clearWaHistory() {
    if (!confirm('¿Limpiar el historial visual de mensajes enviados?')) return;
    await fetch("{{ route('whatsapp.clearHistory') }}", {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
    });
    refreshWaHistoryTable();
}

document.addEventListener('DOMContentLoaded', function () {
    let activeTextarea = document.getElementById('tplInstantNew');
    document.querySelectorAll('.wa-tpl-textarea').forEach(ta => {
        ta.addEventListener('focus', () => { activeTextarea = ta; });
    });
    document.querySelectorAll('#waTemplateTabs button[data-bs-toggle="pill"]').forEach(tabBtn => {
        tabBtn.addEventListener('shown.bs.tab', function (ev) {
            const targetPane = document.querySelector(ev.target.getAttribute('data-bs-target'));
            const ta = targetPane?.querySelector('textarea');
            if (ta) activeTextarea = ta;
        });
    });

    document.querySelectorAll('.var-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const varText = this.getAttribute('data-var');
            const textarea = activeTextarea || document.querySelector('.tab-pane.active textarea');
            if (!textarea) return;
            const start = textarea.selectionStart ?? textarea.value.length;
            const end = textarea.selectionEnd ?? textarea.value.length;
            const text = textarea.value;
            textarea.value = text.substring(0, start) + varText + text.substring(end);
            textarea.focus();
            textarea.selectionStart = textarea.selectionEnd = start + varText.length;
        });
    });

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

    function updateBadge(status, phone = '') {
        if (!statusBadgeContainer) return;
        if (status === 'connected') {
            statusBadgeContainer.innerHTML = `<span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill fs-6"><i class="bi bi-check-circle-fill me-1"></i> Línea Conectada ${phone ? '(' + phone + ')' : ''}</span>`;
        } else if (status === 'offline') {
            statusBadgeContainer.innerHTML = `<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-3 py-2 rounded-pill fs-6"><i class="bi bi-exclamation-triangle-fill me-1"></i> Conector PC Offline</span>`;
        } else {
            statusBadgeContainer.innerHTML = `<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-3 py-2 rounded-pill fs-6"><i class="bi bi-phone me-1"></i> Esperando Escaneo QR</span>`;
        }
    }

    function showConnected(phone) {
        panelConnected.classList.remove('d-none');
        panelDisconnected.classList.add('d-none');
        panelOffline.classList.add('d-none');
        if (connectedPhoneNumber) connectedPhoneNumber.textContent = phone;
        updateBadge('connected', phone);
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

    function startPolling() {
        if (pollInterval) clearInterval(pollInterval);
        pollInterval = setInterval(async () => {
            try {
                const response = await fetch("{{ route('whatsapp.status') }}");
                const data = await response.json();
                if (data.status === 'connected') {
                    clearInterval(pollInterval);
                    pollInterval = null;
                    showConnected(data.phone || 'Conectado');
                } else if (data.status === 'offline') {
                    clearInterval(pollInterval);
                    pollInterval = null;
                    showOffline();
                }
            } catch (_) {}
        }, 2500);
    }

    async function fetchQrCode() {
        qrInitialState.classList.add('d-none');
        qrActiveState.classList.add('d-none');
        qrLoadingState.classList.remove('d-none');
        try {
            const response = await fetch("{{ route('whatsapp.qr') }}");
            const data = await response.json();
            if (data.status === 'connected') {
                showConnected(data.phone || 'Conectado');
                return;
            }
            if (data.qr) {
                qrImageElement.src = data.qr;
                qrLoadingState.classList.add('d-none');
                qrActiveState.classList.remove('d-none');
                startPolling();
            } else {
                setTimeout(fetchQrCode, 2000);
            }
        } catch (_) {
            qrLoadingState.classList.add('d-none');
            showOffline();
        }
    }

    if (btnGenerateQr) btnGenerateQr.addEventListener('click', fetchQrCode);
    if (btnRefreshQr) btnRefreshQr.addEventListener('click', fetchQrCode);
    if (btnRetryService) btnRetryService.addEventListener('click', () => location.reload());

    if (btnDisconnect) {
        btnDisconnect.addEventListener('click', async function () {
            if (!confirm('¿Desvincular esta línea telefónica de WhatsApp?')) return;
            btnDisconnect.disabled = true;
            try {
                const res = await fetch("{{ route('whatsapp.disconnect') }}", {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' }
                });
                const data = await res.json();
                if (data.success || data.status === 'disconnected') showDisconnected();
            } catch (_) {}
            btnDisconnect.disabled = false;
        });
    }

    if (btnSendTest) {
        btnSendTest.addEventListener('click', async function () {
            const phone = document.getElementById('testPhone').value.trim();
            const message = document.getElementById('testMessage').value.trim();
            const resultMsg = document.getElementById('testResultMsg');
            if (!phone || !message) return alert('Ingresá teléfono y mensaje.');

            btnSendTest.disabled = true;
            resultMsg.className = 'small mt-2 d-none';
            try {
                const res = await fetch("{{ route('whatsapp.testSend') }}", {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' },
                    body: JSON.stringify({ phone, message })
                });
                const data = await res.json();
                resultMsg.classList.remove('d-none');
                resultMsg.className = data.success ? 'small mt-2 text-success fw-bold' : 'small mt-2 text-danger fw-bold';
                resultMsg.textContent = data.success ? (data.message || 'Enviado.') : (data.error || 'Error.');
                refreshWaHistoryTable();
            } catch (_) {
                resultMsg.className = 'small mt-2 text-danger fw-bold';
                resultMsg.textContent = 'Error de conexión.';
            } finally {
                btnSendTest.disabled = false;
            }
        });
    }

    @if($serviceStatus === 'qr_ready')
        fetchQrCode();
    @endif

    // Refrescar bandeja de envíos cada 5 segundos
    setInterval(refreshWaHistoryTable, 5000);
});
</script>
@endpush
@endsection
