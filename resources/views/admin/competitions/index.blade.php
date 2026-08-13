@extends('layouts.admin')

@section('title', __('messages.competitions.title'))

@section('actions')
    @if(Auth::user() && (Auth::user()->isSuperAdmin() || Auth::user()->hasPermission('COMPETITION_MANAGE')))
        <a href="{{ route('admin.competitions.create') }}" class="btn text-white shadow-sm" style="background-color: var(--navbar-bg);">
            <i class="fas fa-plus-circle me-1"></i> {{ __('messages.competitions.add') }}
        </a>
    @endif
@endsection

@section('content')
<div class="card card-navbar shadow-sm">
    <div class="card-header card-header-navbar py-3">
        <form method="GET" action="{{ route('admin.competitions.index') }}" class="row g-2 align-items-center" id="filterForm">
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
                    <th>{{ __('messages.competitions.name') }}</th>
                    <th>{{ __('messages.competitions.date') }}</th>
                    <th>{{ __('messages.competitions.place') }}</th>
                    <th>{{ __('messages.competitions.type') }}</th>
                    <th class="text-center">{{ __('messages.competitions.fights') }}</th>
                    <th style="width: 140px;" class="text-end">{{ __('messages.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($competitions as $c)
                    <tr>
                        <td class="fw-semibold">{{ $c->nom }}</td>
                        <td>{{ optional($c->date_competition)->format('d/m/Y') ?? '-' }}</td>
                        <td>{{ $c->lieu ?? '-' }}</td>
                        <td>{{ $c->type ?? '-' }}</td>
                        <td class="text-center">{{ $c->combats_count }}</td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm" role="group">
                                <a href="{{ route('admin.competitions.show', $c) }}" class="btn btn-outline-primary" title="{{ __('messages.view') }}"><i class="fas fa-eye"></i></a>
                                @if(Auth::user() && (Auth::user()->isSuperAdmin() || Auth::user()->hasPermission('COMPETITION_MANAGE')))
                                    <a href="{{ route('admin.competitions.edit', $c) }}" class="btn btn-outline-secondary" title="{{ __('messages.edit') }}"><i class="fas fa-edit"></i></a>
                                @endif
                                @if(Auth::user() && (Auth::user()->isSuperAdmin() || Auth::user()->hasPermission('COMPETITION_MANAGE')))
                                    <button type="button" class="btn btn-outline-danger btn-delete" data-id="{{ $c->id }}" data-name="{{ $c->nom }}" title="{{ __('messages.delete') }}"><i class="fas fa-trash"></i></button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center py-5 text-muted">{{ __('messages.competitions.empty') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($competitions->hasPages())
        <div class="card-footer bg-white border-top-0 pt-3">{{ $competitions->links() }}</div>
    @endif
</div>

@include('admin.partials._delete_modal', ['question' => __('messages.competitions.delete_question')])
@endsection

@section('js')
@include('admin.partials._list_scripts', ['baseUrl' => url('admin/competitions')])
@endsection
