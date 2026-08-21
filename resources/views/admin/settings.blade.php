@extends('layouts.admin')

@section('title', __('messages.settings'))

@section('content')
@php
    $u = Auth::user();
    $can = fn (string $perm) => $u && $u->hasPermission($perm);
    $tabs = [
        ['key' => 'utilisateurs', 'label' => __('messages.parametres.tab_users'), 'icon' => 'fa-user', 'perm' => 'UTILISATEUR_READ'],
        ['key' => 'federations', 'label' => __('messages.federations.title'), 'icon' => 'fa-flag', 'perm' => 'FÉDÉRATION_READ'],
        ['key' => 'ligues', 'label' => __('messages.ligues.title'), 'icon' => 'fa-sitemap', 'perm' => 'LIGUE_READ'],
        ['key' => 'salles', 'label' => __('messages.salles.title'), 'icon' => 'fa-dumbbell', 'perm' => 'SALLE_READ'],
        ['key' => 'grades', 'label' => __('messages.grades.title'), 'icon' => 'fa-medal', 'perm' => 'GRADES_READ'],
        ['key' => 'permissions', 'label' => __('messages.parametres.tab_permissions'), 'icon' => 'fa-key', 'perm' => 'PERMISSION_READ'],
    ];
    $visibleTabs = array_values(array_filter($tabs, fn ($t) => $can($t['perm'])));
    $activeTab = request('tab', $visibleTabs[0]['key'] ?? 'utilisateurs');
@endphp

@if(empty($visibleTabs))
    <div class="alert alert-info">{{ __('messages.parametres.no_access') }}</div>
