<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('messages.disciples.receipt') }} — {{ $disciple->full_name }}</title>
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

        .t-avatar-wrap { display: flex; justify-content: center; margin-bottom: 8px; }
        .t-avatar {
            width: 58px; height: 58px; border-radius: 50%; object-fit: cover;
            border: 2px solid var(--ink);
        }
        .t-avatar-fallback {
            width: 58px; height: 58px; border-radius: 50%;
            background: var(--ink);
            color: #fff; font-weight: 700; font-size: 20px;
            display: flex; align-items: center; justify-content: center;
        }

        table.t-info { width: 100%; border-collapse: collapse; }
        table.t-info td { padding: 3px 0; font-size: 12.5px; vertical-align: top; }
        table.t-info td.l { color: #6b7280; white-space: nowrap; padding-right: 8px; }
        table.t-info td.v { text-align: right; font-weight: bold; color: var(--ink); }

        .t-stamp {
            text-align: center; margin: 6px 0 2px; font-weight: bold; font-size: 12px;
            color: #15803d; letter-spacing: .4px;
        }

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
        <a href="{{ route('admin.disciples.show', $disciple) }}"><i class="fas fa-arrow-left"></i> {{ __('messages.back') }}</a>
    </div>

    <div class="ticket-shell">
        <div class="ticket" id="ticketCapture"
            data-whatsapp-phone="{{ \App\Support\WhatsAppPhone::normalize($disciple->telephone) }}"
            data-whatsapp-caption="{{ __('messages.whatsapp.share_text', ['name' => $disciple->full_name]) }}">
            <div class="t-header">
                <h1>{{ $disciple->salle?->nom ?? __('messages.app_name') }}</h1>
                @if($disciple->salle?->adresse)
                    <div class="sub">{{ $disciple->salle->adresse }}</div>
                @endif
                @if($disciple->salle?->telephone)
                    <div class="sub">☎ {{ $disciple->salle->telephone }}</div>
                @endif
            </div>

            <span class="t-badge">{{ __('messages.disciples.receipt_doc_title') }}</span>
            <div class="t-number">{{ __('messages.disciples.receipt_number') }} : INSC-{{ str_pad($disciple->id, 6, '0', STR_PAD_LEFT) }}</div>

            <div class="t-dashed"></div>

            <div class="t-avatar-wrap">
                @if($disciple->photo_url)
                    <img src="{{ $disciple->photo_url }}" class="t-avatar" alt="">
                @else
                    <div class="t-avatar-fallback">{{ mb_strtoupper(mb_substr($disciple->prenom, 0, 1) . mb_substr($disciple->nom, 0, 1)) }}</div>
                @endif
            </div>

            <table class="t-info">
                <tr><td class="l">{{ __('messages.full_name') }}</td><td class="v">{{ $disciple->full_name }}</td></tr>
                <tr><td class="l">{{ __('messages.disciples.matricule') }}</td><td class="v">{{ $disciple->nmle ?: '—' }}</td></tr>
                <tr><td class="l">{{ __('messages.gender') }}</td><td class="v">{{ $disciple->sexe === 'F' ? __('messages.female') : ($disciple->sexe === 'M' ? __('messages.male') : '—') }}</td></tr>
                <tr><td class="l">{{ __('messages.birth_date') }}</td><td class="v">{{ optional($disciple->date_naissance)->format('d/m/Y') ?? '—' }}</td></tr>
                <tr><td class="l">{{ __('messages.grade') }}</td><td class="v">{{ $disciple->grade?->nom_grade ?? '—' }}</td></tr>
                <tr><td class="l">{{ __('messages.salle') }}</td><td class="v">{{ $disciple->salle?->nom ?? '—' }}</td></tr>
                <tr><td class="l">{{ __('messages.registration_date') }}</td><td class="v">{{ optional($disciple->date_inscription)->format('d/m/Y') ?? '—' }}</td></tr>
                @if($disciple->telephone)
                    <tr><td class="l">{{ __('messages.phone') }}</td><td class="v">{{ $disciple->telephone }}</td></tr>
                @endif
            </table>

            <div class="t-dashed"></div>
            <div class="t-stamp">✔ {{ __('messages.disciples.receipt_confirmed') }}</div>
            <div class="t-dashed"></div>

            @php
                $signerName = $signature?->master_name ?: ($disciple->salle?->maitre_display_name ?? '');
                $signerGrade = $signature?->master_grade ?: ($disciple->salle?->maitre_display_grade ?? '');
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
                <div class="thanks">{{ __('messages.disciples.receipt_welcome', ['salle' => $disciple->salle?->nom ?? __('messages.app_name')]) }}</div>
                <div class="meta">{{ __('messages.disciples.receipt_issued_by', ['name' => Auth::user()->name ?? '—', 'date' => now()->format('d/m/Y H:i')]) }}</div>
            </div>
        </div>
    </div>

    <div class="wa-status" id="waStatus" style="display:none;"></div>

    <div class="actions">
        <button type="button" class="btn btn-print" onclick="window.print()"><i class="fas fa-print"></i> {{ __('messages.print') }}</button>
        <a href="{{ route('admin.disciples.receipt.pdf', $disciple) }}" class="btn btn-pdf"><i class="fas fa-file-pdf"></i> {{ __('messages.download_pdf') }}</a>
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
                shareTitle: @json(__('messages.whatsapp.share_text', ['name' => $disciple->full_name])),
                phoneDigits: @json(\App\Support\WhatsAppPhone::normalize($disciple->telephone)),
                fileName: @json('recu-inscription-' . $disciple->id . '.png'),
            });

            document.getElementById('btnBridgeConfig').addEventListener('click', function () { WhatsappBridge.configure(); });
        })();
    </script>
</body>
</html>
