@extends('layouts.admin')

@section('title', 'Pacientes')
@section('subtitle', 'Historia Clínica Electrónica y Agenda')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div class="search-box">
        <form action="{{ route('patients.index') }}" method="GET" class="input-group">
            <span class="input-group-text bg-white border-end-0 rounded-start-pill ps-3" id="searchIcon">
                <i class="bi bi-search text-muted"></i>
            </span>
            <input type="text" name="search" value="{{ $search ?? '' }}" class="form-control border-start-0 rounded-end-pill py-2 shadow-sm" placeholder="Buscar apellido, DNI..." aria-label="Search">
            @if($search)
                <a href="{{ route('patients.index') }}" class="btn btn-light rounded-pill ms-2 text-muted shadow-sm"><i class="bi bi-x"></i> Limpiar</a>
            @endif
        </form>
    </div>
    <div>
        <a href="{{ route('patients.create') }}" class="btn btn-primary rounded-pill px-4 py-2 shadow-sm fw-medium d-flex align-items-center gap-2" style="background: linear-gradient(135deg, #FF6B6B 0%, #C0392B 100%); border:none;">
            <i class="bi bi-person-heart"></i> Nuevo Paciente
        </a>
    </div>
</div>

<div class="modern-card p-0">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light">
                <tr>
                    <th scope="col" class="py-3 ps-4 text-muted text-uppercase fw-semibold" style="font-size: 0.8rem;">Paciente</th>
                    <th scope="col" class="py-3 text-muted text-uppercase fw-semibold" style="font-size: 0.8rem;">DNI / Edad</th>
                    <th scope="col" class="py-3 text-muted text-uppercase fw-semibold" style="font-size: 0.8rem;">Cobertura Médica</th>
                    <th scope="col" class="py-3 text-muted text-uppercase fw-semibold" style="font-size: 0.8rem;">Última Consulta</th>
                    <th scope="col" class="py-3 text-end pe-4 text-muted text-uppercase fw-semibold" style="font-size: 0.8rem;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($patients as $patient)
                <tr onclick="window.location='{{ route('patients.show', $patient) }}'" style="cursor: pointer;" class="border-bottom">
                    <td class="ps-4 py-3">
                        <div class="d-flex align-items-center">
                            <div class="icon-box rounded-circle shadow-sm bg-light text-primary d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                                <i class="bi bi-file-medical fs-5"></i>
                            </div>
                            <div class="ms-3">
                                <a href="{{ route('patients.show', $patient) }}" class="d-block fw-bold text-dark text-decoration-none">
                                    {{ $patient->last_name }}, {{ $patient->first_name }}
                                </a>
                                <small class="text-muted">ID: {{ str_pad($patient->id, 6, '0', STR_PAD_LEFT) }}</small>
                            </div>
                        </div>
                    </td>
                    <td class="py-3">
                        <span class="d-block fw-medium text-dark">{{ $patient->dni }}</span>
                        <small class="text-muted">{{ \Carbon\Carbon::parse($patient->date_of_birth)->age }} años</small>
                    </td>
                    <td class="py-3">
                        @if($patient->obra_social)
                            <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-3 py-2 rounded-pill fw-medium">
                                {{ $patient->obra_social }} {{ $patient->plan ? ' - '.$patient->plan : '' }}
                            </span>
                        @else
                            <span class="text-muted small">Particular</span>
                        @endif
                    </td>
                    <td class="py-3">
                        @if($patient->latestVisit)
                            <span class="d-block fw-bold text-dark" style="font-size: 0.85rem;"><i class="bi bi-calendar-check text-success me-1"></i> {{ $patient->latestVisit->created_at->format('d M Y') }}</span>
                            <small class="text-muted" style="font-size: 0.75rem;">Por: Dr(a). {{ $patient->latestVisit->doctor->name ?? 'Staff' }}</small>
                        @else
                            <span class="text-muted small"><i class="bi bi-clock-history me-1"></i> Sin visitas previas</span>
                        @endif
                    </td>
                    <td class="text-end pe-4 py-3">
                        <a href="{{ route('patients.show', $patient) }}" class="btn btn-light btn-sm rounded-circle p-2 shadow-sm text-primary" title="Abrir Historia Clínica">
                            <i class="bi bi-folder2-open"></i>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center py-5 text-muted">
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
