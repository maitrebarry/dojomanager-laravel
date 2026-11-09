{{--
    Tableau à élimination directe d'une catégorie, en positions absolues (mm) pour un
    rendu identique à l'écran et dans le PDF (dompdf). Tout est proportionnel à la place
    disponible : un petit tableau occupe toute la page, un grand se resserre.
    Paramètres : $category (combats chargés), $width / $height (mm disponibles).
--}}
@php
    $profile = $category->competition->profile();
    $combats = $category->combats;
    $third = $combats->first(fn ($c) => $c->troisieme_place);
    $main = $combats->reject(fn ($c) => $c->troisieme_place);
    $rounds = (int) $main->max('round');
    $slotsCount = $main->where('round', 1)->count() * 2;

    $labelH = 7;                                         // noms des tours au-dessus des colonnes
    $columns = $rounds + 1;                              // + colonne du vainqueur
    $colW = min(($width - 2) / $columns, 92);
    $slotH = ($height - $labelH) / max($slotsCount, 1);
    $boxH = min($slotH * 0.78, 15);
    $boxW = $colW - 7;                                   // place pour l'accolade et le numéro
    $font = max(2.1, min($boxH * 0.34, 4.6));
    $twoLines = $boxH >= 8.5;                            // club sous le nom si la case est assez haute
    $offsetX = max(0, ($width - $colW * $columns) / 2);  // tableau centré
    $badge = max(3, min($font * 1.25, 5.5));

    // dompdf ignore box-sizing : largeurs et hauteurs données sans bordures ni marges intérieures.
    $inner = fn (float $size, float $minus) => max(1, $size - $minus);
    // Marge du haut qui centre verticalement une ligne de texte (dompdf gère mal line-height).
    $padY = fn (float $fontSize, float $borders = .5) => max(0, ($boxH - $borders - $fontSize * 1.2) / 2);

    $center = fn (int $r, int $i) => $labelH + ($i + 0.5) * $slotH * (2 ** ($r - 1));
    $x = fn (int $r) => $offsetX + ($r - 1) * $colW;
    $final = $main->first(fn ($c) => $c->round === $rounds);

    // Chaque combat avec sa colonne et la hauteur de ses deux cases. Le match pour la
    // 3e place va dans la colonne du vainqueur, sous la case dorée (seule place libre).
    $items = $main->map(fn ($c) => ['combat' => $c, 'r' => $c->round, 'y1' => $center($c->round, $c->position * 2), 'y2' => $center($c->round, $c->position * 2 + 1)])->all();
    if ($third) {
        $thirdY1 = $center($rounds + 1, 0) + $boxH * 1.5 + 6;
        $thirdY2 = min($thirdY1 + max($boxH * 1.4, 6), $height - $boxH / 2);
        $items[] = ['combat' => $third, 'r' => $columns, 'y1' => min($thirdY1, $thirdY2 - $boxH * 1.1), 'y2' => $thirdY2];
    }
