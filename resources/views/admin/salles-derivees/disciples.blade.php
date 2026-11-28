@extends('layouts.admin')

@section('actions')
    <a href="{{ route('admin.salles-derivees.index') }}" class="btn btn-light shadow-sm"><i class="fas fa-arrow-left me-1"></i> {{ __('messages.salles_derivees.back') }}</a>
@endsection

@section('content')
{{-- Disciples d'une salle affiliée : consultation seulement pour le maître de la salle mère. --}}
<p class="small text-muted">
    {{ __('messages.salles_derivees.disciples_help', ['maitre' => $salle->maitre_display_name ?: '—']) }}
    @if($salle->salleMere) · {{ __('messages.salles.derives_from', ['mere' => $salle->salleMere->nom]) }}@endif
</p>
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead class="table-light"><tr><th style="width: 48px;"></th><th>{{ __('messages.pratiquants.col_name') }}</th><th class="text-center">{{ __('messages.pratiquants.col_sex') }}</th><th class="text-center">{{ __('messages.pratiquants.col_age') }}</th><th>{{ __('messages.pratiquants.col_grade') }}</th><th>Matricule</th></tr></thead>
            <tbody>
                @forelse($disciples as $d)
                    <tr>
                        <td>@if($d->photo_url)<img src="{{ $d->photo_url }}" alt="" class="rounded" style="width: 34px; height: 40px; object-fit: cover;">@else<span class="d-inline-flex align-items-center justify-content-center rounded bg-light text-muted" style="width: 34px; height: 40px;"><i class="fas fa-user"></i></span>@endif</td>
                        <td class="fw-semibold">{{ $d->prenom }} {{ mb_strtoupper($d->nom) }}</td>
                        <td class="text-center">{{ $d->sexe ?: '-' }}</td>
                        <td class="text-center">{{ $d->date_naissance ? $year - $d->date_naissance->year : '-' }}</td>
                        <td>{{ $d->grade?->nom_grade ?? '-' }}</td>
                        <td class="small text-muted">{{ $d->nmle }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">{{ __('messages.pratiquants.none') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
