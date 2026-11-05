# Journal des versions

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
