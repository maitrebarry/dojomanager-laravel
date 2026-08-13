@extends('layouts.admin')

@section('content')
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

{{-- Cartes statistiques par rôle --}}
<div class="row g-3 mb-2">
    @forelse($cards ?? [] as $card)
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card border-start border-4 border-{{ $card['tone'] }} shadow-sm h-100">
                <div class="card-body">
                    <div class="text-secondary small">{{ $card['title'] }}</div>
                    <div class="fs-4 fw-semibold">{{ $card['value'] }}</div>
                    <div class="small mt-2 text-{{ $card['tone'] === 'dark' ? 'muted' : $card['tone'] }}">{{ $card['subtitle'] }}</div>
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
                                        <td style="color:#0f172a;">{{ $row[$col] ?? '-' }}</td>
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
