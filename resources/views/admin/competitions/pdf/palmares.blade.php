<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 12mm 10mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111827; }
        h2 { font-size: 12px; color: #0f5132; margin: 10px 0 4px; border-bottom: 1px solid #0f5132; padding-bottom: 2px; }
        table.podium { width: 100%; border-collapse: collapse; }
        table.podium th, table.podium td { border: 1px solid #9ca3af; padding: 3px 5px; vertical-align: top; }
        table.podium th { font-size: 9px; color: #fff; }
        table.podium tr { page-break-inside: avoid; }
        .gold { background: #b45309; } .silver { background: #6b7280; } .bronze { background: #9a3412; }
        .cat { font-weight: bold; width: 22%; }
        .name { font-weight: bold; }
        .club { color: #4b5563; font-size: 8.5px; }
        .note { color: #0f5132; font-size: 8.5px; font-style: italic; }
        .status { color: #9ca3af; font-style: italic; text-align: center; }
        table.medals { width: 70%; border-collapse: collapse; margin-top: 4px; }
        table.medals th, table.medals td { border: 1px solid #9ca3af; padding: 3px 6px; text-align: center; }
        table.medals th { background: #e5e7eb; }
        table.medals td.left { text-align: left; }
        .help { color: #6b7280; font-size: 8.5px; margin: 0 0 4px; }
    </style>
</head>
<body>
@include('admin.competitions.pdf._header', [
    'title' => __('messages.competitions.palmares'),
    'subtitle' => __('messages.competitions.palmares_subtitle', ['finished' => $finished, 'total' => $rows->count()]),
])
<p class="help">{{ __('messages.competitions.palmares_help') }}</p>

@php
    $person = function ($athlete, ?string $note = null) {
        if (!$athlete) {
            return '';
        }
        return '<div class="name">' . e($athlete->fullName()) . '</div>'
            . ($athlete->club ? '<div class="club">' . e($athlete->club) . '</div>' : '')
            . ($note ? '<div class="note">' . e($note) . '</div>' : '');
    };
@endphp

@foreach($rows->groupBy('group') as $group => $items)
    <h2>{{ $group }}</h2>
    <table class="podium">
        <thead>
            <tr>
                <th style="background: #0f5132;">{{ __('messages.competitions.category') }}</th>
                <th class="gold" style="width: 26%;">1<sup>er</sup> — {{ __('messages.competitions.gold') }}</th>
                <th class="silver" style="width: 26%;">2<sup>e</sup> — {{ __('messages.competitions.silver') }}</th>
                <th class="bronze" style="width: 26%;">3<sup>e</sup> — {{ __('messages.competitions.bronze') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $row)
                <tr>
                    <td class="cat">{{ $row['label'] }}</td>
                    @if($row['status'])
                        <td colspan="3" class="status">{{ $row['status'] }}</td>
                    @else
                        <td>{!! $person($row['podium']['gold'], $row['single'] ? __('messages.competitions.palmares_single') : null) !!}</td>
                        <td>{!! $person($row['podium']['silver']) !!}</td>
                        <td>@foreach($row['podium']['bronze'] as $athlete){!! $person($athlete) !!}@if(!$loop->last)<div style="height: 3px;"></div>@endif @endforeach</td>
                    @endif
                </tr>
            @endforeach
        </tbody>
    </table>
@endforeach

@if($medals)
    <h2>{{ __('messages.competitions.medals') }}</h2>
    <table class="medals">
        <thead><tr><th style="text-align: left;">{{ __('messages.competitions.club') }}</th><th>{{ __('messages.competitions.gold') }}</th><th>{{ __('messages.competitions.silver') }}</th><th>{{ __('messages.competitions.bronze') }}</th><th>{{ __('messages.competitions.total') }}</th></tr></thead>
        <tbody>
            @foreach($medals as $club => $m)
                <tr><td class="left">{{ $club }}</td><td>{{ $m['gold'] }}</td><td>{{ $m['silver'] }}</td><td>{{ $m['bronze'] }}</td><td><strong>{{ $m['gold'] + $m['silver'] + $m['bronze'] }}</strong></td></tr>
            @endforeach
        </tbody>
    </table>
    <p class="help">{{ __('messages.competitions.medals_help') }}</p>
@endif
</body>
</html>
