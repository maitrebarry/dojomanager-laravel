@extends('layouts.admin')

@section('title', __('messages.disciples.title'))

@section('actions')
    @if(Auth::user() && (Auth::user()->isSuperAdmin() || Auth::user()->hasPermission('DISCIPLE_CREATE')))
        <a href="{{ route('admin.disciples.create') }}" class="btn text-white shadow-sm" style="background-color: var(--navbar-bg);">
            <i class="fas fa-plus-circle me-1"></i> {{ __('messages.disciples.add') }}
        </a>
    @endif
@endsection

@section('content')
{{-- Liste des disciples (copie fidèle de Disciples.jsx) --}}
<div class="card card-navbar shadow-sm">
    <div class="card-header card-header-navbar d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="mb-0"><i class="fas fa-user-ninja me-2"></i> {{ __('messages.disciples.list_title') }}</h5>
        <div class="d-flex gap-2 align-items-center flex-wrap">
            <button type="button" class="btn btn-sm btn-outline-light" id="btnSelectAll">
                <i class="fas fa-check-double me-1"></i> {{ __('messages.disciples.select_all') }}
            </button>
            <button type="button" class="btn btn-sm btn-outline-light" id="btnClearSel">
                <i class="fas fa-rotate-left me-1"></i> {{ __('messages.reset') }}
            </button>
            <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#signatureModal">
                <i class="fas fa-signature me-1"></i> {{ __('messages.licences.sign') }}
            </button>
            <button type="button" class="btn btn-sm btn-light text-dark fw-semibold" id="btnPrintLicences" disabled>
                <i class="fas fa-id-card me-1"></i> {{ __('messages.licences.print') }} (<span id="printCount">0</span>)
            </button>
            <span class="badge bg-warning text-dark" id="sigStatus">⚠️ {{ __('messages.licences.signature_missing') }}</span>
            <span class="badge bg-light text-dark" id="selCount">0 {{ __('messages.disciples.selected') }}</span>
        </div>
    </div>
    <div class="card-body">
        {{-- Filtres : recherche + afficher archivés --}}
        <form method="GET" action="{{ route('admin.disciples.index') }}" id="filterForm" class="row mb-3">
            <div class="col-md-6">
                <label class="form-label">{{ __('messages.search') }}</label>
                <input type="text" name="search" class="form-control js-auto-filter-text" placeholder="{{ __('messages.disciples.search_placeholder') }}" value="{{ $search }}">
            </div>
            <div class="col-md-6 d-flex align-items-end gap-2 flex-wrap">
                <div class="form-check mb-1 me-2">
                    <input class="form-check-input js-auto-filter" type="checkbox" id="showArchived" name="archived" value="1" {{ $showArchived ? 'checked' : '' }}>
                    <label class="form-check-label" for="showArchived">{{ __('messages.disciples.show_archived') }}</label>
                </div>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-striped table-hover table-bordered align-middle">
                <thead>
                    <tr>
                        <th style="width: 34px;"></th>
                        <th style="width: 48px;">N°</th>
                        <th>{{ __('messages.full_name') }}</th>
                        <th>{{ __('messages.disciples.age') }}</th>
                        <th>{{ __('messages.grade') }}</th>
                        <th>{{ __('messages.salle') }}</th>
                        <th>{{ __('messages.status') }}</th>
                        <th style="width: 185px; white-space: nowrap;">{{ __('messages.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($disciples as $index => $d)
                        <tr class="{{ $d->is_archived ? 'table-secondary' : '' }}">
                            <td>
                                <input type="checkbox" class="form-check-input js-badge-check" value="{{ $d->id }}" {{ $d->is_archived ? 'disabled' : '' }}>
                            </td>
                            <td>{{ $index + 1 }}</td>
                            <td class="fw-semibold">{{ $d->full_name }}</td>
                            <td>{{ $d->age !== null ? $d->age . ' ' . __('messages.disciples.years') : '-' }}</td>
                            <td>{{ $d->grade?->nom_grade ?? '-' }}</td>
                            <td>{{ $d->salle?->nom ?? '-' }}</td>
                            <td>
                                <span class="badge bg-{{ $d->is_archived ? 'secondary' : 'success' }}">
                                    {{ $d->is_archived ? __('messages.disciples.archived') : __('messages.disciples.active') }}
                                </span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-1 flex-nowrap">
                                    <a href="{{ route('admin.disciples.show', $d) }}" class="btn btn-sm btn-outline-secondary" title="{{ __('messages.view') }}"><i class="fas fa-eye"></i></a>
                                    @if(Auth::user() && (Auth::user()->isSuperAdmin() || Auth::user()->hasPermission('DISCIPLE_UPDATE')))
                                        <a href="{{ route('admin.disciples.edit', $d) }}" class="btn btn-sm btn-info" title="{{ __('messages.edit') }}"><i class="fas fa-pen"></i></a>
                                        @if($d->is_archived)
                                            <form action="{{ route('admin.disciples.restore', $d) }}" method="POST" class="d-inline m-0">
                                                @csrf @method('PATCH')
                                                <button type="submit" class="btn btn-sm btn-success" title="{{ __('messages.disciples.restore') }}"><i class="fas fa-rotate-left"></i></button>
                                            </form>
                                        @else
                                            <form action="{{ route('admin.disciples.archive', $d) }}" method="POST" class="d-inline m-0">
                                                @csrf @method('PATCH')
                                                <button type="submit" class="btn btn-sm btn-warning" title="{{ __('messages.disciples.archive') }}"><i class="fas fa-box-archive"></i></button>
                                            </form>
                                        @endif
                                    @endif
                                    @if(Auth::user() && (Auth::user()->isSuperAdmin() || Auth::user()->hasPermission('DISCIPLE_DELETE')))
                                        <form action="{{ route('admin.disciples.destroy', $d) }}" method="POST" class="d-inline m-0" onsubmit="return dojoConfirmDelete(this, '{{ __('messages.disciples.delete_question') }}');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" title="{{ __('messages.delete') }}"><i class="fas fa-trash"></i></button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-4">{{ __('messages.disciples.not_found') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@include('admin.partials._signature_modal')
@endsection

@section('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Filtres auto-submit (recherche + case archivés)
    var form = document.getElementById('filterForm');
    var t = null;
    form.querySelectorAll('.js-auto-filter').forEach(function (i) { i.addEventListener('change', function () { form.submit(); }); });
    form.querySelectorAll('.js-auto-filter-text').forEach(function (i) { i.addEventListener('input', function () { clearTimeout(t); t = setTimeout(function () { form.submit(); }, 500); }); });

    // Sélection (badges) — comme React
    var checks = Array.from(document.querySelectorAll('.js-badge-check'));
    var counter = document.getElementById('selCount');
    var printBtn = document.getElementById('btnPrintLicences');
    var printCount = document.getElementById('printCount');
    var licencesUrl = '{{ route('admin.licences.disciples') }}';
    function selectedIds() { return checks.filter(function (c) { return c.checked; }).map(function (c) { return c.value; }); }
    function refresh() {
        var ids = selectedIds();
        counter.textContent = ids.length + ' {{ __('messages.disciples.selected') }}';
        printCount.textContent = ids.length;
        printBtn.disabled = ids.length === 0;
    }
    checks.forEach(function (c) { c.addEventListener('change', refresh); });
    document.getElementById('btnSelectAll').addEventListener('click', function () { checks.forEach(function (c) { if (!c.disabled) c.checked = true; }); refresh(); });
    document.getElementById('btnClearSel').addEventListener('click', function () { checks.forEach(function (c) { c.checked = false; }); refresh(); });
    printBtn.addEventListener('click', function () {
        var ids = selectedIds();
        if (ids.length === 0) return;
        window.open(licencesUrl + '?ids=' + ids.join(','), '_blank');
    });
    refresh();
});
</script>
@endsection
