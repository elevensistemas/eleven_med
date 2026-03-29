@extends('layouts.admin')

@section('title', 'Gestión de Usuarios')
@section('subtitle', 'Visualiza y administra el equipo y perfiles clínicos')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div class="search-box">
        <div class="input-group">
            <span class="input-group-text bg-white border-end-0 rounded-start-pill ps-3" id="searchIcon">
                <i class="bi bi-search text-muted"></i>
            </span>
            <input type="text" class="form-control border-start-0 rounded-end-pill py-2 shadow-sm" placeholder="Buscar por DNI, correo o nombre..." aria-label="Search">
        </div>
    </div>
    <div>
        <a href="{{ route('users.create') }}" class="btn btn-primary rounded-pill px-4 py-2 shadow-sm fw-medium d-flex align-items-center gap-2" style="background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%); border:none;">
            <i class="bi bi-person-plus-fill"></i> Nuevo Usuario
        </a>
    </div>
</div>

<div class="modern-card p-0">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light">
                <tr>
                    <th scope="col" class="py-3 ps-4 text-muted text-uppercase fw-semibold" style="font-size: 0.8rem;">Usuario / Cargo</th>
                    <th scope="col" class="py-3 text-muted text-uppercase fw-semibold" style="font-size: 0.8rem;">Contacto</th>
                    <th scope="col" class="py-3 text-muted text-uppercase fw-semibold" style="font-size: 0.8rem;">Rol de Sistema</th>
                    <th scope="col" class="py-3 text-muted text-uppercase fw-semibold" style="font-size: 0.8rem;">Estado</th>
                    <th scope="col" class="py-3 text-end pe-4 text-muted text-uppercase fw-semibold" style="font-size: 0.8rem;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                <tr>
                    <td class="ps-4 py-3">
                        <div class="d-flex align-items-center">
                            <img src="{{ $user->avatar_url }}" width="45" height="45" class="shadow-sm rounded-circle object-fit-cover">
                            <div class="ms-3">
                                <span class="d-block fw-bold text-dark">{{ $user->name }}</span>
                                <small class="text-muted">ID: CRT-{{ str_pad($user->id, 4, '0', STR_PAD_LEFT) }}</small>
                            </div>
                        </div>
                    </td>
                    <td class="py-3">
                        <span class="d-block text-dark"><i class="bi bi-envelope me-1 text-muted"></i> {{ $user->email }}</span>
                    </td>
                    <td class="py-3">
                        @foreach($user->roles as $role)
                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-3 py-2 rounded-pill fw-medium text-capitalize">
                                {{ $role->name }}
                            </span>
                        @endforeach
                    </td>
                    <td class="py-3">
                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3 py-2 rounded-pill fw-medium">
                            <i class="bi bi-circle-fill me-1" style="font-size:0.5rem;"></i> Activo
                        </span>
                    </td>
                    <td class="text-end pe-4 py-3">
                        <div class="dropdown">
                            <button class="btn btn-light btn-sm rounded-circle p-2 shadow-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-three-dots-vertical"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3">
                                <li><a class="dropdown-item py-2" href="{{ route('users.edit', $user) }}"><i class="bi bi-pencil me-2 text-warning"></i> Editar</a></li>
                                <li>
                                    <form action="{{ route('users.resetPassword', $user) }}" method="POST">
                                        @csrf
                                        <button class="dropdown-item py-2" type="submit" onclick="return confirm('¿Restablecer la contraseña a elevenmed2026 para {{ $user->name }}?');">
                                            <i class="bi bi-key me-2 text-info"></i> Reset Password
                                        </button>
                                    </form>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form action="{{ route('users.destroy', $user) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button class="dropdown-item py-2 text-danger" type="submit" onclick="return confirm('¿Seguro que deseas inhabilitar al usuario {{ $user->name }}?');">
                                            <i class="bi bi-person-x me-2"></i> Inhabilitar
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center py-5 text-muted">
                        <i class="bi bi-inbox fs-1 d-block mb-3 opacity-50"></i>
                        No hay usuarios registrados
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <!-- Pagination -->
    <div class="px-4 py-3 border-top">
        {{ $users->links() }}
    </div>
</div>
@endsection
