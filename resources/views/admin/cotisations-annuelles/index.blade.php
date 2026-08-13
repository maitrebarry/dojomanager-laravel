@extends('layouts.admin')

@section('title', __('messages.cotisations_annuelles.title'))

@section('actions')
    @if(Auth::user() && (Auth::user()->isSuperAdmin() || Auth::user()->hasPermission('COTISATION_MANAGE')))
        <a href="{{ route('admin.cotisations-annuelles.create') }}" class="btn text-white shadow-sm" style="background-color: var(--navbar-bg);">
            <i class="fas fa-plus-circle me-1"></i> {{ __('messages.cotisations_annuelles.add') }}
        </a>
    @endif
@endsection

@section('content')
<div class="card card-navbar shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>{{ __('messages.cotisations.year') }}</th>
                    <th class="text-end">{{ __('messages.cotisations.amount') }}</th>
                    <th class="text-center">{{ __('messages.cotisations_annuelles.members') }}</th>
                    <th class="text-end">{{ __('messages.cotisations_annuelles.total_due') }}</th>
                    <th class="text-end">{{ __('messages.cotisations_annuelles.total_collected') }}</th>
                    <th style="width: 160px;" class="text-end">{{ __('messages.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($cotisations as $c)
                    <tr>
                        <td class="fw-bold">{{ $c->annee }}</td>
                        <td class="text-end">{{ number_format($c->montant, 0, ',', ' ') }}</td>
                        <td class="text-center">{{ $c->membres_count }}</td>
                        <td class="text-end">{{ number_format($c->total_du ?? 0, 0, ',', ' ') }}</td>
                        <td class="text-end text-success">{{ number_format($c->total_paye ?? 0, 0, ',', ' ') }}</td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm" role="group">
                                <a href="{{ route('admin.cotisations-annuelles.show', $c) }}" class="btn btn-outline-primary" title="{{ __('messages.view') }}"><i class="fas fa-eye"></i></a>
                                @if(Auth::user() && (Auth::user()->isSuperAdmin() || Auth::user()->hasPermission('COTISATION_MANAGE')))
                                    <button type="button" class="btn btn-outline-danger btn-delete" data-id="{{ $c->id }}" data-name="{{ __('messages.cotisations_annuelles.title') }} {{ $c->annee }}" title="{{ __('messages.delete') }}"><i class="fas fa-trash"></i></button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center py-5 text-muted">{{ __('messages.cotisations_annuelles.empty') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($cotisations->hasPages())
        <div class="card-footer bg-white border-top-0 pt-3">{{ $cotisations->links() }}</div>
    @endif
</div>

@include('admin.partials._delete_modal', ['question' => __('messages.cotisations_annuelles.delete_question')])
@endsection

@section('js')
@include('admin.partials._list_scripts', ['baseUrl' => url('admin/cotisations-annuelles')])
@endsection
