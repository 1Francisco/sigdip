<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>SIGDIP - @yield('title', 'Sistema de Gestión')</title>
    <link rel="icon" type="image/png" href="{{ asset('icon_png.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('icon_png.png') }}">
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#2563eb">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary: #2563eb;
            --primary-soft: #eff6ff;
            --secondary: #64748b;
            --bg-body: #f8fafc;
            --sidebar-width: 260px;
            --bottom-nav-height: 65px;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-body);
            color: #1e293b;
            padding-top: env(safe-area-inset-top, 0px);
            padding-bottom: env(safe-area-inset-bottom, 0px);
        }

        /* Sidebar Style */
        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            background: white;
            border-right: 1px solid #e2e8f0;
            z-index: 1050;
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .sidebar-brand {
            padding: calc(2rem + env(safe-area-inset-top, 0px)) 1.5rem 2rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 700;
            font-size: 1.25rem;
            color: var(--primary);
        }

        .nav-link {
            padding: 0.8rem 1.5rem;
            color: var(--secondary);
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all 0.2s;
            margin: 0.2rem 1rem;
            border-radius: 12px;
        }

        .nav-link:hover {
            background-color: var(--primary-soft);
            color: var(--primary);
        }

        .nav-link.active {
            background-color: var(--primary);
            color: white;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
        }

        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 2rem;
            min-height: 100vh;
            transition: all 0.3s;
        }

        /* Mobile Header */
        .mobile-header {
            display: none;
            background: white;
            padding: calc(0.8rem + env(safe-area-inset-top, 0px)) 1rem 0.8rem 1rem;
            border-bottom: 1px solid #e2e8f0;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        /* Bottom Navigation */
        .bottom-nav {
            display: none;
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: calc(var(--bottom-nav-height) + env(safe-area-inset-bottom, 0px));
            background: white;
            border-top: 1px solid #e2e8f0;
            z-index: 1040;
            justify-content: space-around;
            align-items: center;
            padding: 0 10px env(safe-area-inset-bottom, 0px) 10px;
        }

        .bottom-nav-link {
            display: flex;
            flex-direction: column;
            align-items: center;
            color: var(--secondary);
            text-decoration: none;
            font-size: 0.7rem;
            font-weight: 500;
        }

        .bottom-nav-link i {
            font-size: 1.25rem;
            margin-bottom: 2px;
        }

        .bottom-nav-link.active {
            color: var(--primary);
        }

        /* Premium Cards */
        .card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            transition: transform 0.2s;
        }

        .card-header {
            background: transparent;
            border-bottom: 1px solid #f1f5f9;
            padding: 1.2rem;
            font-weight: 600;
        }

        .btn-primary {
            background-color: var(--primary);
            border: none;
            padding: 0.6rem 1.5rem;
            border-radius: 10px;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.15);
        }

        .btn-primary:hover {
            background-color: #1d4ed8;
            transform: translateY(-1px);
        }

        /* Overlay */
        .sidebar-overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(15, 23, 42, 0.45);
            z-index: 1080;
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
        }

        /* Responsive */
        @media (max-width: 991.98px) {
            .sidebar {
                width: 290px;
                max-width: 80%;
                height: 100% !important;
                height: 100dvh !important;
                position: fixed;
                top: 0;
                left: 0;
                bottom: 0;
                right: auto;
                border-right: 1.5px solid #e2e8f0;
                border-bottom: none;
                border-top-right-radius: 28px;
                border-bottom-right-radius: 28px;
                border-top-left-radius: 0;
                border-bottom-left-radius: 0;
                box-shadow: 15px 0 35px rgba(0, 0, 0, 0.12);
                transform: translateX(-100%);
                overflow-y: auto;
                padding-bottom: 2rem;
                z-index: 1100;
            }
            .sidebar.active {
                transform: translateX(0);
            }
            .sidebar-brand {
                padding: calc(1.2rem + env(safe-area-inset-top, 0px)) 1.5rem 1.2rem 1.5rem;
                border-bottom: 1px solid #f1f5f9;
                margin-bottom: 0.8rem;
            }
            .main-content { margin-left: 0; padding: 1rem; padding-bottom: 90px; }
            .mobile-header { display: flex; align-items: center; gap: 15px; }
            .desktop-header { display: none !important; }
            .bottom-nav { display: flex; }
            .sidebar-overlay.active { opacity: 1; pointer-events: auto; }
            
            /* Botones táctiles */
            .btn { 
                padding: 0.8rem 1.2rem; 
                font-weight: 600;
                border-radius: 12px;
            }

            /* Botón de cerrar de la barra lateral (Premium circular) */
            .sidebar-brand .btn {
                padding: 0 !important;
                width: 38px !important;
                height: 38px !important;
                display: flex !important;
                align-items: center;
                justify-content: center;
                border-radius: 50% !important;
                background-color: #f1f5f9 !important;
                border: none !important;
                color: #64748b !important;
                transition: all 0.2s ease;
            }
            .sidebar-brand .btn:active {
                background-color: #e2e8f0 !important;
                transform: scale(0.95);
            }
            /* Cabeceras de tarjetas responsivas (Evita desbordamiento de botones) */
            .card-header.d-flex.justify-content-between.align-items-center {
                flex-direction: column !important;
                align-items: stretch !important;
                gap: 12px !important;
                padding: 1.25rem 1rem !important;
            }
            .card-header.d-flex.justify-content-between.align-items-center > h5 {
                text-align: center;
                width: 100%;
            }
            .card-header.d-flex.justify-content-between.align-items-center > .d-flex,
            .card-header.d-flex.justify-content-between.align-items-center > div {
                flex-direction: column !important;
                gap: 8px !important;
                width: 100% !important;
            }
            .card-header.d-flex.justify-content-between.align-items-center .btn,
            .card-header.d-flex.justify-content-between.align-items-center a.btn {
                width: 100% !important;
                margin: 0 !important;
                display: flex !important;
                align-items: center;
                justify-content: center;
                gap: 8px;
            }

            /* Transformar tablas en tarjetas para móvil */
            .table-mobile-cards {
                border: none !important;
            }
            .table-mobile-cards thead {
                display: none;
            }
            .table-mobile-cards tr {
                display: block;
                margin-bottom: 1.25rem;
                border: 1px solid #e2e8f0;
                border-radius: 20px;
                background: white;
                padding: 1.25rem;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
                position: relative;
                overflow: hidden;
            }
            .table-mobile-cards tr::before {
                content: "";
                position: absolute;
                left: 0; top: 0; bottom: 0;
                width: 5px;
                background: var(--primary);
            }
            .table-mobile-cards td {
                display: flex;
                flex-direction: column;
                align-items: stretch;
                border: none !important;
                padding: 0.6rem 0 !important;
                text-align: left;
                gap: 6px;
            }
            .table-mobile-cards td::before {
                content: attr(data-label);
                font-weight: 700;
                text-align: left;
                color: var(--secondary);
                font-size: 0.75rem;
                text-transform: uppercase;
                letter-spacing: 0.025em;
                display: block;
            }
            /* Excepción para checkbox "Fierro" para que se vea alineado a la derecha en la misma línea */
            .table-mobile-cards td[data-label="Fierro"] {
                flex-direction: row;
                justify-content: space-between;
                align-items: center;
            }
            .table-mobile-cards td[data-label="Fierro"]::before {
                display: inline-block;
                margin-bottom: 0;
            }
            .table-mobile-cards td[data-label="Fierro"] input {
                width: auto !important;
            }
            /* Asegurar que los componentes hijos dentro de las celdas ocupen todo el ancho */
            .table-mobile-cards td > * {
                width: 100%;
            }
            .table-mobile-cards td:last-child {
                border-top: 1px solid #f1f5f9 !important;
                margin-top: 0.75rem;
                padding-top: 1rem !important;
                justify-content: center;
                background: #f8fafc;
                margin-left: -1.25rem;
                margin-right: -1.25rem;
                margin-bottom: -1.25rem;
                border-radius: 0 0 20px 20px;
                flex-direction: row;
                align-items: center;
            }
            .table-mobile-cards td:last-child > * {
                width: auto;
            }
            
            /* Ajustes de inputs en móvil */
            .form-control, .form-select {
                padding: 0.75rem 1rem;
                font-size: 1rem; /* Evita zoom automático en iOS */
                border-radius: 12px;
            }
            
            .btn {
                padding: 0.8rem 1.5rem;
                border-radius: 14px;
            }

            /* Sticky Action Bar for Mobile */
            .sticky-mobile-actions {
                position: fixed;
                bottom: var(--bottom-nav-height);
                left: 0; right: 0;
                background: rgba(255, 255, 255, 0.9);
                backdrop-filter: blur(10px);
                padding: 1rem;
                border-top: 1px solid #e2e8f0;
                display: flex;
                gap: 10px;
                z-index: 1030;
                box-shadow: 0 -4px 12px rgba(0,0,0,0.05);
            }
        }

        /* Utilidades para móviles */
        .touch-scroll {
            -webkit-overflow-scrolling: touch;
            overflow-x: auto;
        }

        /* Premium Global Form Controls */
        .form-control, .form-select {
            padding: 0.6rem 1rem;
            font-size: 0.95rem;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            background-color: #ffffff;
            color: #1e293b;
            transition: all 0.2s ease-in-out;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
            background-color: #ffffff;
        }

        .form-select {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e") !important;
            background-repeat: no-repeat !important;
            background-position: right 1rem center !important;
            background-size: 1.25rem !important;
            padding-right: 2.5rem !important;
            -webkit-appearance: none !important;
            -moz-appearance: none !important;
            appearance: none !important;
        }
    </style>
    @yield('styles')
