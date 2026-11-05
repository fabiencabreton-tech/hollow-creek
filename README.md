# Tsukimori — Ceux qui reviennent (月森町)

Plateforme de RPG littéraire surnaturel, **écrite par des humains, pour des humains**,
dans une petite ville fictive de la préfecture de Nagano, trente ans après l'incendie de
l'hôpital de Kirisawa. (Le projet s'appelait Hollow Creek jusqu'en novembre 2026 : le
dépôt et le fichier `config/hollow.php` gardent ce nom.)
Aucune intelligence artificielle n'intervient dans le jeu : ni personnage, ni réponse,
ni texte, ni avatar générés automatiquement.

## Où en est le projet

**Phase 0 — Fondations** (cette version) :

- comptes joueurs : inscription (pseudo, e-mail, mot de passe, attestation de majorité),
  connexion par e-mail ou pseudo, vérification de l'adresse, mot de passe oublié,
  double authentification, liste et déconnexion des appareils ;
- rôles administrateur, modérateur, joueur, et rôles supplémentaires créés sans code ;
- administration (`/admin`) réservée à l'équipe, double authentification obligatoire :
  comptes, rôles et permissions, pages officielles, réglages, sauvegardes, journal ;
- gabarit visuel gothique (mode nuit et mode parchemin), polices hébergées sur le site ;
- sécurité : en-têtes stricts (CSP, anti-intégration), limites anti-abus, champ piège,
  comptes suspendus déconnectés partout, aucune ressource tierce ;
- sauvegardes hebdomadaires chiffrées, copie externe possible ;
- script de mise à jour en une commande, retour arrière, guide d'installation Alwaysdata.

**Phase 1 — Univers et vitrine** : l'univers, les espèces, les familles fondatrices,
la ville (quartiers et lieux), The Hollow Creek Gazette, les pages Rejoindre et FAQ,
tout modifiable dans l'administration, avec des images ré-encodées et créditées.

**Phase 2 — Personnages** : fiche en étapes (six depuis Tsukimori), plusieurs personnages par compte,
bottin des avatars avec réservations et anti-doublon, rôles prédéfinis, validation par
l'équipe, trombinoscope et profils publics.

**Phase 3 — Scènes RP** : scènes publiques et privées, invitations, tour par tour,
éditeur avec sauvegarde automatique, lecture confortable, export des scènes terminées,
modération. Première version jouable (bêta fermée).

**Phase 4 — Communauté** : notifications, messagerie, blocages et signalements,
relations, Murmures, événements, bulle de contact, profils personnalisés.

**Phase 5 — Finitions** : Mes données (export et suppression), recherche, recensement
d'activité, archives lisibles et archives secrètes, barre mobile, audit d'accessibilité
automatisé.

**Tsukimori** (novembre 2026) : le site devient Tsukimori. Bestiaire en catégories
(natures Humains, Yūrei, Onryō, Revenus, Rappelés ; spécialisations Médiums et
Praticiens de l'occulte ; condition Maudits) avec fiches en neuf rubriques, étape
« Nature » dans la fiche de personnage, registre des 43 victimes de Kirisawa (réservation
automatique des Revenus), cinq quartiers et vingt-trois lieux, carte interactive de la
ville.

Les six phases prévues sont livrées. Les évolutions possibles (temps réel, statistiques
d'écriture, plan interactif de la ville…) figurent dans le document d'architecture.

## Documentation

- [Installer sur Alwaysdata](docs/installation-alwaysdata.md) — pas à pas, offre gratuite
- [Mettre à jour et entretenir](docs/exploitation.md)
- [Sauvegardes et restauration](docs/sauvegardes-et-restauration.md)
- [Journal des versions](CHANGELOG.md)

## Technique

| | |
| --- | --- |
| Langage | PHP 8.4 (8.3 minimum) |
| Framework | Laravel 13, Laravel Fortify (comptes et double authentification) |
| Administration | Filament 5 |
| Rôles | spatie/laravel-permission |
| Sauvegardes | spatie/laravel-backup |
| Base de données | MariaDB 11.8 (SQLite pour les tests) |
| Interface | Blade, CSS sur mesure, Vite ; polices Cormorant Garamond, Literata, Alegreya Sans (licence OFL) |
| Tests | PHPUnit, lancés par GitHub Actions sur SQLite et MariaDB à chaque envoi |

### Organisation du code

```
app/
  Actions/Fortify/     règles d'inscription, de profil et de mot de passe
  Filament/            administration (ressources, pages, widgets)
  Http/Middleware/     sécurité : en-têtes, proxy Alwaysdata, suspension, double authentification
  Models/              User, Page, Setting, ActivityLog
  Policies/            qui peut faire quoi (vérifié côté serveur)
  Support/             réglages, journal, rôles et permissions, sauvegardes
config/hollow.php      réglages propres à Tsukimori (rôles, permissions, pages)
database/seeders/pages/ textes de départ des pages officielles (Markdown)
resources/views/       gabarits du site public et de l'espace joueur
deploy.sh, rollback.sh mise à jour et retour arrière sur le serveur
```

### Développer en local (facultatif)

```sh
composer install
npm install
cp .env.example .env    # puis APP_ENV=local, APP_DEBUG=true, DB_CONNECTION=sqlite
php artisan key:generate
php artisan migrate --seed
php artisan hollow:admin
npm run dev             # dans un second terminal : php artisan serve
php artisan test
```

Après toute modification de `resources/css` ou `resources/js`, lancez `npm run build`
et ajoutez `public/build` au dépôt : le serveur n'a pas besoin de Node.js.

## Règles du projet

- Une fonctionnalité n'est terminée que si elle fonctionne de bout en bout, que ses
  permissions sont vérifiées par des tests (refus compris), qu'elle est utilisable sur
  téléphone et documentée.
- Les secrets de l'intrigue ne sont jamais écrits dans le code ni dans le dépôt.
