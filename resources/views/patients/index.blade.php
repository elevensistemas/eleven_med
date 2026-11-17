@extends('layouts.admin')

@section('title', 'Pacientes')
@section('subtitle', 'Historia Clínica Electrónica y Base de Pacientes Activos')

@section('content')
@if(session('success'))
    <div class="alert alert-success border-0 bg-success bg-opacity-10 text-success rounded-4 mb-3 shadow-sm">
        <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger border-0 bg-danger bg-opacity-10 text-danger rounded-4 mb-3 shadow-sm">
        <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
    </div>
@endif

<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <form action="{{ route('patients.index') }}" method="GET" class="d-flex align-items-center gap-2 flex-grow-1" style="max-width: 480px;">
        <div class="input-group bg-white rounded-pill shadow-sm border overflow-hidden">
            <span class="input-group-text bg-transparent border-0 ps-3 text-muted"><i class="bi bi-search"></i></span>
            <input type="text" name="search" value="{{ $search ?? '' }}" class="form-control border-0 shadow-none" placeholder="Filtrar por Apellido, DNI, Teléfono, Mail o Carpeta...">
            @if(!empty($search))
                <a href="{{ route('patients.index') }}" class="btn btn-link text-secondary text-decoration-none px-2"><i class="bi bi-x-lg"></i></a>
            @endif
            <button type="submit" class="btn btn-primary rounded-pill px-3 m-1 fw-bold" style="background: linear-gradient(135deg, #5e6ad2 0%, #7e448b 100%); border:none;">Buscar</button>
        </div>
    </form>

    <div class="d-flex align-items-center gap-2 flex-wrap">
        <a href="{{ route('patients.duplicates') }}" class="btn btn-outline-primary bg-white rounded-pill px-4 py-2 shadow-sm fw-bold d-flex align-items-center gap-2">
            <i class="bi bi-Subtract bi-intersect"></i> Buscar Fichas Duplicadas
        </a>
        <a href="{{ route('patients.create') }}" class="btn btn-primary rounded-pill px-4 py-2 shadow-sm fw-bold d-flex align-items-center gap-2" style="background: linear-gradient(135deg, #FF6B6B 0%, #C0392B 100%); border:none;">
            <i class="bi bi-person-heart"></i> Nuevo Paciente
        </a>
    </div>
</div>

