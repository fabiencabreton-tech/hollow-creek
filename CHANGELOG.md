# Journal des versions

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
