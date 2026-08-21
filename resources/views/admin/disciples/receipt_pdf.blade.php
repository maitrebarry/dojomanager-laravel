<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<style>
    @page { margin: 2mm 3mm; }
    * { box-sizing: border-box; }

    body {
        font-family: DejaVu Sans, sans-serif;
        font-size: 13px;
        margin: 0; padding: 0;
        color: #1f2937;
    }

    .ticket { width: 100%; }

    header { text-align: center; margin-bottom: 4px; }

    h1 {
        margin: 1px 0; font-size: 17px; font-weight: bold;
        text-transform: uppercase; letter-spacing: 0.3px; color: #152645;
    }

    .sub { display: block; font-size: 11px; color: #4b5563; }

    hr { border: none; border-top: 1.5px dashed #9aa3b2; margin: 6px 0; }

    .doc-badge {
        text-align: center; font-size: 12px; font-weight: bold; color: #fff;
        background: #4060a0; text-transform: uppercase; letter-spacing: 0.5px;
        padding: 5px 8px; margin: 6px 0 3px; border-radius: 4px;
    }
    .doc-number { text-align: center; font-size: 11px; color: #6b7280; margin: 0 0 6px; }

    .avatar-wrap { text-align: center; margin: 4px 0 8px; }
    .avatar-wrap img {
        width: 58px; height: 58px; border-radius: 50%; object-fit: cover;
        border: 2px solid #4060a0;
    }

    table.infos { width: 100%; border-collapse: collapse; margin: 3px 0; }
    table.infos td { padding: 3px 0; vertical-align: top; font-size: 12.5px; }
    table.infos td.label { color: #6b7280; white-space: nowrap; padding-right: 6px; }
    table.infos td.value { text-align: right; font-weight: bold; color: #152645; }

    .stamp { text-align: center; font-weight: bold; font-size: 12px; color: #15803d; margin: 6px 0 2px; }

    .signature { text-align: center; margin: 4px 0; }
    .signature .sig-label { font-size: 9.5px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.4px; margin-bottom: 4px; }
    .signature .sig-img { max-height: 32px; max-width: 130px; }
    .signature .sig-line { height: 24px; border-bottom: 1.5px solid #9aa3b2; width: 130px; margin: 0 auto; }
    .signature .sig-name { font-size: 10px; font-weight: bold; color: #152645; margin-top: 3px; text-transform: uppercase; }
    .signature .sig-grade { font-size: 9px; color: #6b7280; }

    footer { margin-top: 6px; padding-top: 4px; border-top: 1.5px dashed #9aa3b2; font-size: 10px; color: #6b7280; text-align: center; line-height: 1.5; }
    footer .thanks { font-size: 11.5px; color: #152645; font-weight: bold; margin-bottom: 2px; }
</style>
</head>
<body>
<?php date_default_timezone_set('Africa/Bamako'); ?>

<div class="ticket">
    <header>
        <h1>{{ $disciple->salle?->nom ?? __('messages.app_name') }}</h1>
        @if($disciple->salle?->adresse)
            <span class="sub">{{ $disciple->salle->adresse }}</span>
        @endif
        @if($disciple->salle?->telephone)
            <span class="sub">Tel: {{ $disciple->salle->telephone }}</span>
        @endif
    </header>

    <div class="doc-badge">{{ __('messages.disciples.receipt_doc_title') }}</div>
    <div class="doc-number">{{ __('messages.disciples.receipt_number') }} : INSC-{{ str_pad($disciple->id, 6, '0', STR_PAD_LEFT) }}</div>

    <hr>

    @php
        $photoPath = null;
        if ($disciple->photo && \Illuminate\Support\Facades\Storage::disk('public')->exists($disciple->photo)) {
            $photoPath = \Illuminate\Support\Facades\Storage::disk('public')->path($disciple->photo);
        }
    @endphp
    @if($photoPath)
        <div class="avatar-wrap"><img src="file://{{ realpath($photoPath) }}" alt=""></div>
    @endif

    <table class="infos">
        <tr><td class="label">{{ __('messages.full_name') }}</td><td class="value">{{ $disciple->full_name }}</td></tr>
        <tr><td class="label">{{ __('messages.disciples.matricule') }}</td><td class="value">{{ $disciple->nmle ?: '-' }}</td></tr>
        <tr><td class="label">{{ __('messages.gender') }}</td><td class="value">{{ $disciple->sexe === 'F' ? __('messages.female') : ($disciple->sexe === 'M' ? __('messages.male') : '-') }}</td></tr>
        <tr><td class="label">{{ __('messages.birth_date') }}</td><td class="value">{{ optional($disciple->date_naissance)->format('d/m/Y') ?? '-' }}</td></tr>
        <tr><td class="label">{{ __('messages.grade') }}</td><td class="value">{{ $disciple->grade?->nom_grade ?? '-' }}</td></tr>
        <tr><td class="label">{{ __('messages.salle') }}</td><td class="value">{{ $disciple->salle?->nom ?? '-' }}</td></tr>
        <tr><td class="label">{{ __('messages.registration_date') }}</td><td class="value">{{ optional($disciple->date_inscription)->format('d/m/Y') ?? '-' }}</td></tr>
        @if($disciple->telephone)
            <tr><td class="label">{{ __('messages.phone') }}</td><td class="value">{{ $disciple->telephone }}</td></tr>
        @endif
    </table>

    <hr>
    <div class="stamp">&#10004; {{ __('messages.disciples.receipt_confirmed') }}</div>
    <hr>

    @php
        $signerName = $signature?->master_name ?: ($disciple->salle?->maitre_display_name ?? '');
        $signerGrade = $signature?->master_grade ?: ($disciple->salle?->maitre_display_grade ?? '');
    @endphp
    <div class="signature">
        <div class="sig-label">{{ __('messages.master_signature') }}</div>
        @if($signature?->signature_data)
            <img src="{{ $signature->signature_data }}" alt="" class="sig-img">
        @else
            <div class="sig-line"></div>
        @endif
        <div class="sig-name">{{ $signerName }}</div>
        @if($signerGrade)
            <div class="sig-grade">{{ $signerGrade }}</div>
        @endif
    </div>

    <hr>

    <footer>
        <div class="thanks">{{ __('messages.disciples.receipt_welcome', ['salle' => $disciple->salle?->nom ?? __('messages.app_name')]) }}</div>
        {{ __('messages.disciples.receipt_issued_by', ['name' => auth()->user()->name ?? '-', 'date' => now()->format('d/m/Y H:i')]) }}
    </footer>
</div>

</body>
</html>
