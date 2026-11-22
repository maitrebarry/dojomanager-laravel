{{-- Onglet Tirage : tirer, vérifier, valider (verrouiller), imprimer. --}}
@php $drawn = $categories->contains(fn ($c) => $c->tirage_at); @endphp
<div class="alert alert-light border small"><i class="fas fa-scale-balanced me-1"></i> {{ __('messages.competitions.draw_rules') }} {{ __($competition->bronze === 'match' ? 'messages.competitions.bronze_note_match' : 'messages.competitions.bronze_note') }}</div>

<div class="d-flex flex-wrap gap-2 mb-3">
    <form method="POST" action="{{ route('admin.competitions.draw.all', $competition) }}" onsubmit="return dojoConfirmAction(this, @js(__('messages.competitions.redraw_confirm')))">
        @csrf
        <button class="btn btn-primary" @disabled($categories->isEmpty())><i class="fas fa-shuffle me-1"></i> {{ __('messages.competitions.draw_all') }}</button>
    </form>
    <button type="button" class="btn btn-dark js-chrono-open"><i class="fas fa-stopwatch me-1"></i> {{ __('messages.competitions.chrono') }}</button>
    @if($drawn)
        <a class="btn btn-outline-dark" target="_blank" href="{{ route('admin.competitions.draw.pdf', $competition) }}"><i class="fas fa-file-pdf me-1"></i> {{ __('messages.competitions.draw_pdf') }}</a>
        <a class="btn btn-outline-dark" target="_blank" href="{{ route('admin.competitions.fights.pdf', $competition) }}"><i class="fas fa-list-ol me-1"></i> {{ __('messages.competitions.fights_pdf') }}</a>
    @endif
    @if($categories->contains(fn ($c) => $c->athletes_count > 0))
        <a class="btn btn-outline-warning text-dark" target="_blank" href="{{ route('admin.competitions.palmares.pdf', $competition) }}" title="{{ __('messages.competitions.palmares_help') }}"><i class="fas fa-trophy me-1"></i> {{ __('messages.competitions.palmares_pdf') }}</a>
    @endif
</div>

<div id="medals">@include('admin.competitions.tabs._medals')</div>

{{-- Filtres : une catégorie, ou les catégories d'un athlète (nom, prénom ou club). Gardés sur l'appareil. --}}
@php $withAthletes = $categories->filter(fn ($c) => $c->athletes_count > 0); @endphp
@if($withAthletes->count() > 1)
    <div class="card border-0 shadow-sm mb-3 position-sticky" style="top: 0; z-index: 5;">
        <div class="card-body py-2 d-flex flex-wrap gap-2 align-items-center">
            <i class="fas fa-filter text-muted"></i>
            <select id="filterCategory" class="form-select form-select-sm w-auto" style="max-width: 320px;">
                <option value="">{{ __('messages.competitions.filter_all_categories') }}</option>
                @foreach($withAthletes as $c)<option value="{{ $c->id }}">{{ $categoryLabels[$c->id] }}</option>@endforeach
            </select>
            <input type="search" id="filterAthlete" class="form-control form-control-sm w-auto" style="min-width: 220px;" placeholder="{{ __('messages.competitions.filter_athlete') }}">
            <button type="button" id="filterClear" class="btn btn-sm btn-light">{{ __('messages.competitions.filter_clear') }}</button>
            <span id="filterCount" class="small text-muted ms-auto"></span>
        </div>
    </div>
@endif
<div id="filterEmpty" class="alert alert-light border text-center d-none">{{ __('messages.competitions.filter_none') }}</div>

@forelse($categories->filter(fn ($c) => $c->athletes_count > 0) as $c)
    @include('admin.competitions.tabs._category_card', ['c' => $c, 'podium' => $podiums[$c->id]])
@empty
    <div class="card border-0 shadow-sm"><div class="card-body text-center text-muted py-5">{{ __('messages.competitions.no_athletes') }}</div></div>
@endforelse

@if($legacyCombats->isNotEmpty())
    <div class="card border-0 shadow-sm mt-4">
        <div class="card-header bg-white fw-semibold">{{ __('messages.competitions.legacy_fights') }}</div>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light"><tr><th>{{ __('messages.competitions.round') }}</th><th>{{ __('messages.competitions.fighter1') }}</th><th>{{ __('messages.competitions.fighter2') }}</th><th>{{ __('messages.competitions.winner') }}</th><th></th></tr></thead>
                <tbody>
                    @foreach($legacyCombats as $combat)
                        <tr>
                            <td>{{ $combat->tour }}</td>
                            <td>{{ $combat->combattant1?->full_name ?? '-' }}</td>
                            <td>{{ $combat->combattant2?->full_name ?? '-' }}</td>
                            <td>{{ $combat->vainqueur?->full_name ?? '-' }}</td>
                            <td class="text-end">
                                <form action="{{ route('admin.competitions.combats.remove', [$competition, $combat]) }}" method="POST" onsubmit="return dojoConfirmDelete(this)">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

