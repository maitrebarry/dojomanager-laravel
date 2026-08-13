@php
    // Visibilité des onglets fidèle à PassageGrades.jsx :
    //   showConfigTab = rôle ≠ MAITRE · showExamTab = SUPERADMIN/ADMIN/LIGUE
    // Un maître ne voit donc que l'onglet Soumission.
    $tabsUser = Auth::user();
    $tabsRole = $tabsUser->role->value ?? $tabsUser->role;
    $showConfig = $tabsRole !== 'maitre';
    $showExam = in_array($tabsRole, ['superadmin', 'federation', 'ligue'], true);
    $showSessions = $tabsRole !== 'maitre';
    $active = $active ?? '';
@endphp
<ul class="nav nav-tabs mb-4">
    @if($showConfig)
        <li class="nav-item"><a class="nav-link {{ $active === 'config' ? 'active' : '' }}" href="{{ route('admin.grade-passages.configuration') }}"><i class="fas fa-tags me-1"></i> {{ __('messages.grade_passages.tab_config') }}</a></li>
    @endif
    <li class="nav-item"><a class="nav-link {{ $active === 'soumission' ? 'active' : '' }}" href="{{ route('admin.grade-passages.soumission') }}"><i class="fas fa-file-signature me-1"></i> {{ __('messages.grade_passages.tab_submission') }}</a></li>
    @if($showExam)
        <li class="nav-item"><a class="nav-link {{ $active === 'examen' ? 'active' : '' }}" href="{{ route('admin.grade-passages.examen') }}"><i class="fas fa-clipboard-check me-1"></i> {{ __('messages.grade_passages.tab_exam') }}</a></li>
    @endif
    @if($showSessions)
        <li class="nav-item"><a class="nav-link {{ $active === 'sessions' ? 'active' : '' }}" href="{{ route('admin.grade-passages.index') }}"><i class="fas fa-ranking-star me-1"></i> {{ __('messages.grade_passages.sessions_list') }}</a></li>
    @endif
</ul>
