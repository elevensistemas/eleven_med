@extends('layouts.admin')

@section('title', 'Centro de Mando Analítico')
@section('subtitle', '')

@section('content')

<style>
    /* Background Image for Home Dashboard */
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
        background-color: rgba(241, 245, 249, 0.88); /* Light mode gray/white overlay */
        z-index: -1;
    }
    body.theme-dark::before {
        background-color: rgba(15, 23, 42, 0.88); /* Dark mode dark blue/gray overlay */
    }

    /* -------------------------------------
     * UX: The "Diary" Pagination Architecture
     * ------------------------------------- */
    
    /* Lock the main container to avoid any vertical scroll and look like a native app slide */
    #dashboardSlider { 
        height: calc(100vh - 160px); 
        min-height: 500px;
        position: relative; 
        overflow: hidden; 
    }
    
    .carousel-inner, .carousel-item { height: 100%; transition: transform 0.8s cubic-bezier(0.25, 0.8, 0.25, 1); }
    
    /* Content Centering inside slides */
    .slide-wrapper {
        height: 100%;
        display: flex;
        align-items: center; 
        justify-content: center;
        padding: 10px 60px;
    }

    /* -------------------------------------
     * UI: The Stealth Technological Arrows
     * ------------------------------------- */
    .pager-stealth-arrow {
        position: absolute; 
        top: 0; 
        bottom: 180px; /* Deja libre la esquina inferior para los botones flotantes */
        width: 80px;
        z-index: 1040; 
        display: flex; 
        align-items: center; 
        justify-content: center;
        cursor: pointer; 
        opacity: 0.03; /* Barely visible initially */
        transition: all 0.4s ease;
        background: transparent; 
        border: none; 
        outline: none;
    }

    /* Soft neon interaction strictly for the trained eye */
    .pager-stealth-arrow:hover { opacity: 0.8; }
    
    .pager-stealth-arrow.left-arrow { left: -15px; } 
    .pager-stealth-arrow.left-arrow:hover { 
        left: 0; 
        background: linear-gradient(90deg, rgba(94, 106, 210, 0.15) 0%, rgba(255,255,255,0) 100%);
    }

    .pager-stealth-arrow.right-arrow { right: -15px; }
    .pager-stealth-arrow.right-arrow:hover { 
        right: 0; 
        background: linear-gradient(270deg, rgba(94, 106, 210, 0.15) 0%, rgba(255,255,255,0) 100%);
    }

    .pager-stealth-arrow i { 
        font-size: 3.5rem; 
        color: var(--primary-color); 
        text-shadow: 0 0 18px rgba(94, 106, 210, 0.6); 
        transition: transform 0.3s;
    }
    
    /* Push slightly on click to mimic physical page turn */
    .pager-stealth-arrow:active i { transform: scale(0.85); }

    /* Page Indicator Dots (Stealthy bottom track) */
    .stealth-indicators {
        bottom: -15px;
        gap: 15px;
    }
    .stealth-indicators button {
        width: 40px !important;
        height: 4px !important;
        border-radius: 4px;
        background-color: var(--primary-color) !important;
        opacity: 0.15 !important;
        border: none !important;
    }
    .stealth-indicators button.active { opacity: 0.8 !important; }
</style>

