@extends('layouts.admin')

@section('actions')
    <a href="{{ route('admin.pratiquants.index') }}" class="btn btn-light shadow-sm"><i class="fas fa-arrow-left me-1"></i> {{ __('messages.pratiquants.back') }}</a>
@endsection

@section('content')
{{-- Combattants pas encore disciples : avec club → disciple de sa salle ; sans club → le renseigner. --}}
<div class="alert alert-info small"><i class="fas fa-circle-info me-1"></i> {{ __('messages.pratiquants.register_help') }}</div>

@if($withClub->isEmpty() && $withoutClub->isEmpty() && !$matches)
    <div class="card border-0 shadow-sm"><div class="card-body text-center text-muted py-5">{{ __('messages.pratiquants.register_none') }}</div></div>
@endif

@if($withClub->isNotEmpty() || $matches)
    <form method="POST" action="{{ route('admin.pratiquants.register.store') }}" class="card border-0 shadow-sm mb-3" onsubmit="return dojoConfirmAction(this, @js(__('messages.pratiquants.register_confirm', ['count' => $withClub->count()])))">
        @csrf
        <input type="hidden" name="all" value="1">
        <div class="card-body d-flex flex-wrap align-items-center gap-2">
            <div class="flex-fill">
                <div class="fw-semibold"><i class="fas fa-people-group me-1"></i> {{ __('messages.pratiquants.register_backlog', ['count' => $withClub->count()]) }}</div>
                <small class="text-muted">{{ $withClub->pluck('club')->countBy()->sortKeys()->map(fn ($n, $club) => $club . ' (' . $n . ')')->implode(', ') }}</small>
                @if($matches)<div class="small text-success">{{ __('messages.pratiquants.register_matches', ['count' => $matches]) }}</div>@endif
            </div>
            <button class="btn btn-primary"><i class="fas fa-user-plus me-1"></i> {{ __('messages.pratiquants.register_submit') }}</button>
        </div>
    </form>
@endif

@if($withoutClub->isNotEmpty())
    <form method="POST" action="{{ route('admin.pratiquants.register.store') }}" class="card border-0 shadow-sm">
        @csrf
        <div class="card-header bg-white fw-semibold"><i class="fas fa-pen me-1"></i> {{ __('messages.pratiquants.register_no_club_title', ['count' => $withoutClub->count()]) }}</div>
        <datalist id="sallesList">@foreach($salles as $salle)<option value="{{ $salle }}">@endforeach</datalist>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light"><tr><th>{{ __('messages.pratiquants.col_name') }}</th><th class="text-center">{{ __('messages.pratiquants.col_sex') }}</th><th class="text-center">{{ __('messages.pratiquants.col_age') }}</th><th style="width: 40%;">{{ __('messages.pratiquants.register_club') }}</th></tr></thead>
                <tbody>
                    @foreach($withoutClub as $p)
                        <tr>
                            <td class="fw-semibold">{{ $p['name'] }}</td>
                            <td class="text-center">{{ $p['sexe'] ?: '-' }}</td>
                            <td class="text-center">{{ $p['age'] ?? '-' }}</td>
                            <td><input type="text" name="clubs[{{ $p['ref'] }}]" list="sallesList" class="form-control form-control-sm" maxlength="150" placeholder="{{ __('messages.pratiquants.register_club_placeholder') }}"></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white"><button class="btn btn-primary"><i class="fas fa-save me-1"></i> {{ __('messages.pratiquants.register_save_clubs') }}</button></div>
    </form>
@endif
@endsection
