@extends('layouts.admin')

@section('title', __('messages.documentation.title'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('messages.dashboard') }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ __('messages.documentation.title') }}</li>
@endsection

@section('actions')
    <a href="{{ route('admin.documentation.pdf') }}" class="btn btn-outline-danger shadow-sm"><i class="fas fa-file-pdf me-1"></i> Exporter PDF</a>
@endsection

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent border-0 pb-0">
        <div class="mb-2 text-uppercase small text-primary fw-semibold">Documentation utilisateur</div>
        <h3 class="mb-2">DojoManager</h3>
        <div class="text-muted mb-3">La gestion claire du dojo, de la ligue et de la fédération.</div>
        <div class="alert alert-primary mb-0">
            <div class="fw-semibold mb-1">Introduction</div>
            <div>
                DojoManager est une application de gestion dédiée à l'écosystème du taekwondo. Elle couvre notamment
                le suivi des disciples, des ceintures noires, des passages de grades, des finances et des paramètres
                métier.
            </div>
            <div class="mt-2">
                Cette documentation explique comment utiliser l'application dans l'ordre réel de travail, afin qu'un
                utilisateur puisse avancer sans assistance extérieure.
            </div>
        </div>
    </div>

    <div class="card-body">
        <div class="row g-3 mb-4">
            <div class="col-md-6 col-xl-3">
                <div class="border rounded-3 p-3 h-100">
                    <div class="text-muted small">Point d'entrée</div>
                    <div class="fw-semibold">Tableau de bord</div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="border rounded-3 p-3 h-100">
                    <div class="text-muted small">Guide disponible</div>
                    <div class="fw-semibold">Pour tous les utilisateurs connectés</div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="border rounded-3 p-3 h-100">
                    <div class="text-muted small">Logique de travail</div>
                    <div class="fw-semibold">Modules utilisés selon le rôle</div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="border rounded-3 p-3 h-100">
                    <div class="text-muted small">Protection métier</div>
                    <div class="fw-semibold">Périmètre strict par rôle</div>
                </div>
            </div>
        </div>

        <div class="mb-4">
            <h4 class="mb-3">Étape 1 — Accès et démarrage</h4>
            <div class="alert alert-info">
                À la connexion, l'utilisateur n'a pas besoin de choisir manuellement ses modules. Le menu s'adapte
                automatiquement selon son rôle et ses permissions.
            </div>
            <ol class="mb-0">
                <li class="mb-2">Se connecter avec son compte (numéro de téléphone + mot de passe).</li>
                <li class="mb-2">Consulter le tableau de bord pour comprendre son périmètre et ses priorités.</li>
                <li class="mb-2">Ouvrir le module principal lié à son rôle : Disciples, Ceintures noires, Passages de grades ou Finances.</li>
                <li class="mb-2">Utiliser Paramètres pour compléter la configuration métier si cela est autorisé.</li>
            </ol>
        </div>

        <div class="mb-4">
            <h4 class="mb-3">Étape 2 — Comprendre son périmètre</h4>
            <ul class="mb-0">
                <li class="mb-2"><strong>Fédération</strong> : périmètre sur toute la fédération (toutes ses ligues et salles).</li>
                <li class="mb-2"><strong>Ligue</strong> : périmètre sur sa ligue, sans accès aux données des autres ligues.</li>
                <li class="mb-2"><strong>Maître</strong> : périmètre sur sa seule salle.</li>
            </ul>
        </div>

        <div class="mb-4">
            <h4 class="mb-3">Étape 3 — Utilisation des modules du menu</h4>
            <div class="row g-3">
                @php
                    $modules = [
                        ['title' => 'Tableau de bord', 'audience' => 'Tous les profils connectés selon leurs permissions.', 'purpose' => "Donner une vue d'ensemble de l'activité et servir de point d'entrée quotidien.", 'steps' => ['Ouvrir le tableau de bord après connexion.', 'Vérifier les informations clés affichées.', 'Naviguer ensuite vers le module métier concerné.']],
                        ['title' => 'Disciples', 'audience' => 'Principalement Maître.', 'purpose' => 'Gérer les disciples, leur suivi et les informations utiles au dojo.', 'steps' => ['Consulter la liste des disciples.', 'Ajouter ou modifier un disciple selon les droits disponibles.', 'Utiliser ces données comme base pour les mensualités et les passages de grades.']],
                        ['title' => 'Ceintures noires', 'audience' => 'Fédération, Ligue.', 'purpose' => 'Centraliser les ceintures noires, leurs rattachements et leur niveau.', 'steps' => ['Ouvrir la liste des ceintures noires.', 'Rechercher les profils selon la fédération, la ligue ou la salle.', "Vérifier les disciples DAN, les entrées manuelles et les maîtres/responsables selon les besoins."]],
                        ['title' => 'Passages de grades', 'audience' => 'Fédération, Ligue, Maître.', 'purpose' => "Gérer le cycle complet de création de session, soumission, validation, notation, finalisation et résultats.", 'steps' => ["Configurer les frais, l'annonce et la grille tarifaire dans le périmètre autorisé.", 'Soumettre les candidats éligibles.', 'Valider les candidatures si le rôle le permet.', "Saisir les notes d'examen.", 'Cliquer sur Finaliser la session quand toutes les notations sont terminées.', 'Consulter les résultats puis exporter le PDF dans le périmètre du rôle connecté.']],
                        ['title' => 'Finances', 'audience' => 'Maître pour Mensualités, Fédération/Ligue pour Cotisations.', 'purpose' => 'Suivre les flux financiers du dojo, des mensualités et des cotisations annuelles.', 'steps' => ['Ouvrir Mensualités pour le suivi mensuel au niveau de la salle.', 'Ouvrir Cotisations annuelles pour les campagnes annuelles au niveau ligue ou fédération.', "Contrôler les montants, les états et l'historique selon le périmètre autorisé."]],
                        ['title' => 'Paramètres', 'audience' => 'Profils autorisés selon le rôle et les permissions.', 'purpose' => "Configurer les références, paramètres de travail et éléments structurants de l'application.", 'steps' => ['Ouvrir Paramètres.', 'Modifier uniquement les informations relevant de son périmètre.', 'Enregistrer les changements avant de revenir au module métier.']],
                        ['title' => 'Documentation', 'audience' => 'Tous les utilisateurs connectés.', 'purpose' => "Fournir un guide pas à pas pour utiliser l'application sans assistance extérieure.", 'steps' => ['Ouvrir Documentation depuis le menu.', "Lire l'ordre conseillé d'utilisation des modules.", 'Revenir ensuite vers le module adapté à la tâche en cours.']],
                    ];
                @endphp
                @foreach($modules as $module)
                    <div class="col-12">
                        <div class="border rounded-3 p-3 h-100">
                            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-2 mb-2">
                                <div class="fw-semibold">{{ $module['title'] }}</div>
                                <span class="badge bg-light text-dark border">{{ $module['audience'] }}</span>
                            </div>
                            <div class="text-muted mb-2">{{ $module['purpose'] }}</div>
                            <ol class="mb-0">
                                @foreach($module['steps'] as $step)
                                    <li class="mb-1">{{ $step }}</li>
                                @endforeach
                            </ol>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="mb-4">
            <h4 class="mb-3">Étape 4 — Processus complet de Passage des grades</h4>
            <div class="alert alert-success">
                Ce module suit un ordre strict. Respecter cet ordre garantit des résultats cohérents, une
                finalisation claire et des exports fiables.
            </div>
            <ol class="mb-0">
                <li class="mb-2">Créer ou sélectionner une session.</li>
                <li class="mb-2">Configurer les frais, l'annonce et la grille tarifaire.</li>
                <li class="mb-2">Soumettre les candidats éligibles.</li>
                <li class="mb-2">Valider les candidatures si nécessaire.</li>
                <li class="mb-2">Saisir les notes d'examen.</li>
                <li class="mb-2">Suivre le guidage affiché par le système quand la notation est terminée.</li>
                <li class="mb-2">Finaliser la session.</li>
                <li class="mb-2">Consulter les résultats des examens.</li>
                <li class="mb-2">Exporter les résultats PDF dans le périmètre autorisé.</li>
            </ol>
        </div>

        <div class="mb-4">
            <h4 class="mb-3">Étape 5 — Règles de sécurité et de cloisonnement</h4>
            <ul class="mb-0">
                <li class="mb-2">Une ligue ne doit jamais voir les données d'une autre ligue.</li>
                <li class="mb-2">Un maître ne doit jamais voir les données d'une autre salle.</li>
                <li class="mb-2">Les résultats et exports PDF sont filtrés par rôle et par périmètre.</li>
                <li class="mb-2">Le bouton Finaliser la session n'apparaît que lorsque la notation est complète.</li>
                <li class="mb-2">Le bouton Résultats des examens n'apparaît qu'après la finalisation.</li>
                <li class="mb-2">Après 5 échecs de connexion consécutifs, le compte est bloqué temporairement 3 minutes.</li>
            </ul>
        </div>

        <div class="mb-4">
            <h4 class="mb-3">Étape 6 — Nouveautés propres à cette version</h4>
            <div class="alert alert-warning">
                Ces fonctionnalités n'existent pas dans la version d'origine de DojoManager — elles ont été ajoutées
                pour répondre à des besoins concrets du club.
            </div>
            <ul class="mb-0">
                <li class="mb-2"><strong>Mise à jour directe des grades</strong> (Disciples → Mise à jour des grades) : un maître peut faire progresser ses disciples sans attendre une session de passage de grade organisée par sa ligue/fédération.</li>
                <li class="mb-2"><strong>Historique des grades</strong> (fiche disciple → Historique des grades) : enregistre la date d'obtention de chaque grade, utilisée pour remplir automatiquement le verso de la carte de licence.</li>
                <li class="mb-2"><strong>Reçus WhatsApp</strong> : le reçu d'inscription d'un disciple et le reçu de mensualité peuvent être envoyés directement par WhatsApp depuis l'application.</li>
                <li class="mb-2"><strong>Cartes de licence des maîtres/responsables</strong> : un maître ou un responsable de ligue/fédération, s'il est lui-même ceinture noire, peut désormais avoir sa propre carte de licence, imprimable depuis Ceintures noires.</li>
                <li class="mb-2"><strong>Matricule automatique</strong> : les ceintures noires saisies manuellement ou les maîtres/responsables reçoivent un matricule attribué automatiquement, plus besoin de le saisir à la main.</li>
                <li class="mb-2"><strong>Installation mobile</strong> : l'application peut être installée comme une application sur téléphone ou ordinateur (bannière d'installation proposée automatiquement).</li>
                <li class="mb-2"><strong>Mode sombre</strong> : disponible depuis le bouton en haut de l'écran.</li>
            </ul>
        </div>

        <div class="mb-4">
            <h4 class="mb-3">Conclusion</h4>
            <div class="alert alert-warning mb-0">
                La logique de DojoManager repose sur un ordre de travail clair : Tableau de bord → module métier du
                rôle → validation des actions → consultation des résultats. Respecter cet ordre améliore la
                lisibilité, la sécurité métier et la fiabilité des données.
            </div>
        </div>

        <div class="text-muted small">
            Ce document est fourni à titre d'aide utilisateur. Dernière mise à jour : {{ now()->format('d/m/Y') }}.
        </div>
    </div>
</div>
@endsection
