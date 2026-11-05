<?php

namespace App\Http\Controllers;

use App\Enums\GazetteRubric;
use App\Http\Controllers\Concerns\ChecksPreview;
use App\Models\GazetteArticle;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * The Hollow Creek Gazette : le journal local, rédigé par l'équipe.
 */
class GazetteController extends Controller
{
    use ChecksPreview;

    public function index(Request $request): View
    {
        $rubric = GazetteRubric::tryFrom((string) $request->query('rubrique', ''));

        // À la une : le dernier article marqué « à la une », sinon le plus récent.
        $featured = null;

        if ($rubric === null) {
            $featured = GazetteArticle::query()->published()->where('is_featured', true)->latest('published_at')->first()
                ?? GazetteArticle::query()->published()->latest('published_at')->first();
        }

        $articles = GazetteArticle::query()
            ->published()
            ->when($rubric, fn ($query) => $query->where('rubric', $rubric->value))
            ->when($featured, fn ($query) => $query->whereKeyNot($featured->getKey()))
            ->latest('published_at')
            ->paginate(12)
            ->withQueryString();

        return view('gazette.index', [
            'featured' => $featured,
            'articles' => $articles,
            'rubric' => $rubric,
            'rubrics' => GazetteRubric::cases(),
        ]);
    }

    public function show(Request $request, GazetteArticle $article): View
    {
        $isPreview = $this->ensureVisible($request, $article->isPublished(), 'gazette.gerer');

        $article->load(['location', 'event']);

        return view('gazette.show', [
            'article' => $article,
            'location' => $article->location?->is_published ? $article->location : null,
            'isPreview' => $isPreview,
        ]);
    }
}
