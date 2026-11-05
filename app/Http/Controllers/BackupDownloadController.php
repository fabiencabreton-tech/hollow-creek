<?php

namespace App\Http\Controllers;

use App\Support\ActivityLogger;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Télécharge une archive de sauvegarde (réservé aux personnes autorisées).
 * Le fichier est envoyé par morceaux, sans être chargé en mémoire.
 */
class BackupDownloadController extends Controller
{
    public function __invoke(string $disk, string $file): StreamedResponse
    {
        abort_unless(in_array($disk, (array) config('backup.backup.destination.disks', []), true), 404);

        $path = config('backup.backup.name').'/'.basename($file);
        $storage = Storage::disk($disk);

        abort_unless($storage->exists($path), 404);

        ActivityLogger::record('sauvegarde.telechargee', description: basename($file), properties: ['disque' => $disk]);

        return $storage->download($path, basename($file), [
            'Content-Type' => 'application/zip',
        ]);
    }
}
