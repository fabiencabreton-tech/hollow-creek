# Installer Tsukimori sur Alwaysdata (offre gratuite)

Ce guide se suit dans l'ordre, une seule fois. Comptez une heure la première fois.
Aucune installation n'est nécessaire sur votre ordinateur : tout se fait dans le
navigateur (interface Alwaysdata) et dans la console SSH d'Alwaysdata.

Dans tout le guide, **`VOTRECOMPTE`** désigne le nom de votre compte Alwaysdata
(par exemple `hollowcreek`). Remplacez-le partout.

> **Avant de commencer.** L'offre gratuite d'Alwaysdata est réservée à un usage non
> lucratif et à des « besoins personnels ». Écrivez au support d'Alwaysdata pour leur
> demander une confirmation écrite qu'un site communautaire gratuit, sans publicité,
> est accepté sur l'offre gratuite, avant d'ouvrir les inscriptions au public.

---

## Étape 1 — Créer le compte Alwaysdata

1. Créez un compte sur [alwaysdata.com](https://www.alwaysdata.com/fr/) et choisissez l'offre **gratuite**.
2. Donnez au compte le nom qui formera l'adresse du site : un compte `hollowcreek`
   donne le site `https://hollowcreek.alwaysdata.net`.

Je conseille un compte réservé à Tsukimori, séparé de vos autres sites : disque,
mémoire et sauvegardes ne seront pas partagés.

## Étape 2 — Régler PHP

Menu **Environnement › PHP** :

1. Version : **8.4**.
2. Dans le champ **php.ini**, ajoutez ces lignes, puis enregistrez :

   ```ini
   extension = intl.so
   memory_limit = 256M
   expose_php = Off
   ```

   L'extension `intl` est indispensable à l'administration (Filament).

## Étape 3 — Créer la base de données

Menu **Bases de données › MySQL** :

1. **Ajouter une base de données** : nommez-la `VOTRECOMPTE_hollowcreek`.
2. Notez l'utilisateur (par défaut `VOTRECOMPTE`) et son mot de passe, ou créez un
   utilisateur dédié avec tous les droits sur cette base.
3. Si le choix vous est proposé, prenez la version de MariaDB la plus récente (11.8).

L'adresse du serveur est `mysql-VOTRECOMPTE.alwaysdata.net`.

## Étape 4 — Ouvrir la console SSH

Menu **Accès distant › SSH** : vérifiez que l'utilisateur SSH a bien
« Connexion par mot de passe » activée.

Deux façons d'ouvrir la console :

- **Dans le navigateur** (le plus simple) : allez sur `https://ssh-VOTRECOMPTE.alwaysdata.net`
  et connectez-vous avec l'identifiant et le mot de passe SSH ;
- **Depuis un terminal** : `ssh VOTRECOMPTE@ssh-VOTRECOMPTE.alwaysdata.net`.

Toutes les commandes suivantes se tapent dans cette console.

## Étape 5 — Récupérer le code

### Méthode recommandée : depuis le dépôt GitHub privé

1. Dans la console, créez une clé qui permettra au serveur de lire le dépôt :

   ```sh
   ssh-keygen -t ed25519 -C "alwaysdata-hollowcreek" -f ~/.ssh/id_ed25519 -N ""
   cat ~/.ssh/id_ed25519.pub
   ```

2. Copiez la ligne affichée. Sur GitHub, dans le dépôt : **Settings › Deploy keys ›
   Add deploy key**, collez-la, laissez « Allow write access » **décoché**, validez.
3. Récupérez le code (remplacez `VOTRE-NOM-GITHUB`) :

   ```sh
   cd ~
   git clone git@github.com:VOTRE-NOM-GITHUB/hollow-creek.git hollowcreek
   ```

   À la question sur l'authenticité de github.com, répondez `yes`.

### Méthode sans GitHub : envoyer l'archive

Décompressez `hollow-creek.zip` sur votre ordinateur et envoyez le dossier
`hollow-creek` par SFTP (FileZilla : hôte `ssh-VOTRECOMPTE.alwaysdata.net`, port 22)
dans votre dossier personnel, renommé en `hollowcreek`. Les mises à jour se feront
alors en renvoyant les fichiers, puis en lançant `./deploy.sh`.

## Étape 6 — Remplir le fichier de configuration `.env`

```sh
cd ~/hollowcreek
cp .env.example .env
nano .env
```

Dans l'éditeur (flèches pour se déplacer, `Ctrl+O` puis `Entrée` pour enregistrer,
`Ctrl+X` pour quitter), remplacez :

