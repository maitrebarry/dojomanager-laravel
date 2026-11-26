@extends('layouts.admin')

@section('actions')
    @if($salle)
        <a href="{{ route('admin.pratiquants.fiche.excel', $salle) }}" class="btn btn-outline-success shadow-sm"><i class="fas fa-file-excel me-1"></i> {{ __('messages.pratiquants.fiche_excel') }}</a>
        <a href="{{ route('admin.pratiquants.fiche', ['salle' => $salle->id]) }}" target="_blank" class="btn btn-outline-dark shadow-sm"><i class="fas fa-file-pdf me-1"></i> {{ __('messages.pratiquants.fiche_pdf') }}</a>
    @endif
    <a href="{{ route('admin.pratiquants.index', array_filter(['salle' => $salle?->id])) }}" class="btn btn-light shadow-sm"><i class="fas fa-arrow-left me-1"></i> {{ __('messages.pratiquants.back') }}</a>
@endsection

@section('content')
{{-- Saisie rapide de la fiche remplie par le maître : mêmes colonnes que le PDF, Entrée = case suivante. --}}
<form method="GET" class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <label class="fw-semibold">{{ __('messages.pratiquants.photos_salle') }}</label>
    <select name="salle" class="form-select w-auto" onchange="this.form.submit()">
        <option value="">—</option>
        @foreach($salles as $s)<option value="{{ $s->id }}" @selected($salle?->id === $s->id)>{{ $s->nom }}</option>@endforeach
    </select>
    @if($salle)<span class="text-muted small">{{ __('messages.pratiquants.saisie_maitre') }} : <strong>{{ $salle->maitre_display_name ?: '—' }}</strong></span>@endif
</form>

@if(!$salle)
    <div class="alert alert-info">{{ __('messages.pratiquants.photos_choose_salle') }}</div>
@else
{{-- Fiche Excel renvoyée par le maître : import direct (disciples + onglet Maître). --}}
<form method="POST" action="{{ route('admin.pratiquants.saisie.import', $salle) }}" enctype="multipart/form-data" class="card border-0 shadow-sm mb-3">
    @csrf
    <div class="card-body d-flex flex-wrap align-items-center gap-2">
        <span class="fw-semibold"><i class="fas fa-file-import me-1 text-success"></i> {{ __('messages.pratiquants.import_title') }}</span>
        <input type="file" name="fichier" accept=".xlsx,.xls,.ods" class="form-control form-control-sm w-auto" required>
        <button class="btn btn-sm btn-success"><i class="fas fa-upload me-1"></i> {{ __('messages.pratiquants.import_submit') }}</button>
        <small class="text-muted w-100">{{ __('messages.pratiquants.import_help') }}</small>
    </div>
</form>
<p class="small text-muted">{{ __('messages.pratiquants.saisie_help') }}</p>
<form method="POST" action="{{ route('admin.pratiquants.saisie.save', $salle) }}" id="saisieForm">
    @csrf
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0 saisie">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th style="min-width: 120px;">{{ __('messages.first_name') }}</th>
                        <th style="min-width: 110px;">{{ __('messages.last_name') }}</th>
                        <th style="width: 70px;">Sexe</th>
                        <th style="min-width: 110px;">Naissance</th>
                        <th style="min-width: 120px;">Lieu</th>
                        <th style="min-width: 150px;">Adresse</th>
                        <th style="min-width: 110px;">Téléphone</th>
                        <th style="min-width: 120px;">Grade</th>
                        <th style="min-width: 110px;">Date du grade</th>
                    </tr>
                </thead>
                <tbody>
                    @php $rows = $disciples->map(fn ($d) => ['key' => $d->id, 'd' => $d])->concat(collect(range(1, $blankRows))->map(fn ($n) => ['key' => 'new' . $n, 'd' => null])); @endphp
                    @foreach($rows as $i => $r)
                        @php $d = $r['d']; $k = $r['key']; @endphp
                        <tr class="{{ $d ? '' : 'table-warning' }}">
                            <td class="small text-muted">{{ $d ? ($d->nmle ?: $i + 1) : '+' }}</td>
                            <td><input class="form-control form-control-sm" name="rows[{{ $k }}][prenom]" value="{{ $d?->prenom }}" @if($d) required @endif></td>
                            <td><input class="form-control form-control-sm" name="rows[{{ $k }}][nom]" value="{{ $d?->nom }}" @if($d) required @endif></td>
                            <td><select class="form-select form-select-sm" name="rows[{{ $k }}][sexe]"><option value=""></option><option @selected($d?->sexe === 'M')>M</option><option @selected($d?->sexe === 'F')>F</option></select></td>
                            <td><input class="form-control form-control-sm" name="rows[{{ $k }}][date_naissance]" value="{{ $d?->date_naissance?->format('d/m/Y') }}" placeholder="jj/mm/aaaa" inputmode="numeric"></td>
                            <td><input class="form-control form-control-sm" name="rows[{{ $k }}][lieu]" value="{{ $d ? preg_replace('/^.*\s+à\s+/iu', '', (string) $d->date_lieu_naissance) : '' }}"></td>
                            <td><input class="form-control form-control-sm" name="rows[{{ $k }}][adresse]" value="{{ $d?->adresse }}"></td>
                            <td><input class="form-control form-control-sm" name="rows[{{ $k }}][telephone]" value="{{ $d?->telephone }}" inputmode="tel"></td>
                            <td><select class="form-select form-select-sm" name="rows[{{ $k }}][grade_id]"><option value=""></option>@foreach($grades as $g)<option value="{{ $g->id }}" @selected($d?->grade_id === $g->id)>{{ $g->nom_grade }}</option>@endforeach</select></td>
                            <td><input class="form-control form-control-sm" name="rows[{{ $k }}][date_grade]" value="{{ $d?->date_obtention_grade?->format('d/m/Y') }}" placeholder="jj/mm/aaaa" inputmode="numeric"></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white d-flex flex-wrap align-items-center gap-2 position-sticky bottom-0">
            <button class="btn btn-primary"><i class="fas fa-save me-1"></i> {{ __('messages.save') }}</button>
            <small class="text-muted">{{ __('messages.pratiquants.saisie_footer') }}</small>
        </div>
    </div>
</form>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Entrée = case suivante (comme sur la feuille), pas l'envoi du formulaire.
    var form = document.getElementById('saisieForm');
    var fields = Array.from(form.querySelectorAll('tbody input, tbody select'));
    form.addEventListener('keydown', function (e) {
        if (e.key !== 'Enter' || e.target.tagName === 'BUTTON') { return; }
        e.preventDefault();
        var next = fields[fields.indexOf(e.target) + 1];
        if (next) { next.focus(); if (next.select) { next.select(); } }
    });
});
</script>
@endif
@endsection
