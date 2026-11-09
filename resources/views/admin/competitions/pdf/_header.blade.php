{{-- En-tête commun des PDF de compétition. Paramètres : $competition, $title, $subtitle. --}}
<table style="width: 100%; border-bottom: 2px solid #0f5132; margin-bottom: 8px;">
    <tr>
        <td style="vertical-align: bottom;">
            <div style="font-size: 15px; font-weight: bold; color: #0f5132;">{{ $title }}</div>
            <div style="font-size: 13px; font-weight: bold;">{{ $subtitle }}</div>
        </td>
        <td style="text-align: right; vertical-align: bottom; font-size: 11px; color: #4b5563;">
            <strong>{{ $competition->nom }}</strong><br>
            {{ optional($competition->date_competition)->format('d/m/Y') }}@if($competition->lieu) — {{ $competition->lieu }}@endif
        </td>
    </tr>
</table>
