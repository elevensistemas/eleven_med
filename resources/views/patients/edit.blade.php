@extends('layouts.admin')

@section('title', 'Editar Paciente')
@section('subtitle', 'Modificando Ficha de ' . $patient->last_name)

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
    .form-label {
        font-weight: 600 !important;
        color: #495057 !important;
        margin-bottom: 0.4rem !important;
        font-size: 0.85rem !important;
    }
</style>
<div class="modern-card p-4 mb-4">
    <div class="d-flex align-items-center mb-4">
        <div class="icon-box rounded-circle bg-warning text-dark d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px; font-size: 1.5rem;">
            <i class="bi bi-pencil-square"></i>
        </div>
        <div>
            <h5 class="fw-bold mb-0">Actualizar Datos de Registro</h5>
            <small class="text-muted">No olvides guardar los cambios al finalizar</small>
        </div>
    </div>

    <form action="{{ route('patients.update', $patient) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="row">
            <!-- Columna Izquierda: Datos Personales -->
            <div class="col-md-6 border-end pe-md-4">
                <h6 class="fw-bold mb-4 text-primary"><i class="bi bi-person-lines-fill me-2"></i>Datos Personales</h6>
                
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label text-muted small">Nombre(s) *</label>
                        <input type="text" name="first_name" class="form-control modern-input" required value="{{ old('first_name', $patient->first_name) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small">Apellidos *</label>
                        <input type="text" name="last_name" class="form-control modern-input" required value="{{ old('last_name', $patient->last_name) }}">
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label text-muted small">DNI *</label>
                        <input type="text" name="dni" class="form-control modern-input" required value="{{ old('dni', $patient->dni) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small">Nacimiento *</label>
                        <input type="date" name="date_of_birth" class="form-control modern-input" required value="{{ old('date_of_birth', $patient->date_of_birth) }}">
                    </div>
                    
                    <div class="col-md-12">
                        <label class="form-label text-muted small">Dirección</label>
                        <input type="text" name="address" class="form-control modern-input" value="{{ old('address', $patient->address) }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label text-muted small">Teléfono / Celular</label>
                        <input type="tel" name="phone" class="form-control modern-input" value="{{ old('phone', $patient->phone) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small">Correo Electrónico</label>
                        <input type="email" name="email" class="form-control modern-input" value="{{ old('email', $patient->email) }}">
                    </div>

                    <div class="col-md-12">
                        <label class="form-label text-muted small">Recomendado por</label>
                        <input type="text" name="recommendation" class="form-control modern-input" value="{{ old('recommendation', $patient->recommendation) }}">
                    </div>
                </div>
            </div>

            <!-- Columna Derecha: Datos Clínicos y Finanzas -->
            <div class="col-md-6 ps-md-4">
                <h6 class="fw-bold mb-4 text-danger"><i class="bi bi-heart-pulse me-2"></i>Cobertura Médica & Clínica</h6>
                
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label text-muted small">Obra Social / Prepaga</label>
                        <select name="obra_social" class="form-select bg-white border border-secondary border-opacity-25 shadow-sm">
                            <option value="">-- Seleccionar --</option>
                            @foreach($obrasSociales as $os)
                                <option value="{{ $os->name }}" {{ old('obra_social', $patient->obra_social) == $os->name ? 'selected' : '' }}>{{ $os->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small">Plan</label>
                        <input type="text" name="plan" class="form-control modern-input" value="{{ old('plan', $patient->plan) }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label text-muted small">Nro. Afiliado</label>
                        <input type="text" name="affiliate_number" class="form-control modern-input" value="{{ old('affiliate_number', $patient->affiliate_number) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small">Condición IVA</label>
                        <select name="iva_condition" class="form-control modern-input">
                            <option value="">Seleccionar</option>
                            <option value="Consumidor Final" {{ old('iva_condition', $patient->iva_condition) == 'Consumidor Final' ? 'selected' : '' }}>Consumidor Final</option>
                            <option value="Responsable Inscripto" {{ old('iva_condition', $patient->iva_condition) == 'Responsable Inscripto' ? 'selected' : '' }}>Responsable Inscripto</option>
                            <option value="Exento" {{ old('iva_condition', $patient->iva_condition) == 'Exento' ? 'selected' : '' }}>Exento</option>
                        </select>
                    </div>

                    <div class="col-md-12 mt-4">
                        <h6 class="fw-bold mb-3 text-secondary border-bottom pb-2">Asignaciones Internas</h6>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label text-muted small">Profesión</label>
                        <input type="text" name="profession" class="form-control modern-input" value="{{ old('profession', $patient->profession) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small">Nro. Siniestro (Legales)</label>
                        <input type="text" name="nro_siniestro" class="form-control modern-input" value="{{ old('nro_siniestro', $patient->nro_siniestro) }}">
                    </div>

                    <div class="col-md-12">
                        <label class="form-label text-muted small">Médico / Director Asignado (Predeterminado)</label>
                        <select name="director_id" class="form-control modern-input">
                            <option value="">-- Sin Asignar --</option>
                            @foreach($doctors as $doc)
                                <option value="{{ $doc->id }}" {{ old('director_id', $patient->director_id) == $doc->id ? 'selected' : '' }}>{{ $doc->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-12 mt-4">
                        <h6 class="fw-bold mb-3 text-success border-bottom pb-2">Historial Quirúrgico y Clínica</h6>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label text-muted small">Notas Quirúrgicas, Cirugías Previas o Comentarios Médicos</label>
                        <textarea name="medical_notes" rows="4" class="form-control modern-input" placeholder="Especifique cirugías (ej. Cataratas OD, Lasik OI), antecedentes, alergias...">{{ old('medical_notes', $patient->medical_notes) }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <hr class="my-5 border-light">

        <div class="d-flex justify-content-end gap-2">
            <a href="{{ route('patients.show', $patient) }}" class="btn btn-light px-4 py-2 rounded-pill fw-medium">Cancelar</a>
            <button type="submit" class="btn btn-warning px-5 py-2 rounded-pill fw-bold text-dark d-flex align-items-center gap-2" style="background: linear-gradient(135deg, #F9D423 0%, #FF4E50 100%); border:none; color:white!important;">
                <i class="bi bi-save"></i> Guardar Cambios
            </button>
        </div>
    </form>
</div>
@endsection
