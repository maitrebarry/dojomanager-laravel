@extends('layouts.admin')

@section('title', __('messages.cotisations_annuelles.title') . ' ' . $cotisation->annee)

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('messages.dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.cotisations-annuelles.index') }}">{{ __('messages.cotisations_annuelles.title') }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $cotisation->annee }}</li>
@endsection

@section('content')
<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">{{ __('messages.cotisations.amount') }}</div><div class="h4 mb-0">{{ number_format($cotisation->montant, 0, ',', ' ') }}</div></div></div></div>
    <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">{{ __('messages.cotisations_annuelles.total_due') }}</div><div class="h4 mb-0">{{ number_format($totaux['du'], 0, ',', ' ') }}</div></div></div></div>
    <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">{{ __('messages.cotisations_annuelles.total_collected') }}</div><div class="h4 mb-0 text-success">{{ number_format($totaux['paye'], 0, ',', ' ') }}</div></div></div></div>
    <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">{{ __('messages.cotisations.remaining') }}</div><div class="h4 mb-0 text-danger">{{ number_format($totaux['reste'], 0, ',', ' ') }}</div></div></div></div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>{{ __('messages.full_name') }}</th>
                    <th>{{ __('messages.grade') }}</th>
                    <th>{{ __('messages.salle') }}</th>
                    <th class="text-end">{{ __('messages.cotisations.paid_amount') }}</th>
                    <th class="text-end">{{ __('messages.cotisations.remaining') }}</th>
                    <th>{{ __('messages.status') }}</th>
                    <th style="width: 80px;" class="text-end">{{ __('messages.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($membres as $m)
                    <tr>
                        <td class="fw-semibold">{{ $m->fullName() }}</td>
                        <td>{{ $m->grade_nom ?? '-' }}</td>
                        <td>{{ $m->salle_nom ?? '-' }}</td>
                        <td class="text-end">{{ number_format($m->montant_paye, 0, ',', ' ') }}</td>
                        <td class="text-end">{{ number_format($m->reste_a_payer, 0, ',', ' ') }}</td>
                        <td><span class="badge bg-{{ $m->statutColor() }}">{{ $m->statut }}</span></td>
                        <td class="text-end">
                            @if($m->statut !== 'PAYE' && Auth::user() && (Auth::user()->isSuperAdmin() || Auth::user()->hasPermission('COTISATION_MANAGE')))
                                <button type="button" class="btn btn-sm btn-outline-success btn-pay"
                                        data-id="{{ $m->id }}" data-name="{{ $m->fullName() }}" data-reste="{{ $m->reste_a_payer }}"
                                        title="{{ __('messages.cotisations.pay') }}"><i class="fas fa-hand-holding-dollar"></i></button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center py-5 text-muted">{{ __('messages.cotisations_annuelles.no_members') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($membres->hasPages())
        <div class="card-footer bg-white border-top-0 pt-3">{{ $membres->links() }}</div>
    @endif
</div>

{{-- Modale paiement --}}
<div class="modal fade" id="payModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" id="payForm" class="modal-content">
            @csrf
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fas fa-hand-holding-dollar me-2"></i> {{ __('messages.cotisations.pay') }}</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3"><strong id="payName"></strong> — {{ __('messages.cotisations.remaining') }}: <span id="payReste" class="text-danger fw-bold"></span></p>
                <div class="mb-3">
                    <label class="form-label">{{ __('messages.cotisations.amount') }} <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" min="0.01" name="montant" id="payMontant" class="form-control" required>
                </div>
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label">{{ __('messages.cotisations.mode') }} <span class="text-danger">*</span></label>
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
                <div class="mt-3">
                    <label class="form-label">{{ __('messages.cotisations.reference') }}</label>
                    <input type="text" name="reference_paiement" class="form-control">
                </div>
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
document.addEventListener('DOMContentLoaded', function() {
    const payModal = new bootstrap.Modal(document.getElementById('payModal'));
    const base = "{{ url('admin/cotisations-annuelles/membres') }}";
    document.querySelectorAll('.btn-pay').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('payName').textContent = this.dataset.name || '';
            document.getElementById('payReste').textContent = new Intl.NumberFormat('fr-FR').format(this.dataset.reste) + ' FCFA';
            document.getElementById('payMontant').value = this.dataset.reste;
            document.getElementById('payForm').action = `${base}/${this.dataset.id}/payer`;
            payModal.show();
        });
    });
});
</script>
@endsection
