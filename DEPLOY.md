# Déploiement sur LWS (hébergement mutualisé)

Checklist pour mettre DojoManager en ligne chez LWS. À adapter selon le plan LWS
souscrit (accès SSH ou non).

## 1. Pré-requis côté LWS

- **PHP 8.3 ou plus récent**, sélectionné dans l'espace client LWS (Multisites →
  Version PHP). `composer.json` exige `^8.3` (Laravel 13).
- Extensions PHP nécessaires (généralement déjà actives chez LWS, à vérifier dans le
  panneau « Configurer PHP ») : `openssl`, `pdo_mysql`, `mbstring`, `tokenizer`, `xml`,
  `ctype`, `json`, `bcmath`, `fileinfo`, `gd` (utilisée par `endroid/qr-code` pour les
  QR codes des licences).
- Une base de données **MySQL** créée depuis l'espace client (nom de base,
  utilisateur, mot de passe — à reporter dans `.env`).
- Un nom de domaine/sous-domaine pointé vers l'hébergement.

## 2. Racine du site → dossier `public/`

Laravel doit être servi depuis `public/`, jamais depuis la racine du dépôt (sinon les
fichiers `.env`, `app/`, etc. seraient exposés publiquement).

- **Solution propre** : dans l'espace client LWS (Multisites), définir le
  **dossier racine** du domaine sur `DojoManager_laravel/public` (disponible sur la
  plupart des plans mutualisés récents).
- **Si l'option n'existe pas sur le plan souscrit** : déposer tout le projet dans un
  dossier hors de la racine web (ex. `dojomanager_app/`), et ne mettre dans la racine
  web qu'un `index.php` + `.htaccess` qui redirigent vers `dojomanager_app/public/`
  (variante du « front controller » — demander si besoin, pas fait par défaut ici pour
  ne pas complexifier une installation qui a l'option propre).

## 3. Déploiement des fichiers

Deux cas selon que le plan LWS donne un accès **SSH** :

### Avec SSH (recommandé)

```bash
git clone <votre-dépôt> dojomanager        # ou upload par FTP puis
cd dojomanager
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
# éditer .env (voir section 4)
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Sans SSH (FTP uniquement)

1. En local : `composer install --no-dev --optimize-autoloader` (génère `vendor/`).
2. Uploader tout le projet **y compris `vendor/`** par FTP (le dossier `whatsapp-bridge/`
   n'a rien à faire sur LWS — voir section 6, à exclure de l'upload).
3. Créer `.env` sur le serveur (copier `.env.example`, éditer via le gestionnaire de
   fichiers LWS ou un client FTP en édition texte) avec les valeurs de la section 4.
4. `php artisan key:generate` ne peut pas tourner sans shell : générer une clé en local
   (`php artisan key:generate --show`) et la coller dans `APP_KEY=` du `.env` distant.
5. Migrations : soit un accès SSH ponctuel si LWS le permet sur une offre supérieure,
   soit exporter le schéma (`php artisan schema:dump` ou un export SQL depuis une base
   locale) et l'importer via phpMyAdmin (fourni par LWS).
6. **`php artisan storage:link` crée un lien symbolique** — la fonction PHP
   `symlink()` est parfois désactivée sur les plans mutualisés bas de gamme. Si le lien
   ne se crée pas : soit demander son activation au support LWS, soit remplacer par une
   copie physique du dossier `storage/app/public` vers `public/storage` à chaque
   déploiement (moins propre mais fonctionnel sans `symlink()`).

## 4. Valeurs `.env` à adapter en production

```env
APP_NAME=DojoManager
APP_ENV=production
APP_DEBUG=false
APP_URL=https://votre-domaine.tld

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=<base fournie par LWS>
DB_USERNAME=<utilisateur fourni par LWS>
DB_PASSWORD=<mot de passe>

LOG_LEVEL=error

SESSION_DRIVER=database
CACHE_STORE=database

# Aucun job de file d'attente n'est utilisé dans l'application (pas de ShouldQueue,
# pas de Schedule::) : "sync" évite d'avoir à faire tourner un worker permanent
# (php artisan queue:work), impossible à maintenir vivant sur du mutualisé classique.
QUEUE_CONNECTION=sync

MAIL_MAILER=log
```

`APP_DEBUG=false` est important : en `true`, les erreurs affichent des traces
complètes (chemins serveur, requêtes SQL) au public.

## 5. Après chaque mise à jour du code

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan migrate --force   # si de nouvelles migrations
```

(Sans SSH : au minimum, vider `storage/framework/views/*` par FTP après un changement
de vue, sinon d'anciennes versions compilées peuvent rester servies.)

## 6. Le dossier `whatsapp-bridge/` NE VA PAS sur LWS

La passerelle WhatsApp (cf. [whatsapp-bridge/README.md](whatsapp-bridge/README.md))
est un service **Node.js qui doit rester connecté en continu** à WhatsApp — un
hébergement mutualisé PHP comme LWS ne peut pas faire tourner ce genre de processus
permanent. Elle continue de fonctionner exactement comme prévu même une fois le site
sur LWS : elle tourne sur un poste du club (ou une petite machine dédiée toujours
allumée), et c'est le **navigateur** de ce poste qui l'appelle directement en local —
pas le serveur LWS. Concrètement :

- Ne pas uploader `whatsapp-bridge/` sur LWS (inutile, ne fonctionnerait pas).
- Le poste du club installe et lance la passerelle localement (voir son README).
- Dans `.env` du site LWS, `WHATSAPP_BRIDGE_HOST` reste une adresse **locale au poste
  du club** (`127.0.0.1:9300` ou l'IP du poste sur le Wi-Fi du club) — jamais une
  adresse joignable depuis Internet. C'est le navigateur du club qui fait le lien entre
  le site (sur LWS) et la passerelle (chez lui), exactement comme pour l'impression
  thermique dans Projets_licence.

**Point d'attention une fois le site en HTTPS (LWS fournit un certificat gratuit)** :
les navigateurs bloquent en général les appels `http://` depuis une page `https://`
(« contenu mixte »). L'exception `127.0.0.1` (même poste) reste généralement
autorisée par les navigateurs modernes malgré ce blocage — c'est le cas d'usage
principal. En revanche, appeler la passerelle depuis un **autre appareil du Wi-Fi**
via une IP locale (`ALLOW_LAN=1`, ex. `192.168.1.50:9300`) a plus de risques d'être
bloqué par cette même protection selon le navigateur — à tester une fois en HTTPS, et
prévenir si ce cas d'usage (téléphone à distance du poste) est important pour vous.

## 7. Photos des disciples et autres fichiers uploadés

Les photos sont stockées via `Storage::disk('public')`, donc dans
`storage/app/public/` et servies via le lien `public/storage` (section 3, point 6).
Vérifier après déploiement qu'une photo uploadée depuis l'admin s'affiche bien.