<script>
    // Résultats saisis sans recharger la page : la carte de la catégorie et les médailles
    // sont remplacées par la version renvoyée par le serveur, puis le curseur va au
    // combat suivant. Score chiffré + Entrée : le vainqueur est déduit du score.
    (function () {
        var competitionId = @json($competition->id);

        // ---------- Brouillons de score : tapés mais pas encore enregistrés ----------
        // Gardés sur l'appareil par combat, remis à leur place au retour sur la page.
        var drafts = (function () {
            var KEY = 'tirage.drafts.' + competitionId;
            var read = function () { try { return JSON.parse(localStorage.getItem(KEY)) || {}; } catch (e) { return {}; } };
            var write = function (all) { try { localStorage.setItem(KEY, JSON.stringify(all)); } catch (e) {} };
            return {
                all: read,
                set: function (id, value) { var all = read(); if (value) { all[id] = value; } else { delete all[id]; } write(all); },
                remove: function (id) { var all = read(); delete all[id]; write(all); },
                restore: function (root) {
                    var all = read();
                    (root || document).querySelectorAll('.js-score').forEach(function (input) {
                        var value = all[input.dataset.combat];
                        if (value && !input.value) {
                            input.value = value;
                            input.classList.add('border-warning', 'border-2');
                            input.title = @js(__('messages.competitions.draft_restored'));
                        }
                    });
                }
            };
        })();

        document.addEventListener('input', function (event) {
            var input = event.target;
            if (!input.classList || !input.classList.contains('js-score')) { return; }
            drafts.set(input.dataset.combat, input.value.trim());
            rememberCategory(input);
        });

        // Clic sur un lien de l'application avec un score tapé mais pas enregistré : confirmation
        // SweetAlert. (La fenêtre native du navigateur n'est pas utilisée : on ne peut pas la
        // personnaliser, et le brouillon est de toute façon gardé et remis au retour.)
        document.addEventListener('click', function (event) {
            var link = event.target.closest && event.target.closest('a[href]');
            if (!link || link.target === '_blank' || link.getAttribute('href').charAt(0) === '#' || event.ctrlKey || event.metaKey) { return; }
            var pending = Array.prototype.filter.call(document.querySelectorAll('.js-score'), function (i) { return i.value.trim() !== ''; });
            var chronoRunning = window.dojoTirageChrono && window.dojoTirageChrono.isRunning();
            if ((!pending.length && !chronoRunning) || !window.Swal) { return; }
            event.preventDefault();
            Swal.fire({
                title: @js(__('messages.competitions.leave_title')),
                text: chronoRunning ? @js(__('messages.competitions.chrono_leave_text')) : @js(__('messages.competitions.leave_text')),
                icon: 'warning', showCancelButton: true, reverseButtons: true,
                confirmButtonColor: '#6c757d', cancelButtonColor: '#0f5132',
                confirmButtonText: @js(__('messages.competitions.leave_anyway')),
                cancelButtonText: @js(__('messages.competitions.leave_stay'))
            }).then(function (r) {
                if (r.isConfirmed) { window.location.href = link.href; }
                else if (pending.length) { pending[0].scrollIntoView({ block: 'center' }); pending[0].focus(); }
            });
        });

        // ---------- Retour à la catégorie où l'on était ----------
        var LAST = 'tirage.last.' + competitionId;
        function rememberCategory(element) {
            var card = element.closest('.js-category-card');
            if (card) { try { localStorage.setItem(LAST, card.dataset.category); } catch (e) {} }
        }
        document.addEventListener('focusin', function (event) { if (event.target.classList && event.target.classList.contains('js-score')) { rememberCategory(event.target); } });

        // ---------- Filtres par catégorie et par athlète ----------
        var FILTER = 'tirage.filter.' + competitionId;
        var category = document.getElementById('filterCategory');
        var athlete = document.getElementById('filterAthlete');
        var simplify = function (v) { return (v || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim(); };

        function applyFilter() {
            var cat = category ? category.value : '';
            var term = athlete ? simplify(athlete.value) : '';
            var shown = 0;
            document.querySelectorAll('.js-category-card').forEach(function (card) {
                var visible = (!cat || card.dataset.category === cat) && (!term || card.dataset.search.indexOf(term) !== -1);
                card.classList.toggle('d-none', !visible);
                if (visible) { shown++; }
                // L'athlète cherché est surligné dans le tableau et dans la liste des combats.
                card.querySelectorAll('.bracket div, table td').forEach(function (cell) {
                    var hit = term && cell.children.length <= 2 && simplify(cell.textContent).indexOf(term) !== -1;
                    cell.style.outline = hit ? '2px solid #f59e0b' : '';
                    cell.style.backgroundColor = hit && cell.tagName === 'TD' ? '#fef3c7' : '';
                });
            });
            var total = document.querySelectorAll('.js-category-card').length;
            var counter = document.getElementById('filterCount');
            if (counter) { counter.textContent = (cat || term) ? shown + ' / ' + total + ' ' + @js(__('messages.competitions.filter_shown')) : ''; }
            document.getElementById('filterEmpty').classList.toggle('d-none', shown > 0 || (!cat && !term));
            try { localStorage.setItem(FILTER, JSON.stringify({ category: cat, athlete: athlete ? athlete.value : '' })); } catch (e) {}
        }

        if (category) {
            try {
                var saved = JSON.parse(localStorage.getItem(FILTER)) || {};
                if (saved.category && category.querySelector('option[value="' + saved.category + '"]')) { category.value = saved.category; }
                athlete.value = saved.athlete || '';
            } catch (e) {}
            category.addEventListener('change', applyFilter);
            athlete.addEventListener('input', applyFilter);
            document.getElementById('filterClear').addEventListener('click', function () { category.value = ''; athlete.value = ''; applyFilter(); });
        }

        document.addEventListener('tirage:card-updated', function (event) {
            drafts.restore(document.getElementById('categorie-' + event.detail.category));
            applyFilter();
        });

        applyFilter();
        drafts.restore();
        // Retour sur la page : on revient à la catégorie où l'on saisissait (sauf lien direct #categorie-…).
        try {
            var last = localStorage.getItem(LAST);
            var lastCard = last && document.getElementById('categorie-' + last);
            if (!location.hash && lastCard && !lastCard.classList.contains('d-none')) { lastCard.scrollIntoView({ block: 'start' }); }
        } catch (e) {}

        function toast(type, message) {
            if (window.dojoToast) { window.dojoToast(type, message); } else { alert(message); }
        }

        function send(form) {
            var buttons = form.querySelectorAll('button');
            buttons.forEach(function (b) { b.disabled = true; });
            fetch(form.action, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                body: new FormData(form)
            }).then(function (response) {
                return response.json().then(function (data) { return { ok: response.ok, data: data }; });
            }).then(function (result) {
                var data = result.data;
                if (!result.ok) {
                    var errors = data.errors ? [].concat.apply([], Object.values(data.errors)) : [data.message];
                    toast('error', errors.join(' '));
                    buttons.forEach(function (b) { b.disabled = false; });
                    return;
                }
                var holder = document.createElement('div');
                holder.innerHTML = data.card.trim();
                var combat = (form.querySelector('.js-score') || {}).dataset;
                if (combat && combat.combat) { drafts.remove(combat.combat); }
                document.getElementById('categorie-' + data.category).replaceWith(holder.firstElementChild);
                document.getElementById('medals').innerHTML = data.medals;
                document.dispatchEvent(new CustomEvent('tirage:card-updated', { detail: { category: data.category } }));
                toast('success', data.message);
                var next = document.querySelector('#categorie-' + data.category + ' .js-score');
                if (next) { next.focus(); }
            }).catch(function () {
                buttons.forEach(function (b) { b.disabled = false; });
                toast('error', @js(__('messages.competitions.network_error')));
            });
        }

        document.addEventListener('submit', function (event) {
            var form = event.target;
            if (!form.classList || !form.classList.contains('js-result-form')) { return; }
            event.preventDefault();
            if (!form.dataset.confirm) { send(form); return; }
            if (!window.Swal) { if (confirm(form.dataset.confirm)) { send(form); } return; }
            Swal.fire({
                text: form.dataset.confirm, icon: 'question', showCancelButton: true,
                confirmButtonColor: '#0f5132', cancelButtonColor: '#6c757d',
                confirmButtonText: @js(__('messages.confirm')), cancelButtonText: @js(__('messages.cancel'))
            }).then(function (r) { if (r.isConfirmed) { send(form); } });
        });

        document.addEventListener('keydown', function (event) {
            if (event.key !== 'Enter' || !event.target.classList || !event.target.classList.contains('js-score')) { return; }
            event.preventDefault();
            event.target.form.vainqueur.value = '';
            send(event.target.form);
        });
    })();
</script>

@include('admin.competitions.tabs._chrono_panel')
