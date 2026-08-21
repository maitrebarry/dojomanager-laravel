@php
    $currentLocale = $currentLocale ?? app()->getLocale();
    $currentDirection = $currentDirection ?? config("app.supported_locales.$currentLocale.dir", 'ltr');
@endphp
<!DOCTYPE html>
<html lang="{{ $currentLocale }}" dir="{{ $currentDirection }}" data-locale="{{ $currentLocale }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', __('messages.dashboard')) - {{ __('messages.app_name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --primary-color: #4060a0;
            --secondary-color: #556fb8;
            --navbar-bg: #152645;
            --sidebar-bg: #122239;
            --navbar-text: #f4f6f8;
            --sidebar-text: #f4f6f8;
            --main-bg: #e8efff;
            --card-bg: #ffffff;
            --card-border: #d7dee9;
            --footer-bg: #f8f9fa;
            --footer-border: #dfe4ee;
            --footer-text: #6b7280;
            --body-text: #1f2937;
        }

        :root[data-theme="dark-clair"] {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --navbar-bg: #2b3038;
            --sidebar-bg: #2b3038;
            --navbar-text: #f4f6f8;
            --sidebar-text: #f4f6f8;
            --main-bg: #181c24;
            --card-bg: #202532;
            --card-border: #323945;
            --footer-bg: #1d2330;
            --footer-border: #2e3948;
            --footer-text: #a8b0c0;
        }

        :root[data-theme="green"] {
            --primary-color: #10b981;
            --secondary-color: #059669;
            --navbar-bg: #0f766e;
            --sidebar-bg: #0e6e63;
            --navbar-text: #ffffff;
            --sidebar-text: #ffffff;
        }

        :root[data-theme="red"] {
            --primary-color: #ef4444;
            --secondary-color: #dc2626;
            --navbar-bg: #b91c1c;
            --sidebar-bg: #991b1b;
            --navbar-text: #ffffff;
            --sidebar-text: #ffffff;
        }

        :root[data-theme="orange"] {
            --primary-color: #f97316;
            --secondary-color: #ea580c;
            --navbar-bg: #c2410c;
            --sidebar-bg: #9a3412;
            --navbar-text: #ffffff;
            --sidebar-text: #ffffff;
        }

        :root[data-theme="indigo"] {
            --primary-color: #4f46e5;
            --secondary-color: #4338ca;
            --navbar-bg: #4338ca;
            --sidebar-bg: #3730a3;
            --navbar-text: #ffffff;
            --sidebar-text: #ffffff;
        }

        html.dark-mode {
            --navbar-bg: #111827;
            --sidebar-bg: #0f172a;
            --main-bg: #0b1120;
            --card-bg: #111827;
            --card-border: #1f2937;
            --footer-bg: #0f172a;
            --footer-border: #1f2937;
            --footer-text: #cbd5e1;
            --navbar-text: #e2e8f0;
            --sidebar-text: #e2e8f0;
            --body-text: #e2e8f0;
            --surface-alt: #1a2332;
        }

        /* Bootstrap "bg-light"/"bg-white" sont des utilitaires figés (jamais adaptés au
           thème) : très utilisés dans l'admin comme fond de panneau/badge secondaire, ils
           restaient blancs même en mode sombre. On les fait suivre le thème comme le reste. */
        html.dark-mode .bg-light,
        html.dark-mode .bg-white,
        html.dark-mode .table-light {
            background-color: var(--surface-alt) !important;
            color: var(--body-text) !important;
        }
        html.dark-mode .bg-light.border,
        html.dark-mode .bg-white.border {
            border-color: var(--card-border) !important;
        }

        /* Fenêtres Bootstrap (.modal-content) et popups SweetAlert2 : blanches par
           défaut, ni l'une ni l'autre ne suit --card-bg nativement — très visibles
           ("blanc partout") vu leur usage constant (confirmations, formulaires). */
        html.dark-mode .modal-content {
            background-color: var(--card-bg);
            color: var(--body-text);
            border-color: var(--card-border);
        }
        html.dark-mode .modal-header,
        html.dark-mode .modal-footer {
            border-color: var(--card-border);
        }
        html.dark-mode .btn-close {
            filter: invert(1) grayscale(100%) brightness(200%);
        }
        html.dark-mode .swal2-popup {
            background: var(--card-bg) !important;
            color: var(--body-text) !important;
        }
        html.dark-mode .swal2-title,
        html.dark-mode .swal2-html-container {
            color: var(--body-text) !important;
        }

        * {
            transition: background-color 0.3s, color 0.3s;
        }

        html, body {
            height: 100%;
        }

        body {
            background-color: var(--main-bg);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            flex-direction: column;
        }

        [dir="rtl"] body {
            text-align: right;
        }

        html.dark-mode body {
            background-color: var(--main-bg);
            color: var(--body-text);
        }

        .navbar {
            background-color: var(--navbar-bg);
            color: var(--navbar-text);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            flex-shrink: 0;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }

        /* Uniformisation des cartes de tables : en-tête à la couleur du navbar */
        .card-navbar { border-color: var(--navbar-bg) !important; }
        .card-header-navbar {
            background-color: var(--navbar-bg) !important;
            color: #fff !important;
            border-bottom: none;
        }
        .card-header-navbar h1, .card-header-navbar h2, .card-header-navbar h3,
        .card-header-navbar h4, .card-header-navbar h5, .card-header-navbar h6,
        .card-header-navbar .form-label,
        .card-header-navbar label { color: #fff !important; }
        .card-header-navbar .input-group-text {
            background-color: rgba(255,255,255,.15) !important;
            border-color: rgba(255,255,255,.25) !important;
            color: #fff !important;
        }
        .card-header-navbar .input-group-text .text-muted,
        .card-header-navbar .text-muted { color: rgba(255,255,255,.75) !important; }
        .card-header-navbar .form-control,
        .card-header-navbar .form-select { border-color: rgba(255,255,255,.25); }

        /* Alignement des icônes dans la colonne Actions des tableaux */
        .table td form { margin: 0; }
        .table td .btn { vertical-align: middle; }
        .table td .btn-group,
        .table td .d-flex { vertical-align: middle; }
        /* Un <form> dans un btn-group devient transparent : son bouton rejoint le groupe */
        .btn-group > form { display: contents; }

        .navbar-brand {
            font-weight: 700;
            font-size: 20px;
            color: var(--navbar-text) !important;
        }

        .navbar-brand i {
            margin-right: 8px;
        }

        [dir="rtl"] .navbar-brand i {
            margin-right: 0;
            margin-left: 8px;
        }

        /* Navbar dropdown styling: unified and with strong hover contrast */
        .navbar .dropdown-menu {
            background-color: var(--card-bg);
            border: 1px solid var(--card-border);
            color: var(--body-text);
            min-width: 200px;
        }

        .navbar .dropdown-item {
            color: var(--body-text);
            padding: 8px 12px;
            transition: background-color 0.15s ease, color 0.15s ease;
        }

        .navbar .dropdown-item:hover,
        .navbar .dropdown-item:focus {
            /* Use a subtle overlay instead of forcing a solid primary background
               so text color stays readable across light/dark themes */
            background-color: rgba(0,0,0,0.06) !important;
            color: var(--body-text) !important;
        }

        /* Dark mode adjustments for better contrast */
        html.dark-mode .navbar .dropdown-menu {
            background-color: var(--card-bg);
            border-color: var(--card-border);
        }

        html.dark-mode .navbar .dropdown-item {
            color: var(--body-text);
        }

        html.dark-mode .navbar .dropdown-item:hover,
        html.dark-mode .navbar .dropdown-item:focus {
            /* Slightly lighter overlay in dark mode to preserve contrast */
            background-color: rgba(255,255,255,0.06) !important;
            color: var(--body-text) !important;
        }

        .navbar .dropdown-item i {
            margin-right: 8px;
            min-width: 20px;
        }

        [dir="rtl"] .navbar .dropdown-item i {
            margin-right: 0;
            margin-left: 8px;
        }

        .navbar-nav .nav-link {
            color: var(--navbar-text) !important;
            margin-left: 10px;
        }

        [dir="rtl"] .navbar-nav .nav-link {
            margin-left: 0;
            margin-right: 10px;
        }

        .navbar-nav .nav-link:hover {
            color: #e0e0e0;
        }

        #darkModeToggle, #themeSelector {
            border-color: rgba(255, 255, 255, 0.5);
            color: var(--navbar-text);
            background: transparent;
        }

        /* Hover: use dark overlay on light themes, light overlay on dark themes */
        #darkModeToggle:hover, #themeSelector:hover {
            background-color: rgba(0,0,0,0.06);
            border-color: rgba(0,0,0,0.12);
            color: var(--navbar-text);
        }

        html.dark-mode #darkModeToggle:hover, html.dark-mode #themeSelector:hover {
            background-color: rgba(255,255,255,0.06);
            border-color: rgba(255,255,255,0.18);
            color: var(--navbar-text);
        }

        .wrapper {
            display: flex;
            flex: 1;
            overflow: hidden;
        }

        .sidebar {
            background-color: var(--sidebar-bg);
            border-right: 1px solid #333;
            width: 250px;
            overflow-y: auto;
            padding: 20px 0;
            position: fixed;
            left: 0;
            top: 56px;
            height: calc(100vh - 56px - 80px);
            z-index: 1000;
        }

        [dir="rtl"] .sidebar {
            left: auto;
            right: 0;
            border-right: 0;
            border-left: 1px solid #333;
        }

        /* Collapsed (icons-only) sidebar */
        .sidebar.collapsed {
            width: 70px;
        }

        .sidebar.collapsed {
            width: 70px;
        }

        .sidebar.collapsed .nav-link {
            justify-content: center;
            padding-left: 0 !important;
            padding-right: 0 !important;
            text-align: center;
            font-size: 0;
        }

        .sidebar.collapsed .nav-link .nav-text,
        .sidebar.collapsed .nav-link .ms-auto,
        .sidebar.collapsed .nav-section-title,
        .sidebar.collapsed .nav-item.dropdown-menu-like .collapse,
        .sidebar.collapsed .nav-item.dropdown-menu-like .collapse.show {
            display: none !important;
        }

        .sidebar.collapsed .nav-link i {
            margin: 0;
            font-size: 20px;
        }

        .sidebar .sidebar-collapse-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 42px;
            height: 42px;
            margin: 0 auto 15px;
            border: 1px solid rgba(255,255,255,0.18);
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.06);
            color: var(--sidebar-text);
            cursor: pointer;
            transition: transform 0.3s ease, background-color 0.2s ease;
        }

        .sidebar .sidebar-collapse-btn:hover {
            background-color: rgba(255,255,255,0.12);
        }

        .sidebar .sidebar-collapse-btn i {
            transition: transform 0.3s ease;
            font-size: 16px;
        }

        .sidebar.collapsed .sidebar-collapse-btn i {
            transform: rotate(180deg);
        }

        .sidebar.collapsed .sidebar-collapse-btn i {
            transform: rotate(180deg);
        }

        .main-content.collapsed-offset {
            margin-left: 70px;
            width: calc(100% - 70px);
        }

        [dir="rtl"] .main-content.collapsed-offset {
            margin-left: 0;
            margin-right: 70px;
        }

        html.dark-mode .sidebar {
            background-color: #0d0d0d;
            border-right-color: #222;
        }

        .sidebar .nav-link {
            color: var(--sidebar-text);
            padding: 12px 20px;
            margin: 5px 0;
            border-left: 3px solid transparent;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
        }

        [dir="rtl"] .sidebar .nav-link {
            border-left: 0;
            border-right: 3px solid transparent;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background-color: rgba(102, 126, 234, 0.1);
            border-left-color: var(--primary-color);
            color: var(--primary-color);
        }

        [dir="rtl"] .sidebar .nav-link:hover,
        [dir="rtl"] .sidebar .nav-link.active {
            border-left-color: transparent;
            border-right-color: var(--primary-color);
        }

        html.dark-mode .sidebar .nav-link:hover,
        html.dark-mode .sidebar .nav-link.active {
            background-color: rgba(102, 126, 234, 0.15);
        }

        .sidebar .nav-section-title {
            color: #888;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            padding: 15px 20px 5px;
            margin-top: 10px;
        }

        html.dark-mode .sidebar .nav-section-title {
            color: #666;
        }

        .sidebar hr {
            border-color: #333;
            opacity: 0.5;
            margin: 10px 0;
        }

        html.dark-mode .sidebar hr {
            border-color: #222;
        }

        .main-content {
            margin-left: 250px;
            padding: 30px;
            width: calc(100% - 250px);
            flex: 1;
            overflow-y: auto;
        }

        [dir="rtl"] .main-content {
            margin-left: 0;
            margin-right: 250px;
        }

        @media (max-width: 768px) {
            .sidebar {
                display: none;
                position: fixed;
                left: -250px;
                width: 250px;
                z-index: 1040;
            }

            [dir="rtl"] .sidebar {
                left: auto;
                right: -250px;
            }

            .sidebar.show {
                display: block;
                left: 0;
            }

            [dir="rtl"] .sidebar.show {
                left: auto;
                right: 0;
            }

            /* overlay when sidebar slides in */
            .sidebar-overlay {
                display: none;
                position: fixed;
                inset: 0;
                background: rgba(0,0,0,0.4);
                z-index: 1030;
            }

            .sidebar-overlay.show {
                display: block;
            }

            .main-content {
                margin-left: 0;
                width: 100%;
            }

            [dir="rtl"] .main-content {
                margin-right: 0;
                width: 100%;
            }
        }

        [dir="rtl"] .ms-auto {
            margin-left: 0 !important;
            margin-right: auto !important;
        }

        [dir="rtl"] .me-1,
        [dir="rtl"] .me-2,
        [dir="rtl"] .me-3 {
            margin-right: 0 !important;
        }

        [dir="rtl"] .me-1 { margin-left: .25rem !important; }
        [dir="rtl"] .me-2 { margin-left: .5rem !important; }
        [dir="rtl"] .me-3 { margin-left: 1rem !important; }

        [dir="rtl"] .ms-1 { margin-right: .25rem !important; margin-left: 0 !important; }
        [dir="rtl"] .ms-2 { margin-right: .5rem !important; margin-left: 0 !important; }
        [dir="rtl"] .ms-3 { margin-right: 1rem !important; margin-left: 0 !important; }

        [dir="rtl"] .text-end {
            text-align: left !important;
        }

        [dir="rtl"] .text-start {
            text-align: right !important;
        }

        .card {
            border: 1px solid var(--card-border);
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            margin-bottom: 20px;
            background-color: var(--card-bg);
            color: var(--body-text);
        }

        .card:hover {
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.12);
        }

        .card:hover {
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.12);
        }

        .page-header {
            margin-bottom: 30px;
        }

        .breadcrumb {
            --bs-breadcrumb-divider-color: #6b7280;
            font-size: 14px;
        }

        .breadcrumb a {
            color: var(--primary-color);
            font-weight: 600;
            text-decoration: none;
        }

        .breadcrumb a:hover {
            color: var(--secondary-color);
            text-decoration: underline;
        }

        .breadcrumb-item.active {
            color: #6b7280;
        }

        .page-header h1 {
            font-size: 28px;
            font-weight: 700;
            color: #333;
        }

        html.dark-mode .breadcrumb-item.active,
        html.dark-mode .breadcrumb {
            color: #a8b0c0;
        }

        html.dark-mode .page-header h1 {
            color: #e0e0e0;
        }

        .alert {
            border: none;
            border-radius: 10px;
        }

        html.dark-mode .alert-success {
            background-color: #1e4620;
            color: #b8e6c1;
        }

        html.dark-mode .alert-danger {
            background-color: #4a1f1f;
            color: #f5a3a3;
        }

        html.dark-mode .alert-info {
            background-color: #1f3a4a;
            color: #a3d5f5;
        }

        html.dark-mode .alert-warning {
            background-color: #4a3a1f;
            color: #f5d4a3;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border: none;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--secondary-color) 0%, var(--primary-color) 100%);
        }

        .table {
            color: var(--body-text);
            border-color: var(--card-border);
        }

        .table thead th {
            background-color: var(--card-bg);
            color: var(--body-text);
            border-color: var(--card-border);
        }

        .table tbody tr {
            border-color: var(--card-border);
        }

        .table tbody tr:hover {
            background-color: rgba(102, 126, 234, 0.08);
        }

        /* Bootstrap 5 colore ses tableaux via ses propres variables --bs-table-* (fond,
           lignes zébrées, survol), indépendamment du thème "maison" ci-dessus : sans ce
           reset, .table restait blanc en mode sombre même si le texte suivait bien. */
        html.dark-mode .table {
            --bs-table-bg: var(--card-bg);
            --bs-table-color: var(--body-text);
            --bs-table-border-color: var(--card-border);
            --bs-table-striped-bg: rgba(255, 255, 255, .03);
            --bs-table-striped-color: var(--body-text);
            --bs-table-hover-bg: rgba(102, 126, 234, .12);
            --bs-table-hover-color: var(--body-text);
            background-color: var(--card-bg);
        }

        .form-control, .form-select {
            border: 1px solid var(--card-border);
            border-radius: 8px;
            background-color: var(--card-bg);
            color: var(--body-text);
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.15);
        }

        html.dark-mode .text-muted {
            color: #b0b0b0 !important;
        }

        footer {
            background-color: var(--footer-bg);
            border-top: 1px solid var(--footer-border);
            color: var(--footer-text);
            padding: 20px;
            flex-shrink: 0;
            margin-top: auto;
        }

        footer a {
            color: var(--primary-color);
            text-decoration: none;
        }

        footer a:hover {
            color: var(--secondary-color);
        }

        footer p {
            margin: 0;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    @include('layouts.navbar')

    <div class="wrapper">
        <!-- Sidebar -->
        @include('layouts.sidebar')

        <!-- Main Content -->
        <div class="main-content">
            @php
                $hasLayoutContent = trim($__env->yieldContent('layout_content')) !== '';
                $currentPageTitle = $page_title ?? trim($__env->yieldContent('title'));
            @endphp

            @if ($hasLayoutContent)
                @yield('layout_content')
            @else
                @if ($currentPageTitle !== '')
                    <div class="mb-4 page-header">
                        <nav aria-label="breadcrumb" class="mb-2">
                            <ol class="breadcrumb mb-0">
                                @hasSection('breadcrumbs')
                                    @yield('breadcrumbs')
                                @else
                                    <li class="breadcrumb-item">
                                        <a href="{{ Route::has('dashboard') ? route('dashboard') : url('/') }}">{{ __('messages.dashboard') }}</a>
                                    </li>
                                    <li class="breadcrumb-item active" aria-current="page">{{ $currentPageTitle }}</li>
                                @endif
                            </ol>
                        </nav>

                        <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap">
                            <h1 class="mb-0">{{ $currentPageTitle }}</h1>
                            @hasSection('actions')
                                <div class="page-actions">
                                    @yield('actions')
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                @yield('content')
            @endif
        </div>
    </div>

    <div id="sidebarOverlay" class="sidebar-overlay" aria-hidden="true"></div>

    <!-- Footer -->
    @include('layouts.footer')

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Dark Mode Toggle
        const darkModeToggle = document.getElementById('darkModeToggle');
        const html = document.documentElement;

        function loadDarkMode() {
            const isDarkMode = localStorage.getItem('darkMode') === 'true';
            if (isDarkMode) {
                html.classList.add('dark-mode');
                if (darkModeToggle) darkModeToggle.innerHTML = '<i class="fas fa-sun"></i>';
            }
        }

        if (darkModeToggle) {
            darkModeToggle.addEventListener('click', function() {
                html.classList.toggle('dark-mode');
                const isDarkMode = html.classList.contains('dark-mode');
                localStorage.setItem('darkMode', isDarkMode);
                darkModeToggle.innerHTML = isDarkMode 
                    ? '<i class="fas fa-sun"></i>' 
                    : '<i class="fas fa-moon"></i>';
            });
        }

        document.addEventListener('DOMContentLoaded', loadDarkMode);

        // Sidebar toggle: slide-in on mobile, collapse to icons on desktop
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        const mainContent = document.querySelector('.main-content');

        function setSidebarCollapsed(collapsed) {
            if (collapsed) {
                sidebar.classList.add('collapsed');
                mainContent.classList.add('collapsed-offset');
                localStorage.setItem('sidebarCollapsed', 'true');
            } else {
                sidebar.classList.remove('collapsed');
                mainContent.classList.remove('collapsed-offset');
                localStorage.setItem('sidebarCollapsed', 'false');
            }
        }

        // Load collapsed state on desktop
        document.addEventListener('DOMContentLoaded', function() {
            const persisted = localStorage.getItem('sidebarCollapsed') === 'true';
            if (window.innerWidth > 768 && persisted) {
                setSidebarCollapsed(true);
                if (sidebarCollapseBtn) {
                    sidebarCollapseBtn.querySelector('i')?.classList.remove('fa-chevron-left');
                    sidebarCollapseBtn.querySelector('i')?.classList.add('fa-chevron-right');
                }
            }
        });

        const sidebarCollapseBtn = document.getElementById('sidebarCollapseBtn');

        if (sidebarToggle && sidebar) {
            sidebarToggle.addEventListener('click', function() {
                if (window.innerWidth <= 768) {
                    // mobile: slide overlay sidebar
                    sidebar.classList.toggle('show');
                    sidebarOverlay.classList.toggle('show');
                }
            });
        }

        if (sidebarCollapseBtn && sidebar) {
            sidebarCollapseBtn.addEventListener('click', function() {
                const collapsed = sidebar.classList.toggle('collapsed');
                if (collapsed) {
                    mainContent.classList.add('collapsed-offset');
                    sidebarCollapseBtn.querySelector('i').classList.remove('fa-chevron-left');
                    sidebarCollapseBtn.querySelector('i').classList.add('fa-chevron-right');
                    localStorage.setItem('sidebarCollapsed', 'true');
                } else {
                    mainContent.classList.remove('collapsed-offset');
                    sidebarCollapseBtn.querySelector('i').classList.remove('fa-chevron-right');
                    sidebarCollapseBtn.querySelector('i').classList.add('fa-chevron-left');
                    localStorage.setItem('sidebarCollapsed', 'false');
                }
            });
        }

        if (sidebarOverlay) {
            sidebarOverlay.addEventListener('click', function() {
                sidebar.classList.remove('show');
                sidebarOverlay.classList.remove('show');
            });
        }

        // Theme selection
        function setTheme(theme) {
            document.documentElement.setAttribute('data-theme', theme);
            localStorage.setItem('selectedTheme', theme);
            location.reload();
        }

        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = localStorage.getItem('selectedTheme') || 'default';
            if (savedTheme !== 'default') {
                document.documentElement.setAttribute('data-theme', savedTheme);
            }
        });
    </script>

    {{-- SweetAlert2 : messages de succès/erreur + confirmations --}}
    <script>
        window.dojoToast = function (icon, title) {
            if (!window.Swal) return;
            Swal.fire({
                toast: true, position: 'top-end', icon: icon, title: title,
                showConfirmButton: false, timer: 3200, timerProgressBar: true,
            });
        };
        window.dojoConfirmDelete = function (formEl, message) {
            if (!window.Swal) { return confirm(message || 'Confirmer ?'); }
            Swal.fire({
                title: @json(__('messages.confirm_delete')),
                text: message || @json(__('messages.delete_warning')),
                icon: 'warning', showCancelButton: true,
                confirmButtonColor: '#dc3545', cancelButtonColor: '#6c757d',
                confirmButtonText: @json(__('messages.yes_delete')),
                cancelButtonText: @json(__('messages.cancel')),
            }).then((r) => { if (r.isConfirmed) formEl.submit(); });
            return false;
        };
        document.addEventListener('DOMContentLoaded', function () {
            @if(session('success')) window.dojoToast('success', @json(session('success'))); @endif
            @if(session('error')) window.dojoToast('error', @json(session('error'))); @endif
            @if(session('warning')) window.dojoToast('warning', @json(session('warning'))); @endif
            @if($errors->any())
                Swal.fire({
                    icon: 'error',
                    title: @json(__('messages.errors')),
                    html: @json('<ul style="text-align:left;margin:0;padding-left:1.1rem">' . collect($errors->all())->map(fn ($e) => '<li>' . e($e) . '</li>')->implode('') . '</ul>'),
                });
            @endif
        });
    </script>

    @include('partials.localized-inputs-script')
    @yield('scripts')
</body>
</html>
