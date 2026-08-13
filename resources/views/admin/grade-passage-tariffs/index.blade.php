@extends('layouts.admin')

@section('title', __('messages.grade_passage_tariffs.title'))

@section('actions')
    @if(Auth::user() && (Auth::user()->isSuperAdmin() || Auth::user()->hasPermission('PASSAGEGRADES_MANAGE')))
        <a href="{{ route('admin.grade-passage-tariffs.create') }}" class="btn text-white shadow-sm" style="background-color: var(--navbar-bg);">
            <i class="fas fa-plus-circle me-1"></i> {{ __('messages.grade_passage_tariffs.add') }}
        </a>
    @endif
@endsection

@section('content')
<div class="card card-navbar shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>{{ __('messages.grades.type') }}</th>
                    <th>{{ __('messages.grade_passage_tariffs.label') }}</th>
                    <th>{{ __('messages.grade') }}</th>
                    <th class="text-end">{{ __('messages.cotisations.amount') }}</th>
                    <th>{{ __('messages.status') }}</th>
                    <th style="width: 120px;" class="text-end">{{ __('messages.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tariffs as $t)
                    <tr>
                        <td><span class="badge bg-{{ $t->type_grade === 'DAN' ? 'dark' : 'info' }}">{{ $t->type_grade }}</span></td>
                        <td class="fw-semibold">{{ $t->tarif_label }}</td>
                        <td>{{ $t->grade?->nom_grade ?? '-' }}</td>
                        <td class="text-end">{{ number_format($t->montant, 0, ',', ' ') }}</td>
                        <td><span class="badge bg-{{ $t->active ? 'success' : 'secondary' }}">{{ $t->active ? __('messages.active') : __('messages.inactive') }}</span></td>
                        <td class="text-end">
                            @if(Auth::user() && (Auth::user()->isSuperAdmin() || Auth::user()->hasPermission('PASSAGEGRADES_MANAGE')))
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="{{ route('admin.grade-passage-tariffs.edit', $t) }}" class="btn btn-outline-primary"><i class="fas fa-edit"></i></a>
                                    <button type="button" class="btn btn-outline-danger btn-delete" data-id="{{ $t->id }}" data-name="{{ $t->tarif_label }}"><i class="fas fa-trash"></i></button>
                                </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center py-5 text-muted">{{ __('messages.grade_passage_tariffs.empty') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($tariffs->hasPages())
        <div class="card-footer bg-white border-top-0 pt-3">{{ $tariffs->links() }}</div>
    @endif
</div>

@include('admin.partials._delete_modal', ['question' => __('messages.grade_passage_tariffs.delete_question')])
@endsection

@section('js')
@include('admin.partials._list_scripts', ['baseUrl' => url('admin/grade-passage-tariffs')])
@endsection
