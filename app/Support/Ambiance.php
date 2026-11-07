<?php

namespace App\Support;

use App\Enums\GazetteRubric;
use App\Models\District;
use App\Models\Family;
use App\Models\GazetteArticle;
use App\Models\Location;
use App\Models\Species;
use Illuminate\Support\Facades\Vite;

/**
 * Illustrations d'ambiance du site (brume, lac, sanctuaire…), servies en WebP par Vite.
 *
 * Chaque illustration existe en deux largeurs : la petite pour les téléphones et les
 * vignettes, la grande pour les bandeaux. Elles décorent : le texte alternatif est vide.
 * Une image envoyée par l'équipe (article, famille, espèce) passe toujours avant.
 */
final class Ambiance
{
    /** @var array<string, array{small: int, large: int, ratio: float}> */
    public const IMAGES = [
        'lac-torii' => ['small' => 960, 'large' => 1600, 'ratio' => 900 / 1600],
        'foret-brume' => ['small' => 960, 'large' => 1600, 'ratio' => 900 / 1600],
        'gazette' => ['small' => 960, 'large' => 1672, 'ratio' => 521 / 1672],
        'sanctuaire' => ['small' => 800, 'large' => 1440, 'ratio' => 0.75],
        'maison-lanterne' => ['small' => 800, 'large' => 1440, 'ratio' => 0.75],
        'kirisawa' => ['small' => 800, 'large' => 1440, 'ratio' => 0.75],
        'lanternes' => ['small' => 800, 'large' => 1440, 'ratio' => 0.75],
        'rue-commercante' => ['small' => 409, 'large' => 409, 'ratio' => 293 / 409],
        'pluie' => ['small' => 1200, 'large' => 1200, 'ratio' => 0.75],
        'shoji' => ['small' => 1200, 'large' => 1200, 'ratio' => 0.75],
    ];

    /** Illustration de repli par famille (l'équipe peut en envoyer une propre). */
    private const FAMILIES = [
        'minase' => 'lanternes',
        'fujisawa' => 'foret-brume',
        'tachibana' => 'maison-lanterne',
        'katsuragi' => 'rue-commercante',
    ];

    /** Illustration de repli par espèce. */
    private const SPECIES = [
        'humains' => 'rue-commercante',
        'yurei' => 'sanctuaire',
        'onryo' => 'foret-brume',
        'revenus' => 'lanternes',
        'rappeles' => 'lac-torii',
        'mediums' => 'maison-lanterne',
        'praticiens-de-l-occulte' => 'sanctuaire',
        'maudits' => 'foret-brume',
    ];

    /** Illustration de repli par quartier. */
    private const DISTRICTS = [
        'honmachi' => 'rue-commercante',
        'furumachi' => 'maison-lanterne',
        'kohan' => 'lac-torii',
        'kitayama' => 'foret-brume',
        'minamimachi' => 'maison-lanterne',
    ];

    /** Illustration de repli par type de lieu. */
    private const PLACE_KINDS = [
        'Lieu sacré' => 'sanctuaire',
        'Commerce' => 'rue-commercante',
        'Nature' => 'foret-brume',
        'Habitation' => 'maison-lanterne',
    ];

    public static function exists(string $name): bool
    {
        return isset(self::IMAGES[$name]);
    }

    public static function url(string $name, string $size = 'large'): string
    {
        $image = self::IMAGES[$name] ?? self::IMAGES['foret-brume'];
        $name = isset(self::IMAGES[$name]) ? $name : 'foret-brume';

        return Vite::asset("resources/images/ambiance/{$name}-{$image[$size]}.webp");
    }

    /**
     * @return array{small: string, large: string, width: int, height: int}
     */
    public static function picture(string $name): array
    {
        $name = isset(self::IMAGES[$name]) ? $name : 'foret-brume';
        $image = self::IMAGES[$name];

        return [
            'small' => self::url($name, 'small'),
            'large' => self::url($name, 'large'),
            'width' => $image['large'],
            'height' => (int) round($image['large'] * $image['ratio']),
        ];
    }

    public static function texture(string $name): string
    {
        return Vite::asset("resources/images/ambiance/{$name}.webp");
    }

    public static function forFamily(Family $family): string
    {
        return self::FAMILIES[$family->slug] ?? self::rotate((int) $family->getKey());
    }

    public static function forSpecies(Species $species): string
    {
        return self::SPECIES[$species->slug] ?? self::rotate((int) $species->getKey());
    }

    public static function forDistrict(District $district): string
    {
        return self::DISTRICTS[$district->slug] ?? self::rotate((int) $district->getKey());
    }

    public static function forPlace(Location $location): string
    {
        // Les ruines de l'hôpital ont leur propre illustration ; le lac garde ses lanternes.
        if (str_contains((string) $location->slug, 'hopital')) {
            return 'kirisawa';
        }
        if (str_starts_with((string) $location->slug, 'lac-')) {
            return 'lac-torii';
        }

        return self::PLACE_KINDS[$location->kind] ?? self::rotate((int) $location->getKey());
    }

    public static function forArticle(GazetteArticle $article): string
    {
        return match ($article->rubric) {
            GazetteRubric::AvisDeces => 'lanternes',
            GazetteRubric::FaitsDivers => 'foret-brume',
            GazetteRubric::VieLocale => 'rue-commercante',
            GazetteRubric::Actualites => 'maison-lanterne',
            GazetteRubric::Rumeurs => 'sanctuaire',
            GazetteRubric::Annonces => 'maison-lanterne',
            default => self::rotate((int) $article->getKey()),
        };
    }

    private static function rotate(int $seed): string
    {
        $pool = ['lac-torii', 'foret-brume', 'lanternes', 'maison-lanterne', 'sanctuaire'];

        return $pool[$seed % count($pool)];
    }
}
