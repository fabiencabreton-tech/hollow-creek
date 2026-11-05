<?php

namespace Tests\Feature;

use App\Enums\CharacterStatus;
use App\Enums\ClaimStatus;
use App\Enums\PredefinedStatus;
use App\Enums\VictimRole;
use App\Enums\VictimStatus;
use App\Models\Character;
use App\Models\Faceclaim;
use App\Models\FaceclaimClaim;
use App\Models\KirisawaVictim;
use App\Models\PredefinedCharacter;
use App\Models\Species;
use App\Models\User;
use App\Notifications\CharacterReviewed;
use App\Services\CharacterWorkflow;
use App\Support\ImageStore;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CharacterSheetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(ImageStore::DISK);
        Notification::fake();
    }

    #[Test]
    public function un_joueur_remplit_sa_fiche_en_six_etapes_et_l_envoie(): void
    {
        $player = $this->player();

        $character = $this->completeDraft($player, 'Élise', 'Marlowe', 'Rachel Weisz');

        $this->actingAs($player)->get(route('characters.edit', [$character, 6]))
            ->assertOk()
            ->assertSee('Envoyer ma fiche');

        $this->actingAs($player)
            ->post(route('characters.submit', $character), ['attestation' => '1'])
            ->assertRedirect(route('characters.mine'));

        $character->refresh();
        $this->assertSame(CharacterStatus::Pending, $character->status);
        $this->assertSame('elise-marlowe', $character->slug);
        $this->assertNotNull($character->submitted_at);
        $this->assertNull($character->activeClaim->expires_at, 'La célébrité reste de côté pendant l’examen.');
    }

    #[Test]
    public function une_fiche_incomplete_ne_part_pas(): void
    {
        $player = $this->player();
        $character = app(CharacterWorkflow::class)->start($player);

        $this->actingAs($player)
            ->post(route('characters.submit', $character), ['attestation' => '1'])
            ->assertSessionHasErrors('character');

        $this->assertSame(CharacterStatus::Draft, $character->fresh()->status);
    }

    #[Test]
    public function les_personnages_sont_majeurs(): void
    {
        $player = $this->player();
        $character = app(CharacterWorkflow::class)->start($player);

        $this->actingAs($player)
            ->put(route('characters.update', [$character, 1]), $this->identity(['age' => 17]))
            ->assertSessionHasErrors('age');

        $this->actingAs($player)
            ->put(route('characters.update', [$character, 1]), $this->identity(['age' => 300, 'apparent_age' => 16]))
            ->assertSessionHasErrors(['age', 'apparent_age']);
    }

    #[Test]
    public function le_nombre_de_personnages_par_compte_est_plafonne(): void
    {
        app(Settings::class)->set('max_characters', 2);
        $player = $this->player();

        $this->actingAs($player)->post(route('characters.store'))->assertRedirect();
        $this->actingAs($player)->post(route('characters.store'))->assertRedirect();
        $this->actingAs($player)->post(route('characters.store'))->assertSessionHasErrors('character');

        $this->assertSame(2, Character::query()->where('user_id', $player->id)->count());
    }

    #[Test]
    public function une_fiche_en_cours_reste_privee(): void
    {
        $owner = $this->player();
        $character = $this->completeDraft($owner, 'Silas', 'Thorne', 'Cillian Murphy');

        auth()->guard('web')->logout();
        $this->assertGuest();
        $this->get(route('characters.show', $character))->assertNotFound();
        $this->actingAs($this->player())->get(route('characters.show', $character))->assertNotFound();
        $this->actingAs($this->player())->get(route('characters.edit', [$character, 1]))->assertForbidden();
        $this->actingAs($this->player())->put(route('characters.update', [$character, 1]), $this->identity())->assertForbidden();
        $this->actingAs($this->player())->delete(route('characters.destroy', $character))->assertForbidden();

        $this->actingAs($owner)->get(route('characters.show', $character))->assertOk()->assertSee('Aperçu');
        $this->get(route('characters.index'))->assertOk()->assertDontSee('Silas Thorne');
    }

    #[Test]
    public function l_equipe_accepte_une_fiche_qui_devient_publique(): void
    {
        $player = $this->player();
        $character = $this->submittedCharacter($player, 'Ada', 'Ashby', 'Eva Green');
        $staff = $this->staff('administrateur');

        app(CharacterWorkflow::class)->accept($character, $staff, 'Bienvenue !');

        $character->refresh();
        $this->assertSame(CharacterStatus::Accepted, $character->status);
        $this->assertSame(ClaimStatus::Taken, $character->activeClaim->status);
        Notification::assertSentTo($player, CharacterReviewed::class);

        $this->get(route('characters.index'))->assertOk()->assertSee('Ada Ashby');
        $this->get(route('characters.show', $character))->assertOk()->assertSee('Ada Ashby')->assertDontSee('Aperçu');
        $this->get(route('faceclaims.index'))->assertOk()->assertSee('Eva Green')->assertSee('Pris');
    }

    #[Test]
    public function une_demande_de_modifications_rend_la_fiche_au_joueur(): void
    {
        $player = $this->player();
        $character = $this->submittedCharacter($player, 'Noah', 'Vale', 'Dev Patel');

        app(CharacterWorkflow::class)->requestChanges($character, $this->staff(), 'Développez la jeunesse du personnage.');

        $this->assertSame(CharacterStatus::ChangesRequested, $character->fresh()->status);
        $this->actingAs($player)->get(route('characters.mine'))->assertSee('Développez la jeunesse du personnage.');
        $this->actingAs($player)->get(route('characters.edit', [$character->fresh(), 3]))->assertOk();
    }

    #[Test]
    public function un_refus_libere_la_celebrite(): void
    {
        $player = $this->player();
        $character = $this->submittedCharacter($player, 'Iris', 'Crane', 'Jodie Comer');

        app(CharacterWorkflow::class)->refuse($character, $this->staff(), 'Hors univers.');

        $this->assertSame(CharacterStatus::Refused, $character->fresh()->status);
        $this->assertNull(Faceclaim::query()->where('name', 'Jodie Comer')->first()->activeClaim);
    }

    #[Test]
    public function on_ne_peut_pas_valider_sa_propre_fiche(): void
    {
        $staffPlayer = $this->staff('administrateur');
        $character = $this->submittedCharacter($staffPlayer, 'Owen', 'Hale', 'Paul Mescal');

        $this->assertFalse($staffPlayer->can('review', $character));
        $this->assertTrue($this->staff('administrateur')->can('review', $character));
    }

    #[Test]
    public function une_celebrite_prise_ne_peut_pas_servir_deux_fois(): void
    {
        $first = $this->player();
        $this->completeDraft($first, 'June', 'Hollis', 'Florence Pugh');

        $second = $this->player();
        $character = app(CharacterWorkflow::class)->start($second);

        $this->actingAs($second)
            ->put(route('characters.update', [$character, 3]), [
                'faceclaim' => '  florence   PUGH ',
                'avatar' => UploadedFile::fake()->image('avatar.jpg', 400, 500),
                'avatar_credit' => 'Photo sous licence CC BY 2.0',
            ])
            ->assertSessionHasErrors('faceclaim');

        $this->assertSame(1, FaceclaimClaim::query()->active()->count());
    }

    #[Test]
    public function la_base_refuse_deux_demandes_actives_pour_la_meme_celebrite(): void
    {
        $faceclaim = Faceclaim::query()->create(['name' => 'Mads Mikkelsen']);
        $user = $this->player();

        $claim = new FaceclaimClaim;
        $claim->forceFill(['faceclaim_id' => $faceclaim->id, 'user_id' => $user->id, 'status' => ClaimStatus::Reserved])->save();

        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);

        $duplicate = new FaceclaimClaim;
        $duplicate->forceFill(['faceclaim_id' => $faceclaim->id, 'user_id' => $this->player()->id, 'status' => ClaimStatus::Pending])->save();
    }

    #[Test]
    public function une_celebrite_liberee_peut_etre_redemandee(): void
    {
        $faceclaim = Faceclaim::query()->create(['name' => 'Tilda Swinton']);

        $old = new FaceclaimClaim;
        $old->forceFill(['faceclaim_id' => $faceclaim->id, 'user_id' => $this->player()->id, 'status' => ClaimStatus::Released])->save();

        $this->actingAs($this->player())
            ->post(route('faceclaims.store'), ['name' => 'Tilda Swinton'])
            ->assertSessionHasNoErrors();

        $this->assertSame(ClaimStatus::Pending, $faceclaim->fresh()->activeClaim->status);
    }

    #[Test]
    public function le_bottin_signale_une_celebrite_disponible_et_limite_les_demandes(): void
    {
        $this->get(route('faceclaims.index', ['q' => 'Oscar Isaac']))->assertOk()->assertSee('Disponible');

        $player = $this->player();

        foreach (['Oscar Isaac', 'Zendaya Coleman', 'Rami Malek'] as $name) {
            $this->actingAs($player)->post(route('faceclaims.store'), ['name' => $name])->assertSessionHasNoErrors();
        }

        $this->actingAs($player)->post(route('faceclaims.store'), ['name' => 'Ana de Armas'])->assertSessionHasErrors('name');

        $this->actingAs($this->player())->post(route('faceclaims.store'), ['name' => 'oscar isaac'])->assertSessionHasErrors('name');

        $this->get(route('faceclaims.index', ['q' => 'Oscar']))->assertOk()->assertSee('Oscar Isaac')->assertSee('Demande en cours');
    }

    #[Test]
    public function les_reservations_expirees_liberent_la_celebrite(): void
    {
        $player = $this->player();
        $faceclaim = Faceclaim::query()->create(['name' => 'Saoirse Ronan']);

        $claim = new FaceclaimClaim;
        $claim->forceFill([
            'faceclaim_id' => $faceclaim->id,
            'user_id' => $player->id,
            'status' => ClaimStatus::Reserved,
            'expires_at' => now()->subMinute(),
        ])->save();

        $this->artisan('schedule:test', ['--name' => 'hollow:reservations'])->assertSuccessful();

        $this->assertSame(ClaimStatus::Expired, $claim->fresh()->status);
        $this->assertNull($faceclaim->fresh()->activeClaim);
    }

    #[Test]
    public function un_predefini_se_reprend_puis_se_verrouille_a_l_envoi(): void
    {
        $species = Species::query()->where('slug', 'humains')->firstOrFail();
        $predefined = PredefinedCharacter::query()->create([
            'title' => 'Le gardien du phare',
            'first_name' => 'Abel',
            'last_name' => 'Crowe',
            'species_id' => $species->id,
            'summary' => 'Un rôle de l’équipe.',
            'is_published' => true,
        ]);

        $this->get(route('predefined.index'))->assertOk()->assertSee('Le gardien du phare');

        $player = $this->player();
        $this->actingAs($player)->post(route('predefined.claim', $predefined))->assertRedirect();

        $character = Character::query()->where('user_id', $player->id)->firstOrFail();
        $this->assertSame('Abel', $character->first_name);
        $this->assertSame($predefined->id, $character->predefined_character_id);

        $this->fillSheet($player, $character, 'Abel', 'Crowe', 'Andrew Scott');
        $this->actingAs($player)->post(route('characters.submit', $character->fresh()), ['attestation' => '1'])->assertSessionHasNoErrors();

        $this->assertSame(PredefinedStatus::UnderReview, $predefined->fresh()->status);

        $this->actingAs($this->player())->post(route('predefined.claim', $predefined))->assertSessionHasErrors('character');
    }

    #[Test]
    public function l_etape_nature_propose_toutes_les_categories(): void
    {
        $player = $this->player();
        $character = app(CharacterWorkflow::class)->start($player);

        $response = $this->actingAs($player)->get(route('characters.edit', [$character, 2]))->assertOk();

        foreach (['Humains', 'Yūrei', 'Onryō', 'Revenus', 'Rappelés', 'Médiums', 'Praticiens de l’occulte', 'Maudits'] as $name) {
            $response->assertSee($name);
        }
    }

    #[Test]
    public function une_specialisation_reservee_aux_humains_est_refusee_a_un_fantome(): void
    {
        $player = $this->player();
        $character = app(CharacterWorkflow::class)->start($player);

        $this->actingAs($player)
            ->put(route('characters.update', [$character, 2]), $this->nature('yurei', ['mediums']))
            ->assertSessionHasErrors('traits');

        $this->actingAs($player)
            ->put(route('characters.update', [$character, 2]), $this->nature('yurei', ['maudits'], [
                'curse_origin' => 'Une promesse rompue.',
                'curse_effects' => 'Les miroirs se brisent.',
            ]))
            ->assertSessionHasNoErrors();

        $this->assertTrue($character->fresh()->hasTrait('maudits'));
    }

    #[Test]
    public function un_praticien_ne_maitrise_que_deux_specialisations(): void
    {
        $player = $this->player();
        $character = app(CharacterWorkflow::class)->start($player);

        $this->actingAs($player)
            ->put(route('characters.update', [$character, 2]), $this->nature('humains', ['praticiens-de-l-occulte'], [
                'occult_specialties' => ['spiritisme', 'protection', 'divination'],
            ]))
            ->assertSessionHasErrors('occult_specialties');

        $this->actingAs($player)
            ->put(route('characters.update', [$character, 2]), $this->nature('humains', ['praticiens-de-l-occulte'], [
                'occult_specialties' => ['spiritisme', 'protection'],
                'abilities' => 'Lire les présages.',
                'weaknesses' => 'Chaque rituel l’épuise.',
            ]))
            ->assertSessionHasNoErrors();

        $character->refresh();
        $this->assertSame(['spiritisme', 'protection'], $character->occult_specialties);
        $this->assertSame([], $character->missingNatureDetails());
    }

    #[Test]
    public function un_personnage_surnaturel_doit_decrire_capacites_et_faiblesses(): void
    {
        $player = $this->player();
        $character = $this->completeDraft($player, 'Sora', 'Ishida', 'Ken Watanabe');

        $this->actingAs($player)
            ->put(route('characters.update', [$character, 2]), $this->nature('onryo', [], ['death_date' => '2001-03-04']))
            ->assertSessionHasNoErrors();

        $missing = $character->fresh()->missingForSubmission();
        $this->assertContains('Nature : l’émotion qui retient l’Onryō', $missing);
        $this->assertContains('Nature : les capacités et les faiblesses du personnage', $missing);

        $this->actingAs($player)
            ->post(route('characters.submit', $character), ['attestation' => '1'])
            ->assertSessionHasErrors('character');
    }

    #[Test]
    public function un_revenu_incarne_une_victime_du_registre_reservee_puis_attribuee(): void
    {
        $victim = KirisawaVictim::query()->create(['number' => 12, 'first_name' => 'Haruka', 'last_name' => 'Aoki', 'role' => VictimRole::Staff, 'status' => VictimStatus::Open, 'is_published' => true]);
        $closed = KirisawaVictim::query()->create(['number' => 13, 'first_name' => 'Daichi', 'last_name' => 'Ono', 'role' => VictimRole::Patient, 'status' => VictimStatus::Administrative, 'is_published' => true]);

        $player = $this->player();
        $character = $this->completeDraft($player, 'Élise', 'Marlowe', 'Rinko Kikuchi');

        $this->actingAs($player)
            ->put(route('characters.update', [$character, 2]), $this->nature('revenus', [], ['kirisawa_victim_id' => $closed->id]))
            ->assertSessionHasErrors('kirisawa_victim_id');

        $this->actingAs($player)
            ->put(route('characters.update', [$character, 2]), $this->nature('revenus', [], [
                'kirisawa_victim_id' => $victim->id,
                'abilities' => 'Un corps de chair, intact depuis 1996.',
                'weaknesses' => 'Les souvenirs de l’incendie.',
            ]))
            ->assertSessionHasNoErrors();

        $character->refresh();
        $this->assertSame('Haruka Aoki', $character->fullName());
        $this->assertSame('1996-10-31', $character->death_date->format('Y-m-d'));

        $this->actingAs($player)->post(route('characters.submit', $character), ['attestation' => '1'])->assertSessionHasNoErrors();
        $this->assertSame(VictimStatus::Reserved, $victim->fresh()->status);
        $this->assertSame($character->id, $victim->fresh()->character_id);

        // La victime n'est plus proposée aux autres joueurs.
        $other = $this->player();
        $draft = app(CharacterWorkflow::class)->start($other);
        $this->actingAs($other)
            ->put(route('characters.update', [$draft, 2]), $this->nature('revenus', [], ['kirisawa_victim_id' => $victim->id]))
            ->assertSessionHasErrors('kirisawa_victim_id');

        app(CharacterWorkflow::class)->accept($character->fresh(), $this->staff('administrateur'));
        $this->assertSame(VictimStatus::Played, $victim->fresh()->status);

        $this->get(route('kirisawa.index'))->assertOk()->assertSee('Haruka Aoki')->assertSee('Voir le personnage');
        $this->get(route('characters.show', $character))->assertOk()->assertSee('Victime n°12')->assertSee('Les souvenirs de l’incendie.');
    }

    #[Test]
    public function un_refus_rend_la_victime_aux_joueurs(): void
    {
        $victim = KirisawaVictim::query()->create(['number' => 20, 'first_name' => 'Mei', 'last_name' => 'Kato', 'role' => VictimRole::Patient, 'status' => VictimStatus::Open, 'is_published' => true]);

        $player = $this->player();
        $character = $this->completeDraft($player, 'Élise', 'Marlowe', 'Kiko Mizuhara');
        $this->actingAs($player)
            ->put(route('characters.update', [$character, 2]), $this->nature('revenus', [], [
                'kirisawa_victim_id' => $victim->id,
                'abilities' => 'Aucune.',
                'weaknesses' => 'Le feu.',
            ]))
            ->assertSessionHasNoErrors();
        app(CharacterWorkflow::class)->submit($character->fresh());

        app(CharacterWorkflow::class)->refuse($character->fresh(), $this->staff('administrateur'), 'Pas cette fois.');

        $this->assertSame(VictimStatus::Open, $victim->fresh()->status);
        $this->assertNull($victim->fresh()->character_id);
    }

    #[Test]
    public function les_pages_publiques_des_personnages_s_affichent(): void
    {
        $this->get(route('characters.index'))->assertOk()->assertSee('Les personnages');
        $this->get(route('faceclaims.index'))->assertOk()->assertSee('Le bottin des avatars');
        $this->get(route('predefined.index'))->assertOk()->assertSee('Les prédéfinis');
    }

    #[Test]
    public function le_tableau_de_bord_mene_aux_personnages(): void
    {
        $this->actingAs($this->player())->get(route('dashboard'))->assertOk()->assertSee('Mes personnages');
        $this->actingAs($this->player())->get(route('characters.mine'))->assertOk()->assertSee('Créer un personnage');
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function identity(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Élise',
            'last_name' => 'Marlowe',
            'age' => 34,
            'apparent_age' => null,
            'family_id' => null,
            'profession' => 'Libraire',
            'quote' => 'Les livres se souviennent.',
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function nature(string $slug = 'humains', array $traits = [], array $overrides = []): array
    {
        return array_merge([
            'species_id' => Species::query()->where('slug', $slug)->value('id'),
            'traits' => Species::query()->whereIn('slug', $traits)->pluck('id')->all(),
        ], $overrides);
    }

    private function completeDraft(User $player, string $first, string $last, string $faceclaim): Character
    {
        $character = app(CharacterWorkflow::class)->start($player);

        return $this->fillSheet($player, $character, $first, $last, $faceclaim);
    }

    private function fillSheet(User $player, Character $character, string $first, string $last, string $faceclaim): Character
    {
        $this->actingAs($player)
            ->put(route('characters.update', [$character, 1]), $this->identity(['first_name' => $first, 'last_name' => $last]))
            ->assertSessionHasNoErrors();

        $character->refresh();

        $this->actingAs($player)
            ->put(route('characters.update', [$character, 2]), $this->nature())
            ->assertSessionHasNoErrors();

        $this->actingAs($player)
            ->put(route('characters.update', [$character, 3]), [
                'faceclaim' => $faceclaim,
                'avatar' => UploadedFile::fake()->image('avatar.jpg', 400, 500),
                'avatar_credit' => 'Photo sous licence CC BY 2.0',
            ])
            ->assertSessionHasNoErrors();

        $this->actingAs($player)
            ->put(route('characters.update', [$character, 4]), ['history' => str_repeat('Une enfance au bord du lac Tsukikage. ', 10)])
            ->assertSessionHasNoErrors();

        $this->actingAs($player)
            ->put(route('characters.update', [$character, 5]), ['personality' => str_repeat('Curieuse et prudente. ', 8)])
            ->assertSessionHasNoErrors();

        return $character->refresh();
    }

    private function submittedCharacter(User $player, string $first, string $last, string $faceclaim): Character
    {
        $character = $this->completeDraft($player, $first, $last, $faceclaim);
        app(CharacterWorkflow::class)->submit($character);

        return $character->refresh();
    }
}
