<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\GazetteArticle;
use App\Models\Page;
use App\Models\Scene;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $presentation = Page::query()
            ->where('slug', 'accueil')
            ->where('is_published', true)
            ->first();

        $headline = GazetteArticle::query()->published()->where('is_featured', true)->latest('published_at')->first()
            ?? GazetteArticle::query()->published()->latest('published_at')->first();

        return view('home', [
            'presentation' => $presentation,
            'headline' => $headline,
            'event' => Event::query()->active()->latest('published_at')->first(),
            'scenes' => Scene::query()
                ->listed()
                ->whereNotNull('last_post_at')
                ->with('location')
                ->latest('last_post_at')
                ->limit(4)
                ->get(),
        ]);
    }
}
