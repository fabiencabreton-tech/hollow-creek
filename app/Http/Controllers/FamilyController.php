<?php

namespace App\Http\Controllers;

use App\Enums\CharacterStatus;
use App\Enums\FamilyRequestStatus;
use App\Enums\PredefinedStatus;
use App\Enums\PrelinkRelation;
use App\Http\Controllers\Concerns\ChecksPreview;
use App\Models\Character;
use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\FamilyRequest;
use App\Models\Page;
use App\Support\FamilyTree;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Les grandes familles de Tsukimori et les familles créées par les joueurs.
 * Les notes secrètes d'une famille ne quittent jamais l'administration.
 */
class FamilyController extends Controller
{
    use ChecksPreview;

    public function index(Request $request): View
    {
        $families = Family::query()
            ->published()
            ->ordered()
            ->withCount('members')
            ->with('district')
            ->get();
        $user = $request->user();

        return view('universe.families-index', [
            'major' => $families->where('is_major', true)->values(),
            'others' => $families->where('is_major', false)->values(),
            'page' => Page::query()->where('slug', 'familles')->where('is_published', true)->first(),
            'myCharacters' => $user ? Character::query()->where('user_id', $user->getKey())->accepted()->orderBy('first_name')->get() : collect(),
            'myRequests' => $user ? FamilyRequest::query()->where('user_id', $user->getKey())->with(['family', 'character'])->latest('id')->limit(10)->get() : collect(),
        ]);
    }

    public function show(Request $request, Family $family): View
    {
        $isPreview = $this->ensureVisible($request, $family->is_published, 'univers.gerer');
        $user = $request->user();

        $family->load(['district']);
        $treeMembers = $family->treeMembers()->with('character')->get();
        $myCharacters = $user ? Character::query()->where('user_id', $user->getKey())->accepted()->orderBy('first_name')->get() : collect();

        return view('universe.family-show', [
            'family' => $family,
            'district' => $family->district?->is_published ? $family->district : null,
            'related' => $family->relatedFamilies(),
            'tree' => FamilyTree::layout($treeMembers),
            'treeMembers' => $treeMembers,
            'members' => $family->members()->get(),
            'predefined' => $family->predefinedCharacters()->published()->where('status', PredefinedStatus::Open->value)->orderBy('sort_order')->get(),
            'prelinks' => $family->prelinks()->open()->with('character')->latest('id')->get(),
            'relations' => PrelinkRelation::cases(),
            'others' => Family::query()->published()->ordered()->whereKeyNot($family->getKey())->get(),
            'myCharacters' => $myCharacters,
            'myMembers' => $myCharacters->where('family_id', $family->getKey())->values(),
            'myNodes' => $user
                ? $treeMembers->filter(fn (FamilyMember $member): bool => $member->character?->user_id === $user->getKey() && $member->character?->status === CharacterStatus::Accepted)->values()
                : collect(),
            'pending' => $user
                ? FamilyRequest::query()->where('user_id', $user->getKey())->where('family_id', $family->getKey())->where('status', FamilyRequestStatus::Pending->value)->with('character')->get()
                : collect(),
            'isPreview' => $isPreview,
        ]);
    }
}
