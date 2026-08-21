@php
    use Illuminate\Support\Str;

    $formatDate = function ($date) {
        $value = $date ? \Illuminate\Support\Carbon::parse($date) : now();
        return ucfirst($value->locale('fr')->translatedFormat('d F Y'));
    };
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>{{ __('messages.disciple_grades.attestation_title') }}</title>
    <style>
        @page {
            margin: 0;
            size: A4 landscape;
        }

        * {
            box-sizing: border-box;
        }

        body { margin: 0; color: #151d4f; font-family: DejaVu Serif, Times New Roman, serif; background: #ffffff; }

        .attestation-page { width: 296mm; height: 209mm; margin: 0; padding: 0; background: #ffffff; position: relative; overflow: hidden; }
        .attestation-page.has-page-break { page-break-after: always; }

        .outer-frame { position: absolute; inset: 7mm; border: 2px solid #df1f32; }
        .inner-frame { position: absolute; inset: 10mm; border: 1.5px solid #151d4f; }
        .flag-corner { position: absolute; width: 28mm; height: 13mm; z-index: 2; }
        .flag-corner.top-left { left: 7mm; top: 7mm; }
        .flag-corner.bottom-right { right: 7mm; bottom: 7mm; }
        .flag-band { height: 4.35mm; }
        .green { background: #0a8f3c; }
        .gold { background: #ffd321; }
        .red { background: #d91f32; }

        .content { position: absolute; z-index: 1; top: 0; right: 0; bottom: 0; left: 0; padding: 26mm 19mm 0; }
        .reference-block { position: absolute; top: 39mm; right: 30mm; font-family: DejaVu Sans, Arial, sans-serif; color: #151d4f; font-size: 9px; line-height: 1.8; }
        .reference-block strong { display: inline-block; min-width: 20mm; font-family: DejaVu Serif, Times New Roman, serif; }

        .title { width: 220mm; margin: 0 auto; padding-top: 3mm; text-align: center; text-transform: uppercase; }
        .title h1 { margin: 0; color: #151d4f; font-size: 34px; line-height: 1.18; font-weight: 800; letter-spacing: 2px; }
        .intro { margin-top: 18mm; text-align: center; color: #30384b; font-family: DejaVu Sans, Arial, sans-serif; font-size: 16px; }
        .holder-name { margin: 6mm auto 0; max-width: 178mm; padding-bottom: 4mm; border-bottom: 1px solid #d9a21a; text-align: center; color: #151d4f; font-size: 28px; font-weight: 800; }
        .grade-line { margin: 8mm auto 0; max-width: 190mm; text-align: center; color: #30384b; font-family: DejaVu Sans, Arial, sans-serif; font-size: 16px; line-height: 1.55; }
        .grade-line strong { color: #df1f32; font-family: DejaVu Serif, Times New Roman, serif; font-size: 20px; }
        .details { width: 200mm; margin: 8mm auto 0; border-collapse: collapse; table-layout: fixed; font-family: DejaVu Sans, Arial, sans-serif; border: 1px solid #d4dceb; }
        .details th { color: #65718a; font-size: 8px; text-align: left; text-transform: uppercase; font-weight: 500; border-right: 1px solid #d4dceb; padding: 4mm 3mm 1mm; }
        .details td { color: #151d4f; font-size: 12px; font-weight: 800; text-transform: uppercase; border-right: 1px solid #d4dceb; padding: 0 3mm 4mm; }
        .details th:last-child, .details td:last-child { border-right: 0; }
        .legal { margin-top: 7mm; text-align: center; color: #30384b; font-family: DejaVu Sans, Arial, sans-serif; font-size: 15px; }
        .date-block { position: absolute; left: 48mm; bottom: 22mm; color: #151d4f; font-size: 13px; font-weight: 800; text-transform: uppercase; line-height: 1.8; }
        .signature-block { position: absolute; right: 45mm; bottom: 15mm; width: 48mm; text-align: center; }
        .signature-title { font-size: 12px; font-weight: 800; text-transform: uppercase; }
        .signature-space { height: 19mm; margin: 1mm auto; }
        .signature-space img { max-height: 19mm; max-width: 48mm; }
        .signature-line { border-top: 1px solid #151d4f; margin-top: 0; }
        .signature-name { margin-top: 2mm; font-size: 11px; font-weight: 800; text-transform: uppercase; }
        .signature-grade { font-size: 9px; font-weight: 400; text-transform: none; color: #65718a; }

        @media print {
            body {
                background: #ffffff;
            }
        }
    </style>
</head>
<body>
    @foreach($disciples as $disciple)
        @php
            $passageDate = $disciple->date_obtention_grade ?? $disciple->updated_at ?? now();
            $matricule = $disciple->nmle ?: $disciple->id;
            $reference = 'AT-PG-' . strtoupper((string) $matricule);
            $gender = $disciple->sexe === 'F' ? 'Féminin' : 'Masculin';
            $salleNom = $disciple->salle?->nom ?? __('messages.app_name');
            $signature = \App\Models\Signature::forSalle($disciple->salle_id);
            $signerName = $signature?->master_name ?: ($disciple->salle?->maitre_display_name ?? '');
            $signerGrade = $signature?->master_grade ?: ($disciple->salle?->maitre_display_grade ?? '');
        @endphp
        <section class="attestation-page {{ $loop->last ? '' : 'has-page-break' }}">
            <div class="outer-frame"></div>
            <div class="inner-frame"></div>
            <div class="flag-corner top-left">
                <div class="flag-band green"></div>
                <div class="flag-band gold"></div>
                <div class="flag-band red"></div>
            </div>
            <div class="flag-corner bottom-right">
                <div class="flag-band green"></div>
                <div class="flag-band gold"></div>
                <div class="flag-band red"></div>
            </div>
            <div class="content">
                <div class="reference-block">
                    <div><strong>Référence :</strong> {{ $reference }}</div>
                    <div><strong>Matricule :</strong> {{ $matricule }}</div>
                </div>

                <div class="title">
                    <h1>{{ __('messages.disciple_grades.attestation_title') }}</h1>
                </div>

                <div class="intro">{{ __('messages.disciple_grades.attestation_intro') }} :</div>
                <div class="holder-name">{{ $disciple->full_name }}</div>
                <div class="grade-line">
                    {{ __('messages.disciple_grades.attestation_body') }}
                    <strong>{{ $disciple->grade?->nom_grade ?? '-' }}</strong>@if($disciple->grade?->ceinture) ({{ $disciple->grade->ceinture }})@endif.
                </div>

                <table class="details">
                    <thead>
                        <tr>
                            <th>Matricule</th>
                            <th>{{ __('messages.salle') }}</th>
                            <th>{{ __('messages.grade_date') }}</th>
                            <th>{{ __('messages.gender') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>{{ $matricule }}</td>
                            <td>{{ $salleNom }}</td>
                            <td>{{ $formatDate($passageDate) }}</td>
                            <td>{{ $gender }}</td>
                        </tr>
                    </tbody>
                </table>

                <div class="legal">
                    En foi de quoi la présente attestation lui est délivrée pour servir et valoir ce que de droit.
                </div>

                <div class="signature-row">
                    <div class="date-block">
                        {{ mb_strtoupper($salleNom) }},<br>
                        LE {{ $formatDate($passageDate) }}
                    </div>
                    <div class="signature-block">
                        <div class="signature-title">{{ __('messages.master_signature') }}</div>
                        <div class="signature-space">
                            @if($signature?->signature_data)
                                <img src="{{ $signature->signature_data }}" alt="Signature">
                            @endif
                        </div>
                        <div class="signature-line"></div>
                        <div class="signature-name">{{ $signerName ?: '-' }}</div>
                        @if($signerGrade)<div class="signature-grade">{{ $signerGrade }}</div>@endif
                    </div>
                </div>
            </div>
        </section>
    @endforeach
</body>
</html>
