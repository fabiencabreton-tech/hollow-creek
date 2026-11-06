<?php

/*
 * Les quatre grandes familles de Tsukimori, telles que l'équipe les a décrites.
 * Importées une seule fois ; ensuite, tout se modifie dans l'administration.
 */

return [
    'families' => [
        [
            'slug' => 'minase',
            'name' => 'Famille Minase',
            'japanese_name' => '水瀬家',
            'motto' => 'Ceux qui connaissent les eaux du lac',
            'district' => 'Kohan',
            'district_note' => 'près du lac Tsukikage',
            'activities' => 'Navigation, réparation d’embarcations et tourisme lacustre.',
            'reputation' => 'Discrète, travailleuse et attachée aux traditions.',
            'summary' => 'Installés sur les rives du lac Tsukikage depuis plus d’un siècle, les Minase vivent aujourd’hui de leur atelier de réparation d’embarcations et des promenades sur le lac.',
            'history' => <<<'MD'
La famille Minase est installée sur les rives du lac Tsukikage depuis plus d'un siècle.

Autrefois, ses membres vivaient principalement de la pêche et du transport de marchandises entre les villages bordant le lac.

Avec le développement de Tsukimori, leurs activités ont progressivement évolué.

Aujourd'hui, les Minase possèdent un petit atelier de réparation d'embarcations ainsi qu'une entreprise proposant des promenades sur le lac.

Leur maison familiale se trouve près d'un ancien embarcadère.

Très attachés à leur patrimoine, certains membres souhaitent préserver les activités traditionnelles, tandis que d'autres aimeraient moderniser l'entreprise et développer le tourisme.

Ces divergences provoquent régulièrement des tensions entre les générations.
MD,
            'kirisawa_link' => <<<'MD'
Depuis 1997, les Minase participent à l'organisation de la Nuit des lanternes.

Ils mettent notamment des embarcations à disposition pour récupérer les lanternes après la cérémonie.

Certains membres de la famille se souviennent de la grand-mère qui déposait chaque année la quarante-quatrième lanterne en hommage à son petit-enfant à naître.

Après son décès, plusieurs Minase ont tenté de comprendre pourquoi cette lanterne continuait d'apparaître.

Sans succès.

Ils sont donc particulièrement familiers avec la légende, mais **ne connaissent pas sa véritable signification**.
MD,
            'rp_notes' => <<<'MD'
**Possibilités de personnages :** héritiers de l'entreprise, pêcheurs, employés, membres souhaitant quitter Tsukimori ou descendants revenus après plusieurs années d'absence.

**Thématiques :** héritage, traditions, indépendance, attachement familial et mystères du lac.
MD,
        ],
        [
            'slug' => 'fujisawa',
            'name' => 'Famille Fujisawa',
            'japanese_name' => '藤沢家',
            'motto' => 'Ceux qui ont appris à vivre avec l’absence',
            'district' => 'Minamimachi',
            'district_note' => 'et la périphérie agricole',
            'activities' => 'Arboriculture, vergers de pommiers et produits locaux.',
            'reputation' => 'Chaleureuse, travailleuse et particulièrement soudée en apparence.',
            'summary' => 'Les Fujisawa cultivent les vergers de pommiers des collines au sud de la commune. Appréciés de tous, ils sont moins unis qu’ils n’en ont l’air.',
            'history' => <<<'MD'
Les Fujisawa possèdent des terres agricoles dans la vallée de Tsukimori depuis plusieurs générations.

Ils sont principalement connus pour leurs vergers de pommiers, qui occupent une partie des collines situées au sud de la commune.

La famille exploite également une petite boutique proposant des fruits et des produits artisanaux.

Chaque automne, les Fujisawa accueillent des visiteurs venus découvrir les vergers et participer aux récoltes.

Bien que la famille soit généralement appréciée des habitants, les relations entre ses membres ne sont pas toujours aussi harmonieuses qu'elles le paraissent.

Certains descendants souhaitent reprendre l'exploitation, tandis que d'autres rêvent d'une carrière éloignée du monde agricole.

Les questions de succession, de modernisation et de vente éventuelle de certaines terres constituent des sources de désaccord.
MD,
            'kirisawa_link' => <<<'MD'
En 1996, l'un des membres de la famille travaillait à l'hôpital psychiatrique de Kirisawa.

Il a survécu à l'incendie du 31 octobre, mais cette nuit a profondément marqué son existence.

Après la catastrophe, il a quitté son emploi et refusé de raconter précisément ce qu'il avait vécu.

Son silence est devenu un sujet délicat au sein de la famille.

Certains ont respecté sa décision, tandis que d'autres ont tenté d'obtenir des explications.

Les générations suivantes ont grandi avec cette histoire, sans jamais connaître tous les détails.

### Situation en 2026

Le retour des victimes de Kirisawa ravive les souvenirs de la catastrophe.

Certains Fujisawa pourraient chercher à comprendre ce que leur proche a vécu.

D'autres préféreraient ne pas rouvrir les blessures du passé.
MD,
            'rp_notes' => <<<'MD'
**Possibilités de personnages :** héritiers, agriculteurs, étudiants, descendants ayant quitté Tsukimori, membres en conflit avec leurs parents ou personnages souhaitant reprendre l'exploitation.

**Thématiques :** secrets familiaux, attentes, conflits générationnels, deuil et transmission.

**À noter :** il ne faut pas considérer que le survivant de l'incendie connaît obligatoirement la vérité sur Kirisawa. Son silence peut être lié au traumatisme, et non à une dissimulation.
MD,
        ],
        [
            'slug' => 'tachibana',
            'name' => 'Famille Tachibana',
            'japanese_name' => '橘家',
            'motto' => 'Ceux qui conservent les souvenirs',
            'district' => 'Furumachi',
            'district_note' => 'le quartier historique',
            'activities' => 'Photographie, restauration d’images et conservation d’archives photographiques.',
            'reputation' => 'Cultivée, curieuse et parfois indiscrète.',
            'summary' => 'Depuis le milieu du XXe siècle, le studio Tachibana photographie Tsukimori : portraits, mariages, quartiers disparus. Leur collection raconte la ville.',
            'history' => <<<'MD'
Depuis le milieu du XXe siècle, la famille Tachibana possède un studio photographique dans le vieux quartier de Tsukimori.

Pendant plusieurs décennies, les habitants s'y sont rendus pour leurs portraits, photographies de mariage et événements familiaux.

Les Tachibana ont ainsi constitué une importante collection de photographies retraçant l'évolution de la ville.

Certaines représentent des quartiers aujourd'hui transformés, des commerces disparus et des habitants décédés depuis longtemps.

Avec l'arrivée de la photographie numérique, le studio a rencontré des difficultés économiques.

La famille s'est progressivement spécialisée dans la photographie professionnelle et la restauration d'images anciennes.

Aujourd'hui, certains membres souhaitent numériser les archives et les rendre partiellement accessibles au public.

D'autres considèrent que ces souvenirs appartiennent avant tout aux familles qui les ont confiés.

Cette question provoque régulièrement des désaccords.
MD,
            'kirisawa_link' => <<<'MD'
Avant 1996, les Tachibana avaient réalisé plusieurs photographies de l'hôpital psychiatrique.

Certaines représentaient le personnel et les bâtiments de l'établissement.

Après l'incendie, des proches des victimes avaient également demandé des reproductions de photographies anciennes.

La famille possède donc plusieurs images datant d'avant la catastrophe.

En novembre 2026, lorsque des personnes officiellement décédées commencent à réapparaître dans les rues, les archives photographiques prennent une importance inattendue.

Les habitants viennent comparer les visages des Revenus avec ceux des photographies datant de 1996.

La famille pourrait alors être confrontée à de nombreuses sollicitations.
MD,
            'rp_notes' => <<<'MD'
**Possibilités de personnages :** photographes, artistes, restaurateurs d'archives, journalistes, étudiants ou descendants refusant de reprendre le studio.

**Thématiques :** mémoire, identité, nostalgie, vérité et souvenirs.

**À noter :** ces archives ne révèlent pas automatiquement les secrets de Kirisawa.
MD,
        ],
        [
            'slug' => 'katsuragi',
            'name' => 'Famille Katsuragi',
            'japanese_name' => '葛城家',
            'motto' => 'Ceux qui partent, ceux qui reviennent',
            'district' => 'Honmachi',
            'district_note' => 'le centre-ville',
            'activities' => 'Transport local et services de mobilité.',
            'reputation' => 'Indépendante, pragmatique et tournée vers l’extérieur.',
            'summary' => 'L’entreprise de transport des Katsuragi relie Tsukimori au reste de la préfecture. La famille, elle, s’est dispersée de Tokyo à l’étranger.',
            'history' => <<<'MD'
Depuis plusieurs générations, les Katsuragi possèdent une petite entreprise de transport reliant Tsukimori aux communes voisines.

L'activité familiale s'est développée après la Seconde Guerre mondiale, lorsque les déplacements entre les villes de la région sont devenus plus fréquents.

Aujourd'hui, l'entreprise assure différents services de transport local.

Pour de nombreux habitants, les Katsuragi représentent un lien important entre Tsukimori et le reste de la préfecture.

Contrairement aux autres grandes familles, les Katsuragi sont particulièrement dispersés.

Plusieurs descendants vivent désormais à Tokyo, Osaka, Nagoya ou à l'étranger.

Certains ne reviennent à Tsukimori que pendant les vacances.

D'autres n'ont jamais connu la ville autrement qu'à travers les récits de leurs parents.

Cette dispersion a progressivement affaibli les relations entre certaines branches familiales.

### Les tensions familiales

L'avenir de l'entreprise constitue la principale source de conflit.

Certains souhaitent conserver les services locaux malgré les difficultés économiques.

D'autres envisagent une vente ou un rapprochement avec une entreprise plus importante.

Plusieurs descendants refusent également de reprendre une activité qu'ils n'ont jamais choisi d'exercer.
MD,
            'kirisawa_link' => <<<'MD'
Avant l'incendie, certains patients, visiteurs et employés de Kirisawa utilisaient les services de transport de la famille.

Les Katsuragi connaissaient donc l'établissement, sans entretenir de liens particuliers avec sa direction.

En novembre 2026, leurs conducteurs pourraient rencontrer des personnes portant des vêtements anciens ou demandant à rejoindre des lieux qui n'existent plus.

Certains de ces passagers pourraient être des Revenus de Kirisawa.

La famille pourrait ainsi se retrouver indirectement confrontée aux manifestations surnaturelles.
MD,
            'rp_notes' => <<<'MD'
**Possibilités de personnages :** chauffeurs, héritiers, étudiants, membres installés à l'étranger, personnages de retour après plusieurs années ou descendants en conflit avec leur famille.

**Thématiques :** départs, retrouvailles, indépendance, appartenance et reconstruction.
MD,
        ],
    ],

    'relations' => [
        ['minase', 'fujisawa', 'Relations généralement cordiales, avec quelques désaccords concernant le développement touristique de Tsukimori.'],
        ['minase', 'tachibana', 'Collaborations anciennes lors de manifestations locales et d’événements organisés au bord du lac.'],
        ['minase', 'katsuragi', 'Relations professionnelles liées aux transports et aux activités touristiques.'],
        ['fujisawa', 'tachibana', 'Liens anciens, parfois compliqués par des souvenirs ou conflits familiaux.'],
        ['fujisawa', 'katsuragi', 'Divergences possibles entre ceux qui souhaitent préserver les traditions locales et ceux qui privilégient la mobilité.'],
        ['tachibana', 'katsuragi', 'Relations relativement neutres, avec des amitiés et des connaissances communes.'],
    ],
];
