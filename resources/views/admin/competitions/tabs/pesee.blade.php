{{-- Onglet Pesée : un champ de poids par athlète, enregistré automatiquement (sortie de la case
     ou Entrée) et rangé aussitôt dans sa catégorie. Filtre par groupe ; ajout sur place d'un
     athlète absent de la liste. --}}
@php
    // Groupe de chaque athlète : celui de sa catégorie, sinon celui donné à l'import, sinon
    // déduit du sexe et de l'âge (premier groupe qui l'accepte).
    $groupRanges = $categories->groupBy(fn ($c) => $c->groupKey())->map(fn ($list) => [
        'sexe' => $list->first()->sexe, 'min' => $list->first()->age_min, 'max' => $list->first()->age_max,
    ]);
    $groupOf = function ($a) use ($categories, $groupRanges, $year) {
        if ($a->category_id && ($c = $categories->firstWhere('id', $a->category_id))) {
            return $c->groupKey();
        }
        if ($a->groupe) {
            return $a->groupe;
        }
        $age = $a->sportAge($year);
        foreach ($groupRanges as $key => $g) {
            if (($g['sexe'] === null || $g['sexe'] === $a->sexe) && $age !== null
                && ($g['min'] === null || $age >= $g['min']) && ($g['max'] === null || $age <= $g['max'])) {
                return $key;
            }
        }

        return '';
    };
