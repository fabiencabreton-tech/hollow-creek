<?php

/*
|--------------------------------------------------------------------------
| Contenu de départ de l'univers
|--------------------------------------------------------------------------
| Textes repris tels quels du cahier des charges « PROJET HOLLOW CREEK »
| rédigé par l'équipe. Rien n'est inventé : les rubriques encore vides
| (capacités, limites, pistes de jeu, histoires détaillées) sont à écrire
| dans l'administration. Le mystère d'Alderwick n'apparaît nulle part ici.
*/

return [

    'species' => [
        ['name' => 'Humains', 'treatment' => 'humain', 'summary' => 'Habitants ordinaires, étudiants, policiers, médecins, journalistes, commerçants et nouveaux arrivants. Certains connaissent le surnaturel, d’autres l’ignorent complètement.'],
        ['name' => 'Revenants', 'treatment' => 'revenant', 'summary' => 'Personnes décédées lors de l’incendie d’Alderwick en 1996 et revenues physiquement en 2026. Elles semblent avoir conservé l’âge qu’elles avaient au moment de leur mort et possèdent des souvenirs incomplets.'],
        ['name' => 'Fantômes', 'treatment' => 'fantome', 'summary' => 'Esprits de personnes décédées, généralement invisibles aux humains ordinaires. Ils peuvent être attachés à un lieu, un objet ou une personne.'],
        ['name' => 'Médiums', 'treatment' => 'medium', 'summary' => 'Humains capables de percevoir les esprits, de communiquer avec eux ou de ressentir certaines manifestations surnaturelles.'],
        ['name' => 'Sorcières et sorciers', 'treatment' => 'sorciere', 'summary' => 'Individus pratiquant une magie ancienne fondée sur les rituels, les protections, les malédictions et les liens entre les vivants et les morts.'],
        ['name' => 'Chasseurs de spectres', 'treatment' => 'chasseur', 'summary' => 'Individus spécialisés dans l’étude, la recherche ou la neutralisation des manifestations paranormales.'],
    ],

    'families' => [
        ['name' => 'Les Whitmore', 'summary' => 'Ancienne famille de notables ayant participé au financement d’Alderwick. Elle possède plusieurs propriétés et exerce une influence économique importante.'],
        ['name' => 'Les Blackwell', 'summary' => 'Ancienne lignée de sorcières, associée aux protections magiques de la ville et aux traditions occultes.'],
        ['name' => 'Les Ashford', 'summary' => 'Famille historiquement liée aux services funéraires, aux cimetières et aux registres des décès.'],
        ['name' => 'Les Holloway', 'summary' => 'Famille influente dans les institutions municipales, la police et la justice. L’un de ses membres dirigeait l’enquête sur l’incendie de 1996.'],
    ],

    'districts' => [
        [
            'name' => 'Old Creek District',
            'summary' => 'Old Creek est le quartier historique de Hollow Creek.',
            'description' => "Ses rues sont bordées de demeures victoriennes, de jardins anciens et de propriétés transmises depuis plusieurs générations.\n\nPlusieurs familles fondatrices y résident encore.",
        ],
        [
            'name' => 'Centre-ville',
            'summary' => null,
            'description' => null,
        ],
    ],

    'locations' => [
        [
            'name' => 'The Willow Café',
            'kind' => 'Café',
            'district' => 'Centre-ville',
            'summary' => 'Situé au cœur du centre-ville, le Willow Café est un établissement familial fréquenté depuis plusieurs générations.',
            'description' => "Ses banquettes confortables, ses grandes fenêtres et ses pâtisseries maison en font un endroit apprécié des habitants.\n\nOn y retrouve des étudiants, des employés venus prendre leur petit-déjeuner et des habitués qui connaissent les propriétaires depuis des années.\n\nC’est un lieu idéal pour les rencontres quotidiennes, les discussions et les rendez-vous.",
        ],
        [
            'name' => 'Blackwood Books',
            'kind' => 'Librairie',
            'district' => null,
            'summary' => 'Blackwood Books est une librairie indépendante installée dans un ancien bâtiment de briques rouges.',
            'description' => "Ses étagères accueillent des romans contemporains, des éditions anciennes et plusieurs ouvrages consacrés à l’histoire locale.\n\nUne petite salle de lecture se trouve à l’étage.\n\nLa librairie organise régulièrement des événements littéraires.\n\nCertains documents pourraient également contenir des informations sur les légendes de Hollow Creek.",
        ],
        [
            'name' => 'The Last Stop',
            'kind' => 'Bar',
            'district' => null,
            'summary' => 'The Last Stop est un bar populaire auprès des jeunes adultes.',
            'description' => "Sa décoration mêle bois sombre, affiches anciennes et lumières tamisées.\n\nL’établissement propose régulièrement des concerts, des soirées musicales et des événements à thème.\n\nIl constitue un lieu important pour les rencontres, les romances et les intrigues sociales.",
        ],
        [
            'name' => 'Hollow Creek Medical Center',
            'kind' => 'Hôpital',
            'district' => null,
            'summary' => 'L’hôpital actuel accueille les habitants de la ville et des communes voisines.',
            'description' => "Il possède des urgences, plusieurs services médicaux et des consultations spécialisées.\n\nCertains membres du personnel connaissent l’histoire d’Alderwick.\n\nDepuis le retour des premières victimes de l’incendie, plusieurs médecins sont confrontés à des phénomènes qu’ils ne parviennent pas à expliquer.",
        ],
        [
            'name' => 'L’Institut psychiatrique d’Alderwick',
            'kind' => 'Ruines',
            'district' => null,
            'summary' => 'Construit à l’écart de Hollow Creek, l’Institut Alderwick fut autrefois l’un des bâtiments les plus imposants de la région.',
            'description' => "Son architecture austère, ses longs couloirs et ses nombreuses ailes lui conféraient une réputation inquiétante.\n\nAprès l’incendie du 31 octobre 1996, l’établissement fut définitivement fermé.\n\nAujourd’hui, les ruines sont officiellement interdites d’accès.\n\nUne partie du bâtiment s’est effondrée, mais certains sous-sols seraient restés relativement intacts.\n\nD’anciens employés évoquent l’existence de salles qui n’apparaissaient sur aucun plan officiel.",
        ],
        [
            'name' => 'Saint Agnes Cemetery',
            'kind' => 'Cimetière',
            'district' => null,
            'summary' => 'Saint Agnes est le plus ancien cimetière de Hollow Creek.',
            'description' => "Situé à la lisière de la forêt, il accueille les sépultures de plusieurs générations d’habitants.\n\nUn mémorial y rend hommage aux victimes d’Alderwick.\n\nLe cimetière est également connu pour les manifestations spirituelles que certains habitants affirment avoir observées.",
        ],
        [
            'name' => 'Blackpine Forest',
            'kind' => 'Forêt',
            'district' => null,
            'summary' => 'Blackpine Forest est une immense forêt entourant Hollow Creek.',
            'description' => "Elle possède des sentiers de randonnée, d’anciennes cabanes de chasse et plusieurs secteurs difficilement accessibles.\n\nLes habitants s’y promènent régulièrement, mais certaines parties de la forêt sont associées à des légendes locales.",
        ],
        [
            'name' => 'Hollow Lake',
            'kind' => 'Lac',
            'district' => null,
            'summary' => 'Hollow Lake est l’un des principaux attraits naturels de la ville.',
            'description' => "Il possède des pontons, une petite plage aménagée et plusieurs cabanes sur ses rives.\n\nL’été, les habitants viennent s’y baigner ou pratiquer des activités nautiques.\n\nChaque 31 octobre, la Nuit des Lanternes y est organisée.",
        ],
        [
            'name' => 'The Hollow Creek Gazette',
            'kind' => 'Journal',
            'district' => null,
            'summary' => 'Le Hollow Creek Gazette est le journal local.',
            'description' => 'Il publie des actualités, des annonces, des faits divers et des informations sur les événements communautaires.',
        ],
    ],

    // Lieux souhaités dont la description reste à écrire : créés en brouillon, invisibles du public.
    'draft_locations' => [
        ['name' => 'Mairie', 'kind' => 'Institution'],
        ['name' => 'Commissariat', 'kind' => 'Institution'],
        ['name' => 'Lycée', 'kind' => 'Établissement scolaire'],
        ['name' => 'Campus universitaire', 'kind' => 'Établissement scolaire'],
        ['name' => 'Parc municipal', 'kind' => 'Parc'],
    ],

];
