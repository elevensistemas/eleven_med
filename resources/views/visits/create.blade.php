@extends('layouts.admin')

@section('title', 'Nueva Evolución Clínica')
@section('subtitle', 'Registrando visita para: ' . $patient->last_name . ', ' . $patient->first_name)

@section('content')
<div class="row align-items-center mb-4">
    <div class="col d-flex gap-3">
        <a href="{{ route('patients.show', $patient) }}" class="btn btn-light rounded-pill px-4 text-primary fw-bold shadow-sm border-0">
            <i class="bi bi-arrow-left me-2"></i> Volver a la Ficha
        </a>
        <button type="button" class="btn rounded-pill px-4 text-white fw-bold shadow-sm border-0 d-flex align-items-center gap-2" data-bs-toggle="offcanvas" data-bs-target="#historyOffcanvas" style="background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);">
            <i class="bi bi-journal-medical"></i> Ver Historia Clínica
        </button>
    </div>
</div>

<div class="modern-card p-4">
    <form action="{{ route('patient.visits.store', $patient) }}" method="POST">
        @csrf
        
        <!-- Bloque 1: Visita Actual -->
        <h5 class="fw-bold text-primary mb-4 border-bottom pb-2"><i class="bi bi-clipboard-plus me-2"></i> Motivo y Diagnóstico</h5>
        <div class="row g-4 mb-5">
            <div class="col-md-12">
                <label class="form-label text-muted small fw-bold">Motivo Consulta</label>
                <textarea name="motivo_consulta" rows="2" class="form-control bg-light border-0 shadow-none rounded-4 p-3" placeholder="Síntomas principales reportados por el paciente..."></textarea>
            </div>
            <div class="col-md-12">
                <label class="form-label text-muted small fw-bold">Diagnóstico Presuntivo / Definitivo</label>
                <textarea name="diagnostico" rows="2" class="form-control bg-light border-0 shadow-none rounded-4 p-3" placeholder="Impresión diagnóstica final de esta visita..."></textarea>
            </div>
        </div>

        <!-- Bloque 2: Anamnesis / Historial -->
        <h5 class="fw-bold text-primary mb-4 border-bottom pb-2"><i class="bi bi-clock-history me-2"></i> Historial y Tratamientos Previos</h5>
        <div class="row g-4 mb-5">
            <div class="col-md-6">
                <label class="form-label text-muted small fw-bold">Antecedentes Oftalmológicos</label>
                <textarea name="antecedentes_oftalmologicos" rows="3" class="form-control bg-light border-0 shadow-none rounded-4 p-3" placeholder="Cirugías visuales, glaucoma familiar, patologías oculares previas..."></textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label text-muted small fw-bold text-success"><i class="bi bi-capsule"></i> Tratamiento Oftalmológico (Actual/Recetado)</label>
                <textarea name="tratamiento_oftalmologico" rows="3" class="form-control bg-light border-0 shadow-none rounded-4 p-3 border-start border-4 border-success mt-1" style="border-radius: 4px 16px 16px 4px !important;" placeholder="Gotas, medicación intraocular..."></textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label text-muted small fw-bold">Antecedentes Generales</label>
                <textarea name="antecedentes_generales" rows="2" class="form-control bg-light border-0 shadow-none rounded-4 p-3" placeholder="Hipertensión, diabetes, cirugías sistémicas..."></textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label text-muted small fw-bold">Tratamientos Generales</label>
                <textarea name="tratamientos_generales" rows="2" class="form-control bg-light border-0 shadow-none rounded-4 p-3" placeholder="Medicación crónica sistémica..."></textarea>
            </div>
        </div>

        <!-- Bloque 3: Examen Físico y Agudeza Visual (OD / OI estrictos) -->
        <h5 class="fw-bold text-primary mb-4 border-bottom pb-2"><i class="bi bi-eye-fill me-2"></i> Examen Biomicroscópico y Agudeza Visual</h5>
        <div class="row g-4 mb-4">
            
            <!-- Columna Izquierda: Métricas Físicas -->
            <div class="col-lg-5 border-end pe-lg-4">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">PIO (Presión Intraocular)</label>
                    <input type="text" name="pio" class="form-control form-control-lg bg-light border-0 shadow-none rounded-pill px-4" placeholder="Ej: 14 mmHg OD / 16 mmHg OI">
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">BMC (Biomicroscopía)</label>
                    <input type="text" name="bmc" class="form-control form-control-lg bg-light border-0 shadow-none rounded-pill px-4" placeholder="Córnea clara, CA formada...">
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">OBI (Oftalmoscopía)</label>
                    <input type="text" name="obi" class="form-control form-control-lg bg-light border-0 shadow-none rounded-pill px-4" placeholder="Papila excav. fisiológica, mácula sana...">
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">Otros Hallazgos / Estudios Solicitados</label>
                    <input type="text" name="otros_examen" class="form-control form-control-lg bg-light border-0 shadow-none rounded-pill px-4" placeholder="Ej: Pido OCT macular">
                </div>
            </div>

            <!-- Columna Derecha: Agudeza Visual -->
            <div class="col-lg-7 ps-lg-4">
                
                <h6 class="fw-bold mb-3 text-secondary">AV Lejos (S/c ó C/c)</h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="input-group bg-light rounded-pill overflow-hidden border-0 p-1 d-flex align-items-center">
                            <span class="input-group-text bg-white text-danger fw-bold border-0 rounded-pill ms-1" style="width: 50px; justify-content:center;">OD</span>
                            <input type="text" name="av_od_lejos" class="form-control bg-transparent border-0 shadow-none" placeholder="Ojo Derecho Lejos">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="input-group bg-light rounded-pill overflow-hidden border-0 p-1 d-flex align-items-center">
                            <span class="input-group-text bg-white text-primary fw-bold border-0 rounded-pill ms-1" style="width: 50px; justify-content:center;">OI</span>
                            <input type="text" name="av_oi_lejos" class="form-control bg-transparent border-0 shadow-none" placeholder="Ojo Izquierdo Lejos">
                        </div>
                    </div>
                </div>

                <h6 class="fw-bold mb-3 text-secondary">AV Cerca (Lectura)</h6>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="input-group bg-light rounded-pill overflow-hidden border-0 p-1 d-flex align-items-center">
                            <span class="input-group-text bg-white text-danger fw-bold border-0 rounded-pill ms-1" style="width: 50px; justify-content:center;">OD</span>
                            <input type="text" name="av_od_cerca" class="form-control bg-transparent border-0 shadow-none" placeholder="Ojo Derecho Cerca">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="input-group bg-light rounded-pill overflow-hidden border-0 p-1 d-flex align-items-center">
                            <span class="input-group-text bg-white text-primary fw-bold border-0 rounded-pill ms-1" style="width: 50px; justify-content:center;">OI</span>
                            <input type="text" name="av_oi_cerca" class="form-control bg-transparent border-0 shadow-none" placeholder="Ojo Izquierdo Cerca">
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <div class="text-end pt-4 border-top mt-4">
            <button type="submit" class="btn btn-primary rounded-pill px-5 py-2 fw-bold shadow-sm fs-5" style="background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%); border:none;">
                <i class="bi bi-save me-2"></i> Guardar Historia Clínica
            </button>
        </div>

    </form>
