@php
    $formTargetId = $formId ?? 'formNewVisit';
    $ojitoDefaultOpen = $defaultOpenOjito ?? true;
    $allPrevVisits = isset($previousVisits) ? $previousVisits : ($patient->visits ?? collect());
    $allSurgeries = isset($surgeries) ? $surgeries : ($patient->surgeries ?? collect());
    $patientAge = $patient->date_of_birth ? \Carbon\Carbon::parse($patient->date_of_birth)->age : null;
    $patientDob = $patient->date_of_birth ? \Carbon\Carbon::parse($patient->date_of_birth)->format('d/m/Y') : '-';
@endphp

<!-- ENCABEZADO GENERAL COMPLETO DEL PACIENTE -->
<div class="card border-0 rounded-4 shadow-sm mb-3 overflow-hidden" style="border: 1px solid rgba(94, 106, 210, 0.2) !important;">
    <div class="card-body p-3 px-4" style="background: linear-gradient(135deg, #f8faff 0%, #f1f5fe 100%);">
        <!-- Fila Principal: Identidad, Cobertura, N° Afiliado, Carpeta y Botón Ojito -->
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 pb-2 border-bottom border-secondary border-opacity-10">
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <!-- Avatar -->
                <div class="rounded-circle shadow-sm text-white d-flex align-items-center justify-content-center overflow-hidden flex-shrink-0" style="width: 52px; height: 52px; background: linear-gradient(135deg, #5e6ad2 0%, #7e448b 100%); font-size: 1.4rem;">
                    @if($patient->photo_path)
                        @if(\Illuminate\Support\Str::startsWith($patient->photo_path, 'uploads/'))
                            <img src="{{ asset($patient->photo_path) }}" alt="Foto" style="width: 100%; height: 100%; object-fit: cover;">
                        @else
                            <img src="{{ asset('storage/' . $patient->photo_path) }}" alt="Foto" style="width: 100%; height: 100%; object-fit: cover;">
                        @endif
                    @else
                        <i class="bi bi-person-fill"></i>
                    @endif
                </div>

                <div>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <h5 class="fw-bold text-dark mb-0" style="font-size: 1.2rem;">
                            {{ $patient->last_name }}, {{ $patient->first_name }}
                        </h5>
                        <span class="badge bg-white text-dark border rounded-pill px-3 py-1 fw-bold" style="font-size: 0.8rem;">
                            <i class="bi bi-card-text text-secondary me-1"></i> DNI: {{ $patient->dni }}
                        </span>
                        @if($patientAge !== null)
                            <span class="badge bg-white text-secondary border rounded-pill px-3 py-1 fw-semibold" style="font-size: 0.8rem;">
                                <i class="bi bi-cake2 me-1"></i> {{ $patientAge }} años ({{ $patientDob }})
                            </span>
                        @endif
                    </div>
                    <div class="mt-1">
                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-3 py-1 fw-bold" style="font-size: 0.8rem;">
                            <i class="bi bi-person-vcard me-1"></i> N° de Paciente: #{{ $patient->patient_number }}
                        </span>
                        @if(!empty($patient->gender))
                            <span class="badge bg-white text-secondary border rounded-pill px-2.5 py-1 ms-1" style="font-size: 0.76rem;">{{ $patient->gender }}</span>
                        @endif
                    </div>

                    <!-- Datos de Obra Social, N° Cobertura, Teléfono y Profesión -->
                    <div class="d-flex align-items-center gap-3 flex-wrap mt-2" style="font-size: 0.85rem;">
                        <span class="badge text-white rounded-pill px-3 py-1 shadow-sm" style="background-color: {{ $patient->obra_social_color }}; font-size: 0.8rem;">
                            <i class="bi bi-hospital me-1"></i> {{ $patient->obra_social ?: 'PARTICULAR' }}{{ $patient->plan ? ' - Plan: ' . $patient->plan : '' }}
                        </span>
                        <span class="text-dark">
                            <strong class="text-muted">N° Cobertura/Afiliado:</strong>
                            <span class="fw-bold">{{ $patient->affiliate_number ?: 'S/D' }}</span>
                        </span>
                        <span class="text-muted opacity-50">|</span>
                        <span class="text-dark d-inline-flex align-items-center gap-1">
                            <i class="bi bi-telephone text-primary"></i>
                            <span class="fw-semibold">{{ $patient->phone ?: 'S/D' }}</span>
                            @if($patient->phone)
                                <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $patient->phone) }}" target="_blank" class="text-success ms-1" title="Enviar WhatsApp">
                                    <i class="bi bi-whatsapp"></i>
                                </a>
                            @endif
                        </span>
                        @if($patient->email)
                            <span class="text-muted opacity-50">|</span>
                            <span class="text-dark"><i class="bi bi-envelope text-secondary me-1"></i>{{ $patient->email }}</span>
                        @endif
                        @if($patient->profession)
                            <span class="text-muted opacity-50">|</span>
                            <span class="text-dark"><i class="bi bi-briefcase text-secondary me-1"></i>{{ $patient->profession }}</span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Botón "EL OJITO" para desplegar/ocultar y scrollear TODA la Historia Clínica previa -->
            <div class="d-flex align-items-center gap-2">
                <button type="button"
                        class="btn rounded-pill px-4 py-2 fw-bold shadow-sm d-flex align-items-center gap-2 text-white border-0"
                        style="background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);"
                        data-bs-toggle="collapse"
                        data-bs-target="#ojitoHistoriaPreviaCollapse"
                        aria-expanded="{{ $ojitoDefaultOpen ? 'true' : 'false' }}"
                        title="Ver y scrollear en detalle todas las consultas y cirugías previas">
                    <i class="bi bi-eye-fill fs-5" id="ojitoMainIcon"></i>
                    <span>Consultas Previas ({{ $allPrevVisits->count() }}){{ ($allSurgeries && $allSurgeries->count() > 0) ? ' + Qx (' . $allSurgeries->count() . ')' : '' }}</span>
                    <i class="bi {{ $ojitoDefaultOpen ? 'bi-chevron-up' : 'bi-chevron-down' }} small ms-1" id="ojitoChevronIcon"></i>
                </button>
                <a href="{{ route('patients.edit', $patient) }}" target="_blank" class="btn btn-sm btn-white bg-white border rounded-pill px-3 py-2 text-secondary fw-bold shadow-sm" title="Editar datos filiatorios completos">
                    <i class="bi bi-pencil-square"></i>
                </a>
            </div>
        </div>

        <!-- Fila Inferior del Encabezado: Derivante y Comentarios Médicos Privados (Editables en vivo) -->
        <div class="row g-3 pt-2 mt-0 align-items-start">
            <div class="col-md-5">
                <label class="form-label text-dark small fw-bold mb-1 d-flex align-items-center gap-1">
                    <i class="bi bi-person-lines-fill text-primary"></i> Derivado / Recomendado por:
                </label>
                <input type="text"
                       name="recommendation"
                       form="{{ $formTargetId }}"
                       value="{{ old('recommendation', $patient->recommendation) }}"
                       class="form-control form-control-sm bg-white border shadow-none rounded-3"
                       placeholder="Ej: Dr. Pérez, Óptica, Paciente...">
            </div>
            <div class="col-md-7">
                <label class="form-label text-dark small fw-bold mb-1 d-flex align-items-center justify-content-between">
                    <span><i class="bi bi-journal-text text-warning me-1"></i> COMENTARIOS MÉDICOS (Permanentes de Ficha)</span>
                    <span class="badge bg-warning bg-opacity-25 text-dark border border-warning" style="font-size: 0.65rem;">Solo Consultorio (No sale impreso)</span>
                </label>
                <textarea name="medical_notes"
                          form="{{ $formTargetId }}"
                          rows="1"
                          class="form-control form-control-sm bg-white border shadow-none rounded-3 auto-expand"
                          style="min-height: 34px; resize: none; overflow-y: hidden; font-size: 0.85rem;"
                          placeholder="Comentarios internos permanentes del paciente...">{{ old('medical_notes', $patient->medical_notes) }}</textarea>
            </div>
        </div>
    </div>
