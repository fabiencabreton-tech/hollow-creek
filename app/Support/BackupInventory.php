<?php

namespace App\Support;

use Spatie\Backup\BackupDestination\Backup;
use Spatie\Backup\BackupDestination\BackupDestination;
use Throwable;

/**
 * Liste des archives de sauvegarde, sur le serveur et sur le stockage externe.
 */
class BackupInventory
{
    /**
     * @return array<int, array{disk: string, disk_label: string, file: string, date: string, size: string, reachable: bool}>
     */
    public function all(): array
    {
        $rows = [];

        foreach ($this->disks() as $disk) {
            $destination = BackupDestination::create($disk, (string) config('backup.backup.name'));

            try {
                $backups = $destination->backups();
            } catch (Throwable) {
                continue;
            }

            foreach ($backups as $backup) {
                /** @var Backup $backup */
                $rows[] = [
                    'disk' => $disk,
                    'disk_label' => $this->label($disk),
                    'file' => basename($backup->path()),
                    'date' => $backup->date()->timezone(config('app.timezone'))->locale('fr')->isoFormat('dddd D MMMM YYYY, HH:mm'),
                    'timestamp' => $backup->date()->getTimestamp(),
                    'size' => $this->humanSize($backup->sizeInBytes()),
                    'reachable' => true,
                ];
            }
        }

        usort($rows, fn (array $a, array $b): int => $b['timestamp'] <=> $a['timestamp']);

        return $rows;
    }

    /**
     * @return array<int, string>
     */
    public function disks(): array
    {
        return array_values((array) config('backup.backup.destination.disks', []));
    }

    public function delete(string $disk, string $file): bool
    {
        if (! in_array($disk, $this->disks(), true)) {
            return false;
        }

        $destination = BackupDestination::create($disk, (string) config('backup.backup.name'));

        foreach ($destination->backups() as $backup) {
            if (basename($backup->path()) === basename($file)) {
                $backup->delete();

                return true;
            }
        }

        return false;
    }

    public function newestDate(): ?string
    {
        $newest = null;

        foreach ($this->disks() as $disk) {
            try {
                $backup = BackupDestination::create($disk, (string) config('backup.backup.name'))->newestBackup();
            } catch (Throwable) {
                continue;
            }

            if ($backup && ($newest === null || $backup->date()->greaterThan($newest->date()))) {
                $newest = $backup;
            }
        }

        return $newest?->date()->timezone(config('app.timezone'))->locale('fr')->diffForHumans();
    }

    public function label(string $disk): string
    {
        return $disk === 'sauvegardes' ? 'Serveur Alwaysdata' : 'Stockage externe';
    }

    protected function humanSize(float $bytes): string
    {
        $units = ['o', 'Ko', 'Mo', 'Go'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return number_format($bytes, $i === 0 ? 0 : 1, ',', ' ').' '.$units[$i];
    }
}
