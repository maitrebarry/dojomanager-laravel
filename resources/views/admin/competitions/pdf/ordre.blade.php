<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 12mm 10mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111827; }
        .section { page-break-after: always; }
        .section:last-child { page-break-after: auto; }
        table.order { width: 100%; border-collapse: collapse; }
        table.order th, table.order td { border: 1px solid #374151; padding: 2px 4px; }
        table.order th { background: #e5e7eb; font-size: 9px; }
        table.order td { height: 20px; }
        .center { text-align: center; }
        .muted { color: #9ca3af; font-style: italic; }
    </style>
</head>
<body>
@php
    // Une section par aire de combat (une seule s'il n'y a qu'une aire).
    $sections = $order->groupBy('aire');
@endphp
@foreach($sections as $aire => $items)
    <div class="section">
        @include('admin.competitions.pdf._header', [
            'title' => __('messages.competitions.order_title'),
            'subtitle' => ($aires > 1 ? __('messages.competitions.order_area') . ' ' . $aire . ' — ' : '') . $items->count() . ' combat(s)',
        ])
        <table class="order">
            <thead>
                <tr>
                    <th style="width: 6%;">{{ __('messages.competitions.order_number') }}</th>
                    <th style="width: 21%;">{{ __('messages.competitions.category') }}</th>
                    <th style="width: 11%;">{{ __('messages.competitions.round') }}</th>
                    <th style="width: 6%;">{{ __('messages.competitions.order_bracket_no') }}</th>
                    <th style="width: 19%; color: {{ $profile['corners'][1]['color'] }};">{{ $profile['corners'][1]['name'] }}</th>
                    <th style="width: 19%; color: {{ $profile['corners'][2]['color'] }};">{{ $profile['corners'][2]['name'] }}</th>
                    <th style="width: 12%;">{{ __('messages.competitions.winner_to_fill') }}</th>
                    <th style="width: 6%;">{{ __('messages.competitions.score') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $item)
                    @php $f = $item['combat']; @endphp
                    <tr>
                        <td class="center"><strong>{{ $item['order'] }}</strong></td>
                        <td>{{ $labels[$item['category_id']] }}</td>
                        <td>{{ $item['stage'] }}</td>
                        <td class="center">{{ $f->numero }}</td>
                        <td>@if($f->athlete1)<strong>{{ $f->athlete1->fullName() }}</strong>@else<span class="muted">à venir</span>@endif</td>
                        <td>@if($f->athlete2)<strong>{{ $f->athlete2->fullName() }}</strong>@else<span class="muted">à venir</span>@endif</td>
                        <td>{{ $f->vainqueurAthlete?->fullName() }}</td>
                        <td class="center">{{ $f->score }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endforeach
</body>
</html>
