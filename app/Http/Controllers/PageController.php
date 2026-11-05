<?php

namespace App\Http\Controllers;

use App\Models\Page;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Pages officielles (règlement, pages légales), rédigées dans l'administration.
 */
class PageController extends Controller
{
    public function show(Request $request): View
    {
        $slug = (string) $request->route()->defaults['slug'];

        $page = Page::query()
            ->where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();

        return view('pages.show', ['page' => $page]);
    }
}
