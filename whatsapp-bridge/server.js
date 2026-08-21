/**
 * Passerelle WhatsApp locale — à lancer sur le poste du club qui doit envoyer les
 * reçus (disciples, mensualités).
 *
 * DojoManager (PHP/Laravel) n'a pas de moyen fiable, officiel et gratuit de parler
 * directement au protocole WhatsApp depuis le serveur web. Ce petit service Node.js
 * s'y connecte via Baileys (implémentation communautaire du protocole "WhatsApp Web
 * multi-appareils") en se faisant scanner une fois comme un "appareil lié", puis reste
 * en écoute en local : c'est le navigateur (cf. public/js/whatsapp-bridge.js), qui
 * capture le reçu affiché à l'écran, qui l'envoie ici pour transmission réelle.
 *
 * ATTENTION : Baileys n'est PAS l'API officielle WhatsApp Business. Le numéro utilisé
 * ici pourrait être restreint par WhatsApp en cas d'usage jugé abusif/massif. Pour un
 * usage à fort volume ou à fiabilité critique, préférer l'API Cloud officielle (Meta).
 *
 * Lancement : npm install && npm start (voir README.md)
 */

require('dotenv').config();

const path = require('path');
const http = require('http');
const pino = require('pino');
const QRCode = require('qrcode');
const qrcodeTerminal = require('qrcode-terminal');
const {
    default: makeWASocket,
    useMultiFileAuthState,
    DisconnectReason,
    fetchLatestBaileysVersion,
} = require('@whiskeysockets/baileys');

const PORT = parseInt(process.env.PORT || '9300', 10);
const HOST = process.env.ALLOW_LAN === '1' ? '0.0.0.0' : '127.0.0.1';
const BRIDGE_TOKEN = process.env.BRIDGE_TOKEN || '';
const ALLOWED_ORIGINS = (process.env.ALLOWED_ORIGINS || '')
    .split(',').map((s) => s.trim()).filter(Boolean);
const AUTH_DIR = path.join(__dirname, 'auth_session');
const MAX_BODY_BYTES = 15 * 1024 * 1024; // 15 Mo : une image de reçu ne dépasse jamais ça

const logger = pino({ level: process.env.LOG_LEVEL || 'silent' });

let sock = null;
let isConnected = false;
let lastQrDataUrl = null;

async function startSock() {
    const { state, saveCreds } = await useMultiFileAuthState(AUTH_DIR);
    const { version } = await fetchLatestBaileysVersion();

    sock = makeWASocket({
        version,
        logger,
        auth: state,
        browser: ['DojoManager', 'Chrome', '1.0'],
    });

    sock.ev.on('creds.update', saveCreds);

    sock.ev.on('connection.update', async (update) => {
        const { connection, lastDisconnect, qr } = update;

        if (qr) {
            lastQrDataUrl = await QRCode.toDataURL(qr);
            console.log('\n[whatsapp-bridge] Scannez ce QR code avec WhatsApp → Réglages → Appareils liés :\n');
            qrcodeTerminal.generate(qr, { small: true });
            console.log(`\n[whatsapp-bridge] Ou ouvrez http://${HOST}:${PORT}/ dans un navigateur pour le voir en grand.\n`);
        }

        if (connection === 'open') {
            isConnected = true;
            lastQrDataUrl = null;
            console.log('[whatsapp-bridge] Connectée à WhatsApp — prête à envoyer les reçus.');
        }

        if (connection === 'close') {
            isConnected = false;
            const statusCode = lastDisconnect?.error?.output?.statusCode;
            const shouldReconnect = statusCode !== DisconnectReason.loggedOut;
            console.log('[whatsapp-bridge] Connexion fermée (code ' + statusCode + ').', shouldReconnect ? 'Reconnexion…' : 'Session déconnectée.');
            if (shouldReconnect) {
                setTimeout(startSock, 3000);
            } else {
                console.log('[whatsapp-bridge] Supprimez le dossier auth_session/ puis relancez pour relier un nouvel appareil.');
            }
        }
    });
}

function normalizeJid(phone) {
    const digits = String(phone || '').replace(/\D+/g, '');
    if (!digits) return null;
    return digits + '@s.whatsapp.net';
}

function setCors(req, res) {
    const origin = req.headers.origin;
    if (origin && (ALLOWED_ORIGINS.length === 0 || ALLOWED_ORIGINS.includes(origin))) {
        res.setHeader('Access-Control-Allow-Origin', origin);
    }
    res.setHeader('Access-Control-Allow-Methods', 'POST, GET, OPTIONS');
    res.setHeader('Access-Control-Allow-Headers', 'Content-Type, X-Bridge-Token');
    // Chrome (Private Network Access) exige cet en-tête pour laisser un site (surtout en
    // HTTPS) appeler une adresse locale comme 127.0.0.1 — sans lui l'appel échoue
    // silencieusement côté navigateur même si la passerelle répond correctement en direct.
    res.setHeader('Access-Control-Allow-Private-Network', 'true');
}

