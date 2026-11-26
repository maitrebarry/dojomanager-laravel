@extends('layouts.admin')

@section('actions')
    <a href="{{ route('admin.pratiquants.fiche') }}" target="_blank" class="btn btn-outline-dark shadow-sm"><i class="fas fa-file-pdf me-1"></i> {{ __('messages.pratiquants.fiche_all_pdf') }}</a>
    <a href="{{ route('admin.pratiquants.index') }}" class="btn btn-light shadow-sm"><i class="fas fa-arrow-left me-1"></i> {{ __('messages.pratiquants.back') }}</a>
@endsection

@section('content')
{{-- Maîtres des salles : une ligne par salle ; formulaire rapide pour affecter un maître. --}}
<p class="small text-muted">{{ __('messages.pratiquants.maitres_help') }}</p>
@if(session('maitre_account'))
    <div class="alert alert-success d-flex align-items-center gap-2"><i class="fas fa-key fa-lg"></i> <div>{{ session('maitre_account') }}</div></div>
@endif
@php
    $total = $salles->sum('disciples_count');
    $done = $salles->sum('complete_count');
@endphp
{{-- Avancement du recensement de la ligue --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between small mb-1">
            <strong>{{ __('messages.pratiquants.progress_title') }}</strong>
            <span>{{ __('messages.pratiquants.progress_count', ['done' => $done, 'total' => $total, 'salles' => $salles->count(), 'masters' => $salles->filter(fn ($s) => $s->maitre_id || $s->maitreUser)->count()]) }}</span>
        </div>
        <div class="progress" style="height: 10px;"><div class="progress-bar bg-success" style="width: {{ $total ? round($done / $total * 100) : 0 }}%"></div></div>
        <small class="text-muted">{{ __('messages.pratiquants.progress_help') }}</small>
    </div>
</div>

@if($canCreateSalle)
    {{-- Salle pas encore dans le système : ajout rapide, avec son maître s'il est connu. --}}
    <form method="POST" action="{{ route('admin.pratiquants.salles.store') }}" class="card border-0 shadow-sm mb-3">
        @csrf
        <div class="card-header bg-white fw-semibold"><i class="fas fa-plus me-1"></i> {{ __('messages.pratiquants.salle_add') }}</div>
        <div class="card-body row g-2 align-items-end">
            <div class="col-md-3"><input name="nom" class="form-control form-control-sm" required maxlength="150" placeholder="{{ __('messages.pratiquants.salle_name') }}"></div>
            <div class="col-md-2"><input name="adresse" class="form-control form-control-sm" maxlength="255" placeholder="{{ __('messages.pratiquants.salle_address') }}"></div>
            <div class="col-md-3"><input name="nom_complet" class="form-control form-control-sm" maxlength="120" placeholder="{{ __('messages.pratiquants.maitre_name') }} ({{ __('messages.pratiquants.optional') }})"></div>
            <div class="col-md-2"><input name="telephone" class="form-control form-control-sm" maxlength="20" inputmode="tel" placeholder="{{ __('messages.pratiquants.col_phone') }}"></div>
            <div class="col-md-2"><select name="grade_id" class="form-select form-select-sm"><option value="">{{ __('messages.pratiquants.col_grade') }}</option>@foreach($grades as $g)<option value="{{ $g->id }}">{{ $g->nom_grade }}</option>@endforeach</select></div>
            @if($canAccount)<div class="col-md-9 small"><label class="form-check-label"><input type="checkbox" name="compte" value="1" class="form-check-input me-1"> {{ __('messages.pratiquants.maitre_create_account') }}</label></div>@endif
            <div class="col-md-3"><button class="btn btn-sm btn-primary w-100"><i class="fas fa-save me-1"></i> {{ __('messages.pratiquants.salle_add') }}</button></div>
        </div>
    </form>
@endif

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>{{ __('messages.pratiquants.col_salle') }}</th>
                    <th class="text-center">{{ __('messages.pratiquants.total') }}</th>
                    <th style="width: 160px;">{{ __('messages.pratiquants.progress_col') }}</th>
                    <th>{{ __('messages.pratiquants.maitre') }}</th>
                    <th class="text-end">{{ __('messages.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($salles as $salle)
                    @php $hasMaster = $salle->maitre_id || $salle->maitreUser; @endphp
                    <tr>
                        <td class="fw-semibold">{{ $salle->nom }}</td>
                        <td class="text-center">{{ $salle->disciples_count }}</td>
                        <td>
                            @php $pct = $salle->disciples_count ? round($salle->complete_count / $salle->disciples_count * 100) : 0; @endphp
                            <div class="progress" style="height: 8px;" title="{{ $salle->complete_count }} / {{ $salle->disciples_count }}"><div class="progress-bar {{ $pct === 100 ? 'bg-success' : 'bg-warning' }}" style="width: {{ $pct }}%"></div></div>
                            <small class="text-muted">{{ $salle->complete_count }} / {{ $salle->disciples_count }}</small>
                        </td>
                        <td>
                            @if($hasMaster)
                                <i class="fas fa-user-tie text-success me-1"></i> {{ $salle->maitre_display_name }}
                                <small class="text-muted">{{ $salle->maitre_display_grade }} {{ $salle->maitre?->telephone ?: $salle->maitreUser?->phone }}</small>
                                @if($salle->maitreUser)<span class="badge text-bg-success ms-1">{{ __('messages.pratiquants.maitre_has_account') }}</span>@endif
                            @else
                                <form method="POST" action="{{ route('admin.pratiquants.maitres.store', $salle) }}" class="row g-1 align-items-center js-maitre-form">
                                    @csrf
                                    <div class="col-md-4"><input name="nom_complet" class="form-control form-control-sm" required maxlength="120" placeholder="{{ __('messages.pratiquants.maitre_name') }}"></div>
                                    <div class="col-md-3"><input name="telephone" class="form-control form-control-sm" maxlength="20" inputmode="tel" placeholder="{{ __('messages.pratiquants.col_phone') }}"></div>
                                    <div class="col-md-3"><select name="grade_id" class="form-select form-select-sm"><option value="">{{ __('messages.pratiquants.col_grade') }}</option>@foreach($grades as $g)<option value="{{ $g->id }}">{{ $g->nom_grade }}</option>@endforeach</select></div>
                                    <div class="col-md-2"><button class="btn btn-sm btn-primary w-100"><i class="fas fa-save"></i></button></div>
                                    @if($canAccount)
                                        <div class="col-12 small"><label class="form-check-label"><input type="checkbox" name="compte" value="1" class="form-check-input me-1" checked> {{ __('messages.pratiquants.maitre_create_account') }}</label></div>
                                    @endif
                                </form>
                            @endif
                        </td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('admin.pratiquants.fiche.excel', $salle) }}" class="btn btn-sm btn-outline-success" title="{{ __('messages.pratiquants.fiche_excel') }}"><i class="fas fa-file-excel"></i></a>
                            <a href="{{ route('admin.pratiquants.fiche', ['salle' => $salle->id]) }}" target="_blank" class="btn btn-sm btn-outline-dark" title="{{ __('messages.pratiquants.fiche_pdf') }}"><i class="fas fa-file-pdf"></i></a>
                            <a href="{{ route('admin.pratiquants.saisie', ['salle' => $salle->id]) }}" class="btn btn-sm btn-outline-primary" title="{{ __('messages.pratiquants.saisie_title') }}"><i class="fas fa-keyboard"></i></a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
