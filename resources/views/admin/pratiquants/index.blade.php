@extends('layouts.admin')

@section('actions')
    @if(Auth::user()->hasPermission('DISCIPLE_CREATE') && !empty(Auth::user()->scopeContext()['ligue_id']))
        <a href="{{ route('admin.pratiquants.register') }}" class="btn btn-warning shadow-sm"><i class="fas fa-user-plus me-1"></i> {{ __('messages.pratiquants.register') }}</a>
    @endif
    <a href="{{ route('admin.pratiquants.photos', array_filter(['salle' => request('salle')])) }}" class="btn btn-dark shadow-sm"><i class="fas fa-camera me-1"></i> {{ __('messages.pratiquants.photos') }}</a>
    <a href="{{ route('admin.pratiquants.export', request()->query()) }}" class="btn btn-outline-success shadow-sm"><i class="fas fa-file-excel me-1"></i> {{ __('messages.pratiquants.export') }}</a>
@endsection

@section('content')
{{-- Pratiquants de la circonscription : consultation, export, cartes de licence, photos. --}}
<p class="text-muted small">{{ __('messages.pratiquants.subtitle') }}</p>

{{-- Totaux par salle : un clic filtre la liste. --}}
<div class="row g-2 mb-3">
    <div class="col-6 col-md-3 col-xl-2">
        <a href="{{ route('admin.pratiquants.index') }}" class="card border-0 shadow-sm text-decoration-none h-100 {{ request('salle') ? '' : 'border-start border-4 border-primary' }}">
            <div class="card-body py-2">
                <div class="fs-4 fw-bold text-dark">{{ $perSalle->sum('total') }}</div>
                <div class="small text-muted">{{ __('messages.pratiquants.total') }} · {{ __('messages.pratiquants.all_salles') }}</div>
                @if($perSalle->sum('sans_photo'))<div class="small text-danger">{{ $perSalle->sum('sans_photo') }} {{ __('messages.pratiquants.without_photo') }}</div>@endif
            </div>
        </a>
    </div>
    @foreach($salles as $salle)
        @php $stat = $perSalle[$salle->id] ?? null; @endphp
        <div class="col-6 col-md-3 col-xl-2">
            <a href="{{ route('admin.pratiquants.index', ['salle' => $salle->id]) }}" class="card border-0 shadow-sm text-decoration-none h-100 {{ (int) request('salle') === $salle->id ? 'border-start border-4 border-primary' : '' }}">
                <div class="card-body py-2">
                    <div class="fs-4 fw-bold text-dark">{{ $stat->total ?? 0 }}</div>
                    <div class="small text-muted text-truncate" title="{{ $salle->nom }}">{{ $salle->nom }}</div>
                    @if($stat && $stat->sans_photo)<div class="small text-danger">{{ $stat->sans_photo }} {{ __('messages.pratiquants.without_photo') }}</div>@endif
                </div>
            </a>
        </div>
    @endforeach
</div>

<form method="GET" class="card border-0 shadow-sm mb-3" id="filterForm">
    <div class="card-body row g-2 align-items-end">
        <div class="col-md-3"><select name="salle" class="form-select form-select-sm js-auto"><option value="">{{ __('messages.pratiquants.all_salles') }}</option>@foreach($salles as $salle)<option value="{{ $salle->id }}" @selected((int) request('salle') === $salle->id)>{{ $salle->nom }}</option>@endforeach</select></div>
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
