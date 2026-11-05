<?php

namespace App\Http\Controllers;

use App\Models\Page;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $presentation = Page::query()
            ->where('slug', 'accueil')
            ->where('is_published', true)
            ->first();

        return view('home', [
            'presentation' => $presentation,
        ]);
    }
}
