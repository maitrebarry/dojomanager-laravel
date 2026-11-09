{{-- Résultats d'une catégorie : un vainqueur par combat, qui passe au combat suivant ; podium à la fin. --}}
@php
    $profile = $competition->profile();
    $fights = $category->combats->where('exempt', false)->sortBy('numero');
    $thirdFight = $category->combats->first(fn ($c) => $c->troisieme_place);
    $nextOf = fn ($f) => $f->troisieme_place ? null : $category->combats->first(fn ($c) => !$c->troisieme_place && $c->round === $f->round + 1 && $c->position === intdiv($f->position, 2));
    // Combat d'où vient un combattant encore inconnu ; pour la 3e place, le perdant d'une demi-finale.
    $feeder = fn ($f, $side) => $category->combats->first(fn ($c) => !$c->troisieme_place && $c->round === $f->round - 1 && $c->position === ($f->troisieme_place ? $side : $f->position * 2 + $side));
    // Un résultat s'annule tant que les combats où il envoie ses athlètes ne sont pas joués.
    $canClear = fn ($f) => !$nextOf($f)?->vainqueur_athlete_id && !($thirdFight && !$f->troisieme_place && $thirdFight->round === $f->round + 1 && $thirdFight->vainqueur_athlete_id);
@endphp

@if($podium['complete'])
    <div class="d-flex flex-wrap gap-2 mt-3">
        <span class="badge text-bg-warning fs-6">🥇 {{ $podium['gold']?->fullName() }}</span>
        @if($podium['silver'])<span class="badge text-bg-secondary fs-6">🥈 {{ $podium['silver']->fullName() }}</span>@endif
        @foreach($podium['bronze'] as $b)<span class="badge fs-6" style="background: #b4774a;">🥉 {{ $b->fullName() }}</span>@endforeach
    </div>
@endif

@if(!$category->tirage_valide)
    <div class="small text-muted mt-3"><i class="fas fa-lock me-1"></i> {{ __('messages.competitions.results_locked_hint') }}</div>
@else
    <h6 class="mt-4 mb-1 fw-semibold">{{ __('messages.competitions.results') }}</h6>
    <div class="small text-muted mb-2">{{ __('messages.competitions.results_help', ['order' => $profile['corners'][1]['short'] . '-' . $profile['corners'][2]['short'], 'example' => $profile['score_example']]) }}</div>
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 50px;">{{ __('messages.competitions.fight_no') }}</th>
                    <th style="width: 130px;">{{ __('messages.competitions.round') }}</th>
                    <th style="color: {{ $profile['corners'][1]['color'] }};">{{ $profile['corners'][1]['name'] }}</th>
                    <th style="color: {{ $profile['corners'][2]['color'] }};">{{ $profile['corners'][2]['name'] }}</th>
                    <th style="width: 360px;">{{ __('messages.competitions.winner_to_fill') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($fights as $f)
                    @php
                        $won = fn ($id) => $f->vainqueur_athlete_id && $f->vainqueur_athlete_id === $id;
                        $lost = fn ($id) => $f->vainqueur_athlete_id && $id && $f->vainqueur_athlete_id !== $id;
                    @endphp
                    <tr class="{{ $f->isReady() ? 'table-warning' : '' }}">
                        <td class="fw-bold">{{ $f->numero }}</td>
                        <td class="small">{{ $f->tour }}</td>
                        @foreach([[$f->athlete1, 0], [$f->athlete2, 1]] as [$athlete, $side])
                            <td class="{{ $athlete && $won($athlete->id) ? 'fw-bold text-success' : '' }} {{ $athlete && $lost($athlete->id) ? 'text-muted text-decoration-line-through' : '' }}">
                                @if($athlete)
                                    {{ $athlete->fullName() }} @if($athlete->club)<small class="text-muted">· {{ $athlete->club }}</small>@endif
                                @else
                                    <span class="text-muted fst-italic small">
                                        @if($f->troisieme_place){{ __('messages.competitions.loser_of', ['numero' => $feeder($f, $side)?->numero]) }}@else{{ __('messages.competitions.waiting') }} (n° {{ $feeder($f, $side)?->numero }})@endif
                                    </span>
                                @endif
                            </td>
                        @endforeach
                        <td>
                            @if($f->vainqueur_athlete_id)
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge" style="background: {{ $profile['corners'][$f->vainqueur_athlete_id === $f->athlete1_id ? 1 : 2]['color'] }};"><i class="fas fa-trophy me-1"></i>{{ $f->vainqueurAthlete?->fullName() }}</span>
                                    @if($f->score)<span class="small">{{ $f->score }}</span>@endif
                                    @if($canClear($f))
                                        <form method="POST" action="{{ route('admin.competitions.fights.clear', [$competition, $f]) }}" class="ms-auto" onsubmit="return dojoConfirmAction(this, @js(__('messages.competitions.cancel_result_confirm')))">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-link text-muted p-0" title="{{ __('messages.competitions.cancel_result') }}"><i class="fas fa-rotate-left"></i></button>
                                        </form>
                                    @endif
                                </div>
                            @elseif($f->isReady())
                                <form method="POST" action="{{ route('admin.competitions.fights.result', [$competition, $f]) }}" class="d-flex gap-1">
                                    @csrf
                                    {{-- Champ caché : le bouton cliqué est désactivé pendant l'envoi (indicateur de chargement), sa valeur ne partirait pas. --}}
                                    <input type="hidden" name="vainqueur">
                                    <input name="score" class="form-control form-control-sm" style="max-width: 90px;" placeholder="{{ $profile['corners'][1]['short'] }}-{{ $profile['corners'][2]['short'] }}" maxlength="40">
                                    @foreach([1, 2] as $corner)
                                        <button class="btn btn-sm text-white text-nowrap" style="background: {{ $profile['corners'][$corner]['color'] }};" onclick="this.form.vainqueur.value = '{{ $corner }}'">{{ __('messages.competitions.corner_wins', ['corner' => $profile['corners'][$corner]['short']]) }}</button>
                                    @endforeach
                                </form>
                            @else
                                <span class="text-muted small">{{ __('messages.competitions.waiting') }}</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
