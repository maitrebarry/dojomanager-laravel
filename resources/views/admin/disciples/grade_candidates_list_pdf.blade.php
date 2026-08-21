<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>{{ __('messages.disciple_grades.candidates_title') }}</title>
    <style>
        @page { margin: 14mm; }
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; margin: 0; font-size: 12px; }
        header { text-align: center; margin-bottom: 10px; }
        .official-row { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        .official-row td { border: none; padding: 0; vertical-align: top; font-size: 10px; line-height: 1.5; color: #374151; }
        .official-row .left { width: 62%; text-align: left; font-weight: 700; text-transform: uppercase; }
        .official-row .left .federation,
        .official-row .left .ligue { color: #152645; font-size: 11.5px; }
        .official-row .right { width: 38%; text-align: right; font-weight: 700; text-transform: uppercase; }
        .official-row .right .motto { font-style: italic; font-weight: 400; color: #6b7280; text-transform: none; }
        hr.sep { border: none; border-top: 1.5px dashed #c7cfe0; margin: 8px auto 10px; width: 60%; }
        h1 { color: #152645; font-size: 20px; margin: 0 0 2px; text-transform: uppercase; letter-spacing: .5px; }
        .sub { color: #6b7280; font-size: 12px; margin-bottom: 2px; }
        .meta { text-align: center; color: #6b7280; font-size: 11px; margin-bottom: 14px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d7dee9; padding: 6px 8px; }
        th { background: #152645; color: #fff; text-transform: uppercase; font-size: 10px; text-align: left; }
        td { font-size: 11.5px; }
        td.center, th.center { text-align: center; }
        .num { width: 26px; text-align: center; color: #6b7280; }
        .blank { color: #cbd5e1; }
        footer { margin-top: 26px; display: flex; justify-content: space-between; font-size: 11px; }
        .sign { width: 45%; text-align: center; }
        .sign .line { border-top: 1px solid #333; margin-top: 40px; padding-top: 4px; }
    </style>
</head>
<body>
    <header>
        <table class="official-row">
            <tr>
                <td class="left">
                    {{ $official['ministry'] }}<br>
                    <span class="federation">{{ $federation?->nom ?? $official['federation'] }}</span>
                    @if($ligue?->nom)<br><span class="ligue">{{ $ligue->nom }}</span>@endif
                </td>
                <td class="right">
                    République du Mali<br>
                    <span class="motto">Un Peuple – Un But – Une Foi</span>
                </td>
            </tr>
        </table>
        <hr class="sep">
        <h1>{{ __('messages.disciple_grades.candidates_title') }}</h1>
        <div class="sub">{{ $salle?->nom ?? __('messages.app_name') }}</div>
    </header>
    <div class="meta">
        {{ __('messages.disciples.title') }} : {{ $rows->count() }} &nbsp;·&nbsp; {{ now()->format('d/m/Y') }}
    </div>

    <table>
        <thead>
            <tr>
                <th class="center">N°</th>
                <th>{{ __('messages.full_name') }}</th>
                <th>{{ __('messages.disciples.matricule') }}</th>
                <th>{{ __('messages.disciple_grades.current_grade') }}</th>
                <th>{{ __('messages.disciple_grades.next_grade') }}</th>
                <th class="center">{{ __('messages.disciple_grades.result') }}</th>
                <th class="center">{{ __('messages.disciple_grades.candidate_signature') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $row)
                <tr>
                    <td class="num">{{ $loop->iteration }}</td>
                    <td>{{ $row->disciple->full_name }}</td>
                    <td>{{ $row->disciple->nmle ?: '-' }}</td>
                    <td>{{ $row->disciple->grade?->nom_grade ?? __('messages.disciple_grades.no_grade') }}</td>
                    <td>{{ $row->nextGrade?->nom_grade ?? '-' }}</td>
                    <td class="center blank">&nbsp;</td>
                    <td class="center blank">&nbsp;</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <footer>
        <div class="sign">
            <div class="line">{{ __('messages.disciple_grades.exam_date') }}</div>
        </div>
        <div class="sign">
            <div class="line">{{ __('messages.master_signature') }}</div>
        </div>
    </footer>
</body>
</html>
