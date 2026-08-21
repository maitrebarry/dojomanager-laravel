# DojoManager

Application de gestion administrative, pédagogique et financière pour une fédération
d'arts martiaux (disciples, grades, licences, cotisations, ceintures noires) —
réécriture en **Laravel/Blade** de l'application d'origine (backend Spring Boot +
frontend React, dossier `DojoManager/` du poste). Ce document a deux objectifs, comme
le README du frontend d'origine :

- servir de guide technique pour installer et lancer l'application ;
- servir de guide fonctionnel complet pour tous les modules, dans l'ordre de la
  navigation, **y compris les modules qui n'existaient pas dans la version
  Spring+React** et qui ont été ajoutés au fil de cette réécriture.

## 1. Base technique

- **Laravel 13** (PHP ^8.3), Blade pour les vues (pas de SPA séparée : tout est rendu
  côté serveur, contrairement à la version d'origine qui séparait un backend Spring
  et un frontend React communiquant en API).
- **MySQL/MariaDB** comme base de données.
- **barryvdh/laravel-dompdf** pour tous les documents PDF (cartes de licence, reçus,
  attestations, listes de candidats).
- **endroid/qr-code** pour les QR codes des licences.
- **SweetAlert2** (CDN) pour les confirmations et notifications, **Bootstrap 5.3**
  pour l'interface.
- Pas de build frontend (pas de npm/webpack) : les assets JS/CSS sont soit inline
  dans les vues, soit servis tels quels depuis `public/`.

Fichiers/dossiers principaux :

- `app/Http/Controllers/Admin/` : contrôleurs des modules admin ;
- `app/Models/` : modèles Eloquent ;
- `app/Models/Concerns/ScopedToUser.php` : trait de cloisonnement multi-tenant
  (fédération → ligue → salle), utilisé par la quasi-totalité des modèles via
  `Model::visibleTo($user)` ;
- `resources/views/admin/` : interfaces d'administration (une vue par module) ;
- `resources/views/layouts/sidebar.blade.php` : configuration de la navigation ;
- `routes/web.php` : toutes les routes web.

## 2. Installation (développement local)

```bash
cd DojoManager_laravel
composer install
cp .env.example .env
php artisan key:generate
```

Configurer la base de données dans `.env` :

```env
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=dojomanager
DB_USERNAME=root
DB_PASSWORD=
```

`APP_URL` doit correspondre exactement à l'adresse utilisée pour accéder au site
(y compris le port) — une valeur incorrecte casse la génération des liens vers les
fichiers uploadés (photos de disciples, etc.).

```bash
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

L'application est alors disponible sur `http://127.0.0.1:8000`.

**Déploiement en production** : voir [`DEPLOY.md`](DEPLOY.md) (procédure détaillée
pour un hébergement mutualisé de type LWS, y compris les cas particuliers sans accès
SSH, sans `symlink()`, ou sans `mod_rewrite`).

## 3. Rôles et périmètres

Quatre rôles, du plus large au plus restreint :

- `superadmin` : vue globale, seul rôle non soumis au cloisonnement de périmètre ;
- `federation` : vue sur toute sa fédération (toutes les ligues et salles qui en
  dépendent) ;
- `ligue` : vue sur sa ligue et les salles qui en dépendent ;
- `maitre` : vue sur sa seule salle.

Le cloisonnement est appliqué au niveau des requêtes Eloquent via le trait
`ScopedToUser` (`Model::visibleTo($user)`), pas seulement au niveau de l'affichage —
une ligue ne peut donc pas accéder aux données d'une autre ligue même en forgeant une
URL directement.

## 4. Navigation générale et modules

Ordre de la sidebar, piloté par les rôles et permissions (`PermissionSeeder`) :

1. Tableau de bord
2. Disciples
3. Ceintures noires
4. Passages de grades
5. Finances (Mensualités, Cotisations annuelles)
6. Paramètres

### 4.1. Tableau de bord

Statistiques adaptées au rôle connecté (vue globale pour le superadmin/la
fédération, vue ligue, vue salle pour un maître). Cartes redessinées en dégradés de
couleur avec icônes, 3 par ligne.

### 4.2. Disciples

Public principal : `maitre`. Gestion des disciples d'une salle : fiche complète
(identité, naissance, contact, grade), photo, reçu d'inscription imprimable. Sert de
source de vérité pour les autres modules (passages de grade, mensualités,
cotisations, cartes de licence).

### 4.3. Ceintures noires

Public principal : `superadmin`, `federation`, `ligue`. Liste consolidée des
ceintures noires (grade `DAN`), à partir de **trois sources** (voir aussi § 5.5) :

- les disciples au grade DAN ;
- les saisies manuelles (personnes non enregistrées comme disciple) ;
- les maîtres/responsables de ligue ou fédération eux-mêmes, dès lors qu'ils ont un
  grade DAN **et** sont responsables d'au moins une salle.

### 4.4. Passages de grades

