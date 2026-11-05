<?php

namespace App\Support;

use App\Enums\ParticipantStatus;
use App\Models\Post;
use App\Models\Scene;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ZipArchive;

/**
 * Archives lisibles : toutes les scènes en pages HTML autonomes, réunies dans un ZIP,
 * consultables sans le site (un navigateur suffit).
 */
class SceneArchive
{
    /**
     * @return string chemin du fichier ZIP créé (à supprimer après envoi)
     */
    public function build(): string
    {
        $directory = storage_path('app/backup-temp');
        File::ensureDirectoryExists($directory);

        $path = $directory.'/tsukimori-scenes-'.now()->format('Y-m-d-His').'.zip';
        $zip = new ZipArchive;

        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Impossible de créer l’archive des scènes.');
        }

        $entries = [];

        Scene::query()->with('location')->orderBy('id')->chunkById(50, function ($scenes) use ($zip, &$entries): void {
            foreach ($scenes as $scene) {
                $file = 'scenes/'.$scene->getKey().'-'.(Str::slug($scene->title) ?: 'sans-titre').'.html';

                $zip->addFromString($file, view('scenes.export', [
                    'scene' => $scene,
                    'posts' => Post::query()->where('scene_id', $scene->getKey())->with('character')->orderBy('id')->get(),
                    'participants' => $scene->participants()->with('character')->whereIn('status', [ParticipantStatus::Accepted->value, ParticipantStatus::Left->value])->get(),
                ])->render());

                $entries[] = ['file' => $file, 'scene' => $scene];
            }
        });

        $zip->addFromString('index.html', view('scenes.archive-index', ['entries' => $entries])->render());
        $zip->close();

        return $path;
    }
}
