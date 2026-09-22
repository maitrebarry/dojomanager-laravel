<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('messages.cotisations.receipt') }} — {{ $cotisation->disciple?->full_name }} — {{ $cotisation->moisLabel() }} {{ $cotisation->annee }}</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
    <style>
        /* Même habillage que le PDF thermique (receipt_pdf.blade.php) : ce qui est
           affiché ici doit ressembler à ce qui sort de l'imprimante/du PDF, pas à une
           carte web à part — cf. le ticket.php de référence (Projets_licence). */
        :root { --ink: #152645; }
        * { box-sizing: border-box; }
        body {
            margin: 0; padding: 24px 16px 50px;
            background: #eef1f6;
            font-family: 'DejaVu Sans', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #1f2937;
            min-height: 100vh;
        }
        .topbar { max-width: 302px; margin: 0 auto 14px; }
        .topbar a { color: var(--ink); text-decoration: none; font-weight: 600; font-size: 14px; }
        .topbar a:hover { text-decoration: underline; }

        .ticket-shell { max-width: 302px; margin: 0 auto; padding: 0 0 20px; }

        .ticket {
            background: #fff;
            border: 1px solid #d9dee6;
            padding: 14px 14px 10px;
            font-size: 13px;
        }

        .t-header { text-align: center; margin-bottom: 4px; }
        .t-header h1 {
            margin: 1px 0; font-size: 17px; font-weight: bold; letter-spacing: .3px;
            color: var(--ink); text-transform: uppercase;
        }
        .t-header .sub { display: block; font-size: 11px; color: #4b5563; }

        .t-badge {
            text-align: center; font-size: 14px; font-weight: bold;
            text-transform: uppercase; letter-spacing: .5px; margin: 6px 0 3px; color: var(--ink);
        }
        .t-number { text-align: center; font-size: 11px; color: #6b7280; margin-bottom: 4px; }

        .t-dashed { border-top: 1.5px dashed #9aa3b2; margin: 6px 0; }

        table.t-info { width: 100%; border-collapse: collapse; }
        table.t-info td { padding: 3px 0; font-size: 12.5px; vertical-align: top; }
        table.t-info td.l { color: #6b7280; white-space: nowrap; padding-right: 8px; }
        table.t-info td.v { text-align: right; font-weight: bold; color: var(--ink); }
        table.t-info tr.total td { font-size: 14px; padding-top: 6px; }
        table.t-info tr.total td.v { color: #15803d; }

        .t-status {
            display: inline-block; float: right; padding: 3px 10px; border-radius: 12px;
            color: #fff; font-size: 11px; font-weight: bold;
        }
        .t-status.paid { background: #198754; } .t-status.partial { background: #d97706; } .t-status.unpaid { background: #dc3545; }

        .t-hist { margin-top: 4px; }
        .t-hist .hist-title { font-size: 10.5px; text-transform: uppercase; letter-spacing: .4px; color: #6b7280; margin-bottom: 2px; }
        table.t-hist-table { width: 100%; border-collapse: collapse; font-size: 11.5px; }
        table.t-hist-table td { padding: 2px 0; }
        table.t-hist-table td.v { text-align: right; font-weight: bold; color: var(--ink); }

        .t-footer { text-align: center; margin-top: 6px; padding-top: 4px; font-size: 10px; color: #6b7280; line-height: 1.5; }
        .t-footer .thanks { font-weight: bold; color: var(--ink); font-size: 11.5px; margin-bottom: 2px; }
        .t-footer .meta { font-size: 10px; color: #6b7280; }

        .t-signature { text-align: center; margin: 4px 0; }
        .t-signature .sig-label { font-size: 10.5px; color: #6b7280; text-transform: uppercase; letter-spacing: .4px; margin-bottom: 4px; }
        .t-signature .sig-img { max-height: 42px; max-width: 160px; object-fit: contain; }
        .t-signature .sig-line { height: 32px; border-bottom: 1.5px solid #9aa3b2; width: 160px; margin: 0 auto; }
        .t-signature .sig-name { font-size: 11px; font-weight: bold; color: var(--ink); margin-top: 4px; text-transform: uppercase; }
        .t-signature .sig-grade { font-size: 10px; color: #6b7280; }

        .actions {
            max-width: 302px; margin: 14px auto 0; display: flex; flex-wrap: wrap; gap: 10px; justify-content: center;
        }
        .btn {
            border: none; border-radius: 8px; padding: 11px 16px; font-size: 13.5px; font-weight: 600;
            cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 7px;
        }
        .btn-print { background: #fff; color: var(--ink); border: 1px solid #d9dee6; }
        .btn-pdf { background: #fff; color: var(--ink); border: 1px solid #d9dee6; }
        .btn-whatsapp { background: #25d366; color: #fff; }
        .btn-gear { background: #fff; color: #6b7280; border: 1px solid #d9dee6; padding: 11px 13px; }
        .btn:disabled { opacity: .65; cursor: wait; }

        .wa-status {
            max-width: 302px; margin: 0 auto 12px; text-align: center; font-size: 12.5px;
            font-weight: 600; padding: 7px 10px; border-radius: 8px;
        }
        .wa-status-ok { background: #dcfce7; color: #15803d; }
        .wa-status-error { background: #fee2e2; color: #b91c1c; }

        @media print {
            body { background: #fff; padding: 0; }
            .topbar, .actions, .wa-status { display: none; }
            .ticket-shell { padding: 0; max-width: 100%; }
            .ticket { border: none; margin: 0 auto; max-width: 302px; }
        }
    </style>
</head>
<body>

    <div class="topbar">
        <a href="{{ route('admin.mensualites.index') }}"><i class="fas fa-arrow-left"></i> {{ __('messages.back') }}</a>
    </div>

    <div class="ticket-shell">
        <div class="ticket" id="ticketCapture"
            data-whatsapp-phone="{{ \App\Support\WhatsAppPhone::normalize($cotisation->disciple?->telephone) }}"
            data-whatsapp-caption="{{ __('messages.whatsapp.share_text', ['name' => $cotisation->disciple?->full_name ?? '']) }}">
            <div class="t-header">
                <h1>{{ $cotisation->disciple?->salle?->nom ?? __('messages.app_name') }}</h1>
                @if($cotisation->disciple?->salle?->adresse)
                    <div class="sub">{{ $cotisation->disciple->salle->adresse }}</div>
                @endif
                @if($cotisation->disciple?->salle?->telephone)
                    <div class="sub">☎ {{ $cotisation->disciple->salle->telephone }}</div>
                @endif
            </div>

            <span class="t-badge">{{ __('messages.cotisations.receipt_doc_title') }}</span>
            <div class="t-number">{{ __('messages.cotisations.receipt') }} N° {{ str_pad($cotisation->id, 6, '0', STR_PAD_LEFT) }}</div>

            <div class="t-dashed"></div>

            <table class="t-info">
                <tr><td class="l">{{ __('messages.full_name') }}</td><td class="v">{{ $cotisation->disciple?->full_name }}</td></tr>
                <tr><td class="l">{{ __('messages.salle') }}</td><td class="v">{{ $cotisation->disciple?->salle?->nom ?? '—' }}</td></tr>
                <tr><td class="l">{{ __('messages.cotisations.period') }}</td><td class="v">{{ $cotisation->moisLabel() }} {{ $cotisation->annee }}</td></tr>
                <tr><td class="l">{{ __('messages.cotisations.amount') }}</td><td class="v">{{ number_format($cotisation->montant, 0, ',', ' ') }} FCFA</td></tr>
                <tr><td class="l">{{ __('messages.cotisations.paid_amount') }}</td><td class="v">{{ number_format($cotisation->montant_paye, 0, ',', ' ') }} FCFA</td></tr>
                <tr class="total"><td class="l">{{ __('messages.cotisations.remaining') }}</td><td class="v">{{ number_format($cotisation->reste_a_payer, 0, ',', ' ') }} FCFA</td></tr>
            </table>

            <div style="clear:both"></div>
            <div style="text-align:center; margin: 8px 0 2px;">
                <span class="t-status {{ $cotisation->statut === 'PAYE' ? 'paid' : ($cotisation->statut === 'PARTIEL' ? 'partial' : 'unpaid') }}">
                    {{ __('messages.cotisations.' . strtolower($cotisation->statut === 'PAYE' ? 'paid' : ($cotisation->statut === 'PARTIEL' ? 'partial' : 'unpaid'))) }}
                </span>
            </div>

            @if($cotisation->paiements->count())
                <div class="t-dashed"></div>
                <div class="t-hist">
                    <div class="hist-title">{{ __('messages.cotisations.payments_history') }}</div>
                    <table class="t-hist-table">
                        @foreach($cotisation->paiements as $p)
                            <tr>
                                <td>{{ optional($p->date_paiement)->format('d/m/Y') }} · {{ $p->mode_paiement }}</td>
                                <td class="v">{{ number_format($p->montant, 0, ',', ' ') }} FCFA</td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            @endif

            <div class="t-dashed"></div>

            @php
                $signerName = $signature?->master_name ?: ($cotisation->disciple?->salle?->maitre_display_name ?? '');
                $signerGrade = $signature?->master_grade ?: ($cotisation->disciple?->salle?->maitre_display_grade ?? '');
            @endphp
            <div class="t-signature">
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

            <div class="t-dashed"></div>

            <div class="t-footer">
                <div class="thanks">{{ __('messages.cotisations.receipt_welcome') }}</div>
                <div class="meta">{{ __('messages.cotisations.receipt_issued_by', ['name' => Auth::user()->name ?? '—', 'date' => now()->format('d/m/Y H:i')]) }}</div>
            </div>
        </div>
    </div>

    <div class="wa-status" id="waStatus" style="display:none;"></div>

    <div class="actions">
        <button type="button" class="btn btn-print" onclick="window.print()"><i class="fas fa-print"></i> {{ __('messages.print') }}</button>
        <a href="{{ route('admin.mensualites.receipt.pdf', $cotisation) }}" class="btn btn-pdf"><i class="fas fa-file-pdf"></i> {{ __('messages.download_pdf') }}</a>
        <button type="button" class="btn btn-whatsapp" id="btnWhatsapp"><i class="fab fa-whatsapp"></i> {{ __('messages.whatsapp.send') }}</button>
        <button type="button" class="btn btn-gear" id="btnBridgeConfig" title="{{ __('messages.whatsapp.configure') }}"><i class="fas fa-gear"></i></button>
    </div>

    <script>window.WHATSAPP_BRIDGE_CONFIG = @json(['host' => config('services.whatsapp_bridge.default_host'), 'token' => config('services.whatsapp_bridge.token')]);</script>
    <script src="{{ asset('js/whatsapp-bridge.js') }}"></script>
    <script>
        (function () {
            const ticket = document.getElementById('ticketCapture');
            const statusEl = document.getElementById('waStatus');
            const labels = {
                sending: @json(__('messages.whatsapp.sending')),
                sent: @json(__('messages.whatsapp.sent')),
                failed: @json(__('messages.whatsapp.auto_failed')),
                fallbackTitle: @json(__('messages.whatsapp.fallback_title')),
                fallbackText: @json(__('messages.whatsapp.fallback_text')),
                error: @json(__('messages.whatsapp.share_error')),
            };

            WhatsappBridge.autoSendIfRequested(ticket, statusEl, labels);

            WhatsappBridge.attachSendButton(document.getElementById('btnWhatsapp'), ticket, {
                labels: labels,
                shareTitle: @json(__('messages.whatsapp.share_text', ['name' => $cotisation->disciple?->full_name ?? ''])),
                phoneDigits: @json(\App\Support\WhatsAppPhone::normalize($cotisation->disciple?->telephone)),
                fileName: @json('recu-cotisation-' . $cotisation->id . '.png'),
            });

            document.getElementById('btnBridgeConfig').addEventListener('click', function () { WhatsappBridge.configure(); });
        })();
    </script>
</body>
</html>