</div>

<!-- Offcanvas de Historia Clínica -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="historyOffcanvas" aria-labelledby="historyOffcanvasLabel" style="width: 450px; max-width: 90vw;">
    <div class="offcanvas-header border-bottom shadow-sm" style="background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); color: white;">
        <h5 class="offcanvas-title fw-bold" id="historyOffcanvasLabel"><i class="bi bi-clock-history me-2"></i> Historia Clínica - {{ $patient->first_name }}</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body" style="background-color: #f8f9fa;">
        
        <!-- Bloque de Cirugías -->
        @if($patient->surgeries && $patient->surgeries->count() > 0)
            <div class="mb-4">
                <h6 class="fw-bold text-muted mb-3 text-uppercase" style="letter-spacing: 1px; font-size: 0.8rem;"><i class="bi bi-bandaid text-danger me-1"></i> Historial Quirúrgico</h6>
                <div class="d-flex flex-column gap-2">
                    @foreach($patient->surgeries->sortByDesc('surgery_date') as $surg)
                        <div class="card border-0 shadow-sm rounded-3 bg-danger bg-opacity-10 border-start border-4 border-danger">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="fw-bold text-danger"><i class="bi bi-heart-pulse me-1"></i> Cirugía Ojo {{ $surg->eye }}</span>
                                    <span class="badge bg-white text-danger border rounded-pill">{{ $surg->surgery_date->format('d M Y') }}</span>
                                </div>
                                <p class="mb-0 text-dark" style="font-size: 0.85rem;">{{ $surg->notes }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Bloque de Evoluciones -->
        @if($patient->visits && $patient->visits->count() > 0)
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold text-muted mb-0 text-uppercase" style="letter-spacing: 1px; font-size: 0.8rem;">Evoluciones Previas</h6>
                <a href="{{ route('patients.history.print', $patient) }}" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill fw-bold">
                    <i class="bi bi-printer"></i> Formato Impresión
                </a>
            </div>
            <div class="d-flex flex-column gap-3">
                @foreach($patient->visits->sortByDesc('created_at') as $v)
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="badge bg-light text-primary border rounded-pill"><i class="bi bi-calendar-check me-1"></i> {{ $v->created_at->format('d M Y') }}</span>
                                <small class="text-muted" style="font-size: 0.7rem;">Dr. {{ $v->doctor->name ?? 'Staff' }}</small>
                            </div>
                            @if($v->motivo_consulta)
                                <p class="mb-1 text-dark" style="font-size: 0.85rem;"><span class="fw-bold">Motivo:</span> {{ $v->motivo_consulta }}</p>
                            @endif
                            
                            @if($v->diagnostico)
                                <p class="mb-1 text-dark" style="font-size: 0.85rem;"><span class="fw-bold">Diagnóstico:</span> <span class="text-danger fw-bold">{{ $v->diagnostico }}</span></p>
                            @endif
                            
                            @if($v->antecedentes_oftalmologicos)
                                <p class="mb-1 text-dark" style="font-size: 0.85rem;"><span class="fw-bold">Ant. Oftalmológicos:</span> {{ $v->antecedentes_oftalmologicos }}</p>
                            @endif
                            
                            @if($v->antecedentes_generales)
                                <p class="mb-1 text-dark" style="font-size: 0.85rem;"><span class="fw-bold">Ant. Generales:</span> {{ $v->antecedentes_generales }}</p>
                            @endif
                            
                            @if($v->tratamientos_generales)
                                <p class="mb-1 text-dark" style="font-size: 0.85rem;"><span class="fw-bold">Tratamientos Generales:</span> {{ $v->tratamientos_generales }}</p>
                            @endif

                            @if($v->pio || $v->bmc || $v->obi || $v->otros_examen)
                                <div class="mt-2 p-2 bg-light rounded border border-secondary border-opacity-10">
                                    <small class="fw-bold text-secondary d-block mb-1 border-bottom pb-1"><i class="bi bi-eye"></i> Examen Físico:</small>
                                    @if($v->pio)<div style="font-size: 0.8rem;"><span class="fw-bold">PIO:</span> {{ $v->pio }}</div>@endif
                                    @if($v->bmc)<div style="font-size: 0.8rem;"><span class="fw-bold">BMC:</span> {{ $v->bmc }}</div>@endif
                                    @if($v->obi)<div style="font-size: 0.8rem;"><span class="fw-bold">OBI:</span> {{ $v->obi }}</div>@endif
                                    @if($v->otros_examen)<div style="font-size: 0.8rem;"><span class="fw-bold">Otros:</span> {{ $v->otros_examen }}</div>@endif
                                </div>
                            @endif

                            @if($v->av_od_lejos || $v->av_oi_lejos || $v->av_od_cerca || $v->av_oi_cerca)
                                <div class="mt-2 p-2 bg-light rounded border border-secondary border-opacity-10">
                                    <small class="fw-bold text-secondary d-block mb-1 border-bottom pb-1"><i class="bi bi-eyeglasses"></i> Agudeza Visual:</small>
                                    @if($v->av_od_lejos || $v->av_oi_lejos)
                                        <div style="font-size: 0.8rem;"><span class="fw-bold">Lejos:</span> OD: {{ $v->av_od_lejos ?? '-' }} | OI: {{ $v->av_oi_lejos ?? '-' }}</div>
                                    @endif
                                    @if($v->av_od_cerca || $v->av_oi_cerca)
                                        <div style="font-size: 0.8rem;"><span class="fw-bold">Cerca:</span> OD: {{ $v->av_od_cerca ?? '-' }} | OI: {{ $v->av_oi_cerca ?? '-' }}</div>
                                    @endif
                                </div>
                            @endif

                            @if($v->tratamiento_oftalmologico)
                                <div class="mt-2 p-2 bg-success bg-opacity-10 rounded border-start border-3 border-success">
                                    <small class="fw-bold text-success d-block mb-1"><i class="bi bi-capsule"></i> Tratamiento Oftalmológico:</small>
                                    <span style="font-size: 0.85rem;">{{ $v->tratamiento_oftalmologico }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-5">
                <i class="bi bi-journal-x text-muted opacity-25" style="font-size: 4rem;"></i>
                <p class="text-muted mt-3 fw-bold">No hay visitas previas registradas.</p>
            </div>
        @endif
    </div>
</div>


<style>
.input-group-text { font-size: 0.9rem; letter-spacing: 0.5px; }
.form-control::placeholder { color: #adb5bd; }
</style>
@endsection
