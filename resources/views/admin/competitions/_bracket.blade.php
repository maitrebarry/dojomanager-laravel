{{--
    Tableau à élimination directe d'une catégorie, dessiné en positions absolues (mm)
    pour un rendu identique à l'écran et dans le PDF (dompdf).
    Paramètres : $category (combats chargés), $width / $height (mm disponibles).
--}}
@php
    $combats = $category->combats;
    $rounds = (int) $combats->max('round');
    $firstRound = $combats->where('round', 1)->values();
    $slotsCount = $firstRound->count() * 2;
    $columns = $rounds + 1; // + colonne du vainqueur
    $colW = min(58, ($width - 2) / $columns);
    $slotH = min(13, $height / max($slotsCount, 1));
    $font = $slotH >= 8 ? 3 : ($slotH >= 5.5 ? 2.5 : 2);
    $byRound = $combats->groupBy('round');

    // Centre vertical (mm) de la ligne i du tour r (r commence à 1).
    $center = fn (int $r, int $i) => ($i + 0.5) * $slotH * (2 ** ($r - 1));
    $name = fn ($athlete) => $athlete ? $athlete->fullName() : null;
@endphp
<div class="bracket" style="position: relative; width: {{ $colW * $columns }}mm; height: {{ $slotH * $slotsCount }}mm; font-size: {{ $font }}mm;">
    @for($r = 1; $r <= $rounds; $r++)
        @foreach($byRound[$r] ?? [] as $combat)
            @foreach([0, 1] as $side)
                @php
                    $i = $combat->position * 2 + $side;
                    $athlete = $side === 0 ? $combat->athlete1 : $combat->athlete2;
                    $isBye = $r === 1 && $combat->exempt && !$athlete;
                @endphp
                <div style="position: absolute; left: {{ ($r - 1) * $colW }}mm; top: {{ $center($r, $i) - $font * 1.45 }}mm; width: {{ $colW - 1 }}mm; height: {{ $font * 1.45 }}mm; border-bottom: .3mm solid #1f2937; white-space: nowrap; overflow: hidden; line-height: {{ $font * 1.4 }}mm; padding-left: .8mm;">
                    @if($athlete)
                        <strong>{{ $name($athlete) }}</strong>@if($athlete->club)<span style="color: #6b7280;"> · {{ $athlete->club }}</span>@endif
                    @elseif($isBye)
                        <span style="color: #9ca3af; font-style: italic;">{{ __('messages.competitions.exempt') }}</span>
                    @endif
                </div>
            @endforeach
            {{-- Accolade du combat : relie les deux lignes ; numéro d'appel au milieu. --}}
            @php $top = $center($r, $combat->position * 2); $bottom = $center($r, $combat->position * 2 + 1); @endphp
            <div style="position: absolute; left: {{ $r * $colW - 1 }}mm; top: {{ $top }}mm; width: 1mm; height: {{ $bottom - $top }}mm; border-right: .3mm solid #1f2937;"></div>
            @if($combat->numero)
                <div style="position: absolute; left: {{ $r * $colW - 1 - $font * 2.6 }}mm; top: {{ ($top + $bottom) / 2 - $font * .7 }}mm; width: {{ $font * 2.4 }}mm; text-align: right; color: #b91c1c; font-weight: bold; font-size: {{ $font * .85 }}mm;">{{ $combat->numero }}</div>
            @endif
        @endforeach
    @endfor
    {{-- Vainqueur de la finale --}}
    @php $final = ($byRound[$rounds] ?? collect())->first(); @endphp
    <div style="position: absolute; left: {{ $rounds * $colW }}mm; top: {{ $center($rounds + 1, 0) - $font * 1.45 }}mm; width: {{ $colW - 1 }}mm; height: {{ $font * 1.45 }}mm; border-bottom: .5mm solid #0f5132; line-height: {{ $font * 1.4 }}mm; padding-left: .8mm; white-space: nowrap; overflow: hidden;">
        @if($final?->vainqueurAthlete)<strong>{{ $name($final->vainqueurAthlete) }}</strong>@else<span style="color: #9ca3af;">{{ __('messages.competitions.winner_to_fill') }}</span>@endif
    </div>
</div>
