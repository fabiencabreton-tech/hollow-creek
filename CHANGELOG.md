# Journal des versions

## Palette « Crépuscule de cerisier » (novembre 2026)

- Nouvelle palette, choisie parmi cinq propositions : prune et mauve du ciel au couchant,
  rose des cerisiers, or des lanternes, vermillon du sceau ; les papiers (Gazette, cartes)
  restent clairs. Remplace l'ardoise de la refonte « Brume ».
- Mode clair accordé : papier rosé, encre prune, liens rouge cerise (contraste AA).
- Les ombres posées sur les illustrations, l'en-tête et la carte de la ville prennent les
  teintes de la palette (variables de couleur partout : changer de palette ne demande plus
  que les valeurs de départ).
- Icône de l'application, couleur de la barre du navigateur, page hors connexion et
  administration aux nouvelles couleurs ; l'application installée remplace ses fichiers
  gardés (nouvelle version du script d'arrière-plan).


## Refonte « Brume » (novembre 2026)

- Nouvelle charte d'après la maquette de l'équipe : ardoise et gris bleuté du lac dans la
  brume, papier des journaux, lueur ambrée des lanternes, rose des cerisiers. Titres en
  Cormorant Garamond, lecture en Literata, japonais en Shippori Mincho ; pictogrammes au
  trait (torii, cerisier, lanterne, montagnes, lune…).
- Illustrations d'ambiance fournies par l'équipe, converties en WebP en deux tailles : lac
  et torii (accueil), rue sous la pluie (Gazette), forêt dans la brume (contexte),
  sanctuaire (espèces), maison à la lanterne (maisons, personnages), pluie (scènes), shoji
  (prédéfinis, murmures), hôpital de Kirisawa (registre, archives), lanternes (événements).
  Elles habillent aussi les fiches encore sans image ; une image envoyée par l'équipe passe
  toujours avant. Mention en pied de page, modifiable dans Réglages › Illustrations du site.
- En-tête de la maquette : Accueil, Contexte, Maisons, Espèces, Gazette, Carte et un menu
  RPG (scènes, personnages, rôles prédéfinis, murmures, événements, enquêtes, bottin,
  petites annonces, archives) ; recherche et compte dans des menus ; tiroir sur téléphone.
- Accueil : le lac Tsukikage en grand avec ses quarante-trois lanternes jaunes et la
  quarante-quatrième, blanche, qui flottent ; quatre entrées (contexte, maisons, Gazette,
  carte) ; « À la une » et derniers articles ; puis la vitrine (mystères, qui incarner,
  maisons, ville, en ce moment, principes, rejoindre).
- Gazette « 月森新聞 » : bandeau, rubriques avec pictogrammes (dont Actualités locales et
  Rumeurs et témoignages), article à la une sur papier, dernières publications, recherche,
  archives par année, dossier spécial (nouveau champ « dossier » des articles). Un article
  propose son dossier et d'autres lectures.
- Espèces : écriture japonaise facultative (幽霊, 怨霊), affichée sur les cartes.
- Mode parchemin et réduction des animations conservés ; administration aux couleurs du
  site.


## Notifications push (novembre 2026)

- Mon compte › « Notifications sur vos appareils » : activer ou désactiver les alertes sur
  ce téléphone ou cet ordinateur, liste des appareils abonnés (retrait un par un),
  notification d'essai. Une case « Sur mes appareils » par type de notification.
- Chaque notification du site (réponse, tour d'écriture, invitation, message, décision de
  l'équipe…) part aussi sur les appareils abonnés ; la toucher ouvre la bonne page.
  L'alerte ne contient qu'un titre et une adresse, jamais le texte d'un RP ou d'un message.
- Chiffrement (RFC 8291) et signature VAPID (RFC 8292) écrits avec OpenSSL, sans
  dépendance ; envois uniquement vers les services push des navigateurs (Google, Mozilla,
  Apple, Microsoft). Abonnements expirés oubliés automatiquement.
- Clés créées une fois par « php artisan hollow:cles-push », lancé par deploy.sh.
- iPhone et iPad : notifications disponibles une fois Tsukimori ajouté à l'écran d'accueil.
- Vérifié dans Chromium à chaque modification : abonnement, alerte chiffrée et signée reçue
  par un service push simulé puis déchiffrée par un code indépendant, affichage par
  l'application, retrait de l'appareil.


## Application installable (novembre 2026)

- Tsukimori s'installe comme une application sur Android, iPhone, iPad et ordinateur
  (manifeste, icônes au sceau 月森町, plein écran, raccourcis Tableau de bord, Mes scènes,
  Messages). Page « Installer l'application » avec bouton direct quand le navigateur le
  permet et pas à pas pour Safari ; lien en pied de page et invitation sur le tableau de
  bord (téléphone, tant que l'application n'est pas installée).
- Sans réseau : page « hors connexion ». Seuls les styles, polices et icônes sont gardés
  sur l'appareil, jamais une page du site (scènes privées et messages restent au serveur).
- Bouton « retour » dans l'application installée, qui n'a pas celui du navigateur.
- Vérification dans un vrai Chromium à chaque modification : manifeste, installabilité,
  contenu gardé sur l'appareil, page hors connexion.
- Icônes régénérables : node scripts/generer-icones.mjs "$PWD" (après npm run build).


## Accueils, tableau de bord et validation (novembre 2026)

- Accueil vitrine pour les visiteurs : les trois mystères (Kirisawa, la quarante-quatrième
  lanterne, ceux qui reviennent), les catégories jouables, les quatre familles, les rôles
  prédéfinis, la carte interactive de la ville, l'actualité, un appel à rejoindre répété
  (et toujours à portée sur téléphone). Un membre connecté arrive sur son tableau de bord.
- Tableau de bord du joueur : personnage actif (choisi en un clic, proposé en premier dans
  tous les formulaires, rappelé dans l'en-tête), RP à répondre, en attente et brouillons,
  courrier et notifications, scènes ouvertes et préliens, actualités et événements.
- Accueil des comptes en attente : étapes de l'inscription, état de chaque fiche,
  message de l'équipe en cas de modification demandée ou de refus, quoi lire en attendant.
- Administration : « À valider » rassemble fiches, réservations d'avatar, demandes de
  famille et inscriptions non confirmées ; filtre par type, aperçu complet et décision
  (accepter, demander une modification, refuser) dans la même fenêtre, motif obligatoire
  hors acceptation, membre prévenu ; « Décisions » garde l'historique avec les motifs.
- Lac de l'accueil : quarante-trois lanternes jaunes et la quarante-quatrième, blanche,
  flottent doucement ; la brume glisse sur l'eau. Option « Réduire les animations » en
  pied de page, en plus du réglage du système.
- Crédits des images facultatifs (avatar, bannière, murmures, administration), avec le
  rappel de les indiquer quand la licence l'exige ; règlement mis à jour.


## Les grandes familles de Tsukimori (novembre 2026)

- Rubrique « Les grandes familles de Tsukimori » : Minase (水瀬家), Fujisawa (藤沢家),
  Tachibana (橘家) et Katsuragi (葛城家), avec nom japonais, devise, quartier, activités,
  réputation, histoire, lien public avec Kirisawa, conseils de jeu et liens entre familles
  (affichés sur les deux fiches). Les familles de Hollow Creek ne reviennent pas.
- Notes secrètes par famille, réservées à l'administration et jamais affichées.
- Arbre généalogique interactif (survol ou clavier : la lignée s'éclaire), avec une
  version en liste ; parents non joués ajoutés par l'équipe ; chaque joueur place son
  propre personnage (parents, conjoint, mention), sans boucle possible.
- Rejoindre une famille : créer un personnage directement dans la famille (lien validé
  avec la fiche), ou demander l'entrée d'un personnage déjà accepté ; proposer sa propre
  famille. L'équipe accepte ou refuse dans « Demandes de famille », le joueur est prévenu.
- Préliens familiaux publiés par les membres (frère, sœur, cousin, parent, conjoint…),
  à côté des prédéfinis de l'équipe ; réponse en message privé, « pourvu », retrait.
- L'étape « Identité » de la fiche regroupe les grandes familles et les familles des
  joueurs, et rappelle qu'aucune n'est obligatoire.


## Parcours du nouvel arrivant (novembre 2026)

- Vérification automatique, dans un vrai navigateur, à chaque envoi : inscription,
  refus d'un mot de passe trop court, lien de vérification reçu par e-mail, création
  d'un personnage en six étapes avec image d'avatar, envoi de la fiche, pages du compte,
  déconnexion, reconnexion par le pseudo, mot de passe oublié. Toute erreur JavaScript,
  ressource refusée ou erreur serveur fait échouer la vérification.
- Les e-mails non envoyés (`MAIL_MAILER=log`) sont désormais écrits dans
  `storage/logs/courriels-AAAA-MM-JJ.log`, quel que soit `LOG_LEVEL` (avec
  `LOG_LEVEL=warning`, ils n'étaient écrits nulle part).


## Refonte visuelle « Nuit de Tsukimori » (novembre 2026)

- Nouvelle palette : encre sumi et indigo de nuit, papier washi, vermillon des torii,
  ambre des lanternes, bleu-gris du lac ; mode parchemin repensé en papier washi.
- Polices auto-hébergées : Shippori Mincho (titres et kanji 月森町), Zen Kaku Gothic New
  (interface), Literata (lecture), IBM Plex Sans pour les voyelles longues (ū, ō).
- En-tête en deux niveaux : sceau vermillon 月森町, recherche toujours visible,
  navigation soulignée au pinceau ; menu repensé sur téléphone.
- Accueil : lune au-dessus du lac Tsukikage, torii dans l'eau, quarante-trois lanternes
  flottantes et la quarante-quatrième à part (animation coupée si l'utilisateur réduit
  les mouvements).
- Cartes, tuiles, titres de section, boutons, champs et pied de page (vagues seigaiha)
  redessinés ; carte de la ville aux couleurs de la nuit et filtres alignés.
- Contrastes vérifiés (WCAG 2.1 AA) dans les deux modes par l'audit automatisé.


## Immersion (novembre 2026)

### Ajouté

- Archives municipales (`/archives`) : documents rédigés par l'équipe (série, cote, date
  dans l'univers, lieu, quartier), recherche et filtres par série et par année.
- Petites annonces (`/annonces`) : signées par un personnage accepté, sept rubriques,
  expiration après 7, 14 ou 30 jours, prolongation, cinq annonces en ligne au plus par
  compte, réponse en message privé, signalement et retrait par la modération.
- Journaux personnels (`/personnages/…/journal`) : entrées publiques ou privées ; une
  entrée privée n'est servie qu'à son auteur et à la modération des scènes.
- Habitations (`/ville/habitations`) : par quartier, avec capacité ; un personnage
  emménage seul dans une habitation libre qui a de la place (verrou contre les
  dépassements), déménage, et libère sa place à l'archivage.
- Enquêtes et indices (`/enquetes`, `/mes-indices`) : indices publics ou confiés à des
  personnages par l'équipe, transmissibles d'un personnage à l'autre si l'équipe le
  permet, avec notification « Nouvel indice ». Le contrôle d'accès se fait sur le serveur.
- Administration : groupe « Immersion » (archives, habitations, enquêtes, indices,
  annonces) et permission « enquetes.gerer ».
- Export des données : journaux et annonces ; les annonces sont supprimées avec le compte.


## Tsukimori — Ceux qui reviennent (novembre 2026)

Évolution du site existant : comptes, scènes, communauté, administration et sauvegardes
sont conservés. Le contenu de test de Hollow Creek est remplacé à la mise à jour.

### Ajouté

- Nouveau nom et nouvel univers : Tsukimori (月森町), l'incendie de Kirisawa, la
  quarante-quatrième lanterne, le retour des morts ; pages Accueil, Univers, Ville,
  Rejoindre, FAQ et Règlement réécrites à partir du texte de l'équipe.
- Bestiaire : natures (Humains, Yūrei, Onryō, Revenus, Rappelés), spécialisations
  (Médiums, Praticiens de l'occulte) et conditions (Maudits), chacune avec sa fiche en
  neuf rubriques (description, origine, caractéristiques physiques, longévité, capacités,
  faiblesses, restrictions, particularités RP, conditions de création), modifiables dans
  l'administration (« Bestiaire »).
- Fiche de personnage en six étapes, avec une étape « Nature » : nature unique,
  spécialisations et conditions compatibles, date et circonstances du décès, attache et
  émotion des esprits, spécialités occultes (deux au plus), malédiction (origine, effets,
  rupture), capacités et faiblesses. Les champs utiles sont exigés à l'envoi.
- Registre de Kirisawa : 43 emplacements gérés par l'équipe (notes internes jamais
  affichées) ; un Revenu incarne une victime ouverte, réservée à l'envoi de la fiche,
  attribuée à l'acceptation, rendue en cas de refus ou d'archivage.
- Cinq quartiers et vingt-trois lieux ; carte interactive de la ville (recherche,
  filtres par quartier et par type, description, illustration, scènes publiques en cours,
  « Commencer un RP ici ») ; position des lieux réglable dans l'administration.
- Profils publics : nature, particularités, victime du registre et onglet « Nature ».

### Modifié

- Les familles fondatrices de Hollow Creek sont retirées ; le module Familles reste.
- Les anciennes espèces deviennent les nouvelles catégories (les personnages de test
  sont rattachés automatiquement).


## Phase 5 — Finitions (novembre 2026)

### Ajouté

- « Mes données » (Mon compte) : téléchargement de tout ce que le joueur a écrit, en page
  lisible (HTML) et en format réutilisable (JSON) ; suppression du compte, avec les
  réponses gardées sous « Ancien membre » (par défaut) ou effacées au choix ; avatars
  libérés, personnages supprimés. Prévu à la phase 4, livré ici.
- Recherche dans tout le site (personnages, scènes, célébrités, lieux, Gazette) avec
  l'index plein texte de MariaDB ; une scène privée n'apparaît que pour ses participants.
- Recensement d'activité : l'équipe le lance, chaque joueur confirme ses personnages,
  les non recensés peuvent être archivés à l'échéance (absences annoncées épargnées).
- Archives lisibles : toutes les scènes en pages HTML dans un ZIP, depuis la page
  Sauvegardes ; archives secrètes de l'équipe, réservées aux administrateurs.
- Barre de navigation de l'espace joueur sur téléphone.
- Audit d'accessibilité automatisé à chaque envoi (axe-core, WCAG 2.1 AA, modes nuit et
  parchemin, ordinateur et téléphone, pages publiques et espace joueur) : corrections du
  libellé de la bulle de contact sur téléphone et du contraste des avertissements.
- Performance : cache navigateur d'un an pour les images envoyées, compression des pages.

## Phase 4 — Communauté (novembre 2026)

### Ajouté

- Notifications sur le site (la lanterne, compteur rafraîchi chaque minute) pour les
  scènes, invitations, tours de jeu, messages, liens, fiches, réservations, réponses de
  l'équipe, commentaires et événements ; e-mail réglable par type : immédiat, résumé
  quotidien (7 h 40) ou aucun. Le réglage « e-mails des scènes » de la phase 3 est repris.
- Messagerie entre joueurs : conversations à deux ou en groupe (8 au plus), non-lus,
  archives, recherche, nouveaux messages affichés toutes les 15 secondes. L'équipe n'y a
  pas accès, sauf aux messages signalés.
- Blocage d'un joueur : plus de message, d'invitation de scène ni de proposition de lien.
- Signalements (réponse, message, profil, publication, commentaire) : seul l'extrait
  signalé est transmis ; motif « texte ou image générés par une IA » ; file de
  modération avec masquage du contenu.
- Relations entre personnages : proposées, acceptées, chacun sa description, fin du lien.
- Murmures : pensées, courts textes, images créditées, annonces ; commentaires signés.
- Événements de l'équipe : inscription d'un personnage, scènes et articles rattachés,
  annonce à tous les joueurs à la première publication.
- Bulle de contact : équipe en ligne ou délai annoncé, dossiers HC-XXXX, lien de suivi
  secret (empreinte seule en base, 30 jours, renouvelable), « Mes demandes », boîte de
  réception de l'équipe avec notes internes, attribution et clôture.
- Demande d'avatar sans compte, confirmée par e-mail et rattachée au compte créé avec la
  même adresse.
- Profils : bannière créditée, couleur d'accent, trois mises en page, onglets Histoire,
  Caractère, Liens, RP, Publications, statut d'absence.
- Permissions « signalements.traiter », « contact.repondre » (données aux modérateurs à la
  création du rôle) et « evenements.gerer ».

## Phase 3 — Scènes RP (novembre 2026)

### Ajouté

- Ouverture d'une scène : personnage, lieu, titre, date RP, visibilité publique ou
  privée, scène ouverte à qui veut la rejoindre, avertissements de contenu, ordre de
  passage libre ou tour par tour, partenaires invités, introduction.
- Invitations acceptées avec le personnage de son choix, ou déclinées ; retrait d'une
  invitation ; arrivée dans une scène ouverte ; départ d'une scène.
- Éditeur : gras, italique, dialogue, citation, séparateur ; compteur de mots ; aperçu
  (avec ou sans JavaScript) ; brouillon enregistré automatiquement sur le serveur et
  copie de secours dans le navigateur si la connexion tombe.
- Modification de ses réponses avec mention « modifiée le… » ; versions précédentes
  conservées pour l'équipe.
- Lecture : 20 réponses par page, saut à la première réponse non lue, taille du texte
  réglable, couleur de dialogue par personnage, impression propre.
- Statuts en cours, en pause, terminée, archivée ; export HTML (imprimable en PDF)
  des scènes terminées.
- Scènes privées : réponse 404 pour quiconque n'y participe pas ; lecture par l'équipe
  (permission « scenes.moderer ») journalisée.
- E-mails : invitation, nouvelle réponse, « à vous d'écrire » ; préférence dans Mon compte.
- Mes scènes, tableau de bord (invitations, à vous, non-lus, brouillons), profils
  (scènes en cours, RP terminés), lieux (scènes en cours, « Ouvrir une scène ici »),
  accueil (dernières scènes).
- Administration : scènes, réponses, versions, masquage et restauration, statut,
  suppression restaurable.

## Phase 2 — Personnages (novembre 2026)

### Ajouté

- Fiche de personnage en cinq étapes (identité, avatar, histoire, personnalité,
  envoi), brouillon enregistré à chaque étape, compteur de mots, attestation
  d'écriture humaine à l'envoi ; personnages majeurs (âge réel et apparent).
- Plusieurs personnages par compte, plafond réglable (3 par défaut) ; « Mes
  personnages » et carte du tableau de bord.
- Bottin des avatars : recherche insensible aux accents et majuscules, statut
  Disponible / Demande en cours / Réservé / Pris, demandes (3 en cours maximum),
  réservation de 7 jours prolongeable une fois, rappel la veille, libération
  automatique ; anti-doublon garanti par un index unique en base.
- Avatars : célébrités réelles uniquement, image ré-encodée, crédit obligatoire.
- Rôles prédéfinis : page publique, « Je veux ce rôle », fiche pré-remplie, rôle
  verrouillé à l'envoi, libéré en cas de refus.
- Validation par l'équipe : accepter, demander des modifications (commentaire),
  refuser (motif), archiver ; e-mail au joueur ; historique des décisions ;
  impossible de valider sa propre fiche.
- Trombinoscope filtrable par espèce et famille, profils publics avec le
  traitement visuel de l'espèce ; fiches non acceptées visibles seulement par
  leur auteur et l'équipe.
- Administration : fiches, réservations, bottin, prédéfinis ; nouvelles
  permissions « personnages.valider », « bottin.gerer », « predefinis.gerer ».

## Phase 1 — Univers et vitrine (novembre 2026)

### Ajouté

- Pages publiques : L’univers, les espèces (une page par espèce), les familles
  fondatrices, la ville (quartiers et lieux), The Hollow Creek Gazette (rubriques,
  article à la une, pagination), Rejoindre et Questions fréquentes.
- Administration : espèces (traitement visuel des portraits, capacités, limites,
  pistes de jeu), familles (histoire publique, rôles ouverts), quartiers, lieux,
  articles de la Gazette (signature fictive ou « La rédaction », date RP,
  brouillon, publication programmée, mise à la une) ; ordre d’affichage par
  glisser-déposer ; aperçu des brouillons réservé à l’équipe.
- Permissions « univers.gerer » et « gazette.gerer » : un rôle « rédaction de la
  Gazette » se crée sans code.
- Images : formats contrôlés d’après le contenu, ré-encodage WebP sans
  métadonnées, redimensionnement, noms aléatoires, crédit obligatoire,
  suppression de l’ancien fichier au remplacement.
- Accueil : météo de la ville (réglable), une de la Gazette, accès à l’univers.
- Contenu de départ repris du cahier des charges (contexte, six espèces, quatre
  familles, Old Creek District, neuf lieux), importé une seule fois ; mairie,
  commissariat, lycée, campus et parc créés en brouillon, à rédiger.
- Police UnifrakturMaguntia pour le bandeau de la Gazette.
- Tests : affichage, brouillons, secret absent des pages, administration,
  images (67 tests sur SQLite et MariaDB).

## Phase 0 — Fondations (novembre 2026)

### Ajouté

- Socle Laravel 13 configuré en français (fuseau Europe/Paris), MariaDB, sessions en base.
- Comptes : inscription avec pseudo unique (insensible aux accents et majuscules),
  attestation de majorité et acceptation des conditions ; connexion par e-mail ou
  pseudo ; vérification de l'adresse ; mot de passe oublié ; mots de passe de
  12 caractères minimum, refusés s'ils figurent dans une fuite connue (production).
- Double authentification par application (codes de secours inclus), obligatoire
  pour l'équipe ; liste et déconnexion des autres appareils.
- Rôles administrateur, modérateur, joueur ; permissions détaillées ; création de
  nouveaux rôles depuis l'administration.
- Administration `/admin` : comptes (rôles, suspension motivée, validation d'adresse,
  réinitialisation de double authentification), rôles et permissions, pages officielles
  en Markdown, réglages (inscriptions, date RP), sauvegardes, journal d'activité,
  tableau de bord avec état de l'installation.
- Site public : accueil, règlement et pages légales (brouillons à compléter),
  pages d'erreur et de maintenance, mode parchemin.
- Sécurité : Content-Security-Policy avec nonce, protection anti-intégration,
  limites de débit (connexion, double authentification, inscription, mot de passe
  oublié), champ piège anti-robots, adresse IP réelle derrière le proxy Alwaysdata,
  déconnexion immédiate des comptes suspendus, avatars de l'administration générés
  sur place (aucun service tiers).
- Sauvegardes hebdomadaires chiffrées (base et images), nettoyage automatique,
  surveillance, copie externe S3 facultative, téléchargement depuis l'administration.
- `deploy.sh` (mise à jour en une commande), `rollback.sh` (retour arrière),
  commande `hollow:admin`, tâche planifiée unique.
- Tests automatiques (comptes, accès à l'administration, en-têtes, proxy) et
  vérifications GitHub Actions sur SQLite et MariaDB 11.8.
