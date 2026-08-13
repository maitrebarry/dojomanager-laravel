<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Reçu cotisation #{{ $cotisation->id }}</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, sans-serif; color: #1f2937; padding: 30px; }
        .receipt { max-width: 520px; margin: 0 auto; border: 1px solid #d7dee9; border-radius: 10px; padding: 28px; }
        h1 { font-size: 20px; margin: 0 0 4px; }
        .muted { color: #6b7280; font-size: 13px; }
        table { width: 100%; border-collapse: collapse; margin-top: 18px; }
        td { padding: 8px 4px; border-bottom: 1px solid #eef1f6; font-size: 14px; }
        td.label { color: #6b7280; }
        td.val { text-align: right; font-weight: 600; }
        .total { font-size: 16px; }
        .badge { display: inline-block; padding: 3px 10px; border-radius: 20px; color: #fff; font-size: 12px; }
        .paid { background: #198754; } .partial { background: #ffc107; color:#000;} .unpaid { background: #dc3545; }
        .actions { text-align: center; margin-top: 20px; }
        .btn { display: inline-block; padding: 8px 16px; border-radius: 6px; text-decoration: none; border: 1px solid #4060a0; color: #4060a0; }
        @media print { .actions { display: none; } body { padding: 0; } }
    </style>
</head>
<body>
    <div class="receipt">
        <h1>{{ __('messages.app_name') }}</h1>
        <div class="muted">{{ __('messages.cotisations.receipt') }} N° {{ str_pad($cotisation->id, 6, '0', STR_PAD_LEFT) }}</div>

        <table>
            <tr><td class="label">{{ __('messages.full_name') }}</td><td class="val">{{ $cotisation->disciple?->full_name }}</td></tr>
            <tr><td class="label">{{ __('messages.salle') }}</td><td class="val">{{ $cotisation->disciple?->salle?->nom ?? '-' }}</td></tr>
            <tr><td class="label">{{ __('messages.cotisations.period') }}</td><td class="val">{{ $cotisation->moisLabel() }} {{ $cotisation->annee }}</td></tr>
            <tr><td class="label">{{ __('messages.cotisations.amount') }}</td><td class="val">{{ number_format($cotisation->montant, 0, ',', ' ') }} FCFA</td></tr>
            <tr><td class="label">{{ __('messages.cotisations.paid_amount') }}</td><td class="val">{{ number_format($cotisation->montant_paye, 0, ',', ' ') }} FCFA</td></tr>
            <tr><td class="label total">{{ __('messages.cotisations.remaining') }}</td><td class="val total">{{ number_format($cotisation->reste_a_payer, 0, ',', ' ') }} FCFA</td></tr>
            <tr>
                <td class="label">{{ __('messages.status') }}</td>
                <td class="val">
                    <span class="badge {{ $cotisation->statut === 'PAYE' ? 'paid' : ($cotisation->statut === 'PARTIEL' ? 'partial' : 'unpaid') }}">{{ $cotisation->statut }}</span>
                </td>
            </tr>
        </table>

        @if($cotisation->paiements->count())
            <div class="muted" style="margin-top:18px;">{{ __('messages.cotisations.payments_history') }}</div>
            <table>
                @foreach($cotisation->paiements as $p)
                    <tr>
                        <td class="label">{{ optional($p->date_paiement)->format('d/m/Y') }} · {{ $p->mode_paiement }}</td>
                        <td class="val">{{ number_format($p->montant, 0, ',', ' ') }} FCFA</td>
                    </tr>
                @endforeach
            </table>
        @endif

        <div class="actions">
            <a href="#" class="btn" onclick="window.print(); return false;">{{ __('messages.print') }}</a>
            <a href="{{ route('admin.mensualites.receipt.pdf', $cotisation) }}" class="btn">PDF</a>
        </div>
    </div>
</body>
</html>
