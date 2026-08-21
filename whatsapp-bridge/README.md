# Passerelle WhatsApp — DojoManager

Petit service local qui envoie automatiquement, sur WhatsApp, les reçus générés par
DojoManager (inscription d'un disciple, paiement d'une mensualité) — sans aucun clic
côté utilisateur du club.

## Pourquoi ce service existe

Il n'existe pas de moyen simple et gratuit pour un serveur PHP/Laravel de parler
directement au protocole WhatsApp. Ce service utilise
[Baileys](https://github.com/WhiskeySockets/Baileys), une implémentation communautaire
du protocole « WhatsApp Web multi-appareils » : on scanne un QR code une seule fois
(comme pour lier WhatsApp Web classique), puis ce service reste connecté et peut
envoyer des messages à la demande.

**⚠️ Important — ce n'est pas l'API officielle WhatsApp Business.** Le numéro utilisé
ici est un numéro WhatsApp classique « lié » comme un appareil supplémentaire.
WhatsApp peut, en théorie, restreindre un numéro dont l'usage paraît automatisé ou
trop massif. Pour un usage à très fort volume ou à fiabilité critique (grosse
structure, plusieurs milliers d'envois), il est préférable de migrer vers l'API Cloud
officielle de Meta (payante au-delà d'un quota gratuit, mais sans risque de blocage).
Pour l'usage d'un club (quelques dizaines/centaines de reçus), cette solution est
généralement fiable au quotidien.

## Installation

1. **Installer Node.js** (version 18 ou plus récente) si ce n'est pas déjà fait :
   https://nodejs.org (choisir la version LTS).

2. Dans ce dossier (`whatsapp-bridge/`) :

   ```bash
   npm install
   cp .env.example .env
   ```

3. Modifier `.env` :
   - `ALLOWED_ORIGINS` : l'URL exacte du site DojoManager (celle qu'on voit dans la
     barre d'adresse du navigateur), par exemple `http://localhost/DojoManager_laravel`.
   - `ALLOW_LAN=1` si DojoManager est aussi utilisé depuis un téléphone ou un autre
     ordinateur du même réseau Wi-Fi que ce poste (sinon laisser `0`).
   - `BRIDGE_TOKEN` : optionnel, mais recommandé si `ALLOW_LAN=1` — un mot de passe
     partagé qui empêche un autre appareil du Wi-Fi d'envoyer des messages via ce
     numéro. La **même** valeur doit être recopiée dans `WHATSAPP_BRIDGE_TOKEN` du
     `.env` principal de DojoManager (à la racine du dépôt Laravel).

4. Démarrer le service :

   ```bash
   npm start
   ```

5. Ouvrir **http://127.0.0.1:9300/** dans un navigateur (ou lire le QR code affiché
   dans le terminal). Sur le téléphone qui doit envoyer les reçus : **WhatsApp →
   Réglages → Appareils liés → Lier un appareil**, puis scanner le QR code.

6. Une fois connecté (la page affiche « Connectée ✅ »), retourner dans DojoManager :
   ajouter un disciple ou enregistrer un paiement de mensualité déclenche
   automatiquement l'envoi du reçu par WhatsApp, sans aucune autre action.

## Faire tourner ce service en permanence

Le service doit rester lancé pour que l'envoi automatique fonctionne (exactement comme
une imprimante doit rester allumée). Options :

- **Le plus simple** : laisser la fenêtre `npm start` ouverte sur le poste du club.
- **Windows** : exécuter `install-windows.ps1` (voir plus bas) pour un démarrage
  automatique à l'ouverture de session.
- **Linux/Mac** : utiliser [pm2](https://pm2.keymetrics.io/) (`npm install -g pm2 &&
  pm2 start server.js --name whatsapp-bridge && pm2 save && pm2 startup`) ou un
  service systemd.

La session WhatsApp est sauvegardée dans `auth_session/` (ne pas la partager — elle
donne un accès complet au compte WhatsApp lié). Pour changer de numéro ou si la
session expire, supprimer ce dossier et relancer le service pour re-scanner un QR
code.

## Dépannage

- **« Envoi WhatsApp automatique impossible » côté DojoManager** : le service n'est pas
  lancé, pas encore connecté (QR code pas scanné), ou l'adresse configurée dans le
  bouton ⚙ de la page reçu ne correspond pas à ce poste.
- **`GET /health`** renvoie `{"status":"ok","connected":true|false}` — pratique pour
  vérifier rapidement l'état depuis un navigateur ou `curl`.
- **CORS / « bloqué »** : vérifier que `ALLOWED_ORIGINS` contient bien l'URL exacte
  (avec le bon port) utilisée pour accéder à DojoManager.
