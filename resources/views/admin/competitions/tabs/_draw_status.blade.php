@if($category->tirage_valide)
    <span class="badge bg-success"><i class="fas fa-lock me-1"></i>{{ __('messages.competitions.draw_status_valid') }}</span>
@elseif($category->tirage_at)
    <span class="badge bg-warning text-dark">{{ __('messages.competitions.draw_status_draft') }}</span>
@else
    <span class="badge bg-light text-muted border">{{ __('messages.competitions.draw_status_none') }}</span>
@endif
