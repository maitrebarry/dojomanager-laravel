@extends('layouts.admin')

@section('title', __('messages.ceintures_noires.title'))

@section('actions')
    @if(Auth::user() && (Auth::user()->isSuperAdmin() || Auth::user()->hasPermission('CEINTURESNOIRES_CREATE')))
        <a href="{{ route('admin.ceintures-noires.create') }}" class="btn text-white shadow-sm" style="background-color: var(--navbar-bg);">
            <i class="fas fa-plus-circle me-1"></i> {{ __('messages.ceintures_noires.add') }}
        </a>
    @endif
@endsection

@section('content')
<div class="card card-navbar shadow-sm">
    <div class="card-header card-header-navbar py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <form method="GET" action="{{ route('admin.ceintures-noires.index') }}" class="flex-grow-1" id="filterForm" style="max-width: 420px;">
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                <input type="text" name="search" class="form-control border-start-0 js-auto-filter-text" placeholder="{{ __('messages.search') }}" value="{{ request('search') }}">
            </div>
        </form>
        <div class="d-flex gap-2 align-items-center flex-wrap">
            <button type="button" class="btn btn-sm btn-outline-light" id="btnSelectAllCn"><i class="fas fa-check-double me-1"></i> {{ __('messages.disciples.select_all') }}</button>
            <button type="button" class="btn btn-sm btn-outline-light" id="btnClearCn"><i class="fas fa-rotate-left me-1"></i> {{ __('messages.reset') }}</button>
            <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#signatureModal">
                <i class="fas fa-signature me-1"></i> {{ __('messages.licences.sign') }}
            </button>
            <button type="button" class="btn btn-sm btn-light text-dark fw-semibold" id="btnPrintLicencesCn" disabled>
                <i class="fas fa-id-card me-1"></i> {{ __('messages.licences.print') }} (<span id="printCountCn">0</span>)
            </button>
            <span class="badge bg-warning text-dark" id="sigStatus">⚠️ {{ __('messages.licences.signature_missing') }}</span>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 34px;"></th>
                    <th>{{ __('messages.full_name') }}</th>
                    <th>{{ __('messages.gender') }}</th>
                    <th>{{ __('messages.grade') }}</th>
                    <th>{{ __('messages.salle') }}</th>
                    <th>{{ __('messages.ceintures_noires.origin') }}</th>
                    <th style="width: 120px;" class="text-end">{{ __('messages.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($ceinturesNoires as $cn)
                    <tr>
                        <td>
                            @php
                                $checkKind = ['DISCIPLE' => 'disciple', 'GESTIONNAIRE' => 'user', 'MANUELLE' => 'manuelle'][$cn->origine] ?? null;
                            @endphp
                            @if($checkKind)
                                <input type="checkbox" class="form-check-input js-licence-check" data-kind="{{ $checkKind }}" value="{{ $cn->id }}">
                            @endif
                        </td>
                        <td class="fw-semibold">{{ $cn->full_name }}</td>
                        <td>{{ $cn->sexe === 'F' ? __('messages.female') : ($cn->sexe === 'M' ? __('messages.male') : '-') }}</td>
                        <td><span class="badge bg-dark">{{ $cn->grade_nom ?? '-' }}</span></td>
                        <td>{{ $cn->salle_nom ?? '-' }}</td>
                        <td>
                            @php
                                $originBadge = ['MANUELLE' => 'info', 'DISCIPLE' => 'secondary', 'GESTIONNAIRE' => 'success'][$cn->origine] ?? 'secondary';
                                $originLabel = [
                                    'MANUELLE' => __('messages.ceintures_noires.manual'),
                                    'DISCIPLE' => __('messages.ceintures_noires.disciple'),
                                    'GESTIONNAIRE' => __('messages.ceintures_noires.manager'),
                                ][$cn->origine] ?? __('messages.ceintures_noires.disciple');
                            @endphp
                            <span class="badge bg-{{ $originBadge }}">{{ $originLabel }}</span>
                        </td>
                        <td class="text-end">
                            @if($cn->editable)
                                <div class="btn-group btn-group-sm" role="group">
                                    @if(Auth::user() && (Auth::user()->isSuperAdmin() || Auth::user()->hasPermission('CEINTURESNOIRES_UPDATE')))
                                        <a href="{{ route('admin.ceintures-noires.edit', $cn->id) }}" class="btn btn-outline-primary" title="{{ __('messages.edit') }}"><i class="fas fa-edit"></i></a>
                                    @endif
                                    @if(Auth::user() && (Auth::user()->isSuperAdmin() || Auth::user()->hasPermission('CEINTURESNOIRES_DELETE')))
                                        <button type="button" class="btn btn-outline-danger btn-delete" data-id="{{ $cn->id }}" data-name="{{ $cn->full_name }}" title="{{ __('messages.delete') }}"><i class="fas fa-trash"></i></button>
                                    @endif
                                </div>
                            @elseif($cn->edit_maitre_url)
                                @if(Auth::user() && (Auth::user()->isSuperAdmin() || Auth::user()->hasPermission('CEINTURESNOIRES_UPDATE')))
                                    <a href="{{ $cn->edit_maitre_url }}" class="btn btn-sm btn-outline-primary" title="{{ __('messages.ceintures_noires.edit_manager') }}"><i class="fas fa-edit"></i></a>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            @else
                                <span class="text-muted small">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center py-5 text-muted">{{ __('messages.ceintures_noires.empty') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@include('admin.partials._delete_modal', ['question' => __('messages.ceintures_noires.delete_question')])
@include('admin.partials._signature_modal')
@endsection

@section('js')
@include('admin.partials._list_scripts', ['baseUrl' => url('admin/ceintures-noires')])
<script>
document.addEventListener('DOMContentLoaded', function () {
    var checks = Array.from(document.querySelectorAll('.js-licence-check'));
    var printBtn = document.getElementById('btnPrintLicencesCn');
    var printCount = document.getElementById('printCountCn');
    var licencesUrl = '{{ route('admin.licences.disciples') }}';
    function selectedIds(kind) {
        return checks.filter(function (c) { return c.checked && (c.dataset.kind || 'disciple') === kind; }).map(function (c) { return c.value; });
    }
    function refresh() {
        var total = checks.filter(function (c) { return c.checked; }).length;
        printCount.textContent = total;
        printBtn.disabled = total === 0;
    }
    checks.forEach(function (c) { c.addEventListener('change', refresh); });
    document.getElementById('btnSelectAllCn').addEventListener('click', function () { checks.forEach(function (c) { c.checked = true; }); refresh(); });
    document.getElementById('btnClearCn').addEventListener('click', function () { checks.forEach(function (c) { c.checked = false; }); refresh(); });
    printBtn.addEventListener('click', function () {
        var discipleIds = selectedIds('disciple');
        var userIds = selectedIds('user');
        var manuelleIds = selectedIds('manuelle');
        if (discipleIds.length === 0 && userIds.length === 0 && manuelleIds.length === 0) return;
        var params = [];
        if (discipleIds.length) params.push('ids=' + discipleIds.join(','));
        if (userIds.length) params.push('user_ids=' + userIds.join(','));
        if (manuelleIds.length) params.push('manuelle_ids=' + manuelleIds.join(','));
        window.open(licencesUrl + '?' + params.join('&'), '_blank');
    });
    refresh();
});
</script>
@endsection
