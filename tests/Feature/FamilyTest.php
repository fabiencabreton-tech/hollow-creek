<?php

namespace Tests\Feature;

use App\Enums\FamilyRequestStatus;
use App\Filament\Resources\FamilyRequests\Pages\ListFamilyRequests;
use App\Models\Character;
use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\FamilyPrelink;
use App\Models\FamilyRequest;
use App\Models\UserNotification;
use App\Services\CharacterWorkflow;
use App\Services\FamilyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\MakesCharacters;
use Tests\TestCase;

class FamilyTest extends TestCase
{
    use MakesCharacters, RefreshDatabase;

    private function minase(): Family
    {
        return Family::query()->where('slug', 'minase')->firstOrFail();
    }

    #[Test]
    public function les_quatre_grandes_familles_sont_presentees(): void
    {
        $response = $this->get('/univers/familles')->assertOk();

        foreach (['Famille Minase', '水瀬家', 'Famille Fujisawa', 'Famille Tachibana', 'Famille Katsuragi', 'Ceux qui connaissent les eaux du lac', 'jamais obligatoire', 'Le surnaturel dans les familles'] as $text) {
            $response->assertSee($text);
        }

        $this->get('/univers/familles/minase')
            ->assertOk()
            ->assertSee('Kohan')
            ->assertSee('Navigation, réparation d’embarcations et tourisme lacustre.')
            ->assertSee('Lien avec Kirisawa')
            ->assertSee('Famille Fujisawa')
            ->assertSee('Relations généralement cordiales', false)
            ->assertSee('Créer un personnage Minase', false);

        // Le lien est affiché sur les deux fiches.
        $this->get('/univers/familles/katsuragi')->assertOk()->assertSee('Relations professionnelles liées aux transports');
    }

    #[Test]
    public function les_notes_secretes_ne_sont_jamais_publiques(): void
    {
        $this->minase()->update(['secret_notes' => 'La grand-mère savait tout.']);

        $this->get('/univers/familles')->assertOk()->assertDontSee('La grand-mère savait tout.');
        $this->get('/univers/familles/minase')->assertOk()->assertDontSee('La grand-mère savait tout.');
        $this->actingAs($this->player())->get('/univers/familles/minase')->assertOk()->assertDontSee('La grand-mère savait tout.');
    }

    #[Test]
    public function un_nouveau_personnage_nait_dans_une_famille_et_entre_dans_l_arbre_a_l_acceptation(): void
    {
        $player = $this->player();

        $this->actingAs($player)->post(route('families.new-character', $this->minase()))->assertRedirect();
        $character = Character::query()->where('user_id', $player->id)->firstOrFail();
        $this->assertSame($this->minase()->id, $character->family_id);
        $this->assertNull(FamilyMember::query()->where('character_id', $character->id)->first());

        // La fiche est validée par l'équipe : l'arbre accueille le personnage.
        $character->forceFill(['first_name' => 'Ren', 'last_name' => 'Minase', 'status' => \App\Enums\CharacterStatus::Pending, 'submitted_at' => now()])->save();
        app(CharacterWorkflow::class)->accept($character->fresh(), $this->staff());

        $node = FamilyMember::query()->where('character_id', $character->id)->firstOrFail();
        $this->assertSame($this->minase()->id, $node->family_id);
        $this->get('/univers/familles/minase')->assertOk()->assertSee('Ren Minase')->assertSee('data-family-tree', false);
    }

    #[Test]
    public function une_famille_fermee_ne_se_rejoint_pas(): void
    {
        $this->minase()->update(['is_open' => false]);
        [$user, $character] = $this->playerWithCharacter('Aki', 'Sato');

        $this->actingAs($user)->post(route('families.new-character', $this->minase()))->assertSessionHasErrors('family_id');
        $this->actingAs($user)->post(route('families.join', $this->minase()), ['character_id' => $character->id])->assertSessionHasErrors('family_id');
    }

