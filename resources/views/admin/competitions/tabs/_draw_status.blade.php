{{-- État du tirage d'une catégorie, avec ce qu'il faut faire quand il n'y en a pas. --}}
@php $count = $category->athletes_count ?? $category->athletes()->count(); @endphp
@if($category->tirage_valide)
    <span class="badge bg-success"><i class="fas fa-lock me-1"></i>{{ __('messages.competitions.draw_status_valid') }}</span>
@elseif($category->tirage_at)
    <span class="badge bg-warning text-dark">{{ __('messages.competitions.draw_status_draft') }}</span>
@elseif($count === 1)
    @php $alone = $category->athletes->first(); @endphp
    <span class="badge bg-info text-dark" title="{{ __('messages.competitions.single_athlete') }}"><i class="fas fa-user me-1"></i>{{ __('messages.competitions.draw_status_single_name', ['name' => $alone?->fullName()]) }}</span>
@elseif($count === 0)
    <span class="badge bg-light text-muted border">{{ __('messages.competitions.draw_status_empty') }}</span>
@else
    <span class="badge bg-danger-subtle text-danger border border-danger-subtle"><i class="fas fa-shuffle me-1"></i>{{ __('messages.competitions.draw_status_todo') }}</span>
@endif
