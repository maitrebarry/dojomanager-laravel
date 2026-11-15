{{-- Onglet Tirage : tirer, vérifier, valider (verrouiller), imprimer. --}}
@php $drawn = $categories->contains(fn ($c) => $c->tirage_at); @endphp
<div class="alert alert-light border small"><i class="fas fa-scale-balanced me-1"></i> {{ __('messages.competitions.draw_rules') }} {{ __($competition->bronze === 'match' ? 'messages.competitions.bronze_note_match' : 'messages.competitions.bronze_note') }}</div>

<div class="d-flex flex-wrap gap-2 mb-3">
    <form method="POST" action="{{ route('admin.competitions.draw.all', $competition) }}" onsubmit="return dojoConfirmAction(this, @js(__('messages.competitions.redraw_confirm')))">
        @csrf
        <button class="btn btn-primary" @disabled($categories->isEmpty())><i class="fas fa-shuffle me-1"></i> {{ __('messages.competitions.draw_all') }}</button>
    </form>
    <a class="btn btn-dark" target="_blank" href="{{ route('admin.competitions.chrono', $competition) }}"><i class="fas fa-stopwatch me-1"></i> {{ __('messages.competitions.chrono') }}</a>
    @if($drawn)
        <a class="btn btn-outline-dark" target="_blank" href="{{ route('admin.competitions.draw.pdf', $competition) }}"><i class="fas fa-file-pdf me-1"></i> {{ __('messages.competitions.draw_pdf') }}</a>
        <a class="btn btn-outline-dark" target="_blank" href="{{ route('admin.competitions.fights.pdf', $competition) }}"><i class="fas fa-list-ol me-1"></i> {{ __('messages.competitions.fights_pdf') }}</a>
    @endif
</div>

<div id="medals">@include('admin.competitions.tabs._medals')</div>

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
                document.getElementById('categorie-' + data.category).replaceWith(holder.firstElementChild);
                document.getElementById('medals').innerHTML = data.medals;
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
