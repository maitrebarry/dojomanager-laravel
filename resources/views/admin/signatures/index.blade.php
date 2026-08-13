@extends('layouts.admin')

@section('title', __('messages.signatures.title'))

@section('actions')
    @if(Auth::user() && (Auth::user()->isSuperAdmin() || Auth::user()->hasPermission('PARAMETRES_MANAGE')))
        <a href="{{ route('admin.signatures.create') }}" class="btn text-white shadow-sm" style="background-color: var(--navbar-bg);">
            <i class="fas fa-plus-circle me-1"></i> {{ __('messages.signatures.add') }}
        </a>
    @endif
@endsection

@section('content')
<div class="card card-navbar shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>{{ __('messages.signatures.master') }}</th>
                    <th>{{ __('messages.signatures.grade') }}</th>
                    <th>{{ __('messages.signatures.role') }}</th>
                    <th>{{ __('messages.signatures.signature') }}</th>
                    <th style="width: 120px;" class="text-end">{{ __('messages.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($signatures as $sig)
                    <tr>
                        <td class="fw-semibold">{{ $sig->master_name }}</td>
                        <td>{{ $sig->master_grade ?? '-' }}</td>
                        <td>{{ $sig->role ?? '-' }}</td>
                        <td>
                            @if($sig->signature_data)
                                <img src="{{ $sig->signature_data }}" alt="signature" style="height: 40px; max-width: 140px; object-fit: contain;">
                            @else
                                <span class="text-muted small">—</span>
                            @endif
                        </td>
                        <td class="text-end">
                            @if(Auth::user() && (Auth::user()->isSuperAdmin() || Auth::user()->hasPermission('PARAMETRES_MANAGE')))
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="{{ route('admin.signatures.edit', $sig) }}" class="btn btn-outline-primary"><i class="fas fa-edit"></i></a>
                                    <button type="button" class="btn btn-outline-danger btn-delete" data-id="{{ $sig->id }}" data-name="{{ $sig->master_name }}"><i class="fas fa-trash"></i></button>
                                </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center py-5 text-muted">{{ __('messages.signatures.empty') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($signatures->hasPages())
        <div class="card-footer bg-white border-top-0 pt-3">{{ $signatures->links() }}</div>
    @endif
</div>

@include('admin.partials._delete_modal', ['question' => __('messages.signatures.delete_question')])
@endsection

@section('js')
@include('admin.partials._list_scripts', ['baseUrl' => url('admin/signatures')])
@endsection
