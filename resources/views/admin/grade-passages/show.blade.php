@extends('layouts.admin')

@section('title', __('messages.grade_passages.session'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('messages.dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.grade-passages.index') }}">{{ __('messages.grade_passages.title') }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $session->date_session?->format('d/m/Y') }}</li>
@endsection

@section('actions')
    @if(!$session->finalisee && Auth::user() && (Auth::user()->isSuperAdmin() || Auth::user()->hasPermission('PASSAGEGRADES_MANAGE')))
        <a href="{{ route('admin.grade-passages.edit', $session) }}" class="btn btn-outline-primary shadow-sm"><i class="fas fa-edit me-1"></i> {{ __('messages.edit') }}</a>
        <form action="{{ route('admin.grade-passages.finalize', $session) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('messages.grade_passages.finalize_confirm') }}');">
            @csrf
            <button type="submit" class="btn btn-success shadow-sm"><i class="fas fa-lock me-1"></i> {{ __('messages.grade_passages.finalize') }}</button>
        </form>
    @endif
@endsection

@section('content')
<div class="row g-3 mb-4">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h5 class="fw-bold mb-1">{{ $session->lieu }}</h5>
                        <div class="text-muted">{{ $session->date_session?->format('d/m/Y') }} · <span class="badge bg-{{ $session->type_grade === 'DAN' ? 'dark' : 'info' }}">{{ $session->type_grade }}</span></div>
                    </div>
                    <span class="badge bg-{{ $session->finalisee ? 'success' : 'warning' }} fs-6">{{ $session->finalisee ? __('messages.grade_passages.finalized_badge') : __('messages.grade_passages.open') }}</span>
                </div>
                @if($session->annonce)
                    <p class="text-muted mt-3 mb-0">{{ $session->annonce }}</p>
                @endif
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">{{ __('messages.grade_passages.fee') }}</div>
                <div class="h4">{{ number_format($session->frais_participation, 0, ',', ' ') }} FCFA</div>
                <div class="text-muted small mt-2">{{ __('messages.grade_passages.candidates') }}: <strong>{{ $session->candidats->count() }}</strong></div>
            </div>
        </div>
    </div>
</div>

