<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Recherche : index plein texte de MariaDB (mots préfixés, insensible aux accents
 * selon la collation), et LIKE ailleurs ou pour les termes très courts.
 */
class Search
{
    /**
     * @param  Builder<\Illuminate\Database\Eloquent\Model>  $query
     * @param  array<int, string>  $columns  dans l'ordre exact de l'index plein texte
     * @return Builder<\Illuminate\Database\Eloquent\Model>
     */
    public static function apply(Builder $query, array $columns, string $term, bool $indexed = false): Builder
    {
        $words = collect(preg_split('/\s+/u', trim($term)) ?: [])
            ->map(fn (string $word): string => trim((string) preg_replace('/[^\p{L}\p{N}\'’-]+/u', '', $word)))
            ->filter(fn (string $word): bool => $word !== '')
            ->values();

        if ($words->isEmpty()) {
            return $query->whereRaw('1 = 0');
        }

        // $indexed : les colonnes ont un index plein texte (voir la migration des finitions).
        $fullText = $indexed
            && config('hollow.search_fulltext', true)
            && in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)
            && $words->every(fn (string $word): bool => mb_strlen($word) >= 3);

        if ($fullText) {
            $boolean = $words->map(fn (string $word): string => '+'.str_replace(['+', '-', '"', '*', '(', ')', '<', '>', '~', '@'], '', $word).'*')->implode(' ');

            return $query->whereFullText($columns, $boolean, ['mode' => 'boolean']);
        }

        foreach ($words as $word) {
            $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $word).'%';
            $query->where(function (Builder $inner) use ($columns, $like): void {
                foreach ($columns as $column) {
                    $inner->orWhere($column, 'like', $like);
                }
            });
        }

        return $query;
    }
}
