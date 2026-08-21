// Envoi des reçus (disciples / mensualités) vers la passerelle WhatsApp locale
// (cf. whatsapp-bridge/ à la racine du dépôt). Le site est servi par le serveur web
// du club ; il ne peut pas forcément atteindre lui-même la passerelle (poste dédié,
// éventuellement sur un autre appareil du même Wi-Fi). C'est donc le navigateur —
// via ce script — qui capture le reçu affiché à l'écran (html2canvas) et le transmet
// directement à la passerelle, qui seule sait parler à WhatsApp.
(function (window) {
    'use strict';

    var DEFAULT_HOST = (window.WHATSAPP_BRIDGE_CONFIG && window.WHATSAPP_BRIDGE_CONFIG.host) || '127.0.0.1:9300';
    var TOKEN = (window.WHATSAPP_BRIDGE_CONFIG && window.WHATSAPP_BRIDGE_CONFIG.token) || '';

    function getHost() {
        return localStorage.getItem('whatsappBridgeHost') || DEFAULT_HOST;
    }

    function setHost(host) {
        localStorage.setItem('whatsappBridgeHost', host);
    }

    function bridgeUrl(path) {
        return 'http://' + getHost() + path;
    }

    function configure(onSaved) {
        if (!window.Swal) {
            var v = window.prompt('Adresse de la passerelle WhatsApp (ex: 127.0.0.1:9300)', getHost());
            if (v) { setHost(v.trim()); if (onSaved) onSaved(); }
            return;
        }
        Swal.fire({
            title: 'Adresse de la passerelle WhatsApp',
            html: 'Sur le poste qui héberge la passerelle, laissez la valeur par défaut. ' +
                'Depuis un autre appareil du même Wi-Fi, indiquez l\'IP et le port de ce poste ' +
                '(ex : 192.168.1.50:9300).',
            input: 'text',
            inputValue: getHost(),
            showCancelButton: true,
            confirmButtonText: 'Enregistrer',
            cancelButtonText: 'Annuler',
        }).then(function (r) {
            if (r.isConfirmed && r.value) { setHost(r.value.trim()); if (onSaved) onSaved(); }
        });
    }

    function canvasToDataUrl(canvas) {
        return new Promise(function (resolve, reject) {
            canvas.toBlob(function (blob) {
                if (!blob) { reject(new Error('toBlob-failed')); return; }
                var reader = new FileReader();
                reader.onloadend = function () { resolve(reader.result); };
                reader.onerror = reject;
                reader.readAsDataURL(blob);
            }, 'image/png');
        });
    }

    function captureElement(el) {
        return window.html2canvas(el, { scale: 2, backgroundColor: '#fffdf8', useCORS: true })
            .then(canvasToDataUrl);
    }

    function sendToBridge(phone, imageDataUrl, caption) {
        var headers = { 'Content-Type': 'application/json' };
        if (TOKEN) headers['X-Bridge-Token'] = TOKEN;

        return fetch(bridgeUrl('/send-whatsapp'), {
            method: 'POST',
            headers: headers,
            body: JSON.stringify({ phone: phone, image: imageDataUrl, caption: caption }),
        }).then(function (response) {
            return response.json().catch(function () { return {}; }).then(function (json) {
                if (!response.ok || !json.success) {
                    throw new Error(json.message || 'send-failed');
                }
                return json;
            });
        });
    }

    /** Capture un ticket (#ticketCapture, data-whatsapp-phone/-caption) et l'envoie. */
    function captureAndSend(el) {
        var phone = el.getAttribute('data-whatsapp-phone') || '';
        var caption = el.getAttribute('data-whatsapp-caption') || '';
        if (!phone) { return Promise.reject(new Error('no-phone')); }

        return captureElement(el).then(function (dataUrl) {
            return sendToBridge(phone, dataUrl, caption);
        });
    }

    /**
     * Envoi en tâche de fond depuis UNE AUTRE page (ex : liste des mensualités juste
     * après l'enregistrement d'un paiement) : charge la page du reçu dans un iframe
     * caché (même origine via srcdoc), capture son ticket, l'envoie, puis nettoie —
     * sans quitter la page courante ni perturber le travail en cours.
     */
    function sendFromUrl(pageUrl) {
        return fetch(pageUrl, { credentials: 'same-origin' })
            .then(function (res) { return res.text(); })
            .then(function (html) {
                return new Promise(function (resolve, reject) {
                    var iframe = document.createElement('iframe');
                    iframe.style.cssText = 'position:absolute;left:-9999px;top:0;width:400px;height:900px;border:0;';
                    iframe.onload = function () {
                        // Laisse le temps aux images (photo du disciple) de finir de charger.
                        setTimeout(function () {
                            var doc = iframe.contentDocument;
                            var el = doc && doc.getElementById('ticketCapture');
                            if (!el) { iframe.remove(); reject(new Error('ticket-not-found')); return; }

                            captureAndSend(el)
                                .then(resolve)
                                .catch(reject)
                                .finally(function () { iframe.remove(); });
                        }, 350);
                    };
                    iframe.onerror = function () { iframe.remove(); reject(new Error('iframe-load-failed')); };
                    document.body.appendChild(iframe);
                    iframe.srcdoc = html;
                });
            });
    }

    /** Branche le bouton manuel « Envoyer par WhatsApp » : passerelle d'abord, repli Web Share / wa.me sinon. */
    function attachSendButton(btn, el, opts) {
        opts = opts || {};
        var labels = opts.labels || {};
        var original = btn.innerHTML;

        btn.addEventListener('click', function () {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> ' + (labels.sending || '…');

            captureAndSend(el).then(function () {
                if (window.Swal) {
                    Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: labels.sent || 'Envoyé', showConfirmButton: false, timer: 3000 });
                }
                btn.disabled = false; btn.innerHTML = original;
            }).catch(function () {
                // Passerelle injoignable ou pas de numéro : repli manuel (partage natif / wa.me).
                captureElement(el).then(function (dataUrl) {
                    var byteString = atob(dataUrl.split(',')[1]);
                    var arr = new Uint8Array(byteString.length);
                    for (var i = 0; i < byteString.length; i++) arr[i] = byteString.charCodeAt(i);
                    var blob = new Blob([arr], { type: 'image/png' });
                    var file = new File([blob], opts.fileName || 'recu.png', { type: 'image/png' });

                    if (navigator.canShare && navigator.canShare({ files: [file] })) {
                        return navigator.share({ files: [file], title: opts.shareTitle, text: opts.shareTitle }).catch(function () {});
                    }

                    var url = URL.createObjectURL(blob);
                    var link = document.createElement('a');
                    link.href = url; link.download = file.name; link.click();
                    URL.revokeObjectURL(url);

                    var waUrl = 'https://wa.me/' + (opts.phoneDigits || '') + '?text=' + encodeURIComponent(opts.shareTitle || '');
                    window.open(waUrl, '_blank');

                    if (window.Swal) {
                        Swal.fire({ icon: 'info', title: labels.fallbackTitle, text: labels.fallbackText });
                    }
                }).catch(function () {
                    if (window.Swal) Swal.fire({ icon: 'error', text: labels.error || 'Erreur' });
                }).finally(function () {
                    btn.disabled = false; btn.innerHTML = original;
                });
            });
        });
    }

    /** Déclenche l'envoi automatique si l'URL courante porte ?auto_whatsapp=1, puis nettoie l'URL. */
    function autoSendIfRequested(el, statusEl, labels) {
        var params = new URLSearchParams(window.location.search);
        if (params.get('auto_whatsapp') !== '1') return;

        params.delete('auto_whatsapp');
        var cleanUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
        window.history.replaceState({}, '', cleanUrl);

        if (!el.getAttribute('data-whatsapp-phone')) return;

        if (statusEl) { statusEl.style.display = ''; statusEl.textContent = labels.sending || '…'; }

        captureAndSend(el).then(function () {
            if (statusEl) { statusEl.textContent = labels.sent || 'Envoyé'; statusEl.className = 'wa-status wa-status-ok'; }
        }).catch(function () {
            if (statusEl) { statusEl.textContent = labels.failed || 'Échec'; statusEl.className = 'wa-status wa-status-error'; }
        });
    }

    window.WhatsappBridge = {
        getHost: getHost,
        setHost: setHost,
        configure: configure,
        captureAndSend: captureAndSend,
        sendFromUrl: sendFromUrl,
        attachSendButton: attachSendButton,
        autoSendIfRequested: autoSendIfRequested,
    };
})(window);
