<?php

namespace App\Http\Controllers;

use App\Enums\GazetteRubric;
use App\Http\Controllers\Concerns\ChecksPreview;
use App\Models\GazetteArticle;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * La Gazette de Tsukimori : le journal local, rédigé par l'équipe.
 */
class GazetteController extends Controller
{
    use ChecksPreview;

    /** Une année écrite dans la date RP d'un article (« Jeudi 31 octobre 1996 »). */
    private const YEAR = '/\b(1[89]\d\d|20\d\d)\b/u';

    public function index(Request $request): View
    {
        $rubric = GazetteRubric::tryFrom((string) $request->query('rubrique', ''));
        $search = Str::limit(trim((string) $request->query('q', '')), 80, '');
        $year = preg_match('/^\d{4}$/', (string) $request->query('annee', '')) ? (string) $request->query('annee') : null;
        $dossier = Str::limit(trim((string) $request->query('dossier', '')), 80, '') ?: null;
        $filtered = $rubric !== null || $search !== '' || $year !== null || $dossier !== null;

        // À la une : le dernier article marqué « à la une », sinon le plus récent.
        $featured = null;

        if (! $filtered) {
            $featured = GazetteArticle::query()->published()->where('is_featured', true)->latest('published_at')->first()
                ?? GazetteArticle::query()->published()->latest('published_at')->first();
        }

        $articles = GazetteArticle::query()
            ->published()
            ->when($rubric, fn (Builder $query) => $query->where('rubric', $rubric->value))
            ->when($search !== '', fn (Builder $query) => $this->search($query, $search))
            ->when($year, fn (Builder $query) => $query->where('rp_date', 'like', '%'.$year.'%'))
            ->when($dossier, fn (Builder $query) => $query->where('dossier', $dossier))
            ->when($featured, fn (Builder $query) => $query->whereKeyNot($featured->getKey()))
            ->latest('published_at')
            ->paginate(9)
            ->withQueryString();

        return view('gazette.index', [
            'featured' => $featured,
            'articles' => $articles,
            'rubric' => $rubric,
            'rubrics' => GazetteRubric::cases(),
            'search' => $search,
            'year' => $year,
            'dossier' => $dossier,
            'filtered' => $filtered,
            'years' => $this->years(),
            'special' => $this->specialDossier(),
        ]);
    }

    public function show(Request $request, GazetteArticle $article): View
    {
        $isPreview = $this->ensureVisible($request, $article->isPublished(), 'gazette.gerer');

        $article->load(['location', 'event']);

        $related = $article->dossier
            ? GazetteArticle::query()->published()->where('dossier', $article->dossier)->whereKeyNot($article->getKey())->latest('published_at')->limit(5)->get()
            : collect();

        $others = GazetteArticle::query()
            ->published()
            ->whereKeyNot($article->getKey())
            ->whereNotIn('id', $related->pluck('id')->all())
            ->latest('published_at')
            ->limit(3)
            ->get();

        return view('gazette.show', [
            'article' => $article,
            'location' => $article->location?->is_published ? $article->location : null,
            'isPreview' => $isPreview,
            'related' => $related,
            'others' => $others,
        ]);
    }

    /**
     * Année tirée de la date RP, pour classer les articles par époque.
     */
    public static function yearOf(GazetteArticle $article): ?string
    {
        return preg_match(self::YEAR, (string) $article->rp_date, $match) ? $match[1] : null;
    }

    /**
     * @param  Builder<GazetteArticle>  $query
     */
    private function search(Builder $query, string $search): void
    {
        $term = '%'.addcslashes($search, '%_\\').'%';

        $query->where(fn (Builder $inner) => $inner
            ->where('title', 'like', $term)
            ->orWhere('excerpt', 'like', $term)
            ->orWhere('body', 'like', $term));
    }

    /**
     * Années présentes dans les dates RP des articles publiés, de la plus récente à la plus ancienne.
     *
     * @return Collection<string, int>
     */
    private function years(): Collection
    {
        return GazetteArticle::query()
            ->published()
            ->whereNotNull('rp_date')
            ->pluck('rp_date')
            ->map(fn (?string $date): ?string => preg_match(self::YEAR, (string) $date, $match) ? $match[1] : null)
            ->filter()
            ->countBy()
            ->sortKeysDesc();
    }

    /**
     * Le dossier le plus récent : son nom, son nombre d'articles et l'article qui l'illustre.
     *
     * @return array{name: string, count: int, cover: GazetteArticle}|null
     */
    private function specialDossier(): ?array
    {
        $latest = GazetteArticle::query()->published()->whereNotNull('dossier')->where('dossier', '!=', '')->latest('published_at')->first();

        if ($latest === null) {
            return null;
        }

        $articles = GazetteArticle::query()->published()->where('dossier', $latest->dossier)->latest('published_at')->get();

        return [
            'name' => (string) $latest->dossier,
            'count' => $articles->count(),
            'cover' => $articles->first(fn (GazetteArticle $article): bool => $article->imageUrl() !== null) ?? $latest,
        ];
    }
}
