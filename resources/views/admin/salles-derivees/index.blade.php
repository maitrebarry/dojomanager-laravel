@extends('layouts.admin')

@section('content')
{{-- Espace du maître d'une salle mère : salles dérivées et création de l'espace d'un subalterne. --}}
<p class="text-muted small">{{ __('messages.salles_derivees.help', ['salle' => $mere->nom]) }}</p>

@if(session('maitre_account'))
    <div class="alert alert-success d-flex align-items-center gap-2"><i class="fas fa-key fa-lg"></i> <div>{{ session('maitre_account') }}</div></div>
@endif

{{-- Ma salle et toutes ses salles affiliées (à tous les niveaux), en arbre. --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white d-flex flex-wrap align-items-center gap-2">
        <span class="fw-semibold"><i class="fas fa-sitemap me-1"></i> {{ __('messages.salles_derivees.list', ['salle' => $mere->nom]) }}</span>
        <span class="ms-auto small text-muted">{{ __('messages.salles_derivees.totals', ['salles' => $derivees->count(), 'disciples' => $totalDisciples]) }}</span>
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light"><tr><th>{{ __('messages.pratiquants.col_salle') }}</th><th>{{ __('messages.pratiquants.maitre') }}</th><th>{{ __('messages.pratiquants.col_phone') }}</th><th class="text-center">{{ __('messages.pratiquants.total') }}</th><th></th></tr></thead>
            <tbody>
                <tr class="table-success">
                    <td class="fw-bold"><i class="fas fa-house-chimney me-1"></i>{{ $mere->nom }} <small class="text-muted fw-normal">({{ __('messages.salles_derivees.my_salle') }})</small></td>
                    <td>{{ $mere->maitre_display_name ?: '—' }}</td><td></td><td class="text-center">{{ $mere->disciples()->whereNull('archived_at')->count() }}</td><td></td>
                </tr>
                @forelse($derivees as $salle)
                    <tr>
                        <td class="fw-semibold" style="padding-left: {{ 12 + $salle->depth * 22 }}px;"><i class="fas fa-level-up-alt fa-rotate-90 text-muted me-1"></i>{{ $salle->nom }}
                            @if($salle->depth > 1)<small class="d-block text-muted fw-normal">{{ __('messages.salles.derives_from', ['mere' => $salle->salleMere?->nom]) }}</small>@endif
                            @if($salle->adresse)<small class="d-block text-muted fw-normal">{{ $salle->adresse }}</small>@endif</td>
                        <td>{{ $salle->maitre_display_name ?: '—' }} <small class="text-muted">{{ $salle->maitre?->grade }}</small></td>
                        <td>{{ $salle->maitre?->telephone ?: $salle->maitreUser?->phone ?: '—' }}</td>
                        <td class="text-center">{{ $salle->disciples_count }}</td>
                        <td class="text-end"><a href="{{ route('admin.salles-derivees.disciples', $salle) }}" class="btn btn-sm btn-outline-primary"><i class="fas fa-users me-1"></i> {{ __('messages.salles_derivees.see_disciples') }}</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">{{ __('messages.salles_derivees.none') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<form method="POST" action="{{ route('admin.salles-derivees.store') }}" class="card border-0 shadow-sm">
    @csrf
    <div class="card-header bg-white fw-semibold"><i class="fas fa-plus me-1"></i> {{ __('messages.salles_derivees.create') }}</div>
    <div class="card-body row g-2">
        <div class="col-md-6"><label class="form-label small">{{ __('messages.salles_derivees.salle_name') }} <span class="text-danger">*</span></label><input name="nom" class="form-control" value="{{ old('nom') }}" required maxlength="140"></div>
        <div class="col-md-6"><label class="form-label small">{{ __('messages.pratiquants.salle_address') }}</label><input name="adresse" class="form-control" value="{{ old('adresse') }}" maxlength="255"></div>
        <div class="col-md-5"><label class="form-label small">{{ __('messages.salles_derivees.maitre_name') }} <span class="text-danger">*</span></label><input name="nom_complet" class="form-control" value="{{ old('nom_complet') }}" required maxlength="120"></div>
        <div class="col-md-4"><label class="form-label small">{{ __('messages.salles_derivees.maitre_phone') }} <span class="text-danger">*</span></label><input name="telephone" class="form-control" value="{{ old('telephone') }}" required maxlength="20" inputmode="tel"></div>
        <div class="col-md-3"><label class="form-label small">{{ __('messages.pratiquants.col_grade') }}</label><select name="grade_id" class="form-select"><option value=""></option>@foreach($grades as $g)<option value="{{ $g->id }}" @selected((string) old('grade_id') === (string) $g->id)>{{ $g->nom_grade }}</option>@endforeach</select></div>
        <div class="col-12"><small class="text-muted">{{ __('messages.salles_derivees.create_help') }}</small></div>
        <div class="col-12"><button class="btn btn-primary"><i class="fas fa-save me-1"></i> {{ __('messages.salles_derivees.create') }}</button></div>
    </div>
</form>
@endsection
