@extends('layouts.admin')

@section('title', 'Nueva Evolución Clínica')
@section('subtitle', 'Registrando visita para: ' . $patient->last_name . ', ' . $patient->first_name)

@section('content')
<div class="row align-items-center mb-4">
    <div class="col d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div class="d-flex gap-2 align-items-center">
            <a href="{{ route('patients.show', $patient) }}" class="btn btn-light rounded-pill px-4 text-primary fw-bold shadow-sm border-0">
                <i class="bi bi-arrow-left me-2"></i> Volver a la Ficha
            </a>
            <button type="button" class="btn rounded-pill px-4 text-white fw-bold shadow-sm border-0 d-flex align-items-center gap-2" data-bs-toggle="offcanvas" data-bs-target="#historyOffcanvas" style="background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);">
                <i class="bi bi-journal-medical"></i> Ver Historia Completa
            </button>
        </div>

        <div class="d-flex align-items-center gap-2 bg-white px-3 py-2 rounded-pill shadow-sm border">
            <label class="small fw-bold text-muted mb-0 text-nowrap"><i class="bi bi-person-badge text-primary me-1"></i> Médico actuante:</label>
            <select name="doctor_id" form="formNewVisit" class="form-select form-select-sm border-0 bg-light rounded-pill shadow-none fw-bold text-dark" style="min-width: 200px;">
                @foreach($doctors as $doc)
                    <option value="{{ $doc->id }}" {{ (old('doctor_id', auth()->id()) == $doc->id) ? 'selected' : '' }}>
                        {{ $doc->name }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>
</div>

<div class="modern-card p-4">

    <!-- Bloque Superior: Consulta Anterior en Detalle (Desplegable y Copiable) -->
    @if(isset($lastVisit) && $lastVisit)
        <div class="card border rounded-4 shadow-sm mb-4" style="background: linear-gradient(135deg, rgba(30, 60, 114, 0.04) 0%, rgba(42, 82, 152, 0.07) 100%); border: 1.5px solid rgba(42, 82, 152, 0.25) !important;">
            <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center py-3 px-4 flex-wrap gap-2" style="cursor: pointer;" data-bs-toggle="collapse" data-bs-target="#prevVisitCollapse">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="badge rounded-pill px-3 py-2 text-white shadow-sm" style="background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);">
                        <i class="bi bi-clock-history me-1"></i> Consulta Anterior: {{ $lastVisit->created_at->format('d/m/Y - H:i') }}
                    </span>
                    <span class="text-dark small fw-bold"><i class="bi bi-person me-1 text-primary"></i> Dr. {{ $lastVisit->doctor->name ?? 'Staff' }}</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-white bg-white border rounded-pill px-3 py-1 shadow-sm text-primary fw-bold" onclick="copyPreviousVisitData(event)">
                        <i class="bi bi-clipboard-plus me-1 text-success"></i> Copiar antecedentes a esta consulta
                    </button>
                    <button type="button" class="btn btn-sm btn-light rounded-circle p-1" style="width: 30px; height: 30px;">
                        <i class="bi bi-chevron-down" id="prevVisitIcon"></i>
                    </button>
                </div>
            </div>
            <div class="collapse show" id="prevVisitCollapse">
                <div class="card-body pt-0 px-4 pb-4 border-top border-secondary border-opacity-10 mt-2">
                    <div class="row g-3 small mt-1">
                        <div class="col-md-6">
                            <div class="p-3 bg-white rounded-3 border shadow-sm">
                                <strong class="text-primary d-block mb-1"><i class="bi bi-chat-square-text me-1"></i> Motivo Previo:</strong>
                                <div class="text-dark" id="prevMotivo">{{ $lastVisit->motivo_consulta ?: 'Sin registrar' }}</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 bg-white rounded-3 border shadow-sm">
                                <strong class="text-danger d-block mb-1"><i class="bi bi-check2-circle me-1"></i> Diagnóstico Previo:</strong>
                                <div class="text-dark fw-bold" id="prevDiag">{{ $lastVisit->diagnostico ?: 'Sin registrar' }}</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 bg-white rounded-3 border shadow-sm">
                                <strong class="text-secondary d-block mb-1"><i class="bi bi-eye me-1"></i> Ant. Oftalmológicos:</strong>
                                <div class="text-dark" id="prevAntOft">{{ $lastVisit->antecedentes_oftalmologicos ?: '-' }}</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 bg-white rounded-3 border shadow-sm">
                                <strong class="text-success d-block mb-1"><i class="bi bi-capsule me-1"></i> Tratamiento Oftalmológico:</strong>
                                <div class="text-dark fw-bold text-success" id="prevTratOft">{{ $lastVisit->tratamiento_oftalmologico ?: '-' }}</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 bg-white rounded-3 border shadow-sm">
                                <strong class="text-secondary d-block mb-1"><i class="bi bi-activity me-1"></i> Examen Físico:</strong>
                                <div class="text-dark">
                                    <span class="me-2"><strong>PIO:</strong> {{ $lastVisit->pio ?: '-' }}</span> | 
                                    <span class="mx-2"><strong>BMC:</strong> {{ $lastVisit->bmc ?: '-' }}</span> | 
                                    <span class="ms-2"><strong>OBI:</strong> {{ $lastVisit->obi ?: '-' }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 bg-white rounded-3 border shadow-sm">
                                <strong class="text-secondary d-block mb-1"><i class="bi bi-eyeglasses me-1"></i> Agudeza Visual:</strong>
                                <div class="text-dark">
                                    <span class="me-2"><strong>Lejos:</strong> OD {{ $lastVisit->av_od_lejos ?: '-' }} / OI {{ $lastVisit->av_oi_lejos ?: '-' }}</span> | 
                                    <span class="ms-2"><strong>Cerca:</strong> OD {{ $lastVisit->av_od_cerca ?: '-' }} / OI {{ $lastVisit->av_oi_cerca ?: '-' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="alert alert-light border rounded-4 shadow-sm mb-4 d-flex align-items-center gap-2 text-muted small py-2 px-3">
            <i class="bi bi-info-circle text-primary fs-5"></i>
            <span>Esta es la <strong>primera consulta registrada</strong> del paciente. Puedes completar los antecedentes que se guardarán para futuras consultas.</span>
        </div>
    @endif

    <form id="formNewVisit" action="{{ route('patient.visits.store', $patient) }}" method="POST">
        @csrf
        
        <!-- Información de Ficha: Derivación y Comentarios Médicos -->
        <div class="card border rounded-4 shadow-sm p-3 mb-4" style="background: linear-gradient(135deg, rgba(94, 106, 210, 0.04) 0%, rgba(126, 68, 139, 0.04) 100%); border: 1.5px solid rgba(94, 106, 210, 0.15) !important;">
            <div class="row g-3">
                <div class="col-md-6 border-end pe-md-3">
                    <label class="form-label text-dark small fw-bold d-flex align-items-center justify-content-between">
                        <span><i class="bi bi-person-lines-fill text-primary me-1"></i> Derivado / Recomendado por</span>
                        <small class="text-muted fw-normal" style="font-size:0.75rem;">Ficha del Paciente</small>
                    </label>
                    <input type="text" name="recommendation" value="{{ old('recommendation', $patient->recommendation) }}" class="form-control bg-white border shadow-none rounded-3" placeholder="Ej: Dr. Pérez, Óptica San Martín, Familiar...">
                </div>
                <div class="col-md-6 ps-md-3">
                    <label class="form-label text-dark small fw-bold d-flex align-items-center justify-content-between">
                        <span><i class="bi bi-journal-text text-warning me-1"></i> COMENTARIOS MÉDICOS</span>
                        <span class="badge bg-warning bg-opacity-25 text-dark border border-warning" style="font-size:0.65rem;">Solo Consultorio (No se imprime)</span>
                    </label>
                    <textarea name="medical_notes" rows="1" class="form-control bg-white border shadow-none rounded-3 auto-expand" style="min-height: 38px; resize: none; overflow-y: hidden; font-size:0.85rem;" placeholder="Notas y comentarios médicos permanentes del paciente...">{{ old('medical_notes', $patient->medical_notes) }}</textarea>
                </div>
            </div>
        </div>

        <!-- Bloque 1: Visita Actual -->
        <h5 class="fw-bold text-primary mb-3 border-bottom pb-2 d-flex align-items-center justify-content-between">
            <span><i class="bi bi-clipboard-plus me-2"></i> Motivo y Diagnóstico</span>
            <small class="text-muted fw-normal fs-6">Campos compactos auto-expandibles</small>
        </h5>
        <div class="row g-3 mb-4">
            <div class="col-md-12">
                <label class="form-label text-muted small fw-bold">Motivo Consulta</label>
                <textarea name="motivo_consulta" rows="1" class="form-control bg-light border-0 shadow-none rounded-3 p-2 px-3 auto-expand" style="min-height: 42px; resize: none; overflow-y: hidden;" placeholder="Síntomas principales reportados por el paciente..."></textarea>
            </div>
            <div class="col-md-12">
                <label class="form-label text-muted small fw-bold">Diagnóstico Presuntivo / Definitivo</label>
                <textarea name="diagnostico" rows="1" class="form-control bg-light border-0 shadow-none rounded-3 p-2 px-3 auto-expand" style="min-height: 42px; resize: none; overflow-y: hidden;" placeholder="Impresión diagnóstica final de esta visita..."></textarea>
            </div>
        </div>

        <!-- Bloque 2: Anamnesis / Historial -->
        <h5 class="fw-bold text-primary mb-3 border-bottom pb-2"><i class="bi bi-clock-history me-2"></i> Historial y Tratamientos Previos</h5>
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <label class="form-label text-muted small fw-bold">Antecedentes Oftalmológicos</label>
                <textarea name="antecedentes_oftalmologicos" id="inputAntOft" rows="1" class="form-control bg-light border-0 shadow-none rounded-3 p-2 px-3 auto-expand" style="min-height: 42px; resize: none; overflow-y: hidden;" placeholder="Cirugías visuales, glaucoma familiar, patologías oculares previas..."></textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label text-muted small fw-bold text-success"><i class="bi bi-capsule"></i> Tratamiento Oftalmológico (Actual/Recetado)</label>
                <textarea name="tratamiento_oftalmologico" id="inputTratOft" rows="1" class="form-control bg-light border-0 shadow-none rounded-3 p-2 px-3 auto-expand border-start border-4 border-success" style="min-height: 42px; resize: none; overflow-y: hidden;" placeholder="Gotas, medicación intraocular..."></textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label text-muted small fw-bold">Antecedentes Generales</label>
                <textarea name="antecedentes_generales" rows="1" class="form-control bg-light border-0 shadow-none rounded-3 p-2 px-3 auto-expand" style="min-height: 42px; resize: none; overflow-y: hidden;" placeholder="Hipertensión, diabetes, cirugías sistémicas..."></textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label text-muted small fw-bold">Tratamientos Generales</label>
                <textarea name="tratamientos_generales" rows="1" class="form-control bg-light border-0 shadow-none rounded-3 p-2 px-3 auto-expand" style="min-height: 42px; resize: none; overflow-y: hidden;" placeholder="Medicación crónica sistémica..."></textarea>
            </div>
        </div>

        <!-- Bloque 3: Examen Físico y Agudeza Visual (OD / OI estrictos) -->
        <h5 class="fw-bold text-primary mb-3 border-bottom pb-2"><i class="bi bi-eye-fill me-2"></i> Examen Biomicroscópico y Agudeza Visual</h5>
        <div class="row g-4 mb-4">
            
            <!-- Columna Izquierda: Métricas Físicas -->
            <div class="col-lg-5 border-end pe-lg-4">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">PIO (Presión Intraocular)</label>
                    <input type="text" name="pio" class="form-control bg-light border-0 shadow-none rounded-pill px-4" placeholder="Ej: 14 mmHg OD / 16 mmHg OI">
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">BMC (Biomicroscopía)</label>
                    <input type="text" name="bmc" class="form-control bg-light border-0 shadow-none rounded-pill px-4" placeholder="Córnea clara, CA formada...">
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">OBI (Oftalmoscopía)</label>
                    <input type="text" name="obi" class="form-control bg-light border-0 shadow-none rounded-pill px-4" placeholder="Papila excav. fisiológica, mácula sana...">
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">Otros Hallazgos / Estudios Solicitados</label>
                    <input type="text" name="otros_examen" class="form-control bg-light border-0 shadow-none rounded-pill px-4" placeholder="Ej: Pido OCT macular">
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

        <div class="text-end pt-4 border-top mt-4 d-flex justify-content-end gap-3 align-items-center">
            <button type="submit" class="btn btn-primary rounded-pill px-5 py-2 fw-bold shadow-sm fs-5" style="background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%); border:none;">
                <i class="bi bi-save me-2"></i> Guardar Historia Clínica
            </button>
        </div>

    </form>
</div>

<!-- Offcanvas de Historia Clínica Completa -->
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

<script>
function copyPreviousVisitData(e) {
    e.stopPropagation();
    const antOft = document.getElementById('prevAntOft')?.innerText?.trim();
    const tratOft = document.getElementById('prevTratOft')?.innerText?.trim();
    
    if (antOft && antOft !== '-') {
        const inputAnt = document.getElementById('inputAntOft');
        if (inputAnt) {
            inputAnt.value = antOft;
            inputAnt.dispatchEvent(new Event('input'));
        }
    }

    if (tratOft && tratOft !== '-') {
        const inputTrat = document.getElementById('inputTratOft');
        if (inputTrat) {
            inputTrat.value = tratOft;
            inputTrat.dispatchEvent(new Event('input'));
        }
    }

    alert('¡Antecedentes y tratamientos oftalmológicos copiados a la consulta actual!');
}

function initAutoExpand() {
    document.querySelectorAll('textarea.auto-expand').forEach(textarea => {
        const resize = () => {
            textarea.style.height = 'auto';
            textarea.style.height = (textarea.scrollHeight > 38 ? textarea.scrollHeight + 4 : 38) + 'px';
        };
        textarea.addEventListener('input', resize);
        resize();
    });
}

document.addEventListener('DOMContentLoaded', initAutoExpand);
</script>

<style>
.input-group-text { font-size: 0.9rem; letter-spacing: 0.5px; }
.form-control::placeholder { color: #adb5bd; }
textarea.auto-expand { transition: height 0.15s ease; }
</style>
@endsection