function sendJson(res, statusCode, payload) {
    res.writeHead(statusCode, { 'Content-Type': 'application/json; charset=utf-8' });
    res.end(JSON.stringify(payload));
}

function readBody(req) {
    return new Promise((resolve, reject) => {
        let data = '';
        let bytes = 0;
        req.on('data', (chunk) => {
            bytes += chunk.length;
            if (bytes > MAX_BODY_BYTES) {
                reject(new Error('payload-too-large'));
                req.destroy();
                return;
            }
            data += chunk;
        });
        req.on('end', () => resolve(data));
        req.on('error', reject);
    });
}

function renderStatusPage() {
    const qrImg = lastQrDataUrl
        ? `<img src="${lastQrDataUrl}" alt="QR code" width="280" height="280">`
        : (isConnected ? '' : '<p>Génération du QR code…</p>');

    return `<!doctype html><html><head><meta charset="utf-8">
<title>Passerelle WhatsApp — DojoManager</title>
<meta http-equiv="refresh" content="5">
<style>body{font-family:system-ui,sans-serif;text-align:center;padding:40px;background:#f4f6fb;color:#1f2937}
h1{font-size:20px}
.status{display:inline-block;padding:6px 16px;border-radius:20px;font-weight:700;margin:10px 0}
.ok{background:#dcfce7;color:#15803d}.pending{background:#fef3c7;color:#92400e}
img{border-radius:8px;box-shadow:0 8px 24px rgba(0,0,0,.15);margin-top:10px}</style>
</head><body>
<h1>🥋 Passerelle WhatsApp — DojoManager</h1>
<div class="status ${isConnected ? 'ok' : 'pending'}">${isConnected ? 'Connectée ✅' : 'En attente de connexion…'}</div>
${qrImg}
${isConnected ? '<p>Les reçus DojoManager peuvent être envoyés automatiquement.</p>' : '<p>Ouvrez WhatsApp sur le téléphone qui doit envoyer les reçus → Réglages → Appareils liés → Lier un appareil, puis scannez ce code.</p>'}
</body></html>`;
}

const server = http.createServer(async (req, res) => {
    setCors(req, res);

    if (req.method === 'OPTIONS') {
        res.writeHead(204);
        res.end();
        return;
    }

    if (req.method === 'GET' && req.url === '/health') {
        sendJson(res, 200, { status: 'ok', connected: isConnected });
        return;
    }

    if (req.method === 'GET' && req.url === '/') {
        res.writeHead(200, { 'Content-Type': 'text/html; charset=utf-8' });
        res.end(renderStatusPage());
        return;
    }

    if (req.method === 'POST' && req.url === '/send-whatsapp') {
        if (BRIDGE_TOKEN && req.headers['x-bridge-token'] !== BRIDGE_TOKEN) {
            sendJson(res, 401, { success: false, message: 'Jeton invalide.' });
            return;
        }

        if (!isConnected || !sock) {
            sendJson(res, 503, { success: false, message: 'Passerelle non connectée à WhatsApp.' });
            return;
        }

        try {
            const raw = await readBody(req);
            const body = JSON.parse(raw || '{}');
            const jid = normalizeJid(body.phone);
            if (!jid) {
                sendJson(res, 422, { success: false, message: 'Numéro de téléphone manquant ou invalide.' });
                return;
            }

            const dataUrl = String(body.image || '');
            const base64 = dataUrl.includes(',') ? dataUrl.split(',')[1] : dataUrl;
            if (!base64) {
                sendJson(res, 422, { success: false, message: 'Image du reçu manquante.' });
                return;
            }

            await sock.sendMessage(jid, {
                image: Buffer.from(base64, 'base64'),
                caption: body.caption || '',
            });

            sendJson(res, 200, { success: true });
        } catch (e) {
            console.error('[whatsapp-bridge] Échec envoi:', e);
            sendJson(res, 500, { success: false, message: 'Échec de l\'envoi : ' + e.message });
        }
        return;
    }

    sendJson(res, 404, { success: false, message: 'Route inconnue.' });
});

server.listen(PORT, HOST, () => {
    console.log(`[whatsapp-bridge] En écoute sur http://${HOST}:${PORT}  (page de statut/QR : http://${HOST === '0.0.0.0' ? '127.0.0.1' : HOST}:${PORT}/)`);
});

startSock().catch((e) => {
    console.error('[whatsapp-bridge] Erreur au démarrage :', e);
    process.exit(1);
});
