@extends('layouts.admin')

@section('title', __('messages.ligues.title'))

@section('actions')
    @if(Auth::user() && (Auth::user()->isSuperAdmin() || Auth::user()->hasPermission('LIGUE_CREATE')))
        <a href="{{ route('admin.ligues.create') }}" class="btn text-white shadow-sm" style="background-color: var(--navbar-bg);">
            <i class="fas fa-plus-circle me-1"></i> {{ __('messages.ligues.add') }}
        </a>
    @endif
@endsection

@section('content')
<div class="card card-navbar shadow-sm">
    <div class="card-header card-header-navbar py-3">
        <form method="GET" action="{{ route('admin.ligues.index') }}" class="row g-2 align-items-center" id="filterForm">
            <div class="col-md-5">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control border-start-0 js-auto-filter-text" placeholder="{{ __('messages.search') }}" value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-md-4">
                <select name="federation" class="form-select form-select-sm js-auto-filter">
                    <option value="">{{ __('messages.ligues.all_federations') }}</option>
                    @foreach($federations as $federation)
                        <option value="{{ $federation->id }}" {{ (string) request('federation') === (string) $federation->id ? 'selected' : '' }}>{{ $federation->nom }}</option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>{{ __('messages.ligues.name') }}</th>
                    <th>{{ __('messages.ligues.region') }}</th>
                    <th>{{ __('messages.ligues.federation') }}</th>
                    <th class="text-center">{{ __('messages.ligues.salles_count') }}</th>
                    <th>{{ __('messages.status') }}</th>
                    <th style="width: 140px;" class="text-end">{{ __('messages.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($ligues as $ligue)
                    <tr>
                        <td class="fw-semibold">{{ $ligue->nom }}</td>
                        <td>{{ $ligue->region ?? '-' }}</td>
                        <td>{{ $ligue->federation?->nom ?? '-' }}</td>
                        <td class="text-center">{{ $ligue->salles_count }}</td>
                        <td><span class="badge bg-{{ $ligue->active ? 'success' : 'secondary' }}">{{ $ligue->active ? __('messages.active') : __('messages.inactive') }}</span></td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm" role="group">
                                @if(Auth::user() && (Auth::user()->isSuperAdmin() || Auth::user()->hasPermission('LIGUE_UPDATE')))
                                    <a href="{{ route('admin.ligues.edit', $ligue) }}" class="btn btn-outline-primary" title="{{ __('messages.edit') }}"><i class="fas fa-edit"></i></a>
                                @endif
                                @if(Auth::user() && (Auth::user()->isSuperAdmin() || Auth::user()->hasPermission('LIGUE_DELETE')))
                                    <button type="button" class="btn btn-outline-danger btn-delete" data-id="{{ $ligue->id }}" data-name="{{ $ligue->nom }}" title="{{ __('messages.delete') }}"><i class="fas fa-trash"></i></button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center py-5 text-muted">{{ __('messages.ligues.not_found') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($ligues->hasPages())
        <div class="card-footer bg-white border-top-0 pt-3">{{ $ligues->links() }}</div>
    @endif
</div>

@include('admin.partials._delete_modal', ['question' => __('messages.ligues.delete_question')])
@endsection

@section('js')
@include('admin.partials._list_scripts', ['baseUrl' => url('admin/ligues')])
@endsection
