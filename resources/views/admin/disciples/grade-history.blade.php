@extends('layouts.admin')

@section('title', __('messages.disciple_grades.history_title'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('messages.dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.disciples.index') }}">{{ __('messages.disciples.title') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.disciples.show', $disciple) }}">{{ $disciple->full_name }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ __('messages.disciple_grades.history_title') }}</li>
@endsection

@section('actions')
    <a href="{{ route('admin.disciples.show', $disciple) }}" class="btn btn-light shadow-sm"><i class="fas fa-arrow-left me-1"></i> {{ __('messages.back') }}</a>
@endsection

@section('content')
<div class="card card-navbar shadow-sm">
    <div class="card-header card-header-navbar">
        <h5 class="mb-0"><i class="fas fa-calendar-check me-2"></i> {{ __('messages.disciple_grades.history_title') }} — {{ $disciple->full_name }}</h5>
        <div class="small mt-1" style="opacity:.85">{{ __('messages.disciple_grades.history_subtitle') }}</div>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.disciples.grades.history.save', $disciple) }}">
            @csrf
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-3">
                    <thead>
                        <tr>
                            <th>{{ __('messages.grade') }}</th>
                            <th style="width: 220px;">{{ __('messages.disciple_grades.date_obtained') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($grades as $grade)
                            @php $isCurrentOrPast = $disciple->grade && $grade->niveau <= $disciple->grade->niveau; @endphp
                            <tr class="{{ $grade->id === $disciple->grade_id ? 'table-success' : '' }}">
                                <td>
                                    {{ $grade->nom_grade }} <span class="text-muted small">({{ $grade->ceinture }})</span>
                                    @if($grade->id === $disciple->grade_id)
                                        <span class="badge bg-success ms-1">{{ __('messages.disciple_grades.current_grade') }}</span>
                                    @endif
                                </td>
                                <td>
                                    <input type="date" name="dates[{{ $grade->id }}]" class="form-control form-control-sm"
                                        value="{{ optional($dates->get($grade->id))->format('Y-m-d') }}">
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> {{ __('messages.save') }}</button>
        </form>
    </div>
</div>
@endsection
