<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <style>
        /* A4 paysage : 277 × 190 mm utiles = bandeau 20 + tableau 152 + pied 14 (+ marges). */
        @page { margin: 10mm; }
        body { font-family: DejaVu Sans, sans-serif; color: #0f172a; margin: 0; }
        .section { page-break-after: always; height: 189mm; position: relative; }
        .section:last-child { page-break-after: auto; }

        .banner { width: 100%; height: 18mm; background: #0f5132; color: #fff; border-radius: 2mm; border-collapse: separate; }
        .banner td { padding: 0 5mm; vertical-align: middle; }
        .banner .kicker { font-size: 2.6mm; letter-spacing: .5mm; color: #d4af37; font-weight: bold; }
        .banner .category { font-size: 6mm; font-weight: bold; line-height: 7.5mm; }
        .banner .event { text-align: right; font-size: 3.2mm; line-height: 4.6mm; }
        .chip { display: inline-block; padding: .6mm 2.2mm; border-radius: 3mm; font-size: 2.6mm; font-weight: bold; margin-left: 2mm; vertical-align: middle; }
        .chip-count { background: #d4af37; color: #0f172a; }
        .chip-draft { background: #fef3c7; color: #92400e; }
        .chip-valid { background: #dcfce7; color: #166534; }

        .footer { position: absolute; left: 0; right: 0; bottom: 0; height: 11mm; border-top: .3mm solid #cbd5e1; font-size: 2.5mm; color: #475569; }
        .footer table { width: 100%; margin-top: 2mm; }
        .legend span { display: inline-block; width: 3mm; height: 3mm; border-radius: .6mm; vertical-align: middle; margin: 0 1mm 0 3mm; }
        .single { margin-top: 40mm; text-align: center; font-size: 6mm; }
        .single small { display: block; margin-top: 4mm; font-size: 3.5mm; color: #64748b; }
    </style>
</head>
<body>
@foreach($categories as $category)
    <div class="section">
        <table class="banner">
            <tr>
                <td>
                    <div class="kicker">{{ mb_strtoupper(__('messages.competitions.brackets_title')) }}</div>
                    <div class="category">
                        {{ $labels[$category->id] }}
                        <span class="chip chip-count">{{ $category->athletes->count() }} athlète(s)</span>
                        @if($category->tirage_valide)
                            <span class="chip chip-valid">{{ __('messages.competitions.draw_status_valid') }}</span>
                        @else
                            <span class="chip chip-draft">{{ __('messages.competitions.draw_status_draft') }}</span>
                        @endif
                    </div>
                </td>
                <td class="event">
                    <strong>{{ $competition->nom }}</strong><br>
                    {{ optional($competition->date_competition)->format('d/m/Y') }}@if($competition->lieu) — {{ $competition->lieu }}@endif
                </td>
            </tr>
        </table>

        <div style="margin-top: 4mm;">
            @if($category->combats->isEmpty())
                <div class="single">
                    <strong>{{ $category->athletes->first()?->fullName() }}</strong>
                    <small>{{ __('messages.competitions.single_athlete') }}</small>
                </div>
            @else
                @include('admin.competitions._bracket', ['category' => $category, 'width' => 277, 'height' => 150])
            @endif
        </div>

        <div class="footer">
            <table>
                <tr>
                    <td class="legend">
                        <span style="background: #1d4ed8; margin-left: 0;"></span>{{ __('messages.competitions.blue') }}
                        <span style="background: #b91c1c;"></span>{{ __('messages.competitions.red') }}
                        <span style="background: #b91c1c; border-radius: 1.5mm;"></span>N° d'appel du combat
                        &nbsp;·&nbsp; {{ __('messages.competitions.bronze_note') }}
                    </td>
                    <td style="text-align: right; white-space: nowrap;">
                        Tirage du {{ $category->tirage_at?->format('d/m/Y à H:i') }} &nbsp;—&nbsp; Signature : ..............................
                    </td>
                </tr>
            </table>
        </div>
    </div>
@endforeach
</body>
</html>
