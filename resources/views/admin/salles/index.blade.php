@extends('layouts.admin')

@section('title', __('messages.salles.title'))

@section('actions')
    @if(Auth::user() && (Auth::user()->isSuperAdmin() || Auth::user()->hasPermission('SALLE_CREATE')))
        <a href="{{ route('admin.salles.create') }}" class="btn text-white shadow-sm" style="background-color: var(--navbar-bg);">
            <i class="fas fa-plus-circle me-1"></i> {{ __('messages.salles.add') }}
        </a>
    @endif
@endsection

@section('content')
<div class="card card-navbar shadow-sm">
    <div class="card-header card-header-navbar py-3">
        <form method="GET" action="{{ route('admin.salles.index') }}" class="row g-2 align-items-center" id="filterForm">
            <div class="col-md-5">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control border-start-0 js-auto-filter-text" placeholder="{{ __('messages.search') }}" value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-md-4">
                <select name="ligue" class="form-select form-select-sm js-auto-filter">
                    <option value="">{{ __('messages.salles.all_ligues') }}</option>
                    @foreach($ligues as $ligue)
                        <option value="{{ $ligue->id }}" {{ (string) request('ligue') === (string) $ligue->id ? 'selected' : '' }}>{{ $ligue->nom }}</option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>{{ __('messages.salles.name') }}</th>
                    <th>{{ __('messages.salles.ligue') }}</th>
                    <th>{{ __('messages.salles.maitre') }}</th>
                    <th>{{ __('messages.salles.monthly_fee') }}</th>
                    <th class="text-center">{{ __('messages.salles.disciples_count') }}</th>
                    <th>{{ __('messages.status') }}</th>
                    <th style="width: 140px;" class="text-end">{{ __('messages.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($salles as $salle)
                    <tr>
                        <td class="fw-semibold">{{ $salle->nom }}</td>
                        <td>{{ $salle->ligue?->nom ?? '-' }}</td>
                        <td>{{ $salle->maitre_display_name ?? '-' }}</td>
                        <td>{{ $salle->mensualite ? number_format($salle->mensualite, 0, ',', ' ') . ' FCFA' : '-' }}</td>
                        <td class="text-center">{{ $salle->disciples_count }}</td>
                        <td><span class="badge bg-{{ $salle->active ? 'success' : 'secondary' }}">{{ $salle->active ? __('messages.active') : __('messages.inactive') }}</span></td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm" role="group">
                                @if(Auth::user() && (Auth::user()->isSuperAdmin() || Auth::user()->hasPermission('SALLE_UPDATE')))
                                    <a href="{{ route('admin.salles.edit', $salle) }}" class="btn btn-outline-primary" title="{{ __('messages.edit') }}"><i class="fas fa-edit"></i></a>
                                @endif
                                @if(Auth::user() && (Auth::user()->isSuperAdmin() || Auth::user()->hasPermission('SALLE_DELETE')))
                                    <button type="button" class="btn btn-outline-danger btn-delete" data-id="{{ $salle->id }}" data-name="{{ $salle->nom }}" title="{{ __('messages.delete') }}"><i class="fas fa-trash"></i></button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center py-5 text-muted">{{ __('messages.salles.not_found') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($salles->hasPages())
        <div class="card-footer bg-white border-top-0 pt-3">{{ $salles->links() }}</div>
    @endif
</div>

@include('admin.partials._delete_modal', ['baseUrl' => url('admin/salles'), 'question' => __('messages.salles.delete_question')])
@endsection

@section('js')
@include('admin.partials._list_scripts', ['baseUrl' => url('admin/salles')])
@endsection
