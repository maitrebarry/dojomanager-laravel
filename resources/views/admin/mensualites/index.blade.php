@extends('layouts.admin')

@section('title', __('messages.cotisations.title'))

@section('content')
{{-- Copie fidèle de Mensualites.jsx --}}
<div class="card border-primary shadow-sm mb-3">
    <div class="card-header card-header-navbar">
        <h5 class="mb-0"><i class="fas fa-money-bill-wave me-2"></i> {{ __('messages.cotisations.title') }}</h5>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('admin.mensualites.index') }}" id="filterForm" class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label">{{ __('messages.cotisations.month') }}</label>
                <select name="mois" class="form-select js-auto-filter">
                    @foreach($moisLabels as $num => $label)
                        <option value="{{ $num }}" {{ $mois === $num ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">{{ __('messages.cotisations.year') }}</label>
                <input type="number" name="annee" class="form-control js-auto-filter" value="{{ $annee }}" min="2000" max="2100">
            </div>
            <div class="col-md-2">
                <label class="form-label">{{ __('messages.cotisations.monthly_ref') }}</label>
                <input type="number" step="0.01" min="0" name="montant" class="form-control js-auto-filter" value="{{ (int) $montantAttendu }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ __('messages.search') }}</label>
                <input type="text" name="search" class="form-control js-auto-filter-text" placeholder="{{ __('messages.full_name') }}" value="{{ $search }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ __('messages.status') }}</label>
                <select name="statut" class="form-select js-auto-filter">
                    <option value="">{{ __('messages.all') }}</option>
                    <option value="IMPAYE" {{ $statut === 'IMPAYE' ? 'selected' : '' }}>{{ __('messages.cotisations.unpaid') }}</option>
                    <option value="PARTIEL" {{ $statut === 'PARTIEL' ? 'selected' : '' }}>{{ __('messages.cotisations.partial') }}</option>
                    <option value="PAYE" {{ $statut === 'PAYE' ? 'selected' : '' }}>{{ __('messages.cotisations.paid') }}</option>
                </select>
            </div>
        </form>
    </div>
</div>

{{-- Paiement groupé --}}
@if(Auth::user() && (Auth::user()->isSuperAdmin() || Auth::user()->hasPermission('COTISATION_MANAGE') || Auth::user()->isTenantAdmin()))
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.mensualites.bulk-pay') }}" id="bulkForm" class="row g-2 align-items-end" onsubmit="return prepareBulk();">
            @csrf
            <input type="hidden" name="mois" value="{{ $mois }}"><input type="hidden" name="annee" value="{{ $annee }}">
            <input type="hidden" name="ids" id="bulkIds">
            <div class="col-md-4">
                <h6 class="mb-1"><i class="fas fa-layer-group me-2"></i> {{ __('messages.cotisations.bulk_title') }}</h6>
                <div class="text-muted small">{{ __('messages.cotisations.bulk_help') }}</div>
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ __('messages.cotisations.amount_per') }}</label>
                <input type="number" step="0.01" min="0" name="montant" class="form-control" required>
            </div>
            <div class="col-md-5 d-flex gap-2">
                <button type="button" class="btn btn-outline-secondary" id="btnSelAll">{{ __('messages.disciples.select_all') }}</button>
                <button type="submit" class="btn btn-success"><i class="fas fa-check me-1"></i> {{ __('messages.cotisations.bulk_submit') }}</button>
            </div>
        </form>
    </div>
</div>
@endif

