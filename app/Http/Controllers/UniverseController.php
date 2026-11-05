<?php

namespace App\Http\Controllers;

use App\Models\Family;
use App\Models\Page;
use App\Models\Species;
use Illuminate\Contracts\View\View;

class UniverseController extends Controller
{
    public function __invoke(): View
    {
        return view('universe.index', [
            'page' => Page::query()->where('slug', 'univers')->where('is_published', true)->first(),
            'species' => Species::query()->published()->ordered()->get(),
            'families' => Family::query()->published()->ordered()->get(),
        ]);
    }
}
