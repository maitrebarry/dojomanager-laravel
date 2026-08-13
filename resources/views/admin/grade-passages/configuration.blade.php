@extends('layouts.admin')

@section('title', __('messages.grade_passages.tab_config'))

@php
    $u = Auth::user();
    $roleVal = $u->role->value ?? $u->role;
    $isSuper = $roleVal === 'superadmin';
    // Types disponibles selon le rôle (fidèle à PassageGrades.jsx) :
    //  superadmin → KEUP + DAN · fédération → DAN · ligue → KEUP
    $types = match ($roleVal) {
        'federation' => ['DAN'],
        'ligue' => ['KEUP'],
        default => ['KEUP', 'DAN'],
    };
    $defaultType = $types[0];
    // Qui choisit le périmètre : la fédération (DAN) ne se choisit que par superadmin ;
    // la ligue (KEUP) se choisit par superadmin/fédération.
    $canPickFederation = $isSuper;
    $canPickLigue = in_array($roleVal, ['superadmin', 'federation'], true);
@endphp

@section('content')
{{-- Badges profil / fonction --}}
<div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-3">
    <div></div>
    <div class="d-flex gap-2 flex-wrap">
        <span class="badge bg-primary-subtle text-primary border border-primary">{{ __('messages.grade_passages.profile') }}: {{ $roleVal }}</span>
        <span class="badge bg-light text-dark border">{{ __('messages.parametres.function') }}: {{ $u->fonction ?: __('messages.parametres.function_none') }}</span>
    </div>
</div>

@include('admin.grade-passages._tabs', ['active' => 'config'])

