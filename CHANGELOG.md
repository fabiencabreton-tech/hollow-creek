# Journal des versions

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
