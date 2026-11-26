<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 9mm 9mm 10mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 8.5px; color: #111827; }
        .salle { page-break-after: always; }
        .salle:last-child { page-break-after: auto; }
        .head { width: 100%; border-bottom: 2px solid #0f5132; margin-bottom: 5px; }
        .head td { vertical-align: bottom; }
        .title { font-size: 13px; font-weight: bold; color: #0f5132; }
        .sub { font-size: 10px; font-weight: bold; }
        .meta { text-align: right; font-size: 9px; color: #374151; }
        .box { width: 100%; border: 1px solid #9ca3af; margin-bottom: 4px; }
        .box td { padding: 3px 5px; font-size: 9px; }
        .line { border-bottom: 1px dotted #6b7280; display: inline-block; min-width: 150px; }
        .help { font-size: 7.8px; color: #374151; margin: 0 0 4px; }
        table.grid { width: 100%; border-collapse: collapse; }
        table.grid th { background: #e5e7eb; border: 1px solid #374151; padding: 2px 3px; font-size: 7.8px; }
        table.grid td { border: 1px solid #374151; padding: 1px 3px; height: 21px; font-size: 8.5px; }
        table.grid tr { page-break-inside: avoid; }
        .known { color: #1f2937; }
        .center { text-align: center; }
        .new td { background: #fafafa; }
        .sign { width: 100%; margin-top: 8px; }
        .sign td { font-size: 9px; padding-top: 4px; }
        .grades { font-size: 7.5px; color: #4b5563; margin-top: 3px; }
    </style>
</head>
<body>
@foreach($salles as $salle)
    @php
        $list = $disciples[$salle->id] ?? collect();
        $maitreName = $salle->maitre_display_name;
        $maitrePhone = $salle->maitre?->telephone ?: $salle->maitreUser?->phone;
        $maitreGrade = $salle->maitre_display_grade;
    @endphp
    <div class="salle">
        <table class="head">
            <tr>
                <td>
                    <div class="title">FICHE DE RENSEIGNEMENTS — LICENCE RÉGIONALE</div>
                    <div class="sub">Salle : {{ mb_strtoupper($salle->nom) }}</div>
                </td>
                <td class="meta">
                    <strong>{{ $salle->ligue?->nom }}</strong>@if($salle->ligue?->region) — {{ $salle->ligue->region }}@endif<br>
                    {{ $salle->ligue?->federation?->nom }}<br>
                    Saison {{ now()->year }}-{{ now()->year + 1 }}
                </td>
            </tr>
        </table>

        <table class="box">
            <tr>
                <td style="width: 45%;"><strong>Maître de la salle :</strong> <span class="line" style="min-width: 220px;">{{ $maitreName }}</span></td>
                <td style="width: 28%;"><strong>Téléphone :</strong> <span class="line" style="min-width: 110px;">{{ $maitrePhone }}</span></td>
                <td><strong>Grade :</strong> <span class="line" style="min-width: 90px;">{{ $maitreGrade }}</span></td>
            </tr>
        </table>
        <p class="help">
            Merci de&nbsp;<strong>vérifier</strong>&nbsp;les informations déjà connues (corrigez-les si besoin en les barrant), de&nbsp;<strong>compléter les cases vides</strong>&nbsp;et d'ajouter
            vos nouveaux disciples sur les lignes libres. Ces informations figureront sur la carte de licence régionale. Joignez une&nbsp;<strong>photo d'identité récente</strong>
            de chaque disciple (prénom et nom écrits au dos) et cochez la colonne « Photo ». Dates au format jj/mm/aaaa.
        </p>

        <table class="grid">
            <thead>
                <tr>
                    <th style="width: 3%;">N°</th>
                    <th style="width: 8%;">Matricule</th>
                    <th style="width: 11%;">Prénom</th>
                    <th style="width: 10%;">NOM</th>
                    <th style="width: 4%;">Sexe<br>M / F</th>
                    <th style="width: 8%;">Date de naissance</th>
                    <th style="width: 10%;">Lieu de naissance</th>
                    <th style="width: 13%;">Adresse (quartier, ville)</th>
                    <th style="width: 9%;">Téléphone</th>
                    <th style="width: 8%;">Grade actuel</th>
                    <th style="width: 8%;">Date du grade</th>
                    <th style="width: 4%;">Photo</th>
                </tr>
            </thead>
            <tbody>
                @foreach($list as $i => $d)
                    @php [$birthDate, $birthPlace] = [$d->date_naissance?->format('d/m/Y') ?? '', preg_replace('/^.*\s+à\s+/iu', '', (string) $d->date_lieu_naissance)]; @endphp
                    <tr class="known">
                        <td class="center">{{ $i + 1 }}</td>
                        <td>{{ $d->nmle }}</td>
                        <td>{{ $d->prenom }}</td>
                        <td>{{ mb_strtoupper($d->nom) }}</td>
                        <td class="center">{{ $d->sexe }}</td>
                        <td class="center">{{ $birthDate }}</td>
                        <td>{{ $birthPlace }}</td>
                        <td>{{ $d->adresse }}</td>
                        <td>{{ $d->telephone }}</td>
                        <td>{{ $d->grade?->nom_grade }}</td>
                        <td class="center">{{ $d->date_obtention_grade?->format('d/m/Y') }}</td>
                        <td class="center">{{ $d->photo ? '✓' : '' }}</td>
                    </tr>
                @endforeach
                @for($n = 1; $n <= $blankRows; $n++)
                    <tr class="new">
                        <td class="center">{{ $list->count() + $n }}</td>
                        <td style="color: #9ca3af; font-size: 7px;">nouveau</td>
                        <td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td>
                    </tr>
                @endfor
            </tbody>
        </table>
        <div class="grades">Grades : {{ $grades->pluck('nom_grade')->implode(' · ') }}</div>

        <table class="sign">
            <tr>
                <td style="width: 50%;">Fait à <span class="line" style="min-width: 140px;"></span> le <span class="line" style="min-width: 90px;"></span></td>
                <td style="text-align: right;">Signature et cachet du maître : <span class="line" style="min-width: 170px;"></span></td>
            </tr>
        </table>
    </div>
@endforeach
</body>
</html>
