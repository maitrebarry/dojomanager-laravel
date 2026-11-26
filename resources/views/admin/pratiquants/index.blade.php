@extends('layouts.admin')

@section('actions')
    @if(Auth::user()->hasPermission('DISCIPLE_CREATE') && !empty(Auth::user()->scopeContext()['ligue_id']))
        <a href="{{ route('admin.pratiquants.register') }}" class="btn btn-warning shadow-sm"><i class="fas fa-user-plus me-1"></i> {{ __('messages.pratiquants.register') }}</a>
    @endif
    <a href="{{ route('admin.pratiquants.maitres') }}" class="btn btn-outline-primary shadow-sm"><i class="fas fa-user-tie me-1"></i> {{ __('messages.pratiquants.maitres_title') }}</a>
    <a href="{{ route('admin.pratiquants.fiche', array_filter(['salle' => request('salle')])) }}" target="_blank" class="btn btn-outline-dark shadow-sm"><i class="fas fa-file-pdf me-1"></i> {{ request('salle') ? __('messages.pratiquants.fiche_pdf') : __('messages.pratiquants.fiche_all_pdf') }}</a>
    @if(request('salle'))
        <a href="{{ route('admin.pratiquants.fiche.excel', request('salle')) }}" class="btn btn-outline-success shadow-sm"><i class="fas fa-file-excel me-1"></i> {{ __('messages.pratiquants.fiche_excel') }}</a>
    @endif
    @if(Auth::user()->hasPermission('DISCIPLE_UPDATE'))
        <a href="{{ route('admin.pratiquants.saisie', array_filter(['salle' => request('salle')])) }}" class="btn btn-outline-primary shadow-sm"><i class="fas fa-keyboard me-1"></i> {{ __('messages.pratiquants.saisie_title') }}</a>
    @endif
    <a href="{{ route('admin.pratiquants.photos', array_filter(['salle' => request('salle')])) }}" class="btn btn-dark shadow-sm"><i class="fas fa-camera me-1"></i> {{ __('messages.pratiquants.photos') }}</a>
    <a href="{{ route('admin.pratiquants.export', request()->query()) }}" class="btn btn-outline-success shadow-sm"><i class="fas fa-file-excel me-1"></i> {{ __('messages.pratiquants.export') }}</a>
@endsection

@section('content')
{{-- Pratiquants de la circonscription : consultation, export, cartes de licence, photos. --}}
<p class="text-muted small">{{ __('messages.pratiquants.subtitle') }}</p>