    #[Test]
    public function un_personnage_accepte_demande_a_rejoindre_et_l_equipe_accepte(): void
    {
        [$user, $character] = $this->playerWithCharacter('Aki', 'Sato');
        [$other, $theirs] = $this->playerWithCharacter('Rin', 'Abe');

        $this->actingAs($user)->post(route('families.join', $this->minase()), ['character_id' => $theirs->id])->assertSessionHasErrors('character_id');
        $this->actingAs($user)->post(route('families.join', $this->minase()), ['character_id' => $character->id, 'message' => 'Mariée à un Minase.'])->assertSessionHasNoErrors();
        $this->actingAs($user)->post(route('families.join', $this->minase()), ['character_id' => $character->id])->assertSessionHasErrors('character_id');

        $request = FamilyRequest::query()->firstOrFail();
        $this->assertNull($character->fresh()->family_id);

        $this->actingAs($this->staff());
        Livewire::test(ListFamilyRequests::class)
            ->callTableAction('accept', $request, ['response' => 'Bienvenue !'])
            ->assertHasNoTableActionErrors();

        $this->assertSame(FamilyRequestStatus::Accepted, $request->fresh()->status);
        $this->assertSame($this->minase()->id, $character->fresh()->family_id);
        $this->assertTrue(FamilyMember::query()->where('character_id', $character->id)->exists());
        $this->assertSame(1, UserNotification::query()->where('user_id', $user->id)->where('type', 'famille')->count());
    }

    #[Test]
    public function un_joueur_propose_sa_propre_famille(): void
    {
        [$user, $character] = $this->playerWithCharacter('Aki', 'Sakuma');

        $this->actingAs($user)->post(route('families.propose'), [
            'character_id' => $character->id,
            'proposed_name' => 'Sakuma',
            'proposed_japanese_name' => '佐久間',
            'proposed_summary' => 'Une famille de luthiers installée à Furumachi depuis deux générations.',
        ])->assertSessionHasNoErrors();

        $request = FamilyRequest::query()->firstOrFail();
        $family = app(FamilyService::class)->approve($request, $this->staff(), null);

        $this->assertFalse($family->is_major);
        $this->assertTrue($family->is_published);
        $this->assertSame($family->id, $character->fresh()->family_id);
        $this->get('/univers/familles')->assertOk()->assertSee('Familles des joueurs')->assertSee('Famille Sakuma');
    }

    #[Test]
    public function un_refus_previent_le_joueur_et_une_demande_s_annule(): void
    {
        [$user, $character] = $this->playerWithCharacter('Aki', 'Sato');
        $this->actingAs($user)->post(route('families.join', $this->minase()), ['character_id' => $character->id]);
        $request = FamilyRequest::query()->firstOrFail();

        app(FamilyService::class)->refuse($request, $this->staff(), 'Pas de lien crédible.');
        $this->assertSame(FamilyRequestStatus::Refused, $request->fresh()->status);
        $this->assertNull($character->fresh()->family_id);

        $this->actingAs($user)->post(route('families.join', $this->minase()), ['character_id' => $character->id]);
        $second = FamilyRequest::query()->latest('id')->firstOrFail();
        [$intruder] = $this->playerWithCharacter('Rin', 'Abe');
        $this->actingAs($intruder)->delete(route('family-requests.cancel', $second))->assertForbidden();
        $this->actingAs($user)->delete(route('family-requests.cancel', $second))->assertRedirect();
        $this->assertSame(FamilyRequestStatus::Cancelled, $second->fresh()->status);
    }

