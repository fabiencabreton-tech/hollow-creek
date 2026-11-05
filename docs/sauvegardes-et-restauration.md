# Sauvegardes et restauration

## Ce qui est sauvegardé, et où

| Quoi | Par qui | Fréquence | Conservation |
| --- | --- | --- | --- |
| Tout le compte (fichiers, bases, e-mails) | Alwaysdata | chaque jour | 3 jours (offre gratuite), 7 jours (Plus Small) |
| Base de données + images envoyées par les joueurs, dans une archive ZIP chiffrée | Hollow Creek | chaque dimanche à 3 h, et à la demande | 8 semaines, puis une par mois pendant 6 mois, dans la limite de `BACKUP_MAX_LOCAL_MEGABYTES` |
| Copie de ces archives hors d'Alwaysdata | Hollow Creek, si un stockage externe est configuré | à chaque sauvegarde | selon les mêmes règles |
| Le code | GitHub | à chaque modification | historique complet |

Chaque sauvegarde Hollow Creek envoie un compte rendu à `BACKUP_NOTIFICATION_EMAIL`,
et une alerte si aucune sauvegarde récente n'existe.

**Deux secrets à garder hors du serveur**, dans un gestionnaire de mots de passe :
le fichier `.env` complet (dont `APP_KEY`) et `BACKUP_ARCHIVE_PASSWORD`.

## Ouvrir une archive

1. **Administration › Sauvegardes › Télécharger**.
2. Ouvrez le fichier avec 7-Zip (Windows), Keka (Mac) ou `unzip` (Linux) : le mot de
   passe demandé est `BACKUP_ARCHIVE_PASSWORD`.
3. L'archive contient :
   - `db-dumps/database.sql` (ou un nom proche) : toute la base, au format SQL standard ;
   - `public/` : les images envoyées par les joueurs (quand il y en aura).

## Restaurer la base de données

> Toute restauration remplace les données actuelles. Faites d'abord une sauvegarde de
> l'état présent (`php artisan backup:run`), même s'il est abîmé.

1. Mettez le site en maintenance : `cd ~/hollowcreek && php artisan down --render="errors::503"`.
2. Envoyez `database.sql` dans votre dossier personnel par SFTP.
3. Importez-le :

   ```sh
   mysql -h mysql-VOTRECOMPTE.alwaysdata.net -u VOTRECOMPTE -p VOTRECOMPTE_hollowcreek < ~/database.sql
   ```

   (ou par phpMyAdmin : base `VOTRECOMPTE_hollowcreek` › Importer).
4. Recopiez au besoin le dossier `public/` de l'archive dans `~/hollowcreek/storage/app/public/`.
5. `php artisan optimize:clear && php artisan up`.

Pour revenir à l'état d'un des jours précédents sans archive Hollow Creek, utilisez
**Avancé › Restauration de sauvegardes** dans l'interface Alwaysdata.

## Restauration d'essai

À la fin de chaque phase du projet, faites une restauration d'essai : importez la
dernière archive dans une base de test vide (créée dans **Bases de données › MySQL**)
et vérifiez que les tables contiennent bien les comptes. Une sauvegarde qui n'a
jamais été relue n'est pas une sauvegarde.

## Déménager chez un autre hébergeur

Il suffit d'un hébergeur avec PHP 8.3 ou plus et MariaDB (ou MySQL) :

1. récupérer le code depuis GitHub, copier `.env` et l'adapter (base, adresse du site) ;
2. importer `database.sql`, recopier le dossier des images ;
3. lancer `./deploy.sh --premiere` puis faire pointer le site vers le dossier `public`.

Aucun service propre à Alwaysdata n'est nécessaire au fonctionnement.
