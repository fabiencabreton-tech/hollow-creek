<?php

namespace App\Services;

use App\Enums\CharacterStatus;
use App\Enums\FamilyRequestStatus;
use App\Models\Character;
use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\FamilyRequest;
use App\Models\User;
use App\Support\ActivityLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Appartenance aux familles : arbre généalogique, demandes d'intégration et
 * propositions de nouvelles familles. Rejoindre une famille n'est jamais obligatoire.
 */
class FamilyService
{
    public function __construct(private readonly Notifier $notifier) {}

    /**
     * Le personnage accepté a sa place dans l'arbre de sa famille.
     */
    public function ensureMember(Character $character): void
    {
        $node = FamilyMember::query()->where('character_id', $character->getKey())->first();

        if ($character->family_id === null || $character->status !== CharacterStatus::Accepted) {
            if ($node) {
                $this->detachNode($node, $character);
            }

            return;
        }

        if ($node && $node->family_id === $character->family_id) {
            $node->forceFill(['name' => $character->fullName()])->save();

            return;
        }

        if ($node) {
            $this->detachNode($node, $character);
        }

        FamilyMember::query()->create([
            'family_id' => $character->family_id,
            'character_id' => $character->getKey(),
            'name' => $character->fullName(),
            'sort_order' => 1000,
        ]);
    }

    /**
     * Le personnage quitte l'arbre. Un nœud dont dépendent d'autres membres reste,
     * sous son nom, pour ne pas casser les branches.
     */
    public function detachNode(FamilyMember $node, ?Character $character = null): void
    {
        $referenced = FamilyMember::query()
            ->where(fn ($query) => $query->where('parent_id', $node->getKey())->orWhere('second_parent_id', $node->getKey()))
            ->exists();

        if ($referenced) {
            $node->forceFill(['character_id' => null, 'name' => $character?->fullName() ?? $node->name, 'spouse_id' => null])->save();
            FamilyMember::query()->where('spouse_id', $node->getKey())->update(['spouse_id' => null]);

            return;
        }

        FamilyMember::query()->where('spouse_id', $node->getKey())->update(['spouse_id' => null]);
        $node->delete();
    }

    /**
     * Le joueur quitte la famille avec son personnage.
     */
    public function leave(Character $character): void
    {
        DB::transaction(function () use ($character): void {
            $character->forceFill(['family_id' => null])->save();
            $this->ensureMember($character);
        });
    }

    public function requestJoin(Character $character, Family $family, ?string $message): FamilyRequest
    {
        $this->ensureNoPending($character);

        if (! $family->is_published || ! $family->is_open) {
            throw ValidationException::withMessages(['family_id' => 'Cette famille n’accueille pas de nouveaux membres pour le moment.']);
        }

        if ($character->family_id === $family->getKey()) {
            throw ValidationException::withMessages(['character_id' => $character->fullName().' fait déjà partie de cette famille.']);
        }

        return $this->createRequest($character, ['family_id' => $family->getKey(), 'message' => $message]);
    }

