@extends('layouts.admin')

@section('title', 'Editar Usuario: ' . $user->name)
@section('subtitle', 'Modifica los datos de acceso y múltiples roles del integrante')

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
            <form action="{{ route('users.update', $user) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="row g-4 mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-muted small text-uppercase">Nombre Completo</label>
                        <input type="text" name="name" class="form-control modern-input @error('name') is-invalid @enderror" value="{{ old('name', $user->name) }}" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-muted small text-uppercase">Correo Electrónico (Login)</label>
                        <input type="email" name="email" class="form-control modern-input @error('email') is-invalid @enderror" value="{{ old('email', $user->email) }}" required>
                        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold text-muted small text-uppercase">Foto de Perfil (Opcional Reemplazo)</label>
                    <div class="d-flex align-items-center gap-3">
                        <img src="{{ $user->avatar_url }}" width="60" height="60" class="rounded-circle shadow-sm object-fit-cover">
                        <div class="flex-grow-1">
                            <input type="file" name="profile_photo" class="form-control modern-input @error('profile_photo') is-invalid @enderror" accept="image/png, image/jpeg, image/webp">
                            <div class="form-text text-muted small mt-1"><i class="bi bi-info-circle me-1"></i>Dejá este campo vacío si querés conservar la foto actual o ícono.</div>
                        </div>
                    </div>
                    @error('profile_photo') <div class="invalid-feedback mt-2">{{ $message }}</div> @enderror
                </div>

                @if(auth()->user() && auth()->user()->hasRole('administrador'))
                <div class="p-3 rounded-4 bg-light border mb-4">
                    <label class="form-label fw-bold text-dark small text-uppercase mb-2"><i class="bi bi-key-fill text-primary me-1"></i> Asignar Nueva Contraseña (Opcional - Solo Administradores)</label>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <input type="password" name="password" class="form-control modern-input" placeholder="Nueva contraseña (dejar vacío para no cambiar)" autocomplete="new-password">
                        </div>
                        <div class="col-md-6">
                            <input type="password" name="password_confirmation" class="form-control modern-input" placeholder="Confirmar nueva contraseña" autocomplete="new-password">
                        </div>
                    </div>
                </div>
                @endif

                <hr class="my-4 text-muted opacity-25">

                @php
                    $selectedRoles = old('roles', $userRoles ?? []);
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
                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-3 py-1">Puede tildar varios roles a la vez</span>
                    </div>
                    <p class="small text-muted mb-3">
                        Si tilda <strong>Médico/a</strong> y <strong>Técnico/a</strong> simultáneamente, esta persona tendrá <strong>dos agendas separadas</strong> (su Agenda Médica y su Agenda Técnica / Estudios). Si tilda <strong>Recepcionista + Técnico/a</strong>, conservará sus tareas de secretaría y tendrá su propia Agenda Técnica de Estudios.
                    </p>
                    <div class="row g-3">
                        @foreach($roles as $role)
                        @php
                            $meta = $roleIcons[$role->name] ?? ['icon' => 'bi-person-badge text-info', 'desc' => 'Rol del sistema'];
                        @endphp
                        <div class="col-md-4 col-sm-6">
                            <div class="custom-role-card h-100">
                                <input class="form-check-input d-none" type="checkbox" name="roles[]" id="role_{{ $role->id }}" value="{{ $role->name }}" {{ in_array($role->name, $selectedRoles) ? 'checked' : '' }}>
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
                        Guardar Cambios <i class="bi bi-check2 ms-1"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card border-0 bg-primary bg-opacity-10 rounded-4 p-4 text-primary">
            <h5 class="fw-bold"><i class="bi bi-info-circle-fill me-2"></i>Múltiples Funciones</h5>
            <p class="small mb-2 mt-3 text-dark opacity-75">
                <strong>Secretaría + Técnica:</strong> Ej. Recepcionista que realiza Campos Visuales u OCT con su propia agenda bloqueable y modificable.
            </p>
            <p class="small mb-2 text-dark opacity-75">
                <strong>Médica + Técnica:</strong> Profesional con agenda de consultorio médico por un lado y agenda de estudios por otro.
            </p>
            <p class="small mb-0 text-dark opacity-75">
                <strong>Médico + Administrador:</strong> Permite además cambiar su propia contraseña desde el perfil arriba a la derecha.
            </p>
        </div>
    </div>
</div>
@endsection
