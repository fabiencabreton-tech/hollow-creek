<?php

/*
|--------------------------------------------------------------------------
| Contenu de départ de l'univers de Tsukimori
|--------------------------------------------------------------------------
| Textes repris du document « TSUKIMORI — CEUX QUI REVIENNENT » rédigé par
| l'équipe, sans rien inventer : les rubriques que le document ne couvre pas
| restent vides et s'écrivent dans l'administration. Les positions sur la
| carte (map_x, map_y, en %) sont une proposition, ajustable elle aussi.
*/

return [

    'species' => [
        [
            'slug' => 'humains',
            'name' => 'Humains',
            'kind' => 'nature',
            'treatment' => 'humain',
            'summary' => 'Les humains constituent la majorité des habitants de Tsukimori. Ils ne possèdent aucune capacité surnaturelle particulière.',
            'description' => "Certains connaissent les légendes locales, tandis que d'autres refusent de croire à l'existence des fantômes.\n\nIls peuvent exercer n'importe quelle profession cohérente avec l'univers et participer librement aux intrigues quotidiennes ou surnaturelles.",
            'longevity' => 'Espérance de vie humaine.',
            'abilities' => "Aucune capacité magique innée.\n\nIls peuvent éventuellement développer une sensibilité spirituelle ou étudier l'occulte (voir les médiums et les praticiens de l'occulte).",
            'weaknesses' => 'Vulnérables aux phénomènes surnaturels.',
        ],
        [
            'slug' => 'yurei',
            'name' => 'Yūrei',
            'kind' => 'nature',
            'treatment' => 'yurei',
            'needs_death_date' => true,
            'summary' => 'Les Yūrei sont les esprits de personnes décédées qui demeurent dans le monde des vivants.',
            'description' => "Ils conservent leur personnalité, leurs émotions et une grande partie de leurs souvenirs.\n\nCertains restent attachés à un lieu ou à une personne, tandis que d'autres peuvent se déplacer plus librement.",
            'physical' => "Les Yūrei ne sont pas constamment invisibles aux humains ordinaires : ils peuvent se manifester physiquement ou visuellement selon certaines conditions.\n\nIls ne possèdent pas en permanence un corps aussi stable que celui d'un Revenu.",
            'longevity' => "Les fantômes ne vieillissent pas et ne peuvent pas mourir une seconde fois de façon ordinaire.\n\nLeur disparition définitive ou leur passage vers l'au-delà obéit à des règles spécifiques.",
            'abilities' => "- Devenir visibles aux humains ordinaires, volontairement ou involontairement.\n- Redevenir invisibles.\n- Communiquer avec les vivants lorsqu'ils sont manifestés.\n- Traverser certaines surfaces matérielles lorsqu'ils sont immatériels.\n- Toucher temporairement des objets ou des personnes.\n- Provoquer de légères variations de température.\n- Se déplacer sans être systématiquement attachés au lieu de leur décès.",
            'weaknesses' => "La matérialisation demande de l'énergie spirituelle. Plus un fantôme cherche à interagir physiquement avec le monde, plus il s'épuise.",
            'rp_notes' => "Un Yūrei peut participer normalement à des RP avec des humains.\n\nL'objectif est de permettre aux Yūrei de développer des relations, des romances et des intrigues avec les vivants, sans être condamnés à rester de simples spectateurs invisibles.",
        ],
        [
            'slug' => 'onryo',
            'name' => 'Onryō',
            'kind' => 'nature',
            'parent' => 'yurei',
            'treatment' => 'onryo',
            'needs_death_date' => true,
            'summary' => 'Les Onryō sont une catégorie particulière de Yūrei, retenus dans le monde des vivants par une émotion particulièrement intense.',
            'description' => "Rancœur, colère, tristesse, sentiment d'injustice ou désir de vengeance : une émotion intense les retient parmi les vivants.\n\nCependant, **ils ne sont pas nécessairement malveillants**. Certains cherchent simplement à obtenir réparation, à protéger quelqu'un ou à comprendre les circonstances de leur mort.",
            'abilities' => "Ils possèdent les facultés des Yūrei, mais leurs manifestations peuvent être plus intenses. Ils peuvent notamment :\n\n- Apparaître aux humains ordinaires.\n- Se matérialiser temporairement.\n- Produire des phénomènes paranormaux dans leur environnement.\n- Influencer certaines sensations ou émotions de manière limitée.\n- Provoquer des cauchemars ou des manifestations liées à leur passé.\n- Perturber temporairement certains objets électroniques.",
            'weaknesses' => "Leurs capacités dépendent de leur état émotionnel. Une manifestation trop intense peut les épuiser ou leur faire perdre momentanément le contrôle.",
            'limits' => "Ils ne peuvent pas tuer, blesser ou contrôler un autre personnage sans l'accord de son joueur.",
            'rp_notes' => "Les Onryō doivent rester jouables dans des intrigues quotidiennes et relationnelles. Ils ne sont pas systématiquement des monstres ou des antagonistes.",
        ],
        [
            'slug' => 'revenus',
            'name' => 'Revenus',
            'kind' => 'nature',
            'treatment' => 'revenu',
            'needs_death_date' => true,
            'needs_registry' => true,
            'summary' => 'Les Revenus sont des personnes officiellement décédées dans l’incendie de Kirisawa du 31 octobre 1996, qui réapparaissent à partir du 1er novembre 2026.',
            'description' => "Contrairement aux Yūrei ordinaires, **les Revenus possèdent une enveloppe physique stable**. Ils peuvent être vus, entendus et touchés par tout le monde.",
            'origin' => "Ils commencent mystérieusement à réapparaître à partir du 1er novembre 2026.\n\nLeur corps n'est pas nécessairement leur véritable cadavre : il pourrait s'agir d'une manifestation surnaturelle particulièrement stable. Leur nature exacte reste mystérieuse.",
            'physical' => "- Apparence identique à celle de 1996.\n- Aucun vieillissement durant les trente années écoulées.\n- Personnalité et souvenirs majoritairement conservés.\n- Absence de souvenirs des trente années séparant leur mort de leur retour.",
            'longevity' => 'Ils ne vieillissent pas.',
            'abilities' => "- Capacité à parler et interagir physiquement.\n- Possibilité de ressentir certaines sensations humaines.",
            'weaknesses' => 'Leur fonctionnement biologique peut présenter certaines anomalies.',
            'limits' => "Ils ne possèdent pas automatiquement de pouvoirs supplémentaires.\n\nIls ne peuvent pas devenir invisibles ou traverser les murs comme les Yūrei ordinaires.",
            'creation_rules' => "Seules les personnes figurant parmi les 43 victimes officielles de Kirisawa peuvent appartenir à cette catégorie.\n\nLe [registre de Kirisawa](/registre-de-kirisawa) indique les victimes que les joueurs peuvent incarner ; chaque fiche est validée par l'équipe.",
        ],
        [
            'slug' => 'rappeles',
            'name' => 'Rappelés',
            'kind' => 'nature',
            'treatment' => 'rappele',
            'needs_death_date' => true,
            'is_rare' => true,
            'summary' => 'Les Rappelés sont des individus réellement décédés dont le corps a mystérieusement recommencé à fonctionner partiellement : des zombies conscients.',
            'description' => "Contrairement aux Revenus, ils retrouvent leur **véritable corps physique**.\n\nIls conservent leur conscience, leur personnalité, leurs souvenirs et leurs émotions. Ils ne sont pas des zombies agressifs ou dépourvus d'intelligence.",
            'origin' => 'Leur origine reste mystérieuse.',
            'physical' => "- Corps physique d'origine.\n- Absence de battements cardiaques.\n- Température corporelle anormalement basse.\n- Besoins biologiques très différents de ceux des humains.\n\nLeur apparence reste humaine et leur corps ne se décompose pas normalement.",
            'longevity' => 'Absence de vieillissement.',
            'abilities' => "- Conscience et souvenirs conservés.\n- Capacité à vivre et interagir normalement en société.",
            'weaknesses' => "Ils peuvent être blessés et ne sont pas invincibles.\n\nUne destruction irréversible de leur corps peut entraîner leur disparition définitive.",
            'limits' => "Les Rappelés ne possèdent aucune force surhumaine.\n\nIls ne peuvent pas transmettre leur état par morsure ou contamination.",
            'creation_rules' => 'Cette catégorie est rare et soumise à validation administrative.',
        ],
        [
            'slug' => 'mediums',
            'name' => 'Médiums',
            'kind' => 'specialisation',
            'allowed_natures' => ['humains'],
            'treatment' => 'medium',
            'summary' => 'Les médiums sont des humains naturellement sensibles au monde des esprits.',
            'origin' => 'Leurs capacités peuvent apparaître dès l’enfance ou se développer après un événement marquant.',
            'abilities' => "Tous les médiums ne possèdent pas les mêmes dons. Capacités possibles :\n\n- Percevoir la présence d'un esprit.\n- Entendre ou voir certains fantômes.\n- Communiquer avec les défunts.\n- Ressentir les émotions associées à un lieu.\n- Recevoir des visions fragmentées ou des impressions surnaturelles.",
            'weaknesses' => 'Ils restent physiquement humains et peuvent être affectés par leurs capacités.',
            'limits' => "Les médiums ne peuvent pas lire automatiquement les pensées des autres personnages, connaître tous les secrets d'un fantôme ou prédire précisément l'avenir.",
            'creation_rules' => "Un personnage peut éventuellement être médium et praticien de l'occulte, sous réserve de restrictions pour éviter une accumulation excessive de capacités.",
        ],
        [
            'slug' => 'praticiens-de-l-occulte',
            'name' => 'Praticiens de l’occulte',
            'kind' => 'specialisation',
            'allowed_natures' => ['humains'],
            'treatment' => 'occulte',
            'summary' => 'Les praticiens de l’occulte sont des humains ayant acquis des connaissances surnaturelles par l’apprentissage, les recherches ou la transmission familiale.',
            'origin' => "Contrairement aux médiums, leurs capacités ne sont pas nécessairement innées.\n\nIls utilisent des rituels, des symboles et des objets particuliers pour interagir avec certaines forces surnaturelles.",
            'abilities' => "Spécialisations possibles :\n\n- **Spiritisme :** entrer en contact avec les esprits grâce à certains rituels.\n- **Protection :** créer des talismans, des sceaux ou des barrières temporaires.\n- **Divination :** interpréter des présages et des manifestations surnaturelles.\n- **Malédictions :** étudier, identifier ou tenter d'atténuer certaines malédictions.\n- **Rituels :** accomplir des cérémonies aux effets surnaturels limités.",
            'weaknesses' => 'Les rituels nécessitent une préparation et peuvent échouer.',
            'limits' => "Les praticiens ne peuvent pas ressusciter librement les morts, contrôler d'autres personnages ou résoudre automatiquement les mystères de Kirisawa.",
            'creation_rules' => "Les personnages ne doivent pas maîtriser toutes les spécialisations.\n\nUn personnage peut éventuellement être médium et praticien, sous réserve de restrictions pour éviter une accumulation excessive de capacités.",
        ],
        [
            'slug' => 'maudits',
            'name' => 'Maudits',
            'kind' => 'condition',
            'treatment' => 'maudit',
            'summary' => 'Les Maudits sont des individus affectés par une malédiction surnaturelle. Il ne s’agit pas d’une espèce indépendante.',
            'description' => "Un humain, un médium ou un autre personnage surnaturel peut être maudit.",
            'origin' => 'Les malédictions peuvent avoir différentes origines : rituels, objets, événements surnaturels ou rencontres avec certaines entités.',
            'abilities' => "Manifestations possibles :\n\n- Cauchemars récurrents liés à un événement.\n- Apparitions que le personnage est le seul à percevoir.\n- Incapacité temporaire à quitter un lieu.\n- Altérations physiques ponctuelles.\n- Sensibilité accrue à certaines manifestations surnaturelles.\n- Phénomènes étranges affectant son quotidien.",
            'limits' => 'Les malédictions ne doivent pas servir à justifier des pouvoirs illimités.',
            'creation_rules' => 'Chaque malédiction doit posséder des effets clairement définis, des conséquences et éventuellement une possibilité de résolution.',
        ],
    ],

    'districts' => [
        ['name' => 'Honmachi', 'summary' => 'Centre-ville et commerces.', 'map' => [52, 47]],
        ['name' => 'Furumachi', 'summary' => 'Quartier historique.', 'map' => [80, 38]],
        ['name' => 'Kohan', 'summary' => 'Quartier du lac.', 'map' => [30, 64]],
        ['name' => 'Kitayama', 'summary' => 'Montagnes, forêts et ruines de Kirisawa.', 'map' => [48, 12]],
        ['name' => 'Minamimachi', 'summary' => 'Quartier résidentiel.', 'map' => [62, 84]],
    ],

    'locations' => [
        // Lieux importants
        ['name' => 'Lac Tsukikage', 'kind' => 'Nature', 'district' => 'Kohan', 'summary' => 'Le lac qui borde Tsukimori.', 'map' => [17, 57]],
        ['name' => 'Rivière Kirisawa', 'kind' => 'Nature', 'district' => 'Kitayama', 'summary' => null, 'map' => [44, 30]],
        ['name' => 'Forêt de Kuroyama', 'kind' => 'Nature', 'district' => 'Kitayama', 'summary' => null, 'map' => [24, 18]],
        ['name' => 'Sanctuaire Mizukami', 'kind' => 'Lieu sacré', 'district' => 'Furumachi', 'summary' => null, 'map' => [86, 26]],
        ['name' => 'Temple Jōren', 'kind' => 'Lieu sacré', 'district' => 'Furumachi', 'summary' => null, 'map' => [74, 28]],
        ['name' => 'Pont Yūgure', 'kind' => 'Lieu important', 'district' => 'Kohan', 'summary' => null, 'map' => [37, 47]],
        ['name' => 'Ancien hôpital psychiatrique de Kirisawa', 'kind' => 'Lieu important', 'district' => 'Kitayama', 'summary' => 'Les ruines de l’hôpital psychiatrique de Kirisawa (霧沢精神病院), surnommé « l’hôpital des Brumes », ravagé par l’incendie du 31 octobre 1996, sur les hauteurs boisées de Tsukimori.', 'map' => [63, 9]],
        // Commerces
        ['name' => 'Kissa Komorebi', 'kind' => 'Commerce', 'district' => 'Honmachi', 'summary' => 'Café.', 'map' => [45, 52]],
        ['name' => 'Kohaku Shobō', 'kind' => 'Commerce', 'district' => 'Honmachi', 'summary' => 'Librairie.', 'map' => [53, 55]],
        ['name' => 'Yamabiko Shokudō', 'kind' => 'Commerce', 'district' => 'Honmachi', 'summary' => 'Restaurant familial.', 'map' => [60, 46]],
        ['name' => 'Kogetsudō', 'kind' => 'Commerce', 'district' => 'Furumachi', 'summary' => 'Pâtisserie.', 'map' => [73, 43]],
        ['name' => 'Hananooto', 'kind' => 'Commerce', 'district' => 'Honmachi', 'summary' => 'Fleuriste.', 'map' => [48, 59]],
        ['name' => 'Mikazuki Ryokan', 'kind' => 'Commerce', 'district' => 'Kohan', 'summary' => 'Auberge.', 'map' => [31, 72]],
        ['name' => 'Sakurayu', 'kind' => 'Commerce', 'district' => 'Furumachi', 'summary' => 'Bains publics.', 'map' => [82, 47]],
        ['name' => 'Tsukimi', 'kind' => 'Commerce', 'district' => 'Honmachi', 'summary' => 'Izakaya.', 'map' => [62, 55]],
        // Infrastructures
        ['name' => 'Mairie', 'kind' => 'Institution', 'district' => 'Honmachi', 'summary' => null, 'map' => [52, 42]],
        ['name' => 'Gare de Tsukimori', 'kind' => 'Transport', 'district' => 'Honmachi', 'summary' => null, 'map' => [57, 64]],
        ['name' => 'Konbini', 'kind' => 'Commerce', 'district' => 'Honmachi', 'summary' => null, 'map' => [41, 58]],
        ['name' => 'Clinique', 'kind' => 'Santé', 'district' => 'Minamimachi', 'summary' => null, 'map' => [71, 78]],
        ['name' => 'École primaire', 'kind' => 'École', 'district' => 'Minamimachi', 'summary' => null, 'map' => [51, 79]],
        ['name' => 'Collège', 'kind' => 'École', 'district' => 'Minamimachi', 'summary' => null, 'map' => [58, 90]],
        ['name' => 'Lycée', 'kind' => 'École', 'district' => 'Minamimachi', 'summary' => null, 'map' => [77, 88]],
        ['name' => 'Habitations', 'kind' => 'Habitation', 'district' => 'Minamimachi', 'summary' => null, 'map' => [65, 82]],
    ],

    // Archives secrètes de l'équipe (jamais publiées sur le site).
    'lore' => [
        [
            'title' => 'Rappelés et quarante-quatrième lanterne',
            'category' => 'Intrigue',
            'body' => "Il pourrait exister un lien entre les Rappelés et les noms apparaissant sur la quarante-quatrième lanterne, mais il ne faut pas considérer que chaque personne nommée devient automatiquement un Rappelé.\n\nL'origine des Rappelés doit rester mystérieuse.",
        ],
    ],

    // Contenu de Hollow Creek, retiré lors du passage à Tsukimori.
    'hollow_creek' => [
        'species' => [
            'revenants' => 'revenus',
            'fantomes' => 'yurei',
            'mediums' => 'mediums',
            'sorcieres-et-sorciers' => 'praticiens-de-l-occulte',
            'chasseurs-de-spectres' => 'humains',
        ],
        'families' => ['Les Whitmore', 'Les Blackwell', 'Les Ashford', 'Les Holloway'],
        'districts' => ['Old Creek District', 'Centre-ville'],
        'locations' => [
            'The Willow Café', 'Blackwood Books', 'The Last Stop', 'Hollow Creek Medical Center',
            'L’Institut psychiatrique d’Alderwick', 'Saint Agnes Cemetery', 'Blackpine Forest', 'Hollow Lake',
            'The Hollow Creek Gazette', 'Mairie', 'Commissariat', 'Lycée', 'Campus universitaire', 'Parc municipal',
        ],
        'pages' => ['accueil', 'univers', 'ville', 'rejoindre', 'faq'],
    ],

];
