<?php

namespace App\Http\Controllers;

use App\Enums\ArchiveCategory;
use App\Http\Controllers\Concerns\ChecksPreview;
use App\Models\MunicipalRecord;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Archives municipales : documents officiels, coupures de presse et pièces
 * d'histoire de Tsukimori, publiés par l'équipe.
 */
class ArchiveController extends Controller
{
    use ChecksPreview;

    public function index(Request $request): View
    {
        $category = ArchiveCategory::tryFrom((string) $request->query('categorie', ''));
        $year = (int) $request->query('annee', 0) ?: null;
        $search = trim(mb_substr((string) $request->query('q', ''), 0, 100));

        $records = MunicipalRecord::query()
            ->published()
            ->when($category, fn ($query) => $query->where('category', $category->value))
            ->when($year, fn ($query) => $query->whereYear('document_date', $year))
            ->when($search !== '', function ($query) use ($search): void {
                $like = '%'.addcslashes($search, '%_\\').'%';
                $query->where(fn ($query) => $query
                    ->where('title', 'like', $like)
                    ->orWhere('reference', 'like', $like)
                    ->orWhere('excerpt', 'like', $like)
                    ->orWhere('body', 'like', $like));
            })
            ->orderByDesc('document_date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $years = MunicipalRecord::query()
            ->published()
            ->whereNotNull('document_date')
            ->pluck('document_date')
            ->map(fn ($date) => $date->year)
            ->unique()
            ->sortDesc()
            ->values();

        return view('archives.index', [
            'records' => $records,
            'categories' => ArchiveCategory::cases(),
            'category' => $category,
            'years' => $years,
            'year' => $year,
            'search' => $search,
        ]);
    }

    public function show(Request $request, MunicipalRecord $record): View
    {
        $isPreview = $this->ensureVisible($request, $record->is_published, 'univers.gerer');

        $record->load(['district', 'location']);

        return view('archives.show', [
            'record' => $record,
            'district' => $record->district?->is_published ? $record->district : null,
            'location' => $record->location?->is_published ? $record->location : null,
            'isPreview' => $isPreview,
        ]);
    }
}
