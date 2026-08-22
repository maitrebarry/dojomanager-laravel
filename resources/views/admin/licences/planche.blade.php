<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Planche des licences</title>
    <style>
        /* Sans ceci, les navigateurs suppriment par défaut les couleurs de fond à
           l'impression/export PDF (économie d'encre) — ce qui fait disparaître le
           drapeau malien (fond coloré, pas une image) et les autres aplats du visuel. */
        * { -webkit-print-color-adjust: exact; print-color-adjust: exact; color-adjust: exact; }

        body { margin: 0; background: #eef2f7; padding: 10mm 0; }

        .sheet-actions {
            width: 194mm; margin: 0 auto 6mm; display: flex; gap: 8px;
            justify-content: center; font-family: Arial, sans-serif;
        }
        .sheet-actions button {
            border: 0; border-radius: 6px; padding: 8px 14px;
            background: #0f5132; color: #fff; font-weight: 700; cursor: pointer;
        }
        .sheet-actions .btn-close { background: #6b7280; }

        @media print {
            body { padding: 0; background: #fff; }
            .sheet-actions { display: none; }
        }

        /* ---- Copie fidèle de _sheet_content.blade.php (licence_regionale_tkd) ---- */
        .sheet-document { font-family: DejaVu Sans, Arial, Helvetica, sans-serif; color: #111827; }

        .print-page {
            width: 194mm; min-height: 281mm; margin: 0 auto;
            page-break-after: always; box-sizing: border-box;
        }
        .print-page:last-child { page-break-after: auto; }

        .page-title { height: 8mm; line-height: 8mm; color: #6b7280; font-size: 3mm; font-weight: 800; text-align: center; }

        .cards-grid { width: 100%; border-collapse: separate; border-spacing: 4mm 4mm; table-layout: fixed; }
        .cards-grid td { width: 86mm; height: 54mm; padding: 0; vertical-align: top; }

        .sheet-card {
            width: 86mm; height: 54mm; position: relative; overflow: hidden;
            border: .55mm solid var(--primary); border-radius: 2.2mm;
            background: var(--bg); box-sizing: border-box;
        }
        .sheet-card::before {
            content: ""; position: absolute; right: -16mm; top: -18mm;
            width: 48mm; height: 48mm; border-radius: 50%; background: rgba(212, 175, 55, .13);
        }
        .sheet-card::after {
            content: ""; position: absolute; left: -18mm; bottom: -20mm;
            width: 50mm; height: 50mm; border-radius: 50%; background: rgba(15, 81, 50, .09);
        }
        .sheet-card > * { position: absolute; z-index: 1; }

        .sheet-ministry { left: 3mm; top: 2mm; width: 56mm; font-size: 1.55mm; line-height: 1.08; font-weight: 900; }
        .sheet-republic { right: 3mm; top: 2mm; width: 23mm; text-align: center; font-size: 1.65mm; line-height: 1.08; font-weight: 900; }
        .sheet-flag { width: 10mm; height: 5.8mm; margin: .8mm auto 0; border: .18mm solid rgba(17, 24, 39, .2); }
        .sheet-flag i { display: block; float: left; width: 33.33%; height: 100%; }
        .sheet-flag i:nth-child(1) { background: #14b53a; }
        .sheet-flag i:nth-child(2) { background: #fcd116; }
        .sheet-flag i:nth-child(3) { background: #ce1126; }

        .sheet-title { left: 3mm; top: 11mm; width: 62mm; color: var(--primary); font-size: 2.45mm; line-height: 1.08; font-weight: 900; overflow-wrap: break-word; }

        .sheet-photo {
            left: 3mm; top: 22.5mm; width: 15mm; height: 19mm; border: .3mm solid #1f2937;
            border-radius: 1.4mm; overflow: hidden; background: #e5e7eb; text-align: center;
            line-height: 19mm; color: #6b7280; font-size: 4mm; font-weight: 900;
        }
        .sheet-photo img { width: 100%; height: 100%; object-fit: cover; display: block; }

        .sheet-name { left: 23mm; top: 22.5mm; width: 58mm; color: var(--primary); font-size: 2mm; line-height: 1.05; font-weight: 900; }

        .sheet-fields { left: 23mm; top: 26mm; width: 58mm; }
        .sheet-fields div {
            position: relative; min-height: 2.55mm; border-bottom: .15mm solid rgba(17, 24, 39, .15);
            line-height: 1.05; font-size: 1.55mm; clear: both;
        }
        .sheet-fields strong { display: block; float: left; width: 13mm; color: #4b5563; font-weight: 900; }
        .sheet-fields span { display: block; margin-left: 13.5mm; font-weight: 800; overflow-wrap: break-word; word-break: break-word; }

        .sheet-signature { left: 3mm; right: 3mm; bottom: 1.5mm; height: 7.3mm; color: #4b5563; text-align: center; font-size: 1.35mm; font-weight: 900; }
        .sheet-signature img { max-height: 5mm; max-width: 34mm; object-fit: contain; display: block; margin: 0 auto .3mm; }
        .sheet-signature span { display: block; line-height: 1.05; text-transform: uppercase; }

        .sheet-grade-title {
            left: 3mm; right: 3mm; top: 2.2mm; color: #374151; text-align: center;
            font-family: Georgia, "Times New Roman", serif; font-size: 4.7mm; font-weight: 900;
        }
        .sheet-grade-table-wrap { left: 3mm; right: 3mm; top: 9.6mm; bottom: 10.5mm; }
        .sheet-grade-table {
            width: 100%; height: 100%; border-collapse: collapse; table-layout: fixed;
            background: rgba(255, 255, 255, .62); color: #111827; font-family: Georgia, "Times New Roman", serif;
        }
        .sheet-grade-table th, .sheet-grade-table td {
            border: .22mm solid rgba(31, 41, 55, .82); padding: .25mm .45mm; line-height: 1.02; vertical-align: middle;
        }
        .sheet-grade-table th { text-align: center; font-size: 1.42mm; font-weight: 900; }
        .sheet-grade-table td { height: 2.85mm; font-size: 1.68mm; font-weight: 800; }
        .sheet-grade-table th:nth-child(1), .sheet-grade-table td:nth-child(1) { width: 24%; }
        .sheet-grade-table th:nth-child(2), .sheet-grade-table td:nth-child(2) { width: 25%; }
        .sheet-grade-table th:nth-child(3), .sheet-grade-table td:nth-child(3) { width: 51%; }
        .sheet-grade-sig { display: flex; flex-direction: column; align-items: center; line-height: 1; }
        .sheet-grade-sig img { max-height: 2.3mm; max-width: 22mm; object-fit: contain; display: block; }
        .sheet-grade-sig span { font-size: 1.15mm; font-weight: 800; text-transform: uppercase; }

        .empty-sheet { padding-top: 80mm; text-align: center; color: #6b7280; font-weight: 800; }

        @media print {
            @page { size: A4 portrait; margin: 8mm; }
            body { margin: 0; background: #fff; }
            .page-title { color: #9ca3af; }
        }
        @media screen and (max-width: 767.98px) {
            .sheet-document { width: 194mm; max-width: none; zoom: .48; margin-left: auto; margin-right: auto; }
        }
        @media screen and (max-width: 380px) { .sheet-document { zoom: .42; } }
    </style>
</head>
@php
    $chunks = collect($cards)->chunk(8);
@endphp
<body>
    <div class="sheet-actions">
        <button type="button" onclick="window.print()">🖨️ Imprimer la planche</button>
        <button type="button" class="btn-close" onclick="window.close()">✖ Fermer</button>
    </div>

    <div class="sheet-document template-classic" style="--primary: #0f5132; --secondary: #d4af37; --bg: #f8fafc;">
        @forelse($chunks as $chunkIndex => $chunk)
            {{-- RECTO --}}
            <section class="print-page">
                <div class="page-title">RECTO - Planche {{ $chunkIndex + 1 }}</div>
                <table class="cards-grid">
                    @foreach($chunk->chunk(2) as $row)
                        <tr>
                            @foreach($row as $c)
                                <td>
                                    <div class="sheet-card sheet-card-front">
                                        <div class="sheet-ministry">{{ mb_strtoupper($official['ministry']) }}</div>
                                        <div class="sheet-republic">
                                            RÉPUBLIQUE DU MALI
                                            <div>Un Peuple – Un But – Une Foi</div>
                                            <div class="sheet-flag"><i></i><i></i><i></i></div>
                                        </div>
                                        <div class="sheet-title">{{ mb_strtoupper($c['federation']) }}<br>{{ mb_strtoupper($c['ligue']) }}<br>{{ $c['license_label'] }}</div>
                                        <div class="sheet-photo">
                                            @if($c['photo'])
                                                <img src="{{ $c['photo'] }}" alt="Photo {{ $c['full_name'] }}">
                                            @else
                                                <span>{{ mb_strtoupper(mb_substr($c['prenom'], 0, 1) . mb_substr($c['nom'], 0, 1)) }}</span>
                                            @endif
                                        </div>
                                        <div class="sheet-name">{{ mb_strtoupper($c['nom']) }} {{ $c['prenom'] }}</div>
                                        <div class="sheet-fields">
                                            <div><strong>Nom</strong><span>{{ mb_strtoupper($c['nom']) }}</span></div>
                                            <div><strong>Prénom</strong><span>{{ $c['prenom'] }}</span></div>
                                            <div><strong>Grade</strong><span>{{ $c['grade'] ?: '-' }}</span></div>
                                            <div><strong>Sexe</strong><span>{{ $c['gender'] ?: '-' }}</span></div>
                                            <div><strong>Naissance</strong><span>{{ $c['birth_date'] ?: '-' }}</span></div>
                                            <div><strong>Lieu</strong><span>{{ $c['birth_place'] ?: '-' }}</span></div>
                                            <div><strong>Section</strong><span>{{ $c['ligue'] ?: '-' }}</span></div>
                                            <div><strong>Salle</strong><span>{{ $c['salle'] ?: '-' }}</span></div>
                                            <div><strong>Tél.</strong><span>{{ $c['phone'] ?: '-' }}</span></div>
                                        </div>
                                    </div>
                                </td>
                            @endforeach
                            @for($i = $row->count(); $i < 2; $i++)<td></td>@endfor
                        </tr>
                    @endforeach
                </table>
            </section>

            {{-- VERSO --}}
            <section class="print-page">
                <div class="page-title">VERSO - Planche {{ $chunkIndex + 1 }}</div>
                <table class="cards-grid">
                    @foreach($chunk->chunk(2) as $row)
                        <tr>
                            @foreach($row as $c)
                                <td>
                                    <div class="sheet-card sheet-card-back">
                                        <div class="sheet-grade-title">GRADES</div>
                                        <div class="sheet-grade-table-wrap">
                                            <table class="sheet-grade-table">
                                                <thead>
                                                    <tr>
                                                        <th>Grade</th>
                                                        <th>Date d'obtention du Grade</th>
                                                        <th>Signature du Maître de salle</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($c['grade_rows'] as $gradeRow)
                                                        <tr>
                                                            <td>{{ $gradeRow['label'] }}</td>
                                                            <td>{{ $gradeRow['date'] }}</td>
                                                            <td>
                                                                @if($gradeRow['date'])
                                                                    <div class="sheet-grade-sig">
                                                                        @if(!empty($gradeRow['signature']))
                                                                            <img src="{{ $gradeRow['signature'] }}" alt="Signature">
                                                                        @endif
                                                                        <span>{{ $gradeRow['signer_name'] }}{{ $gradeRow['signer_grade'] ? ' — ' . $gradeRow['signer_grade'] : '' }}</span>
                                                                    </div>
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                        <div class="sheet-signature">
                                            @if(!empty($c['signature']))<img src="{{ $c['signature'] }}" alt="Signature">@endif
                                            <span>{{ mb_strtoupper($c['signer']) }}</span>
                                        </div>
                                    </div>
                                </td>
                            @endforeach
                            @for($i = $row->count(); $i < 2; $i++)<td></td>@endfor
                        </tr>
                    @endforeach
                </table>
            </section>
        @empty
            <section class="print-page"><div class="empty-sheet">Aucune carte à imprimer.</div></section>
        @endforelse
    </div>

    <script>
        window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 400); });
    </script>
</body>
</html>
