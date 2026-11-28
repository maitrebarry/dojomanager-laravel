@extends('layouts.admin')

@section('content')
{{-- Vue globale de la filiation des salles (ligue : ses salles ; fédération : toutes les ligues). --}}
<p class="text-muted small">{{ __('messages.salles_arbre.help') }}</p>

<div class="row g-2 mb-3">
    @foreach([['fa-house', 'salles'], ['fa-sitemap', 'meres'], ['fa-code-branch', 'affiliees'], ['fa-users', 'disciples']] as [$icon, $key])
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100"><div class="card-body py-2">
                <div class="fs-4 fw-bold"><i class="fas {{ $icon }} text-muted me-1 small"></i>{{ $stats[$key] }}</div>
                <div class="small text-muted">{{ __('messages.salles_arbre.stat_' . $key) }}</div>
            </div></div>
        </div>
    @endforeach
</div>

@if($ligues->count() > 1)
    <form method="GET" class="d-flex flex-wrap align-items-center gap-2 mb-3">
        <select name="ligue" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
            <option value="">{{ __('messages.salles_arbre.all_ligues') }}</option>
            @foreach($ligues as $ligue)<option value="{{ $ligue->id }}" @selected($ligueId === $ligue->id)>{{ $ligue->nom }}</option>@endforeach
        </select>
    </form>
@endif

@forelse($groups as $list)
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white fw-semibold"><i class="fas fa-flag me-1"></i> {{ $list->first()?->ligue?->nom ?? '—' }} <span class="badge text-bg-light border ms-1">{{ $list->count() }}</span></div>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light"><tr><th>{{ __('messages.pratiquants.col_salle') }}</th><th>{{ __('messages.pratiquants.maitre') }}</th><th class="text-center">{{ __('messages.salles_arbre.affiliees') }}</th><th class="text-center">{{ __('messages.pratiquants.total') }}</th></tr></thead>
                <tbody>
                    @foreach($list as $salle)
                        <tr class="{{ ($salle->depth ?? 0) === 0 && $salle->salles_derivees_count ? 'table-light' : '' }}">
                            <td style="padding-left: {{ 10 + ($salle->depth ?? 0) * 24 }}px;">
                                @if($salle->depth ?? 0)<i class="fas fa-level-up-alt fa-rotate-90 text-muted me-1"></i>@elseif($salle->salles_derivees_count)<i class="fas fa-sitemap text-success me-1"></i>@endif
                                <span class="{{ $salle->salles_derivees_count ? 'fw-bold' : 'fw-semibold' }}">{{ $salle->nom }}</span>
                                @if(!$salle->active)<span class="badge text-bg-secondary ms-1">{{ __('messages.inactive') }}</span>@endif
                            </td>
                            <td>{{ $salle->maitre_display_name ?: '—' }}</td>
                            <td class="text-center">{{ $salle->salles_derivees_count ?: '' }}</td>
                            <td class="text-center">{{ $salle->disciples_count }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@empty
    <div class="card border-0 shadow-sm"><div class="card-body text-center text-muted py-5">{{ __('messages.salles.not_found') }}</div></div>
@endforelse
@endsection
