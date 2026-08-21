<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Documentation DojoManager</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; color: #1f2937; font-size: 11px; line-height: 1.5; }
        h1 { font-size: 20px; margin: 0 0 2px; color: #0f172a; }
        h2 { font-size: 14px; margin: 0 0 14px; color: #475569; font-weight: normal; }
        h3 { font-size: 13px; margin: 18px 0 6px; color: #0f172a; border-bottom: 1px solid #cbd5e1; padding-bottom: 3px; }
        .box { border: 1px solid #cbd5e1; border-radius: 4px; padding: 8px 10px; margin-bottom: 10px; background: #f8fafc; }
        ul, ol { margin: 4px 0 4px 18px; padding: 0; }
        li { margin-bottom: 3px; }
        .muted { color: #64748b; }
        .module { border: 1px solid #e2e8f0; border-radius: 4px; padding: 6px 10px; margin-bottom: 8px; }
        .module .name { font-weight: bold; }
        .module .audience { color: #64748b; font-size: 10px; }
    </style>
</head>
<body>
    <h1>Documentation utilisateur</h1>
    <h2>DojoManager — la gestion claire du dojo, de la ligue et de la fédération</h2>

    <div class="box">
        <strong>Introduction</strong><br>
        DojoManager est une application de gestion dédiée à l'écosystème du taekwondo. Elle couvre notamment le
        suivi des disciples, des ceintures noires, des passages de grades, des finances et des paramètres métier.
        Cette documentation explique comment utiliser l'application dans l'ordre réel de travail, afin qu'un
        utilisateur puisse avancer sans assistance extérieure.
    </div>

    <h3>Étape 1 — Accès et démarrage</h3>
    <ol>
        <li>Se connecter avec son compte (numéro de téléphone + mot de passe).</li>
        <li>Consulter le tableau de bord pour comprendre son périmètre et ses priorités.</li>
        <li>Ouvrir le module principal lié à son rôle : Disciples, Ceintures noires, Passages de grades ou Finances.</li>
        <li>Utiliser Paramètres pour compléter la configuration métier si cela est autorisé.</li>
    </ol>

    <h3>Étape 2 — Comprendre son périmètre</h3>
    <ul>
        <li>Fédération : périmètre sur toute la fédération (toutes ses ligues et salles).</li>
        <li>Ligue : périmètre sur sa ligue, sans accès aux données des autres ligues.</li>
        <li>Maître : périmètre sur sa seule salle.</li>
    </ul>

    <h3>Étape 3 — Utilisation des modules du menu</h3>
    @php
        $modules = [
            ['title' => 'Tableau de bord', 'audience' => 'Tous les profils connectés selon leurs permissions.', 'purpose' => "Donner une vue d'ensemble de l'activité et servir de point d'entrée quotidien."],
            ['title' => 'Disciples', 'audience' => 'Principalement Maître.', 'purpose' => 'Gérer les disciples, leur suivi et les informations utiles au dojo.'],
            ['title' => 'Ceintures noires', 'audience' => 'Fédération, Ligue.', 'purpose' => 'Centraliser les ceintures noires (disciples DAN, saisies manuelles, maîtres/responsables), leurs rattachements et leur niveau.'],
            ['title' => 'Passages de grades', 'audience' => 'Fédération, Ligue, Maître.', 'purpose' => "Gérer le cycle complet : session, soumission, validation, notation, finalisation, résultats."],
            ['title' => 'Finances', 'audience' => 'Maître (Mensualités), Fédération/Ligue (Cotisations).', 'purpose' => 'Suivre les flux financiers du dojo, des mensualités et des cotisations annuelles.'],
            ['title' => 'Paramètres', 'audience' => 'Profils autorisés selon le rôle.', 'purpose' => "Configurer les références et éléments structurants de l'application."],
        ];
    @endphp
    @foreach($modules as $module)
        <div class="module">
            <span class="name">{{ $module['title'] }}</span> — <span class="audience">{{ $module['audience'] }}</span><br>
            {{ $module['purpose'] }}
        </div>
    @endforeach

    <h3>Étape 4 — Processus complet de Passage des grades</h3>
    <ol>
        <li>Créer ou sélectionner une session.</li>
        <li>Configurer les frais, l'annonce et la grille tarifaire.</li>
        <li>Soumettre les candidats éligibles.</li>
        <li>Valider les candidatures si nécessaire.</li>
        <li>Saisir les notes d'examen.</li>
        <li>Finaliser la session une fois la notation terminée.</li>
        <li>Consulter les résultats des examens.</li>
        <li>Exporter les résultats en PDF dans le périmètre autorisé.</li>
    </ol>

    <h3>Étape 5 — Règles de sécurité et de cloisonnement</h3>
    <ul>
        <li>Une ligue ne voit jamais les données d'une autre ligue.</li>
        <li>Un maître ne voit jamais les données d'une autre salle.</li>
        <li>Les résultats et exports PDF sont filtrés par rôle et par périmètre.</li>
        <li>Après 5 échecs de connexion consécutifs, le compte est bloqué temporairement 3 minutes.</li>
    </ul>

    <h3>Étape 6 — Nouveautés propres à cette version</h3>
    <ul>
        <li>Mise à jour directe des grades par un maître, sans attendre une session de passage organisée par sa ligue/fédération.</li>
        <li>Historique des grades par disciple, avec remplissage automatique du verso de la carte de licence.</li>
        <li>Reçus d'inscription et de mensualité envoyables directement par WhatsApp.</li>
        <li>Carte de licence disponible aussi pour les maîtres/responsables ceintures noires et les saisies manuelles.</li>
        <li>Matricule attribué automatiquement pour les ceintures noires saisies manuellement et les maîtres/responsables.</li>
        <li>Application installable sur mobile/ordinateur, mode sombre disponible.</li>
    </ul>

    <h3>Conclusion</h3>
    <div class="box">
        La logique de DojoManager repose sur un ordre de travail clair : Tableau de bord → module métier du rôle →
        validation des actions → consultation des résultats. Respecter cet ordre améliore la lisibilité, la
        sécurité métier et la fiabilité des données.
    </div>

    <p class="muted">Ce document est fourni à titre d'aide utilisateur. Dernière mise à jour : {{ now()->format('d/m/Y') }}.</p>
</body>
</html>
