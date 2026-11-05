<?php

use Spatie\Backup\Notifications\Notifiable;
use Spatie\Backup\Notifications\Notifications\BackupHasFailedNotification;
use Spatie\Backup\Notifications\Notifications\BackupWasSuccessfulNotification;
use Spatie\Backup\Notifications\Notifications\CleanupHasFailedNotification;
use Spatie\Backup\Notifications\Notifications\CleanupWasSuccessfulNotification;
use Spatie\Backup\Notifications\Notifications\HealthyBackupWasFoundNotification;
use Spatie\Backup\Notifications\Notifications\UnhealthyBackupWasFoundNotification;
use Spatie\Backup\Tasks\Cleanup\Strategies\DefaultStrategy;
use Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumAgeInDays;
use Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumStorageInMegabytes;

/*
|--------------------------------------------------------------------------
| Sauvegardes de Hollow Creek (spatie/laravel-backup)
|--------------------------------------------------------------------------
|
| Chaque semaine : la base de données et les fichiers envoyés par les joueurs
| sont réunis dans une archive ZIP chiffrée (mot de passe BACKUP_ARCHIVE_PASSWORD).
| L'archive est gardée sur le serveur (disque « sauvegardes ») et, si un stockage
| externe est configuré, copiée hors d'Alwaysdata (disque « sauvegardes-externes »).
| Le code n'est pas sauvegardé ici : il vit dans le dépôt Git.
|
*/

$destinationDisks = array_values(array_filter([
    'sauvegardes',
    filled(env('BACKUP_EXTERNAL_BUCKET')) ? 'sauvegardes-externes' : null,
]));

$maximumMegabytes = (int) (env('BACKUP_MAX_LOCAL_MEGABYTES') ?: 250);

// Une valeur laissée vide dans .env ne doit jamais empêcher le site de démarrer.
$senderAddress = env('MAIL_FROM_ADDRESS') ?: 'equipe@example.com';
$notificationAddress = env('BACKUP_NOTIFICATION_EMAIL') ?: $senderAddress;

return [

    'backup' => [

        'name' => env('BACKUP_NAME', 'hollow-creek'),

        'source' => [

            'files' => [
                // Images et fichiers envoyés par les joueurs.
                'include' => [
                    storage_path('app/public'),
                ],

                'exclude' => [],

                'follow_links' => false,

                'ignore_unreadable_directories' => true,

                'relative_path' => storage_path('app'),
            ],

            'databases' => [
                env('DB_CONNECTION', 'mariadb'),
            ],
        ],

        'database_dump_compressor' => null,

        'database_dump_file_timestamp_format' => null,

        'database_dump_filename_base' => 'database',

        'database_dump_file_extension' => '',

        'destination' => [

            'compression_method' => ZipArchive::CM_DEFAULT,

            'compression_level' => 9,

            'filename_prefix' => 'hollow-creek-',

            'disks' => $destinationDisks,

            // Si le stockage externe est injoignable, l'archive locale est tout de même gardée.
            'continue_on_failure' => true,
        ],

        'temporary_directory' => storage_path('app/backup-temp'),

        // Mot de passe de l'archive : à conserver hors du serveur (gestionnaire de mots de passe).
        'password' => env('BACKUP_ARCHIVE_PASSWORD') ?: null,

        'encryption' => 'default',

        'verify_backup' => true,

        'tries' => 1,

        'retry_delay' => 0,
    ],

    'notifications' => [

        'notifications' => [
            BackupHasFailedNotification::class => ['mail'],
            UnhealthyBackupWasFoundNotification::class => ['mail'],
            CleanupHasFailedNotification::class => ['mail'],
            BackupWasSuccessfulNotification::class => ['mail'],
            HealthyBackupWasFoundNotification::class => [],
            CleanupWasSuccessfulNotification::class => [],
        ],

        'notifiable' => Notifiable::class,

        'mail' => [
            'to' => $notificationAddress,

            'from' => [
                'address' => $senderAddress,
                'name' => env('MAIL_FROM_NAME') ?: 'Hollow Creek',
            ],
        ],

        'slack' => [
            'webhook_url' => '',
            'channel' => null,
            'username' => null,
            'icon' => null,
        ],

        'discord' => [
            'webhook_url' => '',
            'username' => '',
            'avatar_url' => '',
        ],

        'webhook' => [
            'url' => '',
        ],
    ],

    'log_channel' => null,

    'monitor_backups' => [
        [
            'name' => env('BACKUP_NAME', 'hollow-creek'),
            'disks' => $destinationDisks,
            'health_checks' => [
                // Une sauvegarde par semaine : on s'inquiète au-delà de 8 jours.
                MaximumAgeInDays::class => 8,
                MaximumStorageInMegabytes::class => $maximumMegabytes,
            ],
        ],
    ],

    'cleanup' => [

        'strategy' => DefaultStrategy::class,

        'default_strategy' => [
            'keep_all_backups_for_days' => 7,
            'keep_daily_backups_for_days' => 0,
            'keep_weekly_backups_for_weeks' => 8,
            'keep_monthly_backups_for_months' => 6,
            'keep_yearly_backups_for_years' => 1,
            // Garde-fou pour l'offre gratuite (1 Go au total, base et e-mails compris).
            'delete_oldest_backups_when_using_more_megabytes_than' => $maximumMegabytes,
        ],

        'tries' => 1,

        'retry_delay' => 0,
    ],

];
