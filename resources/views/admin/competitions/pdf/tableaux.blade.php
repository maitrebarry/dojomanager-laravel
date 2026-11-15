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

        /* Page de fin : catégories sans tableau (pas de hauteur fixe, elle peut déborder). */
        .others h2 { font-size: 4.5mm; color: #0f5132; margin: 5mm 0 2mm; }
        .others p { font-size: 3mm; color: #475569; margin: 0 0 3mm; }
        .others table { width: 100%; border-collapse: collapse; font-size: 3.2mm; }
        .others th, .others td { border: .3mm solid #94a3b8; padding: 1.6mm 2mm; text-align: left; }
        .others th { background: #e8f3ed; color: #0f5132; }
        .others .medal { color: #92400e; font-weight: bold; }
        .single small { display: block; margin-top: 4mm; font-size: 3.5mm; color: #64748b; }
    </style>
</head>
<body>
@php $profile = $competition->profile(); @endphp
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
                        <span style="background: {{ $profile['corners'][1]['color'] }}; margin-left: 0;"></span>{{ $profile['corners'][1]['name'] }}
                        <span style="background: {{ $profile['corners'][2]['color'] }};"></span>{{ $profile['corners'][2]['name'] }}
                        <span style="background: #b91c1c; border-radius: 1.5mm;"></span>N° d'appel du combat
                        &nbsp;·&nbsp; {{ __($competition->bronze === 'match' ? 'messages.competitions.bronze_note_match' : 'messages.competitions.bronze_note') }}
                    </td>
                    <td style="text-align: right; white-space: nowrap;">
                        Tirage du {{ $category->tirage_at?->format('d/m/Y à H:i') }} &nbsp;—&nbsp; Signature : ..............................
                    </td>
                </tr>
            </table>
        </div>
    </div>
@endforeach

@if($singles->isNotEmpty() || $pending->isNotEmpty())
    <div class="others">
        <table class="banner">
            <tr>
                <td>
                    <div class="kicker">{{ mb_strtoupper(__('messages.competitions.brackets_title')) }}</div>
                    <div class="category">{{ __('messages.competitions.pdf_others_title') }}</div>
                </td>
                <td class="event">
                    <strong>{{ $competition->nom }}</strong><br>
                    {{ optional($competition->date_competition)->format('d/m/Y') }}@if($competition->lieu) — {{ $competition->lieu }}@endif
                </td>
            </tr>
        </table>

        @if($singles->isNotEmpty())
            <h2>{{ __('messages.competitions.pdf_singles_title', ['count' => $singles->count()]) }}</h2>
            <p>{{ __('messages.competitions.pdf_singles_help') }}</p>
            <table>
                <thead><tr><th style="width: 6%;">N°</th><th style="width: 34%;">{{ __('messages.competitions.category') }}</th><th style="width: 26%;">{{ __('messages.competitions.athletes') }}</th><th style="width: 18%;">{{ __('messages.competitions.club') }}</th><th style="width: 16%;">{{ __('messages.competitions.pdf_result') }}</th></tr></thead>
                <tbody>
                    @foreach($singles as $i => $category)
                        @php $athlete = $category->athletes->first(); @endphp
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>{{ $labels[$category->id] }}</td>
                            <td><strong>{{ $athlete->fullName() }}</strong></td>
                            <td>{{ $athlete->club ?: '—' }}</td>
                            <td class="medal">{{ __('messages.competitions.pdf_walkover') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        @if($pending->isNotEmpty())
            <h2>{{ __('messages.competitions.pdf_pending_title', ['count' => $pending->count()]) }}</h2>
            <p>{{ __('messages.competitions.pdf_pending_help') }}</p>
            <table>
                <thead><tr><th style="width: 6%;">N°</th><th style="width: 34%;">{{ __('messages.competitions.category') }}</th><th style="width: 60%;">{{ __('messages.competitions.athletes') }}</th></tr></thead>
                <tbody>
                    @foreach($pending as $i => $category)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>{{ $labels[$category->id] }}</td>
                            <td>{{ $category->athletes->map->fullName()->implode(', ') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endif
</body>
</html>