<div id="dashboardSlider" class="carousel slide" data-bs-ride="carousel" data-bs-interval="7000" data-bs-touch="true">
    
    <!-- Minimalist Progress Tracker -->
    <div class="carousel-indicators stealth-indicators">
      <button type="button" data-bs-target="#dashboardSlider" data-bs-slide-to="0" class="active" aria-current="true"></button>
      <button type="button" data-bs-target="#dashboardSlider" data-bs-slide-to="1"></button>
      <button type="button" data-bs-target="#dashboardSlider" data-bs-slide-to="2"></button>
    </div>

    <!-- Páginas Físicas -->
    <div class="carousel-inner">
        
        <!-- PÁGINA 1: KPIs y Pulso -->
        <div class="carousel-item active">
            <div class="slide-wrapper">
                <div class="container-fluid px-0 h-100 d-flex flex-column justify-content-md-center justify-content-start pt-3 pt-md-0">
                    
                    <div class="row g-4 mb-4">
                        <!-- KPI 1: Histórico -->
                        <div class="col-md-4">
                            <div class="modern-card p-5 d-flex flex-column align-items-center justify-content-center h-100 text-center shadow-lg border-0" style="background: linear-gradient(135deg, #5e6ad2 0%, #7e448b 100%); color: white;">
                                <div class="rounded-circle d-flex align-items-center justify-content-center shadow-sm bg-white text-primary mb-4" style="width: 80px; height: 80px;">
                                    <i class="bi bi-person-hearts fs-1"></i>
                                </div>
                                <p class="text-white-50 small fw-bold text-uppercase mb-2">Base de Pacientes Clínicos</p>
                                <h1 class="fw-bold text-white display-4">{{ number_format($totalPatients) }}</h1>
                                <small class="text-white-50"><i class="bi bi-clock-history"></i> Histórico totalizado</small>
                            </div>
                        </div>

                        <!-- KPI 2: Jornada Diaria -->
                        <div class="col-md-4">
                            <div class="modern-card p-5 d-flex flex-column align-items-center justify-content-center h-100 text-center shadow-lg border-0" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white;">
                                <div class="rounded-circle d-flex align-items-center justify-content-center shadow-sm bg-white text-success mb-4" style="width: 80px; height: 80px;">
                                    <i class="bi bi-calendar2-check-fill fs-1"></i>
                                </div>
                                <p class="text-white-50 small fw-bold text-uppercase mb-2">Agenda del Día</p>
                                <h1 class="fw-bold text-white display-4 mb-0">{{ number_format($totalBookedToday) }}</h1>
                                <p class="fw-bold text-white small mb-3">Turnos Totales</p>
                                <div class="d-flex justify-content-center gap-3 w-100">
                                    <div class="bg-white bg-opacity-25 text-white px-3 py-1 rounded-pill small fw-bold shadow-sm border border-white border-opacity-25">
                                        <i class="bi bi-check-circle"></i> Libres: {{ $totalFreeToday }}
                                    </div>
                                    <div class="bg-white bg-opacity-25 text-white px-3 py-1 rounded-pill small fw-bold shadow-sm border border-white border-opacity-25">
                                        <i class="bi bi-exclamation-circle"></i> Sobre-turnos: {{ $totalExtraToday }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- KPI 3: Margen de Crecimiento -->
                        <div class="col-md-4">
                            <div class="modern-card p-5 d-flex flex-column align-items-center justify-content-center h-100 text-center shadow-lg border-0" style="background: linear-gradient(135deg, #00c6ff 0%, #0072ff 100%); color: white;">
                                <div class="rounded-circle d-flex align-items-center justify-content-center shadow-sm bg-white text-info mb-4" style="width: 80px; height: 80px;">
                                    <i class="bi bi-graph-up-arrow fs-1"></i>
                                </div>
                                <p class="text-white-50 small fw-bold text-uppercase mb-2">Captación (Últimos 30d)</p>
                                <h1 class="fw-bold text-white display-4">+{{ number_format($newPatientsThisMonth) }}</h1>
                                <span class="badge bg-white bg-opacity-25 rounded-pill text-white px-3 py-2 mt-2 f-12 shadow-sm border border-white border-opacity-25">
                                    {{ $growth >= 0 ? '▲' : '▼' }} {{ number_format($growth, 1) }}% comparado al último mes
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- PÁGINA 2: Obras Sociales (Doughnut) -->
        <div class="carousel-item">
            <div class="slide-wrapper">
                <div class="container-fluid px-0 h-100 d-flex flex-column justify-content-md-center justify-content-start align-items-center pt-3 pt-md-0">
                    <h4 class="fw-bold text-dark mb-4 text-center" style="letter-spacing: -1px;">Matriz Demográfica y Coberturas Médicas</h4>
                    
                    <div class="modern-card p-4 shadow-lg w-100" style="max-width: 900px;">
                        <div class="row align-items-center">
                            <!-- Gráfico -->
                            <div class="col-md-7 border-end">
                                <div style="height: 400px; width:100%;" class="d-flex justify-content-center">
                                    <canvas id="osChart"></canvas>
                                </div>
                            </div>
                            <!-- Leyenda Textual -->
                            <div class="col-md-5 ps-md-5">
                                <h5 class="fw-bold mb-4">Top 5 Coberturas Activas</h5>
                                @foreach($patientsByObraSocial as $index => $os)
                                    <div class="d-flex justify-content-between align-items-center mb-4 p-3 rounded-4 shadow-sm" style="background: rgba(0,0,0,0.02); border-left: 4px solid {{ $os->color }};">
                                        <div>
                                            <span class="badge rounded-circle me-2" style="background-color: {{ $os->color }}; color: #fff;">{{ $index + 1 }}</span>
                                            <span class="fw-bold text-dark fs-6">{{ $os->os_name }}</span>
                                        </div>
                                        <h5 class="fw-bold mb-0" style="color: {{ $os->color }};">{{ $os->count }} <small class="text-muted fw-normal fs-6">pts</small></h5>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- PÁGINA 3: Histórico Anual (Barras) -->
        <div class="carousel-item">
            <div class="slide-wrapper">
                <div class="container-fluid px-0 h-100 d-flex flex-column justify-content-md-center justify-content-start pt-3 pt-md-0">
                    <h4 class="fw-bold text-dark mb-4 text-center" style="letter-spacing: -1px;">Evolución Cronológica de Visitas ({{ now()->year }})</h4>
                    
                    <div class="modern-card p-5 shadow-lg w-100 h-100 d-flex flex-column">
                        <div style="flex-grow: 1; min-height: 450px;">
                            <canvas id="annualChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Navegación Furtiva (Stealth) -->
    <button class="pager-stealth-arrow left-arrow" type="button" data-bs-target="#dashboardSlider" data-bs-slide="prev">
        <i class="bi bi-chevron-compact-left"></i>
        <span class="visually-hidden">Anterior</span>
    </button>
    <button class="pager-stealth-arrow right-arrow" type="button" data-bs-target="#dashboardSlider" data-bs-slide="next">
        <i class="bi bi-chevron-compact-right"></i>
        <span class="visually-hidden">Siguiente</span>
    </button>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // Keyboard Hardware Navigation (The Doctor's Secret)
    document.addEventListener('keydown', function(event) {
        let carouselElement = document.getElementById('dashboardSlider');
        let carouselInstance = bootstrap.Carousel.getInstance(carouselElement) || new bootstrap.Carousel(carouselElement);
        
        if (event.code === 'ArrowRight') {
            carouselInstance.next();
        } else if (event.code === 'ArrowLeft') {
            carouselInstance.prev();
        }
    });

    // Chart.js Shared Settings
    const primaryColor = '#5E6AD2';
    const secondaryColor = '#8A94DF';
    const infoColor = '#0dcaf0';
    const successColor = '#20c997';
    const warningColor = '#ffc107';
    
    Chart.defaults.font.family = "'Outfit', sans-serif";
    Chart.defaults.color = '#7f8a9e';

    // -----------------------------------------
    // RENDER: PÁGINA 2 (Obras Sociales Doughnut)
    // -----------------------------------------
    const osDataRaw = @json($patientsByObraSocial);
    const labelsOs = osDataRaw.map(d => d.os_name);
    const valuesOs = osDataRaw.map(d => d.count);
    
    // Colorful array matching database dynamic colors
    const chartColors = osDataRaw.map(d => d.color);

    new Chart(document.getElementById('osChart').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: labelsOs,
            datasets: [{
                data: valuesOs,
                backgroundColor: chartColors,
                borderWidth: 5,
                borderColor: '#ffffff',
                hoverOffset: 15
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '70%',
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(26, 26, 46, 0.95)',
                    titleFont: { size: 14 },
                    bodyFont: { size: 16, weight: 'bold' },
                    padding: 16,
                    cornerRadius: 12,
                    boxPadding: 8
                }
            },
            animation: { animateScale: true, animateRotate: true, duration: 2000, easing: 'easeOutExpo' }
        }
    });

    // -----------------------------------------
    // RENDER: PÁGINA 3 (Evolución Anual Bar Chart)
    // -----------------------------------------
    const ctxAnnual = document.getElementById('annualChart').getContext('2d');
    const annualDataRaw = @json($annualData);
    const labelsAnnual = annualDataRaw.map(d => d.month);
    const valuesAnnual = annualDataRaw.map(d => d.total);
    
    let barGradient = ctxAnnual.createLinearGradient(0, 0, 0, 500);
    barGradient.addColorStop(0, primaryColor);
    barGradient.addColorStop(1, 'rgba(94, 106, 210, 0.05)');

    new Chart(ctxAnnual, {
        type: 'bar',
        data: {
            labels: labelsAnnual,
            datasets: [{
                label: 'Pacientes Clínicos Atendidos',
                data: valuesAnnual,
                backgroundColor: barGradient,
                borderRadius: {topLeft: 15, topRight: 15, bottomLeft: 0, bottomRight: 0},
                borderSkipped: false,
                borderWidth: 0,
                barThickness: 'flex',
                maxBarThickness: 60
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(26, 26, 46, 0.95)',
                    titleFont: { size: 14 },
                    bodyFont: { size: 16, weight: 'bold' },
                    padding: 16,
                    cornerRadius: 8
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(0,0,0,0.04)', drawBorder: false, tickLength: 0 },
                    ticks: { padding: 15, font: { weight: 'bold'} }
                },
                x: {
                    grid: { display: false, drawBorder: false },
                    ticks: { font: { weight: 'bold'} }
                }
            },
            animation: { duration: 2000, easing: 'easeOutExpo' }
        }
    });
});
</script>
@endsection
