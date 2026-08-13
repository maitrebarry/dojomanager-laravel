@php
    /**
     * Copie fidèle de dojo-frontend/src/components/Sidebar.jsx :
     * chaque entrée est filtrée par permission ET (optionnellement) par rôle.
     * Rôles en vocabulaire Laravel : superadmin / federation (=ADMIN Spring) / ligue / maitre.
     * Le superadmin voit tout ($u->canSeeMenu court-circuite pour lui).
     */
    $u = Auth::user();
    $menu = fn (?string $perm, ?array $roles = null) => $u && $u->canSeeMenu($perm, $roles);

    $showMensualite = $menu(null, ['maitre']);
    $showCotisations = $menu('COTISATION_MANAGE', ['federation', 'ligue']);
    $showFinances = $showMensualite || $showCotisations;
@endphp
<aside class="col-md-3 col-lg-2 d-md-block sidebar" id="sidebar" style="background-color: var(--sidebar-bg); min-height: calc(100vh - 56px);">
    <div class="position-sticky pt-3">
        <button type="button" id="sidebarCollapseBtn" class="sidebar-collapse-btn" aria-label="{{ __('messages.menu_toggle') }}">
            <i class="fas fa-chevron-left"></i>
        </button>
        <nav class="nav flex-column">

            @if($menu('DASHBOARD_VIEW'))
                <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
                    <i class="fas fa-home"></i> <span class="nav-text">{{ __('messages.dashboard') }}</span>
                </a>
            @endif

            @if($menu('DISCIPLE_READ', ['maitre']))
                <a class="nav-link {{ request()->routeIs('admin.disciples.*') ? 'active' : '' }}" href="{{ route('admin.disciples.index') }}">
                    <i class="fas fa-user-check"></i> <span class="nav-text">{{ __('messages.disciples.title') }}</span>
                </a>
            @endif

            @if($menu('CEINTURESNOIRES_READ', ['federation', 'ligue']))
                <a class="nav-link {{ request()->routeIs('admin.ceintures-noires.*') ? 'active' : '' }}" href="{{ route('admin.ceintures-noires.index') }}">
                    <i class="fas fa-medal"></i> <span class="nav-text">{{ __('messages.ceintures_noires.title') }}</span>
                </a>
            @endif

            @if($menu('PASSAGEGRADES_READ'))
                <a class="nav-link {{ request()->routeIs('admin.grade-passages.*') || request()->routeIs('admin.grade-passage-tariffs.*') ? 'active' : '' }}" href="{{ route('admin.grade-passages.soumission') }}">
                    <i class="fas fa-award"></i> <span class="nav-text">{{ __('messages.grade_passages.title') }}</span>
                </a>
            @endif

            @if($showFinances)
                <div class="nav-section-title">{{ __('messages.nav.finances') }}</div>
                @if($showMensualite)
                    <a class="nav-link {{ request()->routeIs('admin.mensualites.*') ? 'active' : '' }}" href="{{ route('admin.mensualites.index') }}">
                        <i class="fas fa-calendar"></i> <span class="nav-text">{{ __('messages.cotisations.title') }}</span>
                    </a>
                @endif
                @if($showCotisations)
                    <a class="nav-link {{ request()->routeIs('admin.cotisations-annuelles.*') ? 'active' : '' }}" href="{{ route('admin.cotisations-annuelles.index') }}">
                        <i class="fas fa-wallet"></i> <span class="nav-text">{{ __('messages.cotisations_annuelles.title') }}</span>
                    </a>
                @endif
            @endif

            @if($menu('PARAMETRES_READ'))
                <div class="nav-section-title">{{ __('messages.nav.administration') }}</div>
                <a class="nav-link {{ request()->routeIs('admin.settings') || request()->routeIs('admin.permissions.*') || request()->routeIs('admin.users.*') || request()->routeIs('admin.federations.*') || request()->routeIs('admin.ligues.*') || request()->routeIs('admin.salles.*') || request()->routeIs('admin.grades.*') || request()->routeIs('admin.maitres.*') ? 'active' : '' }}" href="{{ route('admin.settings') }}">
                    <i class="fas fa-cog"></i> <span class="nav-text">{{ __('messages.settings') }}</span>
                </a>
            @endif

        </nav>
    </div>
</aside>
