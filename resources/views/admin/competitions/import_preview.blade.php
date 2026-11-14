@extends('layouts.admin')

@section('title', __('messages.competitions.import_preview'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.competitions.index') }}">{{ __('messages.competitions.title') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.competitions.show', $competition) }}">{{ $competition->nom }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ __('messages.competitions.import_preview') }}</li>
@endsection

@php
    $errorCount = collect($rows)->filter(fn ($r) => $r['errors'])->count();
@endphp

@section('content')
<form method="POST" action="{{ route('admin.competitions.athletes.confirm', $competition) }}">
    @csrf
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body d-flex flex-wrap gap-3 align-items-end">
            <div>
                <div class="fw-semibold">{{ count($rows) }} ligne(s) lue(s)
                    @if($errorCount)<span class="badge bg-danger ms-1">{{ $errorCount }} {{ mb_strtolower(__('messages.competitions.import_errors')) }}</span>@endif
                </div>
                <small class="text-muted">{{ __('messages.competitions.import_preview_help') }}</small>
            </div>
            <div class="ms-auto" style="min-width: 260px;">
                <label class="form-label small mb-1">{{ __('messages.competitions.import_group') }}</label>
                <select name="groupe" class="form-select form-select-sm">
                    <option value="">{{ __('messages.competitions.import_group_auto') }}</option>
                    @foreach($groups as $key => $label)<option value="{{ $key }}" @selected($groupe === $key)>{{ $label }}</option>@endforeach
                </select>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="text-center" style="width: 60px;">{{ __('messages.competitions.import_keep') }}</th>
                        <th style="width: 50px;">{{ __('messages.competitions.import_line') }}</th>
                        <th>{{ __('messages.competitions.last_name') }} *</th>
                        <th>{{ __('messages.competitions.first_name') }} *</th>
                        <th style="width: 90px;">{{ __('messages.competitions.weight') }}</th>
                        <th style="width: 70px;">{{ __('messages.competitions.sex') }}</th>
                        <th style="width: 130px;">{{ __('messages.competitions.birth') }}</th>
                        <th>{{ __('messages.competitions.club') }}</th>
                        <th style="width: 110px;">{{ __('messages.competitions.group') }}</th>
                        <th>{{ __('messages.competitions.import_errors') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @php $seen = $existing; @endphp
                    @foreach($rows as $i => $row)
                        @php
                            // Doublon : déjà inscrit, ou déjà plus haut dans la même liste.
                            $identity = \App\Http\Controllers\Admin\CompetitionAthleteController::identity($row['nom'], $row['prenom'], $row['poids']);
                            $duplicate = trim($row['nom']) !== '' && isset($seen[$identity]);
                            $seen[$identity] = true;
                            $has = fn ($word) => collect($row['errors'])->contains(fn ($e) => str_contains($e, $word));
                        @endphp
                        <tr class="{{ $row['errors'] ? 'table-danger' : ($duplicate ? 'table-secondary' : '') }}">
                            <td class="text-center">
                                <input type="hidden" name="rows[{{ $i }}][line]" value="{{ $row['line'] }}">
                                {{-- Décochée, seule la valeur cachée « 1 » est envoyée : la ligne est ignorée. --}}
                                <input type="hidden" name="rows[{{ $i }}][ignore]" value="1">
                                <input type="checkbox" class="form-check-input" name="rows[{{ $i }}][ignore]" value="" @checked(!$duplicate)>
                            </td>
                            <td class="text-muted small">{{ $row['line'] }}</td>
                            <td><input name="rows[{{ $i }}][nom]" value="{{ $row['nom'] }}" class="form-control form-control-sm {{ $has('nom') ? 'is-invalid' : '' }}"></td>
                            <td><input name="rows[{{ $i }}][prenom]" value="{{ $row['prenom'] }}" class="form-control form-control-sm {{ $has('prénom') ? 'is-invalid' : '' }}"></td>
                            <td><input name="rows[{{ $i }}][poids]" value="{{ $row['poids'] }}" class="form-control form-control-sm {{ $has('poids') ? 'is-invalid' : '' }}" inputmode="decimal"></td>
                            <td><input name="rows[{{ $i }}][sexe]" value="{{ $row['sexe'] }}" class="form-control form-control-sm {{ $has('sexe') ? 'is-invalid' : '' }}" maxlength="10"></td>
                            <td><input name="rows[{{ $i }}][naissance]" value="{{ $row['naissance'] }}" class="form-control form-control-sm {{ $has('âge') ? 'is-invalid' : '' }}"></td>
                            <td><input name="rows[{{ $i }}][club]" value="{{ $row['club'] }}" class="form-control form-control-sm"></td>
                            <td><input name="rows[{{ $i }}][groupe]" value="{{ $row['groupe'] ?? '' }}" class="form-control form-control-sm {{ $has('groupe') ? 'is-invalid' : '' }}" placeholder="—"></td>
                            <td class="small">
                                @if($row['errors'])<span class="text-danger">{{ implode(', ', $row['errors']) }}</span>@endif
                                @if($duplicate)<span class="text-muted">{{ __('messages.competitions.import_duplicate') }}</span>@endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white d-flex gap-2">
            <button class="btn btn-success"><i class="fas fa-check me-1"></i> {{ __('messages.competitions.import_confirm') }}</button>
            <button class="btn btn-light" form="cancelImport">{{ __('messages.competitions.import_cancel') }}</button>
        </div>
    </div>
</form>
<form method="POST" id="cancelImport" action="{{ route('admin.competitions.athletes.cancel-import', $competition) }}">@csrf</form>
@endsection
