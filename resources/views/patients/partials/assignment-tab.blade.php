<div class="tab-pane fade show active" id="assignment" role="tabpanel" tabindex="0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="fw-bold text-dark mb-0"><i class="bi bi-clock-history text-info me-2"></i> Flujo y Asignación Clínica</h5>
    </div>

    <!-- Panel de Nueva Asignación -->
    <div class="bg-light bg-opacity-50 border p-4 rounded-4 mb-5 shadow-sm position-relative overflow-hidden">
        <!-- Decoración de fondo -->
        <div class="position-absolute top-0 end-0 p-3 opacity-10">
            <i class="bi bi-person-bounding-box" style="font-size: 8rem;"></i>
        </div>
        
        <form action="{{ route('assignments.store', $patient) }}" method="POST" class="position-relative z-index-1">
            @csrf
            <div class="d-flex align-items-center mb-4">
                <div class="bg-white p-2 rounded-3 shadow-sm d-inline-block me-3 text-info">
                    <i class="bi bi-person-plus-fill fs-3"></i>
                </div>
                <div>
                    <h6 class="fw-bold mb-0">Agregar Asignación</h6>
                    <small class="text-muted d-block">Permite asignar este paciente a un proceso clínico interno (ej. Dilatación).</small>
                </div>
            </div>

            <div class="row bg-white rounded-3 shadow-sm border p-3 border-bottom-0 rounded-bottom-0 mx-0 g-3">
                <div class="col-md-6">
                    <label class="form-label text-muted small fw-bold">Tipo de Evento / Proceso</label>
                    <select name="event_type" class="form-select bg-light border-0 shadow-none text-dark" required>
                        <option value="" disabled selected>Seleccione proceso...</option>
                        <option value="Dilatación">Dilatación</option>
                        <option value="Atención Médica">Atención / Consulta Médica</option>
                        <option value="Estudios">Estudios Visuales</option>
                        <option value="Pre-quirúrgico">Pre-quirúrgico</option>
                        <option value="Recepción">Recepción / Administración</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label text-muted small fw-bold">Médico / Especialista</label>
                    <select name="doctor_id" class="form-select bg-light border-0 shadow-none text-dark" required>
                        <option value="" disabled selected>Seleccione médico...</option>
                        @foreach($doctors as $doctor)
                            <option value="{{ $doctor->id }}">{{ $doctor->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="bg-white rounded-3 shadow-sm border p-3 border-top-0 rounded-top-0 mx-0 mb-3">
                <textarea name="notes" rows="2" class="form-control bg-light border-0 shadow-none mb-3" placeholder="Observaciones adicionales u órdenes breves..."></textarea>
                <div class="text-end">
                    <button type="submit" class="btn btn-info px-4 rounded-pill text-white fw-bold shadow-sm" style="background: linear-gradient(135deg, #0288d1 0%, #26c6da 100%); border:none;"><i class="bi bi-play-circle me-1"></i> Iniciar Flujo</button>
                </div>
            </div>
        </form>
    </div>

    <!-- Tablas de Tracker -->
    <div class="row g-4">
        
        <!-- En Curso -->
        <div class="col-md-7">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100">
                <div class="card-header bg-success text-white border-0 py-3">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-arrow-repeat me-2"></i> Procesos En Curso</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead class="table-light text-muted small">
                                <tr>
                                    <th class="ps-4 fw-bold border-0">INICIO</th>
                                    <th class="fw-bold border-0">EVENTO</th>
                                    <th class="fw-bold border-0">ESTADO</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($patient->assignments->where('status', 'in_progress') as $assignment)
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold text-dark">{{ $assignment->started_at->format('H:i') }} hs</div>
                                        <small class="text-muted timer" data-start="{{ $assignment->started_at->toIso8601String() }}">{{ $assignment->started_at->diffForHumans() }}</small>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border px-2 py-1 mb-1">{{ $assignment->event_type }}</span>
                                        <div class="small fw-medium text-muted"><i class="bi bi-person me-1"></i> {{ $assignment->doctor->name ?? 'N/A' }}</div>
                                    </td>
                                    <td class="pe-4">
                                        <form action="{{ route('assignments.complete', $assignment) }}" method="POST">
                                            @csrf
                                            @method('PUT')
                                            <button type="submit" class="btn btn-sm btn-outline-success rounded-pill fw-bold w-100">Finalizar</button>
                                        </form>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="3" class="text-center py-4 text-muted bg-light">
                                        Sin eventos en curso
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Finalizados Hoy -->
        <div class="col-md-5">
            <div class="card border border-2 border-light shadow-none rounded-4 overflow-hidden h-100">
                <div class="card-header bg-light border-0 py-3">
                    <h6 class="mb-0 fw-bold text-secondary"><i class="bi bi-check2-all me-2"></i> Finalizados (Historial)</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                        <table class="table table-hover table-borderless mb-0 align-middle">
                            <thead class="border-bottom text-muted small">
                                <tr>
                                    <th class="ps-4 fw-bold">RANGO</th>
                                    <th class="fw-bold">EVENTO</th>
                                </tr>
                            </thead>
                            <tbody class="small">
                                @forelse($patient->assignments->where('status', 'completed') as $assignment)
                                <tr class="border-bottom">
                                    <td class="ps-4">
                                        <div class="fw-medium text-dark">{{ $assignment->started_at->format('H:i') }} - {{ $assignment->ended_at->format('H:i') }}</div>
                                        <div class="text-muted" style="font-size: 0.75rem;">Total: {{ $assignment->started_at->diffInMinutes($assignment->ended_at) }} min</div>
                                    </td>
                                    <td class="pe-3">
                                        <strong class="d-block text-secondary">{{ $assignment->event_type }}</strong>
                                        <span class="text-muted">{{ $assignment->doctor->name ?? 'N/A' }}</span>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="2" class="text-center py-4 text-muted bg-light">
                                        Sin eventos finalizados
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
    // Live Timer Update
    setInterval(() => {
        document.querySelectorAll('.timer').forEach(el => {
            const startStr = el.getAttribute('data-start');
            if(startStr) {
                const start = new Date(startStr);
                const now = new Date();
                const diffMs = now - start;
                const diffMins = Math.floor(diffMs / 60000);
                if (diffMins < 1) el.innerText = 'hace segundos';
                else if (diffMins === 1) el.innerText = 'hace 1 minuto';
                else if (diffMins < 60) el.innerText = `hace ${diffMins} minutos`;
                else {
                    const hrs = Math.floor(diffMins / 60);
                    const mins = diffMins % 60;
                    el.innerText = `hace ${hrs}h ${mins}m`;
                }
            }
        });
    }, 60000); // update every minute
</script>
