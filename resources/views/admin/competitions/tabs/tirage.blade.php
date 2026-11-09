{{-- Onglet Tirage : tirer, vérifier, valider (verrouiller), imprimer. --}}
@php $drawn = $categories->contains(fn ($c) => $c->tirage_at); @endphp
<div class="alert alert-light border small"><i class="fas fa-scale-balanced me-1"></i> {{ __('messages.competitions.draw_rules') }}</div>

<div class="d-flex flex-wrap gap-2 mb-3">
    <form method="POST" action="{{ route('admin.competitions.draw.all', $competition) }}" onsubmit="return dojoConfirmAction(this, @js(__('messages.competitions.redraw_confirm')))">
        @csrf
        <button class="btn btn-primary" @disabled($categories->isEmpty())><i class="fas fa-shuffle me-1"></i> {{ __('messages.competitions.draw_all') }}</button>
    </form>
    @if($drawn)
        <a class="btn btn-outline-dark" target="_blank" href="{{ route('admin.competitions.draw.pdf', $competition) }}"><i class="fas fa-file-pdf me-1"></i> {{ __('messages.competitions.draw_pdf') }}</a>
        <a class="btn btn-outline-dark" target="_blank" href="{{ route('admin.competitions.fights.pdf', $competition) }}"><i class="fas fa-list-ol me-1"></i> {{ __('messages.competitions.fights_pdf') }}</a>
    @endif
</div>

@forelse($categories->filter(fn ($c) => $c->athletes_count > 0) as $c)
    <div class="card border-0 shadow-sm mb-3" id="categorie-{{ $c->id }}">
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
                @if($c->tirage_valide)
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
                <div class="text-muted small">{{ __('messages.competitions.single_athlete') }}</div>
            @elseif($c->combats->isNotEmpty())
                <div style="overflow-x: auto;">
                    @include('admin.competitions._bracket', ['category' => $c, 'width' => 260, 'height' => 160])
                </div>
                @if($c->combats->max('round') >= 2)
                    <div class="small text-muted mt-2">{{ __('messages.competitions.bronze_note') }}</div>
                @endif
            @else
                <div class="text-muted small">{{ __('messages.competitions.draw_status_none') }}</div>
            @endif
        </div>
    </div>
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