@endphp
<style>
    .weigh-state { width: 18px; display: inline-block; text-align: center; }
    tr.weigh-saved .weigh-input { border-color: #198754; background: #f0fdf4; }
    tr.weigh-error .weigh-input { border-color: #dc3545; background: #fef2f2; }
</style>
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex flex-wrap gap-2 align-items-center">
        <span class="fw-semibold me-2">{{ __('messages.competitions.weigh_title') }}</span>
        <select class="form-select form-select-sm w-auto" id="weighGroup">
            <option value="">{{ __('messages.competitions.weigh_all_groups') }}</option>
            @foreach($groups as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
            <option value="-">{{ __('messages.competitions.weigh_no_group') }}</option>
        </select>
        <input type="search" class="form-control form-control-sm w-auto" id="weighSearch" placeholder="{{ __('messages.competitions.weigh_search') }}">
        <div class="form-check ms-1">
            <input class="form-check-input" type="checkbox" id="weighMissing" @checked($toWeigh->isNotEmpty())>
            <label class="form-check-label small" for="weighMissing">{{ __('messages.competitions.weigh_only_missing') }} (<span id="weighMissingCount">{{ $toWeigh->count() }}</span>)</label>
        </div>
        <span class="small text-muted" id="weighCount"></span>
        <div class="ms-auto d-flex gap-2">
            <button type="button" class="btn btn-sm btn-success" data-bs-toggle="collapse" data-bs-target="#quickAdd"><i class="fas fa-user-plus me-1"></i> {{ __('messages.competitions.quick_add') }}</button>
            @if($competition->athletes->isNotEmpty())
                <a class="btn btn-sm btn-outline-dark" target="_blank" href="{{ route('admin.competitions.athletes.pdf', $competition) }}"><i class="fas fa-file-pdf me-1"></i> {{ __('messages.competitions.list_pdf') }}</a>
            @endif
        </div>
    </div>

    {{-- Athlète absent de la liste : ajouté sur place, pesé et rangé aussitôt. --}}
    <div class="collapse" id="quickAdd">
        <form class="card-body border-bottom bg-light row g-2 align-items-end" id="quickAddForm" autocomplete="off">
            <div class="col-6 col-md-2"><label class="form-label small mb-1">{{ __('messages.competitions.first_name') }} *</label><input name="prenom" class="form-control form-control-sm" required maxlength="100"></div>
            <div class="col-6 col-md-2"><label class="form-label small mb-1">{{ __('messages.competitions.last_name') }} *</label><input name="nom" class="form-control form-control-sm" required maxlength="100"></div>
            <div class="col-4 col-md-1"><label class="form-label small mb-1">{{ __('messages.competitions.sex') }}</label><select name="sexe" class="form-select form-select-sm"><option value=""></option><option>M</option><option>F</option></select></div>
            <div class="col-8 col-md-2"><label class="form-label small mb-1">{{ __('messages.competitions.quick_birth') }}</label><input name="naissance" class="form-control form-control-sm" inputmode="numeric" placeholder="12/03/2009, 2009 ou 15"></div>
            <div class="col-6 col-md-2"><label class="form-label small mb-1">{{ __('messages.competitions.club') }}</label><input name="club" class="form-control form-control-sm" maxlength="150" list="quickClubs">
                <datalist id="quickClubs">@foreach($competition->athletes->pluck('club')->filter()->unique()->sort() as $club)<option value="{{ $club }}">@endforeach</datalist></div>
            <div class="col-6 col-md-1"><label class="form-label small mb-1">{{ __('messages.competitions.weight') }}</label><input name="poids" class="form-control form-control-sm" inputmode="decimal" placeholder="kg"></div>
            <div class="col-12 col-md-2"><button class="btn btn-sm btn-success w-100"><i class="fas fa-plus me-1"></i> {{ __('messages.competitions.quick_add_submit') }}</button></div>
            <div class="col-12"><small class="text-muted">{{ __('messages.competitions.quick_add_help') }}</small></div>
        </form>
    </div>

    <div class="card-body pb-0"><small class="text-muted">{{ __('messages.competitions.weigh_help_auto') }}</small></div>
    <form method="POST" action="{{ route('admin.competitions.athletes.weigh', $competition) }}" id="weighForm">
        @csrf
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0" id="weighTable">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('messages.competitions.first_name') }}</th>
                        <th>{{ __('messages.competitions.last_name') }}</th>
                        <th class="text-center">{{ __('messages.competitions.age') }}</th>
                        <th class="text-center">{{ __('messages.competitions.sex') }}</th>
                        <th>{{ __('messages.competitions.club') }}</th>
                        <th>{{ __('messages.competitions.current_category') }}</th>
                        <th style="width: 140px;">{{ __('messages.competitions.weight') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($competition->athletes as $a)
                        <tr data-id="{{ $a->id }}" data-missing="{{ $a->poids === null ? 1 : 0 }}" data-group="{{ $groupOf($a) ?: '-' }}" data-search="{{ mb_strtolower($a->nom . ' ' . $a->prenom . ' ' . $a->club) }}">
                            <td>{{ $a->prenom }}</td>
                            <td class="fw-semibold">{{ $a->nom }}</td>
                            @php $naissance = $a->date_naissance?->format('d/m/Y') ?? ($a->age ? (string) $a->age : ''); @endphp
                            <td class="text-center"><input class="form-control form-control-sm js-field text-center" data-field="naissance" style="width: 92px;" value="{{ $naissance }}" data-saved="{{ $naissance }}" placeholder="{{ __('messages.competitions.age') }}" inputmode="numeric" title="{{ __('messages.competitions.quick_birth') }}"></td>
                            <td class="text-center">{{ $a->sexe ?? '-' }}</td>
                            <td><input class="form-control form-control-sm js-field" data-field="club" style="min-width: 130px;" value="{{ $a->club }}" data-saved="{{ $a->club }}" list="quickClubs" placeholder="{{ __('messages.competitions.club') }}"></td>
                            <td class="small js-category">
                                @if($a->category_id)
                                    {{ $categoryLabels[$a->category_id] }}
                                    @if($categories->firstWhere('id', $a->category_id)?->tirage_valide)<i class="fas fa-lock text-success" title="{{ __('messages.competitions.draw_status_valid') }}"></i>@endif
                                @elseif($a->poids === null)
                                    <span class="badge bg-warning text-dark">{{ __('messages.competitions.to_weigh') }}</span>
                                @else
                                    <span class="badge bg-secondary">{{ __('messages.competitions.unclassified') }}</span>
                                @endif
                            </td>
                            <td class="text-nowrap">
                                <input name="poids[{{ $a->id }}]" class="form-control form-control-sm weigh-input d-inline-block" style="width: 100px;" inputmode="decimal" autocomplete="off"
                                    value="{{ $a->poids !== null ? \App\Models\CompetitionCategory::formatKg((float) $a->poids) : '' }}" data-saved="{{ $a->poids !== null ? \App\Models\CompetitionCategory::formatKg((float) $a->poids) : '' }}" placeholder="kg">
                                <span class="weigh-state"></span>
                            </td>
                        </tr>
                    @empty
                        <tr class="js-empty"><td colspan="7" class="text-center text-muted py-5">{{ __('messages.competitions.no_athletes') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($competition->athletes->isNotEmpty())
            <div class="card-footer bg-white position-sticky bottom-0 d-flex align-items-center gap-2">
                <small class="text-muted" id="weighStatus"></small>
                <button class="btn btn-outline-primary btn-sm ms-auto"><i class="fas fa-save me-1"></i> {{ __('messages.competitions.weigh_save_all') }}</button>
            </div>
        @endif
    </form>
</div>

<script>
    (function () {
        var url = @js(route('admin.competitions.athletes.weigh', $competition));
        var quickUrl = @js(route('admin.competitions.athletes.quick', $competition));
        var token = @js(csrf_token());
        var labels = { toWeigh: @js(__('messages.competitions.to_weigh')), unclassified: @js(__('messages.competitions.unclassified')), saved: @js(__('messages.competitions.weigh_saved_one')) };
        var table = document.getElementById('weighTable');
        var search = document.getElementById('weighSearch');
        var missing = document.getElementById('weighMissing');
        var group = document.getElementById('weighGroup');
        var KEY = 'pesee.group.' + @js($competition->id);
        var pending = 0;

        function toast(type, message) { if (window.dojoToast) { window.dojoToast(type, message); } }
        function rows() { return Array.prototype.slice.call(table.querySelectorAll('tbody tr[data-id]')); }

        function filter() {
            var term = search.value.trim().toLowerCase(), g = group.value, shown = 0;
            rows().forEach(function (row) {
                var visible = (!term || row.dataset.search.indexOf(term) !== -1)
                    && (!missing.checked || row.dataset.missing === '1' || row.classList.contains('weigh-keep'))
                    && (!g || row.dataset.group === g);
                row.style.display = visible ? '' : 'none';
                if (visible) { shown++; }
            });
            document.getElementById('weighCount').textContent = shown + ' / ' + rows().length;
            document.getElementById('weighMissingCount').textContent = rows().filter(function (r) { return r.dataset.missing === '1'; }).length;
            try { localStorage.setItem(KEY, g); } catch (e) {}
        }
        try { var saved = localStorage.getItem(KEY); if (saved && group.querySelector('option[value="' + saved + '"]')) { group.value = saved; } } catch (e) {}
        search.addEventListener('input', filter);
        missing.addEventListener('change', filter);
        group.addEventListener('change', filter);
        filter();

        function categoryHtml(r) {
            if (r.category) { return r.category + (r.locked ? ' <i class="fas fa-lock text-success"></i>' : ''); }
            return r.poids ? '<span class="badge bg-secondary">' + labels.unclassified + '</span>' : '<span class="badge bg-warning text-dark">' + labels.toWeigh + '</span>';
        }
        function status(text) { var s = document.getElementById('weighStatus'); if (s) { s.textContent = text; } }

        // Enregistrement automatique d'un poids : à la sortie de la case (ou Entrée).
        function save(input) {
            var row = input.closest('tr'), value = input.value.trim();
            if (value === (input.dataset.saved || '')) { return; }
            if (value === '') { input.value = input.dataset.saved || ''; return; } // pas d'effacement par mégarde
            var body = new FormData();
            body.append('_token', token);
            body.append('poids[' + row.dataset.id + ']', value);
            row.querySelector('.weigh-state').innerHTML = '<span class="spinner-border spinner-border-sm text-secondary"></span>';
            pending++; status('…');
            fetch(url, { method: 'POST', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: body })
                .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, d: d }; }); })
                .then(function (res) {
                    if (!res.ok) { throw new Error(res.d.message || ''); }
                    var r = (res.d.rows || [])[0];
                    row.classList.remove('weigh-saved', 'weigh-error');
                    if (r && (r.status === 'saved' || r.status === 'unchanged')) {
                        input.value = r.poids; input.dataset.saved = r.poids;
                        row.dataset.missing = r.poids ? '0' : '1';
                        row.classList.add('weigh-saved', 'weigh-keep'); // reste visible jusqu'au prochain filtrage
                        row.querySelector('.js-category').innerHTML = categoryHtml(r);
                        row.querySelector('.weigh-state').innerHTML = '<i class="fas fa-check text-success" title="' + labels.saved + '"></i>';
                        if (r.group) { row.dataset.group = r.group; }
                    } else {
                        row.classList.add('weigh-error');
                        row.querySelector('.weigh-state').innerHTML = '<i class="fas fa-triangle-exclamation text-danger"></i>';
                        toast('error', (r && r.message) || '');
                    }
                    if (res.d.redraw) { toast('warning', res.d.redraw); }
                })
                .catch(function (e) {
                    row.classList.add('weigh-error');
                    row.querySelector('.weigh-state').innerHTML = '<i class="fas fa-triangle-exclamation text-danger"></i>';
                    toast('error', e.message || 'Erreur réseau : poids non enregistré.');
                })
                .finally(function () { pending--; status(pending ? '…' : labels.saved); filter(); });
        }
        // Âge (ou naissance) et club corrigés sur place, enregistrés automatiquement.
        var fieldUrl = @js(route('admin.competitions.athletes.quick-update', [$competition, '__ID__']));
        function saveField(input) {
            var row = input.closest('tr'), value = input.value.trim();
            if (value === (input.dataset.saved || '')) { return; }
            var body = new FormData();
            body.append('_token', token);
            body.append(input.dataset.field, value);
            row.querySelector('.weigh-state').innerHTML = '<span class="spinner-border spinner-border-sm text-secondary"></span>';
            pending++; status('…');
            fetch(fieldUrl.replace('__ID__', row.dataset.id), { method: 'POST', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: body })
                .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, d: d }; }); })
                .then(function (res) {
                    if (!res.ok) { throw new Error(res.d.errors ? [].concat.apply([], Object.values(res.d.errors)).join(' ') : (res.d.message || '')); }
                    var r = res.d.row;
                    input.value = r[input.dataset.field] || ''; input.dataset.saved = input.value;
                    row.querySelector('.js-category').innerHTML = categoryHtml(r);
                    if (r.group) { row.dataset.group = r.group; }
                    row.dataset.search = (row.children[1].textContent + ' ' + row.children[0].textContent + ' ' + (r.club || '')).toLowerCase();
                    row.classList.remove('weigh-error'); row.classList.add('weigh-saved', 'weigh-keep');
                    row.querySelector('.weigh-state').innerHTML = '<i class="fas fa-check text-success" title="' + labels.saved + '"></i>';
                    if (res.d.redraw) { toast('warning', res.d.redraw); }
                })
                .catch(function (e) {
                    input.value = input.dataset.saved || '';
                    row.querySelector('.weigh-state').innerHTML = '<i class="fas fa-triangle-exclamation text-danger"></i>';
                    toast('error', e.message || 'Erreur réseau.');
                })
                .finally(function () { pending--; status(pending ? '…' : labels.saved); filter(); });
        }
        table.addEventListener('change', function (e) {
            if (e.target.classList.contains('weigh-input')) { save(e.target); }
            else if (e.target.classList.contains('js-field')) { saveField(e.target); }
        });
        // Entrée dans l'âge ou le club : enregistre (sans envoyer le formulaire).
        table.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && e.target.classList.contains('js-field')) { e.preventDefault(); e.target.blur(); }
        });
        // Entrée : enregistre et passe au poids suivant visible.
        table.addEventListener('keydown', function (event) {
            if (event.key !== 'Enter' || !event.target.classList.contains('weigh-input')) { return; }
            event.preventDefault();
            var inputs = Array.prototype.filter.call(table.querySelectorAll('.weigh-input'), function (i) { return i.offsetParent !== null; });
            var next = inputs[inputs.indexOf(event.target) + 1];
            if (next) { next.focus(); next.select(); } else { event.target.blur(); }
        });
        // Quitter la page pendant un enregistrement : avertir.
        window.addEventListener('beforeunload', function (e) { if (pending) { e.preventDefault(); e.returnValue = ''; } });

        // Ajout sur place d'un athlète absent de la liste.
        var quick = document.getElementById('quickAddForm');
        quick.addEventListener('submit', function (e) {
            e.preventDefault();
            var body = new FormData(quick);
            body.append('_token', token);
            if (group.value && group.value !== '-') { body.append('groupe', group.value); }
            var button = quick.querySelector('button'); button.disabled = true;
            fetch(quickUrl, { method: 'POST', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: body })
                .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, d: d }; }); })
                .then(function (res) {
                    if (!res.ok) { throw new Error(res.d.errors ? [].concat.apply([], Object.values(res.d.errors)).join(' ') : (res.d.message || '')); }
                    var a = res.d.athlete, tbody = table.querySelector('tbody');
                    var empty = tbody.querySelector('.js-empty'); if (empty) { empty.remove(); }
                    var tr = document.createElement('tr');
                    tr.dataset.id = a.id; tr.dataset.missing = a.poids ? '0' : '1'; tr.dataset.group = a.group || '-';
                    tr.dataset.search = (a.nom + ' ' + a.prenom + ' ' + (a.club || '')).toLowerCase();
                    tr.className = 'weigh-saved weigh-keep';
                    tr.innerHTML = '<td></td><td class="fw-semibold"></td><td class="text-center"><input class="form-control form-control-sm js-field text-center" data-field="naissance" style="width: 92px;" inputmode="numeric"></td><td class="text-center"></td>'
                        + '<td><input class="form-control form-control-sm js-field" data-field="club" style="min-width: 130px;" list="quickClubs"></td><td class="small js-category"></td>'
                        + '<td class="text-nowrap"><input class="form-control form-control-sm weigh-input d-inline-block" style="width: 100px;" inputmode="decimal" autocomplete="off" placeholder="kg"> <span class="weigh-state"><i class="fas fa-check text-success"></i></span></td>';
                    var cells = tr.children;
                    cells[0].textContent = a.prenom; cells[1].textContent = a.nom; cells[3].textContent = a.sexe || '-';
                    var n = cells[2].querySelector('input'); n.value = a.naissance || ''; n.dataset.saved = n.value;
                    var c = cells[4].querySelector('input'); c.value = a.club || ''; c.dataset.saved = c.value;
                    cells[5].innerHTML = categoryHtml(a);
                    var input = tr.querySelector('input'); input.name = 'poids[' + a.id + ']'; input.value = a.poids; input.dataset.saved = a.poids;
                    tbody.insertBefore(tr, tbody.firstChild);
                    toast('success', res.d.message);
                    if (res.d.redraw) { toast('warning', res.d.redraw); }
                    quick.reset(); quick.querySelector('[name=prenom]').focus();
                    filter();
                })
                .catch(function (e) { toast('error', e.message || 'Erreur réseau.'); })
                .finally(function () { button.disabled = false; });
        });
    })();
</script>
