@extends('layouts.admin')

@section('title', __('messages.disciple_grades.title'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('messages.dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.disciples.index') }}">{{ __('messages.disciples.title') }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ __('messages.disciple_grades.title') }}</li>
@endsection

@section('actions')
    <a href="{{ route('admin.disciples.index') }}" class="btn btn-light shadow-sm"><i class="fas fa-arrow-left me-1"></i> {{ __('messages.back') }}</a>
@endsection

@section('content')
<div class="card card-navbar shadow-sm mb-3">
    <div class="card-header card-header-navbar">
        <h5 class="mb-0"><i class="fas fa-arrow-up-right-dots me-2"></i> {{ __('messages.disciple_grades.title') }}</h5>
        <div class="small mt-1" style="opacity:.85">{{ __('messages.disciple_grades.subtitle') }}</div>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('admin.disciples.grades.index') }}" id="filterForm" class="row g-2">
            <div class="col-md-6">
                <label class="form-label">{{ __('messages.search') }}</label>
                <input type="text" name="search" class="form-control js-auto-filter-text" placeholder="{{ __('messages.disciple_grades.search_placeholder') }}" value="{{ $search }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">{{ __('messages.disciple_grades.current_grade') }}</label>
                <select name="grade_id" class="form-select js-auto-filter">
                    <option value="">{{ __('messages.disciple_grades.all_grades') }}</option>
                    @foreach($grades as $grade)
                        <option value="{{ $grade->id }}" {{ (string) $gradeId === (string) $grade->id ? 'selected' : '' }}>{{ $grade->nom_grade }} ({{ $grade->ceinture }})</option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>
</div>

<form method="POST" action="{{ route('admin.disciples.grades.apply') }}" id="gradeForm">
    @csrf
    @if($search !== '')<input type="hidden" name="search" value="{{ $search }}">@endif
    @if($gradeId)<input type="hidden" name="grade_id" value="{{ $gradeId }}">@endif

    <div class="card card-navbar shadow-sm">
        <div class="card-header card-header-navbar d-flex flex-wrap gap-2 justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
                <input type="checkbox" class="form-check-input" id="selectAll" checked>
                <label for="selectAll" class="mb-0">{{ __('messages.disciple_grades.select_all') }}</label>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <select name="update_mode" id="updateMode" class="form-select form-select-sm">
                    <option value="next">{{ __('messages.disciple_grades.mode_next') }}</option>
                    <option value="custom">{{ __('messages.disciple_grades.mode_custom') }}</option>
                </select>
                <select name="target_grade_id" id="targetGrade" class="form-select form-select-sm" disabled>
                    <option value="">{{ __('messages.disciple_grades.target_grade') }}</option>
                    @foreach($grades as $grade)
                        <option value="{{ $grade->id }}">{{ $grade->nom_grade }} ({{ $grade->ceinture }})</option>
                    @endforeach
                </select>
                <div class="form-check d-flex align-items-center gap-1 mb-0">
                    <input class="form-check-input" type="checkbox" name="refresh_date" value="1" id="refreshDate">
                    <label class="form-check-label mb-0" for="refreshDate">{{ __('messages.disciple_grades.refresh_date') }}</label>
                </div>
                <button type="submit" class="btn btn-sm btn-outline-light" formaction="{{ route('admin.disciples.grades.attestations.selection') }}" formtarget="_blank">
                    <i class="fas fa-file-pdf me-1"></i> {{ __('messages.disciple_grades.print_attestations') }}
                </button>
                <button type="submit" class="btn btn-sm btn-warning fw-semibold">
                    <i class="fas fa-save me-1"></i> {{ __('messages.disciple_grades.apply') }}
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-striped table-hover table-bordered align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width: 34px;"></th>
                        <th>{{ __('messages.full_name') }}</th>
                        <th>{{ __('messages.disciple_grades.current_grade') }}</th>
                        <th>{{ __('messages.disciple_grades.next_grade') }}</th>
                        <th style="width: 80px;"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($disciples as $disciple)
                        @php $nextGradeId = $nextGradeMap[$disciple->grade_id ?? 0] ?? null; @endphp
                        <tr>
                            <td><input type="checkbox" class="form-check-input js-disciple-check" name="disciple_ids[]" value="{{ $disciple->id }}" checked></td>
                            <td class="fw-semibold">{{ $disciple->full_name }}</td>
                            <td>{{ $disciple->grade?->nom_grade ?? __('messages.disciple_grades.no_grade') }}</td>
                            <td>
                                @if($nextGradeId)
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">{{ $grades->firstWhere('id', $nextGradeId)?->nom_grade }}</span>
                                @else
                                    <span class="text-muted small">{{ __('messages.disciple_grades.no_higher_grade') }}</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('admin.disciples.grades.attestation', $disciple) }}" class="btn btn-sm btn-outline-secondary" title="{{ __('messages.disciple_grades.attestation') }}" target="_blank" @if(!$disciple->grade_id) tabindex="-1" style="pointer-events:none;opacity:.3;" @endif>
                                    <i class="fas fa-file-pdf"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">{{ __('messages.disciple_grades.not_found') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</form>
@endsection

@section('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('filterForm');
    var t = null;
    form.querySelectorAll('.js-auto-filter').forEach(function (i) { i.addEventListener('change', function () { form.submit(); }); });
    form.querySelectorAll('.js-auto-filter-text').forEach(function (i) { i.addEventListener('input', function () { clearTimeout(t); t = setTimeout(function () { form.submit(); }, 500); }); });

    var selectAll = document.getElementById('selectAll');
    var checks = Array.from(document.querySelectorAll('.js-disciple-check'));
    selectAll.addEventListener('change', function () { checks.forEach(function (c) { c.checked = selectAll.checked; }); });
    checks.forEach(function (c) { c.addEventListener('change', function () {
        selectAll.checked = checks.every(function (i) { return i.checked; });
    }); });

    var updateMode = document.getElementById('updateMode');
    var targetGrade = document.getElementById('targetGrade');
    function syncTargetGrade() {
        var isCustom = updateMode.value === 'custom';
        targetGrade.disabled = !isCustom;
        targetGrade.required = isCustom;
    }
    updateMode.addEventListener('change', syncTargetGrade);
    syncTargetGrade();
});
</script>
@endsection