@if(!$session->finalisee && Auth::user() && (Auth::user()->isSuperAdmin() || Auth::user()->hasPermission('PASSAGEGRADES_MANAGE')))
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3"><h6 class="mb-0"><i class="fas fa-user-plus me-2"></i> {{ __('messages.grade_passages.add_candidate') }}</h6></div>
    <div class="card-body">
        <form action="{{ route('admin.grade-passages.candidats.add', $session) }}" method="POST" class="row g-2 align-items-end">
            @csrf
            <div class="col-md-5">
                <label class="form-label">{{ __('messages.disciples.title') }}</label>
                <select name="disciple_id" class="form-select" required>
                    <option value="">-</option>
                    @foreach($disciples as $d)
                        <option value="{{ $d->id }}">{{ $d->full_name }} @if($d->grade)— {{ $d->grade->nom_grade }}@endif</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">{{ __('messages.grade_passages.proposed_grade') }}</label>
                <select name="proposed_grade_id" class="form-select" required>
                    <option value="">-</option>
                    @foreach($grades as $g)
                        <option value="{{ $g->id }}">{{ $g->nom_grade }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">{{ __('messages.grade_passages.fee') }}</label>
                <input type="number" step="0.01" min="0" name="frais_participation" class="form-control" value="{{ $session->frais_participation }}">
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-plus"></i></button>
            </div>
        </form>
    </div>
</div>
@endif

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3"><h6 class="mb-0"><i class="fas fa-users me-2"></i> {{ __('messages.grade_passages.candidates') }}</h6></div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>{{ __('messages.full_name') }}</th>
                    <th>{{ __('messages.grade_passages.grade_change') }}</th>
                    <th class="text-end">{{ __('messages.cotisations.paid_amount') }}/{{ __('messages.grade_passages.fee') }}</th>
                    <th>{{ __('messages.grade_passages.payment') }}</th>
                    <th>{{ __('messages.grade_passages.result') }}</th>
                    <th style="width: 180px;" class="text-end">{{ __('messages.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($session->candidats as $c)
                    <tr>
                        <td class="fw-semibold">{{ $c->fullName() }}<div class="text-muted small">{{ $c->salle_nom }}</div></td>
                        <td>{{ $c->current_grade_nom ?? '-' }} <i class="fas fa-arrow-right text-muted mx-1"></i> <strong>{{ $c->proposed_grade_nom ?? '-' }}</strong></td>
                        <td class="text-end">{{ number_format($c->montant_paye, 0, ',', ' ') }} / {{ number_format($c->frais_participation, 0, ',', ' ') }}</td>
                        <td><span class="badge bg-{{ $c->paiementColor() }}">{{ $c->statut_paiement }}</span></td>
                        <td>
                            <span class="badge bg-{{ $c->statutColor() }}">{{ $c->statut }}</span>
                            @if($c->note_globale !== null)<div class="text-muted small">{{ $c->note_globale }}/100</div>@endif
                        </td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm" role="group">
                                @if(Auth::user() && (Auth::user()->isSuperAdmin() || Auth::user()->hasPermission('PASSAGEGRADES_MANAGE')))
                                    @if($c->resteAPayer() > 0)
                                        <button type="button" class="btn btn-outline-success btn-pay" data-id="{{ $c->id }}" data-name="{{ $c->fullName() }}" data-reste="{{ $c->resteAPayer() }}" title="{{ __('messages.cotisations.pay') }}"><i class="fas fa-hand-holding-dollar"></i></button>
                                    @endif
                                    @if(!$session->finalisee)
                                        <button type="button" class="btn btn-outline-primary btn-eval" data-id="{{ $c->id }}" data-name="{{ $c->fullName() }}" title="{{ __('messages.grade_passages.evaluate') }}"><i class="fas fa-clipboard-check"></i></button>
                                    @endif
                                @endif
                                @if($c->statut === 'VALIDE')
                                    <a href="{{ route('admin.grade-passages.candidats.attestation', [$session, $c]) }}" class="btn btn-outline-dark" title="{{ __('messages.grade_passages.attestation') }}"><i class="fas fa-award"></i></a>
                                @endif
                                @if(!$session->finalisee && Auth::user() && (Auth::user()->isSuperAdmin() || Auth::user()->hasPermission('PASSAGEGRADES_MANAGE')))
                                    <form action="{{ route('admin.grade-passages.candidats.remove', [$session, $c]) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('messages.grade_passages.remove_confirm') }}');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger" title="{{ __('messages.delete') }}"><i class="fas fa-trash"></i></button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center py-5 text-muted">{{ __('messages.grade_passages.no_candidates') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Modale paiement --}}
<div class="modal fade" id="payModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" id="payForm" class="modal-content">
            @csrf
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">{{ __('messages.cotisations.pay') }}</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p><strong id="payName"></strong> — {{ __('messages.cotisations.remaining') }}: <span id="payReste" class="text-danger fw-bold"></span></p>
                <div class="mb-3">
                    <label class="form-label">{{ __('messages.cotisations.amount') }} <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" min="0.01" name="montant" id="payMontant" class="form-control" required>
                </div>
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label">{{ __('messages.cotisations.mode') }}</label>
                        <select name="mode_paiement" class="form-select" required>
                            <option value="ESPECES">{{ __('messages.cotisations.cash') }}</option>
                            <option value="MOBILE_MONEY">Mobile Money</option>
                            <option value="VIREMENT">{{ __('messages.cotisations.transfer') }}</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Date</label>
                        <input type="date" name="date_paiement" class="form-control" value="{{ now()->toDateString() }}" required>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('messages.cancel') }}</button>
                <button type="submit" class="btn btn-success">{{ __('messages.save') }}</button>
            </div>
        </form>
    </div>
</div>

{{-- Modale évaluation --}}
<div class="modal fade" id="evalModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" id="evalForm" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">{{ __('messages.grade_passages.evaluate') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p><strong id="evalName"></strong></p>
                <div class="mb-3">
                    <label class="form-label">{{ __('messages.grade_passages.global_note') }} (/100)</label>
                    <input type="number" step="0.01" min="0" max="100" name="note_globale" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">{{ __('messages.grade_passages.result') }} <span class="text-danger">*</span></label>
                    <select name="resultat" class="form-select" required>
                        <option value="ADMIS">{{ __('messages.grade_passages.admitted') }}</option>
                        <option value="AJOURNE">{{ __('messages.grade_passages.deferred') }}</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('messages.cancel') }}</button>
                <button type="submit" class="btn btn-primary">{{ __('messages.save') }}</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('js')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const base = "{{ url('admin/grade-passages/' . $session->id . '/candidats') }}";

    const payModal = new bootstrap.Modal(document.getElementById('payModal'));
    document.querySelectorAll('.btn-pay').forEach(btn => btn.addEventListener('click', function() {
        document.getElementById('payName').textContent = this.dataset.name;
        document.getElementById('payReste').textContent = new Intl.NumberFormat('fr-FR').format(this.dataset.reste) + ' FCFA';
        document.getElementById('payMontant').value = this.dataset.reste;
        document.getElementById('payForm').action = `${base}/${this.dataset.id}/payer`;
        payModal.show();
    }));

    const evalModal = new bootstrap.Modal(document.getElementById('evalModal'));
    document.querySelectorAll('.btn-eval').forEach(btn => btn.addEventListener('click', function() {
        document.getElementById('evalName').textContent = this.dataset.name;
        document.getElementById('evalForm').action = `${base}/${this.dataset.id}/evaluer`;
        evalModal.show();
    }));
});
</script>
@endsection