<div class="row g-4">
    {{-- Nouvelle session & annonce --}}
    <div class="col-12 col-xxl-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header card-header-navbar"><h5 class="mb-0">{{ __('messages.grade_passages.new_session_announce') }}</h5></div>
            <div class="card-body">
                <div class="alert alert-info border-0 small py-2 mb-3">
                    <i class="fas fa-circle-info me-1"></i>
                    <span class="js-hint-keup">{{ __('messages.grade_passages.scope_keup_hint') }}</span>
                    <span class="js-hint-dan" style="display:none;">{{ __('messages.grade_passages.scope_dan_hint') }}</span>
                </div>
                <form method="POST" action="{{ route('admin.grade-passages.store') }}" class="row g-3">
                    @csrf
                    <div class="col-6">
                        <label class="form-label">{{ __('messages.grade_passages.date') }}</label>
                        <input type="date" name="date_session" class="form-control" value="{{ old('date_session', now()->toDateString()) }}" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label">{{ __('messages.grade_passages.bareme') }}</label>
                        <select name="bareme" class="form-select" id="cfgBareme">
                            <option value="20">SUR_20</option>
                            <option value="100">SUR_100</option>
                        </select>
                        <input type="hidden" name="type_notation" value="NOTE">
                    </div>
                    <div class="col-12">
                        <label class="form-label">{{ __('messages.grades.type') }}</label>
                        <select name="type_grade" class="form-select" id="cfgType" {{ count($types) === 1 ? 'readonly' : '' }}>
                            @foreach($types as $t)<option value="{{ $t }}">{{ $t }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">{{ __('messages.grade_passages.place') }}</label>
                        <input type="text" name="lieu" class="form-control" placeholder="Gymnase, siège, centre fédéral..." required>
                    </div>
                    <div class="col-6">
                        <label class="form-label">{{ __('messages.grade_passages.fee') }}</label>
                        <input type="number" name="frais_participation" class="form-control" min="0" step="0.01" value="0">
                    </div>
                    @if($canPickLigue)
                        <div class="col-6 js-cfg-ligue" data-type="KEUP">
                            <label class="form-label">{{ __('messages.ligues.title') }} <span class="text-danger">*</span></label>
                            <select name="ligue_id" class="form-select"><option value="">-</option>@foreach($ligues as $l)<option value="{{ $l->id }}">{{ $l->nom }}</option>@endforeach</select>
                        </div>
                    @endif
                    @if($canPickFederation)
                        <div class="col-6 js-cfg-fed" data-type="DAN" style="display:none;">
                            <label class="form-label">{{ __('messages.federations.title') }} <span class="text-danger">*</span></label>
                            <select name="federation_id" class="form-select"><option value="">-</option>@foreach($federations as $f)<option value="{{ $f->id }}">{{ $f->nom }}</option>@endforeach</select>
                        </div>
                    @endif
                    <div class="col-12">
                        <label class="form-label">{{ __('messages.grade_passages.notation_max') }}</label>
                        <input type="text" class="form-control" id="cfgBaremeMax" value="20 {{ __('messages.grade_passages.per_criterion') }}" readonly>
                    </div>
                    <div class="col-12">
                        <label class="form-label">{{ __('messages.grade_passages.announcement') }}</label>
                        <textarea name="annonce" class="form-control" rows="3" placeholder="Informations à afficher aux acteurs concernés"></textarea>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary w-100"><i class="fas fa-plus-circle me-1"></i> {{ __('messages.grade_passages.create_session') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Grille tarifaire (panier : DAN par grade, KEUP par ceinture) --}}
    <div class="col-12 col-xxl-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header card-header-navbar">
                <h5 class="mb-0">{{ __('messages.grade_passages.tariff_grid') }}</h5>
                <div class="small opacity-75">{{ __('messages.grade_passages.tariff_grid_hint') }}</div>
            </div>
            <div class="card-body">
                {{-- Sélecteurs type + périmètre --}}
                <div class="row g-2 align-items-end mb-3">
                    <div class="col-6 col-lg-4">
                        <label class="form-label">{{ __('messages.grade_passages.tariff_type') }}</label>
                        <select class="form-select" id="tarifType" {{ count($types) === 1 ? 'readonly' : '' }}>
                            @foreach($types as $t)<option value="{{ $t }}">{{ $t }}</option>@endforeach
                        </select>
                    </div>
                    @if($canPickFederation)
                        <div class="col-6 col-lg-4 js-tarif-fed" style="display:none;">
                            <label class="form-label">{{ __('messages.federations.title') }}</label>
                            <select class="form-select" id="tarifFederation"><option value="">-</option>@foreach($federations as $f)<option value="{{ $f->id }}">{{ $f->nom }}</option>@endforeach</select>
                        </div>
                    @endif
                    @if($canPickLigue)
                        <div class="col-6 col-lg-4 js-tarif-ligue">
                            <label class="form-label">{{ __('messages.ligues.title') }}</label>
                            <select class="form-select" id="tarifLigue"><option value="">-</option>@foreach($ligues as $l)<option value="{{ $l->id }}">{{ $l->nom }}</option>@endforeach</select>
                        </div>
                    @endif
                </div>

                {{-- DAN : ajout d'un grade au panier --}}
                <div class="mb-3 js-tarif-dan" style="display:none;">
                    <label class="form-label">{{ __('messages.grade_passages.dan_grade_add') }}</label>
                    <select class="form-select" id="danGradeSelect">
                        <option value="">{{ __('messages.grade_passages.choose_candidate') }}</option>
                        @foreach($danGrades as $g)<option value="{{ $g->id }}" data-name="{{ $g->nom_grade }}">{{ $g->nom_grade }}</option>@endforeach
                    </select>
                </div>

                {{-- KEUP : ceintures à basculer dans le panier --}}
                <div class="mb-3 js-tarif-keup">
                    <label class="form-label d-block">{{ __('messages.grade_passages.belts_add') }}</label>
                    <div class="d-flex flex-wrap gap-2" id="beltButtons">
                        @foreach($keupBelts as $belt)
                            <button type="button" class="btn btn-sm btn-outline-secondary js-belt" data-belt="{{ $belt }}">{{ $belt }}</button>
                        @endforeach
                    </div>
                </div>

                {{-- Panier --}}
                <form method="POST" action="{{ route('admin.grade-passage-tariffs.batch') }}" id="tariffCartForm">
                    @csrf
                    <input type="hidden" name="type_grade" id="cartType" value="{{ $defaultType }}">
                    <input type="hidden" name="federation_id" id="cartFederation" value="">
                    <input type="hidden" name="ligue_id" id="cartLigue" value="">
                    <div class="border rounded-3 p-3 bg-light">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="fw-semibold">{{ __('messages.grade_passages.tariff_cart') }}</div>
                            <span class="badge bg-secondary"><span id="tariffCartCount">0</span> {{ __('messages.grade_passages.cart_selections') }}</span>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0" id="tariffCartTable">
                                <thead><tr>
                                    <th>{{ __('messages.grade_passages.tariff_label') }}</th>
                                    <th style="width: 120px;">{{ __('messages.grade_passages.tariff_amount') }}</th>
                                    <th class="text-end" style="width: 48px;"></th>
                                </tr></thead>
                                <tbody id="tariffCartBody">
                                    <tr id="tariffCartEmpty"><td colspan="3" class="text-muted small">{{ __('messages.grade_passages.no_candidate_selected') }}</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3">
                            <button type="submit" id="saveTariffs" class="btn btn-primary" disabled><i class="fas fa-check me-1"></i> {{ __('messages.grade_passages.save_tariffs') }}</button>
                        </div>
                    </div>
                </form>

                <hr class="my-4">

                {{-- Liste des tarifs existants --}}
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light"><tr>
                            <th>{{ __('messages.grade_passages.tariff_type') }}</th>
                            <th>{{ __('messages.grade_passages.tariff_label') }}</th>
                            <th class="text-end">{{ __('messages.grade_passages.tariff_amount') }}</th>
                            <th class="text-end">{{ __('messages.actions') }}</th>
                        </tr></thead>
                        <tbody>
                            @forelse($tariffs as $t)
                                <tr>
                                    <td><span class="badge bg-{{ $t->type_grade === 'DAN' ? 'dark' : 'info' }}">{{ $t->type_grade }}</span></td>
                                    <td>{{ $t->tarif_label }}@if($t->grade) <span class="text-muted small">({{ $t->grade->nom_grade }})</span>@elseif($t->ceinture_keys) <span class="text-muted small">({{ $t->ceinture_keys }})</span>@endif</td>
                                    <td class="text-end fw-semibold">{{ number_format($t->montant, 0, ',', ' ') }}</td>
                                    <td class="text-end">
                                        <form action="{{ route('admin.grade-passage-tariffs.destroy', $t) }}" method="POST" class="d-inline m-0" onsubmit="return confirm('{{ __('messages.confirm_delete') }}');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-3">{{ __('messages.grade_passages.no_tariff') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // ---------- Session : bascule ligue (KEUP) / fédération (DAN) + barème + hint ----------
    var cfgType = document.getElementById('cfgType');
    var cfgBareme = document.getElementById('cfgBareme');
    var cfgBaremeMax = document.getElementById('cfgBaremeMax');
    function cfgToggle() {
        var t = cfgType.value;
        document.querySelectorAll('.js-cfg-ligue').forEach(function (b) { b.style.display = t === 'KEUP' ? '' : 'none'; b.querySelector('select').required = (t === 'KEUP'); });
        document.querySelectorAll('.js-cfg-fed').forEach(function (b) { b.style.display = t === 'DAN' ? '' : 'none'; b.querySelector('select').required = (t === 'DAN'); });
        document.querySelectorAll('.js-hint-keup').forEach(function (e) { e.style.display = t === 'KEUP' ? '' : 'none'; });
        document.querySelectorAll('.js-hint-dan').forEach(function (e) { e.style.display = t === 'DAN' ? '' : 'none'; });
    }
    function baremeMax() { cfgBaremeMax.value = cfgBareme.value + ' {{ __('messages.grade_passages.per_criterion') }}'; }
    if (cfgType) { cfgType.addEventListener('change', cfgToggle); cfgToggle(); }
    if (cfgBareme) { cfgBareme.addEventListener('change', baremeMax); baremeMax(); }

    // ---------- Grille tarifaire : panier ----------
    var tarifType = document.getElementById('tarifType');
    var tarifFed = document.getElementById('tarifFederation');
    var tarifLigue = document.getElementById('tarifLigue');
    var danSelect = document.getElementById('danGradeSelect');
    var beltButtons = document.getElementById('beltButtons');
    var body = document.getElementById('tariffCartBody');
    var empty = document.getElementById('tariffCartEmpty');
    var countEl = document.getElementById('tariffCartCount');
    var saveBtn = document.getElementById('saveTariffs');
    var form = document.getElementById('tariffCartForm');
    var cartType = document.getElementById('cartType');
    var cartFed = document.getElementById('cartFederation');
    var cartLigue = document.getElementById('cartLigue');
    var cart = []; // { key, label, montant, grade_id, ceinture_keys }

    function currentType() { return tarifType ? tarifType.value : '{{ $defaultType }}'; }

    function tarifToggle() {
        var t = currentType();
        document.querySelectorAll('.js-tarif-dan').forEach(function (b) { b.style.display = t === 'DAN' ? '' : 'none'; });
        document.querySelectorAll('.js-tarif-keup').forEach(function (b) { b.style.display = t === 'KEUP' ? '' : 'none'; });
        document.querySelectorAll('.js-tarif-fed').forEach(function (b) { b.style.display = t === 'DAN' ? '' : 'none'; });
        document.querySelectorAll('.js-tarif-ligue').forEach(function (b) { b.style.display = t === 'KEUP' ? '' : 'none'; });
        // changer de type/scope vide le panier
        cart = []; render();
    }

    function syncHidden() {
        cartType.value = currentType();
        cartFed.value = tarifFed ? tarifFed.value : '';
        cartLigue.value = tarifLigue ? tarifLigue.value : '';
    }

    function render() {
        countEl.textContent = cart.length;
        saveBtn.disabled = cart.length === 0;
        empty.style.display = cart.length ? 'none' : '';
        // reconstruire les lignes visibles + inputs cachés
        Array.prototype.slice.call(body.querySelectorAll('tr.js-cart-row')).forEach(function (r) { r.remove(); });
        form.querySelectorAll('.cart-hidden').forEach(function (n) { n.remove(); });
        cart.forEach(function (c, i) {
            var tr = document.createElement('tr');
            tr.className = 'js-cart-row';
            tr.innerHTML = '<td>' + c.label + '</td>' +
                '<td><input type="number" min="0" step="0.01" class="form-control form-control-sm js-cart-montant" value="' + (c.montant || '') + '"></td>' +
                '<td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger js-cart-remove">✕</button></td>';
            tr.querySelector('.js-cart-montant').addEventListener('input', function () { c.montant = this.value; syncInputs(); });
            tr.querySelector('.js-cart-remove').addEventListener('click', function () {
                cart = cart.filter(function (x) { return x.key !== c.key; }); render();
            });
            body.appendChild(tr);
        });
        syncInputs();
    }

    function syncInputs() {
        form.querySelectorAll('.cart-hidden').forEach(function (n) { n.remove(); });
        cart.forEach(function (c, i) {
            var fields = { tarif_label: c.label, montant: c.montant || 0, grade_id: c.grade_id || '', ceinture_keys: c.ceinture_keys || '' };
            Object.keys(fields).forEach(function (k) {
                var input = document.createElement('input');
                input.type = 'hidden'; input.className = 'cart-hidden';
                input.name = 'tariffs[' + i + '][' + k + ']';
                input.value = fields[k];
                form.appendChild(input);
            });
        });
    }

    // DAN : ajout d'un grade
    if (danSelect) danSelect.addEventListener('change', function () {
        if (!this.value) return;
        var key = 'DAN:' + this.value;
        if (cart.some(function (c) { return c.key === key; })) { this.value = ''; return; }
        var o = this.options[this.selectedIndex];
        cart.push({ key: key, label: o.dataset.name, montant: '', grade_id: this.value, ceinture_keys: '' });
        this.value = ''; render();
    });

    // KEUP : toggle des ceintures
    if (beltButtons) beltButtons.querySelectorAll('.js-belt').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var belt = this.dataset.belt;
            var key = 'KEUP:' + belt;
            var idx = cart.findIndex(function (c) { return c.key === key; });
            if (idx > -1) { cart.splice(idx, 1); this.classList.remove('btn-primary'); this.classList.add('btn-outline-secondary'); }
            else { cart.push({ key: key, label: belt, montant: '', grade_id: '', ceinture_keys: belt }); this.classList.add('btn-primary'); this.classList.remove('btn-outline-secondary'); }
            render();
        });
    });

    if (tarifType) tarifType.addEventListener('change', function () { syncHidden(); tarifToggle(); resetBelts(); });
    if (tarifFed) tarifFed.addEventListener('change', function () { syncHidden(); cart = []; resetBelts(); render(); });
    if (tarifLigue) tarifLigue.addEventListener('change', function () { syncHidden(); cart = []; resetBelts(); render(); });

    function resetBelts() { if (beltButtons) beltButtons.querySelectorAll('.js-belt').forEach(function (b) { b.classList.remove('btn-primary'); b.classList.add('btn-outline-secondary'); }); }

    // Validation : montant requis avant envoi
    form.addEventListener('submit', function (e) {
        if (cart.some(function (c) { return c.montant === '' || c.montant === null || Number(c.montant) < 0; })) {
            e.preventDefault();
            if (window.dojoToast) dojoToast('warning', @json(__('messages.grade_passages.tariff_amount')));
        }
    });

    syncHidden(); tarifToggle();
});
</script>
@endsection
