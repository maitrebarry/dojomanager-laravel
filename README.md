# sigLAB Manager

Application Laravel de gestion administrative, pédagogique et financière pour un centre de formation.

## Présentation

**sigLAB Manager** permet de gérer le cycle complet d'un centre de formation: utilisateurs, apprenants, formations, inscriptions, suivi pédagogique, paiements, dépenses, commissions formateurs et attestations.

Une évolution est en cours pour organiser les formations par **groupes de formation**. Pour le moment, cette évolution est surtout appliquée aux modules **Gestion des formations** et **Apprenants / inscriptions**. Les autres modules doivent encore être alignés proprement sur `groupe_formation_id`.

Le projet est construit avec Laravel et utilise une interface d'administration responsive.

## Fonctionnalités principales

- Tableau de bord administrateur avec statistiques.
- Gestion des utilisateurs, rôles et permissions.
- Gestion des apprenants et de leurs inscriptions.
- Gestion des formations et catégories de formation.
- Gestion des groupes de formation avec formateurs, planning, salle, capacité et statut.
- Génération PDF de l'emploi du temps d'un groupe de formation.
- Gestion pédagogique: présences, évaluations, examens, notes et résultats.
- Gestion financière: paiements apprenants, reçus, dépenses et paiements/commissions formateurs.
- Gestion des attestations.

## Modules importants

### Formations et groupes

Les formations représentent l'offre pédagogique globale. Les opérations concrètes se font ensuite par **groupes de formation**:

- création et modification des groupes;
- attribution du formateur principal et des formateurs associés;
- suivi du statut: planifiée, en cours, terminée ou suspendue;
- liaison des apprenants et inscriptions au groupe.

Important: l'intégration des groupes est effective côté formations et inscriptions/apprenants. Les modules pédagogie, finances et attestations doivent encore être vérifiés et finalisés avant d'être considérés comme complètement basés sur les groupes.

Routes principales:

```text
admin/formations
admin/groupes-formations
admin/groupes-formations/{groupe}/emploi-du-temps/pdf
```

### Apprenants

Le module apprenants permet de créer, modifier et consulter les dossiers des apprenants. Les inscriptions relient chaque apprenant à une formation et à un groupe de formation.

Route principale:

```text
admin/apprenants
```

### Pédagogie

Le module pédagogique existe déjà pour:

- feuille de présence;
- création des évaluations et examens;
- saisie des notes;
- consultation des résultats.

Routes principales:

```text
admin/pedagogie/presences
admin/pedagogie/evaluations
admin/pedagogie/examens
admin/pedagogie/notes
admin/pedagogie/resultats
```

Etat actuel avec les groupes: à finaliser. Les écrans et contrôleurs doivent être revus pour utiliser de façon cohérente `groupe_formation_id` au lieu d'une simple formation.

### Finances

Le module finances couvre:

- encaissements des paiements apprenants;
- génération de reçus;
- suivi des dépenses;
- paiement des formateurs et commissions.

Routes principales:

```text
admin/finances
admin/finances/paiements
admin/finances/depenses
admin/finances/formateurs
```

Etat actuel avec les groupes: à finaliser. Les paiements, reçus, dépenses et commissions doivent être vérifiés pour afficher et enregistrer le bon groupe de formation quand il existe.

### Attestations

Les attestations existent déjà dans l'application.

Etat actuel avec les groupes: à finaliser. La logique doit être vérifiée pour générer une attestation à partir du bon groupe de formation, contrôler l'inscription de l'apprenant dans ce groupe et vérifier le paiement complet.

Route principale:

```text
admin/attestations
```

## Installation

### Prérequis

- PHP compatible avec la version Laravel du projet.
- Composer.
- MySQL ou MariaDB.
- Node.js et npm si vous souhaitez compiler les assets.

### Mise en place

```bash
cd /opt/lampp/htdocs/sigLAB_Manager
composer install
cp .env.example .env
php artisan key:generate
```

Configurer ensuite la base de données dans `.env`:

```env
APP_NAME="sigLAB Manager"
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=db_taek_segou
DB_USERNAME=root
DB_PASSWORD=
```

Lancer les migrations et les seeders:

```bash
php artisan migrate --seed
```

Démarrer le serveur local:

```bash
php artisan serve
```

L'application sera disponible sur:

```text
http://127.0.0.1:8000
```

## Commandes utiles

Nettoyer les caches Laravel:

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

Relancer les migrations en développement:

```bash
php artisan migrate:fresh --seed
```

Lancer les tests:

```bash
php artisan test
```

Si les tests utilisent SQLite en mémoire, vérifier que l'extension PHP SQLite est installée et activée.

## Structure du projet

```text
app/
├── Http/Controllers/Admin/      Contrôleurs des modules admin
├── Http/Requests/               Validation des formulaires
├── Models/                      Modèles Eloquent
└── Services/                    Services applicatifs

database/
├── migrations/                  Structure de la base de données
└── seeders/                     Données initiales

resources/views/
├── admin/                       Interfaces d'administration
├── auth/                        Authentification et profil
└── layouts/                     Layouts, sidebar et navigation

routes/
└── web.php                      Routes web de l'application
```

## Mises à jour récentes

### Gestion financière et reçus (Juin 2026)
- **Références de l'entreprise :** Mise à jour des reçus élèves (`receipt.blade.php`) et reçus formateurs (`trainer_receipt.blade.php`) pour afficher les informations de *SigLAB Technologie SARL* (Adresse Bamako Boulkansoumbougou près du marché, Imm. Thioma Guidado en face de PMU-Mali, Tél: +223 93 38 73 25).
- **CRUD des paiements apprenants :** Implémentation complète de la modification et de la suppression des paiements depuis l'historique financier (`payments.blade.php` et `FinanceController.php`).
  - Validation dynamique pour s'assurer que les paiements modifiés n'excèdent pas le reste à payer d'une inscription.
  - Recalcul et réajustement automatiques du champ `montant_paye` de la table `inscriptions` lors de la modification ou suppression, protégés par des transactions de base de données.
  - Interface dynamique avec SweetAlert2 pour les confirmations de suppression et un modal Bootstrap pour la modification.
- **Sécurité :** Ajout des permissions `edit_payment` et `delete_payment` dans le `PermissionSeeder`.
- **Stabilisation des routes :** Nettoyage des routes orphelines et import du contrôleur inexistant `GroupeController` pour corriger les dysfonctionnements des commandes Artisan (`route:list`, cache, etc.).

### Formation par groupe
Ajout du module **groupes de formation** pour la gestion des formations et des inscriptions:
- nouveau contrôleur `GroupeFormationController`;
- nouveau modèle `GroupeFormation`;
- nouvelles vues dans `resources/views/admin/groupes-formations`;
- nouvelles migrations pour créer les groupes et ajouter le champ `groupe_formation_id` aux tables concernées;
- adaptation principale des formations, groupes de formation, apprenants et inscriptions.

### Travail restant pour le binôme
Les modules suivants doivent encore être alignés et testés avec les groupes de formation:
- **Pédagogie**: présences, évaluations, examens, notes et résultats.
- **Finances**: vérifier et s'assurer que les paiements / commissions formateurs sont alignés sur `groupe_formation_id`.
- **Attestations**: génération par groupe, vérification de l'inscription au groupe et contrôle du paiement complet.

## Git

Après un `git pull` avec rebase, résoudre les conflits puis continuer:

```bash
git status
git add <fichiers_resolus>
git rebase --continue
```

Pour envoyer les travaux:

```bash
git push origin main
```

## Licence

Projet propriétaire - sigLAB.
