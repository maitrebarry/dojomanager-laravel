<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 12mm 10mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10.5px; color: #111827; }
        .section { page-break-after: always; }
        .section:last-child { page-break-after: auto; }
        table.fights { width: 100%; border-collapse: collapse; }
        table.fights th, table.fights td { border: 1px solid #374151; padding: 2px 4px; }
        table.fights th { background: #e5e7eb; font-size: 9.5px; }
        table.fights td { height: 24px; }
        .center { text-align: center; }
        .blue { color: #1d4ed8; }
        .red { color: #b91c1c; }
        .muted { color: #9ca3af; font-style: italic; }
    </style>
</head>
<body>
@php $profile = $competition->profile(); @endphp
@foreach($categories as $category)
    @php
        $fights = $category->combats->where('exempt', false)->sortBy('numero');
        // Combat d'où vient un combattant encore inconnu : tour précédent, place 2p ou 2p+1.
        $feeder = fn ($fight, $side) => $category->combats->first(fn ($c) => !$c->troisieme_place && $c->round === $fight->round - 1 && $c->position === ($fight->troisieme_place ? $side : $fight->position * 2 + $side));
        $origin = fn ($fight, $side) => $fight->troisieme_place
            ? __('messages.competitions.loser_of', ['numero' => $feeder($fight, $side)?->numero])
            : 'Vainqueur du n° ' . $feeder($fight, $side)?->numero;
    @endphp
    @continue($fights->isEmpty())
    <div class="section">
        @include('admin.competitions.pdf._header', ['title' => __('messages.competitions.fights_title'), 'subtitle' => $labels[$category->id]])
        <table class="fights">
            <thead>
                <tr>
                    <th style="width: 6%;">{{ __('messages.competitions.fight_no') }}</th>
                    <th style="width: 14%;">{{ __('messages.competitions.round') }}</th>
                    <th style="width: 28%; color: {{ $profile['corners'][1]['color'] }};">{{ $profile['corners'][1]['name'] }}</th>
                    <th style="width: 28%; color: {{ $profile['corners'][2]['color'] }};">{{ $profile['corners'][2]['name'] }}</th>
                    <th style="width: 16%;">{{ __('messages.competitions.winner_to_fill') }}</th>
                    <th style="width: 8%;">{{ __('messages.competitions.score') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($fights as $fight)
                    <tr>
                        <td class="center"><strong>{{ $fight->numero }}</strong></td>
                        <td>{{ $fight->tour }}</td>
                        <td>
                            @if($fight->athlete1)<strong>{{ $fight->athlete1->fullName() }}</strong>@if($fight->athlete1->club)<br><small>{{ $fight->athlete1->club }}</small>@endif
                            @else<span class="muted">{{ $origin($fight, 0) }}</span>@endif
                        </td>
                        <td>
                            @if($fight->athlete2)<strong>{{ $fight->athlete2->fullName() }}</strong>@if($fight->athlete2->club)<br><small>{{ $fight->athlete2->club }}</small>@endif
                            @else<span class="muted">{{ $origin($fight, 1) }}</span>@endif
                        </td>
                        <td>{{ $fight->vainqueurAthlete?->fullName() }}</td>
                        <td class="center">{{ $fight->score }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endforeach
</body>
</html>
