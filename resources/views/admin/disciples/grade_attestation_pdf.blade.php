<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>{{ __('messages.disciple_grades.attestation_title') }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; margin: 0; }
        .page { padding: 30px 50px; page-break-after: always; }
        .page:last-child { page-break-after: auto; }
        .frame { border: 3px double #152645; padding: 40px; text-align: center; }
        h1 { color: #152645; font-size: 26px; margin-bottom: 4px; letter-spacing: 1px; }
        .sub { color: #6b7280; margin-bottom: 30px; }
        .body { font-size: 15px; line-height: 1.9; margin: 20px 60px; }
        .name { font-size: 22px; font-weight: bold; color: #152645; }
        .grade { font-size: 20px; font-weight: bold; }
        .meta { margin-top: 18px; font-size: 12px; color: #6b7280; }
        .foot { margin-top: 50px; display: flex; justify-content: space-between; font-size: 13px; }
        .sign { width: 45%; text-align: center; }
        .sig-img { max-height: 42px; max-width: 160px; margin-bottom: 4px; }
        .sig-line { border-top: 1px solid #333; margin-top: 4px; padding-top: 4px; }
    </style>
</head>
<body>
@foreach($disciples as $disciple)
    @php
        $signature = \App\Models\Signature::forSalle($disciple->salle_id);
        $signerName = $signature?->master_name ?: ($disciple->salle?->maitre_display_name ?? '');
        $signerGrade = $signature?->master_grade ?: ($disciple->salle?->maitre_display_grade ?? '');
    @endphp
    <div class="page">
        <div class="frame">
            <h1>{{ __('messages.disciple_grades.attestation_title') }}</h1>
            <div class="sub">{{ $disciple->salle?->nom ?? __('messages.app_name') }}</div>

            <div class="body">
                {{ __('messages.disciple_grades.attestation_intro') }}<br><br>
                <span class="name">{{ $disciple->full_name }}</span><br><br>
                {{ __('messages.disciple_grades.attestation_body') }}<br>
                <span class="grade">{{ $disciple->grade?->nom_grade ?? '-' }} ({{ $disciple->grade?->ceinture }})</span>
            </div>

            <div class="meta">
                {{ __('messages.salle') }} : {{ $disciple->salle?->nom ?? '-' }}
                &nbsp;·&nbsp;
                {{ __('messages.grade_date') }} : {{ optional($disciple->date_obtention_grade)->format('d/m/Y') ?? now()->format('d/m/Y') }}
            </div>

            <div class="foot">
                <div class="sign"></div>
                <div class="sign">
                    @if($signature?->signature_data)
                        <img src="{{ $signature->signature_data }}" alt="" class="sig-img">
                    @endif
                    <div class="sig-line">
                        {{ __('messages.master_signature') }}<br>
                        <strong>{{ $signerName ?: '-' }}</strong>
                        @if($signerGrade)<br><span style="font-size:11px;color:#6b7280;">{{ $signerGrade }}</span>@endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endforeach
</body>
</html>
