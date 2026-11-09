<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 12mm 10mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111827; }
        .section { page-break-after: always; }
        .section:last-child { page-break-after: auto; }
        table.list { width: 100%; border-collapse: collapse; }
        table.list th, table.list td { border: 1px solid #374151; padding: 0 4px; }
        table.list th { background: #e5e7eb; font-size: 10px; height: 22px; }
        table.list td { height: 26px; }
        .center { text-align: center; }
        .blank { background: #fff; }
    </style>
</head>
<body>
@foreach($sections as $section)
    <div class="section">
        @include('admin.competitions.pdf._header', ['title' => __('messages.competitions.list_title'), 'subtitle' => $section['label'] . ' — ' . $section['athletes']->count() . ' athlète(s)'])
        <table class="list">
            <thead>
                <tr>
                    <th style="width: 5%;">N°</th>
                    <th style="width: 17%;">{{ __('messages.competitions.last_name') }}</th>
                    <th style="width: 17%;">{{ __('messages.competitions.first_name') }}</th>
                    <th style="width: 6%;">{{ __('messages.competitions.age') }}</th>
                    <th style="width: 15%;">{{ __('messages.competitions.club') }}</th>
                    <th style="width: 8%;">{{ __('messages.competitions.weight') }}</th>
                    <th style="width: 9%;">{{ __('messages.competitions.weighed') }}</th>
                    <th style="width: 11%;">{{ __('messages.competitions.signature') }}</th>
                    <th style="width: 12%;">{{ __('messages.competitions.remark') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($section['athletes'] as $i => $a)
                    <tr>
                        <td class="center">{{ $loop->iteration }}</td>
                        <td><strong>{{ $a->nom }}</strong></td>
                        <td>{{ $a->prenom }}</td>
                        <td class="center">{{ $a->sportAge($year) }}</td>
                        <td>{{ $a->club }}</td>
                        <td class="center">{{ $a->poids !== null ? \App\Models\CompetitionCategory::formatKg((float) $a->poids) : '' }}</td>
                        <td class="blank"></td>
                        <td class="blank"></td>
                        <td class="blank"></td>
                    </tr>
                @endforeach
                {{-- Lignes vides pour les retardataires inscrits sur place. --}}
                @for($i = 0; $i < 3; $i++)
                    <tr><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
                @endfor
            </tbody>
        </table>
        <table style="width: 100%; margin-top: 18px; font-size: 11px;">
            <tr>
                <td style="width: 50%;">Responsable de la pesée : ............................................</td>
                <td style="width: 50%; text-align: right;">Signature : ............................................</td>
            </tr>
        </table>
    </div>
@endforeach
</body>
</html>
