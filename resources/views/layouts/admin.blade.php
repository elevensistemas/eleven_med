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

    <style>
        /* Modern Admin Layout Override */
        body { background-color: #f7f9fc; }
        .sidebar {
            width: 280px;
            background: #ffffff;
            border-right: 1px solid rgba(0,0,0,0.05);
            position: fixed;
            height: 100vh;
            top: 0;
            left: 0;
            z-index: 1000;
            transition: all 0.3s ease;
            box-shadow: 4px 0 24px rgba(0,0,0,0.02);
        }
        .main-content {
            margin-left: 280px;
            padding: 2rem;
            min-height: 100vh;
            background-color: #f4f7f6;
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
            color: #555;
            padding: 1rem 1.5rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all 0.2s ease;
            border-radius: 0 12px 12px 0;
            margin: 4px 1rem 4px 0;
        }
        .nav-sidebar .nav-link i { font-size: 1.2rem; opacity: 0.7; }
        .nav-sidebar .nav-link:hover, .nav-sidebar .nav-link.active {
            color: var(--primary-color);
            background: rgba(94, 106, 210, 0.08);
        }
        .nav-sidebar .nav-link.active i { color: var(--primary-color); opacity: 1; }
        .topbar {
            background: #fff;
            padding: 1rem 2rem;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.03);
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid rgba(0,0,0,0.02);
            position: sticky;
            top: 2rem;
            z-index: 100;
        }
        .omnibar-search {
            display: flex;
            background: rgba(94, 106, 210, 0.05);
            border-radius: 12px;
            overflow: hidden;
            width: 400px;
            border: 1px solid rgba(94, 106, 210, 0.1);
            transition: all 0.3s ease;
        }
        .omnibar-search:focus-within {
            background: #fff;
            border-color: rgba(94, 106, 210, 0.4);
            box-shadow: 0 0 0 4px rgba(94, 106, 210, 0.1);
        }
        .omnibar-search input {
            border: none;
            background: transparent;
            padding: 0.7rem 1.2rem;
            width: 100%;
            outline: none;
            color: #333;
            font-weight: 500;
        }
        .omnibar-search input::placeholder {
            color: #aaa;
        }
        .omnibar-search button {
            background: transparent;
            color: var(--primary-color);
            border: none;
            padding: 0 1.2rem;
            transition: all 0.2s;
            font-size: 1.1rem;
        }
        .omnibar-search button:hover {
            transform: scale(1.1);
        }
        .omnibar-icon {
            color: var(--primary-color);
            background: rgba(94, 106, 210, 0.06);
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
            border: 1px solid rgba(94, 106, 210, 0.05);
        }
        .omnibar-icon:hover {
            background: var(--primary-color);
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(94, 106, 210, 0.2);
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
            border: none;
            border-radius: 16px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.04);
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
            padding: 2.2rem 1rem 1.8rem 1rem;
            border-bottom: 1px solid rgba(0,0,0,0.03);
            text-align: center;
        }
        .brand-logo-main {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 1.6rem;
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

        /* Dark Mode Configuration globally */
        body.theme-dark {
            background-color: #121212 !important;
            color: #e0e0e0 !important;
        }
        body.theme-dark .main-content {
            background-color: #1a1a1a !important;
        }
        body.theme-dark .sidebar, 
        body.theme-dark .topbar, 
        body.theme-dark .bg-white, 
        body.theme-dark .modern-card, 
        body.theme-dark .toast,
        body.theme-dark .modal-content,
        body.theme-dark #floatingChatWidget,
        body.theme-dark .offcanvas {
            background-color: #242424 !important;
            border-color: rgba(255,255,255,0.05) !important;
            color: #e0e0e0 !important;
        }
        body.theme-dark .text-dark {
            color: #f8f9fa !important;
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
            background-color: #2a2a2a !important;
            color: #f8f9fa !important;
            border-color: #444 !important;
        }
        body.theme-dark .dropdown-menu {
            background-color: #2a2a2a !important;
            border-color: #444 !important;
        }
        body.theme-dark .dropdown-item {
            color: #e0e0e0 !important;
            background-color: transparent !important;
        }
        body.theme-dark .dropdown-item:hover {
            background-color: #383838 !important;
            color: #fff !important;
        }
        body.theme-dark .list-group-item {
            background-color: transparent !important;
            border-color: #444 !important;
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
    </style>
    
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
                <i class="bi bi-eye-fill" style="font-size: 2.2rem; filter: drop-shadow(0 0 6px rgba(181, 164, 255, 0.6));"></i>
                CORTALEZZI
            </div>
            <div class="brand-subtitle-vision">Vision</div>
        </div>
        <div class="nav flex-column nav-sidebar mt-4 flex-grow-1">
            <a href="{{ url('/home') }}" class="nav-link {{ request()->is('home') ? 'active' : '' }}">
                <i class="bi bi-grid"></i> Dashboard
            </a>
            <a href="{{ route('users.index') }}" class="nav-link {{ request()->is('users*') ? 'active' : '' }}">
                <i class="bi bi-people"></i> Usuarios & Roles
            </a>
            <a href="{{ route('patients.index') }}" class="nav-link {{ request()->is('patients*') ? 'active' : '' }}">
                <i class="bi bi-person-vcard"></i> Pacientes
            </a>
            <a href="{{ route('console.index') }}" class="nav-link {{ request()->is('console*') ? 'active' : '' }} fw-bold text-primary">
                <i class="bi bi-display"></i> Consola Pacientes <span class="badge bg-danger rounded-pill ms-2 pulse-badge" style="font-size: 0.6rem;">LIVE</span>
            </a>
            <a href="{{ route('agenda.index') }}" class="nav-link {{ request()->is('agenda') ? 'active' : '' }}">
                <i class="bi bi-calendar-check"></i> Agenda Médica
            </a>
            <a href="{{ route('agenda.settings') }}" class="nav-link {{ request()->is('agenda/settings') ? 'active' : '' }}" style="padding-left: 2.5rem; font-size: 0.9em;">
                <i class="bi bi-gear-fill"></i> Crear Agenda
            </a>
            <a href="{{ route('config.index') }}" class="nav-link {{ request()->is('configuracion*') ? 'active' : '' }}">
                <i class="bi bi-sliders"></i> Módulo Configuración
            </a>
            <a href="#messengerDrawer" data-bs-toggle="offcanvas" class="nav-link cursor-pointer">
                <i class="bi bi-chat-dots"></i> Chat Interno <span class="badge bg-primary ms-auto rounded-pill d-none" id="globalChatBadge">0</span>
            </a>
        </div>
        <div class="p-4 mt-auto">
            <div class="card bg-light border-0 rounded-4 p-3 text-center">
                <i class="bi bi-shield-check fs-4 text-success mb-2"></i>
                <small class="text-muted d-block fw-bold">Sistema Seguro</small>
                <small class="text-muted" style="font-size: 0.75rem;">Oftalmología Core v2.0</small>
            </div>
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
            <div class="d-flex align-items-center gap-3">
                
                <!-- Date/Time Badge (Elegante) -->
                <div id="omnibar-clock" class="d-none d-lg-flex" style="background: rgba(94, 106, 210, 0.05); color: var(--primary-color); padding: 0.5rem 1.2rem; border-radius: 12px; font-weight: 600; font-size: 0.9rem; align-items: center; gap: 0.8rem; border: 1px solid rgba(94, 106, 210, 0.08);">
                    <i class="bi bi-calendar3"></i> <span id="omnibar-date">--</span>
                    <span class="text-muted opacity-50">|</span>
                    <i class="bi bi-clock"></i> <span id="omnibar-time">--</span>
                </div>

                <!-- Dropdown: Últimos Buscados -->
                <div class="dropdown">
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
                <div class="dropdown">
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
                <div class="dropdown">
                    <i class="bi bi-bell-fill omnibar-icon" data-bs-toggle="dropdown" aria-expanded="false" title="Notificaciones">
                        <span class="omnibar-badge bg-secondary">0</span>
                    </i>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3 mt-3 p-2" style="width: 320px;">
                        <li class="dropdown-header fw-bold text-primary">Notificaciones del Sistema</li>
                        <li><span class="dropdown-item text-muted small py-4 text-center"><i class="bi bi-check2-all fs-4 d-block mb-2"></i>Todo al día</span></li>
                    </ul>
                </div>

                <!-- Theme Toggle -->
                <div class="ms-2">
                    <button class="btn btn-light rounded-circle shadow-sm omnibar-icon p-0 border-0" id="themeToggleBtn" title="Cambiar Tema" onclick="toggleThemeGlobal()">
                        <i class="bi bi-moon-stars-fill"></i>
                    </button>
                </div>

                <div class="dropdown user-dropdown ms-3 pl-3" style="border-left: 1px solid rgba(150,150,150,0.3);">
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
    <div id="floatingChatWidget" class="position-fixed shadow-lg rounded-4 overflow-hidden d-none flex-column" style="bottom: 85px; right: 20px; width: 350px; height: 500px; max-height: 80vh; background: #fff; z-index: 1050; border: 1px solid rgba(0,0,0,0.1);">
        
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
                    <input type="text" class="form-control rounded-pill border-0 bg-light px-3 shadow-none" id="chatInputMessage" placeholder="Escribe un mensaje..." required autocomplete="off" style="font-size: 0.95rem;">
                    <button type="submit" class="btn btn-primary rounded-circle shadow-sm d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; min-width: 38px;"><i class="bi bi-send-fill"></i></button>
                </form>
            </div>
        </div>
    </div>

    <!-- Bubble Toggle Button -->
    <button id="floatingChatBtn" class="position-fixed shadow-lg rounded-circle btn btn-primary border-0 p-0 d-flex align-items-center justify-content-center" style="bottom: 20px; right: 20px; width: 60px; height: 60px; z-index: 1050; background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);" onclick="toggleFloatingChat()">
        <i class="bi bi-chat-fill text-white fs-4 mt-1"></i>
        <span id="globalChatBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none border border-white" style="font-size: 0.75rem;">0</span>
    </button>
    
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script>
        const authId = {{ Auth::id() }};
        
        function toggleFloatingChat() {
            const widget = document.getElementById('floatingChatWidget');
            if (widget.classList.contains('d-none')) {
                widget.classList.remove('d-none');
                widget.classList.add('d-flex');
                document.getElementById('globalChatBadge').classList.add('d-none');
                document.getElementById('globalChatBadge').innerText = '0';
            } else {
                widget.classList.add('d-none');
                widget.classList.remove('d-flex');
            }
        }

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
                });
        }

        function appendMessageUI(msg, isMe) {
            let alignmentUrl = isMe ? 'justify-content-end' : 'justify-content-start';
            let bgColor = isMe ? 'linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%)' : '#fff';
            let textColor = isMe ? '#fff' : '#222';
            let borderRadius = isMe ? '18px 18px 4px 18px' : '18px 18px 18px 4px';
            let extraClass = isMe ? '' : 'border';
            
            let html = `
            <div class="d-flex w-100 ${alignmentUrl} mb-1">
                <div class="px-3 py-2 shadow-sm ${extraClass}" style="border-radius: ${borderRadius}; background: ${bgColor}; color: ${textColor}; max-width: 85%;">
                    <span style="font-size: 0.9rem;">${msg.content}</span>
                </div>
            </div>`;
            document.getElementById('chatMessages').insertAdjacentHTML('beforeend', html);
        }

        document.getElementById('chatForm').addEventListener('submit', function(e) {
            e.preventDefault();
            let input = document.getElementById('chatInputMessage');
            let content = input.value;
            let targetId = document.getElementById('chatTargetUser').value;
            if(!content.trim() || !targetId) return;

            input.value = '';
            
            let tempMsg = { content: content, sender_id: authId };
            appendMessageUI(tempMsg, true);
            let container = document.getElementById('chatMessages');
            container.scrollTop = container.scrollHeight;

            axios.post(`{{ url('api/messages') }}`, {
                receiver_id: targetId,
                content: content
            }, { headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') } });
        });

        // Global Toast Dispatcher
        function spawnToast(title, message, icon = 'info-circle-fill', color = 'primary') {
            let id = 'toast_' + Date.now();
            let html = `
            <div id="${id}" class="toast border-0 shadow-lg rounded-4 overflow-hidden mb-2" role="alert" aria-live="assertive" aria-atomic="true">
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
            let t = new bootstrap.Toast(toastEl, { delay: 6000 });
            t.show();
            toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
        }

        document.addEventListener('DOMContentLoaded', () => {
            if(window.Echo) {
                // 1. Direct Messages Listener
                window.Echo.private(`chat.${authId}`)
                    .listen('MessageSent', (e) => {
                        let widget = document.getElementById('floatingChatWidget');
                        let isChatOpen = !widget.classList.contains('d-none');
                        let isChatWithUser = !document.getElementById('activeChatArea').classList.contains('d-none') && document.getElementById('chatTargetUser').value == e.message.sender_id;

                        if (isChatOpen && isChatWithUser) {
                            appendMessageUI(e.message, false);
                            let container = document.getElementById('chatMessages');
                            container.scrollTop = container.scrollHeight;
                        } else {
                            // Show Badge & Toast
                            let b = document.getElementById('globalChatBadge');
                            b.innerText = parseInt(b.innerText || 0) + 1;
                            b.classList.remove('d-none');
                            spawnToast('Nuevo Mensaje', `<b>${e.message.sender.name}</b>: ${e.message.content}`, 'chat-dots-fill', 'primary');
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
                        spawnToast('Nuevo Ingreso', `El paciente <b>${e.patient_name}</b> acaba de ingresar a Recepción.`, 'door-open-fill', 'warning text-dark');
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