<div class="card card-navbar shadow-sm">
    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 34px;"><input type="checkbox" class="form-check-input" id="checkAll"></th>
                    <th>{{ __('messages.disciples.title') }}</th>
                    <th>{{ __('messages.salle') }}</th>
                    <th class="text-end">{{ __('messages.cotisations.title_singular') }}</th>
                    <th class="text-end">{{ __('messages.cotisations.paid_amount') }}</th>
                    <th class="text-end">{{ __('messages.cotisations.remaining') }}</th>
                    <th>{{ __('messages.status') }}</th>
                    <th style="width: 150px;">{{ __('messages.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($cotisations as $c)
                    @php $reste = (float) $c->reste_a_payer; $payable = $c->statut !== 'PAYE' && $reste > 0; @endphp
                    <tr>
                        <td><input type="checkbox" class="form-check-input js-pay-check" value="{{ $c->id }}" {{ $payable ? '' : 'disabled' }}></td>
                        <td class="fw-semibold">{{ $c->disciple?->full_name }}</td>
                        <td>{{ $c->disciple?->salle?->nom ?? '-' }}</td>
                        <td class="text-end">{{ number_format($c->montant, 0, ',', ' ') }}</td>
                        <td class="text-end">{{ number_format($c->montant_paye, 0, ',', ' ') }}</td>
                        <td class="text-end fw-semibold">{{ number_format($reste, 0, ',', ' ') }} FCFA</td>
                        <td><span class="badge bg-{{ $c->statutColor() }}">{{ $c->statut === 'PAYE' ? __('messages.cotisations.paid') : ($c->statut === 'PARTIEL' ? __('messages.cotisations.partial') : __('messages.cotisations.unpaid')) }}</span></td>
                        <td style="white-space: nowrap;">
                            <div class="d-flex align-items-center gap-1 flex-nowrap">
                                @if($payable && Auth::user() && (Auth::user()->isSuperAdmin() || Auth::user()->hasPermission('COTISATION_MANAGE') || Auth::user()->isTenantAdmin()))
                                    <button type="button" class="btn btn-sm btn-primary btn-pay" data-id="{{ $c->id }}" data-name="{{ $c->disciple?->full_name }}" data-reste="{{ $reste }}"><i class="fas fa-hand-holding-dollar me-1"></i>{{ __('messages.cotisations.pay') }}</button>
                                @endif
                                <a href="{{ route('admin.mensualites.receipt', $c) }}" target="_blank" class="btn btn-sm btn-outline-success {{ $c->paiements->count() ? '' : 'disabled' }}"><i class="fas fa-receipt me-1"></i>{{ __('messages.cotisations.receipt') }}</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">{{ __('messages.cotisations.empty') }}</td></tr>
                @endforelse
            </tbody>
            @if($cotisations->count())
                <tfoot class="table-light fw-bold">
                    <tr>
                        <td colspan="3" class="text-end">{{ __('messages.cotisations.totals') }}</td>
                        <td class="text-end">{{ number_format($totaux['du'], 0, ',', ' ') }}</td>
                        <td class="text-end text-success">{{ number_format($totaux['paye'], 0, ',', ' ') }}</td>
                        <td class="text-end text-danger">{{ number_format($totaux['reste'], 0, ',', ' ') }}</td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
</div>

{{-- Modale paiement --}}
<div class="modal fade" id="payModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" id="payForm" class="modal-content">
            @csrf
            <div class="modal-header" style="background-color: var(--navbar-bg); color: var(--navbar-text);">
                <h5 class="modal-title"><i class="fas fa-hand-holding-dollar me-2"></i> {{ __('messages.cotisations.pay') }}</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3"><strong id="payName"></strong> — {{ __('messages.cotisations.remaining') }}: <span id="payReste" class="text-danger fw-bold"></span></p>
                <div class="mb-3">
                    <label class="form-label">{{ __('messages.cotisations.amount_paid') }} <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" min="0.01" name="montant" id="payMontant" class="form-control" required>
                </div>
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label">{{ __('messages.cotisations.mode') }}</label>
                        <select name="mode_paiement" class="form-select" required>
                            <option value="ESPECES">{{ __('messages.cotisations.cash') }}</option>
                            <option value="MOBILE_MONEY">Mobile Money</option>
                            <option value="VIREMENT">{{ __('messages.cotisations.transfer') }}</option>
                            <option value="CHEQUE">{{ __('messages.cotisations.cheque') }}</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Date</label>
                        <input type="date" name="date_paiement" class="form-control" value="{{ now()->toDateString() }}" required>
                    </div>
                </div>
                <div class="mt-3"><label class="form-label">{{ __('messages.cotisations.reference') }}</label><input type="text" name="reference_paiement" class="form-control"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('messages.cancel') }}</button>
                <button type="submit" class="btn btn-success">{{ __('messages.save') }}</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('filterForm');
    var t = null;
    form.querySelectorAll('.js-auto-filter').forEach(function (i) { i.addEventListener('change', function () { form.submit(); }); });
    form.querySelectorAll('.js-auto-filter-text').forEach(function (i) { i.addEventListener('input', function () { clearTimeout(t); t = setTimeout(function () { form.submit(); }, 500); }); });

    // Paiement individuel
    var payModal = new bootstrap.Modal(document.getElementById('payModal'));
    var payBase = "{{ url('admin/mensualites') }}";
    document.querySelectorAll('.btn-pay').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('payName').textContent = this.dataset.name || '';
            document.getElementById('payReste').textContent = new Intl.NumberFormat('fr-FR').format(this.dataset.reste) + ' FCFA';
            document.getElementById('payMontant').value = this.dataset.reste;
            document.getElementById('payForm').action = payBase + '/' + this.dataset.id + '/payer';
            payModal.show();
        });
    });

    // Sélection + paiement groupé
    var payChecks = Array.from(document.querySelectorAll('.js-pay-check'));
    var checkAll = document.getElementById('checkAll');
    if (checkAll) checkAll.addEventListener('change', function () { payChecks.forEach(function (c) { if (!c.disabled) c.checked = checkAll.checked; }); });
    var btnSelAll = document.getElementById('btnSelAll');
    if (btnSelAll) btnSelAll.addEventListener('click', function () { payChecks.forEach(function (c) { if (!c.disabled) c.checked = true; }); });
    window.prepareBulk = function () {
        var ids = payChecks.filter(function (c) { return c.checked; }).map(function (c) { return c.value; });
        if (!ids.length) { window.dojoToast && window.dojoToast('warning', @json(__('messages.cotisations.bulk_none'))); return false; }
        document.getElementById('bulkIds').value = ids.join(',');
        return true;
    };
});
</script>
@endsection
