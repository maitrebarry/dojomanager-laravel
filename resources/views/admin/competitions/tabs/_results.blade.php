{{-- Résultats d'une catégorie : un vainqueur par combat, qui passe au combat suivant ; podium à la fin. --}}
@php
    $fights = $category->combats->where('exempt', false)->sortBy('numero');
    $nextOf = fn ($f) => $category->combats->first(fn ($c) => $c->round === $f->round + 1 && $c->position === intdiv($f->position, 2));
    $feeder = fn ($f, $side) => $category->combats->first(fn ($c) => $c->round === $f->round - 1 && $c->position === $f->position * 2 + $side);
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
    <div class="small text-muted mb-2">{{ __('messages.competitions.results_help') }}</div>
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 50px;">{{ __('messages.competitions.fight_no') }}</th>
                    <th style="width: 130px;">{{ __('messages.competitions.round') }}</th>
                    <th class="text-primary">{{ __('messages.competitions.blue') }}</th>
                    <th class="text-danger">{{ __('messages.competitions.red') }}</th>
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
                                    <span class="text-muted fst-italic small">{{ __('messages.competitions.waiting') }} (n° {{ $feeder($f, $side)?->numero }})</span>
                                @endif
                            </td>
                        @endforeach
                        <td>
                            @if($f->vainqueur_athlete_id)
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge {{ $f->vainqueur_athlete_id === $f->athlete1_id ? 'bg-primary' : 'bg-danger' }}"><i class="fas fa-trophy me-1"></i>{{ $f->vainqueurAthlete?->fullName() }}</span>
                                    @if($f->score)<span class="small">{{ $f->score }}</span>@endif
                                    @unless($nextOf($f)?->vainqueur_athlete_id)
                                        <form method="POST" action="{{ route('admin.competitions.fights.clear', [$competition, $f]) }}" class="ms-auto" onsubmit="return dojoConfirmAction(this, @js(__('messages.competitions.cancel_result_confirm')))">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-link text-muted p-0" title="{{ __('messages.competitions.cancel_result') }}"><i class="fas fa-rotate-left"></i></button>
                                        </form>
                                    @endunless
                                </div>
                            @elseif($f->isReady())
                                <form method="POST" action="{{ route('admin.competitions.fights.result', [$competition, $f]) }}" class="d-flex gap-1">
                                    @csrf
                                    {{-- Champ caché : le bouton cliqué est désactivé pendant l'envoi (indicateur de chargement), sa valeur ne partirait pas. --}}
                                    <input type="hidden" name="vainqueur">
                                    <input name="score" class="form-control form-control-sm" style="max-width: 90px;" placeholder="{{ __('messages.competitions.score_placeholder') }}" maxlength="40">
                                    <button class="btn btn-sm btn-primary text-nowrap" onclick="this.form.vainqueur.value = 'bleu'">{{ __('messages.competitions.blue_wins') }}</button>
                                    <button class="btn btn-sm btn-danger text-nowrap" onclick="this.form.vainqueur.value = 'rouge'">{{ __('messages.competitions.red_wins') }}</button>
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
