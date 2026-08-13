<nav class="navbar navbar-expand-md sticky-top" id="navbar">
    <div class="container-fluid">
        <a class="navbar-brand" href="{{ route('admin.dashboard') }}">
            <i class="fas fa-id-card"></i>
            <span style="color: var(--navbar-text); font-weight: 700;">{{ __('messages.app_name') }}</span>
        </a>
        
        <button class="navbar-toggler" type="button" id="sidebarToggle">
            <i class="fas fa-bars" style="color: var(--navbar-text);"></i>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <!-- Theme Color Selector -->
                <li class="nav-item dropdown me-2">
                    <button class="btn btn-sm btn-theme-toggle dropdown-toggle" id="themeSelector" data-bs-toggle="dropdown" title="{{ __('messages.change_theme') }}">
                        <i class="fas fa-palette"></i> {{ __('messages.theme') }}
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="themeSelector">
                        <li><a class="dropdown-item" href="#" data-theme="default" onclick="setTheme('default'); return false;">
                            <i class="fas fa-circle" style="color: #4060a0;"></i> Bleu Sombre (défaut)
                        </a></li>
                        <li><a class="dropdown-item" href="#" data-theme="dark-clair" onclick="setTheme('dark-clair'); return false;">
                            <i class="fas fa-circle" style="color: #667eea;"></i> Dark Clair
                        </a></li>
                        <li><a class="dropdown-item" href="#" data-theme="green" onclick="setTheme('green'); return false;">
                            <i class="fas fa-circle" style="color: #10b981;"></i> Vert
                        </a></li>
                        <li><a class="dropdown-item" href="#" data-theme="red" onclick="setTheme('red'); return false;">
                            <i class="fas fa-circle" style="color: #ef4444;"></i> Rouge
                        </a></li>
                        <li><a class="dropdown-item" href="#" data-theme="orange" onclick="setTheme('orange'); return false;">
                            <i class="fas fa-circle" style="color: #f97316;"></i> Orange
                        </a></li>
                        <li><a class="dropdown-item" href="#" data-theme="indigo" onclick="setTheme('indigo'); return false;">
                            <i class="fas fa-circle" style="color: #4f46e5;"></i> Indigo
                        </a></li>
                    </ul>
                </li>

                <li class="nav-item dropdown me-2">
                    <button class="btn btn-sm btn-theme-toggle dropdown-toggle" id="languageSelector" data-bs-toggle="dropdown" title="{{ __('messages.language') }}">
                        <i class="fas fa-language"></i> {{ strtoupper(app()->getLocale()) }}
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="languageSelector">
                        @foreach(config('app.supported_locales') as $locale => $localeConfig)
                            <li>
                                <form method="POST" action="{{ route('language.update', $locale) }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item {{ app()->getLocale() === $locale ? 'active' : '' }}">
                                        <i class="fas fa-check {{ app()->getLocale() === $locale ? '' : 'invisible' }}"></i>
                                        {{ $localeConfig['native'] }}
                                    </button>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                </li>

                <!-- Dark Mode Toggle -->
                <li class="nav-item">
                    <button class="btn btn-outline-light btn-sm me-2" id="darkModeToggle" title="{{ __('messages.dark_mode') }}">
                        <i class="fas fa-moon"></i>
                    </button>
                </li>
                
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        @if(Auth::user()->avatar)
                            <img src="{{ asset('storage/' . Auth::user()->avatar) }}" alt="{{ Auth::user()->name }}" class="rounded-circle" style="width: 28px; height: 28px; object-fit: cover;">
                        @else
                            <i class="fas fa-user-circle fs-5"></i>
                        @endif
                        @php
                            $navRole = Auth::user()->role->value ?? Auth::user()->role;
                            $navRoleLabel = trans()->has('messages.roles.' . $navRole) ? __('messages.roles.' . $navRole) : ucfirst((string) $navRole);
                        @endphp
                        <span class="d-flex flex-column lh-1 text-start">
                            <small class="opacity-75" style="font-size: .68rem;">{{ $navRoleLabel }}</small>
                            <span>{{ Auth::user()->name ?? __('messages.guest') }}</span>
                        </span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <li>
                            <a class="dropdown-item" href="{{ route('profile.edit') }}">
                                <i class="fas fa-user"></i> {{ __('messages.profile') }}
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form action="{{ route('logout') }}" method="POST" style="display: inline; width: 100%;">
                                @csrf
                                <button type="submit" class="dropdown-item">
                                    <i class="fas fa-sign-out-alt"></i> {{ __('messages.logout') }}
                                </button>
                            </form>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<style>
    html.dark-mode #navbar {
        background-color: #0d0d0d !important;
        border-bottom: 1px solid #222;
    }

    #navbar {
        background-color: var(--navbar-bg) !important;
        border-bottom: 1px solid rgba(0,0,0,0.06);
    }

    .btn-theme-toggle {
        background-color: rgba(255,255,255,0.08);
        border-color: rgba(255,255,255,0.2);
        color: var(--navbar-text);
    }

    .btn-theme-toggle:hover,
    .btn-theme-toggle:focus {
        background-color: rgba(255,255,255,0.16);
        color: var(--navbar-text);
    }

    .dropdown-menu {
        background-color: var(--card-bg) !important;
        border: 1px solid var(--card-border) !important;
        color: var(--body-text) !important;
    }

    .dropdown-menu .dropdown-item {
        color: var(--body-text) !important;
    }

    .dropdown-menu .dropdown-item:hover,
    .dropdown-menu .dropdown-item:focus {
        /* Light theme: subtle darker overlay + body text for readability */
        background-color: rgba(0,0,0,0.06) !important;
        color: var(--body-text) !important;
    }

    /* Dark mode: use a light overlay and keep navbar-text (usually light) */
    html.dark-mode .dropdown-menu .dropdown-item:hover,
    html.dark-mode .dropdown-menu .dropdown-item:focus {
        background-color: rgba(255,255,255,0.06) !important;
        color: var(--navbar-text) !important;
    }

    .dropdown-menu .dropdown-item i {
        width: 18px;
    }

    @media (max-width: 767.98px) {
        #navbar .container-fluid {
            flex-wrap: nowrap;
            gap: 8px;
        }

        #navbar .navbar-brand {
            flex: 1 1 auto;
            min-width: 0;
            font-size: 18px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        #navbar .navbar-toggler {
            flex: 0 0 auto;
            padding: 4px 8px;
        }

        #navbarNav {
            display: flex !important;
            flex: 0 0 auto;
            width: auto;
        }

        #navbarNav .navbar-nav {
            flex-direction: row;
            align-items: center;
            gap: 6px;
        }

        #navbarNav .nav-item {
            flex: 0 0 auto;
        }

        #navbarNav .nav-link {
            margin-left: 0;
            padding: 6px;
        }

        #themeSelector,
        #languageSelector,
        #darkModeToggle {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            margin-right: 0 !important;
            padding: 0;
        }

        #themeSelector,
        #languageSelector {
            font-size: 0;
        }

        #themeSelector i,
        #languageSelector i {
            font-size: 14px;
        }

        #userDropdown span {
            display: none;
        }

        #navbar .dropdown-menu {
            z-index: 1100;
            min-width: 190px;
            max-width: calc(100vw - 24px);
        }
    }

    /* Theme Color Variables */
    :root {
        --primary-color: #4060a0;
        --secondary-color: #556fb8;
        --navbar-bg: #152645;
        --sidebar-bg: #122239;
        --sidebar-text: #f4f6f8;
        --navbar-text: #f4f6f8;
    }

    :root[data-theme="dark-clair"] {
        --primary-color: #667eea;
        --secondary-color: #764ba2;
        --navbar-bg: #2b3038;
        --sidebar-bg: #2b3038;
        --sidebar-text: #f4f6f8;
        --navbar-text: #f4f6f8;
    }

    :root[data-theme="green"] {
        --primary-color: #10b981;
        --secondary-color: #059669;
        --navbar-bg: #0f766e;
        --sidebar-bg: #0e6e63;
        --sidebar-text: #ffffff;
        --navbar-text: #ffffff;
    }

    :root[data-theme="red"] {
        --primary-color: #ef4444;
        --secondary-color: #dc2626;
        --navbar-bg: #b91c1c;
        --sidebar-bg: #991b1b;
        --sidebar-text: #ffffff;
        --navbar-text: #ffffff;
    }

    :root[data-theme="orange"] {
        --primary-color: #f97316;
        --secondary-color: #ea580c;
        --navbar-bg: #c2410c;
        --sidebar-bg: #9a3412;
        --sidebar-text: #ffffff;
        --navbar-text: #ffffff;
    }

    :root[data-theme="indigo"] {
        --primary-color: #4f46e5;
        --secondary-color: #4338ca;
        --navbar-bg: #4338ca;
        --sidebar-bg: #3730a3;
        --sidebar-text: #ffffff;
        --navbar-text: #ffffff;
    }
</style>

<script>
    function setTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem('selectedTheme', theme);
        location.reload();
    }

    // Load saved theme on page load
    document.addEventListener('DOMContentLoaded', function() {
        const savedTheme = localStorage.getItem('selectedTheme') || 'default';
        if (savedTheme !== 'default') {
            document.documentElement.setAttribute('data-theme', savedTheme);
        }
    });
</script>
