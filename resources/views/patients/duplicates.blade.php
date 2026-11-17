@extends('layouts.admin')

@section('title', 'Buscador de Fichas Duplicadas')
@section('subtitle', 'Detección y unificación segura de fichas duplicadas por Teléfono, DNI, Apellido o Email')

@section('content')
@if(session('success'))
    <div class="alert alert-success border-0 bg-success bg-opacity-10 text-success rounded-4 mb-4 shadow-sm">
        <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
    </div>
@endif

<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <a href="{{ route('patients.index') }}" class="btn btn-light bg-white border rounded-pill px-4 fw-bold text-primary shadow-sm">
        <i class="bi bi-arrow-left me-2"></i> Volver a Base de Pacientes
    </a>

    <!-- Criterios rápidos -->
    <div class="d-flex flex-wrap gap-2">
        @php
            $tabs = [
                'all' => ['label' => 'Todos los Criterios', 'icon' => 'bi-intersect'],
                'phone' => ['label' => 'Por Teléfono', 'icon' => 'bi-telephone-fill'],
                'dni' => ['label' => 'Por DNI', 'icon' => 'bi-card-text'],
                'last_name' => ['label' => 'Por Apellido', 'icon' => 'bi-people-fill'],
                'email' => ['label' => 'Por Mail', 'icon' => 'bi-envelope-fill'],
            ];
        @endphp
        @foreach($tabs as $key => $meta)
            <a href="{{ route('patients.duplicates', ['by' => $key, 'q' => $filterTerm]) }}"
               class="btn btn-sm rounded-pill px-3 py-2 fw-bold shadow-sm {{ $criterion === $key ? 'btn-primary text-white' : 'btn-white bg-white border text-dark' }}">
                <i class="bi {{ $meta['icon'] }} me-1"></i> {{ $meta['label'] }}
            </a>
        @endforeach
    </div>
</div>

<!-- Buscador puntual de duplicados -->
<div class="modern-card p-3 px-4 mb-4 rounded-4 shadow-sm border-0 bg-white">
    <form action="{{ route('patients.duplicates') }}" method="GET" class="row g-2 align-items-center">
        <input type="hidden" name="by" value="{{ $criterion }}">
        <div class="col-md-8">
            <div class="input-group bg-light rounded-pill px-2 border">
                <span class="input-group-text bg-transparent border-0 text-muted"><i class="bi bi-search"></i></span>
                <input type="text" name="q" value="{{ $filterTerm }}" class="form-control bg-transparent border-0 shadow-none" placeholder="Filtrar duplicados por apellido, teléfono, DNI o correo específico...">
                @if($filterTerm !== '')
                    <a href="{{ route('patients.duplicates', ['by' => $criterion]) }}" class="btn btn-link text-muted text-decoration-none"><i class="bi bi-x-lg"></i></a>
                @endif
            </div>
        </div>
        <div class="col-md-4 d-flex gap-2 justify-content-md-end">
            <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm">
                <i class="bi bi-funnel-fill me-1"></i> Filtrar Duplicados
            </button>
            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-3 py-2 d-inline-flex align-items-center fw-bold">
                {{ count($groups) }} grupo(s) detectado(s)
            </span>
        </div>
    </form>
</div>

@if(count($groups) === 0)
    <div class="modern-card p-5 text-center rounded-4 shadow-sm border-0 bg-white">
        <i class="bi bi-check-circle-fill text-success opacity-75" style="font-size: 3.5rem;"></i>
        <h5 class="fw-bold text-dark mt-3">No se detectaron fichas duplicadas</h5>
        <p class="text-muted mb-0">No existen coincidencias repetidas para el criterio seleccionado{{ $filterTerm ? ' ("' . $filterTerm . '")' : '' }}.</p>
    </div>