{{-- Salles avec maître / sans maître (créées d'après le club des combattants) : choix en haut,
     et liste déroulante des salles avec leur nombre de pratiquants dans les filtres. --}}
@php
    $mastered = $salles->where('has_master', true);
    $orphans = $salles->where('has_master', false);
    $sum = fn ($list, $field) => $list->sum(fn ($s) => $perSalle[$s->id]->{$field} ?? 0);
    $mode = request('salles');
@endphp
{{-- Choix toujours visible : salles avec maître (par défaut), sans maître, toutes. --}}
<div class="d-flex flex-wrap align-items-center gap-2 mb-3 small">
    <div class="btn-group btn-group-sm" role="group">
        <a href="{{ route('admin.pratiquants.index') }}" class="btn {{ !$mode && !request('salle') ? 'btn-primary' : 'btn-outline-primary' }}"><i class="fas fa-user-tie me-1"></i> {{ __('messages.pratiquants.filter_with_master', ['count' => $sum($mastered, 'total')]) }}</a>
        <a href="{{ route('admin.pratiquants.index', ['salles' => 'sans']) }}" class="btn {{ $mode === 'sans' ? 'btn-secondary' : 'btn-outline-secondary' }}"><i class="fas fa-user-slash me-1"></i> {{ __('messages.pratiquants.filter_without_master', ['count' => $sum($orphans, 'total'), 'salles' => $orphans->count()]) }}</a>
        <a href="{{ route('admin.pratiquants.index', ['salles' => 'aucune']) }}" class="btn {{ $mode === 'aucune' ? 'btn-warning' : 'btn-outline-warning text-dark' }}"><i class="fas fa-circle-question me-1"></i> {{ __('messages.pratiquants.filter_no_salle', ['count' => $withoutSalle->count()]) }}</a>
        <a href="{{ route('admin.pratiquants.index', ['salles' => 'toutes']) }}" class="btn {{ $mode === 'toutes' ? 'btn-dark' : 'btn-outline-dark' }}">{{ __('messages.pratiquants.filter_all', ['count' => $sum($salles, 'total') + $withoutSalle->count()]) }}</a>
    </div>
    <span class="text-muted w-100">{{ $orphans->isEmpty() ? __('messages.pratiquants.no_salles_without_master') : __('messages.pratiquants.salles_without_master_help') }}</span>
</div>

<form method="GET" class="card border-0 shadow-sm mb-3" id="filterForm">
    @if(request('salles'))<input type="hidden" name="salles" value="{{ request('salles') }}">@endif
    <div class="card-body row g-2 align-items-end">
        <div class="col-md-3">
            {{-- Une salle choisie remplace le choix « avec / sans maître / sans salle ». --}}
            <select name="salle" class="form-select form-select-sm js-auto" onchange="var h = this.form.querySelector('input[name=salles]'); if (h && this.value) { h.remove(); }">
                <option value="">{{ __('messages.pratiquants.all_salles') }}</option>
                @foreach([__('messages.pratiquants.salles_with_master_group') => $mastered, __('messages.pratiquants.salles_without_master_group') => $orphans] as $label => $list)
                    @if($list->isNotEmpty())
                        <optgroup label="{{ $label }}">
                            @foreach($list as $salle)
                                @php $stat = $perSalle[$salle->id] ?? null; @endphp
                                <option value="{{ $salle->id }}" @selected((int) request('salle') === $salle->id)>{{ $salle->nom }} ({{ $stat->total ?? 0 }}{{ $stat && $stat->sans_photo ? ' · ' . $stat->sans_photo . ' ' . __('messages.pratiquants.without_photo') : '' }})</option>
                            @endforeach
                        </optgroup>
                    @endif
                @endforeach
            </select>
        </div>
        <div class="col-md-2"><select name="grade" class="form-select form-select-sm js-auto"><option value="">{{ __('messages.pratiquants.all_grades') }}</option>@foreach($grades as $grade)<option value="{{ $grade->id }}" @selected((int) request('grade') === $grade->id)>{{ $grade->nom_grade }}</option>@endforeach</select></div>
        <div class="col-6 col-md-2"><select name="sexe" class="form-select form-select-sm js-auto"><option value="">{{ __('messages.pratiquants.all_sexes') }}</option><option value="M" @selected(request('sexe') === 'M')>M</option><option value="F" @selected(request('sexe') === 'F')>F</option></select></div>
        <div class="col-3 col-md-1"><input type="number" name="age_min" value="{{ request('age_min') }}" min="3" max="99" class="form-control form-control-sm js-auto" placeholder="{{ __('messages.pratiquants.age_min') }}"></div>
        <div class="col-3 col-md-1"><input type="number" name="age_max" value="{{ request('age_max') }}" min="3" max="99" class="form-control form-control-sm js-auto" placeholder="{{ __('messages.pratiquants.age_max') }}"></div>
        <div class="col-6 col-md-2"><select name="photo" class="form-select form-select-sm js-auto"><option value="">{{ __('messages.pratiquants.photo_all') }}</option><option value="sans" @selected(request('photo') === 'sans')>{{ __('messages.pratiquants.photo_without') }}</option><option value="avec" @selected(request('photo') === 'avec')>{{ __('messages.pratiquants.photo_with') }}</option></select></div>
        <div class="col-6 col-md-1"><select name="statut" class="form-select form-select-sm js-auto"><option value="">{{ __('messages.pratiquants.active') }}</option><option value="archives" @selected(request('statut') === 'archives')>{{ __('messages.pratiquants.archived') }}</option></select></div>
        <div class="col-md-9"><input type="search" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="{{ __('messages.pratiquants.search') }}"></div>
        <div class="col-md-3 d-flex gap-2"><button class="btn btn-sm btn-primary flex-fill"><i class="fas fa-filter me-1"></i> OK</button><a href="{{ route('admin.pratiquants.index') }}" class="btn btn-sm btn-light flex-fill">{{ __('messages.pratiquants.reset') }}</a></div>
    </div>
</form>

{{-- Combattants sans club (donc sans salle) : saisir leur club en fait des disciples de cette salle. --}}
@if(in_array($mode, ['aucune', 'toutes'], true) && $withoutSalle->isNotEmpty())
    <form method="POST" action="{{ route('admin.pratiquants.register.store') }}" class="card border-0 shadow-sm mb-3 border-start border-4 border-warning">
        @csrf
        <div class="card-header bg-white fw-semibold"><i class="fas fa-circle-question me-1 text-warning"></i> {{ __('messages.pratiquants.no_salle_title', ['count' => $withoutSalle->count()]) }}
            <small class="text-muted fw-normal d-block">{{ __('messages.pratiquants.no_salle_help') }}</small></div>
        <datalist id="sallesList">@foreach($sallesNames as $name)<option value="{{ $name }}">@endforeach</datalist>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light"><tr><th>{{ __('messages.pratiquants.col_name') }}</th><th class="text-center">{{ __('messages.pratiquants.col_sex') }}</th><th class="text-center">{{ __('messages.pratiquants.col_age') }}</th><th class="text-center">{{ __('messages.pratiquants.register_competitions') }}</th>@if($canFillClub)<th style="width: 35%;">{{ __('messages.pratiquants.register_club') }}</th>@endif</tr></thead>
                <tbody>
                    @foreach($withoutSalle as $p)
                        <tr>
                            <td class="fw-semibold">{{ $p['name'] }}</td>
                            <td class="text-center">{{ $p['sexe'] ?: '-' }}</td>
                            <td class="text-center">{{ $p['age'] ?? '-' }}</td>
                            <td class="text-center">{{ count($p['ids']) }}</td>
                            @if($canFillClub)<td><input type="text" name="clubs[{{ $p['ref'] }}]" list="sallesList" class="form-control form-control-sm" maxlength="150" placeholder="{{ __('messages.pratiquants.register_club_placeholder') }}"></td>@endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($canFillClub)<div class="card-footer bg-white"><button class="btn btn-primary btn-sm"><i class="fas fa-save me-1"></i> {{ __('messages.pratiquants.register_save_clubs') }}</button></div>@endif
    </form>
@elseif($mode === 'aucune')
    <div class="card border-0 shadow-sm mb-3"><div class="card-body text-center text-muted py-4">{{ __('messages.pratiquants.no_salle_none') }}</div></div>
@endif

@if($mode !== 'aucune')
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex flex-wrap align-items-center gap-2">
        <button type="button" class="btn btn-sm btn-outline-secondary" id="selectPage">{{ __('messages.pratiquants.select_page') }}</button>
        <span class="small text-muted" id="selCount">0 {{ __('messages.pratiquants.selected') }}</span>
        <button type="button" class="btn btn-sm btn-primary ms-auto" id="printCards" disabled><i class="fas fa-id-card me-1"></i> {{ __('messages.pratiquants.cards') }} (<span id="printCount">0</span>)</button>
        <span class="small text-muted">{{ $disciples->total() }} {{ mb_strtolower(__('messages.pratiquants.total')) }}</span>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 32px;"></th>
                    <th style="width: 48px;"></th>
                    <th>{{ __('messages.pratiquants.col_name') }}</th>
                    <th class="text-center">{{ __('messages.pratiquants.col_sex') }}</th>
                    <th class="text-center">{{ __('messages.pratiquants.col_age') }}</th>
                    <th>{{ __('messages.pratiquants.col_salle') }}</th>
                    <th>{{ __('messages.pratiquants.col_grade') }}</th>
                    <th>{{ __('messages.pratiquants.col_phone') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($disciples as $d)
                    <tr>
                        <td><input type="checkbox" class="form-check-input js-pick" value="{{ $d->id }}"></td>
                        <td>
                            @if($d->photo_url)
                                <img src="{{ $d->photo_url }}" alt="" class="rounded" style="width: 36px; height: 42px; object-fit: cover;">
                            @else
                                <span class="d-inline-flex align-items-center justify-content-center rounded bg-light text-danger" style="width: 36px; height: 42px;" title="{{ __('messages.pratiquants.photo_without') }}"><i class="fas fa-camera"></i></span>
                            @endif
                        </td>
                        <td class="fw-semibold">{{ $d->prenom }} {{ mb_strtoupper($d->nom) }}@if($d->nmle) <small class="text-muted fw-normal">· {{ $d->nmle }}</small>@endif</td>
                        <td class="text-center">{{ $d->sexe ?: '-' }}</td>
                        <td class="text-center">{{ $d->date_naissance ? $year - $d->date_naissance->year : '-' }}</td>
                        <td>{{ $d->salle?->nom ?? '-' }}</td>
                        <td class="small">{{ $d->grade?->nom_grade ?? '-' }}</td>
                        <td class="small">{{ $d->telephone ?: '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-5">{{ __('messages.pratiquants.none') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($disciples->hasPages())<div class="card-footer bg-white">{{ $disciples->links() }}</div>@endif
</div>

@endif

<script>
document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('filterForm');
    form.querySelectorAll('.js-auto').forEach(function (i) { i.addEventListener('change', function () { form.submit(); }); });

    // Sélection → cartes de licence (régionale pour la ligue, fédérale pour la fédération).
    var checks = Array.from(document.querySelectorAll('.js-pick'));
    var button = document.getElementById('printCards');
    function ids() { return checks.filter(function (c) { return c.checked; }).map(function (c) { return c.value; }); }
    function refresh() {
        var n = ids().length;
        document.getElementById('selCount').textContent = n + ' ' + @js(__('messages.pratiquants.selected'));
        document.getElementById('printCount').textContent = n;
        button.disabled = !n;
    }
    checks.forEach(function (c) { c.addEventListener('change', refresh); });
    document.getElementById('selectPage').addEventListener('click', function () {
        var all = checks.every(function (c) { return c.checked; });
        checks.forEach(function (c) { c.checked = !all; });
        refresh();
    });
    button.addEventListener('click', function () {
        if (ids().length) { window.open(@js(route('admin.licences.disciples')) + '?ids=' + ids().join(','), '_blank'); }
    });
    refresh();
});
</script>
@endsection
