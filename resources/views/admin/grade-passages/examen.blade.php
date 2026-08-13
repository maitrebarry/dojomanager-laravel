@extends('layouts.admin')

@section('title', __('messages.grade_passages.tab_exam'))

@php
    $u = Auth::user();
    $roleVal = $u->role->value ?? $u->role;
    $canManage = $u->isSuperAdmin() || $u->hasPermission('PASSAGEGRADES_MANAGE');
    $bareme = $session ? (int) ($session->bareme ?: 20) : 20;
    $scopeLabel = fn ($s) => $s ? ($s->type_grade === 'KEUP' ? ($s->ligue?->nom ?? $s->federation?->nom ?? '-') : ($s->federation?->nom ?? '-')) : '-';
@endphp

@section('content')
{{-- Badges profil / fonction --}}
<div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-3">
    <div></div>
    <div class="d-flex gap-2 flex-wrap">
        <span class="badge bg-primary-subtle text-primary border border-primary">{{ __('messages.grade_passages.profile') }}: {{ $roleVal }}</span>
        <span class="badge bg-light text-dark border">{{ __('messages.grade_passages.bareme') }}: {{ $bareme }} {{ __('messages.grade_passages.per_criterion') }}</span>
    </div>
</div>

@include('admin.grade-passages._tabs', ['active' => 'examen'])

{{-- Sélecteur de session --}}
<div class="card card-navbar shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.grade-passages.examen') }}" class="row g-3 align-items-end">
            <div class="col-12 col-lg-8">
                <label class="form-label">{{ __('messages.grade_passages.select_session') }}</label>
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
        </form>
    </div>
</div>

@if(!$session)
    <div class="card border-0 shadow-sm"><div class="card-body text-muted">{{ __('messages.grade_passages.no_session') }}</div></div>
