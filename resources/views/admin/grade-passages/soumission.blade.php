@extends('layouts.admin')

@section('title', __('messages.grade_passages.submission_title'))

@php
    $u = Auth::user();
    $roleVal = $u->role->value ?? $u->role;
    $canManage = $u->isSuperAdmin() || $u->hasPermission('PASSAGEGRADES_MANAGE');
    $scopeLabel = fn ($s) => $s ? ($s->type_grade === 'KEUP' ? ($s->ligue?->nom ?? $s->federation?->nom ?? '-') : ($s->federation?->nom ?? '-')) : '-';
@endphp

@section('content')
{{-- En-tête : badges profil / fonction (fidèle à PassageGrades.jsx) --}}
<div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-3">
    <div></div>
    <div class="d-flex gap-2 flex-wrap">
        <span class="badge bg-primary-subtle text-primary border border-primary">{{ __('messages.grade_passages.profile') }}: {{ $roleVal }}</span>
        <span class="badge bg-light text-dark border">{{ __('messages.parametres.function') }}: {{ $u->fonction ?: __('messages.parametres.function_none') }}</span>
    </div>
</div>

@include('admin.grade-passages._tabs', ['active' => 'soumission'])

{{-- Sélecteur de session --}}
<div class="card card-navbar shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.grade-passages.soumission') }}" class="row g-3 align-items-end">
            <div class="col-12 col-lg-8">
                <label class="form-label">{{ __('messages.grade_passages.select_session') }} ({{ $sessions->count() }} {{ __('messages.grade_passages.sessions_visible') }})</label>
                <select name="session" class="form-select" onchange="this.form.submit()" {{ $sessions->isEmpty() ? 'disabled' : '' }}>
                    @forelse($sessions as $s)
                        <option value="{{ $s->id }}" {{ $session && $session->id === $s->id ? 'selected' : '' }}>
                            {{ $s->type_grade }} • {{ $s->date_session?->format('d/m/Y') }} • {{ $s->lieu }} • {{ $scopeLabel($s) }} • {{ $s->finalisee ? __('messages.grade_passages.finalized_badge') : __('messages.grade_passages.open') }}
                        </option>
                    @empty
                        <option value="">{{ __('messages.grade_passages.empty') }}</option>
                    @endforelse
                </select>
            </div>
            <div class="col-12 col-lg-4 text-lg-end text-muted small">
                {{ $sessions->count() }} {{ __('messages.grade_passages.sessions_visible') }}
            </div>
        </form>
    </div>
</div>

@if(!$session)
    <div class="card border-0 shadow-sm"><div class="card-body text-muted">{{ __('messages.grade_passages.no_session') }}</div></div>
