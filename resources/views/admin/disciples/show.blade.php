@extends('layouts.admin')

@section('title', $disciple->full_name)

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('messages.dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.disciples.index') }}">{{ __('messages.disciples.title') }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $disciple->full_name }}</li>
@endsection

@section('actions')
    <a href="{{ route('admin.disciples.receipt', $disciple) }}" target="_blank" class="btn btn-success shadow-sm"><i class="fas fa-receipt me-1"></i> {{ __('messages.disciples.receipt') }}</a>
    @if(Auth::user() && (Auth::user()->isSuperAdmin() || Auth::user()->hasPermission('DISCIPLE_UPDATE')))
        <a href="{{ route('admin.disciples.edit', $disciple) }}" class="btn btn-primary shadow-sm"><i class="fas fa-edit me-1"></i> {{ __('messages.edit') }}</a>
    @endif
    <a href="{{ route('admin.disciples.index') }}" class="btn btn-light shadow-sm"><i class="fas fa-arrow-left me-1"></i> {{ __('messages.back') }}</a>
@endsection

@section('content')
<div class="row g-4">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm text-center">
            <div class="card-body">
                @if($disciple->photo_url)
                    <img src="{{ $disciple->photo_url }}" alt="Photo" class="rounded-circle mb-3" style="width: 130px; height: 130px; object-fit: cover;">
                @else
                    <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center text-white mx-auto mb-3" style="width: 130px; height: 130px; font-size: 42px; font-weight: bold;">
                        {{ mb_strtoupper(mb_substr($disciple->prenom, 0, 1) . mb_substr($disciple->nom, 0, 1)) }}
                    </div>
                @endif
                <h5 class="fw-bold mb-1">{{ $disciple->full_name }}</h5>
                <span class="badge bg-{{ $disciple->status_color }}">{{ $disciple->status_label }}</span>
                <div class="text-muted small mt-2">{{ $disciple->nmle }}</div>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <table class="table table-borderless mb-0">
                    <tbody>
                        <tr><th style="width: 40%;">{{ __('messages.gender') }}</th><td>{{ $disciple->sexe === 'F' ? __('messages.female') : ($disciple->sexe === 'M' ? __('messages.male') : '-') }}</td></tr>
                        <tr><th>{{ __('messages.birth_date') }}</th><td>{{ optional($disciple->date_naissance)->format('d/m/Y') ?? '-' }}</td></tr>
                        <tr><th>{{ __('messages.birth_place') }}</th><td>{{ $disciple->date_lieu_naissance ?? '-' }}</td></tr>
                        <tr><th>{{ __('messages.phone') }}</th><td>{{ $disciple->telephone ?? '-' }}</td></tr>
                        <tr><th>{{ __('messages.address') }}</th><td>{{ $disciple->adresse ?? '-' }}</td></tr>
                        <tr><th>{{ __('messages.salle') }}</th><td>{{ $disciple->salle?->nom ?? '-' }}</td></tr>
                        <tr><th>{{ __('messages.grade') }}</th><td>{{ $disciple->grade?->nom_grade ?? '-' }}</td></tr>
                        <tr><th>{{ __('messages.registration_date') }}</th><td>{{ optional($disciple->date_inscription)->format('d/m/Y') ?? '-' }}</td></tr>
                        <tr><th>{{ __('messages.grade_date') }}</th><td>{{ optional($disciple->date_obtention_grade)->format('d/m/Y') ?? '-' }}</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
