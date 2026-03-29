@extends('layouts.admin')

@section('title', 'Nuevo Usuario')
@section('subtitle', 'Alta de un nuevo integrante al sistema')

@section('content')
<div class="row">
    <div class="col-lg-8">
        <div class="modern-card p-4">
            <form action="{{ route('users.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="row g-4 mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-muted small text-uppercase">Nombre Completo</label>
                        <input type="text" name="name" class="form-control form-control-lg bg-light border-0 shadow-none @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-muted small text-uppercase">Correo Electrónico (Login)</label>
                        <input type="email" name="email" class="form-control form-control-lg bg-light border-0 shadow-none @error('email') is-invalid @enderror" value="{{ old('email') }}" required>
                        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold text-muted small text-uppercase">Foto de Perfil (Opcional)</label>
                    <input type="file" name="profile_photo" class="form-control bg-light border-0 shadow-none @error('profile_photo') is-invalid @enderror" accept="image/png, image/jpeg, image/webp">
                    <div class="form-text text-muted small mt-1"><i class="bi bi-info-circle me-1"></i>Sugerido: Imagen cuadrada. Formatos aceptados: JPG, PNG, WEBP.</div>
                    @error('profile_photo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="row g-4 mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-muted small text-uppercase">Contraseña de acceso</label>
                        <input type="password" name="password" class="form-control form-control-lg bg-light border-0 shadow-none @error('password') is-invalid @enderror" required autocomplete="new-password">
                        @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-muted small text-uppercase">Confirmar Contraseña</label>
                        <input type="password" name="password_confirmation" class="form-control form-control-lg bg-light border-0 shadow-none" required>
                    </div>
                </div>

                <hr class="my-4 text-muted opacity-25">

                <div class="mb-5">
                    <label class="form-label fw-bold text-muted small text-uppercase mb-3">Rol del Sistema</label>
                    <div class="d-flex gap-3 flex-wrap">
                        @foreach($roles as $role)
                        <div class="form-check custom-radio-card flex-grow-1">
                            <input class="form-check-input d-none" type="radio" name="role_name" id="role_{{ $role->id }}" value="{{ $role->name }}" required {{ old('role_name') == $role->name ? 'checked' : '' }}>
                            <label class="form-check-label w-100 p-3 text-center border rounded-4 cursor-pointer transition-all" for="role_{{ $role->id }}">
                                <i class="bi {{ $role->name == 'médico' ? 'bi-heart-pulse text-danger' : ($role->name == 'administrador' ? 'bi-shield-lock text-primary' : 'bi-person-badge text-info') }} fs-3 d-block mb-2"></i>
                                <span class="fw-bold d-block text-capitalize">{{ $role->name }}</span>
                            </label>
                        </div>
                        @endforeach
                    </div>
                    @error('role_name') <div class="text-danger small mt-2">{{ $message }}</div> @enderror
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
            <h5 class="fw-bold"><i class="bi bi-info-circle-fill me-2"></i>Sobre los Roles</h5>
            <p class="small mb-2 mt-3 text-dark opacity-75">
                <strong>Administrador:</strong> Acceso total, puede ver agendas de todos, destruir registros, configurar chat global.
            </p>
            <p class="small mb-2 text-dark opacity-75">
                <strong>Médico:</strong> Revisa turnos propios, anota diagnóstico, y visualiza estudios. 
            </p>
            <p class="small mb-0 text-dark opacity-75">
                <strong>Recepcionista:</strong> Agenda rápida, sin acceso a borrado de historia clínica confidencial.
            </p>
        </div>
    </div>
</div>

<style>
.custom-radio-card input:checked + label {
    background-color: var(--primary-color);
    color: white !important;
    border-color: var(--primary-color) !important;
    box-shadow: 0 10px 20px rgba(94, 106, 210, 0.2);
}
.custom-radio-card input:checked + label i {
    color: white !important;
}
.cursor-pointer { cursor: pointer; }
.transition-all { transition: all 0.2s ease; }
</style>
@endsection
