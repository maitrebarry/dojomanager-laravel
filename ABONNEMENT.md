# Module Abonnement — explication complète (pas encore implémenté)

Ce document explique en détail le module « Abonnement » tel qu'il a été
conçu avec toi, **avant toute implémentation**. Rien de ce qui est décrit
ici n'existe encore dans le code — c'est une explication à lire et
valider ; le code viendra dans un second temps, quand tu me diras d'y
aller.

## 1. Pourquoi ce module

Aujourd'hui, DojoManager n'a aucune notion de facturation : n'importe
quel compte créé fonctionne indéfiniment, gratuitement. Le but de ce
module est de pouvoir, à terme, faire payer l'utilisation de
l'application — un peu comme un logiciel en location (SaaS) — avec un
système de blocage automatique quand un compte n'est plus à jour de
paiement.

Il n'existait aucune base à réutiliser dans DojoManager : la page
`Abonnement.jsx` de la version React d'origine appelait déjà des
fonctions d'API (`fetchCurrentSubscription`, `submitManualSubscription
Payment`, etc.) mais **rien de correspondant n'a jamais été codé côté
serveur Spring** — c'était un écran maquette, jamais branché. On repart
donc entièrement du modèle qui, lui, fonctionne réellement :
**ATELIKO_laravel**, une autre application Laravel où ce système est déjà
en production. On en reprend l'architecture technique (tables, contrôleurs,
verrouillage), en l'adaptant à une particularité de DojoManager que tu as
précisée : contrairement à ATELIKO (un seul niveau de client, « l'atelier »),
DojoManager a **trois niveaux hiérarchiques** qui peuvent chacun être
facturés séparément : fédération, ligue, salle (maître).

## 2. Le principe : chaque niveau a son propre abonnement, avec un système de « couverture »

C'est le cœur du module, et le point qui le distingue le plus d'ATELIKO.

- **Chaque salle (maître), chaque ligue et chaque fédération peut avoir
  son propre abonnement**, complètement indépendant des autres.
- Un maître peut s'abonner **directement auprès du superadmin**, même si
  sa ligue ou sa fédération n'ont elles-mêmes aucun abonnement. Il n'a
  besoin de l'accord de personne d'autre.
- Une fédération (ou une ligue) qui a un abonnement **actif** peut décider
  elle-même — via un simple interrupteur dans son propre écran Abonnement —
  si son abonnement **couvre automatiquement** tout ce qui est en dessous
  d'elle, ou non :
  - Si elle active la couverture : toutes les ligues et salles sous elle
    fonctionnent librement, sans avoir besoin de payer quoi que ce soit
    elles-mêmes.
  - Si elle ne l'active pas (ou n'a pas d'abonnement du tout) : chaque
    ligue et chaque salle en dessous doit avoir son **propre** abonnement
    actif pour continuer à fonctionner — ou s'abonner directement auprès
    du superadmin, comme n'importe quel maître peut déjà le faire.
- **Le superadmin n'est jamais concerné** par un abonnement — il gère
  toute la plateforme et n'a pas à payer.

### Exemples concrets

**Exemple A — Fédération qui paie pour tout le monde**
La Fédération Malienne de Taekwondo prend un abonnement « à vie » auprès
du superadmin, et active la couverture. Résultat : toutes les ligues et
toutes les salles rattachées à cette fédération utilisent l'application
librement, sans jamais avoir à s'abonner elles-mêmes — même si elles n'ont
individuellement aucun abonnement enregistré.

**Exemple B — Fédération qui ne paie que pour elle-même**
La même fédération prend son abonnement à vie, mais **n'active pas** la
couverture (ou choisit plus tard de la désactiver). Résultat : la
fédération elle-même fonctionne (elle est à jour), mais chaque ligue et
chaque salle sous elle doit avoir son propre abonnement actif, sinon elle
sera bloquée à la connexion.

**Exemple C — Un maître isolé, sans ligue ni fédération abonnées**
Un maître dont ni la ligue ni la fédération n'utilisent DojoManager (ou
n'ont pas d'abonnement actif) peut malgré tout s'abonner **lui-même**,
directement, en soumettant un paiement au superadmin — exactement le cas
de figure que tu as décrit au tout début : « les maîtres de salle vont
utiliser l'application sans que les ligues ou fédérations n'y adhèrent ».
Son abonnement à lui suffit à débloquer l'usage pour sa salle.

**Exemple D — Ligue autonome sous une fédération non couvrante**
Une ligue dont la fédération n'a pas activé la couverture peut prendre
son propre abonnement (mensuel, annuel ou à vie) et, elle aussi, décider
si **cet** abonnement couvre ou non les salles sous elle.

### Comment l'accès est vérifié, en résumé

Quand un utilisateur (autre que superadmin) se connecte ou navigue dans
l'application, le système vérifie dans cet ordre :

1. Est-ce que sa fédération a un abonnement actif **et** couvre les
   niveaux en dessous ? → accès autorisé pour tout le monde en dessous,
   fin de la vérification.
2. Sinon (pour un rôle ligue ou une salle sous cette ligue) : est-ce que
   sa ligue a un abonnement actif **et** couvre les salles en dessous ?
   → accès autorisé.
3. Sinon : est-ce que **son propre** abonnement (fédération, ligue ou
   salle selon son rôle) est actif ? Si oui, accès autorisé ; si non,
   bloqué.

Important : une fédération ou une ligue **sans** abonnement actif ne
bloque jamais automatiquement ce qui est en dessous d'elle — elle ne fait
que ne rien couvrir. Chaque niveau reste libre de s'abonner de son côté.

## 3. Que se passe-t-il concrètement quand un compte est bloqué ?

Comme dans ATELIKO, il y a **deux niveaux de verrouillage** :

1. **Au moment de la connexion** : si le compte qui essaie de se
   connecter n'est couvert par aucun abonnement actif (ni le sien, ni
   celui d'une fédération/ligue au-dessus qui le couvrirait), il n'est
   **pas connecté du tout** — pas de session ouverte, pas d'accès au
   tableau de bord. À la place, l'écran de connexion affiche directement
   un formulaire : choix d'un plan (mensuel, trimestriel, semestriel,
   annuel, à vie), et upload d'une preuve de paiement Orange Money
   (référence de transaction + capture d'écran/PDF). Une fois envoyée,
   cette demande passe en attente de validation par le superadmin.

2. **Pendant une session déjà ouverte** : si un abonnement expire pendant
   qu'un utilisateur est déjà connecté (cas typique : il s'est connecté
   le matin, son abonnement a expiré à minuit), toutes les pages de
   l'application le redirigent automatiquement vers le tableau de bord
   (qui affichera l'état bloqué et le lien vers Abonnement), sauf les
   pages indispensables : Abonnement lui-même, Déconnexion, Profil.

## 4. Les écrans

### Écran « Abonnement » (accessible à tous les rôles, sauf superadmin)

Chaque utilisateur y voit **uniquement son propre niveau** : un maître
voit l'abonnement de sa salle, un responsable de ligue voit celui de sa
ligue, un responsable de fédération voit celui de sa fédération.

Contenu :
- Statut actuel (plan en cours, date d'échéance, nombre de jours
  restants, badge de couleur selon le statut).
- Formulaire pour soumettre un nouveau paiement (choix du plan + preuve
  Orange Money) — que l'abonnement soit expiré ou qu'on veuille
  simplement le renouveler à l'avance.
- Historique de tous les paiements soumis (avec leur statut : en
  attente / validé / rejeté).
- **Si l'utilisateur est fédération ou ligue et a un abonnement actif** :
  un interrupteur « Couvrir les niveaux en dessous » — c'est lui qui
  active/désactive la couverture automatique décrite plus haut.

### Écran superadmin « Gestion des abonnements »

Réservé au superadmin, avec plusieurs onglets :
- **Paiements en attente** : liste des preuves de paiement soumises par
  n'importe quel maître/ligue/fédération, avec boutons Approuver / Rejeter.
  Approuver un paiement active (ou prolonge) automatiquement l'abonnement
  correspondant à la bonne durée.
- **Historique** : tous les paiements déjà traités.
- **Fédérations / Ligues / Salles** : vue d'ensemble de tous les
  abonnements existants à tous les niveaux, avec possibilité d'activer,
  suspendre ou ajuster manuellement les dates d'un abonnement sans passer
  par un paiement (utile pour un geste commercial, une correction, etc.).
- **Plans** : gestion des plans disponibles (créer, modifier le prix/la
  durée, activer/désactiver).

## 5. Les plans par défaut

Cinq plans seront créés automatiquement à l'installation du module, tous
au prix de **0 FCFA au départ** — tu ajusteras toi-même les vrais tarifs
plus tard directement depuis l'écran Plans, sans avoir besoin de
retoucher au code :

| Code | Durée |
|---|---|
| MENSUEL | 1 mois |
| TRIMESTRIEL | 3 mois |
| SEMESTRIEL | 6 mois |
| ANNUEL | 12 mois |
| A_VIE | illimitée |

## 6. Moyen de paiement

Pour l'instant, seul **Orange Money** est prévu (numéro à renseigner
dans les paramètres). La structure technique reste toutefois assez
générique pour qu'on puisse ajouter Wave, Mobicash ou un autre moyen plus
tard sans grand changement.

## 7. Point d'attention avant activation

Le jour où ce module sera réellement mis en ligne (verrou de connexion +
middleware activés), **tout compte qui n'a encore aucun abonnement
enregistré nulle part dans son arbre (ni le sien, ni celui d'une
fédération/ligue au-dessus qui le couvre) sera bloqué dès sa prochaine
connexion** — exactement comme dans ATELIKO. Il faudra donc, avant de
basculer le verrou en production, créer manuellement au moins un
abonnement actif pour tes comptes de test/réels existants (via l'écran
superadmin), pour ne pas bloquer tout le monde d'un coup le jour de la
mise en service.

## 8. Détails techniques (pour référence, quand on codera)

- **3 nouvelles tables** : `abonnement_plans` (le catalogue des plans),
  `abonnements` (un enregistrement par abonnement — fédération, ligue ou
  salle — avec son statut, ses dates, et l'interrupteur de couverture),
  `abonnement_paiements` (chaque preuve de paiement soumise, avec son
  statut de validation).
- **Un service centralisé** (`SubscriptionGateService`) qui contient
  toute la logique de la cascade décrite en section 2 — un seul endroit
  à modifier si la règle métier évolue plus tard.
- **Réutilisation du modèle ATELIKO_laravel**, déjà en production et
  fonctionnel, pour tout ce qui est structure de contrôleurs, formulaire
  de soumission de preuve, et écran d'administration — donc pas
  d'improvisation sur la partie technique, seulement une adaptation du
  modèle à trois niveaux plutôt qu'un seul.
- Le plan d'implémentation détaillé (fichiers exacts, migrations,
  vérifications) est prêt et pourra être exécuté dès que tu donnes le
  feu vert — aucune raison de le re-détailler ici, ce document se
  concentre sur la compréhension fonctionnelle.
