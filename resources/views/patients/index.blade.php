@extends('layouts.admin')

@section('title', 'Pacientes')
@section('subtitle', 'Historia Clínica Electrónica y Agenda')

@section('content')
<div class="d-flex justify-content-end align-items-center mb-4">
    <div>
        <a href="{{ route('patients.create') }}" class="btn btn-primary rounded-pill px-4 py-2 shadow-sm fw-medium d-flex align-items-center gap-2" style="background: linear-gradient(135deg, #FF6B6B 0%, #C0392B 100%); border:none;">
            <i class="bi bi-person-heart"></i> Nuevo Paciente
        </a>
    </div>
</div>

<div class="modern-card p-0 rounded-4 overflow-hidden border-0 shadow-sm bg-white">
    <div class="px-4 py-3 border-bottom d-flex justify-content-between align-items-center patient-list-header">
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
                .table > :not(caption) > * > * { border-bottom-width: 2px; border-bottom-color: #d1d5db; }
                .patient-row:hover { background-color: #f8faff !important; transform: scale(1.002); box-shadow: 0 4px 15px rgba(0,0,0,0.02); }
                .theme-dark .patient-row:hover { background-color: #2a2a2a !important; }
            </style>
            <thead class="bg-light">
                <tr>
                    <th scope="col" class="py-3 ps-4 text-muted text-uppercase fw-bold" style="font-size: 0.75rem; letter-spacing: 0.5px;">Paciente</th>
                    <th scope="col" class="py-3 text-muted text-uppercase fw-bold" style="font-size: 0.75rem; letter-spacing: 0.5px;">DNI / Edad</th>
                    <th scope="col" class="py-3 text-muted text-uppercase fw-bold" style="font-size: 0.75rem; letter-spacing: 0.5px;">Cobertura Médica</th>
                    <th scope="col" class="py-3 text-muted text-uppercase fw-bold" style="font-size: 0.75rem; letter-spacing: 0.5px;">Última Consulta</th>
                    <th scope="col" class="py-3 text-center text-muted text-uppercase fw-bold" style="font-size: 0.75rem; letter-spacing: 0.5px;">Ausencias</th>
                    <th scope="col" class="py-3 text-end pe-4 text-muted text-uppercase fw-bold" style="font-size: 0.75rem; letter-spacing: 0.5px;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($patients as $patient)
                <tr onclick="window.location='{{ route('patients.show', $patient) }}'" style="cursor: pointer; transition: all 0.2s;" class="patient-row border-bottom">
                    <td class="ps-4 py-3">
                        <div class="d-flex align-items-center">
                            @if($patient->photo_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($patient->photo_path))
                                <img src="{{ asset('storage/' . $patient->photo_path) }}" alt="{{ $patient->first_name }}" class="rounded-circle shadow-sm" style="width: 45px; height: 45px; object-fit: cover; border: 2px solid #fff;">
                            @else
                                @php 
                                    $colors = ['#5e6ad2, #7e448b', '#00c6ff, #0072ff', '#f12711, #f5af19', '#11998e, #38ef7d'];
                                    $gradient = $colors[$patient->id % count($colors)];
                                @endphp
                                <div class="icon-box rounded-circle shadow-sm text-white d-flex align-items-center justify-content-center" style="width: 45px; height: 45px; background: linear-gradient(135deg, {{ $gradient }}); border: 2px solid #fff; box-shadow: 0 4px 10px rgba(0,0,0,0.1) !important;">
                                    <span class="fw-bold fs-5">{{ substr($patient->first_name, 0, 1) }}{{ substr($patient->last_name, 0, 1) }}</span>
                                </div>
                            @endif
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
                        @if($patient->latestVisit)
                            <span class="d-block fw-bold text-dark" style="font-size: 0.85rem;"><i class="bi bi-calendar-check text-success me-1"></i> {{ $patient->latestVisit->created_at->format('d M Y') }}</span>
                            <small class="text-muted" style="font-size: 0.75rem;">Por: Dr(a). {{ $patient->latestVisit->doctor->name ?? 'Staff' }}</small>
                        @else
                            <span class="text-muted small"><i class="bi bi-clock-history me-1"></i> Sin visitas previas</span>
                        @endif
                    </td>
                    <td class="py-3 text-center">
                        @if($patient->ausencias_count > 0)
                            <span class="badge bg-danger bg-opacity-10 text-danger rounded-pill px-3 py-1 fw-bold border border-danger border-opacity-25" title="Inasistencias registradas">
                                <i class="bi bi-exclamation-triangle-fill me-1"></i> {{ $patient->ausencias_count }}
                            </span>
                        @else
                            <span class="text-muted small">-</span>
                        @endif
                    </td>
                    <td class="text-end pe-4 py-3">
                        <a href="{{ route('patients.show', $patient) }}" class="btn btn-primary btn-sm rounded-circle p-2 shadow-sm" title="Abrir Historia Clínica" style="background: linear-gradient(135deg, #5e6ad2 0%, #7e448b 100%); border: none;">
                            <i class="bi bi-arrow-right text-white"></i>
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