    #[Test]
    public function le_joueur_place_son_personnage_dans_l_arbre_sans_boucle(): void
    {
        [$user, $character] = $this->playerWithCharacter('Ren', 'Minase');
        $character->forceFill(['family_id' => $this->minase()->id])->save();
        app(FamilyService::class)->ensureMember($character);

        $grandmother = FamilyMember::query()->create(['family_id' => $this->minase()->id, 'name' => 'Grand-mère Minase', 'is_deceased' => true]);
        $node = FamilyMember::query()->where('character_id', $character->id)->firstOrFail();

        $this->actingAs($user)->put(route('families.place', [$this->minase(), $node]), ['parent_id' => $grandmother->id, 'note' => 'Héritier de l’atelier'])->assertSessionHasNoErrors();
        $this->assertSame($grandmother->id, $node->fresh()->parent_id);

        // La grand-mère ne peut pas devenir l'enfant de son petit-fils… mais seul l'équipe la gère ;
        // et le joueur ne peut pas se donner pour parent un de ses descendants.
        $child = FamilyMember::query()->create(['family_id' => $this->minase()->id, 'name' => 'Fils de Ren', 'parent_id' => $node->id]);
        $this->actingAs($user)->put(route('families.place', [$this->minase(), $node]), ['parent_id' => $child->id])->assertSessionHasErrors('parent_id');

        [$other] = $this->playerWithCharacter('Rin', 'Abe');
        $this->actingAs($other)->put(route('families.place', [$this->minase(), $node]), ['parent_id' => null])->assertForbidden();

        $this->get('/univers/familles/minase')->assertOk()->assertSee('Grand-mère Minase')->assertSee('enfant de Grand-mère Minase');
    }

    #[Test]
    public function quitter_la_famille_garde_les_branches_de_l_arbre(): void
    {
        [$user, $character] = $this->playerWithCharacter('Ren', 'Minase');
        $character->forceFill(['family_id' => $this->minase()->id])->save();
        app(FamilyService::class)->ensureMember($character);
        $node = FamilyMember::query()->where('character_id', $character->id)->firstOrFail();
        FamilyMember::query()->create(['family_id' => $this->minase()->id, 'name' => 'Fille de Ren', 'parent_id' => $node->id]);

        $this->actingAs($user)->post(route('families.leave', $character))->assertRedirect();

        $this->assertNull($character->fresh()->family_id);
        $this->assertNull($node->fresh()->character_id);
        $this->assertSame('Ren Minase', $node->fresh()->name);
    }

    #[Test]
    public function un_membre_publie_un_prelien_familial(): void
    {
        [$user, $character] = $this->playerWithCharacter('Ren', 'Minase');
        [$other, $outsider] = $this->playerWithCharacter('Rin', 'Abe');
        $character->forceFill(['family_id' => $this->minase()->id])->save();
        $payload = ['relation' => 'fratrie', 'title' => 'La sœur partie à Tokyo', 'description' => 'Elle a quitté Tsukimori il y a dix ans et revient pour la Nuit des lanternes.'];

        $this->actingAs($other)->post(route('prelinks.store', $this->minase()), $payload + ['character_id' => $outsider->id])->assertSessionHasErrors('character_id');
        $this->actingAs($user)->post(route('prelinks.store', $this->minase()), $payload + ['character_id' => $character->id])->assertSessionHasNoErrors();

        $prelink = FamilyPrelink::query()->firstOrFail();
        $this->get('/univers/familles/minase')->assertOk()->assertSee('La sœur partie à Tokyo')->assertSee('Frère ou sœur recherché·e');

        $this->actingAs($other)->post(route('prelinks.fill', $prelink))->assertForbidden();
        $this->actingAs($user)->post(route('prelinks.fill', $prelink))->assertRedirect();
        $this->get('/univers/familles/minase')->assertDontSee('La sœur partie à Tokyo');
    }

    #[Test]
    public function l_etape_identite_propose_les_familles_sans_obligation(): void
    {
        $player = $this->player();
        $character = app(CharacterWorkflow::class)->start($player);

        $this->actingAs($player)->get(route('characters.edit', [$character, 1]))
            ->assertOk()
            ->assertSee('Les grandes familles')
            ->assertSee('Famille Minase (水瀬家)')
            ->assertSee('Aucune famille du site');
    }

    #[Test]
    public function l_equipe_gere_l_arbre_et_les_demandes_dans_l_administration(): void
    {
        $this->actingAs($this->staff())->get('/admin/familles/'.$this->minase()->getRouteKey().'/modifier')->assertOk();
        $this->actingAs($this->staff())->get('/admin/demandes-de-famille')->assertOk();
        $this->actingAs($this->staff())->get('/admin/preliens')->assertOk();
        $this->actingAs($this->player())->get('/admin/demandes-de-famille')->assertForbidden();
    }
}
