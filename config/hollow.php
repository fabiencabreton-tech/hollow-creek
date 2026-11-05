<?php

/*
|--------------------------------------------------------------------------
| Réglages propres à Hollow Creek
|--------------------------------------------------------------------------
|
| Les valeurs ci-dessous viennent du fichier .env. Celles qui se modifient
| depuis l'administration (inscriptions, date RP) ne servent ici que de
| valeur de départ, tant que l'équipe ne les a pas enregistrées.
|
*/

return [

    'name' => 'Hollow Creek',

    'tagline' => 'Ceux qui reviennent',

    'motto' => 'Des histoires écrites par des humains, pour des humains.',

    /*
    | Valeurs par défaut des réglages modifiables dans l'administration.
    */
    'defaults' => [
        'registrations_open' => (bool) env('HOLLOW_REGISTRATIONS_OPEN', false),
        'rp_date' => env('HOLLOW_RP_DATE', 'Jeudi 5 novembre 2026'),
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
    'queue_via_scheduler' => (bool) env('HOLLOW_QUEUE_VIA_SCHEDULER', true),

    /*
    | Sauvegardes.
    */
    'backups' => [
        'notification_email' => env('BACKUP_NOTIFICATION_EMAIL'),
        'external_disk_enabled' => filled(env('BACKUP_EXTERNAL_BUCKET')),
        'max_local_megabytes' => (int) env('BACKUP_MAX_LOCAL_MEGABYTES', 250),
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
            ],
        ],
        'joueur' => [
            'label' => 'Joueur',
            'permissions' => [],
        ],
    ],

    /*
    | Pages officielles fixes : leur adresse est réservée et leur contenu
    | se modifie dans l'administration.
    */
    'pages' => [
        'accueil' => 'Accueil',
        'reglement' => 'Règlement',
        'mentions-legales' => 'Mentions légales',
        'confidentialite' => 'Politique de confidentialité',
        'conditions' => 'Conditions d’utilisation',
    ],

];
