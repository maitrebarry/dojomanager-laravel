@extends('layouts.admin')

@section('title', __('messages.grades.title'))

@section('actions')
    @if(Auth::user() && (Auth::user()->isSuperAdmin() || Auth::user()->hasPermission('GRADES_CREATE')))
        <a href="{{ route('admin.grades.create') }}" class="btn text-white shadow-sm" style="background-color: var(--navbar-bg);">
            <i class="fas fa-plus-circle me-1"></i> {{ __('messages.grades.add') }}
        </a>
    @endif
@endsection

@section('content')
<div class="card card-navbar shadow-sm">
    <div class="card-header card-header-navbar py-3">
        <form method="GET" action="{{ route('admin.grades.index') }}" class="row g-2 align-items-center" id="filterForm">
            <div class="col-md-5">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control border-start-0 js-auto-filter-text" placeholder="{{ __('messages.search') }}" value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-md-3">
                <select name="type" class="form-select form-select-sm js-auto-filter">
                    <option value="">{{ __('messages.grades.all_types') }}</option>
                    <option value="KEUP" {{ request('type') === 'KEUP' ? 'selected' : '' }}>KEUP</option>
                    <option value="DAN" {{ request('type') === 'DAN' ? 'selected' : '' }}>DAN</option>
                </select>
            </div>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>{{ __('messages.grades.level') }}</th>
                    <th>{{ __('messages.grades.name') }}</th>
                    <th>{{ __('messages.grades.belt') }}</th>
                    <th>{{ __('messages.grades.type') }}</th>
                    <th>{{ __('messages.grades.federation') }}</th>
                    <th style="width: 140px;" class="text-end">{{ __('messages.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($grades as $grade)
                    <tr>
                        <td>{{ $grade->niveau }}</td>
                        <td class="fw-semibold">{{ $grade->nom_grade }}</td>
                        <td>{{ $grade->ceinture }}</td>
                        <td><span class="badge bg-{{ $grade->type_grade === 'DAN' ? 'dark' : 'info' }}">{{ $grade->type_grade }}</span></td>
                        <td>{{ $grade->federation?->nom ?? '-' }}</td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm" role="group">
                                @if(Auth::user() && (Auth::user()->isSuperAdmin() || Auth::user()->hasPermission('GRADES_UPDATE')))
                                    <a href="{{ route('admin.grades.edit', $grade) }}" class="btn btn-outline-primary" title="{{ __('messages.edit') }}"><i class="fas fa-edit"></i></a>
                                @endif
                                @if(Auth::user() && (Auth::user()->isSuperAdmin() || Auth::user()->hasPermission('GRADES_DELETE')))
                                    <button type="button" class="btn btn-outline-danger btn-delete" data-id="{{ $grade->id }}" data-name="{{ $grade->nom_grade }}" title="{{ __('messages.delete') }}"><i class="fas fa-trash"></i></button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center py-5 text-muted">{{ __('messages.grades.not_found') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($grades->hasPages())
        <div class="card-footer bg-white border-top-0 pt-3">{{ $grades->links() }}</div>
    @endif
</div>

@include('admin.partials._delete_modal', ['question' => __('messages.grades.delete_question')])
@endsection

@section('js')
@include('admin.partials._list_scripts', ['baseUrl' => url('admin/grades')])
@endsection
