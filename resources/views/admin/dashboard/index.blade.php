@extends('layouts.admin')

@section('content')
<style>
    /* Fond plein (dégradé) sur chaque tone Bootstrap : texte clair partout, sauf sur
       bg-warning (jaune) où il reste illisible sans texte foncé. Fonctionne à
       l'identique en clair/sombre puisque la carte ne dépend plus de --card-bg. */
    .dash-stat-card, .dash-stat-card .opacity-75, .dash-stat-card .opacity-50 { color: #fff; }
    .dash-stat-card.bg-warning, .dash-stat-card.bg-warning .opacity-75, .dash-stat-card.bg-warning .opacity-50 { color: #1f2937; }

    /* Pastille icône ronde (modèle wari-nioumas) : cercle blanc plein avec l'icône
       reprenant la couleur de la carte — reste lisible qu'importe le thème puisque le
       cercle ne dépend pas de --card-bg. */
    .dash-stat-card .widgets-icons {
        width: 46px; height: 46px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 20px; flex-shrink: 0; background: #fff;
    }
</style>
@php
    $u = Auth::user();
    $navRole = $u->role->value ?? $u->role;
@endphp

{{-- En-tête : titre + sous-titre + Rôle + Mise à jour + insights (fidèle à Home.jsx) --}}
<div class="card border-top border-0 border-4 border-dark shadow-sm mb-4">
    <div class="card-header bg-transparent d-flex flex-wrap gap-2 justify-content-between align-items-center">
        <div>
            <h5 class="mb-1">{{ $dashboard_title ?? __('messages.dashboard') }}</h5>
            <div class="text-muted small">{{ $subtitle ?? '' }}</div>
        </div>
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <span class="badge bg-light text-dark border">{{ __('messages.role') }}: {{ strtoupper((string) $navRole) }}</span>
            <span class="badge bg-light text-dark border">Mise à jour: {{ $generated_at ?? '-' }}</span>
        </div>
    </div>
    @if(!empty($insights))
        <div class="card-body pt-0">
            <div class="row g-2">
                @foreach($insights as $insight)
                    <div class="col-12 col-lg-6">
                        <div class="border rounded-3 p-3 bg-light h-100">
                            <span class="text-muted small">{{ $insight }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>

{{-- Cartes statistiques par rôle : fonds pleins/dégradés (pas de fond blanc qui
     resterait clair en mode sombre), une icône par indicateur --}}
@php
    $cardIcons = [
        'ligues' => 'fa-sitemap',
        'salles' => 'fa-door-open',
        'disciples' => 'fa-user-ninja',
        'maitres' => 'fa-user-tie',
        'ceintures-noires' => 'fa-medal',
        'finances' => 'fa-money-bill-wave',
        'payees' => 'fa-circle-check',
        'partielles' => 'fa-hourglass-half',
        'attente' => 'fa-triangle-exclamation',
        'sessions' => 'fa-graduation-cap',
    ];
@endphp
<div class="row g-3 mb-2">
    @forelse($cards ?? [] as $card)
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card dash-stat-card bg-{{ $card['tone'] }} bg-gradient border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div>
                        <div class="small opacity-75">{{ $card['title'] }}</div>
                        <div class="fs-4 fw-bold my-1">{{ $card['value'] }}</div>
                        <div class="small opacity-75">{{ $card['subtitle'] }}</div>
                    </div>
                    <div class="widgets-icons text-{{ $card['tone'] }} ms-auto"><i class="fas {{ $cardIcons[$card['key']] ?? 'fa-chart-simple' }}"></i></div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12"><div class="alert alert-info border-0">{{ $subtitle ?? '' }}</div></div>
    @endforelse
</div>

{{-- Tables par rôle (plein-largeur, avec filtre) --}}
@foreach($tables ?? [] as $ti => $table)
    <div class="card border-top border-0 border-4 border-dark shadow-sm mt-4">
        <div class="card-header bg-transparent d-flex flex-wrap gap-2 justify-content-between align-items-center">
            <div>
                <h5 class="mb-1">{{ $table['title'] }}</h5>
                <div class="text-muted small">Filtrez les lignes selon un nom, une date, un lieu ou un statut.</div>
            </div>
            <div class="col-12 col-md-4 col-lg-3">
                <input type="text" class="form-control js-table-filter" data-table="dashTable{{ $ti }}" placeholder="Filtrer ce tableau...">
            </div>
        </div>
        <div class="card-body">
            @if(empty($table['rows']))
                <div class="text-muted py-3">{{ $table['empty'] ?? 'Aucune donnée disponible.' }}</div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="dashTable{{ $ti }}">
                        <thead>
                            <tr>@foreach($table['columns'] as $label)<th class="text-uppercase small text-muted">{{ $label }}</th>@endforeach</tr>
                        </thead>
                        <tbody>
                            @foreach($table['rows'] as $row)
                                <tr class="js-filter-row">
                                    @foreach(array_keys($table['columns']) as $col)
                                        <td>{{ $row[$col] ?? '-' }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endforeach
@endsection

@section('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.js-table-filter').forEach(function (input) {
        input.addEventListener('input', function () {
            var table = document.getElementById(this.dataset.table);
            if (!table) return;
            var q = this.value.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');
            table.querySelectorAll('tbody tr.js-filter-row').forEach(function (row) {
                var text = row.textContent.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');
                row.style.display = text.indexOf(q) !== -1 ? '' : 'none';
            });
        });
    });
});
</script>
@endsection
