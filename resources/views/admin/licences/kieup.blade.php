<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $meta['document_title'] }}</title>
    <style>
        @page { size: A4 portrait; margin: 8mm; }

        /* Sans ceci, les navigateurs suppriment par défaut les couleurs de fond à
           l'impression/export PDF (économie d'encre) — ce qui viderait les aplats de
           couleur du visuel (cadres, bandeaux). */
        * { -webkit-print-color-adjust: exact; print-color-adjust: exact; color-adjust: exact; }

        body { margin: 0; background: #e9edf2; color: #1f2933; font-family: DejaVu Serif, "Times New Roman", serif; }

        .toolbar {
            width: 194mm; margin: 6mm auto; display: flex; gap: 8px; justify-content: center;
            font-family: DejaVu Sans, Arial, sans-serif;
        }
        .toolbar button { border: 0; border-radius: 6px; padding: 8px 14px; background: #374151; color: #fff; font-weight: 700; cursor: pointer; }
        .toolbar .btn-print { background: #0f5132; }

        @media print {
            body { background: #fff; }
            .toolbar { display: none; }
        }

        /* ---- Copie fidèle de legacy-kieup-mockup-pdf.blade.php (licence_regionale_tkd) ---- */
        .mockup-page {
            width: 194mm; min-height: 265mm; box-sizing: border-box;
            margin: 0 auto; page-break-after: always; padding-top: 3mm; background: #fff;
        }
        .mockup-page:last-child { page-break-after: auto; }

        .mockup-title { margin-bottom: 3mm; text-align: center; font-family: DejaVu Sans, Arial, sans-serif; font-size: 3mm; font-weight: 800; color: #4b5563; }

        .legacy-card {
            width: 184mm; height: 120mm; margin: 0 auto 3mm; position: relative;
            border: .75mm double #374151; box-sizing: border-box; background: #fbfaf2;
        }
        .legacy-card:before {
            content: ""; position: absolute; top: 0; bottom: 0; left: 50%;
            border-left: .35mm solid #9ca3af; z-index: 3;
        }

        .panel { position: absolute; top: 3.5mm; bottom: 3.5mm; width: 86mm; }
        .panel-left { left: 4mm; }
        .panel-right { right: 4mm; }

        .photo-box {
            position: absolute; left: 5mm; top: 3mm; width: 44mm; height: 42mm;
            border: .45mm solid #374151; background: #f3f4f6; text-align: center; line-height: 42mm;
            font-family: DejaVu Sans, Arial, sans-serif; font-size: 3.5mm; font-weight: 700; color: #6b7280;
            overflow: hidden;
        }
        .photo-box img { width: 100%; height: 100%; object-fit: cover; display: block; }

        .identity-lines { position: absolute; left: 4mm; right: 3mm; top: 47mm; font-size: 2.9mm; line-height: 6mm; font-weight: 700; }
        .identity-lines div { position: relative; min-height: 5.8mm; }
        .identity-lines span { display: inline-block; min-width: 46mm; border-bottom: .35mm dotted #4b5563; vertical-align: baseline; font-weight: 800; }
        .identity-lines .short { min-width: 18mm; }
        .identity-lines .wide { min-width: 58mm; }

        .grade-title { text-align: center; font-size: 6.8mm; line-height: 1; font-weight: 800; letter-spacing: .4mm; margin-bottom: 2.2mm; }

        .grade-table { width: 82.5mm; border-collapse: collapse; table-layout: fixed; margin: 0 auto; font-size: 2.2mm; font-weight: 700; }
        .grade-table th, .grade-table td { border: .35mm solid #374151; padding: .7mm 1mm; vertical-align: middle; }
        .grade-table th { text-align: center; line-height: 1.05; }
        .grade-table td { height: 5.85mm; }
        .grade-table .rank { width: 30%; }
        .grade-table .date { width: 32%; }
        .grade-table .sign { width: 38%; }

        .president-line {
            position: absolute; left: 7mm; right: 7mm; bottom: 4mm; height: 14mm; padding-top: 1.5mm;
            border-top: .35mm dotted #6b7280; text-align: center; font-size: 3mm; font-weight: 800; box-sizing: border-box;
        }
        .president-line img { max-height: 8mm; max-width: 46mm; object-fit: contain; display: block; margin: 0 auto 1mm; }

        .motto-box { position: absolute; left: 10mm; top: 1mm; width: 66mm; font-size: 3.2mm; line-height: 1.15; font-weight: 800; text-align: center; }
        .motto-box h3 { margin: 0 0 1mm; text-align: center; font-size: 4mm; }
        .motto-box ul { margin: 0; padding-left: 0; list-style-position: inside; }

        .warning { position: absolute; left: 0; right: 3mm; top: 47mm; font-size: 3.05mm; line-height: 1.24; font-weight: 800; }
        .warning h3, .warning h4, .warning p { margin: 0; }
        .warning h3 { font-size: 3.35mm; margin-bottom: 2mm; }
        .warning h4 { font-size: 3.35mm; margin-bottom: 4mm; }

        .official-block { font-size: 2.35mm; line-height: 1.12; font-weight: 800; text-align: center; }
        .official-block h3 { margin: 0; font-size: 2.35mm; line-height: 1.08; }
        .official-block p { margin: 2mm 0 0; text-align: left; font-size: 2.35mm; line-height: 1.18; }

        .large-logo { margin: 5mm auto 2.5mm; width: 62mm; height: 41mm; object-fit: contain; display: block; }

        .licence-title { text-align: center; font-size: 4.8mm; font-weight: 900; letter-spacing: .4mm; }
        .licence-number { margin-top: 3mm; text-align: center; font-size: 3.8mm; font-weight: 900; }
        .licence-number span { display: inline-block; min-width: 22mm; color: #b91c1c; letter-spacing: .8mm; }
    </style>
</head>
@php
    $motto = collect(preg_split('/\s*[-–]\s*/u', $official['motto']))->filter()->values();
    $logoPath = public_path('images/legacy-taekwondo-logo.jpg');
    $logoSrc = file_exists($logoPath) ? 'data:image/jpeg;base64,' . base64_encode(file_get_contents($logoPath)) : null;
    $kieupRows = ['9ème KIEUP','8ème KIEUP','7ème KIEUP','6ème KIEUP','5ème KIEUP','4ème KIEUP','3ème KIEUP','2ème KIEUP','1er KIEUP'];
    $pairs = collect($cards)->chunk(2);
@endphp
<body>
    <div class="toolbar">
        <button type="button" class="btn-print" onclick="window.print()">🖨️ Imprimer</button>
        <button type="button" onclick="window.close()">✖ Fermer</button>
    </div>

    @foreach($pairs as $pair)
        {{-- RECTO : intérieur pliable, 2 licences par page --}}
        <section class="mockup-page">
            <div class="mockup-title">RECTO - Intérieur pliable, 2 licences par page</div>
            @foreach($pair as $c)
                <div class="legacy-card">
                    <div class="panel panel-left">
                        <div class="photo-box">
                            @if($c['photo'])<img src="{{ $c['photo'] }}" alt="Photo">@else Photo @endif
                        </div>
                        <div class="identity-lines">
                            <div>Nom : <span>{{ mb_strtoupper($c['nom']) }}</span></div>
                            <div>Prénom : <span>{{ $c['prenom'] }}</span></div>
                            <div>Date et lieu de naissance : <span class="short">{{ $c['birth_date'] }}</span></div>
                            <div>à <span class="wide">{{ $c['birth_place'] }}</span></div>
                            <div>Ligue de : <span>{{ $c['ligue'] }}</span></div>
                            <div>Club : <span class="short">{{ $c['salle'] }}</span> Salle : <span class="short">{{ $c['salle'] }}</span></div>
                            <div>Domicile : <span>{{ $c['adresse'] }}</span></div>
                            <div>N° Mle : <span>{{ $c['reference'] }}</span></div>
                            <div>Tél : <span>{{ $c['phone'] }}</span></div>
                        </div>
                    </div>
                    <div class="panel panel-right">
                        <div class="grade-title">GRADES</div>
                        <table class="grade-table">
                            <thead>
                                <tr>
                                    <th class="rank"></th>
                                    <th class="date">Date d'obtention du Grade</th>
                                    <th class="sign">Signature du Directeur Technique Régional</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($kieupRows as $row)
                                    <tr>
                                        <td>{{ $row }}<br>le</td>
                                        <td>@if($c['grade'] === $row) {{ $c['birth_date'] }} @endif</td>
                                        <td></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <div class="president-line">
                            @if(!empty($c['signature']))<img src="{{ $c['signature'] }}" alt="Signature">@endif
                            {{ mb_strtoupper($c['signer']) }}
                        </div>
                    </div>
                </div>
            @endforeach
        </section>

        {{-- VERSO : extérieur pliable, 2 licences par page --}}
        <section class="mockup-page">
            <div class="mockup-title">VERSO - Extérieur pliable, 2 licences par page</div>
            @foreach($pair as $c)
                <div class="legacy-card">
                    <div class="panel panel-left">
                        <div class="motto-box">
                            <h3>DEVISE</h3>
                            <ul>
                                @foreach($motto as $item)<li>{{ $item }}</li>@endforeach
                            </ul>
                        </div>
                        <div class="warning">
                            <h3>N'ATTAQUER JAMAIS LE PREMIER</h3>
                            <h4>ATTENTION</h4>
                            <p>Il est formellement interdit à tout pratiquant de <strong>TAEKWONDO</strong> de faire usage de ses connaissances en dehors du dojang, pour provoquer des bagarres, sauf en cas de légitime défense.</p>
                            <p>Toute pratique du <strong>TAEKWONDO</strong> en dehors des lieux autorisés est prohibée.</p>
                        </div>
                    </div>
                    <div class="panel panel-right">
                        <div class="official-block">
                            <h3>{{ mb_strtoupper($official['ministry']) }}</h3>
                            <h3>{{ mb_strtoupper($c['federation']) }}</h3>
                            <h3>{{ mb_strtoupper($c['ligue']) }}</h3>
                            <p>
                                @if($c['region'])Région : {{ $c['region'] }}<br>@endif
                                {{ $c['ligue'] }}<br>
                                Taekwondo
                            </p>
                        </div>
                        @if($logoSrc)
                            <img class="large-logo" src="{{ $logoSrc }}" alt="">
                        @endif
                        <div class="licence-title">{{ $c['license_label'] }}</div>
                        <div class="licence-number">N° <span>{{ $c['reference'] }}</span> {{ mb_strtoupper($c['region'] ?: $c['ligue']) }}</div>
                    </div>
                </div>
            @endforeach
        </section>
    @endforeach

    <script>
        window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 400); });
    </script>
</body>
</html>
