@extends('layouts.admin')

@section('title', __('messages.federations.title'))

@section('actions')
    @if(Auth::user() && (Auth::user()->isSuperAdmin() || Auth::user()->hasPermission('FÉDÉRATION_CREATE')))
        <a href="{{ route('admin.federations.create') }}" class="btn text-white shadow-sm" style="background-color: var(--navbar-bg);">
            <i class="fas fa-plus-circle me-1"></i> {{ __('messages.federations.add') }}
        </a>
    @endif
@endsection

@section('content')
<div class="card card-navbar shadow-sm">
    <div class="card-header card-header-navbar py-3">
        <form method="GET" action="{{ route('admin.federations.index') }}" class="row g-2 align-items-center" id="filterForm">
            <div class="col-md-5">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control border-start-0 js-auto-filter-text" placeholder="{{ __('messages.search') }}" value="{{ request('search') }}">
                </div>
            </div>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>{{ __('messages.federations.name') }}</th>
                    <th>{{ __('messages.federations.acronym') }}</th>
                    <th>{{ __('messages.phone') }}</th>
                    <th class="text-center">{{ __('messages.federations.ligues_count') }}</th>
                    <th class="text-center">{{ __('messages.federations.grades_count') }}</th>
                    <th style="width: 140px;" class="text-end">{{ __('messages.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($federations as $federation)
                    <tr>
                        <td class="fw-semibold">{{ $federation->nom }}</td>
                        <td>{{ $federation->sigle ?? '-' }}</td>
                        <td>{{ $federation->telephone ?? '-' }}</td>
                        <td class="text-center">{{ $federation->ligues_count }}</td>
                        <td class="text-center">{{ $federation->grades_count }}</td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm" role="group">
                                @if(Auth::user() && (Auth::user()->isSuperAdmin() || Auth::user()->hasPermission('FÉDÉRATION_UPDATE')))
                                    <a href="{{ route('admin.federations.edit', $federation) }}" class="btn btn-outline-primary" title="{{ __('messages.edit') }}"><i class="fas fa-edit"></i></a>
                                @endif
                                @if(Auth::user() && (Auth::user()->isSuperAdmin() || Auth::user()->hasPermission('FÉDÉRATION_DELETE')))
                                    <button type="button" class="btn btn-outline-danger btn-delete" data-id="{{ $federation->id }}" data-name="{{ $federation->nom }}" title="{{ __('messages.delete') }}"><i class="fas fa-trash"></i></button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center py-5 text-muted">{{ __('messages.federations.not_found') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($federations->hasPages())
        <div class="card-footer bg-white border-top-0 pt-3">{{ $federations->links() }}</div>
    @endif
</div>

@include('admin.partials._delete_modal', ['question' => __('messages.federations.delete_question')])
@endsection

@section('js')
@include('admin.partials._list_scripts', ['baseUrl' => url('admin/federations')])
@endsection
