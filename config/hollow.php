<?php

/*
|--------------------------------------------------------------------------
| Réglages propres à Tsukimori (anciennement Hollow Creek)
|--------------------------------------------------------------------------
|
| Les valeurs ci-dessous viennent du fichier .env. Celles qui se modifient
| depuis l'administration (inscriptions, date RP) ne servent ici que de
| valeur de départ, tant que l'équipe ne les a pas enregistrées.
|
*/

return [

    'name' => 'Tsukimori',

    'kanji' => '月森町',

    'tagline' => 'Ceux qui reviennent',

    'motto' => 'Des histoires écrites par des humains, pour des humains.',

    /*
    | Valeurs par défaut des réglages modifiables dans l'administration.
    */
    'defaults' => [
        'registrations_open' => (bool) env('HOLLOW_REGISTRATIONS_OPEN', false),
        'rp_date' => env('HOLLOW_RP_DATE') ?: 'Jeudi 5 novembre 2026',
        'weather' => env('HOLLOW_WEATHER') ?: '',
        'max_characters' => (int) (env('HOLLOW_MAX_CHARACTERS') ?: 3),
        'reservation_days' => (int) (env('HOLLOW_RESERVATION_DAYS') ?: 7),
        'contact_delay' => env('HOLLOW_CONTACT_DELAY') ?: 'Nous répondons en général sous 24 heures.',
    ],

    /*
    | Comptes : longueur du pseudo et âge minimum attesté à l'inscription.
    */
    'accounts' => [
        'pseudo_min' => 3,
        'pseudo_max' => 30,
        'minimum_age' => 18,
    ],

    /*
    | Sécurité.
    | - staff_two_factor : double authentification obligatoire pour accéder à l'administration.
    | - hsts : en-tête Strict-Transport-Security (à n'activer qu'une fois le site servi en HTTPS).
    | - proxy : faire confiance à l'en-tête X-Real-IP envoyé par le proxy de l'hébergeur
    |   (Alwaysdata) pour connaître la véritable adresse IP des visiteurs.
    */
    'security' => [
        'staff_two_factor' => (bool) env('HOLLOW_STAFF_TWO_FACTOR', true),
        'hsts' => (bool) env('HOLLOW_HSTS', false),
        'trust_hosting_proxy' => (bool) env('HOLLOW_TRUST_HOSTING_PROXY', true),
        'trusted_proxy_ranges' => array_filter(array_map('trim', explode(',', (string) env(
            'HOLLOW_TRUSTED_PROXY_RANGES',
            '127.0.0.0/8,::1,10.0.0.0/8,172.16.0.0/12,192.168.0.0/16,fc00::/7'
        )))),
    ],

    /*
    | File d'attente traitée par la tâche planifiée (offre gratuite d'Alwaysdata,
    | où les processus permanents ne sont pas autorisés). À passer à false le jour
    | où un « service » Alwaysdata exécute `php artisan queue:work` en continu.
    */
    /*
    | Recherche : index plein texte de MariaDB. Désactivé pendant les tests ordinaires,
    | car InnoDB n'indexe les lignes qu'une fois la transaction validée.
    */
    'search_fulltext' => (bool) env('HOLLOW_SEARCH_FULLTEXT', true),

    'queue_via_scheduler' => (bool) env('HOLLOW_QUEUE_VIA_SCHEDULER', true),

    /*
    | Sauvegardes.
    */
    'backups' => [
        'notification_email' => env('BACKUP_NOTIFICATION_EMAIL'),
        'external_disk_enabled' => filled(env('BACKUP_EXTERNAL_BUCKET')),
        'max_local_megabytes' => (int) (env('BACKUP_MAX_LOCAL_MEGABYTES') ?: 250),
    ],

    /*
    | Permissions de l'équipe. Le rôle « administrateur » les reçoit toutes ;
    | le rôle « modérateur » reçoit celles listées dans `roles.moderateur`
    | lors de sa création. Les rôles se modifient ensuite depuis l'administration.
    */
    'permissions' => [
        'administration.acceder' => 'Accéder à l’administration',
        'comptes.voir' => 'Consulter les comptes',
        'comptes.moderer' => 'Suspendre et réactiver des comptes, valider une adresse e-mail',
        'comptes.gerer-roles' => 'Attribuer des rôles aux comptes',
        'roles.gerer' => 'Créer et modifier les rôles',
        'pages.gerer' => 'Modifier les pages officielles',
        'univers.gerer' => 'Gérer l’univers : bestiaire, familles, quartiers, lieux, habitations, registre de Kirisawa et archives municipales',
        'enquetes.gerer' => 'Gérer les enquêtes et confier des indices aux personnages',
        'gazette.gerer' => 'Écrire et publier les articles de la Gazette',
        'personnages.valider' => 'Examiner et valider les fiches de personnage',
        'bottin.gerer' => 'Gérer le bottin des avatars et les réservations',
        'predefinis.gerer' => 'Gérer les personnages prédéfinis',
        'signalements.traiter' => 'Traiter les signalements',
        'contact.repondre' => 'Répondre aux demandes de la bulle de contact',
        'evenements.gerer' => 'Gérer les événements',
        'recensements.gerer' => 'Organiser les recensements d’activité',
        'archives.lire' => 'Lire et écrire les archives secrètes de l’équipe (administrateurs)',
        'scenes.moderer' => 'Modérer les scènes : lire les scènes privées, masquer une réponse, changer un statut',
        'reglages.gerer' => 'Modifier les réglages du site',
        'sauvegardes.gerer' => 'Gérer les sauvegardes',
        'journal.voir' => 'Consulter le journal d’activité',
    ],

    'roles' => [
        'administrateur' => [
            'label' => 'Administrateur',
            'permissions' => '*',
        ],
        'moderateur' => [
            'label' => 'Modérateur',
            'permissions' => [
                'administration.acceder',
                'comptes.voir',
                'comptes.moderer',
                'signalements.traiter',
                'contact.repondre',
            ],
        ],
        'joueur' => [
            'label' => 'Joueur',
            'permissions' => [],
        ],
    ],

    /*
    | Fiches de personnage : spécialisations des praticiens de l'occulte (deux au plus,
    | « les personnages ne doivent pas maîtriser toutes les spécialisations »).
    */
    'characters' => [
        'occult_specialties' => [
            'spiritisme' => 'Spiritisme',
            'protection' => 'Protection',
            'divination' => 'Divination',
            'maledictions' => 'Malédictions',
            'rituels' => 'Rituels',
        ],
        'max_occult_specialties' => 2,
    ],

    /*
    | Scènes RP.
    | - content_warnings : avertissements proposés à la création d'une scène ;
    | - dialogue_colors : palette des couleurs de dialogue, lisible en mode nuit
    |   comme en mode parchemin (les teintes sont dans resources/css/app.css) ;
    | - posts_per_page : réponses par page de lecture.
    */
    'scenes' => [
        'posts_per_page' => 20,
        'max_post_length' => 30000,
        'content_warnings' => [
            'violence' => 'Violence',
            'sang' => 'Sang',
            'mort' => 'Mort',
            'deuil' => 'Deuil',
            'horreur' => 'Horreur',
            'phobies' => 'Phobies',
            'addictions' => 'Addictions',
            'sante-mentale' => 'Santé mentale',
        ],
        'dialogue_colors' => [
            'lanterne' => 'Lanterne',
            'vin' => 'Vin',
            'sapin' => 'Sapin',
            'brume' => 'Brume',
            'lilas' => 'Lilas',
            'cuivre' => 'Cuivre',
            'argent' => 'Argent',
        ],
    ],

    /*
    | Notifications : toujours visibles sur le site (la lanterne) ; l'e-mail se règle
    | par type dans « Mon compte » : immédiat, résumé quotidien ou aucun.
    */
    'notifications' => [
        'modes' => [
            'immediat' => 'E-mail immédiat',
            'quotidien' => 'Résumé quotidien',
            'aucun' => 'Pas d’e-mail',
        ],
        'types' => [
            'scene_reponse' => ['label' => 'Nouvelle réponse dans une de mes scènes', 'default' => 'immediat'],
            'scene_tour' => ['label' => 'C’est à moi d’écrire (tour par tour)', 'default' => 'immediat'],
            'scene_invitation' => ['label' => 'Invitation dans une scène', 'default' => 'immediat'],
            'message' => ['label' => 'Message privé', 'default' => 'immediat'],
            'relation' => ['label' => 'Proposition ou réponse de lien', 'default' => 'immediat'],
            'fiche' => ['label' => 'Décision de l’équipe sur une fiche', 'default' => 'immediat'],
            'bottin' => ['label' => 'Réservations d’avatar', 'default' => 'immediat'],
            'equipe' => ['label' => 'Réponse de l’équipe à une demande', 'default' => 'immediat'],
            'murmure' => ['label' => 'Commentaire sur une de mes publications', 'default' => 'quotidien'],
            'evenement' => ['label' => 'Nouvel événement', 'default' => 'quotidien'],
            'recensement' => ['label' => 'Recensement des personnages actifs', 'default' => 'immediat'],
            'indice' => ['label' => 'Nouvel indice pour un de mes personnages', 'default' => 'immediat'],
        ],
    ],

    /*
    | Profils de personnage : mises en page et couleurs d'accent (palette harmonisée,
    | les teintes exactes sont dans resources/css/app.css).
    */
    'profiles' => [
        'layouts' => [
            'classique' => 'Classique : portrait à côté du texte',
            'portrait' => 'Portrait : grand portrait en tête de page',
            'journal' => 'Journal : texte en pleine largeur, portrait en médaillon',
        ],
        'accent_colors' => [
            'lanterne' => 'Lanterne',
            'vin' => 'Vin',
            'sapin' => 'Sapin',
            'brume' => 'Brume',
            'lilas' => 'Lilas',
            'cuivre' => 'Cuivre',
            'argent' => 'Argent',
        ],
    ],

    /*
    | Bulle de contact : motifs proposés, délai de réponse annoncé quand personne
    | de l'équipe n'est en ligne, durée de validité du lien de suivi.
    */
    'contact' => [
        'motifs' => [
            'question' => 'Poser une question',
            'avatar' => 'Réserver un avatar',
            'predefini' => 'Un prédéfini m’intéresse',
            'probleme' => 'Signaler un problème',
        ],
        'token_days' => 30,
    ],

    /*
    | Signalements : motifs proposés.
    */
    'reports' => [
        'reasons' => [
            'harcelement' => 'Harcèlement ou propos blessants',
            'contenu' => 'Contenu inapproprié ou sans avertissement',
            'ia' => 'Texte ou image générés par une IA',
            'droits' => 'Image ou texte utilisés sans autorisation',
            'spam' => 'Publicité ou spam',
            'autre' => 'Autre',
        ],
    ],

    /*
    | Pages officielles fixes : leur adresse est réservée et leur contenu
    | se modifie dans l'administration.
    */
    'pages' => [
        'accueil' => 'Accueil',
        'univers' => 'L’univers',
        'ville' => 'La ville',
        'rejoindre' => 'Rejoindre Tsukimori',
        'faq' => 'Questions fréquentes',
        'reglement' => 'Règlement',
        'mentions-legales' => 'Mentions légales',
        'confidentialite' => 'Politique de confidentialité',
        'conditions' => 'Conditions d’utilisation',
    ],

];
