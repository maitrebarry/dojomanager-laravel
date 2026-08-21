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

    table.infos { width: 100%; border-collapse: collapse; margin: 3px 0; }
    table.infos td { padding: 3px 0; vertical-align: top; font-size: 12.5px; }
    table.infos td.label { color: #6b7280; white-space: nowrap; padding-right: 6px; }
    table.infos td.value { text-align: right; font-weight: bold; color: #152645; }
    table.infos tr.total td { padding-top: 6px; font-size: 14px; }
    table.infos tr.total td.value { color: #15803d; }

    .status {
        text-align: center; margin: 6px 0; font-size: 11px; font-weight: bold; color: #fff;
        display: inline-block; padding: 3px 12px; border-radius: 12px;
    }
    .status-wrap { text-align: center; }
    .status.paid { background: #198754; } .status.partial { background: #d97706; } .status.unpaid { background: #dc3545; }

    .hist-title { font-size: 10.5px; text-transform: uppercase; letter-spacing: 0.4px; color: #9aa3b2; margin: 4px 0 2px; }

    .signature { text-align: center; margin: 4px 0; }
    .signature .sig-label { font-size: 9.5px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.4px; margin-bottom: 4px; }
    .signature .sig-img { max-height: 32px; max-width: 130px; }
    .signature .sig-line { height: 24px; border-bottom: 1.5px solid #9aa3b2; width: 130px; margin: 0 auto; }
    .signature .sig-name { font-size: 10px; font-weight: bold; color: #152645; margin-top: 3px; text-transform: uppercase; }
    .signature .sig-grade { font-size: 9px; color: #6b7280; }
    table.hist { width: 100%; border-collapse: collapse; font-size: 11.5px; }
    table.hist td { padding: 2px 0; }
    table.hist td.value { text-align: right; font-weight: bold; color: #152645; }

    footer { margin-top: 6px; padding-top: 4px; border-top: 1.5px dashed #9aa3b2; font-size: 10px; color: #6b7280; text-align: center; line-height: 1.5; }
    footer .thanks { font-size: 11.5px; color: #152645; font-weight: bold; margin-bottom: 2px; }
</style>
</head>
<body>
<?php date_default_timezone_set('Africa/Bamako'); ?>

<div class="ticket">
    <header>
        <h1>{{ $cotisation->disciple?->salle?->nom ?? __('messages.app_name') }}</h1>
        @if($cotisation->disciple?->salle?->adresse)
            <span class="sub">{{ $cotisation->disciple->salle->adresse }}</span>
        @endif
        @if($cotisation->disciple?->salle?->telephone)
            <span class="sub">Tel: {{ $cotisation->disciple->salle->telephone }}</span>
        @endif
    </header>

    <div class="doc-badge">{{ __('messages.cotisations.receipt_doc_title') }}</div>
    <div class="doc-number">{{ __('messages.cotisations.receipt') }} N° {{ str_pad($cotisation->id, 6, '0', STR_PAD_LEFT) }} — {{ now()->format('d/m/Y') }}</div>

    <hr>

    <table class="infos">
        <tr><td class="label">{{ __('messages.full_name') }}</td><td class="value">{{ $cotisation->disciple?->full_name }}</td></tr>
        <tr><td class="label">{{ __('messages.salle') }}</td><td class="value">{{ $cotisation->disciple?->salle?->nom ?? '-' }}</td></tr>
        <tr><td class="label">{{ __('messages.cotisations.period') }}</td><td class="value">{{ $cotisation->moisLabel() }} {{ $cotisation->annee }}</td></tr>
        <tr><td class="label">{{ __('messages.cotisations.amount') }}</td><td class="value">{{ number_format($cotisation->montant, 0, ',', ' ') }} FCFA</td></tr>
        <tr><td class="label">{{ __('messages.cotisations.paid_amount') }}</td><td class="value">{{ number_format($cotisation->montant_paye, 0, ',', ' ') }} FCFA</td></tr>
        <tr class="total"><td class="label">{{ __('messages.cotisations.remaining') }}</td><td class="value">{{ number_format($cotisation->reste_a_payer, 0, ',', ' ') }} FCFA</td></tr>
    </table>

    <div class="status-wrap">
        <span class="status {{ $cotisation->statut === 'PAYE' ? 'paid' : ($cotisation->statut === 'PARTIEL' ? 'partial' : 'unpaid') }}">
            {{ __('messages.cotisations.' . strtolower($cotisation->statut === 'PAYE' ? 'paid' : ($cotisation->statut === 'PARTIEL' ? 'partial' : 'unpaid'))) }}
        </span>
    </div>

    @if($cotisation->paiements->count())
        <hr>
        <div class="hist-title">{{ __('messages.cotisations.payments_history') }}</div>
        <table class="hist">
            @foreach($cotisation->paiements as $p)
                <tr>
                    <td>{{ optional($p->date_paiement)->format('d/m/Y') }} &middot; {{ $p->mode_paiement }}</td>
                    <td class="value">{{ number_format($p->montant, 0, ',', ' ') }} FCFA</td>
                </tr>
            @endforeach
        </table>
    @endif

    <hr>

    @php
        $signerName = $signature?->master_name ?: ($cotisation->disciple?->salle?->maitre_display_name ?? '');
        $signerGrade = $signature?->master_grade ?: ($cotisation->disciple?->salle?->maitre_display_grade ?? '');
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
        <div class="thanks">{{ __('messages.cotisations.receipt_welcome') }}</div>
        {{ __('messages.cotisations.receipt_issued_by', ['name' => auth()->user()->name ?? '-', 'date' => now()->format('d/m/Y H:i')]) }}
    </footer>
</div>

</body>
</html>
