{{-- Onglet Pesée : un champ de poids par athlète ; à l'enregistrement, chacun est rangé dans sa catégorie. --}}
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex flex-wrap gap-2 align-items-center">
        <span class="fw-semibold me-2">{{ __('messages.competitions.weigh_title') }}</span>
        <input type="search" class="form-control form-control-sm w-auto" id="weighSearch" placeholder="{{ __('messages.competitions.weigh_search') }}">
        <div class="form-check ms-1">
            <input class="form-check-input" type="checkbox" id="weighMissing" @checked($toWeigh->isNotEmpty())>
            <label class="form-check-label small" for="weighMissing">{{ __('messages.competitions.weigh_only_missing') }} ({{ $toWeigh->count() }})</label>
        </div>
        @if($competition->athletes->isNotEmpty())
            <a class="btn btn-sm btn-outline-dark ms-auto" target="_blank" href="{{ route('admin.competitions.athletes.pdf', $competition) }}"><i class="fas fa-file-pdf me-1"></i> {{ __('messages.competitions.list_pdf') }}</a>
        @endif
    </div>
    <div class="card-body pb-0"><small class="text-muted">{{ __('messages.competitions.weigh_help') }}</small></div>
    <form method="POST" action="{{ route('admin.competitions.athletes.weigh', $competition) }}">
        @csrf
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0" id="weighTable">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('messages.competitions.last_name') }}</th>
                        <th>{{ __('messages.competitions.first_name') }}</th>
                        <th class="text-center">{{ __('messages.competitions.age') }}</th>
                        <th class="text-center">{{ __('messages.competitions.sex') }}</th>
                        <th>{{ __('messages.competitions.club') }}</th>
                        <th>{{ __('messages.competitions.current_category') }}</th>
                        <th style="width: 120px;">{{ __('messages.competitions.weight') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($competition->athletes as $a)
                        <tr data-missing="{{ $a->poids === null ? 1 : 0 }}" data-search="{{ mb_strtolower($a->nom . ' ' . $a->prenom . ' ' . $a->club) }}">
                            <td class="fw-semibold">{{ $a->nom }}</td>
                            <td>{{ $a->prenom }}</td>
                            <td class="text-center">{{ $a->sportAge($year) ?? '-' }}</td>
                            <td class="text-center">{{ $a->sexe ?? '-' }}</td>
                            <td>{{ $a->club ?? '-' }}</td>
                            <td class="small">
                                @if($a->category_id)
                                    {{ $categoryLabels[$a->category_id] }}
                                    @if($categories->firstWhere('id', $a->category_id)?->tirage_valide)<i class="fas fa-lock text-success" title="{{ __('messages.competitions.draw_status_valid') }}"></i>@endif
                                @elseif($a->poids === null)
                                    <span class="badge bg-warning text-dark">{{ __('messages.competitions.to_weigh') }}</span>
                                @else
                                    <span class="badge bg-secondary">{{ __('messages.competitions.unclassified') }}</span>
                                @endif
                            </td>
                            <td>
                                <input name="poids[{{ $a->id }}]" class="form-control form-control-sm weigh-input" inputmode="decimal" autocomplete="off"
                                    value="{{ $a->poids !== null ? \App\Models\CompetitionCategory::formatKg((float) $a->poids) : '' }}" placeholder="kg">
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-5">{{ __('messages.competitions.no_athletes') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($competition->athletes->isNotEmpty())
            <div class="card-footer bg-white position-sticky bottom-0 d-flex">
                <button class="btn btn-primary ms-auto"><i class="fas fa-save me-1"></i> {{ __('messages.competitions.weigh_save') }}</button>
            </div>
        @endif
    </form>
</div>

<script>
    (function () {
        var search = document.getElementById('weighSearch');
        var missing = document.getElementById('weighMissing');
        var rows = document.querySelectorAll('#weighTable tbody tr[data-search]');

        function filter() {
            var term = search.value.trim().toLowerCase();
            rows.forEach(function (row) {
                var visible = (!term || row.dataset.search.indexOf(term) !== -1) && (!missing.checked || row.dataset.missing === '1');
                row.style.display = visible ? '' : 'none';
            });
        }

        search.addEventListener('input', filter);
        missing.addEventListener('change', filter);
        filter();

        // Entrée passe au poids suivant visible au lieu d'envoyer le formulaire.
        document.getElementById('weighTable').addEventListener('keydown', function (event) {
            if (event.key !== 'Enter' || !event.target.classList.contains('weigh-input')) { return; }
            event.preventDefault();
            var inputs = Array.prototype.filter.call(document.querySelectorAll('.weigh-input'), function (i) { return i.offsetParent !== null; });
            var next = inputs[inputs.indexOf(event.target) + 1];
            if (next) { next.focus(); next.select(); }
        });
    })();
</script>
