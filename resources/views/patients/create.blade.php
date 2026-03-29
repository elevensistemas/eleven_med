@extends('layouts.admin')

@section('title', 'Nuevo Paciente')
@section('subtitle', 'Alta de Historia Clínica')

@section('content')
<div class="modern-card p-4">
    <form action="{{ route('patients.store') }}" method="POST">
        @csrf
        <div class="row">
            <!-- Columna Izquierda: Datos Personales -->
            <div class="col-md-6 border-end pe-md-4">
                <h6 class="fw-bold mb-4 text-primary"><i class="bi bi-person-lines-fill me-2"></i>Datos Personales</h6>
                
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label text-muted small">Nombre(s) *</label>
                        <input type="text" name="first_name" class="form-control bg-light border-0 shadow-none" required value="{{ old('first_name') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small">Apellidos *</label>
                        <input type="text" name="last_name" class="form-control bg-light border-0 shadow-none" required value="{{ old('last_name') }}">
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label text-muted small">DNI *</label>
                        <input type="text" name="dni" class="form-control bg-light border-0 shadow-none" required value="{{ old('dni') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small">Nacimiento *</label>
                        <input type="date" name="date_of_birth" class="form-control bg-light border-0 shadow-none" required value="{{ old('date_of_birth') }}">
                    </div>
                    
                    <div class="col-md-12">
                        <label class="form-label text-muted small">Dirección</label>
                        <input type="text" name="address" class="form-control bg-light border-0 shadow-none" value="{{ old('address') }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label text-muted small">Teléfono / Celular</label>
                        <input type="tel" name="phone" class="form-control bg-light border-0 shadow-none" value="{{ old('phone') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small">Correo Electrónico</label>
                        <input type="email" name="email" class="form-control bg-light border-0 shadow-none" value="{{ old('email') }}">
                    </div>

                    <div class="col-md-12">
                        <label class="form-label text-muted small">Recomendado por</label>
                        <input type="text" name="recommendation" class="form-control bg-light border-0 shadow-none" value="{{ old('recommendation') }}">
                    </div>
                </div>
            </div>

            <!-- Columna Derecha: Datos Clínicos y Finanzas -->
            <div class="col-md-6 ps-md-4">
                <h6 class="fw-bold mb-4 text-danger"><i class="bi bi-heart-pulse me-2"></i>Cobertura Médica & Clínica</h6>
                
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label text-muted small">Obra Social / Prepaga</label>
                        <input type="text" name="obra_social" class="form-control bg-light border-0 shadow-none" value="{{ old('obra_social') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small">Plan</label>
                        <input type="text" name="plan" class="form-control bg-light border-0 shadow-none" value="{{ old('plan') }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label text-muted small">Nro. Afiliado</label>
                        <input type="text" name="affiliate_number" class="form-control bg-light border-0 shadow-none" value="{{ old('affiliate_number') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small">Condición IVA</label>
                        <select name="iva_condition" class="form-control bg-light border-0 shadow-none">
                            <option value="">Seleccionar</option>
                            <option value="Consumidor Final">Consumidor Final</option>
                            <option value="Responsable Inscripto">Responsable Inscripto</option>
                            <option value="Exento">Exento</option>
                        </select>
                    </div>

                    <div class="col-md-12 mt-4">
                        <h6 class="fw-bold mb-3 text-secondary border-bottom pb-2">Asignaciones Internas</h6>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label text-muted small">Profesión</label>
                        <input type="text" name="profession" class="form-control bg-light border-0 shadow-none" value="{{ old('profession') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small">Nro. Siniestro (Legales)</label>
                        <input type="text" name="nro_siniestro" class="form-control bg-light border-0 shadow-none" value="{{ old('nro_siniestro') }}">
                    </div>

                    <div class="col-md-12">
                        <label class="form-label text-muted small">Médico / Director Asignado (Predeterminado)</label>
                        <select name="director_id" class="form-control bg-light border-0 shadow-none">
                            <option value="">-- Sin Asignar --</option>
                            @foreach($doctors as $doc)
                                <option value="{{ $doc->id }}">{{ $doc->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <hr class="my-5 border-light">

        <div class="d-flex justify-content-end gap-2">
            <a href="{{ route('patients.index') }}" class="btn btn-light px-4 py-2 rounded-pill fw-medium">Cancelar</a>
            <button type="submit" class="btn btn-primary px-5 py-2 rounded-pill fw-medium d-flex align-items-center gap-2" style="background: linear-gradient(135deg, #FF6B6B 0%, #C0392B 100%); border:none;">
                <i class="bi bi-save"></i> Guardar Ficha
            </button>
        </div>
    </form>
</div>
@endsection
