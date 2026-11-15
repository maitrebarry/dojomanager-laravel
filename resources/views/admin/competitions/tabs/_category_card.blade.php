{{-- Carte d'une catégorie (tirage, tableau, résultats). Rendue aussi seule après la saisie d'un résultat. --}}
{{-- data-search : noms, prénoms et clubs des athlètes, pour le filtre de l'onglet Tirage. --}}
<div class="card border-0 shadow-sm mb-3 js-category-card" id="categorie-{{ $c->id }}" data-category="{{ $c->id }}"
     data-search="{{ \Illuminate\Support\Str::lower(\Illuminate\Support\Str::ascii($categoryLabels[$c->id] . ' ' . $c->athletes->map(fn ($a) => $a->prenom . ' ' . $a->nom . ' ' . $a->club)->implode(' '))) }}">
    <div class="card-header bg-white d-flex flex-wrap align-items-center gap-2">
        <span class="fw-bold me-1">{{ $categoryLabels[$c->id] }}</span>
        <span class="badge bg-dark">{{ $c->athletes_count }}</span>
        @include('admin.competitions.tabs._draw_status', ['category' => $c])
        <div class="ms-auto d-flex flex-wrap gap-1">
            @if($c->athletes_count >= 2 && !$c->tirage_valide)
                <form method="POST" action="{{ route('admin.competitions.draw', [$competition, $c]) }}" @if($c->tirage_at) onsubmit="return dojoConfirmAction(this, @js(__('messages.competitions.redraw_confirm')))" @endif>
                    @csrf
                    <button class="btn btn-sm btn-primary"><i class="fas fa-shuffle me-1"></i> {{ $c->tirage_at ? __('messages.competitions.redraw') : __('messages.competitions.draw') }}</button>
                </form>
            @endif
            @if($c->tirage_at && !$c->tirage_valide)
                <form method="POST" action="{{ route('admin.competitions.draw.validate', [$competition, $c]) }}">
                    @csrf
                    <button class="btn btn-sm btn-success"><i class="fas fa-lock me-1"></i> {{ __('messages.competitions.draw_validate') }}</button>
                </form>
                <form method="POST" action="{{ route('admin.competitions.draw.cancel', [$competition, $c]) }}" onsubmit="return dojoConfirmDelete(this)">
                    @csrf @method('DELETE')
                    <button class="btn btn-sm btn-outline-danger" title="{{ __('messages.competitions.draw_delete') }}"><i class="fas fa-trash"></i></button>
                </form>
            @endif
            {{-- Plus de déverrouillage une fois des résultats saisis. --}}
            @if($c->tirage_valide && !$c->combats->contains(fn ($f) => !$f->exempt && $f->vainqueur_athlete_id))
                <form method="POST" action="{{ route('admin.competitions.draw.unlock', [$competition, $c]) }}" onsubmit="return dojoConfirmAction(this, @js(__('messages.competitions.unlock_confirm')))">
                    @csrf
                    <button class="btn btn-sm btn-outline-warning"><i class="fas fa-lock-open me-1"></i> {{ __('messages.competitions.draw_unlock') }}</button>
                </form>
            @endif
            @if($c->tirage_at)
                <a class="btn btn-sm btn-outline-dark" target="_blank" href="{{ route('admin.competitions.draw.pdf', [$competition, 'category' => $c->id]) }}"><i class="fas fa-file-pdf"></i> PDF</a>
            @endif
        </div>
    </div>
    <div class="card-body">
        @if($c->athletes_count < 2)
            <div class="small">
                <i class="fas fa-medal text-warning me-1"></i>
                <strong>{{ $c->athletes->first()?->fullName() }}</strong>@if($c->athletes->first()?->club) <span class="text-muted">· {{ $c->athletes->first()->club }}</span>@endif
                <span class="text-muted">— {{ __('messages.competitions.single_athlete') }}</span>
            </div>
        @elseif($c->combats->isNotEmpty())
            <div style="overflow-x: auto;">
                @include('admin.competitions._bracket', ['category' => $c, 'width' => 260, 'height' => min(170, max(50, $c->combats->where('round', 1)->count() * 2 * 10 + 7))])
            </div>
            @if($c->combats->max('round') >= 2)
                <div class="small text-muted mt-2">{{ __($competition->bronze === 'match' ? 'messages.competitions.bronze_note_match' : 'messages.competitions.bronze_note') }}</div>
            @endif
            @include('admin.competitions.tabs._results', ['category' => $c, 'podium' => $podium])
        @else
            <div class="text-muted small"><i class="fas fa-shuffle me-1"></i>{{ __('messages.competitions.draw_todo_hint') }}</div>
        @endif
    </div>
</div>
