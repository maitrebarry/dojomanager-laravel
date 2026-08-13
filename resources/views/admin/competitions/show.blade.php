@extends('layouts.admin')

@section('title', $competition->nom)

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('messages.dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.competitions.index') }}">{{ __('messages.competitions.title') }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $competition->nom }}</li>
@endsection

@section('actions')
    @if(Auth::user() && (Auth::user()->isSuperAdmin() || Auth::user()->hasPermission('COMPETITION_MANAGE')))
        <a href="{{ route('admin.competitions.edit', $competition) }}" class="btn btn-outline-primary shadow-sm"><i class="fas fa-edit me-1"></i> {{ __('messages.edit') }}</a>
    @endif
    <a href="{{ route('admin.competitions.index') }}" class="btn btn-light shadow-sm"><i class="fas fa-arrow-left me-1"></i> {{ __('messages.back') }}</a>
@endsection

@section('content')
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <h5 class="fw-bold mb-1">{{ $competition->nom }}</h5>
        <div class="text-muted">
            {{ optional($competition->date_competition)->format('d/m/Y') ?? '-' }}
            @if($competition->lieu) · {{ $competition->lieu }} @endif
            @if($competition->type) · <span class="badge bg-secondary">{{ $competition->type }}</span> @endif
        </div>
    </div>
</div>

@if(Auth::user() && (Auth::user()->isSuperAdmin() || Auth::user()->hasPermission('COMPETITION_MANAGE')))
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3"><h6 class="mb-0"><i class="fas fa-plus me-2"></i> {{ __('messages.competitions.add_fight') }}</h6></div>
    <div class="card-body">
        <form action="{{ route('admin.competitions.combats.add', $competition) }}" method="POST" class="row g-2 align-items-end">
            @csrf
            <div class="col-md-2">
                <label class="form-label">{{ __('messages.competitions.round') }}</label>
                <input type="text" name="tour" class="form-control" placeholder="{{ __('messages.competitions.round_placeholder') }}" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ __('messages.competitions.fighter1') }}</label>
                <select name="combattant1_id" class="form-select">
                    <option value="">-</option>
                    @foreach($disciples as $d)<option value="{{ $d->id }}">{{ $d->full_name }}</option>@endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ __('messages.competitions.fighter2') }}</label>
                <select name="combattant2_id" class="form-select">
                    <option value="">-</option>
                    @foreach($disciples as $d)<option value="{{ $d->id }}">{{ $d->full_name }}</option>@endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ __('messages.competitions.winner') }}</label>
                <select name="vainqueur_id" class="form-select">
                    <option value="">-</option>
                    @foreach($disciples as $d)<option value="{{ $d->id }}">{{ $d->full_name }}</option>@endforeach
                </select>
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-plus"></i></button>
            </div>
        </form>
    </div>
</div>
@endif

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3"><h6 class="mb-0"><i class="fas fa-hand-fist me-2"></i> {{ __('messages.competitions.fights') }}</h6></div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>{{ __('messages.competitions.round') }}</th>
                    <th>{{ __('messages.competitions.fighter1') }}</th>
                    <th>{{ __('messages.competitions.fighter2') }}</th>
                    <th>{{ __('messages.competitions.winner') }}</th>
                    <th style="width: 60px;" class="text-end">{{ __('messages.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($competition->combats as $combat)
                    <tr>
                        <td><span class="badge bg-light text-dark border">{{ $combat->tour }}</span></td>
                        <td>{{ $combat->combattant1?->full_name ?? '-' }}</td>
                        <td>{{ $combat->combattant2?->full_name ?? '-' }}</td>
                        <td class="fw-semibold text-success">{{ $combat->vainqueur?->full_name ?? '-' }}</td>
                        <td class="text-end">
                            @if(Auth::user() && (Auth::user()->isSuperAdmin() || Auth::user()->hasPermission('COMPETITION_MANAGE')))
                                <form action="{{ route('admin.competitions.combats.remove', [$competition, $combat]) }}" method="POST" onsubmit="return confirm('{{ __('messages.competitions.remove_fight_confirm') }}');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center py-5 text-muted">{{ __('messages.competitions.no_fights') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
