<?php

namespace App\Http\Controllers;

use App\Support\SceneInbox;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * « Mes scènes » : invitations, à vous d'écrire, en cours, brouillons, terminées.
 */
class MySceneController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        return view('scenes.mine', [
            'invitations' => SceneInbox::invitations($user),
            'active' => SceneInbox::active($user),
            'drafts' => SceneInbox::drafts($user),
            'finished' => SceneInbox::finished($user),
        ]);
    }
}
