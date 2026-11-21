{{-- Bouton « Modifier le groupe » (fenêtre #editGroup de l'onglet Catégories). --}}
@php $g = $groupData[$key] ?? null; @endphp
@if($g)
    @if($g['locked'])
        <button type="button" class="btn btn-sm btn-link text-muted p-0 ms-1" disabled title="{{ __('messages.competitions.group_locked') }}"><i class="fas fa-lock"></i></button>
    @else
        <button type="button" class="btn btn-sm btn-outline-primary js-group-edit {{ $class ?? '' }}" data-key="{{ $key }}" data-group="{{ json_encode($g) }}" title="{{ __('messages.competitions.group_edit') }}"><i class="fas fa-pen"></i></button>
    @endif
@endif