</div>

<!-- VISOR SCROLLEABLE "EL OJITO": TODAS LAS CONSULTAS PREVIAS Y CIRUGÍAS DEBAJO DEL ENCABEZADO -->
<div class="collapse {{ $ojitoDefaultOpen ? 'show' : '' }} mb-4" id="ojitoHistoriaPreviaCollapse">
    <div class="card border rounded-4 shadow-sm overflow-hidden" style="border: 1.5px solid rgba(30, 60, 114, 0.28) !important; background-color: #f8fafc;">
        <!-- Barra de herramientas del visor -->
        <div class="card-header bg-white border-bottom py-2 px-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="badge rounded-pill px-3 py-1 text-white" style="background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);">
                    <i class="bi bi-eye-fill me-1"></i> Visor Completo de Historia Clínica Previa
                </span>
                <small class="text-muted fw-semibold">Scrollee aquí todas las consultas, PIO, aumentos, antecedentes, medicación y cirugías sin salir de la pantalla.</small>
            </div>
            <div class="d-flex align-items-center gap-2">
                <input type="text" id="ojitoSearchFilter" class="form-control form-control-sm bg-light border rounded-pill px-3 shadow-none" style="width: 210px; font-size: 0.8rem;" placeholder="Filtrar en HC (ej: PIO, lente, gotas)..." oninput="filterOjitoHistory(this.value)">
                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 fw-bold" style="font-size: 0.78rem;" onclick="reverseOjitoVisitsOrder()" id="ojitoOrderBtn">
                    <i class="bi bi-sort-down me-1"></i> Más recientes arriba
                </button>
            </div>
        </div>

        <!-- Cuerpo Scrolleable (SCROLL 1 SUPERIOR) con Consultas y Cirugías Intercaladas Cronológicamente -->
        @php
            $ojitoTimeline = collect();
            if ($allPrevVisits) {
                foreach ($allPrevVisits as $vIdx => $pv) {
                    $ojitoTimeline->push([
                        'type' => 'visit',
                        'timestamp' => $pv->created_at ? $pv->created_at->timestamp : 0,
                        'is_latest_visit' => ($vIdx === 0),
                        'item' => $pv,
                    ]);
                }
            }
            if ($allSurgeries) {
                foreach ($allSurgeries as $surg) {
                    $sTs = $surg->surgery_date
                        ? \Carbon\Carbon::parse($surg->surgery_date->format('Y-m-d') . ' 12:00:00')->timestamp
                        : ($surg->created_at ? $surg->created_at->timestamp : 0);
                    $ojitoTimeline->push([
                        'type' => 'surgery',
                        'timestamp' => $sTs,
                        'is_latest_visit' => false,
                        'item' => $surg,
                    ]);
                }
            }
            $ojitoTimeline = $ojitoTimeline->sortByDesc('timestamp')->values();
        @endphp
        <div class="card-body p-3" style="max-height: 30vh; min-height: 195px; overflow-y: auto; resize: vertical;" id="ojitoScrollViewport">
            {{-- Resumen Quirúrgico Destacado Arriba + Intercalado Cronológico Abajo --}}
            @if($allSurgeries && $allSurgeries->count() > 0)
                <div class="mb-3 p-2 px-3 rounded-3 bg-danger bg-opacity-10 border border-danger border-opacity-25">
                    <div class="fw-bold text-danger small text-uppercase mb-1">
                        <i class="bi bi-bandaid-fill me-1"></i> Resumen de Cirugías ({{ $allSurgeries->count() }}) — También intercaladas por fecha en la línea de tiempo abajo
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach($allSurgeries->sortByDesc('surgery_date') as $surg)
                            <span class="badge bg-white text-dark border shadow-sm px-3 py-2 text-wrap text-start" style="font-size: 0.8rem; font-weight: 500;">
                                <strong class="{{ $surg->eye === 'OD' ? 'text-danger' : 'text-primary' }}">Ojo {{ $surg->eye }}</strong>
                                ({{ $surg->surgery_date ? $surg->surgery_date->format('d/m/Y') : '-' }}):
                                <span class="fw-bold">{{ $surg->notes }}</span>
                                @if($surg->doctor)
                                    <em class="text-muted">— Dr. {{ $surg->doctor->name }}</em>
                                @endif
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Línea de Tiempo Unificada y Scrolleable (Consultas + Cirugías en Orden Cronológico) --}}
            @if($ojitoTimeline->count() > 0)
                <div class="d-flex flex-column gap-3" id="ojitoVisitsList">
                    @foreach($ojitoTimeline as $entry)
                        @if($entry['type'] === 'surgery')
                            @php $surg = $entry['item']; @endphp
                            <div class="card rounded-3 shadow-sm ojito-visit-card"
                                 data-timestamp="{{ $entry['timestamp'] }}"
                                 style="background: linear-gradient(90deg, #fff5f5 0%, #ffffff 100%); border: 2px solid {{ $surg->eye === 'OD' ? '#dc3545' : '#0d6efd' }} !important; border-left-width: 6px !important;">
                                <div class="card-body py-2 px-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                        <span class="badge {{ $surg->eye === 'OD' ? 'bg-danger' : 'bg-primary' }} rounded-pill px-3 py-1 fw-bold">
                                            <i class="bi bi-bandaid-fill me-1"></i> CIRUGÍA OJO {{ $surg->eye }} — {{ $surg->surgery_date ? $surg->surgery_date->format('d/m/Y') : '-' }}
                                        </span>
                                        <span class="fw-bold text-dark" style="font-size: 0.9rem;">{{ $surg->notes }}</span>
                                    </div>
                                    @if($surg->doctor)
                                        <span class="small fw-semibold text-muted">
                                            <i class="bi bi-person-badge me-1"></i> Cirujano: Dr. {{ $surg->doctor->name }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        @else
                            @php $pv = $entry['item']; @endphp
                            <div class="card border rounded-3 shadow-sm bg-white ojito-visit-card"
                                 data-timestamp="{{ $entry['timestamp'] }}">
                                <div class="card-header bg-light bg-opacity-75 border-bottom py-2 px-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                        <span class="badge {{ $entry['is_latest_visit'] ? 'bg-primary' : 'bg-secondary' }} rounded-pill px-3 py-1">
                                            <i class="bi bi-calendar-check me-1"></i> {{ $pv->created_at ? $pv->created_at->format('d/m/Y H:i') : '-' }}
                                        </span>
                                        @if($entry['is_latest_visit'])
                                            <span class="badge bg-info bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill">Última Consulta Previa</span>
                                        @endif
                                        <span class="small fw-bold text-dark">
                                            <i class="bi bi-person-badge text-primary me-1"></i> Dr. {{ $pv->doctor->name ?? 'Staff' }}
                                        </span>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <button type="button"
                                                class="btn btn-sm btn-white bg-white border rounded-pill px-3 py-0.5 text-primary fw-bold shadow-sm"
                                                style="font-size: 0.76rem;"
                                                data-ant-oft="{{ e($pv->antecedentes_oftalmologicos ?? '') }}"
                                                data-trat-oft="{{ e($pv->tratamiento_oftalmologico ?? '') }}"
                                                data-ant-gen="{{ e($pv->antecedentes_generales ?? '') }}"
                                                data-trat-gen="{{ e($pv->tratamientos_generales ?? '') }}"
                                                onclick="copyVisitFromOjitoBtn(this)">
                                            <i class="bi bi-clipboard-plus text-success me-1"></i> Copiar antecedentes/medicación a consulta actual
                                        </button>
                                        <a href="{{ route('visits.edit', $pv) }}" class="btn btn-sm btn-outline-secondary rounded-pill px-2 py-0.5" style="font-size: 0.75rem;" title="Editar esta consulta anterior">
                                            <i class="bi bi-pencil-square"></i> Modificar
                                        </a>
                                    </div>
                                </div>

                                <div class="card-body p-3 small">
                                    <div class="row g-2">
                                        @if($pv->motivo_consulta)
                                            <div class="col-md-6">
                                                <div class="p-2 rounded bg-light h-100">
                                                    <strong class="text-primary d-block">Motivo:</strong>
                                                    <div class="text-dark" style="white-space: pre-line;">{{ $pv->motivo_consulta }}</div>
                                                </div>
                                            </div>
                                        @endif
                                        @if($pv->diagnostico)
                                            <div class="col-md-6">
                                                <div class="p-2 rounded bg-light h-100">
                                                    <strong class="text-danger d-block">Diagnóstico:</strong>
                                                    <div class="text-dark fw-bold" style="white-space: pre-line;">{{ $pv->diagnostico }}</div>
                                                </div>
                                            </div>
                                        @endif

                                        @if($pv->av_od_lejos || $pv->av_oi_lejos || $pv->av_od_cerca || $pv->av_oi_cerca)
                                            <div class="col-md-6">
                                                <div class="p-2 rounded border border-primary border-opacity-25 bg-primary bg-opacity-10 h-100">
                                                    <strong class="text-primary d-block mb-1"><i class="bi bi-eyeglasses me-1"></i> Agudeza Visual / Anteojos:</strong>
                                                    <div class="text-dark font-monospace" style="font-size: 0.82rem;">
                                                        <div><strong>Lejos:</strong> <span class="text-danger fw-bold">OD:</span> {{ $pv->av_od_lejos ?: '-' }} &nbsp;|&nbsp; <span class="text-primary fw-bold">OI:</span> {{ $pv->av_oi_lejos ?: '-' }}</div>
                                                        <div><strong>Cerca:</strong> <span class="text-danger fw-bold">OD:</span> {{ $pv->av_od_cerca ?: '-' }} &nbsp;|&nbsp; <span class="text-primary fw-bold">OI:</span> {{ $pv->av_oi_cerca ?: '-' }}</div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif

                                        @if($pv->pio || $pv->bmc || $pv->obi || $pv->otros_examen)
                                            <div class="col-md-6">
                                                <div class="p-2 rounded border bg-white h-100">
                                                    <strong class="text-secondary d-block mb-1"><i class="bi bi-activity me-1"></i> Examen Oftalmológico (PIO / BMC / OBI):</strong>
                                                    <div class="text-dark" style="font-size: 0.83rem;">
                                                        @if($pv->pio) <span class="badge bg-warning bg-opacity-25 text-dark border border-warning me-1">PIO: {{ $pv->pio }}</span> @endif
                                                        @if($pv->bmc) <div><strong>BMC:</strong> {{ $pv->bmc }}</div> @endif
                                                        @if($pv->obi) <div><strong>OBI:</strong> {{ $pv->obi }}</div> @endif
                                                        @if($pv->otros_examen) <div><strong>Otros:</strong> {{ $pv->otros_examen }}</div> @endif
                                                    </div>
                                                </div>
                                            </div>
                                        @endif

                                        @if($pv->antecedentes_oftalmologicos || $pv->tratamiento_oftalmologico || $pv->antecedentes_generales || $pv->tratamientos_generales)
                                            <div class="col-12">
                                                <div class="p-2 rounded bg-light border-start border-4 border-success">
                                                    <div class="row g-2">
                                                        @if($pv->antecedentes_oftalmologicos)
                                                            <div class="col-md-6"><strong>Ant. Oftalmológicos:</strong> <span style="white-space: pre-line;">{{ $pv->antecedentes_oftalmologicos }}</span></div>
                                                        @endif
                                                        @if($pv->tratamiento_oftalmologico)
                                                            <div class="col-md-6"><strong class="text-success">Medicación / Trat. Oftalmológico:</strong> <span class="fw-semibold text-dark" style="white-space: pre-line;">{{ $pv->tratamiento_oftalmologico }}</span></div>
                                                        @endif
                                                        @if($pv->antecedentes_generales)
                                                            <div class="col-md-6"><strong>Ant. Generales:</strong> <span style="white-space: pre-line;">{{ $pv->antecedentes_generales }}</span></div>
                                                        @endif
                                                        @if($pv->tratamientos_generales)
                                                            <div class="col-md-6"><strong>Trat. Generales:</strong> <span style="white-space: pre-line;">{{ $pv->tratamientos_generales }}</span></div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            @else
                <div class="text-center py-4 text-muted small">
                    <i class="bi bi-journal-medical fs-3 d-block mb-1 opacity-50"></i>
                    No se registran consultas ni cirugías anteriores cargadas para este paciente.
                </div>
            @endif
        </div>
    </div>
</div>

<script>
    function copyVisitFromOjitoBtn(btn) {
        const antOft = btn.getAttribute('data-ant-oft') || '';
        const tratOft = btn.getAttribute('data-trat-oft') || '';
        const antGen = btn.getAttribute('data-ant-gen') || '';
        const tratGen = btn.getAttribute('data-trat-gen') || '';

        const fAntOft = document.querySelector('textarea[name="antecedentes_oftalmologicos"]');
        const fTratOft = document.querySelector('textarea[name="tratamiento_oftalmologico"]');
        const fAntGen = document.querySelector('textarea[name="antecedentes_generales"]');
        const fTratGen = document.querySelector('textarea[name="tratamientos_generales"]');

        if (fAntOft && antOft) { fAntOft.value = antOft; fAntOft.dispatchEvent(new Event('input')); }
        if (fTratOft && tratOft) { fTratOft.value = tratOft; fTratOft.dispatchEvent(new Event('input')); }
        if (fAntGen && antGen) { fAntGen.value = antGen; fAntGen.dispatchEvent(new Event('input')); }
        if (fTratGen && tratGen) { fTratGen.value = tratGen; fTratGen.dispatchEvent(new Event('input')); }

        const origHtml = btn.innerHTML;
        btn.innerHTML = '<i class="bi bi-check-circle-fill text-success me-1"></i> ¡Copiado!';
        setTimeout(() => { btn.innerHTML = origHtml; }, 1800);
    }

    function filterOjitoHistory(term) {
        const q = (term || '').toLowerCase().trim();
        document.querySelectorAll('#ojitoVisitsList .ojito-visit-card').forEach(card => {
            const text = card.innerText.toLowerCase();
            card.style.display = (!q || text.includes(q)) ? '' : 'none';
        });
    }

    let ojitoOrderDesc = true;
    function reverseOjitoVisitsOrder() {
        const list = document.getElementById('ojitoVisitsList');
        const btn = document.getElementById('ojitoOrderBtn');
        if (!list) return;
        ojitoOrderDesc = !ojitoOrderDesc;
        const cards = Array.from(list.querySelectorAll('.ojito-visit-card'));
        cards.sort((a, b) => {
            const tA = parseInt(a.getAttribute('data-timestamp')) || 0;
            const tB = parseInt(b.getAttribute('data-timestamp')) || 0;
            return ojitoOrderDesc ? (tB - tA) : (tA - tB);
        });
        cards.forEach(c => list.appendChild(c));
        if (btn) {
            btn.innerHTML = ojitoOrderDesc
                ? '<i class="bi bi-sort-down me-1"></i> Más recientes arriba'
                : '<i class="bi bi-sort-up me-1"></i> Más antiguas arriba';
        }
    }

    function toggleDualScrollHeight(viewportId, btn) {
        const el = document.getElementById(viewportId);
        if (!el) return;
        const expanded = el.getAttribute('data-expanded') === '1';
        if (expanded) {
            el.style.maxHeight = '45vh';
            el.setAttribute('data-expanded', '0');
            if (btn) btn.innerHTML = '<i class="bi bi-arrows-expand me-1"></i> Expandir alto';
        } else {
            el.style.maxHeight = 'none';
            el.setAttribute('data-expanded', '1');
            if (btn) btn.innerHTML = '<i class="bi bi-arrows-collapse me-1"></i> Volver a doble scroll';
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const ojitoCol = document.getElementById('ojitoHistoriaPreviaCollapse');
        const ojitoChev = document.getElementById('ojitoChevronIcon');
        if (ojitoCol && ojitoChev) {
            ojitoCol.addEventListener('show.bs.collapse', () => { ojitoChev.className = 'bi bi-chevron-up small ms-1'; });
            ojitoCol.addEventListener('hide.bs.collapse', () => { ojitoChev.className = 'bi bi-chevron-down small ms-1'; });
        }
    });
</script>