| Ligne | Valeur |
| --- | --- |
| `APP_URL` | `https://VOTRECOMPTE.alwaysdata.net` |
| `DB_HOST` | `mysql-VOTRECOMPTE.alwaysdata.net` |
| `DB_DATABASE` | `VOTRECOMPTE_hollowcreek` |
| `DB_USERNAME` / `DB_PASSWORD` | l'utilisateur et le mot de passe de l'étape 3 |
| `BACKUP_ARCHIVE_PASSWORD` | une longue phrase secrète, à recopier dans votre gestionnaire de mots de passe |
| `BACKUP_NOTIFICATION_EMAIL` | votre adresse e-mail |

Laissez `MAIL_MAILER=log` pour l'instant (voir l'étape 11).

> **À conserver hors du serveur** : une copie du fichier `.env` complet (après
> l'étape 7, qui y ajoute la clé `APP_KEY`). Sans `APP_KEY`, les secrets de double
> authentification et les sessions deviennent illisibles ; sans
> `BACKUP_ARCHIVE_PASSWORD`, les archives de sauvegarde ne s'ouvrent plus.

## Étape 7 — Installer

```sh
cd ~/hollowcreek
chmod +x deploy.sh rollback.sh
./deploy.sh --premiere
```

Le script installe les dépendances, crée la clé de chiffrement, construit la base,
crée les rôles et les pages officielles, puis prépare les caches. La toute première
fois, l'installation des dépendances prend plusieurs minutes.

Si le message « composer.lock absent » apparaît et que l'installation s'interrompt
faute de mémoire, attendez que le dépôt GitHub ait été vérifié une première fois
(il y ajoute automatiquement le fichier `composer.lock`), puis lancez
`git pull && ./deploy.sh --premiere`.

## Étape 8 — Créer le site

Menu **Web › Sites › Ajouter un site** :

| Champ | Valeur |
| --- | --- |
| Adresses | `VOTRECOMPTE.alwaysdata.net` |
| Type | **PHP** |
| Répertoire racine | `/hollowcreek/public` |
| Version PHP | celle de l'environnement (8.4) |

Dans l'onglet **SSL**, cochez **Forcer HTTPS**. Enregistrez.

Ouvrez `https://VOTRECOMPTE.alwaysdata.net` : la page d'accueil de Tsukimori
doit s'afficher, aux couleurs du site.

## Étape 9 — Créer le premier administrateur

```sh
cd ~/hollowcreek
php artisan hollow:admin
```

Indiquez pseudo, e-mail et mot de passe (12 caractères minimum). Puis, sur le site :

1. **Connexion** avec ce compte.
2. **Mon compte › Double authentification › Activer**. Scannez le code avec une
   application d'authentification (Aegis, 2FAS, Google Authenticator, Proton Pass…),
   saisissez le code à six chiffres, puis **notez les codes de secours** en lieu sûr.
3. Le lien **Administration** apparaît dans le menu : `/admin`.

La double authentification est obligatoire pour toute l'équipe ; sans elle,
l'administration renvoie vers « Mon compte ».

Pour donner un rôle à un autre membre de l'équipe : il crée son compte sur le site,
puis **Administration › Comptes › ⋮ › Modifier les rôles**.

## Étape 10 — Ajouter la tâche planifiée

Menu **Avancé › Tâches planifiées › Ajouter une tâche planifiée** :

| Champ | Valeur |
| --- | --- |
| Type | Commande |
| Commande | `php /home/VOTRECOMPTE/hollowcreek/artisan schedule:run` |
| Fréquence | toutes les minutes (`* * * * *`) — à défaut, toutes les 5 minutes |
| E-mails | votre adresse, pour recevoir les rapports d'erreur |

Cette unique tâche fait tout le reste : envoi des e-mails en attente, sauvegarde
hebdomadaire (dimanche 3 h), contrôle quotidien des sauvegardes et ménage.

Vérification : quelques minutes plus tard, **Administration › Tableau de bord ›
État de l'installation** doit afficher « Tâche planifiée : Active ».

## Étape 11 — Envoyer les e-mails

Les e-mails (vérification d'adresse, mot de passe oublié, comptes rendus de
sauvegarde) ont besoin d'une adresse d'expédition sur un **nom de domaine** qui vous
appartient : sans cela, ils finiraient dans les courriers indésirables.

Tant que vous n'avez pas de domaine (`MAIL_MAILER=log`), les e-mails ne partent pas :
ils sont écrits dans `storage/logs/courriels-AAAA-MM-JJ.log` (un fichier par jour). Un
nouvel arrivant ne reçoit donc pas son lien de vérification. Pendant cette période :

- validez vous-même les adresses des testeurs : **Administration › Comptes › ⋮ ›
  Valider l'adresse e-mail** ;
- gardez les inscriptions fermées au public.

Quand le domaine est prêt (acheté chez Alwaysdata ou ailleurs) :

1. **Domaines › Ajouter un domaine**, puis laissez Alwaysdata gérer ses DNS (ou créez
   chez votre registraire les enregistrements indiqués par Alwaysdata).
2. **E-mails › Adresses › Ajouter** : créez par exemple `ne-pas-repondre@votre-domaine.fr`.
3. Activez **DKIM** pour ce domaine (menu du domaine › E-mails) ; l'enregistrement
   SPF est créé automatiquement si les DNS sont chez Alwaysdata.
4. Dans `.env` :

   ```ini
   MAIL_MAILER=smtp
   MAIL_SCHEME=smtps
   MAIL_HOST=smtp-VOTRECOMPTE.alwaysdata.net
   MAIL_PORT=465
   MAIL_USERNAME=ne-pas-repondre@votre-domaine.fr
   MAIL_PASSWORD=le-mot-de-passe-de-cette-adresse
   MAIL_FROM_ADDRESS="ne-pas-repondre@votre-domaine.fr"
   ```

5. `cd ~/hollowcreek && php artisan optimize` pour prendre en compte le changement.
6. Testez avec « Mot de passe oublié » sur votre propre compte, et contrôlez la
   délivrabilité sur [mail-tester.com](https://www.mail-tester.com/?lang=fr).

## Étape 12 — Copie des sauvegardes hors d'Alwaysdata

Indispensable sur l'offre gratuite, qui ne garde que 3 jours de sauvegardes.

**Sans service supplémentaire** : chaque semaine, **Administration › Sauvegardes ›
Télécharger** la plus récente archive et rangez-la sur votre ordinateur ou votre
cloud personnel.

**Automatiquement** (conseillé) : créez un espace de stockage compatible S3 chez un
hébergeur européen, puis renseignez dans `.env` :

```ini
BACKUP_EXTERNAL_BUCKET=nom-du-bucket
BACKUP_EXTERNAL_ENDPOINT=https://adresse-du-service-s3
BACKUP_EXTERNAL_REGION=region-indiquee-par-le-service
BACKUP_EXTERNAL_KEY=cle-d-acces
BACKUP_EXTERNAL_SECRET=cle-secrete
```

puis `php artisan optimize`. La page **Sauvegardes** affiche alors les archives des
deux emplacements. Faites une sauvegarde manuelle pour vérifier.

## Étape 13 — Vérifications finales

- [ ] `https://VOTRECOMPTE.alwaysdata.net` s'affiche, et `http://` redirige vers `https://`.
- [ ] **Administration › Tableau de bord › État de l'installation** : HTTPS, votre
      adresse IP réelle (celle de votre box), tâche planifiée active.
- [ ] Une sauvegarde lancée depuis **Sauvegardes** apparaît quelques minutes plus tard,
      et s'ouvre sur votre ordinateur avec le mot de passe `BACKUP_ARCHIVE_PASSWORD`.
- [ ] Avec un compte joueur de test : inscription (inscriptions ouvertes le temps du
      test dans **Réglages**), connexion, déconnexion, mot de passe oublié.
- [ ] Le compte joueur de test reçoit « accès refusé » sur `/admin`.
- [ ] Une fois tout vérifié, passez `HOLLOW_HSTS=true` dans `.env`, puis `php artisan optimize`.

Les pages légales et le règlement sont des **brouillons** à compléter dans
**Administration › Pages officielles** (passages entre crochets) avant d'ouvrir les
inscriptions.

## En cas de problème

| Symptôme | Piste |
| --- | --- |
| Page blanche ou « Erreur 500 » | `tail -n 50 ~/hollowcreek/storage/logs/laravel-*.log` |
| « could not find driver » ou « intl » | étape 2 (php.ini) |
| « Access denied for user » | identifiants de la base dans `.env` (étape 6), puis `php artisan optimize` |
| Le style ne s'affiche pas | répertoire racine du site = `/hollowcreek/public` (étape 8) |
| Tâche planifiée « À vérifier » | commande et fréquence de l'étape 10 ; journal dans `~/admin/logs/jobs/` |
| Sauvegarde en échec | `cd ~/hollowcreek && php artisan backup:run` affiche l'erreur exacte |

Mettre à jour le site ensuite : voir [exploitation.md](exploitation.md).
