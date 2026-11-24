@extends('layouts.admin')

@section('title', 'Nuevo Usuario')
@section('subtitle', 'Alta de un nuevo integrante al sistema (soporta múltiples funciones/roles)')

@section('content')
<style>
    .modern-input {
        background-color: #f8f9fa !important;
        border: 1px solid #ced4da !important;
        border-radius: 8px !important;
        padding: 0.75rem 1rem !important;
        font-size: 0.95rem !important;
        color: #212529 !important;
        transition: all 0.2s ease-in-out !important;
        box-shadow: inset 0 1px 2px rgba(0,0,0,0.02) !important;
    }
    .modern-input:focus {
        background-color: #ffffff !important;
        border-color: #5e6ad2 !important;
        box-shadow: 0 0 0 4px rgba(94, 106, 210, 0.15) !important;
        outline: none !important;
    }
    .custom-role-card input:checked + label {
        background-color: var(--primary-color);
        color: white !important;
        border-color: var(--primary-color) !important;
        box-shadow: 0 10px 20px rgba(94, 106, 210, 0.22);
    }
    .custom-role-card input:checked + label i,
    .custom-role-card input:checked + label small {
        color: white !important;
    }
    .cursor-pointer { cursor: pointer; }
    .transition-all { transition: all 0.2s ease; }
</style>
<div class="row">
    <div class="col-lg-8">
        <div class="modern-card p-4">
            <form action="{{ route('users.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="row g-4 mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-muted small text-uppercase">Nombre Completo</label>
                        <input type="text" name="name" class="form-control modern-input @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-muted small text-uppercase">Correo Electrónico (Login)</label>
                        <input type="email" name="email" class="form-control modern-input @error('email') is-invalid @enderror" value="{{ old('email') }}" required>
                        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold text-muted small text-uppercase">Foto de Perfil (Opcional)</label>
                    <input type="file" name="profile_photo" class="form-control modern-input @error('profile_photo') is-invalid @enderror" accept="image/png, image/jpeg, image/webp">
                    <div class="form-text text-muted small mt-1"><i class="bi bi-info-circle me-1"></i>Sugerido: Imagen cuadrada. Formatos aceptados: JPG, PNG, WEBP.</div>
                    @error('profile_photo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="row g-4 mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-muted small text-uppercase">Contraseña de acceso</label>
                        <input type="password" name="password" class="form-control modern-input @error('password') is-invalid @enderror" required autocomplete="new-password">
                        @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-muted small text-uppercase">Confirmar Contraseña</label>
                        <input type="password" name="password_confirmation" class="form-control modern-input" required>
                    </div>
                </div>

                <hr class="my-4 text-muted opacity-25">

                @php
                    $oldRoles = old('roles', []);
                    $roleIcons = [
                        'administrador' => ['icon' => 'bi-shield-lock text-primary', 'desc' => 'Gestión total'],
                        'médico' => ['icon' => 'bi-heart-pulse text-danger', 'desc' => 'Agenda Médica + HC'],
                        'técnico' => ['icon' => 'bi-eyedropper text-success', 'desc' => 'Agenda Estudios / Técnica'],
                        'recepcionista' => ['icon' => 'bi-person-workspace text-info', 'desc' => 'Secretaría y Admisión'],
                        'supervisor' => ['icon' => 'bi-clipboard-data text-warning', 'desc' => 'Supervisión'],
                    ];
                @endphp

                <div class="mb-5">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                        <label class="form-label fw-bold text-muted small text-uppercase mb-0">Roles y Funciones en el Consultorio (Selección Múltiple)</label>
                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-3 py-1">Puede tildar más de un rol</span>
                    </div>
                    <p class="small text-muted mb-3">
                        Por ejemplo: una persona puede ser <strong>Recepcionista + Técnico/a</strong> (tendrá agenda propia de estudios como Campos Visuales) o <strong>Médico/a + Técnico/a</strong> (tendrá dos agendas independientes: una médica y otra de estudios).
                    </p>
                    <div class="row g-3">
                        @foreach($roles as $role)
                        @php
                            $meta = $roleIcons[$role->name] ?? ['icon' => 'bi-person-badge text-info', 'desc' => 'Rol del sistema'];
                        @endphp
                        <div class="col-md-4 col-sm-6">
                            <div class="custom-role-card h-100">
                                <input class="form-check-input d-none" type="checkbox" name="roles[]" id="role_{{ $role->id }}" value="{{ $role->name }}" {{ in_array($role->name, $oldRoles) ? 'checked' : '' }}>
                                <label class="form-check-label w-100 h-100 p-3 text-center border rounded-4 cursor-pointer transition-all d-flex flex-column justify-content-center" for="role_{{ $role->id }}">
                                    <i class="bi {{ $meta['icon'] }} fs-3 d-block mb-1"></i>
                                    <span class="fw-bold d-block text-capitalize">{{ $role->name === 'técnico' ? 'Técnico / Técnica' : $role->name }}</span>
                                    <small class="text-muted" style="font-size: 0.75rem;">{{ $meta['desc'] }}</small>
                                </label>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @error('roles') <div class="text-danger small mt-2 fw-bold">{{ $message }}</div> @enderror
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('users.index') }}" class="btn btn-light px-4 py-2 rounded-pill fw-medium">Cancelar</a>
                    <button type="submit" class="btn btn-primary px-5 py-2 rounded-pill fw-medium shadow-sm" style="background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%); border:none;">
                        Crear Usuario <i class="bi bi-check2 ms-1"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card border-0 bg-primary bg-opacity-10 rounded-4 p-4 text-primary">
            <h5 class="fw-bold"><i class="bi bi-info-circle-fill me-2"></i>Sobre los Roles y Agendas</h5>
            <p class="small mb-2 mt-3 text-dark opacity-75">
                <strong>Administrador:</strong> Acceso total y habilitado para cambiar su propia contraseña de entrada desde su menú superior derecho.
            </p>
            <p class="small mb-2 text-dark opacity-75">
                <strong>Médico/a:</strong> Posee su propia <em>Agenda Médica</em> configurable, bloqueable y atención de historias clínicas.
            </p>
            <p class="small mb-2 text-dark opacity-75">
                <strong>Técnico / Técnica:</strong> Posee su propia <em>Agenda Técnica / Estudios</em> (ej: Campos Visuales, OCT, Biometría), totalmente independiente de la médica, con sus propios horarios y bloqueos.
            </p>
            <p class="small mb-0 text-dark opacity-75">
                <strong>Recepcionista / Secretaría:</strong> Admisión en Sala de Espera, Intercomunicador y gestión de turnos. Puede combinarse con <em>Técnico/a</em>.
            </p>
        </div>
    </div>
</div>
@endsection
