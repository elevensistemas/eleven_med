<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Cortalezzi') }} - @yield('title', 'Admin Panel')</title>

    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Build Styles -->
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    
    <!-- Chart.js para IA -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        /* Enterprise Card-Style Layout Overrides with Vibrant Colors */
        body { 
            background: #f7f8fa;
            background-image: 
                radial-gradient(at 0% 0%, rgba(94, 106, 210, 0.1) 0px, transparent 50%),
                radial-gradient(at 100% 0%, rgba(126, 68, 139, 0.1) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(13, 202, 240, 0.15) 0px, transparent 50%),
                radial-gradient(at 0% 100%, rgba(94, 106, 210, 0.1) 0px, transparent 50%);
            background-attachment: fixed;
            font-family: 'Outfit', sans-serif;
            overflow-x: hidden;
        }
        .sidebar {
            width: 240px;
            background: linear-gradient(160deg, #5e6ad2 0%, #7e448b 100%);
            border: none;
            border-radius: 20px;
            position: fixed;
            height: calc(100vh - 2rem);
            top: 1rem;
            left: 1rem;
            z-index: 1000;
            transition: all 0.3s ease;
            box-shadow: 0 15px 35px rgba(94, 106, 210, 0.3);
            overflow-y: auto;
            overflow-x: hidden;
        }
        /* Custom Scrollbar for Sidebar */
        .sidebar::-webkit-scrollbar {
            width: 4px;
        }
        .sidebar::-webkit-scrollbar-track {
            background: transparent;
        }
        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 10px;
        }
        .sidebar::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.4);
        }
        /* Override sidebar text colors to fit the vibrant background */
        .sidebar .brand-logo-main {
            background: none;
            -webkit-text-fill-color: #ffffff;
            color: #ffffff;
            text-shadow: 0 2px 10px rgba(0,0,0,0.15);
        }
        .sidebar .brand-subtitle-vision { color: #a1ecff; }
        .sidebar .nav-sidebar .nav-link { 
            color: rgba(255,255,255,0.7); 
            padding: 0.4rem 1rem;
            font-size: 0.9rem;
            margin: 0.1rem 1rem;
        }
        .sidebar .nav-sidebar .nav-link i { color: rgba(255,255,255,0.7); font-size: 1.1rem; }
        .sidebar .nav-sidebar .nav-link:hover, .sidebar .nav-sidebar .nav-link.active {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(5px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .sidebar .nav-sidebar .nav-link.active::before { background: #ffffff; }
        .sidebar .nav-sidebar .nav-link.active i { color: #ffffff; }
        .sidebar .text-muted { color: rgba(255,255,255,0.6) !important; }
        .sidebar .text-success { color: #a1ecff !important; }
        .sidebar .card { background: rgba(0,0,0,0.15) !important; color: #fff; }
        .sidebar .text-primary { color: #ffffff !important; }
        .sidebar .bg-danger { background-color: #ff4757 !important; }
        
        .main-content {
            margin-left: calc(240px + 1rem);
            padding: 1rem 2rem 2rem 2rem;
            min-height: 100vh;
            background-color: transparent;
        }
        .sidebar-brand {
            padding: 2rem 1.5rem;
            font-weight: 800;
            font-size: 1.25rem;
            border-bottom: 1px solid rgba(0,0,0,0.03);
            text-transform: uppercase;
            letter-spacing: 1px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .nav-sidebar .nav-link {
            color: #4b5563;
            padding: 1rem 1.5rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all 0.3s ease;
            border-radius: 0 16px 16px 0;
            margin: 4px 1rem 4px 0;
            position: relative;
        }
        .nav-sidebar .nav-link i { font-size: 1.2rem; opacity: 0.7; transition: all 0.3s ease; }
        .nav-sidebar .nav-link:hover, .nav-sidebar .nav-link.active {
            color: #5e6ad2;
            background: #ffffff;
            box-shadow: 0 4px 15px rgba(94, 106, 210, 0.08);
        }
        .nav-sidebar .nav-link.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 10%;
            height: 80%;
            width: 4px;
            background: #5e6ad2;
            border-radius: 0 4px 4px 0;
        }
        .nav-sidebar .nav-link.active i { color: #5e6ad2; opacity: 1; transform: scale(1.1); }
        .topbar {
            background: linear-gradient(135deg, #5e6ad2 0%, #7e448b 100%);
            padding: 1rem 1.5rem;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(94, 106, 210, 0.25);
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: none;
            position: sticky;
            top: 1rem;
            z-index: 100;
        }
        .topbar .text-dark { color: #ffffff !important; }
        .topbar .text-muted { color: rgba(255, 255, 255, 0.7) !important; }
        
        .omnibar-search {
            display: flex;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 12px;
            overflow: hidden;
            width: 400px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: inset 0 2px 5px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            backdrop-filter: blur(5px);
        }
        .omnibar-search:focus-within {
            background: rgba(255, 255, 255, 0.25);
            border-color: rgba(255, 255, 255, 0.4);
            box-shadow: 0 0 0 4px rgba(255, 255, 255, 0.1);
        }
        .omnibar-search input {
            border: none;
            background: transparent;
            padding: 0.7rem 1.2rem;
            width: 100%;
            outline: none;
            color: #fff;
            font-weight: 500;
        }
        .omnibar-search input::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }
        .omnibar-search button {
            background: transparent;
            color: #fff;
            border: none;
            padding: 0 1.2rem;
            transition: all 0.2s;
            font-size: 1.1rem;
        }
        .omnibar-search button:hover {
            transform: scale(1.1);
        }
        .omnibar-icon {
            color: #fff;
            background: rgba(255, 255, 255, 0.15);
            width: 42px;
            height: 42px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            font-size: 1.2rem;
            cursor: pointer;
            position: relative;
            transition: all 0.2s;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        .omnibar-icon:hover {
            background: rgba(255, 255, 255, 0.25);
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }
        .omnibar-badge {
            position: absolute;
            top: -6px;
            right: -6px;
            background: #dc3545;
            color: white;
            font-size: 0.65rem;
            width: 18px;
            height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-weight: bold;
            border: 2px solid #fff;
        }
        .user-dropdown .dropdown-toggle { font-weight: 600; color: #333; }
        .modern-card {
            background: #fff;
            border: 1px solid rgba(0, 0, 0, 0.04);
            border-radius: 20px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.03);
            overflow: hidden;
        }

        @keyframes pulse-animation {
            0% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7); }
            70% { box-shadow: 0 0 0 5px rgba(220, 53, 69, 0); }
            100% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0); }
        }
        .pulse-badge {
            animation: pulse-animation 2s infinite;
        }

        /* LED Brand Styles (Cortalezzi Vision) */
        .brand-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 1.5rem 1rem 1rem 1rem;
            border-bottom: 1px solid rgba(0,0,0,0.03);
            text-align: center;
        }
        .brand-logo-main {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 1.35rem;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-family: 'Outfit', sans-serif;
            background: linear-gradient(135deg, #b5a4ff 0%, #876dff 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            filter: drop-shadow(0 0 8px rgba(181, 164, 255, 0.45));
            line-height: 1;
        }
        .brand-subtitle-vision {
            font-size: 0.75rem;
            font-weight: 800;
            letter-spacing: 6px;
            margin-top: 4px;
            margin-left: 6px;
            color: #0dcaf0;
            text-transform: uppercase;
            animation: vision-fade 2.5s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        @keyframes vision-fade {
        }
        @keyframes vision-fade {
            0%, 100% {
                opacity: 1;
                text-shadow: 0 0 6px rgba(13, 202, 240, 0.7), 0 0 15px rgba(13, 202, 240, 0.4);
            }
            50% {
                opacity: 0.2;
                text-shadow: 0 0 2px rgba(13, 202, 240, 0.1);
            }
        }

        /* Vercel/Linear Style Premium Dark Mode */
        body.theme-dark {
            background: none !important;
            background-color: #0b1121 !important;
            color: #ededed !important;
        }
        body.theme-dark .main-content {
            background-color: transparent !important;
        }
        body.theme-dark .sidebar, 
        body.theme-dark .topbar, 
        body.theme-dark .bg-white, 
        body.theme-dark .modern-card, 
        body.theme-dark .toast,
        body.theme-dark .modal-content,
        body.theme-dark #floatingChatWidget,
        body.theme-dark .offcanvas {
            background: #111827 !important;
            background-color: #111827 !important;
            border: 1px solid rgba(255, 255, 255, 0.08) !important;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5) !important;
            color: #ededed !important;
        }
        body.theme-dark .sidebar .nav-sidebar .nav-link { color: rgba(255,255,255,0.6); }
        body.theme-dark .sidebar .nav-sidebar .nav-link:hover, body.theme-dark .sidebar .nav-sidebar .nav-link.active {
            background: rgba(255, 255, 255, 0.08) !important;
            color: #ffffff !important;
        }
        body.theme-dark .text-dark {
            color: #ffffff !important;
        }
        body.theme-dark .text-muted {
            color: #adb5bd !important;
        }
        body.theme-dark .table {
            color: #e0e0e0 !important;
        }
        body.theme-dark .table th, body.theme-dark .table td {
            background-color: transparent !important;
            border-color: #333 !important;
        }
        body.theme-dark input, 
        body.theme-dark select,
        body.theme-dark .form-control,
        body.theme-dark .bg-light {
            background-color: #1e293b !important;
            color: #f8f9fa !important;
            border-color: #334155 !important;
        }
        body.theme-dark .dropdown-menu {
            background-color: #1e293b !important;
            border-color: #334155 !important;
        }
        body.theme-dark .dropdown-item {
            color: #e0e0e0 !important;
            background-color: transparent !important;
        }
        body.theme-dark .dropdown-item:hover {
            background-color: #334155 !important;
            color: #fff !important;
        }
        body.theme-dark .list-group-item {
            background-color: transparent !important;
            border-color: #334155 !important;
            color: #e0e0e0 !important;
        }
        body.theme-dark .nav-sidebar .nav-link {
            color: #adb5bd;
        }
        body.theme-dark .omnibar-search {
            background: rgba(255,255,255,0.05);
            border-color: rgba(255,255,255,0.1);
        }
        body.theme-dark .omnibar-search input { color: #fff; }

        /* Dark Theme for AI Widget */
        body.theme-dark #floatingAIWidget {
            background: #0f172a !important;
            border-color: #1e293b !important;
        }
        body.theme-dark #aiChatMessages {
            background-color: #020617 !important;
        }
        body.theme-dark #floatingAIWidget .bg-white,
        body.theme-dark #floatingAIWidget .bg-light {
            background-color: #1e293b !important;
            color: #f8f9fa !important;
            border-color: #334155 !important;
        }

        /* Responsive Topbar Tweaks */
        @media (max-width: 1200px) {
            .omnibar-search {
                width: 300px;
            }
        }
        @media (max-width: 992px) {
            #omnibar-clock {
                font-size: 0.75rem !important;
                padding: 0.3rem 0.5rem !important;
                gap: 0.3rem !important;
            }
            .omnibar-search input {
                padding: 0.4rem 0.8rem;
                font-size: 0.85rem;
            }
            .omnibar-icon {
                width: 36px;
                height: 36px;
                font-size: 1rem;
            }
            .main-content {
                padding: 1rem;
            }
        }
        
        .sortable-ghost {
            opacity: 0.4;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
        }
    </style>
    
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
    
    <script>
        // Init theme immediately to prevent flashing
        if(localStorage.getItem('theme') === 'dark') {
            document.documentElement.classList.add('theme-dark');
            if(document.body) document.body.classList.add('theme-dark');
        }
    </script>
</head>
<body class="{{ rtrim(' ' . '<script>document.write(localStorage.getItem("theme") === "dark" ? "theme-dark" : "")</script>') }}">

    <!-- Sidebar -->
    <aside class="sidebar d-flex flex-column">
        <div class="brand-container">
            <div class="brand-logo-main">
                <i class="bi bi-eye-fill" style="font-size: 1.8rem; filter: drop-shadow(0 0 6px rgba(181, 164, 255, 0.6));"></i>
                CORTALEZZI
            </div>
            <div class="brand-subtitle-vision">Vision</div>
        </div>
        <div class="nav flex-column nav-sidebar mt-4 flex-grow-1" id="sortable-sidebar">
            <a href="{{ url('/home') }}" class="nav-link {{ request()->is('home') ? 'active' : '' }}" data-id="dashboard">
                <i class="bi bi-grid"></i> Dashboard
            </a>
            <a href="{{ route('users.index') }}" class="nav-link {{ request()->is('users*') ? 'active' : '' }}" data-id="users">
                <i class="bi bi-people"></i> Usuarios & Roles
            </a>
            <a href="{{ route('patients.index') }}" class="nav-link {{ request()->is('patients*') ? 'active' : '' }}" data-id="patients">
                <i class="bi bi-person-vcard"></i> Pacientes
            </a>
            @php
                $queueCount = \App\Models\Patient::whereHas('assignments', function ($q) {
                    $q->whereDate('started_at', \Carbon\Carbon::today())->where('status', 'in_progress');
                })->count();
            @endphp
            <a href="{{ route('console.index') }}" class="nav-link {{ request()->is('console*') ? 'active' : '' }} fw-bold text-primary" data-id="console">
                <i class="bi bi-display"></i> Consola Pacientes 
                @if($queueCount > 0)
                    <span class="badge bg-danger rounded-pill ms-2 pulse-badge" style="font-size: 0.75rem; padding: 0.35em 0.65em;">{{ $queueCount }}</span>
                @endif
            </a>
            <a href="{{ route('agenda.index') }}" class="nav-link {{ request()->is('agenda') ? 'active' : '' }}" data-id="agenda">
                <i class="bi bi-calendar-check"></i> Agenda Médica
            </a>
            <a href="{{ route('agenda.settings') }}" class="nav-link {{ request()->is('agenda/settings') ? 'active' : '' }}" style="padding-left: 2.5rem; font-size: 0.9em;" data-id="agenda_settings">
                <i class="bi bi-gear-fill"></i> Crear Agenda
            </a>
            <a href="{{ route('news.index') }}" class="nav-link {{ request()->is('noticias*') ? 'active' : '' }}" data-id="news">
                <i class="bi bi-newspaper"></i> Noticias Médicas
            </a>
            <a href="{{ route('config.index') }}" class="nav-link {{ request()->is('configuracion') ? 'active' : '' }}" data-id="config">
                <i class="bi bi-sliders"></i> Módulo Configuración
            </a>
            <a href="{{ route('whatsapp.index') }}" class="nav-link {{ request()->routeIs('whatsapp.*') ? 'active' : '' }}" style="padding-left: 2.5rem; font-size: 0.9em;" data-id="whatsapp_config">
                <i class="bi bi-whatsapp text-success"></i> WhatsApp API
            </a>
            <a href="{{ route('chatit.index') }}" class="nav-link {{ request()->routeIs('chatit.index') ? 'active' : '' }} mt-2" style="background: linear-gradient(90deg, rgba(94, 106, 210, 0.05) 0%, transparent 100%);" data-id="chatit">
                <i class="bi bi-asterisk text-primary"></i> <span class="fw-bold text-primary">Chat IA</span>
            </a>
        </div>

    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Phase 20 Omnibar -->
        <header class="topbar">
            <!-- Left: Omnibar Search -->
            <form action="{{ route('global.search') }}" method="GET" class="omnibar-search">
                <input type="text" name="q" placeholder="DNI o Nombre / Apellido del paciente..." required>
                <button type="submit" title="Buscar paciente"><i class="bi bi-search"></i></button>
            </form>

            <!-- Right: Utilities -->
            <div class="d-flex align-items-center gap-2 gap-md-3" id="sortable-topbar">
                
                <!-- Date/Time Badge (Elegante) -->
                <div id="omnibar-clock" data-id="clock" class="d-flex text-nowrap" style="background: rgba(255, 255, 255, 0.15); color: #ffffff; padding: 0.4rem 0.8rem; border-radius: 12px; font-weight: 600; font-size: 0.85rem; align-items: center; gap: 0.5rem; border: 1px solid rgba(255, 255, 255, 0.2); backdrop-filter: blur(5px);">
                    <i class="bi bi-calendar3"></i> <span id="omnibar-date">--</span>
                    <span class="text-white opacity-50 mx-1">|</span>
                    <i class="bi bi-clock"></i> <span id="omnibar-time">--</span>
                </div>

                <!-- Dropdown: Últimos Buscados -->
                <div class="dropdown" data-id="recent">
                    <i class="bi bi-clock-history omnibar-icon" data-bs-toggle="dropdown" aria-expanded="false" title="Últimos pacientes buscados"></i>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3 mt-3 p-2" style="width: 300px;">
                        <li class="dropdown-header fw-bold text-primary">Vistos Recientemente</li>
                        @if(isset($recentPatients) && count($recentPatients) > 0)
                            @foreach($recentPatients as $rp)
                                <li>
                                    <a class="dropdown-item py-2 d-flex align-items-center gap-2 rounded" href="{{ route('patients.show', $rp['id']) }}">
                                        <i class="bi bi-person-circle text-muted"></i>
                                        <div class="d-flex flex-column">
                                            <span class="fw-bold text-dark" style="font-size: 0.9rem;">{{ mb_strtoupper($rp['name']) }}</span>
                                            <small class="text-muted" style="font-size: 0.75rem;">DNI: {{ $rp['dni'] }}</small>
                                        </div>
                                    </a>
                                </li>
                            @endforeach
                        @else
                            <li><span class="dropdown-item text-muted small py-3 text-center">No hay historial reciente</span></li>
                        @endif
                    </ul>
                </div>

                <!-- Dropdown: Pacientes en Espera -->
                <div class="dropdown" data-id="waiting">
                    <i class="bi bi-clock-fill omnibar-icon" data-bs-toggle="dropdown" aria-expanded="false" title="Pacientes en Espera">
                        @if(isset($waitingAssignments) && $waitingAssignments->count() > 0)
                            <span class="omnibar-badge">{{ $waitingAssignments->count() }}</span>
                        @endif
                    </i>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3 mt-3 p-2" style="width: 350px;">
                        <li class="dropdown-header fw-bold text-danger border-bottom mb-2 pb-2">
                            <i class="bi bi-exclamation-circle-fill me-1"></i> Pacientes Aguardando
                        </li>
                        @if(isset($waitingAssignments) && $waitingAssignments->count() > 0)
                            @foreach($waitingAssignments as $waiting)
                                <li>
                                    <div class="dropdown-item py-2 d-flex flex-column gap-1 pointer-event-none rounded">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="fw-bold text-dark" style="font-size: 0.9rem;">{{ $waiting->patient->last_name }}</span>
                                            <span class="badge bg-danger bg-opacity-10 text-danger rounded-pill">{{ \Carbon\Carbon::parse($waiting->start_time)->diffForHumans(null, true) }}</span>
                                        </div>
                                        <small class="text-muted" style="font-size: 0.75rem;">Espera por: <b>{{ $waiting->doctor->name }}</b></small>
                                    </div>
                                </li>
                            @endforeach
                            <li><hr class="dropdown-divider"></li>
                            <li><a href="{{ route('console.index') }}" class="dropdown-item text-center fw-bold text-primary small py-2">Ir a la Consola</a></li>
                        @else
                            <li><span class="dropdown-item text-muted small py-3 text-center">Sala de espera vacía</span></li>
                        @endif
                    </ul>
                </div>

                <!-- Dropdown: Notificaciones -->
                <div class="dropdown" data-id="notifications">
                    <i class="bi bi-bell-fill omnibar-icon" data-bs-toggle="dropdown" aria-expanded="false" title="Notificaciones">
                        <span id="globalBellBadge" class="omnibar-badge bg-secondary d-none">0</span>
                    </i>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3 mt-3 p-2" id="globalBellMenu" style="width: 320px; max-height: 400px; overflow-y: auto;">
                        <li class="dropdown-header fw-bold text-primary d-flex justify-content-between align-items-center">
                            <span>Notificaciones del Sistema</span>
                            <button class="btn btn-sm text-muted p-0" onclick="clearSystemNotifications()" title="Limpiar"><i class="bi bi-trash"></i></button>
                        </li>
                        <li id="noNotifsItem"><span class="dropdown-item text-muted small py-4 text-center"><i class="bi bi-check2-all fs-4 d-block mb-2"></i>Todo al día</span></li>
                    </ul>
                </div>

                <!-- Theme Toggle -->
                <div class="ms-md-2" data-id="theme">
                    <button class="btn btn-light rounded-circle shadow-sm omnibar-icon p-0 border-0" id="themeToggleBtn" title="Cambiar Tema" onclick="toggleThemeGlobal()">
                        <i class="bi bi-moon-stars-fill"></i>
                    </button>
                </div>

                <div class="dropdown user-dropdown ms-md-3 pl-md-3" data-id="profile" style="border-left: 2px solid rgba(94, 106, 210, 0.15);">
                    <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle px-2" data-bs-toggle="dropdown" aria-expanded="false">
                        <img src="{{ Auth::user()->avatar_url }}" width="42" height="42" class="rounded-circle shadow-sm me-2 object-fit-cover">
                        <div class="d-none d-md-block">
                            <span class="d-block fw-bold text-dark" style="font-size: 0.95rem; line-height: 1;">{{ Auth::user()->name }}</span>
                            <small class="text-muted" style="font-size: 0.75rem;">{{ Auth::user()->getRoleNames()->first() ?? 'Staff' }}</small>
                        </div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3 mt-3">
                        <li class="px-3 py-2 text-center border-bottom mb-2">
                            <img src="{{ Auth::user()->avatar_url }}" width="48" height="48" class="rounded-circle shadow-sm mb-2 object-fit-cover">
                            <div class="fw-bold">{{ Auth::user()->name }}</div>
                            <small class="text-muted d-block">{{ Auth::user()->getRoleNames()->first() ?? 'Staff' }}</small>
                        </li>
                        <li>
                            <a class="dropdown-item py-2 text-danger fw-bold" href="{{ route('logout') }}"
                               onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                <i class="bi bi-box-arrow-right me-2"></i> Cerrar Sesión
                            </a>
                            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                @csrf
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </header>

        <!-- Script for Omnibar Live Clock -->
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const dateEl = document.getElementById('omnibar-date');
                const timeEl = document.getElementById('omnibar-time');

                function updateClock() {
                    const now = new Date();
                    
                    const day = String(now.getDate()).padStart(2, '0');
                    const month = String(now.getMonth() + 1).padStart(2, '0');
                    const year = now.getFullYear();
                    
                    const hours = String(now.getHours()).padStart(2, '0');
                    const minutes = String(now.getMinutes()).padStart(2, '0');

                    dateEl.textContent = `${day}-${month}-${year}`;
                    timeEl.textContent = `${hours}:${minutes}`;
                }

                updateClock();
                setInterval(updateClock, 10000); // 10 secs

                // Init SortableJS for Sidebar
                const sidebar = document.getElementById('sortable-sidebar');
                if (sidebar) {
                    Sortable.create(sidebar, {
                        animation: 150,
                        ghostClass: 'sortable-ghost',
                        store: {
                            get: function (sortable) {
                                var order = localStorage.getItem('sidebar_order');
                                return order ? order.split('|') : [];
                            },
                            set: function (sortable) {
                                var order = sortable.toArray();
                                localStorage.setItem('sidebar_order', order.join('|'));
                            }
                        }
                    });
                }

                // Init SortableJS for Topbar
                const topbar = document.getElementById('sortable-topbar');
                if (topbar) {
                    Sortable.create(topbar, {
                        animation: 150,
                        ghostClass: 'sortable-ghost',
                        store: {
                            get: function (sortable) {
                                var order = localStorage.getItem('topbar_order');
                                return order ? order.split('|') : [];
                            },
                            set: function (sortable) {
                                var order = sortable.toArray();
                                localStorage.setItem('topbar_order', order.join('|'));
                            }
                        }
                    });
                }
            });
        </script>

        <!-- Page Content -->
        @if(session('success'))
            <div class="alert alert-success border-0 rounded-4 shadow-sm mb-4 d-flex align-items-center">
                <i class="bi bi-check-circle-fill fs-4 me-3"></i>
                <div>{{ session('success') }}</div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        
        @if($errors->any())
            <div class="alert alert-danger border-0 rounded-4 shadow-sm mb-4 d-flex align-items-center">
                <i class="bi bi-exclamation-triangle-fill fs-4 me-3"></i>
                <ul class="mb-0 px-2 text-start">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @yield('content')

    </main>

    <!-- Global Notifications Toasts -->
    <div class="toast-container position-fixed top-0 end-0 p-3" id="globalToasts" style="z-index: 9999;"></div>

    <!-- Floating Messenger Widget -->
    <div id="floatingChatWidget" class="position-fixed shadow-lg rounded-4 overflow-hidden d-none flex-column" style="bottom: 20px; right: 20px; width: 350px; height: 500px; max-height: 80vh; background: #fff; z-index: 1050; border: 1px solid rgba(0,0,0,0.1);">
        
        <!-- Header -->
        <div class="p-3 bg-white border-bottom d-flex align-items-center justify-content-between">
            <h6 class="mb-0 fw-bold d-flex align-items-center"><i class="bi bi-chat-dots-fill text-primary me-2"></i> Mensajes</h6>
            <button class="btn btn-sm btn-light border-0 rounded-circle" onclick="toggleFloatingChat()"><i class="bi bi-x-lg"></i></button>
        </div>

        <!-- Contact List View -->
        <div id="fc-contact-list" class="flex-grow-1 overflow-auto bg-light">
            <div class="list-group list-group-flush">
                @php $staff = \App\Models\User::where('id', '!=', Auth::id())->get(); @endphp
                @foreach($staff as $u)
                <button type="button" class="list-group-item list-group-item-action border-0 mb-1 p-3 d-flex align-items-center bg-transparent" onclick="openChatArea({{ $u->id }}, '{{ $u->name }}')">
                    <div class="position-relative">
                        <img src="{{ $u->avatar_url }}" class="rounded-circle shadow-sm object-fit-cover" width="45" height="45">
                        <span class="position-absolute bottom-0 end-0 p-1 border border-light rounded-circle bg-secondary" id="online-dot-{{ $u->id }}"></span>
                    </div>
                    <div class="ms-3 text-start flex-grow-1">
                        <span class="d-block fw-bold text-dark">{{ $u->name }}</span>
                        <small class="text-muted">{{ $u->roles->first()?->name ?? 'Staff' }}</small>
                    </div>
                </button>
                @endforeach
            </div>
        </div>

        <!-- Direct Message View (Hidden by default) -->
        <div id="activeChatArea" class="d-none flex-column h-100 bg-white" style="position: absolute; top:0; left:0; width: 100%; z-index: 10;">
            <div class="p-3 border-bottom d-flex align-items-center bg-white shadow-sm z-1">
                <button class="btn btn-sm btn-light rounded-circle me-2" onclick="closeChatArea()"><i class="bi bi-arrow-left"></i></button>
                <div class="d-flex align-items-center gap-2">
                    <span class="fw-bold text-dark" id="activeChatTitle" style="font-size: 0.95rem;">Prof. Name</span>
                </div>
            </div>
            
            <div class="flex-grow-1 p-3 overflow-auto d-flex flex-column gap-2" id="chatMessages" style="background-color: #f8f9fa;">
                <div class="text-center w-100 text-muted small py-3"><i class="bi bi-shield-lock me-1"></i> Comunicación Encriptada</div>
            </div>

            <div class="p-2 bg-white border-top">
                <form id="chatForm" class="d-flex align-items-center gap-2 m-0">
                    <input type="hidden" id="chatTargetUser" value="">
                    <label for="chatAttachment" class="btn btn-light rounded-circle shadow-sm m-0 d-flex align-items-center justify-content-center text-secondary" style="width: 38px; height: 38px; cursor: pointer;" title="Adjuntar archivo">
                        <i class="bi bi-paperclip fs-5"></i>
                    </label>
                    <input type="file" id="chatAttachment" class="d-none" accept="image/*,.pdf,.doc,.docx,.xls,.xlsx">
                    <input type="text" class="form-control rounded-pill border-0 bg-light px-3 shadow-none" id="chatInputMessage" placeholder="Escribe un mensaje..." autocomplete="off" style="font-size: 0.95rem;">
                    <button type="submit" class="btn btn-primary rounded-circle shadow-sm d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; min-width: 38px;"><i class="bi bi-send-fill"></i></button>
                </form>
            </div>
        </div>
    </div>

    <!-- Floating AI Widget -->
    <div id="floatingAIWidget" class="position-fixed shadow-lg rounded-4 overflow-hidden d-none flex-column" style="bottom: 20px; right: 20px; width: 350px; height: 500px; max-height: 80vh; background: #fff; z-index: 1050; border: 1px solid rgba(0,0,0,0.1);">
        <!-- Header -->
        <div class="p-3 border-bottom d-flex align-items-center justify-content-between" style="background: linear-gradient(135deg, #5e6ad2 0%, #7e448b 100%);">
            <h6 class="mb-0 fw-bold d-flex align-items-center text-white"><i class="bi bi-robot me-2"></i> Asistente IA</h6>
            <button class="btn btn-sm btn-light border-0 rounded-circle text-primary shadow-sm" onclick="toggleFloatingAI()"><i class="bi bi-x-lg"></i></button>
        </div>
        <div class="flex-grow-1 p-3 overflow-auto d-flex flex-column gap-2" id="aiChatMessages" style="background-color: #f8f9fa;">
            <div class="text-center w-100 text-muted small py-2"><i class="bi bi-shield-lock me-1"></i> Conexión Segura RAG</div>
            <div class="p-3 mb-2 rounded-3 bg-white border align-self-start shadow-sm text-dark" style="max-width: 85%;">
                Hola <b>{{ Auth::user()->name }}</b>, soy la IA de Eleven Med. Tengo acceso a métricas y base de datos de pacientes. ¿En qué puedo ayudarte?
            </div>
        </div>
        <div class="p-2 bg-white border-top">
            <form id="aiChatForm" class="d-flex align-items-center gap-2 m-0">
                <input type="text" class="form-control rounded-pill border-0 bg-light px-3 shadow-none" id="aiChatInput" placeholder="Pregúntame algo..." autocomplete="off" style="font-size: 0.95rem;">
                <button type="submit" class="btn btn-primary rounded-circle shadow-sm d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; min-width: 38px; background: linear-gradient(135deg, #5e6ad2 0%, #7e448b 100%); border:none;"><i class="bi bi-send-fill text-white"></i></button>
            </form>
        </div>
    </div>

    <!-- Bubble Toggle Button for AI -->
    <button id="floatingAIBtn" class="position-fixed shadow-lg rounded-circle btn btn-dark border-0 p-0 d-flex align-items-center justify-content-center" style="bottom: 90px; right: 20px; width: 60px; height: 60px; z-index: 1050; background: linear-gradient(135deg, #2b1055 0%, #7597de 100%);" onclick="toggleFloatingAI()">
        <i class="bi bi-robot text-white fs-3"></i>
    </button>

    <!-- Bubble Toggle Button for Internal Chat -->
    @php
        $globalUnreadChats = \App\Models\Message::where('receiver_id', Auth::id())->where('is_read', false)->count();
    @endphp
    <button id="floatingChatBtn" class="position-fixed shadow-lg rounded-circle btn btn-primary border-0 p-0 d-flex align-items-center justify-content-center" style="bottom: 20px; right: 20px; width: 60px; height: 60px; z-index: 1050; background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);" onclick="toggleFloatingChat()">
        <i class="bi bi-chat-fill text-white fs-4 mt-1"></i>
        <span id="globalChatBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger border border-white {{ $globalUnreadChats > 0 ? '' : 'd-none' }}" style="font-size: 0.75rem;">{{ $globalUnreadChats }}</span>
    </button>
    
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script>
        const authId = {{ Auth::id() }};
        
        function toggleFloatingChat() {
            if (document.getElementById('floatingChatBtn').getAttribute('data-dragged') === 'true') return;
            const widget = document.getElementById('floatingChatWidget');
            if (widget.classList.contains('d-none')) {
                widget.classList.remove('d-none');
                widget.classList.add('d-flex');
                
                // Close AI widget if open
                document.getElementById('floatingAIWidget').classList.add('d-none');
                document.getElementById('floatingAIWidget').classList.remove('d-flex');

                // Hide badge immediately on click for instant visual feedback
                let globalBadge = document.getElementById('globalChatBadge');
                if (globalBadge) {
                    globalBadge.classList.add('d-none');
                    globalBadge.innerText = '0';
                }
            } else {
                widget.classList.add('d-none');
                widget.classList.remove('d-flex');
            }
        }

        function toggleFloatingAI() {
            if (document.getElementById('floatingAIBtn').getAttribute('data-dragged') === 'true') return;
            const aiWidget = document.getElementById('floatingAIWidget');
            if (aiWidget.classList.contains('d-none')) {
                aiWidget.classList.remove('d-none');
                aiWidget.classList.add('d-flex');
                
                // Close Chat widget if open
                document.getElementById('floatingChatWidget').classList.add('d-none');
                document.getElementById('floatingChatWidget').classList.remove('d-flex');
                
                // Focus input
                setTimeout(() => document.getElementById('aiChatInput').focus(), 100);
            } else {
                aiWidget.classList.add('d-none');
                aiWidget.classList.remove('d-flex');
            }
        }

        const AI_STORAGE_KEY = 'aiChatHistory_' + {{ Auth::id() }};
        let aiChatHistory = JSON.parse(localStorage.getItem(AI_STORAGE_KEY) || '[]');
        
        // Render saved history on load
        document.addEventListener('DOMContentLoaded', function() {
            // Setup Draggable Floating Buttons
            function makeDraggable(el, storageKey) {
                if (!el) return;
                let isDragging = false;
                let currentX, currentY, initialX, initialY;
                let xOffset = 0, yOffset = 0;
                let dragThresholdExceeded = false;

                const savedPos = localStorage.getItem(storageKey);
                if (savedPos) {
                    const pos = JSON.parse(savedPos);
                    xOffset = pos.x;
                    yOffset = pos.y;
                    setTranslate(xOffset, yOffset, el);
                }

                el.addEventListener("mousedown", dragStart, false);
                document.addEventListener("mouseup", dragEnd, false);
                document.addEventListener("mousemove", drag, false);

                el.addEventListener("touchstart", dragStart, {passive: false});
                document.addEventListener("touchend", dragEnd, false);
                document.addEventListener("touchmove", drag, {passive: false});

                function dragStart(e) {
                    dragThresholdExceeded = false;
                    if (e.type === "touchstart") {
                        initialX = e.touches[0].clientX - xOffset;
                        initialY = e.touches[0].clientY - yOffset;
                    } else {
                        initialX = e.clientX - xOffset;
                        initialY = e.clientY - yOffset;
                    }

                    if (e.target === el || el.contains(e.target)) {
                        isDragging = true;
                        el.style.transition = 'none'; // Disable hover transition during drag
                    }
                }

                function dragEnd(e) {
                    if (!isDragging) return;
                    
                    if (dragThresholdExceeded) {
                        el.setAttribute('data-dragged', 'true');
                        setTimeout(() => el.removeAttribute('data-dragged'), 100);
                    }

                    initialX = currentX;
                    initialY = currentY;
                    isDragging = false;
                    el.style.transition = ''; // Restore transition
                    
                    if (dragThresholdExceeded) {
                        localStorage.setItem(storageKey, JSON.stringify({ x: currentX, y: currentY }));
                    }
                }

                function drag(e) {
                    if (isDragging) {
                        e.preventDefault();
                        if (e.type === "touchmove") {
                            currentX = e.touches[0].clientX - initialX;
                            currentY = e.touches[0].clientY - initialY;
                        } else {
                            currentX = e.clientX - initialX;
                            currentY = e.clientY - initialY;
                        }

                        if (Math.abs(currentX - xOffset) > 5 || Math.abs(currentY - yOffset) > 5) {
                            dragThresholdExceeded = true;
                        }

                        xOffset = currentX;
                        yOffset = currentY;
                        setTranslate(currentX, currentY, el);
                    }
                }

                function setTranslate(xPos, yPos, el) {
                    el.style.transform = "translate3d(" + xPos + "px, " + yPos + "px, 0)";
                }
            }

            makeDraggable(document.getElementById('floatingAIBtn'), 'aiBtnPos');
            makeDraggable(document.getElementById('floatingChatBtn'), 'chatBtnPos');

            const msgContainer = document.getElementById('aiChatMessages');
            if (aiChatHistory.length > 0) {
                // Keep the secure connection badge
                let headerBadge = '<div class="text-center w-100 text-muted small py-2"><i class="bi bi-shield-lock me-1"></i> Conexión Segura RAG</div>';
                let html = headerBadge;
                
                aiChatHistory.forEach(msg => {
                    if (msg.role === 'user') {
                        html += `<div class="p-2 px-3 mb-2 rounded-3 text-white align-self-end shadow-sm" style="background: linear-gradient(135deg, #5e6ad2 0%, #7e448b 100%); max-width: 85%; border-bottom-right-radius: 4px !important;">${msg.content}</div>`;
                    } else if (msg.role === 'assistant') {
                        html += `<div class="p-3 mb-2 rounded-3 bg-white border align-self-start shadow-sm text-dark" style="max-width: 85%; line-height: 1.4; border-bottom-left-radius: 4px !important;">${msg.content.replace(/\n/g, '<br>')}</div>`;
                    }
                });
                msgContainer.innerHTML = html;
                setTimeout(() => msgContainer.scrollTop = msgContainer.scrollHeight, 100);
            }
        });
        document.getElementById('aiChatForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const input = document.getElementById('aiChatInput');
            const prompt = input.value.trim();
            if(!prompt) return;

            const msgContainer = document.getElementById('aiChatMessages');
            
            // Add user message
            msgContainer.insertAdjacentHTML('beforeend', `<div class="p-2 px-3 mb-2 rounded-3 text-white align-self-end shadow-sm" style="background: linear-gradient(135deg, #5e6ad2 0%, #7e448b 100%); max-width: 85%; border-bottom-right-radius: 4px !important;">${prompt}</div>`);
            aiChatHistory.push({ role: "user", content: prompt });
            localStorage.setItem(AI_STORAGE_KEY, JSON.stringify(aiChatHistory));
            input.value = '';
            msgContainer.scrollTop = msgContainer.scrollHeight;

            // Add typing indicator
            const typingId = 'typing_' + Date.now();
            msgContainer.insertAdjacentHTML('beforeend', `<div id="${typingId}" class="p-2 px-3 mb-2 rounded-3 bg-white border align-self-start shadow-sm text-muted" style="max-width: 85%;"><i class="bi bi-three-dots"></i> Escribiendo...</div>`);
            msgContainer.scrollTop = msgContainer.scrollHeight;

            axios.post('{{ route("chatit.ask") }}', { 
                prompt: prompt, 
                history: aiChatHistory
            }, {
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') }
            }).then(res => {
                const el = document.getElementById(typingId);
                if(el) el.remove();
                let reply = res.data.reply || 'Sin respuesta.';
                
                // Parse CHART format: [CHART:pie|Pacientes por OS|OSDE:10,Particular:5]
                let chartMatch = reply.match(/\[CHART:(pie|bar|doughnut)\|([^\|]+)\|([^\]]+)\]/);
                let chartHtml = '';
                if (chartMatch) {
                    let cType = chartMatch[1];
                    let cTitle = chartMatch[2];
                    let cDataRaw = chartMatch[3];
                    let labels = [];
                    let dataValues = [];
                    cDataRaw.split(',').forEach(pair => {
                        let parts = pair.split(':');
                        if (parts.length === 2) {
                            labels.push(parts[0].trim());
                            dataValues.push(parseInt(parts[1].trim()));
                        }
                    });
                    
                    let canvasId = 'aiChart_' + Date.now();
                    chartHtml = `<div class="mt-3 bg-white p-2 rounded border shadow-sm"><canvas id="${canvasId}" style="max-height: 200px;"></canvas></div>`;
                    
                    // Remove the [CHART...] tag from the text reply
                    reply = reply.replace(chartMatch[0], '').trim();
                    
                    // Render the chart after the element is injected
                    setTimeout(() => {
                        let ctx = document.getElementById(canvasId).getContext('2d');
                        new Chart(ctx, {
                            type: cType,
                            data: {
                                labels: labels,
                                datasets: [{
                                    label: cTitle,
                                    data: dataValues,
                                    backgroundColor: [
                                        '#5e6ad2', '#20c997', '#fd7e14', '#e83e8c', '#6f42c1', '#17a2b8', '#ffc107', '#28a745'
                                    ],
                                    borderWidth: 1
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: { position: 'right', labels: { boxWidth: 10, font: { size: 10 } } },
                                    title: { display: true, text: cTitle, font: { size: 12 } }
                                }
                            }
                        });
                    }, 300);
                }
                
                let textHtml = reply ? `<div style="line-height: 1.4;">${reply.replace(/\n/g, '<br>')}</div>` : '';
                msgContainer.insertAdjacentHTML('beforeend', `<div class="p-3 mb-2 rounded-3 bg-white border align-self-start shadow-sm text-dark" style="max-width: 85%; border-bottom-left-radius: 4px !important;">${textHtml}${chartHtml}</div>`);
                aiChatHistory.push({ role: "assistant", content: reply });
                
                if(aiChatHistory.length > 20) aiChatHistory = aiChatHistory.slice(-20);
                localStorage.setItem(AI_STORAGE_KEY, JSON.stringify(aiChatHistory));
                
                msgContainer.scrollTop = msgContainer.scrollHeight;
            }).catch(err => {
                const el = document.getElementById(typingId);
                if(el) el.remove();
                let errorMsg = 'Error al conectar con IA.';
                if(err.response && err.response.data && err.response.data.error) {
                    errorMsg = err.response.data.error;
                }
                msgContainer.insertAdjacentHTML('beforeend', `<div class="p-2 px-3 mb-2 rounded-3 bg-danger text-white align-self-start shadow-sm" style="max-width: 85%; font-size: 0.85rem; word-break: break-word;">${errorMsg}</div>`);
                msgContainer.scrollTop = msgContainer.scrollHeight;
            });
        });

        function openChatArea(userId, userName) {
            document.getElementById('activeChatTitle').innerText = userName;
            document.getElementById('chatTargetUser').value = userId;
            document.getElementById('activeChatArea').classList.remove('d-none');
            document.getElementById('activeChatArea').classList.add('d-flex');
            document.getElementById('fc-contact-list').classList.add('d-none');
            fetchMessages(userId);
        }

        function closeChatArea() {
            document.getElementById('activeChatArea').classList.add('d-none');
            document.getElementById('activeChatArea').classList.remove('d-flex');
            document.getElementById('fc-contact-list').classList.remove('d-none');
        }

        function fetchMessages(userId) {
            let container = document.getElementById('chatMessages');
            container.innerHTML = '<div class="text-center w-100 text-muted small py-3"><div class="spinner-border spinner-border-sm text-primary"></div></div>';
            
            axios.get(`{{ url('api/messages') }}/${userId}`)
                .then(res => {
                    container.innerHTML = '';
                    res.data.forEach(msg => {
                        appendMessageUI(msg, msg.sender_id == authId);
                    });
                    container.scrollTop = container.scrollHeight;
                    
                    // Hide badge optimistically to guarantee UI compliance immediately
                    let badge = document.getElementById('globalChatBadge');
                    if (badge) badge.classList.add('d-none');
                    
                    // Auto-mark as read and refresh it back if there are more
                    axios.post(`{{ url('api/messages/read') }}`, { sender_id: userId }, { headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') } })
                        .then(markRes => {
                            let badge = document.getElementById('globalChatBadge');
                            if (markRes.data.unread_count > 0) {
                                badge.innerText = markRes.data.unread_count;
                                badge.classList.remove('d-none');
                            } else {
                                badge.innerText = '0';
                                badge.classList.add('d-none');
                            }
                        });
                });
        }

        function appendMessageUI(msg, isMe) {
            let alignmentUrl = isMe ? 'justify-content-end' : 'justify-content-start';
            let bgColor = isMe ? 'linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%)' : '#fff';
            let textColor = isMe ? '#fff' : '#222';
            let borderRadius = isMe ? '18px 18px 4px 18px' : '18px 18px 18px 4px';
            let extraClass = isMe ? '' : 'border';

            let d = msg.created_at ? new Date(msg.created_at) : new Date();
            let timeStr = d.getHours().toString().padStart(2, '0') + ':' + d.getMinutes().toString().padStart(2, '0');

            let tickHtml = '';
            if (isMe) {
                if (msg.is_read) {
                    tickHtml = `<i class="bi bi-check2-all text-info ms-1 ds-msg-tick-${msg.id || 'temp'}" style="font-size: 1rem;"></i>`;
                } else {
                    tickHtml = `<i class="bi bi-check2 text-white opacity-75 ms-1 ds-msg-tick-${msg.id || 'temp'}" style="font-size: 1rem;"></i>`;
                }
            }

            let fileHtml = '';
            if (msg.attachment_path) {
                let url = `{{ asset('storage') }}/${msg.attachment_path}`;
                if (url.match(/\.(jpeg|jpg|gif|png|webp|bmp)$/i)) {
                    fileHtml = `<div class="mb-1"><a href="${url}" target="_blank"><img src="${url}" class="img-fluid rounded shadow-sm" style="max-height: 140px; object-fit: cover;"></a></div>`;
                } else {
                    fileHtml = `<div class="mb-1"><a href="${url}" target="_blank" class="btn btn-sm btn-light w-100 text-start text-dark shadow-sm text-truncate d-block" style="border-radius: 8px;"><i class="bi bi-file-earmark-fill text-primary me-1"></i> Archivo Adjunto</a></div>`;
                }
            }

            let msgContent = msg.content ? `<span style="font-size: 0.92rem; word-break: break-word;">${msg.content}</span>` : '';
            
            let html = `
            <div class="d-flex w-100 ${alignmentUrl} mb-1" id="msg-wrapper-${msg.id || 'temp'}">
                <div class="px-2 py-2 shadow-sm ${extraClass} position-relative d-flex flex-column" style="border-radius: ${borderRadius}; background: ${bgColor}; color: ${textColor}; max-width: 85%; min-width: 90px;">
                    ${fileHtml}
                    ${msgContent}
                    <div class="d-flex justify-content-end align-items-end mt-1" style="line-height: 0.9;">
                        <span style="font-size: 0.65rem; opacity: 0.8;">${timeStr}</span>
                        ${tickHtml}
                    </div>
                </div>
            </div>`;
            document.getElementById('chatMessages').insertAdjacentHTML('beforeend', html);
        }

        document.getElementById('chatForm').addEventListener('submit', function(e) {
            e.preventDefault();
            let input = document.getElementById('chatInputMessage');
            let fileInput = document.getElementById('chatAttachment');
            let content = input.value;
            let targetId = document.getElementById('chatTargetUser').value;
            let file = fileInput.files[0];
            
            if((!content.trim() && !file) || !targetId) return;

            input.value = '';
            fileInput.value = '';
            
            let tempMsgId = 'temp-'+Date.now();
            let tempMsg = { id: tempMsgId, content: content, sender_id: authId, is_read: false, attachment_path: file ? 'uploading' : null };
            appendMessageUI(tempMsg, true);
            let container = document.getElementById('chatMessages');
            container.scrollTop = container.scrollHeight;

            // Reset UI for attachment
            let attachLabel = document.querySelector('label[for="chatAttachment"]');
            attachLabel.classList.replace('btn-success', 'btn-light');
            attachLabel.classList.replace('text-white', 'text-secondary');
            document.getElementById('chatInputMessage').placeholder = 'Escribe un mensaje...';

            let formData = new FormData();
            formData.append('receiver_id', targetId);
            if(content.trim()) formData.append('content', content);
            if(file) formData.append('attachment', file);

            axios.post(`{{ url('api/messages') }}`, formData, { 
                headers: { 
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'multipart/form-data'
                } 
            }).then(res => {
                let wrapper = document.getElementById('msg-wrapper-' + tempMsgId);
                let tick = document.querySelector('.ds-msg-tick-' + tempMsgId);
                if (wrapper) wrapper.id = 'msg-wrapper-' + res.data.message.id;
                if (tick) {
                    tick.classList.replace('ds-msg-tick-' + tempMsgId, 'ds-msg-tick-' + res.data.message.id);
                }
            }).catch(err => {
                document.getElementById('msg-wrapper-' + tempMsgId).remove();
                spawnToast('Error de Envío', 'No se pudo adjuntar el archivo. Verifica que no supere los 5MB o sea un formato inválido.', 'exclamation-circle-fill', 'danger');
            });
        });

        document.getElementById('chatAttachment').addEventListener('change', function(e) {
            let label = document.querySelector('label[for="chatAttachment"]');
            if (this.files.length > 0) {
                label.classList.replace('btn-light', 'btn-success');
                label.classList.replace('text-secondary', 'text-white');
                document.getElementById('chatInputMessage').placeholder = 'Archivo listo. Pulsa enviar...';
                document.getElementById('chatInputMessage').focus();
            } else {
                label.classList.replace('btn-success', 'btn-light');
                label.classList.replace('text-white', 'text-secondary');
                document.getElementById('chatInputMessage').placeholder = 'Escribe un mensaje...';
            }
        });

        // Global Toast Dispatcher
        function spawnToast(title, message, icon = 'info-circle-fill', color = 'primary', autohide = true) {
            let id = 'toast_' + Date.now();
            let hideAttr = autohide ? `data-bs-delay="6000"` : `data-bs-autohide="false"`;
            let html = `
            <div id="${id}" class="toast border-0 shadow-lg rounded-4 overflow-hidden mb-2" role="alert" aria-live="assertive" aria-atomic="true" ${hideAttr}>
                <div class="toast-header border-0 bg-${color} text-white">
                    <i class="bi bi-${icon} me-2"></i>
                    <strong class="me-auto">${title}</strong>
                    <button type="button" class="btn-close btn-close-white shadow-none" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body bg-white text-dark" style="font-size: 0.95rem;">
                    ${message}
                </div>
            </div>`;
            document.getElementById('globalToasts').insertAdjacentHTML('beforeend', html);
            let toastEl = document.getElementById(id);
            let t = new bootstrap.Toast(toastEl);
            t.show();
            toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
        }

        // Notification System
        const NOTIF_STORAGE_KEY = 'sysNotifs_' + {{ Auth::id() }};
        let sysNotifs = JSON.parse(localStorage.getItem(NOTIF_STORAGE_KEY) || '[]');
        
        function saveNotification(title, message, icon, color) {
            let timeStr = new Date().getHours().toString().padStart(2,'0') + ':' + new Date().getMinutes().toString().padStart(2,'0');
            sysNotifs.unshift({ time: timeStr, title, message, icon, color, read: false });
            if(sysNotifs.length > 20) sysNotifs.pop(); // Keep only last 20
            localStorage.setItem(NOTIF_STORAGE_KEY, JSON.stringify(sysNotifs));
            renderNotifications();
        }

        function clearSystemNotifications() {
            sysNotifs = [];
            localStorage.setItem(NOTIF_STORAGE_KEY, '[]');
            renderNotifications();
        }

        function renderNotifications() {
            let dmenu = document.getElementById('globalBellMenu');
            let badge = document.getElementById('globalBellBadge');
            
            // Clear existing except header
            Array.from(dmenu.children).forEach(el => {
                if(!el.classList.contains('dropdown-header')) el.remove();
            });

            if (sysNotifs.length === 0) {
                badge.classList.add('d-none');
                badge.innerText = '0';
                dmenu.insertAdjacentHTML('beforeend', `<li id="noNotifsItem"><span class="dropdown-item text-muted small py-4 text-center"><i class="bi bi-check2-all fs-4 d-block mb-2"></i>Todo al día</span></li>`);
                return;
            }

            let unread = sysNotifs.filter(n => !n.read).length;
            if (unread > 0) {
                badge.classList.remove('d-none', 'bg-secondary');
                badge.classList.add('bg-danger');
                badge.innerText = unread;
            } else {
                badge.classList.add('d-none');
            }

            sysNotifs.forEach(n => {
                let html = `<li>
                    <div class="dropdown-item py-2 d-flex gap-3 align-items-center" style="white-space: normal;">
                        <div class="bg-${n.color} bg-opacity-10 text-${n.color} p-2 rounded-circle"><i class="bi bi-${n.icon}"></i></div>
                        <div class="d-flex flex-column w-100">
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted fw-bold" style="font-size: 0.75rem;">${n.title}</small>
                                <small class="text-muted" style="font-size: 0.65rem;">${n.time}</small>
                            </div>
                            <span style="font-size: 0.85rem;">${n.message}</span>
                        </div>
                    </div>
                </li>
                <li><hr class="dropdown-divider m-0"></li>`;
                dmenu.insertAdjacentHTML('beforeend', html);
            });
        }
        
        // Mark as read when opening dropdown
        document.querySelector('.bi-bell-fill.omnibar-icon').addEventListener('click', function() {
            if(sysNotifs.length > 0) {
                sysNotifs.forEach(n => n.read = true);
                localStorage.setItem(NOTIF_STORAGE_KEY, JSON.stringify(sysNotifs));
                document.getElementById('globalBellBadge').classList.add('d-none');
            }
        });

        document.addEventListener('DOMContentLoaded', () => {
            renderNotifications();
            if(window.Echo) {
                // 1. Direct Messages Listener
                window.Echo.private(`chat.${authId}`)
                    .listen('MessageSent', (e) => {
                        // Play ICQ Sound
                        let audio = new Audio('{{ asset("sounds/icq.mp3") }}');
                        audio.play().catch(err => console.log('Audio autoplay prevented'));

                        let widget = document.getElementById('floatingChatWidget');
                        let isChatOpen = !widget.classList.contains('d-none');
                        let isChatWithUser = !document.getElementById('activeChatArea').classList.contains('d-none') && document.getElementById('chatTargetUser').value == e.message.sender_id;

                        if (isChatOpen && isChatWithUser) {
                            appendMessageUI(e.message, false);
                            let container = document.getElementById('chatMessages');
                            container.scrollTop = container.scrollHeight;
                            
                            // Auto mark as read since chat is open
                            axios.post(`{{ url('api/messages/read') }}`, { sender_id: e.message.sender_id }, { headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') } })
                                .then(markRes => {
                                    let badge = document.getElementById('globalChatBadge');
                                    if(markRes.data.unread_count > 0) {
                                        badge.innerText = markRes.data.unread_count;
                                        badge.classList.remove('d-none');
                                    } else {
                                        badge.innerText = '0';
                                        badge.classList.add('d-none');
                                    }
                                });
                        } else {
                            // Show Badge & Toast
                            let b = document.getElementById('globalChatBadge');
                            b.innerText = parseInt(b.innerText || 0) + 1;
                            b.classList.remove('d-none');
                            spawnToast('Nuevo Mensaje', `<b>${e.message.sender.name}</b>: ${e.message.content}`, 'chat-dots-fill', 'primary');
                        }
                    })
                    .listen('MessageRead', (e) => {
                        // Change single ticks to double blue ticks for this reader
                        // Note: MessageRead broadcasts reader_id and sender_id. Here authId is sender_id.
                        if (!document.getElementById('activeChatArea').classList.contains('d-none') && document.getElementById('chatTargetUser').value == e.reader_id) {
                            document.querySelectorAll('.bi-check2.text-white').forEach(tick => {
                                tick.classList.replace('bi-check2', 'bi-check2-all');
                                tick.classList.replace('text-white', 'text-info');
                                tick.classList.remove('opacity-75');
                            });
                        }
                    });

                // 2. Presence Tracking (Online Green Dots)
                window.Echo.join(`messenger`)
                    .here((users) => {
                        users.forEach(user => {
                            let dot = document.getElementById(`online-dot-${user.id}`);
                            if(dot) dot.classList.replace('bg-secondary', 'bg-success');
                        });
                    })
                    .joining((user) => {
                        let dot = document.getElementById(`online-dot-${user.id}`);
                        if(dot) dot.classList.replace('bg-secondary', 'bg-success');
                        spawnToast('Actualización de Red', `<b>${user.name}</b> se ha conectado al sistema.`, 'person-check-fill', 'success');
                    })
                    .leaving((user) => {
                        let dot = document.getElementById(`online-dot-${user.id}`);
                        if(dot) dot.classList.replace('bg-success', 'bg-secondary');
                    });
                    
                // 3. System Receptions Listener
                window.Echo.private(`system`)
                    .listen('PatientArrived', (e) => {
                        if (e.sender_id == authId) return; // Prevent sender from seeing their own notification
                        let msg = `El paciente <b>${e.patient_name}</b> acaba de ingresar a Recepción.`;
                        spawnToast('Nuevo Ingreso', msg, 'door-open-fill', 'warning text-dark', false);
                        saveNotification('Nuevo Ingreso', msg, 'door-open-fill', 'warning');
                        
                        let audio = new Audio('{{ asset("sounds/icq.mp3") }}');
                        audio.play().catch(err => console.log('Audio autoplay prevented'));
                    })
                    .listen('PatientEnteredConsultorio', (e) => {
                        if (e.sender_id == authId) return; // Prevent sender from seeing their own notification
                        let msg = `El paciente <b>${e.patient_name}</b> ha ingresado al Consultorio${e.doctor_name ? ' de ' + e.doctor_name : ''}.`;
                        spawnToast('Atención Médica', msg, 'person-badge-fill', 'info', false);
                        saveNotification('Atención Médica', msg, 'person-badge-fill', 'info');
                        
                        let audio = new Audio('{{ asset("sounds/icq.mp3") }}');
                        audio.play().catch(err => console.log('Audio autoplay prevented'));
                    });
                    
                // 3.5. Doctor Assignments Alert Listener
                window.Echo.private(`doctor.alerts.${authId}`)
                    .listen('DoctorAssignedAlert', (e) => {
                        // Reproducir sonido especial 
                        let audio = new Audio('{{ asset("sounds/icq.mp3") }}');
                        audio.play().catch(err => console.log('Audio autoplay prevented'));

                        let msg = `El paciente <b>${e.patientName}</b> está aguardando en <b>${e.eventType}</b>.`;
                        spawnToast('¡Paciente Asignado!', msg, 'heart-pulse-fill', 'danger', false);
                        saveNotification('Asignación', msg, 'heart-pulse-fill', 'danger');
                    });
            }

            // 4. Time-Based Alerts Polling
            let notifiedDilationIds = [];
            setInterval(() => {
                axios.get(`{{ url('api/notifications/poll') }}?exclude=${notifiedDilationIds.join(',')}`)
                    .then(res => {
                        if(res.data && res.data.length > 0) {
                            res.data.forEach(alert => {
                                spawnToast('Demora en Dilatación', `El paciente <b>${alert.patient_name}</b> lleva ${alert.minutes} minutos en sala de dilatación.`, 'exclamation-circle-fill', 'danger');
                                notifiedDilationIds.push(alert.id);
                            });
                        }
                    }).catch(err => { /* quiet fail */ });
            }, 60000); // 1 minute
        });
        
        // Theme Engine
        function toggleThemeGlobal() {
            let isDark = document.body.classList.contains('theme-dark');
            if (isDark) {
                document.body.classList.remove('theme-dark');
                localStorage.setItem('theme', 'light');
                document.querySelector('#themeToggleBtn i').className = 'bi bi-moon-stars-fill';
            } else {
                document.body.classList.add('theme-dark');
                localStorage.setItem('theme', 'dark');
                document.querySelector('#themeToggleBtn i').className = 'bi bi-sun-fill text-warning';
            }
        }
        
        // Setup initial icon state
        if (localStorage.getItem('theme') === 'dark') {
            document.body.classList.add('theme-dark');
            let tBtn = document.querySelector('#themeToggleBtn i');
            if(tBtn) tBtn.className = 'bi bi-sun-fill text-warning';
        }
    </script>
</body>
</html>