@else
<div class="card border-0 shadow-sm">
    <div class="card-header card-header-navbar d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-2">
        <div>
            <h5 class="mb-1">{{ __('messages.grade_passages.exam_grid') }}</h5>
            <div class="small opacity-75">{{ __('messages.grade_passages.exam_grid_hint') }}</div>
        </div>
        <div class="d-flex gap-2 align-items-center flex-wrap">
            <span class="badge bg-light text-dark">{{ __('messages.grade_passages.exam_progress', ['done' => $completion['evaluated'], 'total' => $completion['total']]) }}</span>
            @if($canManage && !$session->finalisee && $completion['complete'])
                <span class="badge bg-success">{{ __('messages.grade_passages.exam_all_done') }}</span>
                <form action="{{ route('admin.grade-passages.finalize', $session) }}" method="POST" class="d-inline m-0" onsubmit="return confirm('{{ __('messages.grade_passages.finalize_confirm') }}');">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-success"><i class="fas fa-lock me-1"></i> {{ __('messages.grade_passages.finalize') }}</button>
                </form>
            @endif
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-bordered align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>{{ __('messages.grade_passages.candidate') }}</th>
                    <th class="text-center">{{ __('messages.grade_passages.exam_note_forme') }}</th>
                    <th class="text-center">{{ __('messages.grade_passages.exam_note_mvt') }}</th>
                    <th class="text-center">{{ __('messages.grade_passages.exam_note_poomsae') }}</th>
                    <th class="text-center">{{ __('messages.grade_passages.exam_note_attaque') }}</th>
                    <th class="text-center">{{ __('messages.grade_passages.exam_note_combat') }}</th>
                    <th class="text-center">{{ __('messages.grade_passages.average') }}</th>
                    <th class="text-center">{{ __('messages.grade_passages.result') }}</th>
                    <th class="text-center" style="width: 96px;">{{ __('messages.grade_passages.autosave') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($candidates as $c)
                    @php $disabled = $session->finalisee || !$canManage; @endphp
                    <tr class="js-note-row" data-url="{{ route('admin.grade-passages.candidats.notes', [$session, $c]) }}">
                        <td>
                            <div class="fw-semibold">{{ $c->prenom }} {{ $c->nom }}</div>
                            <div class="small text-muted">{{ $c->salle_nom ?: '-' }} • {{ $c->current_grade_nom ?? '-' }} <i class="fas fa-arrow-right mx-1"></i> {{ $c->proposed_grade_nom ?? '-' }}</div>
                        </td>
                        @foreach(['note_forme','note_mouvement_base','note_poomsea','note_attaque_defense','note_combat'] as $field)
                            <td style="width: 84px;">
                                <input type="number" name="{{ $field }}" class="form-control form-control-sm text-center js-note" min="0" max="{{ $bareme }}" step="0.01"
                                       value="{{ $c->$field !== null ? rtrim(rtrim(number_format($c->$field, 2, '.', ''), '0'), '.') : '' }}"
                                       {{ $disabled ? 'disabled' : '' }}>
                            </td>
                        @endforeach
                        <td class="text-center fw-bold js-average">{{ $c->moyenne_generale !== null ? rtrim(rtrim(number_format($c->moyenne_generale, 2, '.', ''), '0'), '.') : '-' }}</td>
                        <td class="text-center js-result">
                            @if($c->resultat)
                                <span class="badge bg-{{ $c->resultat === 'ADMIS' ? 'success' : 'danger' }}">{{ $c->resultat }}</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td class="text-center"><span class="js-note-status small text-muted">—</span></td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center text-muted py-4">{{ __('messages.grade_passages.no_exam_candidate') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endif
@endsection

@section('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var token = "{{ csrf_token() }}";
    var bareme = {{ $bareme ?? 20 }};
    var L = {
        pending: @json(__('messages.grade_passages.autosave_pending')),
        saving: @json(__('messages.grade_passages.autosave_saving')),
        saved: @json(__('messages.grade_passages.autosave_saved')),
        error: @json(__('messages.grade_passages.autosave_error'))
    };

    function setStatus(el, state) {
        if (!el) return;
        var map = {
            pending: ['text-muted', '… ' + L.pending],
            saving:  ['text-primary', '⏳ ' + L.saving],
            saved:   ['text-success', '✓ ' + L.saved],
            error:   ['text-danger', '⚠ ' + L.error]
        };
        var cfg = map[state] || ['text-muted', '—'];
        el.className = 'js-note-status small ' + cfg[0];
        el.textContent = cfg[1];
    }

    // Enregistrement automatique des notes (debounce 500ms) — fidèle à handleExamInputChange (React)
    document.querySelectorAll('.js-note-row').forEach(function (row) {
        var url = row.dataset.url;
        var avgCell = row.querySelector('.js-average');
        var resultCell = row.querySelector('.js-result');
        var statusEl = row.querySelector('.js-note-status');
        var inputs = Array.prototype.slice.call(row.querySelectorAll('.js-note'));
        if (!inputs.length || !url) return;

        var timer = null;

        function recompute() {
            var vals = inputs.map(function (i) { return parseFloat(i.value) || 0; });
            var moy = vals.reduce(function (a, b) { return a + b; }, 0) / inputs.length;
            if (avgCell) avgCell.textContent = moy.toFixed(2).replace(/\.?0+$/, '');
        }

        function persist() {
            setStatus(statusEl, 'saving');
            var payload = {};
            inputs.forEach(function (i) { payload[i.name] = i.value === '' ? 0 : i.value; });
            fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                body: JSON.stringify(payload)
            })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (!d || !d.ok) { setStatus(statusEl, 'error'); return; }
                if (avgCell && d.moyenne !== undefined) avgCell.textContent = String(d.moyenne).replace(/\.?0+$/, '');
                if (resultCell && d.resultat) {
                    resultCell.innerHTML = '<span class="badge bg-' + (d.resultat === 'ADMIS' ? 'success' : 'danger') + '">' + d.resultat + '</span>';
                }
                setStatus(statusEl, 'saved');
            })
            .catch(function () { setStatus(statusEl, 'error'); });
        }

        inputs.forEach(function (i) {
            i.addEventListener('input', function () {
                recompute();
                setStatus(statusEl, 'pending');
                if (timer) clearTimeout(timer);
                timer = setTimeout(persist, 500);
            });
        });
    });
});
</script>
@endsection
