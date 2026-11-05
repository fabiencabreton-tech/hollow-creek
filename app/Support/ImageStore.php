<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Enregistrement sécurisé des images envoyées sur le site.
 *
 * Chaque image est décodée puis ré-encodée (WebP, ou JPEG si WebP est indisponible) :
 * le fichier final ne contient que des pixels, sans métadonnées (position GPS, appareil…)
 * ni code caché. Le nom est aléatoire et le dossier n'exécute jamais de script.
 */
class ImageStore
{
    public const DISK = 'public';

    public const MAX_BYTES = 2 * 1024 * 1024;

    private const MAX_PIXELS = 40_000_000;

    /**
     * Formats acceptés, contrôlés d'après le contenu réel du fichier.
     */
    private const ACCEPTED_TYPES = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP, IMAGETYPE_GIF];

    /**
     * Dossiers autorisés : un chemin enregistré en base doit toujours en venir.
     */
    public const DIRECTORIES = ['univers', 'gazette', 'avatars'];

    /**
     * @return string Chemin relatif sur le disque public, ex. « univers/Ab3….webp ».
     *
     * @throws ValidationException
     */
    public function store(UploadedFile $file, string $directory, int $maxWidth = 1600): string
    {
        if (! in_array($directory, self::DIRECTORIES, true)) {
            throw new \InvalidArgumentException("Dossier d'images inconnu : {$directory}");
        }

        $data = $file->get();

        if (! is_string($data) || $data === '' || strlen($data) > self::MAX_BYTES) {
            $this->fail('L’image doit peser au plus 2 Mo.');
        }

        $info = @getimagesizefromstring($data);

        if ($info === false || ! in_array($info[2] ?? null, self::ACCEPTED_TYPES, true)) {
            $this->fail('Seules les images JPEG, PNG, WebP et GIF sont acceptées.');
        }

        [$width, $height] = $info;

        if ($width < 1 || $height < 1 || $width * $height > self::MAX_PIXELS) {
            $this->fail('Les dimensions de l’image ne sont pas acceptées.');
        }

        $image = @imagecreatefromstring($data);

        if ($image === false) {
            $this->fail('Cette image est illisible.');
        }

        $image = $this->applyOrientation($image, $data, $info[2]);

        if (imagesx($image) > $maxWidth) {
            $scaled = imagescale($image, $maxWidth, -1, IMG_BICUBIC);

            if ($scaled !== false) {
                imagedestroy($image);
                $image = $scaled;
            }
        }

        if (! imageistruecolor($image)) {
            imagepalettetotruecolor($image);
        }

        imagealphablending($image, false);
        imagesavealpha($image, true);

        [$binary, $extension] = $this->encode($image);
        imagedestroy($image);

        $path = $directory.'/'.Str::random(40).'.'.$extension;

        Storage::disk(self::DISK)->put($path, $binary, 'public');

        return $path;
    }

    public function delete(?string $path): void
    {
        if (static::isManagedPath($path)) {
            Storage::disk(self::DISK)->delete($path);
        }
    }

    public static function isManagedPath(?string $path): bool
    {
        if (blank($path)) {
            return false;
        }

        $directories = implode('|', self::DIRECTORIES);

        return (bool) preg_match('#^('.$directories.')/[A-Za-z0-9]{40}\.(webp|jpg)$#', (string) $path);
    }

    public static function url(?string $path): ?string
    {
        return static::isManagedPath($path) ? Storage::disk(self::DISK)->url($path) : null;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function encode(\GdImage $image): array
    {
        ob_start();

        if (function_exists('imagewebp') && imagewebp($image, null, 82)) {
            return [(string) ob_get_clean(), 'webp'];
        }

        ob_end_clean();
        ob_start();

        // JPEG ne gère pas la transparence : fond crème.
        $canvas = imagecreatetruecolor(imagesx($image), imagesy($image));
        imagefill($canvas, 0, 0, imagecolorallocate($canvas, 239, 230, 210));
        imagealphablending($canvas, true);
        imagecopy($canvas, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));
        imagejpeg($canvas, null, 85);
        imagedestroy($canvas);

        return [(string) ob_get_clean(), 'jpg'];
    }

    /**
     * Redresse les photos de téléphone (orientation EXIF), quand l'extension exif est présente.
     */
    private function applyOrientation(\GdImage $image, string $data, int $type): \GdImage
    {
        if ($type !== IMAGETYPE_JPEG || ! function_exists('exif_read_data')) {
            return $image;
        }

        $exif = @exif_read_data('data://image/jpeg;base64,'.base64_encode($data));
        $angle = match ((int) ($exif['Orientation'] ?? 1)) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };

        if ($angle === 0) {
            return $image;
        }

        $rotated = imagerotate($image, $angle, 0);

        if ($rotated === false) {
            return $image;
        }

        imagedestroy($image);

        return $rotated;
    }

    /**
     * @throws ValidationException
     */
    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['image' => $message]);
    }
}