@else
{{-- Carte d'info session + stats --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header card-header-navbar d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
        <div>
            <h5 class="mb-1">{{ __('messages.grade_passages.submission_title') }}</h5>
            <div class="small opacity-75">{{ $session->type_grade }} • {{ $session->date_session?->format('d/m/Y') }} • {{ $session->lieu }}</div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <span class="badge bg-light text-dark border">{{ __('messages.grade_passages.stat_candidatures') }}: {{ $stats['candidatures'] }}</span>
            <span class="badge bg-light text-dark border">{{ __('messages.grade_passages.stat_validees') }}: {{ $stats['validees'] }}</span>
            <span class="badge bg-light text-dark border">{{ __('messages.grade_passages.stat_evaluees') }}: {{ $stats['evaluees'] }}</span>
            <span class="badge bg-light text-dark border">{{ __('messages.grade_passages.stat_admis') }}: {{ $stats['admis'] }}</span>
            <span class="badge bg-warning text-dark">{{ __('messages.grade_passages.expected') }}: {{ number_format($stats['attendus'], 0, ',', ' ') }}</span>
            <span class="badge bg-success">{{ __('messages.grade_passages.collected') }}: {{ number_format($stats['paye'], 0, ',', ' ') }}</span>
            <span class="badge bg-danger">{{ __('messages.grade_passages.remaining') }}: {{ number_format($stats['reste'], 0, ',', ' ') }}</span>
        </div>
    </div>
    <div class="card-body">
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="border rounded-3 p-3 h-100 bg-light">
                    <div class="text-muted small">{{ __('messages.grade_passages.scope') }}</div>
                    <div class="fw-semibold">{{ $scopeLabel($session) }}</div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="border rounded-3 p-3 h-100 bg-light">
                    <div class="text-muted small">{{ __('messages.grade_passages.fee') }}</div>
                    <div class="fw-semibold">{{ number_format($session->frais_participation, 0, ',', ' ') }} FCFA</div>
                </div>
            </div>
        </div>

        @if($canManage && !$session->finalisee)
        {{-- Panneau : soumettre des candidats (panier) --}}
        <div class="border rounded-3 p-3">
            <div class="fw-semibold mb-2">{{ __('messages.grade_passages.submit_candidates') }}</div>
            <div class="row g-3 align-items-end">
                <div class="col-12 col-lg-5">
                    <label class="form-label">{{ __('messages.grade_passages.eligible_candidate') }}</label>
                    <select id="eligibleSelect" class="form-select">
                        <option value="">{{ __('messages.grade_passages.choose_candidate') }}</option>
                        @foreach($eligibles as $d)
                            <option value="{{ $d->id }}" data-name="{{ $d->prenom }} {{ $d->nom }}" data-current="{{ $d->grade?->nom_grade ?? '-' }}" data-salle="{{ $d->salle?->nom ?? '-' }}">
                                {{ $d->prenom }} {{ $d->nom }} • {{ $d->grade?->nom_grade ?? '-' }} @if($d->salle)• {{ $d->salle->nom }}@endif
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-lg-4">
                    <label class="form-label">{{ __('messages.grade_passages.proposed_grade') }}</label>
                    <select id="proposedSelect" class="form-select">
                        <option value="">-</option>
                        @foreach($grades as $g)
                            <option value="{{ $g->id }}" data-name="{{ $g->nom_grade }}">{{ $g->nom_grade }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-4 col-lg-2">
                    <label class="form-label">{{ __('messages.grade_passages.fee') }}</label>
                    <input type="number" id="feeInput" class="form-control" min="0" step="0.01" value="{{ (float) $session->frais_participation }}">
                </div>
                <div class="col-2 col-lg-1">
                    <button type="button" id="addToCart" class="btn text-white w-100" style="background-color: var(--navbar-bg);" title="{{ __('messages.grade_passages.add_to_cart') }}"><i class="fas fa-plus"></i></button>
                </div>
            </div>

            {{-- Panier --}}
            <form method="POST" action="{{ route('admin.grade-passages.candidats.batch', $session) }}" id="cartForm" class="mt-3">
                @csrf
                <div class="border rounded-3 p-3 bg-light">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="fw-semibold">{{ __('messages.grade_passages.cart') }}</div>
                        <span class="badge bg-secondary" id="cartCount">0</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0" id="cartTable">
                            <thead><tr>
                                <th>{{ __('messages.grade_passages.candidate') }}</th>
                                <th>{{ __('messages.grade_passages.grade_change') }}</th>
                                <th>{{ __('messages.grade_passages.fee') }}</th>
                                <th class="text-end">{{ __('messages.actions') }}</th>
                            </tr></thead>
                            <tbody id="cartBody">
                                <tr id="cartEmpty"><td colspan="4" class="text-muted small">{{ __('messages.grade_passages.no_candidate_selected') }}</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        <button type="submit" id="submitCart" class="btn btn-primary" disabled><i class="fas fa-check me-1"></i> {{ __('messages.grade_passages.add_candidatures') }}</button>
                    </div>
                </div>
            </form>
        </div>
        @endif
    </div>
</div>

{{-- Liste des candidatures --}}
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Candidat</th>
                    <th>{{ __('messages.grade_passages.scope') }}</th>
                    <th>{{ __('messages.grades.type') }}</th>
                    <th>{{ __('messages.grade_passages.fee') }}</th>
                    <th>{{ __('messages.grade_passages.validation') }}</th>
                    <th style="width: 160px;">{{ __('messages.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($candidates as $c)
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $c->prenom }} {{ $c->nom }}</div>
                            <div class="small text-muted">{{ $c->candidate_type }} @if($c->sexe)• {{ $c->sexe }}@endif</div>
                        </td>
                        <td><div class="small text-muted">{{ $c->salle_nom ?: '-' }}</div></td>
                        <td>
                            <div>{{ $c->current_grade_nom ?? '-' }}</div>
                            <div class="small text-muted"><i class="fas fa-arrow-right me-1"></i>{{ $c->proposed_grade_nom ?? '-' }}</div>
                        </td>
                        <td>
                            <div class="fw-semibold">{{ number_format($c->frais_participation, 0, ',', ' ') }}</div>
                            <span class="badge bg-{{ $c->paiementColor() }}">{{ $c->statut_paiement }}</span>
                            <div class="small text-muted">{{ __('messages.cotisations.paid_amount') }}: {{ number_format($c->montant_paye, 0, ',', ' ') }}</div>
                            <div class="small text-muted">{{ __('messages.grade_passages.remaining') }}: {{ number_format($c->resteAPayer(), 0, ',', ' ') }}</div>
                        </td>
                        <td>
                            <span class="badge bg-{{ $c->statutColor() }}">{{ $c->statut }}</span>
                            @if($c->note_globale !== null)<div class="small text-muted">{{ $c->note_globale }}/100</div>@endif
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-1 flex-wrap">
                                @if($canManage && $c->resteAPayer() > 0 && !$session->finalisee)
                                    <button type="button" class="btn btn-sm btn-outline-success btn-pay" data-id="{{ $c->id }}" data-name="{{ $c->prenom }} {{ $c->nom }}" data-reste="{{ $c->resteAPayer() }}"><i class="fas fa-hand-holding-dollar me-1"></i>{{ __('messages.cotisations.pay') }}</button>
                                @endif
                                @if($canManage && !$session->finalisee)
                                    <form action="{{ route('admin.grade-passages.candidats.remove', [$session, $c]) }}" method="POST" class="d-inline m-0" onsubmit="return confirm('{{ __('messages.grade_passages.remove_confirm') }}');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="{{ __('messages.grade_passages.remove') }}"><i class="fas fa-trash"></i></button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">{{ __('messages.grade_passages.no_candidatures') }}</td></tr>
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
                <p><strong id="payName"></strong> — {{ __('messages.grade_passages.remaining') }}: <span id="payReste" class="text-danger fw-bold"></span></p>
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
@endif
@endsection

@section('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    @if($session)
    // ---- Panier de candidatures (client-side) ----
    var cart = [];
    var body = document.getElementById('cartBody');
    var empty = document.getElementById('cartEmpty');
    var countEl = document.getElementById('cartCount');
    var submitBtn = document.getElementById('submitCart');
    var form = document.getElementById('cartForm');
    var fmt = new Intl.NumberFormat('fr-FR');

    // Frais applicable par grade proposé (issu de la grille tarifaire — Configuration)
    var feeByGrade = @json($feeByGradeId ?? []);
    var sessionFee = {{ (float) $session->frais_participation }};
    var proposedSel = document.getElementById('proposedSelect');
    var feeInputEl = document.getElementById('feeInput');
    if (proposedSel && feeInputEl) {
        proposedSel.addEventListener('change', function () {
            var v = feeByGrade[this.value];
            feeInputEl.value = (v !== undefined && v !== null) ? v : sessionFee;
        });
    }

    function refresh() {
        if (countEl) countEl.textContent = cart.length;
        if (submitBtn) submitBtn.disabled = cart.length === 0;
        if (empty) empty.style.display = cart.length ? 'none' : '';
        // (re)génère les inputs cachés
        form.querySelectorAll('.cart-hidden').forEach(function (n) { n.remove(); });
        cart.forEach(function (c, i) {
            ['disciple_id', 'proposed_grade_id', 'frais_participation'].forEach(function (k) {
                var input = document.createElement('input');
                input.type = 'hidden'; input.className = 'cart-hidden';
                input.name = 'candidates[' + i + '][' + k + ']';
                input.value = c[k];
                form.appendChild(input);
            });
        });
    }

    var addBtn = document.getElementById('addToCart');
    if (addBtn) addBtn.addEventListener('click', function () {
        var elig = document.getElementById('eligibleSelect');
        var prop = document.getElementById('proposedSelect');
        var fee = document.getElementById('feeInput');
        var did = elig.value, pid = prop.value;
        if (!did) { if (window.dojoToast) dojoToast('warning', @json(__('messages.grade_passages.choose_candidate'))); return; }
        if (!pid) { if (window.dojoToast) dojoToast('warning', @json(__('messages.grade_passages.proposed_grade'))); return; }
        if (cart.some(function (c) { return c.disciple_id === did; })) return;
        var eo = elig.options[elig.selectedIndex], po = prop.options[prop.selectedIndex];
        cart.push({ disciple_id: did, proposed_grade_id: pid, frais_participation: fee.value || 0,
                    name: eo.dataset.name, current: eo.dataset.current, proposed: po.dataset.name });
        var tr = document.createElement('tr');
        tr.innerHTML = '<td>' + eo.dataset.name + '</td><td>' + (eo.dataset.current || '-') + ' → ' + po.dataset.name +
            '</td><td>' + fmt.format(fee.value || 0) + '</td><td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger">✕</button></td>';
        tr.querySelector('button').addEventListener('click', function () {
            var idx = cart.findIndex(function (c) { return c.disciple_id === did; });
            if (idx > -1) cart.splice(idx, 1);
            tr.remove(); refresh();
        });
        body.appendChild(tr);
        elig.value = ''; refresh();
    });
    refresh();

    // ---- Paiement ----
    var base = "{{ url('admin/grade-passages/' . ($session->id ?? 0) . '/candidats') }}";
    var payModalEl = document.getElementById('payModal');
    if (payModalEl) {
        var payModal = new bootstrap.Modal(payModalEl);
        document.querySelectorAll('.btn-pay').forEach(function (btn) {
            btn.addEventListener('click', function () {
                document.getElementById('payName').textContent = this.dataset.name;
                document.getElementById('payReste').textContent = fmt.format(this.dataset.reste) + ' FCFA';
                document.getElementById('payMontant').value = this.dataset.reste;
                document.getElementById('payForm').action = base + '/' + this.dataset.id + '/payer';
                payModal.show();
            });
        });
    }
    @endif
});
</script>
@endsection
