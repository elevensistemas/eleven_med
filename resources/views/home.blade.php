@extends('layouts.admin')

@section('title', 'Centro de Mando Analítico')
@section('subtitle', '')

@section('content')

<style>
    body {
        background-image: url('{{ asset("images/console_bg.png") }}');
        background-size: cover;
        background-position: center;
        background-attachment: fixed;
        background-repeat: no-repeat;
        position: relative;
    }
    body::before {
        content: '';
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        background-color: rgba(241, 245, 249, 0.88);
        z-index: -1;
    }
    body.theme-dark::before {
        background-color: rgba(15, 23, 42, 0.88);
    }

    #dashboardSlider {
        min-height: calc(100vh - 200px);
        position: relative;
    }

    .carousel-inner, .carousel-item {
        transition: transform 0.6s cubic-bezier(0.25, 0.8, 0.25, 1);
    }

    .slide-wrapper {
        min-height: calc(100vh - 220px);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 10px 45px;
    }

    .pager-stealth-arrow {
        position: absolute;
        top: 0;
        bottom: 160px;
        width: 50px;
        z-index: 1040;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        opacity: 0.18;
        transition: all 0.3s ease;
        background: transparent;
        border: none;
        outline: none;
    }
    .pager-stealth-arrow:hover { opacity: 0.9; }
    .pager-stealth-arrow.left-arrow { left: -10px; }
    .pager-stealth-arrow.right-arrow { right: -10px; }
    .pager-stealth-arrow i {
        font-size: 2.8rem;
        color: var(--primary-color);
        text-shadow: 0 0 14px rgba(94, 106, 210, 0.5);
    }

    .dash-nav-pill {
        border-radius: 50rem;
        font-weight: 700;
        font-size: 0.84rem;
        padding: 0.45rem 1.1rem;
        border: 1px solid rgba(94, 106, 210, 0.25);
        background: #ffffff;
        color: #475569;
        transition: all 0.2s ease;
    }
    .dash-nav-pill.active {
        background: linear-gradient(135deg, #5e6ad2 0%, #7e448b 100%);
        color: #ffffff !important;
        border-color: transparent;
        box-shadow: 0 4px 12px rgba(94, 106, 210, 0.3);
    }
    body.theme-dark .dash-nav-pill:not(.active) {
        background: #1e293b;
        color: #cbd5e1;
        border-color: #334155;
    }
</style>

<!-- Barra Superior de Vistas del Dashboard (Navegación Directa) -->
<div class="d-flex justify-content-center align-items-center flex-wrap gap-2 mb-3">
    <button type="button" class="btn dash-nav-pill active" data-slide-idx="0" onclick="goToDashboardSlide(0)">
        <i class="bi bi-speedometer2 me-1"></i> 1. Pulso y Actividad (14d)
    </button>
    <button type="button" class="btn dash-nav-pill" data-slide-idx="1" onclick="goToDashboardSlide(1)">
        <i class="bi bi-pie-chart-fill me-1"></i> 2. Coberturas Médicas
    </button>
    <button type="button" class="btn dash-nav-pill" data-slide-idx="2" onclick="goToDashboardSlide(2)">
        <i class="bi bi-bar-chart-line-fill me-1"></i> 3. Consultas en Ejes Cartesianos
    </button>
    <button type="button" class="btn dash-nav-pill" data-slide-idx="3" onclick="goToDashboardSlide(3)">
        <i class="bi bi-eyedropper me-1"></i> 4. Planilla y Gráfico de Estudios
    </button>
</div>

<div id="dashboardSlider" class="carousel slide" data-bs-ride="false" data-bs-interval="false" data-bs-touch="true">

    <div class="carousel-inner">

        <!-- PÁGINA 1: KPIs y Pulso -->
        <div class="carousel-item active">
            <div class="slide-wrapper">
                <div class="container-fluid px-0">
                    <div class="row g-4 mb-4">
                        <div class="col-md-4">
                            <div class="modern-card p-4 d-flex flex-column align-items-center justify-content-center h-100 text-center shadow-lg border-0" style="background: linear-gradient(135deg, #5e6ad2 0%, #7e448b 100%); color: white;">
                                <div class="rounded-circle d-flex align-items-center justify-content-center shadow-sm bg-white text-primary mb-2" style="width: 56px; height: 56px;">
                                    <i class="bi bi-person-hearts fs-3"></i>
                                </div>
                                <p class="text-white-50 small fw-bold text-uppercase mb-1">Base de Pacientes Clínicos</p>
                                <h2 class="fw-bold text-white display-6 mb-1">{{ number_format($totalPatients) }}</h2>
                                <small class="text-white-50"><i class="bi bi-clock-history"></i> Histórico totalizado</small>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="modern-card p-4 d-flex flex-column align-items-center justify-content-center h-100 text-center shadow-lg border-0" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white;">
                                <div class="rounded-circle d-flex align-items-center justify-content-center shadow-sm bg-white text-success mb-2" style="width: 56px; height: 56px;">
                                    <i class="bi bi-calendar2-check-fill fs-3"></i>
                                </div>
                                <p class="text-white-50 small fw-bold text-uppercase mb-1">Actividad últimos 14 días</p>
                                <h2 class="fw-bold text-white display-6 mb-0">{{ number_format($seenLast14Days) }}</h2>
                                <p class="fw-bold text-white small mb-2">Consultas asentadas (Hoy: {{ $seenToday }} cons. / {{ $totalBookedToday }} turnos)</p>
                                <div class="d-flex justify-content-center gap-2 w-100 flex-wrap">
                                    <div class="bg-white bg-opacity-25 text-white px-2.5 py-1 rounded-pill small fw-bold shadow-sm border border-white border-opacity-25" style="font-size: 0.73rem;">
                                        <i class="bi bi-check-circle"></i> Libres hoy: {{ $totalFreeToday }}
                                    </div>
                                    <div class="bg-white bg-opacity-25 text-white px-2.5 py-1 rounded-pill small fw-bold shadow-sm border border-white border-opacity-25" style="font-size: 0.73rem;">
                                        <i class="bi bi-lightning-fill"></i> Sobreturnos: {{ $totalExtraToday }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="modern-card p-4 d-flex flex-column align-items-center justify-content-center h-100 text-center shadow-lg border-0" style="background: linear-gradient(135deg, #00c6ff 0%, #0072ff 100%); color: white;">
                                <div class="rounded-circle d-flex align-items-center justify-content-center shadow-sm bg-white text-info mb-2" style="width: 56px; height: 56px;">
                                    <i class="bi bi-graph-up-arrow fs-3"></i>
                                </div>
                                <p class="text-white-50 small fw-bold text-uppercase mb-1">Captación (Mes Actual)</p>
                                <h2 class="fw-bold text-white display-6 mb-1">+{{ number_format($newPatientsThisMonth) }}</h2>
                                <span class="badge bg-white bg-opacity-25 rounded-pill text-white px-3 py-1 f-12 shadow-sm border border-white border-opacity-25">
                                    {{ $growth >= 0 ? '▲' : '▼' }} {{ number_format($growth, 1) }}% vs último mes
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="modern-card p-3 shadow-sm border h-100">
                                <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
                                    <h6 class="fw-bold text-primary mb-0" style="font-size: 0.9rem;"><i class="bi bi-clock-history me-1"></i> Fichas Vistas (14 días)</h6>
                                    <span class="badge bg-primary rounded-pill">{{ isset($recentPatients) ? count($recentPatients) : 0 }}</span>
                                </div>
                                <div style="max-height: 210px; overflow-y: auto;">
                                    @if(isset($recentPatients) && count($recentPatients) > 0)
                                        @foreach($recentPatients as $rp)
                                            <a href="{{ route('patients.show', $rp['id']) }}" class="d-flex justify-content-between align-items-center py-1.5 px-2 text-decoration-none rounded border-bottom border-light">
                                                <span class="fw-semibold text-dark text-truncate me-2" style="font-size: 0.84rem;">{{ $rp['name'] }}</span>
                                                <small class="text-muted font-monospace flex-shrink-0" style="font-size: 0.72rem;">{{ !empty($rp['viewed_at']) ? \Carbon\Carbon::parse($rp['viewed_at'])->format('d/m/Y H:i') : '' }}</small>
                                            </a>
                                        @endforeach
                                    @else
                                        <div class="text-center text-muted small py-4">Sin fichas revisadas en 14 días</div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="modern-card p-3 shadow-sm border h-100">
                                <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
                                    <h6 class="fw-bold text-success mb-0" style="font-size: 0.9rem;"><i class="bi bi-journal-medical me-1"></i> Consultas (14 días)</h6>
                                    <span class="badge bg-success rounded-pill">{{ isset($consultedPatients) ? count($consultedPatients) : 0 }}</span>
                                </div>
                                <div style="max-height: 210px; overflow-y: auto;">
                                    @if(isset($consultedPatients) && count($consultedPatients) > 0)
                                        @foreach($consultedPatients as $cp)
                                            <a href="{{ route('patients.show', $cp['id']) }}" class="d-flex justify-content-between align-items-center py-1.5 px-2 text-decoration-none rounded border-bottom border-light">
                                                <span class="fw-semibold text-dark text-truncate me-2" style="font-size: 0.84rem;">{{ $cp['name'] }}</span>
                                                <small class="text-success font-monospace flex-shrink-0" style="font-size: 0.72rem;">{{ $cp['last_visited_at']->format('d/m/Y H:i') }}</small>
                                            </a>
                                        @endforeach
                                    @else
                                        <div class="text-center text-muted small py-4">Sin consultas en 14 días</div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="modern-card p-3 shadow-sm border h-100">
                                <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
                                    <h6 class="fw-bold text-dark mb-0" style="font-size: 0.9rem;"><i class="bi bi-person-check-fill text-warning me-1"></i> Atendidos en Sala (14 días)</h6>
                                    <span class="badge bg-warning text-dark rounded-pill">{{ isset($attendedPatients14d) ? count($attendedPatients14d) : 0 }}</span>
                                </div>
                                <div style="max-height: 210px; overflow-y: auto;">
                                    @if(isset($attendedPatients14d) && count($attendedPatients14d) > 0)
                                        @foreach($attendedPatients14d as $att)
                                            <a href="{{ route('patients.show', $att['id']) }}" class="d-flex justify-content-between align-items-center py-1.5 px-2 text-decoration-none rounded border-bottom border-light">
                                                <span class="fw-semibold text-dark text-truncate me-2" style="font-size: 0.84rem;">{{ $att['name'] }}</span>
                                                <small class="text-secondary font-monospace flex-shrink-0" style="font-size: 0.72rem;">{{ $att['date']->format('d/m/Y H:i') }}</small>
                                            </a>
                                        @endforeach
                                    @else
                                        <div class="text-center text-muted small py-4">Sin ingresos en sala en 14 días</div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- PÁGINA 2: Obras Sociales (Doughnut) -->
        <div class="carousel-item">
            <div class="slide-wrapper">
                <div class="container-fluid px-0 d-flex flex-column align-items-center">
                    <h4 class="fw-bold text-dark mb-4 text-center" style="letter-spacing: -0.5px;">Matriz Demográfica y Coberturas Médicas</h4>
                    <div class="modern-card p-4 shadow-lg w-100" style="max-width: 920px;">
                        <div class="row align-items-center">
                            <div class="col-md-7 border-end">
                                <div style="height: 380px; width:100%;" class="d-flex justify-content-center">
                                    <canvas id="osChart"></canvas>
                                </div>
                            </div>
                            <div class="col-md-5 ps-md-4 mt-3 mt-md-0">
                                <h5 class="fw-bold mb-3">Principales Coberturas</h5>
                                @foreach($patientsByObraSocial as $index => $os)
                                    <div class="d-flex justify-content-between align-items-center mb-2.5 p-2.5 px-3 rounded-4 shadow-sm" style="background: rgba(0,0,0,0.02); border-left: 4px solid {{ $os->color }};">
                                        <div>
                                            <span class="badge rounded-circle me-2" style="background-color: {{ $os->color }}; color: #fff;">{{ $index + 1 }}</span>
                                            <span class="fw-bold text-dark">{{ $os->os_name }}</span>
                                        </div>
                                        <h6 class="fw-bold mb-0" style="color: {{ $os->color }};">{{ $os->count }} <small class="text-muted fw-normal">pts</small></h6>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- PÁGINA 3: Consultas Médicas en Ejes Cartesianos (Filtros: Año, Mes, Médico / Totales) -->
        <div class="carousel-item">
            <div class="slide-wrapper">
                <div class="container-fluid px-0">
                    <div class="modern-card p-4 shadow-lg w-100">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3 pb-3 border-bottom">
                            <div>
                                <h5 class="fw-bold text-dark mb-1"><i class="bi bi-bar-chart-line-fill text-primary me-2"></i> Evolución de Consultas en Ejes Cartesianos</h5>
                                <small class="text-muted">Refleja en tiempo real cada nueva consulta médica asentada (filtre por año, mes y profesional o totales).</small>
                            </div>
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <select id="filterVisitsYear" class="form-select form-select-sm bg-light border fw-bold rounded-pill px-3" style="width: auto;" onchange="syncAndReloadCartesian('visits')">
                                    @foreach($availableYears ?? [now()->year] as $yr)
                                        <option value="{{ $yr }}" {{ $yr == now()->year ? 'selected' : '' }}>Año {{ $yr }}</option>
                                    @endforeach
                                </select>

                                <select id="filterVisitsMonth" class="form-select form-select-sm bg-light border fw-bold rounded-pill px-3" style="width: auto;" onchange="syncAndReloadCartesian('visits')">
                                    <option value="all" selected>Todo el Año (Mes a Mes)</option>
                                    @foreach([1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',7=>'Julio',8=>'Agosto',9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre'] as $mNum => $mLabel)
                                        <option value="{{ $mNum }}">{{ $mLabel }} (Día x Día)</option>
                                    @endforeach
                                </select>

                                <select id="filterVisitsDoctor" class="form-select form-select-sm bg-light border fw-bold rounded-pill px-3" style="width: auto;" onchange="syncAndReloadCartesian('visits')">
                                    <option value="all" selected>Totales Clínica (Todos)</option>
                                    @foreach($staffFilterList ?? $doctors ?? [] as $doc)
                                        <option value="{{ $doc->id }}">{{ $doc->name }}</option>
                                    @endforeach
                                </select>

                                <span class="badge bg-primary rounded-pill px-3 py-2 fw-bold" id="totalVisitsBadge">
                                    Total: {{ $initialAnalytics['total_visits'] ?? 0 }} consultas
                                </span>
                            </div>
                        </div>

                        <div style="height: 400px; width: 100%;">
                            <canvas id="annualChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- PÁGINA 4: Gráfico y Planilla de Estudios Realizados -->
        <div class="carousel-item">
            <div class="slide-wrapper">
                <div class="container-fluid px-0">
                    <div class="modern-card p-4 shadow-lg w-100">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3 pb-3 border-bottom">
                            <div>
                                <h5 class="fw-bold text-dark mb-1"><i class="bi bi-eyedropper text-success me-2"></i> Planilla y Ejes Cartesianos de Estudios Realizados</h5>
                                <small class="text-muted">Seguimiento mensual y diario de estudios diagnósticos cargados y circuito técnico.</small>
                            </div>
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <select id="filterStudiesYear" class="form-select form-select-sm bg-light border fw-bold rounded-pill px-3" style="width: auto;" onchange="syncAndReloadCartesian('studies')">
                                    @foreach($availableYears ?? [now()->year] as $yr)
                                        <option value="{{ $yr }}" {{ $yr == now()->year ? 'selected' : '' }}>Año {{ $yr }}</option>
                                    @endforeach
                                </select>

                                <select id="filterStudiesMonth" class="form-select form-select-sm bg-light border fw-bold rounded-pill px-3" style="width: auto;" onchange="syncAndReloadCartesian('studies')">
                                    <option value="all" selected>Todo el Año (Mes a Mes)</option>
                                    @foreach([1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',7=>'Julio',8=>'Agosto',9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre'] as $mNum => $mLabel)
                                        <option value="{{ $mNum }}">{{ $mLabel }} (Día x Día)</option>
                                    @endforeach
                                </select>

                                <select id="filterStudiesDoctor" class="form-select form-select-sm bg-light border fw-bold rounded-pill px-3" style="width: auto;" onchange="syncAndReloadCartesian('studies')">
                                    <option value="all" selected>Totales Clínica (Todos)</option>
                                    @foreach($staffFilterList ?? $doctors ?? [] as $doc)
                                        <option value="{{ $doc->id }}">{{ $doc->name }}</option>
                                    @endforeach
                                </select>

                                <span class="badge bg-success rounded-pill px-3 py-2 fw-bold" id="totalStudiesBadge">
                                    Total: {{ $initialAnalytics['total_studies'] ?? 0 }} estudios
                                </span>
                            </div>
                        </div>

                        <div class="row g-4">
                            <div class="col-lg-6">
                                <div style="height: 310px; width: 100%;">
                                    <canvas id="studiesCartesianChart"></canvas>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <h6 class="fw-bold text-dark small text-uppercase mb-2"><i class="bi bi-table text-success me-1"></i> Planilla de Estudios del Período</h6>
                                <div class="table-responsive border rounded-3" style="max-height: 285px; overflow-y: auto;">
                                    <table class="table table-sm table-hover align-middle mb-0" style="font-size: 0.8rem;">
                                        <thead class="table-light sticky-top">
                                            <tr>
                                                <th>Fecha</th>
                                                <th>Paciente</th>
                                                <th>Tipo / Estudio</th>
                                                <th>Profesional</th>
                                            </tr>
                                        </thead>
                                        <tbody id="studiesPlanillaTbody">
                                            @forelse($initialAnalytics['studies_table'] ?? [] as $stRow)
                                                <tr>
                                                    <td class="text-nowrap text-muted">{{ $stRow['date'] }}</td>
                                                    <td>
                                                        <a href="/patients/{{ $stRow['patient_id'] }}" class="fw-bold text-dark text-decoration-none">
                                                            {{ $stRow['patient_name'] }}
                                                        </a>
                                                        <small class="text-primary d-block">#{{ $stRow['patient_number'] }}</small>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">{{ $stRow['study_type'] }}</span>
                                                        <small class="text-muted d-block text-truncate" style="max-width: 160px;">{{ $stRow['detail'] }}</small>
                                                    </td>
                                                    <td class="small">{{ $stRow['professional'] }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="4" class="text-center text-muted py-4">No hay estudios registrados en el período seleccionado.</td>
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
        </div>

    </div>

    <!-- Flechas Laterales -->
    <button class="pager-stealth-arrow left-arrow" type="button" data-bs-target="#dashboardSlider" data-bs-slide="prev">
        <i class="bi bi-chevron-compact-left"></i>
        <span class="visually-hidden">Anterior</span>
    </button>
    <button class="pager-stealth-arrow right-arrow" type="button" data-bs-target="#dashboardSlider" data-bs-slide="next">
        <i class="bi bi-chevron-compact-right"></i>
        <span class="visually-hidden">Siguiente</span>
    </button>
</div>

<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    let visitsChartInstance = null;
    let studiesChartInstance = null;

    function goToDashboardSlide(idx) {
        const carouselEl = document.getElementById('dashboardSlider');
        const instance = bootstrap.Carousel.getOrCreateInstance(carouselEl, { interval: false, ride: false });
        instance.to(idx);
    }

    function syncAndReloadCartesian(source) {
        const yr = document.getElementById(source === 'studies' ? 'filterStudiesYear' : 'filterVisitsYear').value;
        const mo = document.getElementById(source === 'studies' ? 'filterStudiesMonth' : 'filterVisitsMonth').value;
        const doc = document.getElementById(source === 'studies' ? 'filterStudiesDoctor' : 'filterVisitsDoctor').value;

        document.getElementById('filterVisitsYear').value = yr;
        document.getElementById('filterStudiesYear').value = yr;
        document.getElementById('filterVisitsMonth').value = mo;
        document.getElementById('filterStudiesMonth').value = mo;
        document.getElementById('filterVisitsDoctor').value = doc;
        document.getElementById('filterStudiesDoctor').value = doc;

        axios.get(`{{ route('api.dashboard.chart_data') }}?year=${yr}&month=${mo}&doctor_id=${doc}`)
            .then(res => {
                const d = res.data;
                if (visitsChartInstance) {
                    visitsChartInstance.data.labels = d.labels;
                    visitsChartInstance.data.datasets[0].data = d.visits_series;
                    visitsChartInstance.update();
                }
                if (studiesChartInstance) {
                    studiesChartInstance.data.labels = d.labels;
                    studiesChartInstance.data.datasets[0].data = d.studies_series;
                    studiesChartInstance.update();
                }

                document.getElementById('totalVisitsBadge').innerText = `Total: ${d.total_visits} consultas`;
                document.getElementById('totalStudiesBadge').innerText = `Total: ${d.total_studies} estudios`;

                const tbody = document.getElementById('studiesPlanillaTbody');
                if (tbody) {
                    if (!d.studies_table || d.studies_table.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-4">No hay estudios registrados en el período seleccionado.</td></tr>';
                    } else {
                        tbody.innerHTML = d.studies_table.map(r => `
                            <tr>
                                <td class="text-nowrap text-muted">${r.date}</td>
                                <td>
                                    <a href="/patients/${r.patient_id}" class="fw-bold text-dark text-decoration-none">${r.patient_name}</a>
                                    <small class="text-primary d-block">#${r.patient_number}</small>
                                </td>
                                <td>
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">${r.study_type}</span>
                                    <small class="text-muted d-block text-truncate" style="max-width: 160px;">${r.detail || '-'}</small>
                                </td>
                                <td class="small">${r.professional}</td>
                            </tr>
                        `).join('');
                    }
                }
            })
            .catch(err => console.error('Error cargando analítica:', err));
    }

    document.addEventListener('DOMContentLoaded', function() {
        const carouselElement = document.getElementById('dashboardSlider');
        const carouselInstance = bootstrap.Carousel.getOrCreateInstance(carouselElement, { interval: false, ride: false });

        carouselElement.addEventListener('slid.bs.carousel', function(ev) {
            document.querySelectorAll('.dash-nav-pill').forEach(btn => {
                btn.classList.toggle('active', parseInt(btn.getAttribute('data-slide-idx'), 10) === ev.to);
            });
        });

        document.addEventListener('keydown', function(event) {
            if (['INPUT', 'SELECT', 'TEXTAREA'].includes(document.activeElement?.tagName)) return;
            if (event.code === 'ArrowRight') carouselInstance.next();
            else if (event.code === 'ArrowLeft') carouselInstance.prev();
        });

        const primaryColor = '#5E6AD2';
        const successColor = '#10b981';

        Chart.defaults.font.family = "'Outfit', sans-serif";
        Chart.defaults.color = '#7f8a9e';

        // Doughnut Coberturas
        const osDataRaw = @json($patientsByObraSocial);
        new Chart(document.getElementById('osChart').getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: osDataRaw.map(d => d.os_name),
                datasets: [{
                    data: osDataRaw.map(d => d.count),
                    backgroundColor: osDataRaw.map(d => d.color),
                    borderWidth: 4,
                    borderColor: '#ffffff',
                    hoverOffset: 12
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '68%',
                plugins: { legend: { display: false } }
            }
        });

        // Cartesian Consultas
        const initialAnalytics = @json($initialAnalytics ?? ['labels'=>[], 'visits_series'=>[], 'studies_series'=>[]]);
        const ctxAnnual = document.getElementById('annualChart').getContext('2d');
        let barGradient = ctxAnnual.createLinearGradient(0, 0, 0, 400);
        barGradient.addColorStop(0, primaryColor);
        barGradient.addColorStop(1, 'rgba(94, 106, 210, 0.15)');

        visitsChartInstance = new Chart(ctxAnnual, {
            type: 'bar',
            data: {
                labels: initialAnalytics.labels,
                datasets: [{
                    label: 'Consultas Médicas Asentadas',
                    data: initialAnalytics.visits_series,
                    backgroundColor: barGradient,
                    borderColor: primaryColor,
                    borderWidth: 1,
                    borderRadius: { topLeft: 10, topRight: 10 },
                    maxBarThickness: 48
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: true } },
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0, font: { weight: 'bold' } } },
                    x: { grid: { display: false }, ticks: { font: { weight: 'bold' } } }
                }
            }
        });

        // Cartesian Estudios
        const ctxStudies = document.getElementById('studiesCartesianChart').getContext('2d');
        let studiesGradient = ctxStudies.createLinearGradient(0, 0, 0, 320);
        studiesGradient.addColorStop(0, successColor);
        studiesGradient.addColorStop(1, 'rgba(16, 185, 129, 0.15)');

        studiesChartInstance = new Chart(ctxStudies, {
            type: 'bar',
            data: {
                labels: initialAnalytics.labels,
                datasets: [{
                    label: 'Estudios Realizados',
                    data: initialAnalytics.studies_series,
                    backgroundColor: studiesGradient,
                    borderColor: successColor,
                    borderWidth: 1,
                    borderRadius: { topLeft: 10, topRight: 10 },
                    maxBarThickness: 40
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: true } },
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0, font: { weight: 'bold' } } },
                    x: { grid: { display: false }, ticks: { font: { weight: 'bold' } } }
                }
            }
        });
    });
</script>
@endsection