@endphp
<div class="bracket" style="position: relative; width: {{ $width }}mm; height: {{ $height }}mm; font-family: DejaVu Sans, Arial, sans-serif;">
    {{-- Noms des tours --}}
    @for($r = 1; $r <= $columns; $r++)
        <div style="position: absolute; left: {{ $x($r) }}mm; top: 0; width: {{ $boxW }}mm; height: 5mm; line-height: 5mm; text-align: center; font-size: {{ min(3.2, max(2.3, $font * .8)) }}mm; font-weight: bold; color: #0f5132; background: #e8f3ed; border-radius: 1mm;">
            {{ $r <= $rounds ? \App\Services\Competition\DrawService::roundName($r, $rounds) : __('messages.competitions.winner_to_fill') }}
        </div>
    @endfor

    @foreach($items as $item)
        @php
            $combat = $item['combat'];
            $r = $item['r'];
        @endphp
        @if($combat->troisieme_place)
            <div style="position: absolute; left: {{ $x($r) }}mm; top: {{ $item['y1'] - $boxH / 2 - 4.5 }}mm; width: {{ $boxW }}mm; height: 4mm; line-height: 4mm; text-align: center; font-size: {{ min(3, max(2.2, $font * .75)) }}mm; font-weight: bold; color: #92400e;">{{ \App\Services\Competition\DrawService::THIRD_PLACE }}</div>
        @endif
        @foreach([1, 2] as $corner)
            @php
                $athlete = $corner === 1 ? $combat->athlete1 : $combat->athlete2;
                $isBye = $r === 1 && $combat->exempt && !$athlete;
                // Combat joué : le perdant est grisé.
                $lost = $athlete && !$combat->exempt && $combat->vainqueur_athlete_id && $combat->vainqueur_athlete_id !== $athlete->id;
                $nameColor = $lost ? '#94a3b8' : '#0f172a';
                $top = ($corner === 1 ? $item['y1'] : $item['y2']) - $boxH / 2;
            @endphp
            @if($isBye)
                <div style="position: absolute; left: {{ $x($r) }}mm; top: {{ $top }}mm; width: {{ $inner($boxW, 2.5) }}mm; height: {{ $inner($boxH, .5 + $padY($font * .85)) }}mm; border: .25mm dashed #cbd5e1; border-radius: 1mm; color: #94a3b8; font-style: italic; font-size: {{ $font * .85 }}mm; line-height: {{ $font * .85 * 1.2 }}mm; padding: {{ $padY($font * .85) }}mm 0 0 2mm;">{{ __('messages.competitions.exempt') }}</div>
            @else
                <div style="position: absolute; left: {{ $x($r) }}mm; top: {{ $top }}mm; width: {{ $inner($boxW, 4.85) }}mm; height: {{ $inner($boxH, .5) }}mm; background: {{ $athlete ? '#ffffff' : '#f8fafc' }}; border: .25mm solid #94a3b8; border-left: 1.6mm solid {{ $profile['corners'][$corner]['color'] }}; padding: 0 1.5mm; overflow: hidden; white-space: nowrap;">
                    @if($athlete)
                        @if($twoLines)
                            @php $textH = $font * 1.2 + $font * .72 * 1.25; @endphp
                            <div style="font-size: {{ $font }}mm; font-weight: bold; color: {{ $nameColor }}; line-height: {{ $font * 1.2 }}mm; padding-top: {{ max(.2, ($boxH - .5 - $textH) / 2) }}mm;">{{ $athlete->fullName() }}</div>
                            <div style="font-size: {{ $font * .72 }}mm; color: #64748b; line-height: {{ $font * .72 * 1.25 }}mm;">{{ $athlete->club ?: ' ' }}</div>
                        @else
                            <div style="font-size: {{ $font }}mm; line-height: {{ $font * 1.2 }}mm; padding-top: {{ $padY($font) }}mm;"><strong style="color: {{ $nameColor }};">{{ $athlete->fullName() }}</strong>@if($athlete->club)<span style="color: #64748b; font-size: {{ $font * .8 }}mm;"> · {{ $athlete->club }}</span>@endif</div>
                        @endif
                    @endif
                </div>
            @endif
        @endforeach

        {{-- Accolade du combat, trait vers le tour suivant et numéro d'appel. --}}
        @php
            [$y1, $y2] = [$item['y1'], $item['y2']];
            $bx = $x($r) + $boxW;
        @endphp
        <div style="position: absolute; left: {{ $bx }}mm; top: {{ $y1 - .175 }}mm; width: 3.15mm; height: {{ $y2 - $y1 - .35 }}mm; border: .35mm solid #334155; border-left: 0;"></div>
        @unless($combat->troisieme_place)
            <div style="position: absolute; left: {{ $bx + 3.5 }}mm; top: {{ ($y1 + $y2) / 2 }}mm; width: {{ $x($r + 1) - $bx - 3.5 }}mm; height: 0; border-top: .35mm solid #334155;"></div>
        @endunless
        @if($combat->numero)
            <div style="position: absolute; left: {{ $bx + 3.5 - $badge / 2 }}mm; top: {{ ($y1 + $y2) / 2 - $badge / 2 }}mm; width: {{ $badge }}mm; height: {{ $badge }}mm; border-radius: {{ $badge / 2 }}mm; background: #b91c1c; color: #fff; text-align: center; font-size: {{ $badge * .55 }}mm; line-height: {{ $badge * .92 }}mm; font-weight: bold;">{{ $combat->numero }}</div>
        @endif
        @if($combat->score)
            {{-- Score à gauche de la pastille, entre les deux cases du combat. --}}
            <div style="position: absolute; left: {{ $bx + 3.5 - $badge / 2 - 13 }}mm; top: {{ ($y1 + $y2) / 2 - $font * .45 }}mm; width: 12.5mm; text-align: right; font-size: {{ $font * .7 }}mm; line-height: {{ $font * .9 }}mm; font-weight: bold; color: #334155; white-space: nowrap;">{{ $combat->score }}</div>
        @endif

        {{-- Case du vainqueur de la finale. --}}
        @if($combat === $final)
            <div style="position: absolute; left: {{ $x($columns) }}mm; top: {{ ($y1 + $y2) / 2 - $boxH / 2 }}mm; width: {{ $inner($boxW, 4.2) }}mm; height: {{ $inner($boxH, 1.2 + $padY($font, 1.2)) }}mm; background: #fffbeb; border: .6mm solid #d4af37; border-radius: 1mm; padding: {{ $padY($font, 1.2) }}mm 1.5mm 0; overflow: hidden; white-space: nowrap; font-size: {{ $font }}mm; line-height: {{ $font * 1.2 }}mm; font-weight: bold; color: #0f172a;">
                {{ $combat->vainqueurAthlete?->fullName() }}
            </div>
        @endif
    @endforeach
</div>