<div class="modern-card p-0 rounded-4 overflow-hidden border-0 shadow-sm bg-white">
    <div class="px-4 py-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2 patient-list-header">
        <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-people-fill text-primary me-2"></i> Base de Pacientes Activos</h5>
        <span class="badge bg-white text-primary border rounded-pill px-3 py-2 shadow-sm fw-bold header-badge">
            Total: {{ $patients->total() }}
        </span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="--bs-table-border-color: #cbd5e1; border-bottom: 2px solid #cbd5e1;">
            <style>
                .patient-list-header {
                    background: linear-gradient(135deg, #f8f9fa 0%, #f1f3f5 100%);
                    border-top: 4px solid #5e6ad2 !important;
                }
                .theme-dark .patient-list-header {
                    background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%) !important;
                    border-color: #3b5998 !important;
                }
                .theme-dark .patient-list-header h5.text-dark {
                    color: #ffffff !important;
                }
                .theme-dark .patient-list-header .text-primary {
                    color: #a1ecff !important;
                }
                .theme-dark .header-badge {
                    background-color: rgba(255,255,255,0.1) !important;
                    color: #ffffff !important;
                    border-color: rgba(255,255,255,0.2) !important;
                }
                .table > :not(caption) > * > * { border-bottom-width: 1.5px; border-bottom-color: #e2e8f0; }
                .patient-row:hover { background-color: #f8faff !important; }
                .theme-dark .patient-row:hover { background-color: #2a2a2a !important; }
            </style>
            <thead class="bg-light">
                <tr>
                    <th scope="col" class="py-3 ps-4 text-muted text-uppercase fw-bold" style="font-size: 0.72rem; letter-spacing: 0.5px;">Paciente</th>
                    <th scope="col" class="py-3 text-muted text-uppercase fw-bold" style="font-size: 0.72rem; letter-spacing: 0.5px;">DNI / Edad</th>
                    <th scope="col" class="py-3 text-muted text-uppercase fw-bold" style="font-size: 0.72rem; letter-spacing: 0.5px;">Cobertura Médica</th>
                    <th scope="col" class="py-3 text-muted text-uppercase fw-bold" style="font-size: 0.72rem; letter-spacing: 0.5px;">Ingreso / 1° Consulta</th>
                    <th scope="col" class="py-3 text-muted text-uppercase fw-bold" style="font-size: 0.72rem; letter-spacing: 0.5px;">Última Consulta</th>
                    <th scope="col" class="py-3 text-center text-muted text-uppercase fw-bold" style="font-size: 0.72rem; letter-spacing: 0.5px;">Reprog.</th>
                    <th scope="col" class="py-3 text-center text-muted text-uppercase fw-bold" style="font-size: 0.72rem; letter-spacing: 0.5px;">Ausencias</th>
                    <th scope="col" class="py-3 text-end pe-4 text-muted text-uppercase fw-bold" style="font-size: 0.72rem; letter-spacing: 0.5px;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($patients as $patient)
                <tr onclick="window.location='{{ route('patients.show', $patient) }}'" style="cursor: pointer; transition: all 0.2s;" class="patient-row border-bottom">
                    <td class="ps-4 py-3">
                        <div class="d-flex align-items-center">
                            @if($patient->photo_path && \Illuminate\Support\Str::startsWith($patient->photo_path, 'uploads/'))
                                <img src="{{ asset($patient->photo_path) }}" alt="{{ $patient->first_name }}" class="rounded-circle shadow-sm" style="width: 44px; height: 44px; object-fit: cover; border: 2px solid #fff;">
                            @elseif($patient->photo_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($patient->photo_path))
                                <img src="{{ asset('storage/' . $patient->photo_path) }}" alt="{{ $patient->first_name }}" class="rounded-circle shadow-sm" style="width: 44px; height: 44px; object-fit: cover; border: 2px solid #fff;">
                            @else
                                @php 
                                    $colors = ['#5e6ad2, #7e448b', '#00c6ff, #0072ff', '#f12711, #f5af19', '#11998e, #38ef7d'];
                                    $gradient = $colors[$patient->id % count($colors)];
                                @endphp
                                <div class="icon-box rounded-circle shadow-sm text-white d-flex align-items-center justify-content-center flex-shrink-0" style="width: 44px; height: 44px; background: linear-gradient(135deg, {{ $gradient }}); border: 2px solid #fff;">
                                    <span class="fw-bold fs-6">{{ substr($patient->first_name, 0, 1) }}{{ substr($patient->last_name, 0, 1) }}</span>
                                </div>
                            @endif
                            <div class="ms-3">
                                <a href="{{ route('patients.show', $patient) }}" class="d-block fw-bold text-dark text-decoration-none">
                                    {{ $patient->last_name }}, {{ $patient->first_name }}
                                </a>
                                <small class="text-muted">
                                    Carpeta #{{ $patient->code ?: str_pad($patient->id, 6, '0', STR_PAD_LEFT) }}
                                    @if($patient->phone)
                                        · <i class="bi bi-telephone"></i> {{ $patient->phone }}
                                    @endif
                                </small>
                            </div>
                        </div>
                    </td>
                    <td class="py-3">
                        <span class="d-block fw-medium text-dark">{{ $patient->dni }}</span>
                        <small class="text-muted">{{ $patient->date_of_birth ? \Carbon\Carbon::parse($patient->date_of_birth)->age . ' años' : '-' }}</small>
                    </td>
                    <td class="py-3">
                        @if($patient->obra_social)
                            <span class="badge rounded-pill fw-bold px-3 py-2 shadow-sm" style="background-color: {{ $patient->obra_social_color }}15; color: {{ $patient->obra_social_color }}; border: 1px solid {{ $patient->obra_social_color }}50;">
                                <i class="bi bi-shield-check me-1"></i> {{ $patient->obra_social }} {{ $patient->plan ? ' - '.$patient->plan : '' }}
                            </span>
                        @else
                            <span class="badge rounded-pill fw-bold px-3 py-2 shadow-sm" style="background-color: {{ $patient->obra_social_color }}15; color: {{ $patient->obra_social_color }}; border: 1px solid {{ $patient->obra_social_color }}50;">
                                <i class="bi bi-person-badge me-1"></i> Particular
                            </span>
                        @endif
                    </td>
                    <td class="py-3">
                        @if($patient->first_visit_at)
                            <span class="d-block fw-bold text-dark" style="font-size: 0.84rem;" title="Fecha de Primera Consulta">
                                <i class="bi bi-journal-check text-primary me-1"></i> {{ \Carbon\Carbon::parse($patient->first_visit_at)->format('d/m/Y') }}
                            </span>
                            <small class="text-muted" style="font-size: 0.72rem;">1° Consulta (Alta: {{ $patient->created_at ? $patient->created_at->format('d/m/Y') : '-' }})</small>
                        @else
                            <span class="d-block fw-semibold text-dark" style="font-size: 0.84rem;" title="Fecha de Ingreso / Alta en sistema">
                                <i class="bi bi-person-plus text-secondary me-1"></i> {{ $patient->created_at ? $patient->created_at->format('d/m/Y') : '-' }}
                            </span>
                            <small class="text-muted" style="font-size: 0.72rem;">Fecha de Ingreso</small>
                        @endif
                    </td>
                    <td class="py-3">
                        @if($patient->latestVisit)
                            <span class="d-block fw-bold text-dark" style="font-size: 0.84rem;"><i class="bi bi-calendar-check text-success me-1"></i> {{ $patient->latestVisit->created_at->format('d/m/Y') }}</span>
                            <small class="text-muted" style="font-size: 0.73rem;">Dr(a). {{ $patient->latestVisit->doctor->name ?? 'Staff' }}</small>
                        @else
                            <span class="text-muted small"><i class="bi bi-clock-history me-1"></i> Sin consultas</span>
                        @endif
                    </td>
                    <td class="py-3 text-center">
                        @if(($patient->reprogramaciones_count ?? 0) > 0)
                            <span class="badge bg-info bg-opacity-10 text-primary rounded-pill px-3 py-1 fw-bold border border-primary border-opacity-25" title="Cantidad de reprogramaciones registradas">
                                <i class="bi bi-arrow-repeat me-1"></i> {{ $patient->reprogramaciones_count }}
                            </span>
                        @else
                            <span class="text-muted small">0</span>
                        @endif
                    </td>
                    <td class="py-3 text-center">
                        @if($patient->ausencias_count > 0)
                            <span class="badge bg-danger bg-opacity-10 text-danger rounded-pill px-3 py-1 fw-bold border border-danger border-opacity-25" title="Inasistencias registradas">
                                <i class="bi bi-exclamation-triangle-fill me-1"></i> {{ $patient->ausencias_count }}
                            </span>
                        @else
                            <span class="text-muted small">0</span>
                        @endif
                    </td>
                    <td class="text-end pe-4 py-3" onclick="event.stopPropagation();">
                        <div class="d-inline-flex align-items-center gap-1 dropdown">
                            <a href="{{ route('patients.edit', $patient) }}" class="btn btn-outline-primary btn-sm rounded-pill px-2.5 py-1 fw-bold shadow-none" style="font-size: 0.76rem;" title="Editar datos filiatorios del paciente">
                                <i class="bi bi-pencil-square me-1"></i> Editar
                            </a>
                            <a href="{{ route('patients.show', $patient) }}" class="btn btn-primary btn-sm rounded-pill px-2.5 py-1 fw-bold shadow-sm text-white" title="Abrir Historia Clínica" style="background: linear-gradient(135deg, #5e6ad2 0%, #7e448b 100%); border: none; font-size: 0.76rem;">
                                <i class="bi bi-folder2-open me-1"></i> HC
                            </a>
                            <button class="btn btn-light btn-sm rounded-circle p-1 border" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Más acciones">
                                <i class="bi bi-three-dots-vertical text-muted"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3">
                                <li>
                                    <a class="dropdown-item py-2 fw-bold text-primary" href="{{ route('patients.edit', $patient) }}">
                                        <i class="bi bi-pencil-square me-2"></i> Editar Datos del Paciente
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item py-2 fw-semibold text-dark" href="{{ route('patients.show', $patient) }}">
                                        <i class="bi bi-folder2-open me-2 text-primary"></i> Abrir Historia Clínica
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item py-2 fw-semibold text-success" href="{{ route('patient.visits.create', $patient) }}">
                                        <i class="bi bi-plus-circle me-2"></i> Nueva Consulta Médica
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center py-5 text-muted">
                        <i class="bi bi-emoji-frown fs-1 d-block mb-3 opacity-50"></i>
                        No se encontraron pacientes registrados.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    <div class="px-4 py-3 border-top">
        {{ $patients->links() }}
    </div>
</div>
@endsection
