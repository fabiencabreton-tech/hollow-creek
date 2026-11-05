<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;

/**
 * Une fiche en brouillon est introuvable pour les visiteurs ;
 * l'équipe qui la gère peut la prévisualiser.
 */
trait ChecksPreview
{
    protected function ensureVisible(Request $request, bool $isPublished, string $permission): bool
    {
        if ($isPublished) {
            return false;
        }

        abort_unless($request->user()?->can($permission) ?? false, 404);

        return true;
    }
}
