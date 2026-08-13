@extends('layouts.admin')

@section('title', __('messages.grade_passages.title'))

@section('actions')
    @if(Auth::user() && (Auth::user()->isSuperAdmin() || Auth::user()->hasPermission('PASSAGEGRADES_MANAGE')))
        <a href="{{ route('admin.grade-passage-tariffs.index') }}" class="btn btn-outline-secondary shadow-sm">
            <i class="fas fa-tags me-1"></i> {{ __('messages.grade_passage_tariffs.title') }}
        </a>
        <a href="{{ route('admin.grade-passages.create') }}" class="btn text-white shadow-sm" style="background-color: var(--navbar-bg);">
            <i class="fas fa-plus-circle me-1"></i> {{ __('messages.grade_passages.add') }}
        </a>
    @endif
@endsection

@section('content')
@include('admin.grade-passages._tabs', ['active' => 'sessions'])
<div class="card card-navbar shadow-sm">
    <div class="card-header card-header-navbar"><h5 class="mb-0"><i class="fas fa-ranking-star me-2"></i> {{ __('messages.grade_passages.sessions_list') }}</h5></div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>{{ __('messages.grade_passages.date') }}</th>
                    <th>{{ __('messages.grade_passages.place') }}</th>
                    <th>{{ __('messages.grades.type') }}</th>
                    <th class="text-end">{{ __('messages.grade_passages.fee') }}</th>
                    <th class="text-center">{{ __('messages.grade_passages.candidates') }}</th>
                    <th>{{ __('messages.status') }}</th>
                    <th style="width: 120px;" class="text-end">{{ __('messages.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($sessions as $s)
                    <tr>
                        <td class="fw-semibold">{{ $s->date_session?->format('d/m/Y') }}</td>
                        <td>{{ $s->lieu }}</td>
                        <td><span class="badge bg-{{ $s->type_grade === 'DAN' ? 'dark' : 'info' }}">{{ $s->type_grade }}</span></td>
                        <td class="text-end">{{ number_format($s->frais_participation, 0, ',', ' ') }}</td>
                        <td class="text-center">{{ $s->candidats_count }}</td>
                        <td><span class="badge bg-{{ $s->finalisee ? 'success' : 'warning' }}">{{ $s->finalisee ? __('messages.grade_passages.finalized_badge') : __('messages.grade_passages.open') }}</span></td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm" role="group">
                                <a href="{{ route('admin.grade-passages.show', $s) }}" class="btn btn-outline-primary" title="{{ __('messages.view') }}"><i class="fas fa-eye"></i></a>
                                @if(!$s->finalisee && Auth::user() && (Auth::user()->isSuperAdmin() || Auth::user()->hasPermission('PASSAGEGRADES_MANAGE')))
                                    <button type="button" class="btn btn-outline-danger btn-delete" data-id="{{ $s->id }}" data-name="{{ $s->lieu }} — {{ $s->date_session?->format('d/m/Y') }}"><i class="fas fa-trash"></i></button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center py-5 text-muted">{{ __('messages.grade_passages.empty') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($sessions->hasPages())
        <div class="card-footer bg-white border-top-0 pt-3">{{ $sessions->links() }}</div>
    @endif
</div>

@include('admin.partials._delete_modal', ['question' => __('messages.grade_passages.delete_question')])
@endsection

@section('js')
@include('admin.partials._list_scripts', ['baseUrl' => url('admin/grade-passages')])
@endsection
