<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; }
        .frame { border: 3px double #152645; padding: 40px; text-align: center; }
        h1 { color: #152645; font-size: 26px; margin-bottom: 4px; letter-spacing: 1px; }
        .sub { color: #6b7280; margin-bottom: 30px; }
        .body { font-size: 15px; line-height: 1.9; margin: 20px 60px; }
        .name { font-size: 22px; font-weight: bold; color: #152645; }
        .grade { font-size: 20px; font-weight: bold; }
        .foot { margin-top: 50px; display: flex; justify-content: space-between; font-size: 13px; }
        .sign { width: 40%; text-align: center; }
        .line { border-top: 1px solid #333; margin-top: 45px; padding-top: 4px; }
    </style>
</head>
<body>
    <div class="frame">
        <h1>{{ __('messages.grade_passages.attestation_title') }}</h1>
        <div class="sub">{{ __('messages.app_name') }}</div>

        <div class="body">
            {{ __('messages.grade_passages.attestation_intro') }}<br><br>
            <span class="name">{{ $candidate->fullName() }}</span><br><br>
            {{ __('messages.grade_passages.attestation_body') }}<br>
            <span class="grade">{{ $candidate->proposed_grade_nom }}</span><br><br>
            {{ __('messages.grade_passages.session') }} : {{ $session->lieu }} — {{ $session->date_session?->format('d/m/Y') }}
            @if($candidate->note_globale !== null)<br>{{ __('messages.grade_passages.global_note') }} : {{ $candidate->note_globale }}/100 @endif
        </div>

        <div class="foot">
            <div class="sign"><div class="line">{{ __('messages.grade_passages.the_examiner') }}</div></div>
            <div class="sign"><div class="line">{{ __('messages.grade_passages.the_president') }}</div></div>
        </div>
    </div>
</body>
</html>