Cycle complet : configuration (frais, grille tarifaire), soumission des candidats,
examen (notation), finalisation, attestations PDF. Types de session `KEUP` (portée
par une ligue) et `DAN` (portée par une fédération), avec délégation possible des
privilèges de validation/examen — logique reprise de la version Spring+React.

Voir aussi § 5.1 et 5.2 pour la voie alternative de mise à jour des grades, ajoutée
dans cette version Laravel.

### 4.5. Finances

- **Mensualités** (public : `maitre`) : suivi des paiements mensuels au niveau de la
  salle, reçus imprimables (voir § 5.3).
- **Cotisations annuelles** (public : `superadmin`, `federation`, `ligue`) :
  campagnes de cotisation annuelle des ceintures noires — fige la liste des
  ceintures noires du moment (les trois sources du § 4.3, y compris les
  maîtres/responsables) et leur applique un montant.

### 4.6. Paramètres

Fédérations, Ligues, Grades, Salles, Utilisateurs, Permissions, Assigner Permission
(cet ordre — voir § 5.9 pour le détail des deux derniers onglets, ajoutés/complétés
dans cette version).

### 4.7. Module présent mais non relié à la navigation

Un module **Compétitions/Combats** existe (contrôleurs et vues complets, routes
`admin.competitions.*` / `admin.combats.*`) mais n'a pas d'entrée dans la sidebar —
hérité de la version d'origine, jamais branché côté Laravel. À relier si le besoin
se confirme.

## 5. Modules absents de la version Spring+React d'origine

Cette section documente tout ce qui a été **ajouté** pendant la réécriture Laravel et
qui n'a pas d'équivalent dans `DojoManager/dojo-frontend` / `dojomanager_backend`.

### 5.1. Mise à jour directe des grades (sans session de passage formelle)

Écran `admin/disciples/grades` (bouton "Mise à jour des grades" depuis la liste des
disciples), réservé au rôle `maitre` avec la permission `DISCIPLE_UPDATE`.

Raison d'être : dans la version d'origine, un passage de grade nécessite une session
créée par une ligue ou une fédération. Un maître qui utilise l'application sans que
sa ligue/fédération n'y adhère encore ne pourrait donc jamais faire progresser ses
disciples. Ce module permet au maître de faire progresser un disciple au grade
suivant (ou à un grade choisi) directement, sans passer par le cycle complet de
session/soumission/examen.

Fonctions :

- mise à jour individuelle ou en lot (sélection multiple) ;
- mode "grade suivant" (calculé automatiquement à partir de la séquence de grades de
  la fédération, `niveau` croissant, KEUP puis DAN) ou "grade choisi" ;
- option pour rafraîchir la date d'obtention du grade ;
- impression d'attestations de grade (individuelle ou pour la sélection) ;
- génération de la **liste des candidats au passage de grade**, avec l'en-tête
  officiel (ministère / fédération / ligue à gauche, République du Mali à droite),
  utilisable par un maître pour préparer une passation même sans que ligue/fédération
  ne l'ait organisée.

### 5.2. Historique des grades par disciple

Écran `admin/disciples/{disciple}/grades-historique` (bouton "Historique des grades"
depuis la fiche disciple). Permet de saisir, pour chaque grade de la séquence, la
date d'obtention exacte — alimente automatiquement le tableau des grades au verso de
la carte de licence de salle (§ 5.4), et est renseigné automatiquement à chaque
mise à jour de grade (§ 5.1).

### 5.3. Reçus thermiques et envoi WhatsApp automatique