@else
<div class="row g-3">
    {{-- ===================== MENU LATÉRAL (col-3) ===================== --}}
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="list-group list-group-flush parametres-menu" id="parametresTabs" role="tablist">
                @foreach($visibleTabs as $t)
                    <a class="list-group-item list-group-item-action {{ $activeTab === $t['key'] ? 'active' : '' }}"
                       id="pill-{{ $t['key'] }}" data-bs-toggle="pill" href="#tab-{{ $t['key'] }}" role="tab">
                        <i class="fas {{ $t['icon'] }} me-2"></i> {{ $t['label'] }}
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ===================== CONTENU (col-9) ===================== --}}
    <div class="col-md-9">
        <div class="tab-content">

            {{-- UTILISATEURS --}}
            @if($can('UTILISATEUR_READ'))
            <div class="tab-pane fade {{ $activeTab === 'utilisateurs' ? 'show active' : '' }}" id="tab-utilisateurs" role="tabpanel">
                <div class="card border-0 shadow-sm"><div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0"><i class="fas fa-user me-2"></i> {{ __('messages.parametres.tab_users') }}</h6>
                        @if($can('UTILISATEUR_CREATE') && !empty($roleOptions))
                            <button type="button" class="btn btn-sm text-white js-open" style="background-color: var(--navbar-bg);"
                                    data-modal="#m-user" data-mode="create" data-action="{{ route('admin.users.store') }}">
                                <i class="fas fa-plus me-1"></i> {{ __('messages.parametres.add_user') }}
                            </button>
                        @endif
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover table-sm align-middle mb-0">
                            <thead class="table-light"><tr>
                                <th>{{ __('messages.name') }}</th><th>{{ __('messages.phone') }}</th><th>{{ __('messages.role') }}</th><th>{{ __('messages.parametres.scope') }}</th><th class="text-end">{{ __('messages.actions') }}</th>
                            </tr></thead>
                            <tbody>
                            @forelse($users as $usr)
                                <tr>
                                    <td class="fw-semibold">{{ $usr->name }}<div class="text-muted small">{{ $usr->email }}</div></td>
                                    <td>{{ $usr->phone ?? '-' }}</td>
                                    <td><span class="badge bg-secondary">{{ __('messages.roles.' . $usr->role) }}</span></td>
                                    <td class="small text-muted">{{ $usr->salle?->nom ?? $usr->ligue?->nom ?? $usr->federation?->nom ?? '-' }}</td>
                                    <td class="text-end">
                                        @if($can('UTILISATEUR_UPDATE'))
                                            @php $plUser = ['name'=>$usr->name,'phone'=>$usr->phone,'email'=>$usr->email,'role'=>$usr->role,'fonction'=>$usr->fonction,'federation_id'=>$usr->federation_id,'ligue_id'=>$usr->ligue_id,'salle_id'=>$usr->salle_id,'grade_id'=>$usr->grade_id]; @endphp
                                            <button type="button" class="btn btn-sm btn-outline-primary js-open"
                                                data-modal="#m-user" data-mode="edit" data-action="{{ route('admin.users.update', $usr) }}"
                                                data-payload='@json($plUser)'>
                                                <i class="fas fa-edit"></i></button>
                                        @endif
                                        @if($can('UTILISATEUR_DELETE'))
                                            <form action="{{ route('admin.users.destroy', $usr) }}" method="POST" class="d-inline" onsubmit="return dojoConfirmDelete(this);">@csrf @method('DELETE')<input type="hidden" name="back_to_settings" value="1"><button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button></form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-4">{{ __('messages.parametres.no_users') }}</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div></div>
            </div>
            @endif

            {{-- FÉDÉRATIONS --}}
            @if($can('FÉDÉRATION_READ'))
            <div class="tab-pane fade {{ $activeTab === 'federations' ? 'show active' : '' }}" id="tab-federations" role="tabpanel">
                <div class="card border-0 shadow-sm"><div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0"><i class="fas fa-flag me-2"></i> {{ __('messages.federations.title') }}</h6>
                        @if($can('FÉDÉRATION_CREATE'))
                            <button type="button" class="btn btn-sm text-white js-open" style="background-color: var(--navbar-bg);" data-modal="#m-fed" data-mode="create" data-action="{{ route('admin.federations.store') }}"><i class="fas fa-plus me-1"></i> {{ __('messages.add') }}</button>
                        @endif
                    </div>
                    <div class="table-responsive"><table class="table table-hover table-sm align-middle mb-0">
                        <thead class="table-light"><tr><th>{{ __('messages.federations.name') }}</th><th>{{ __('messages.federations.acronym') }}</th><th>{{ __('messages.phone') }}</th><th class="text-end">{{ __('messages.actions') }}</th></tr></thead>
                        <tbody>
                        @forelse($federations as $f)
                            <tr>
                                <td class="fw-semibold">{{ $f->nom }}</td><td>{{ $f->sigle ?? '-' }}</td><td>{{ $f->telephone ?? '-' }}</td>
                                <td class="text-end">
                                    @if($can('FÉDÉRATION_UPDATE'))
                                        @php $plFed = ['nom'=>$f->nom,'sigle'=>$f->sigle,'adresse'=>$f->adresse,'telephone'=>$f->telephone,'email'=>$f->email]; @endphp
                                        <button type="button" class="btn btn-sm btn-outline-primary js-open" data-modal="#m-fed" data-mode="edit" data-action="{{ route('admin.federations.update', $f) }}" data-payload='@json($plFed)'><i class="fas fa-edit"></i></button>
                                    @endif
                                    @if($can('FÉDÉRATION_DELETE'))
                                        <form action="{{ route('admin.federations.destroy', $f) }}" method="POST" class="d-inline" onsubmit="return dojoConfirmDelete(this);">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button></form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">{{ __('messages.no_results') }}</td></tr>
                        @endforelse
                        </tbody>
                    </table></div>
                </div></div>
            </div>
            @endif

            {{-- LIGUES --}}
            @if($can('LIGUE_READ'))
            <div class="tab-pane fade {{ $activeTab === 'ligues' ? 'show active' : '' }}" id="tab-ligues" role="tabpanel">
                <div class="card border-0 shadow-sm"><div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0"><i class="fas fa-sitemap me-2"></i> {{ __('messages.ligues.title') }}</h6>
                        @if($can('LIGUE_CREATE'))
                            <button type="button" class="btn btn-sm text-white js-open" style="background-color: var(--navbar-bg);" data-modal="#m-ligue" data-mode="create" data-action="{{ route('admin.ligues.store') }}"><i class="fas fa-plus me-1"></i> {{ __('messages.add') }}</button>
                        @endif
                    </div>
                    <div class="table-responsive"><table class="table table-hover table-sm align-middle mb-0">
                        <thead class="table-light"><tr><th>{{ __('messages.ligues.name') }}</th><th>{{ __('messages.ligues.region') }}</th><th>{{ __('messages.ligues.federation') }}</th><th>{{ __('messages.status') }}</th><th class="text-end">{{ __('messages.actions') }}</th></tr></thead>
                        <tbody>
                        @forelse($ligues as $l)
                            <tr>
                                <td class="fw-semibold">{{ $l->nom }}</td><td>{{ $l->region ?? '-' }}</td><td>{{ $l->federation?->nom ?? '-' }}</td>
                                <td><span class="badge bg-{{ $l->active ? 'success' : 'secondary' }}">{{ $l->active ? __('messages.active') : __('messages.inactive') }}</span></td>
                                <td class="text-end">
                                    @if($can('LIGUE_UPDATE'))
                                        @php $plLigue = ['nom'=>$l->nom,'region'=>$l->region,'federation_id'=>$l->federation_id,'active'=>$l->active ? 1 : 0]; @endphp
                                        <button type="button" class="btn btn-sm btn-outline-primary js-open" data-modal="#m-ligue" data-mode="edit" data-action="{{ route('admin.ligues.update', $l) }}" data-payload='@json($plLigue)'><i class="fas fa-edit"></i></button>
                                    @endif
                                    @if($can('LIGUE_DELETE'))
                                        <form action="{{ route('admin.ligues.destroy', $l) }}" method="POST" class="d-inline" onsubmit="return dojoConfirmDelete(this);">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button></form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">{{ __('messages.no_results') }}</td></tr>
                        @endforelse
                        </tbody>
                    </table></div>
                </div></div>
            </div>
            @endif

            {{-- SALLES --}}
            @if($can('SALLE_READ'))
            <div class="tab-pane fade {{ $activeTab === 'salles' ? 'show active' : '' }}" id="tab-salles" role="tabpanel">
                <div class="card border-0 shadow-sm"><div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0"><i class="fas fa-dumbbell me-2"></i> {{ __('messages.salles.title') }}</h6>
                        @if($can('SALLE_CREATE'))
                            <button type="button" class="btn btn-sm text-white js-open" style="background-color: var(--navbar-bg);" data-modal="#m-salle" data-mode="create" data-action="{{ route('admin.salles.store') }}"><i class="fas fa-plus me-1"></i> {{ __('messages.add') }}</button>
                        @endif
                    </div>
                    <div class="table-responsive"><table class="table table-hover table-sm align-middle mb-0">
                        <thead class="table-light"><tr><th>{{ __('messages.salles.name') }}</th><th>{{ __('messages.salles.ligue') }}</th><th>{{ __('messages.salles.maitre') }}</th><th class="text-center">{{ __('messages.salles.disciples_count') }}</th><th class="text-end">{{ __('messages.actions') }}</th></tr></thead>
                        <tbody>
                        @forelse($salles as $s)
                            <tr>
                                <td class="fw-semibold">{{ $s->nom }}</td><td>{{ $s->ligue?->nom ?? '-' }}</td><td>{{ $s->maitre_display_name ?? '-' }}</td><td class="text-center">{{ $s->disciples_count }}</td>
                                <td class="text-end">
                                    @if($can('SALLE_UPDATE'))
                                        @php $plSalle = ['nom'=>$s->nom,'telephone'=>$s->telephone,'ligue_id'=>$s->ligue_id,'maitre_id'=>$s->maitre_id,'mensualite'=>$s->mensualite,'adresse'=>$s->adresse,'active'=>$s->active ? 1 : 0]; @endphp
                                        <button type="button" class="btn btn-sm btn-outline-primary js-open" data-modal="#m-salle" data-mode="edit" data-action="{{ route('admin.salles.update', $s) }}" data-payload='@json($plSalle)'><i class="fas fa-edit"></i></button>
                                    @endif
                                    @if($can('SALLE_DELETE'))
                                        <form action="{{ route('admin.salles.destroy', $s) }}" method="POST" class="d-inline" onsubmit="return dojoConfirmDelete(this);">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button></form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">{{ __('messages.no_results') }}</td></tr>
                        @endforelse
                        </tbody>
                    </table></div>
                </div></div>
            </div>
            @endif

            {{-- GRADES --}}
            @if($can('GRADES_READ'))
            <div class="tab-pane fade {{ $activeTab === 'grades' ? 'show active' : '' }}" id="tab-grades" role="tabpanel">
                <div class="card border-0 shadow-sm"><div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0"><i class="fas fa-medal me-2"></i> {{ __('messages.grades.title') }}</h6>
                        @if($can('GRADES_CREATE'))
                            <button type="button" class="btn btn-sm text-white js-open" style="background-color: var(--navbar-bg);" data-modal="#m-grade" data-mode="create" data-action="{{ route('admin.grades.store') }}"><i class="fas fa-plus me-1"></i> {{ __('messages.add') }}</button>
                        @endif
                    </div>
                    <div class="table-responsive"><table class="table table-hover table-sm align-middle mb-0">
                        <thead class="table-light"><tr><th>{{ __('messages.grades.level') }}</th><th>{{ __('messages.grades.name') }}</th><th>{{ __('messages.grades.belt') }}</th><th>{{ __('messages.grades.type') }}</th><th class="text-end">{{ __('messages.actions') }}</th></tr></thead>
                        <tbody>
                        @forelse($grades as $g)
                            <tr>
                                <td>{{ $g->niveau }}</td><td class="fw-semibold">{{ $g->nom_grade }}</td><td>{{ $g->ceinture }}</td><td><span class="badge bg-{{ $g->type_grade === 'DAN' ? 'dark' : 'info' }}">{{ $g->type_grade }}</span></td>
                                <td class="text-end">
                                    @if($can('GRADES_UPDATE'))
                                        @php $plGrade = ['niveau'=>$g->niveau,'nom_grade'=>$g->nom_grade,'ceinture'=>$g->ceinture,'type_grade'=>$g->type_grade,'federation_id'=>$g->federation_id]; @endphp
                                        <button type="button" class="btn btn-sm btn-outline-primary js-open" data-modal="#m-grade" data-mode="edit" data-action="{{ route('admin.grades.update', $g) }}" data-payload='@json($plGrade)'><i class="fas fa-edit"></i></button>
                                    @endif
                                    @if($can('GRADES_DELETE'))
                                        <form action="{{ route('admin.grades.destroy', $g) }}" method="POST" class="d-inline" onsubmit="return dojoConfirmDelete(this);">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button></form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">{{ __('messages.no_results') }}</td></tr>
                        @endforelse
                        </tbody>
                    </table></div>
                </div></div>
            </div>
            @endif

            {{-- ASSIGNER PERMISSION (patron KalanNet) --}}
            @if($can('PERMISSION_READ'))
            @php
                $target = request('user_id') ? $assignableUsers->firstWhere('id', (int) request('user_id')) : null;
                $targetIds = $target ? $target->permissions->pluck('id')->map(fn ($i) => (int) $i)->all() : [];
                $totalPerms = $permissions->count();
                $canAssign = $can('PERMISSION_ASSIGN') || $can('PERMISSION_MANAGE');
            @endphp
            <div class="tab-pane fade {{ $activeTab === 'permissions' ? 'show active' : '' }}" id="tab-permissions" role="tabpanel">
                {{-- Sélecteur d'utilisateur (recharge la page) --}}
                <div class="card border-0 shadow-sm mb-3"><div class="card-body">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                        <h6 class="mb-0"><i class="fas fa-user-check me-2"></i> {{ __('messages.parametres.assign_title') }}</h6>
                        @if($target)
                            <span class="badge fs-6 px-3 py-2" style="background-color: var(--navbar-bg); color: var(--navbar-text);" id="permCount" data-total="{{ $totalPerms }}" data-suffix="{{ __('messages.parametres.checked_count') }}">{{ count($targetIds) }} / {{ $totalPerms }} {{ __('messages.parametres.checked_count') }}</span>
                        @endif
                    </div>
                    <form method="GET" action="{{ route('admin.settings') }}" id="permSelectForm" class="row g-2 align-items-end">
                        <input type="hidden" name="tab" value="permissions">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">{{ __('messages.parametres.assign_to') }}</label>
                            <input type="text" id="userFilter" class="form-control mb-2" placeholder="{{ __('messages.parametres.filter_users') }}">
                            <select name="user_id" id="permUserSelect" class="form-select" required>
                                <option value="">{{ __('messages.choose_user') }}</option>
                                @foreach($assignableUsers as $usr)
                                    <option value="{{ $usr->id }}" {{ (string) request('user_id') === (string) $usr->id ? 'selected' : '' }}>{{ $usr->name }} — {{ __('messages.roles.' . $usr->role) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">{{ __('messages.parametres.filter_permissions') }}</label>
                            <input type="text" id="permissionFilter" class="form-control" placeholder="{{ __('messages.parametres.filter_permissions') }}">
                        </div>
                    </form>
                </div></div>

                @if($target)
                    <form method="POST" action="{{ route('admin.permissions.assign') }}" id="permAssignForm">
                        @csrf
                        <input type="hidden" name="user_id" value="{{ $target->id }}">
                        <input type="hidden" name="tab" value="permissions">
                        <div class="card border-0 shadow-sm mb-3"><div class="card-body d-flex flex-wrap gap-3 align-items-center justify-content-between">
                            <div><strong>{{ $target->name }}</strong> <span class="text-muted small">— {{ __('messages.roles.' . $target->role) }}</span></div>
                            <div class="d-flex align-items-center gap-3">
                                <div class="form-check m-0">
                                    <input class="form-check-input" type="checkbox" id="permSelectAll" {{ $canAssign ? '' : 'disabled' }}>
                                    <label class="form-check-label fw-bold" for="permSelectAll">{{ __('messages.parametres.toggle_all') }}</label>
                                </div>
                                @if($canAssign)
                                    <button type="submit" class="btn text-white" style="background-color: var(--navbar-bg);"><i class="fas fa-save me-1"></i> {{ __('messages.save') }}</button>
                                @endif
                            </div>
                        </div></div>

                        <div class="row g-3">
                            @foreach($permissionsByModule as $module => $perms)
                                <div class="col-12">
                                    <div class="card border-0 shadow-sm perm-module-card">
                                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                            <div class="fw-bold"><i class="fas fa-folder me-2 text-muted"></i>{{ $module }} <span class="badge bg-secondary ms-1">{{ count($perms) }}</span></div>
                                            <div class="form-check m-0">
                                                <input class="form-check-input perm-module-toggle" type="checkbox" id="mod-{{ $loop->index }}" data-module="{{ $loop->index }}" {{ $canAssign ? '' : 'disabled' }}>
                                                <label class="form-check-label small" for="mod-{{ $loop->index }}">{{ __('messages.parametres.whole_module') }}</label>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <div class="row g-2">
                                                @foreach($perms as $perm)
                                                    <div class="col-md-6 col-lg-4 perm-item">
                                                        <div class="form-check border rounded p-2 h-100 perm-check-box">
                                                            <input class="form-check-input perm-checkbox" type="checkbox" name="permissions[]" value="{{ $perm->id }}" id="perm-{{ $perm->id }}" data-module="{{ $loop->parent->index }}" {{ in_array((int) $perm->id, $targetIds, true) ? 'checked' : '' }} {{ $canAssign ? '' : 'disabled' }}>
                                                            <label class="form-check-label small" for="perm-{{ $perm->id }}">{{ $perm->name }}<code class="d-block text-muted" style="font-size:.7rem;">{{ $perm->slug }}</code></label>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </form>
                @else
                    <div class="card border-0 shadow-sm"><div class="card-body text-center text-muted py-5">
                        <i class="fas fa-user-check d-block mb-3" style="font-size: 2.5rem; opacity:.3;"></i>
                        {{ __('messages.parametres.select_user_prompt') }}
                    </div></div>
                @endif
            </div>
            @endif

        </div>
    </div>
</div>

{{-- ============================ MODALS ============================ --}}
@include('admin.settings._modals', ['roleOptions' => $roleOptions ?? [], 'federations' => $federations, 'ligues' => $ligues, 'salles' => $salles, 'maitres' => $maitres])
@endif
@endsection

@push('styles')
@endpush

@section('js')
<style>
    /* Hover du menu Paramètres = couleur du header (navbar) */
    .parametres-menu .list-group-item-action { border: none; color: var(--body-text); transition: background-color .15s ease, color .15s ease; }
    .parametres-menu .list-group-item-action:hover,
    .parametres-menu .list-group-item-action:focus { background-color: var(--navbar-bg); color: var(--navbar-text); }
    .parametres-menu .list-group-item-action.active { background-color: var(--navbar-bg); border-color: var(--navbar-bg); color: var(--navbar-text); }
    .modal-header.dojo-modal-header { background-color: var(--navbar-bg); color: var(--navbar-text); }
</style>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Options de fonction par rôle cible (président fédération/ligue, délégués…), fidèle à getFunctionOptions.
    var USER_FUNCTION_OPTIONS = @json($functionOptions ?? []);

    // Reconstruit la liste des fonctions selon le rôle sélectionné.
    function updateFonctionOptions(role) {
        var wrap = document.querySelector('#m-user .js-fonction-wrap');
        if (!wrap) return;
        var sel = wrap.querySelector('select[name="fonction"]');
        var opts = USER_FUNCTION_OPTIONS[role] || [];
        var current = sel.value;
        sel.innerHTML = '<option value="">{{ __('messages.parametres.function_none') }}</option>';
        opts.forEach(function (f) {
            var o = document.createElement('option'); o.value = f; o.textContent = f; sel.appendChild(o);
        });
        if (opts.indexOf(current) !== -1) sel.value = current;
        wrap.style.display = opts.length ? '' : 'none';
    }

    // Affiche uniquement le champ de périmètre pertinent selon le rôle choisi.
    function updateUserScope() {
        var userModal = document.querySelector('#m-user');
        if (!userModal) return;
        var role = (userModal.querySelector('select[name="role"]') || {}).value || '';
        userModal.querySelectorAll('.js-scope').forEach(function (block) {
            var roles = (block.dataset.role || '').split(',');
            var match = roles.indexOf(role) !== -1;
            block.style.display = match ? '' : 'none';
            var field = block.querySelector('select');
            if (field) {
                field.required = match && !block.dataset.optional;
                if (!match) field.value = '';
            }
        });
        updateFonctionOptions(role);
    }

    var userRoleSelect = document.querySelector('#m-user select[name="role"]');
    if (userRoleSelect) userRoleSelect.addEventListener('change', updateUserScope);

    document.querySelectorAll('.js-open').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var modalEl = document.querySelector(this.dataset.modal);
            if (!modalEl) return;
            var form = modalEl.querySelector('.js-modal-form');
            form.reset();
            form.action = this.dataset.action;
            var mi = form.querySelector('input[name="_method"]');
            if (mi) mi.value = this.dataset.mode === 'edit' ? 'PUT' : 'POST';
            form.querySelectorAll('input[type=checkbox]').forEach(function (c) { c.checked = false; });
            var titleEl = modalEl.querySelector('.js-modal-title');
            if (titleEl) titleEl.textContent = this.dataset.mode === 'edit' ? titleEl.dataset.edit : titleEl.dataset.create;
            var pw = form.querySelector('input[name="password"]');
            if (pw) { pw.value = ''; pw.required = this.dataset.mode !== 'edit'; }
            var payload = this.dataset.payload ? JSON.parse(this.dataset.payload) : {};
            Object.keys(payload).forEach(function (k) {
                var el = form.querySelector('[name="' + k + '"]');
                if (!el) return;
                var v = payload[k];
                if (el.type === 'checkbox') el.checked = !!Number(v);
                else el.value = (v === null || v === undefined) ? '' : v;
            });
            if (modalEl.id === 'm-user') {
                updateUserScope();
                // Réapplique fonction/grade une fois les options reconstruites (mode édition).
                ['fonction', 'grade_id'].forEach(function (k) {
                    if (payload[k] !== undefined && payload[k] !== null && payload[k] !== '') {
                        var el = form.querySelector('[name="' + k + '"]');
                        if (el) el.value = payload[k];
                    }
                });
            }
            (bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl)).show();
        });
    });

    /* ---------- Onglet Assigner Permission (patron KalanNet) ---------- */
    function norm(v) { return String(v || '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, ''); }

    var permUserSelect = document.getElementById('permUserSelect');
    if (permUserSelect) {
        permUserSelect.addEventListener('change', function () {
            if (permUserSelect.value) document.getElementById('permSelectForm').submit();
        });
    }

    var userFilter = document.getElementById('userFilter');
    if (userFilter && permUserSelect) {
        userFilter.addEventListener('input', function () {
            var term = norm(this.value);
            Array.from(permUserSelect.options).forEach(function (opt, i) {
                if (i === 0) return;
                opt.hidden = term !== '' && norm(opt.textContent).indexOf(term) === -1;
            });
        });
    }

    var permBoxes = Array.from(document.querySelectorAll('.perm-checkbox'));
    var moduleToggles = Array.from(document.querySelectorAll('.perm-module-toggle'));
    var selectAll = document.getElementById('permSelectAll');
    var countBadge = document.getElementById('permCount');

    function syncModule(mod) {
        var items = permBoxes.filter(function (b) { return b.dataset.module === mod; });
        var mBox = document.querySelector('.perm-module-toggle[data-module="' + mod + '"]');
        if (!mBox || !items.length) return;
        var checked = items.filter(function (b) { return b.checked; }).length;
        mBox.checked = checked === items.length;
        mBox.indeterminate = checked > 0 && checked < items.length;
    }
    function syncAll() {
        var checked = permBoxes.filter(function (b) { return b.checked; }).length;
        if (selectAll) {
            selectAll.checked = checked === permBoxes.length && permBoxes.length > 0;
            selectAll.indeterminate = checked > 0 && checked < permBoxes.length;
        }
        moduleToggles.forEach(function (m) { syncModule(m.dataset.module); });
        if (countBadge) countBadge.textContent = checked + ' / ' + countBadge.dataset.total + ' ' + countBadge.dataset.suffix;
    }
    if (selectAll) selectAll.addEventListener('change', function () { permBoxes.forEach(function (b) { if (!b.disabled) b.checked = selectAll.checked; }); syncAll(); });
    moduleToggles.forEach(function (m) {
        m.addEventListener('change', function () {
            permBoxes.filter(function (b) { return b.dataset.module === m.dataset.module && !b.disabled; }).forEach(function (b) { b.checked = m.checked; });
            syncAll();
        });
    });
    permBoxes.forEach(function (b) { b.addEventListener('change', syncAll); });
    if (permBoxes.length) syncAll();

    var permFilter = document.getElementById('permissionFilter');
    if (permFilter) {
        permFilter.addEventListener('input', function () {
            var term = norm(this.value);
            document.querySelectorAll('.perm-module-card').forEach(function (card) {
                var anyVisible = false;
                card.querySelectorAll('.perm-item').forEach(function (item) {
                    var match = norm(item.textContent).indexOf(term) !== -1;
                    item.style.display = match ? '' : 'none';
                    if (match) anyVisible = true;
                });
                card.closest('.col-12').style.display = anyVisible ? '' : 'none';
            });
        });
    }
});
</script>
@endsection
