<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ChecksPreview;
use App\Models\Family;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class FamilyController extends Controller
{
    use ChecksPreview;

    public function index(): View
    {
        return view('universe.families-index', [
            'families' => Family::query()->published()->ordered()->get(),
        ]);
    }

    public function show(Request $request, Family $family): View
    {
        $isPreview = $this->ensureVisible($request, $family->is_published, 'univers.gerer');

        return view('universe.family-show', [
            'family' => $family,
            'others' => Family::query()->published()->ordered()->whereKeyNot($family->getKey())->get(),
            'isPreview' => $isPreview,
        ]);
    }
}