Reçu d'inscription disciple et reçu de mensualité, au format ticket thermique
(80 mm, fidèle au rendu d'une imprimante de caisse), imprimables et **envoyables
directement par WhatsApp** :

- `whatsapp-bridge/` : passerelle Node.js locale (Baileys, protocole WhatsApp Web
  non officiel) qui tourne sur un poste du club et reste connectée en continu — voir
  son propre [README](whatsapp-bridge/README.md) ;
- le navigateur du poste appelle cette passerelle en local (`127.0.0.1:9300` par
  défaut) pour capturer le reçu à l'écran et l'envoyer, sans jamais transiter par le
  serveur web (important pour un hébergement mutualisé comme LWS, qui ne peut pas
  faire tourner un processus permanent — voir `DEPLOY.md` § 6) ;
- envoi automatique proposé juste après la création d'un disciple ou l'encaissement
  d'une mensualité, en plus du bouton d'envoi manuel.

### 5.4. Cartes de licence étendues au-delà des disciples

Dans la version d'origine, seuls les disciples ont une carte de licence. Cette
version permet aussi d'imprimer une carte régionale/fédérale pour :

- un **maître ou responsable de ligue/fédération** lui-même (il est souvent
  lui-même une ceinture noire, cf. § 4.3) ;
- une **ceinture noire saisie manuellement**.

Techniquement : `LicenceController::disciples()` accepte `?ids=` (disciples,
existant), `?user_ids=` (maîtres/responsables, nouveau) et `?manuelle_ids=` (saisies
manuelles, nouveau), avec la même logique de cloisonnement `visibleTo()`. La case à
cocher correspondante a été ajoutée sur les trois types de lignes de l'écran
Ceintures noires.

Sur la **carte de salle** (verso, imprimée par un maître pour ses disciples) : le
tableau des grades KEUP est désormais rempli automatiquement (date d'obtention +
signature/nom/grade du maître) à partir de l'historique du § 5.2, au lieu d'un
tableau vide à remplir à la main. L'en-tête "Signature du Directeur Technique" a été
renommé "Signature du Maître de salle" sur cette carte uniquement (les cartes
ligue/fédération gardent leur libellé d'origine).

### 5.5. Matricule automatique

Les ceintures noires saisies manuellement et les maîtres/responsables imprimant leur
propre carte reçoivent désormais un **matricule attribué automatiquement**
(`App\Support\MatriculeGenerator`) : préfixe dérivé de la ligue (ex. `SEG-`) suivi
d'un compteur séquentiel partagé entre les deux sources pour éviter toute collision
(ex. `SEG-0001`, `SEG-0002`...). Plus de saisie manuelle du matricule, jamais
modifiable une fois attribué.

### 5.6. Interface mobile / PWA

L'application peut être installée comme une application (Progressive Web App) :
`public/manifest.webmanifest`, `public/sw.js` (mode hors-ligne basique), bannière
d'installation adaptée iOS/Android/desktop. Interface responsive pensée mobile-first.

### 5.7. Mode sombre

Thème sombre complet (variables CSS dédiées, corrections spécifiques pour les
composants Bootstrap qui ne suivent pas les variables globales par défaut : tableaux,
modales, SweetAlert2).

### 5.8. Sécurité de connexion

- **Limitation des tentatives** : blocage temporaire (3 minutes) après 5 échecs
  consécutifs, avec compteur de tentatives restantes affiché à chaque échec.
- **Blocage d'utilisateur avec motif personnalisé** : un superadmin peut désactiver
  n'importe quel compte (sauf un autre superadmin) avec un motif libre — ce motif
  est alors affiché à l'utilisateur bloqué à sa prochaine tentative de connexion
  (par défaut, un message générique l'invite à contacter l'administrateur pour une
  mise à jour). Deux permissions dédiées et indépendantes contrôlent la
  fonctionnalité : voir l'icône (`UTILISATEUR_ACTIVATION_READ`) et
  bloquer/débloquer (`UTILISATEUR_ACTIVATE`).

### 5.9. Gestion des permissions : deux écrans distincts

La version d'origine n'a qu'un seul écran de gestion des permissions (assignation à
un utilisateur). Cette version sépare :

- **Permissions** (onglet Paramètres, réservé au superadmin) : CRUD des permissions
  elles-mêmes (créer/renommer/supprimer un droit), groupé par module pour la
  lisibilité ;
- **Assigner Permission** (inchangé dans son principe) : cocher les permissions
  existantes pour un utilisateur donné de son périmètre.

### 5.10. Module Abonnement (documenté, non implémenté)

Un modèle de facturation par palier (fédération/ligue/salle, avec couverture en
cascade) a été conçu et documenté en détail dans
[`ABONNEMENT.md`](ABONNEMENT.md), à la demande explicite du club pour une mise en
œuvre future — **non implémenté à ce jour**, le fichier sert de spécification.

## 6. Structure du projet

```text
app/
├── Http/Controllers/Admin/      Contrôleurs des modules admin
├── Http/Controllers/Concerns/   Traits partagés (scoping, génération PDF thermique)
├── Models/                      Modèles Eloquent
├── Models/Concerns/             ScopedToUser (cloisonnement multi-tenant)
├── Support/                     Utilitaires (MatriculeGenerator, WhatsAppPhone, CardSettings)
└── Services/                    Services applicatifs (Auth, User, Permission)

database/
├── migrations/                  Structure de la base de données
└── seeders/                     Données initiales, dont PermissionSeeder

resources/views/
├── admin/                       Interfaces d'administration, une vue par module
├── auth/                        Authentification et profil
└── layouts/                     Layouts, sidebar et navigation

routes/
└── web.php                      Routes web de l'application

whatsapp-bridge/                 Passerelle Node.js d'envoi WhatsApp (voir son README)
```

## 7. Commandes utiles

```bash
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan migrate:fresh --seed   # développement uniquement, réinitialise la base
```

## 8. Git

```bash
git add <fichiers>
git commit -m "..."
git push origin main
```

## 9. Documents associés

- [`DEPLOY.md`](DEPLOY.md) : déploiement en production (hébergement mutualisé LWS).
- [`ABONNEMENT.md`](ABONNEMENT.md) : spécification du futur module d'abonnement.
- [`whatsapp-bridge/README.md`](whatsapp-bridge/README.md) : installation et
  utilisation de la passerelle WhatsApp locale.

## 10. Licence

Projet propriétaire.
