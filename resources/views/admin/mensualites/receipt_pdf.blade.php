<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; font-size: 12px; }
        h1 { font-size: 16px; margin: 0 0 2px; }
        .muted { color: #6b7280; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; margin-top: 14px; }
        td { padding: 6px 4px; border-bottom: 1px solid #eef1f6; }
        td.val { text-align: right; font-weight: bold; }
        td.label { color: #6b7280; }
    </style>
</head>
<body>
    <h1>{{ __('messages.app_name') }}</h1>
    <div class="muted">{{ __('messages.cotisations.receipt') }} N° {{ str_pad($cotisation->id, 6, '0', STR_PAD_LEFT) }} — {{ now()->format('d/m/Y') }}</div>

    <table>
        <tr><td class="label">{{ __('messages.full_name') }}</td><td class="val">{{ $cotisation->disciple?->full_name }}</td></tr>
        <tr><td class="label">{{ __('messages.salle') }}</td><td class="val">{{ $cotisation->disciple?->salle?->nom ?? '-' }}</td></tr>
        <tr><td class="label">{{ __('messages.cotisations.period') }}</td><td class="val">{{ $cotisation->moisLabel() }} {{ $cotisation->annee }}</td></tr>
        <tr><td class="label">{{ __('messages.cotisations.amount') }}</td><td class="val">{{ number_format($cotisation->montant, 0, ',', ' ') }} FCFA</td></tr>
        <tr><td class="label">{{ __('messages.cotisations.paid_amount') }}</td><td class="val">{{ number_format($cotisation->montant_paye, 0, ',', ' ') }} FCFA</td></tr>
        <tr><td class="label">{{ __('messages.cotisations.remaining') }}</td><td class="val">{{ number_format($cotisation->reste_a_payer, 0, ',', ' ') }} FCFA</td></tr>
        <tr><td class="label">{{ __('messages.status') }}</td><td class="val">{{ $cotisation->statut }}</td></tr>
    </table>
</body>
</html>
