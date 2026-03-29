@extends('layouts.admin')

@section('title', 'Nueva Evolución Clínica')
@section('subtitle', 'Registrando visita para: ' . $patient->last_name . ', ' . $patient->first_name)

@section('content')
<div class="row align-items-center mb-4">
    <div class="col">
        <a href="{{ route('patients.show', $patient) }}" class="btn btn-light rounded-pill px-4 text-primary fw-bold shadow-sm border-0">
            <i class="bi bi-arrow-left me-2"></i> Volver a la Ficha
        </a>
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

<style>
.input-group-text { font-size: 0.9rem; letter-spacing: 0.5px; }
.form-control::placeholder { color: #adb5bd; }
</style>
@endsection