</head>
<body>
    <!-- Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

    <!-- Mobile Header -->
    <header class="mobile-header shadow-sm">
        @hasSection('back_url')
            <a href="@yield('back_url')" class="btn btn-light rounded-circle p-2">
                <i class="bi bi-arrow-left fs-4"></i>
            </a>
        @else
            <button class="btn btn-light rounded-circle" onclick="toggleSidebar()">
                <i class="bi bi-list fs-4"></i>
            </button>
        @endif
        
        <div class="fw-bold text-primary flex-grow-1 text-center">
            <img src="{{ asset('icon_png.png') }}" alt="SIGDIP" style="width: 20px; height: 20px; object-fit: contain; vertical-align: -3px; margin-right: 6px;"> SIGDIP
        </div>
        <div class="bg-primary-soft text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 35px; height: 35px;">
            <i class="bi bi-person"></i>
        </div>
    </header>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <img src="{{ asset('icon_png.png') }}" alt="SIGDIP" style="width: 22px; height: 22px; object-fit: contain;">
            <span>SIGDIP</span>
            <button class="btn btn-light btn-sm d-lg-none ms-auto" onclick="toggleSidebar()">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <nav class="nav flex-column">
            <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
                <i class="bi bi-grid-1x2-fill"></i> Dashboard
            </a>
            <a class="nav-link {{ request()->routeIs('productores.*') ? 'active' : '' }}" href="{{ route('productores.index') }}">
                <i class="bi bi-people"></i> Productores
            </a>
            <a class="nav-link {{ request()->routeIs('predios.*') ? 'active' : '' }}" href="{{ route('predios.index') }}">
                <i class="bi bi-house-door"></i> Predios
            </a>
            <a class="nav-link {{ request()->is('inspecciones*') ? 'active' : '' }}" href="{{ route('inspecciones.index') }}">
                <i class="bi bi-clipboard-check"></i> Inspecciones
            </a>
            <a class="nav-link {{ request()->routeIs('visitas.*') ? 'active' : '' }}" href="{{ route('visitas.index') }}">
                <i class="bi bi-calendar-event"></i> Agenda / Visitas
            </a>
            @role('Administrador')
            <a class="nav-link {{ request()->routeIs('usuarios.*') ? 'active' : '' }}" href="{{ route('usuarios.index') }}">
                <i class="bi bi-person-badge"></i> Médicos
            </a>
            <a class="nav-link {{ request()->routeIs('import.excel.*') ? 'active' : '' }}" href="{{ route('import.excel.index') }}">
                <i class="bi bi-file-earmark-arrow-up"></i> Importar Excel
            </a>
            @endrole
            @role('Administrador')
            <hr class="mx-3 text-slate-200">
            <a class="nav-link" href="{{ route('reportes.excel') }}">
                <i class="bi bi-file-earmark-excel"></i> Sábana Excel
            </a>
            @endrole

            <hr class="mx-3 text-slate-200">
            <a class="nav-link text-primary fw-semibold" href="{{ route('descargar.apk') }}">
                <i class="bi bi-phone"></i> Descargar App (APK)
            </a>


             <form method="POST" action="{{ route('logout') }}" class="mt-auto">
                @csrf
                <button type="submit" class="nav-link text-danger w-100 border-0 text-start bg-transparent" style="cursor: pointer;">
                    <i class="bi bi-box-arrow-left"></i> Salir
                </button>
            </form>
        </nav>
    </div>

    <!-- Bottom Nav -->
    <div class="bottom-nav">
        <a href="{{ route('admin.dashboard') }}" class="bottom-nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
            <i class="bi bi-grid-1x2"></i>
            <span>Inicio</span>
        </a>
        <a href="{{ route('inspecciones.index') }}" class="bottom-nav-link {{ request()->is('inspecciones*') ? 'active' : '' }}">
            <i class="bi bi-clipboard-check"></i>
            <span>Dictámenes</span>
        </a>
        <a href="{{ route('visitas.index') }}" class="bottom-nav-link {{ request()->routeIs('visitas.*') ? 'active' : '' }}">
            <i class="bi bi-calendar-event"></i>
            <span>Agenda</span>
        </a>
        <a href="{{ route('productores.index') }}" class="bottom-nav-link {{ request()->routeIs('productores.*') ? 'active' : '' }}">
            <i class="bi bi-people"></i>
            <span>Más</span>
        </a>
    </div>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Desktop Header -->
        <header class="desktop-header d-flex justify-content-between align-items-center mb-5">
            <div class="d-flex align-items-center gap-3">
                @hasSection('back_url')
                <a href="@yield('back_url')" class="btn btn-white border shadow-sm rounded-circle d-flex align-items-center justify-content-center" style="width: 45px; height: 45px; background: white;">
                    <i class="bi bi-arrow-left fs-4"></i>
                </a>
                @endif
                <div>
                    <h1 class="h3 fw-bold mb-0">@yield('header_title')</h1>
                    <p class="text-secondary mb-0">@yield('header_subtitle', 'Bienvenido al sistema')</p>
                </div>
            </div>
            <div class="d-flex align-items-center gap-3">
                <div class="d-flex align-items-center gap-2 bg-white p-2 rounded-pill shadow-sm border px-3">
                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                        <i class="bi bi-person"></i>
                    </div>
                    <span class="fw-semibold small">{{ auth()->user()->name ?? 'Administrador' }}</span>
                </div>
            </div>
        </header>

        <!-- Page Specific Header (Mobile) -->
        <div class="d-lg-none mb-4">
            <h2 class="h4 fw-bold mb-1">@yield('header_title')</h2>
            <p class="text-secondary small mb-0">@yield('header_subtitle')</p>
        </div>

        @if($errors->any())
        <div class="alert alert-danger border-0 shadow-sm rounded-4 mb-4">
            <div class="d-flex align-items-center gap-3 mb-2">
                <i class="bi bi-exclamation-octagon-fill fs-5"></i>
                <strong>Por favor corrige los siguientes errores:</strong>
            </div>
            <ul class="mb-0 small">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-4 mb-4 d-flex align-items-center gap-3">
            <i class="bi bi-check-circle-fill"></i>
            <div class="small">{{ session('success') }}</div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
        @endif

        @if(session('error'))
        <div class="alert alert-danger border-0 shadow-sm rounded-4 mb-4 d-flex align-items-center gap-3">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <div class="small">{{ session('error') }}</div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
        @endif

        @yield('content')
    </main>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
            document.getElementById('sidebarOverlay').classList.toggle('active');
        }
    </script>
    @yield('scripts')

    <!-- Capacitor Mobile Bridge Integration -->
    <script>
        (function() {
            const isCapacitor = window.hasOwnProperty('Capacitor') || 
                                navigator.userAgent.includes('Capacitor') || 
                                !!window.androidBridge;
            
            if (isCapacitor) {
                console.log('Detectada ejecución dentro de Capacitor. Cargando puente...');
                const capScript = document.createElement('script');
                capScript.src = '/capacitor.js';
                capScript.onload = function() {
                    console.log('Capacitor Bridge cargado correctamente.');
                    
                    if (window.Capacitor && window.Capacitor.Plugins) {
                        const { App, StatusBar } = window.Capacitor.Plugins;
                        
                        // 1. Control del botón físico de retroceso en Android
                        if (App) {
                            App.addListener('backButton', function() {
                                const currentPath = window.location.pathname;
                                const isHome = currentPath === '/' || 
                                               currentPath === '/admin/dashboard' || 
                                               currentPath === '/dashboard' || 
                                               currentPath.includes('/login');
                                               
                                if (isHome || window.history.length <= 1) {
                                    console.log('Inicio de aplicación o sin historial. Saliendo de la app...');
                                    App.exitApp();
                                } else {
                                    console.log('Retrocediendo historial web...');
                                    window.history.back();
                                }
                            });
                        }
                        
                        // 2. Personalización de la barra de estado superior nativa
                        if (StatusBar) {
                            try {
                                StatusBar.setBackgroundColor({ color: '#2563eb' })
                                    .then(() => console.log('Barra de estado pintada de azul (#2563eb)'))
                                    .catch(err => console.warn('No se pudo establecer color de StatusBar:', err));
                                    
                                StatusBar.setStyle({ style: 'DARK' })
                                    .then(() => console.log('Estilo de barra establecido en DARK (iconos claros)'))
                                    .catch(err => console.warn('No se pudo establecer estilo de StatusBar:', err));
                            } catch (e) {
                                console.warn('Error al configurar StatusBar nativa:', e);
                            }
                        }
                    }
                };
                capScript.onerror = function() {
                    console.error('No se pudo cargar /capacitor.js. Asegúrate de que el servidor nativo esté inyectando la librería.');
                };
                document.body.appendChild(capScript);
            }
        })();
    </script>
    <!-- PWA Service Worker Registration -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('/sw.js')
                    .then(function(reg) {
                        console.log('Service Worker registrado con éxito. Scope:', reg.scope);
                    })
                    .catch(function(err) {
                        console.log('Fallo en registro de Service Worker:', err);
                    });
            });
        }
    </script>
    @auth
    <!-- Background Pre-fetching for Offline PWA use -->
    <script>
        window.addEventListener('load', function() {
            // Wait 2.5 seconds to let the main page finish loading smoothly
            setTimeout(function() {
                if ('serviceWorker' in navigator && navigator.serviceWorker.controller) {
                    const pagesToPrefetch = [
                        '/inspecciones/nuevo',
                        '/visitas',
                        '/productores',
                        '/predios'
                    ];
                    pagesToPrefetch.forEach(function(url) {
                        fetch(url)
                            .then(function() {
                                console.log('[PWA] Pre-descargado y cacheado en segundo plano:', url);
                            })
                            .catch(function(err) {
                                console.warn('[PWA] Fallo al pre-descargar en segundo plano:', url, err);
                            });
                    });
                }
            }, 2500);
        });
    </script>
    @endauth
</body>
</html>