@else
    <div class="d-flex flex-column gap-4">
        @foreach($groups as $gIdx => $group)
            @php
                // Sugerir como ficha principal la que tenga más historia clínica (o la más antigua en caso de empate)
                $sortedPts = $group['patients']->sortByDesc(function($pt) {
                    return ($pt->visits_count * 100) + ($pt->surgeries_count * 50) + ($pt->studies_count * 20) + $pt->appointments_count;
                })->values();
                $primaryCandidate = $sortedPts->first();
            @endphp
            <div class="modern-card p-0 rounded-4 overflow-hidden shadow-sm border bg-white">
                <div class="px-4 py-3 bg-light border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span class="badge bg-{{ $group['color'] }} rounded-pill px-3 py-1 fw-bold">
                            <i class="bi {{ $group['icon'] }} me-1"></i> {{ $group['badge'] }}
                        </span>
                        <span class="fw-bold text-dark fs-6">{{ $group['key_label'] }}</span>
                        <span class="badge bg-white text-secondary border rounded-pill">{{ $sortedPts->count() }} fichas</span>
                    </div>
                    <small class="text-muted">Puede editar los datos o unificar una ficha secundaria dentro de la ficha principal sin perder consultas ni estudios.</small>
                </div>

                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light small text-muted">
                            <tr>
                                <th class="ps-4">Carpeta / Paciente</th>
                                <th>DNI</th>
                                <th>Teléfono</th>
                                <th>Email</th>
                                <th>Ingreso</th>
                                <th class="text-center">Actividad Clínica</th>
                                <th class="text-end pe-4">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($sortedPts as $idx => $pt)
                                <tr class="{{ $idx === 0 ? 'bg-success bg-opacity-10' : '' }}">
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center gap-2">
                                            <div>
                                                <a href="{{ route('patients.show', $pt) }}" class="fw-bold text-dark text-decoration-none">
                                                    {{ $pt->last_name }}, {{ $pt->first_name }}
                                                </a>
                                                @if($idx === 0)
                                                    <span class="badge bg-success text-white rounded-pill ms-1" style="font-size: 0.68rem;">Principal sugerida</span>
                                                @endif
                                                <div class="small text-muted">Carpeta #{{ $pt->code ?: str_pad($pt->id, 6, '0', STR_PAD_LEFT) }} · {{ $pt->obra_social ?: 'Particular' }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="fw-semibold">{{ $pt->dni ?: '-' }}</td>
                                    <td>{{ $pt->phone ?: '-' }}</td>
                                    <td class="small">{{ $pt->email ?: '-' }}</td>
                                    <td class="small">{{ $pt->created_at ? $pt->created_at->format('d/m/Y') : '-' }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-white text-dark border me-1" title="Consultas">{{ $pt->visits_count }} Cons.</span>
                                        <span class="badge bg-white text-danger border me-1" title="Cirugías">{{ $pt->surgeries_count }} Qx</span>
                                        <span class="badge bg-white text-primary border me-1" title="Estudios">{{ $pt->studies_count }} Est.</span>
                                        <span class="badge bg-white text-secondary border" title="Turnos">{{ $pt->appointments_count }} Turnos</span>
                                    </td>
                                    <td class="text-end pe-4">
                                        <div class="d-inline-flex align-items-center gap-1 flex-wrap justify-content-end">
                                            <a href="{{ route('patients.show', $pt) }}" target="_blank" class="btn btn-sm btn-outline-secondary rounded-pill px-2.5 fw-bold" style="font-size: 0.74rem;">
                                                <i class="bi bi-folder2-open me-1"></i> Ver HC
                                            </a>
                                            <a href="{{ route('patients.edit', $pt) }}" class="btn btn-sm btn-outline-primary rounded-pill px-2.5 fw-bold" style="font-size: 0.74rem;">
                                                <i class="bi bi-pencil-square me-1"></i> Editar
                                            </a>
                                            @if($pt->id !== $primaryCandidate->id)
                                                <form action="{{ route('patients.duplicates.merge') }}" method="POST" class="d-inline m-0"
                                                      onsubmit="return confirm('¿Unificar la ficha de {{ addslashes($pt->last_name . ', ' . $pt->first_name) }} (#{{ $pt->id }}) dentro de la ficha principal #{{ $primaryCandidate->id }}? Todas sus consultas, estudios, cirugías y turnos pasarán a la ficha #{{ $primaryCandidate->id }}.');">
                                                    @csrf
                                                    <input type="hidden" name="primary_id" value="{{ $primaryCandidate->id }}">
                                                    <input type="hidden" name="duplicate_id" value="{{ $pt->id }}">
                                                    <button type="submit" class="btn btn-sm btn-warning text-dark rounded-pill px-2.5 fw-bold shadow-sm" style="font-size: 0.74rem;" title="Transferir historial a la ficha #{{ $primaryCandidate->id }} y eliminar este duplicado">
                                                        <i class="bi bi-arrows-collapse me-1"></i> Unificar en #{{ $primaryCandidate->id }}
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach
    </div>
@endif
@endsection
