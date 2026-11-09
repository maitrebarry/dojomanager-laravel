<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 10mm; }
        body { font-family: DejaVu Sans, sans-serif; color: #111827; }
        .section { page-break-after: always; }
        .section:last-child { page-break-after: auto; }
        .note { font-size: 9px; color: #6b7280; margin-top: 4mm; }
    </style>
</head>
<body>
@foreach($categories as $category)
    <div class="section">
        @include('admin.competitions.pdf._header', [
            'title' => __('messages.competitions.brackets_title'),
            'subtitle' => $labels[$category->id] . ' — ' . $category->athletes->count() . ' athlète(s)' . ($category->tirage_valide ? '' : ' — ' . mb_strtoupper(__('messages.competitions.draw_status_draft'))),
        ])
        @if($category->combats->isEmpty())
            <p>{{ __('messages.competitions.single_athlete') }} {{ $category->athletes->first()?->fullName() }}</p>
        @else
            @include('admin.competitions._bracket', ['category' => $category, 'width' => 275, 'height' => 158])
            <div class="note">{{ __('messages.competitions.bronze_note') }} Les numéros en rouge sont les numéros d'appel des combats.</div>
        @endif
    </div>
@endforeach
</body>
</html>