    public function propose(Character $character, string $name, ?string $japaneseName, string $summary, ?string $message): FamilyRequest
    {
        $this->ensureNoPending($character);

        if (Family::query()->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($name))])->exists()) {
            throw ValidationException::withMessages(['proposed_name' => 'Une famille porte déjà ce nom.']);
        }

        return $this->createRequest($character, [
            'proposed_name' => trim($name),
            'proposed_japanese_name' => filled($japaneseName) ? trim($japaneseName) : null,
            'proposed_summary' => trim($summary),
            'message' => $message,
        ]);
    }

    public function cancel(FamilyRequest $request): void
    {
        if ($request->status === FamilyRequestStatus::Pending) {
            $request->forceFill(['status' => FamilyRequestStatus::Cancelled])->save();
        }
    }

    public function approve(FamilyRequest $request, User $staff, ?string $response = null): Family
    {
        $family = DB::transaction(function () use ($request, $staff, $response): Family {
            $request = FamilyRequest::query()->whereKey($request->getKey())->lockForUpdate()->firstOrFail();
            $this->ensurePending($request);

            $family = $request->family ?? Family::query()->create([
                'name' => 'Famille '.$request->proposed_name,
                'slug' => Str::slug('famille '.$request->proposed_name),
                'japanese_name' => $request->proposed_japanese_name,
                'summary' => $request->proposed_summary,
                'is_major' => false,
                'is_open' => true,
                'is_published' => true,
                'sort_order' => 100,
            ]);

            $character = $request->character;
            $character->forceFill(['family_id' => $family->getKey()])->save();
            $this->ensureMember($character);

            $request->forceFill([
                'status' => FamilyRequestStatus::Accepted,
                'family_id' => $family->getKey(),
                'reviewed_by' => $staff->getKey(),
                'reviewed_at' => now(),
                'response' => $response,
            ])->save();

            return $family;
        });

        ActivityLogger::record('famille.demande-acceptee', $family, $request->label().' · '.$request->character?->fullName(), $response ? ['motif' => $response] : [], causer: $staff);
        $this->notifyRequester($request->fresh(), $family->name.' : demande acceptée', route('families.show', $family, false));

        return $family;
    }

    public function refuse(FamilyRequest $request, User $staff, string $response): void
    {
        $this->ensurePending($request);

        $request->forceFill([
            'status' => FamilyRequestStatus::Refused,
            'reviewed_by' => $staff->getKey(),
            'reviewed_at' => now(),
            'response' => $response,
        ])->save();

        ActivityLogger::record('famille.demande-refusee', $request->character, $request->label().' · '.$request->character?->fullName(), ['motif' => $response], causer: $staff);
        $this->notifyRequester($request, 'Demande de famille refusée : '.$request->label(), route('families.index', [], false));
    }

    /**
     * Le joueur place son propre personnage dans l'arbre.
     */
    public function placeNode(FamilyMember $node, ?int $parentId, ?int $secondParentId, ?int $spouseId, ?string $note): void
    {
        $forbidden = $node->descendantIds();
        $candidates = FamilyMember::query()->where('family_id', $node->family_id)->pluck('id')->all();

        foreach (['parent_id' => $parentId, 'second_parent_id' => $secondParentId] as $field => $id) {
            if ($id !== null && (! in_array($id, $candidates, true) || in_array($id, $forbidden, true))) {
                throw ValidationException::withMessages([$field => 'Ce parent ne peut pas être choisi : il appartient à une autre famille ou descend déjà de ce personnage.']);
            }
        }

        if ($parentId !== null && $parentId === $secondParentId) {
            throw ValidationException::withMessages(['second_parent_id' => 'Choisissez deux parents différents.']);
        }

        if ($spouseId !== null && (! in_array($spouseId, $candidates, true) || $spouseId === $node->getKey())) {
            throw ValidationException::withMessages(['spouse_id' => 'Ce conjoint ne peut pas être choisi.']);
        }

        DB::transaction(function () use ($node, $parentId, $secondParentId, $spouseId, $note): void {
            if ($node->spouse_id && $node->spouse_id !== $spouseId) {
                FamilyMember::query()->whereKey($node->spouse_id)->where('spouse_id', $node->getKey())->update(['spouse_id' => null]);
            }

            if ($spouseId !== null) {
                // Le lien est réciproque ; l'ancien conjoint de l'autre perd le sien.
                $other = FamilyMember::query()->findOrFail($spouseId);
                if ($other->spouse_id && $other->spouse_id !== $node->getKey()) {
                    FamilyMember::query()->whereKey($other->spouse_id)->update(['spouse_id' => null]);
                }
                $other->forceFill(['spouse_id' => $node->getKey()])->save();
            }

            $node->forceFill([
                'parent_id' => $parentId,
                'second_parent_id' => $secondParentId,
                'spouse_id' => $spouseId,
                'note' => filled($note) ? trim($note) : null,
            ])->save();
        });
    }

    private function ensureNoPending(Character $character): void
    {
        if (FamilyRequest::query()->where('character_id', $character->getKey())->where('status', FamilyRequestStatus::Pending->value)->exists()) {
            throw ValidationException::withMessages(['character_id' => $character->fullName().' a déjà une demande en attente.']);
        }
    }

    private function ensurePending(FamilyRequest $request): void
    {
        if ($request->status !== FamilyRequestStatus::Pending) {
            throw ValidationException::withMessages(['status' => 'Cette demande a déjà été traitée.']);
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createRequest(Character $character, array $attributes): FamilyRequest
    {
        $request = new FamilyRequest;
        $request->forceFill($attributes + [
            'user_id' => $character->user_id,
            'character_id' => $character->getKey(),
            'status' => FamilyRequestStatus::Pending,
        ])->save();

        ActivityLogger::record('famille.demande', $request->character, $request->label(), causer: $character->user);

        return $request;
    }

    private function notifyRequester(FamilyRequest $request, string $title, string $url): void
    {
        if ($request->user) {
            $this->notifier->send($request->user, 'famille', $title, $url, $request->response);
        }
    }
}
