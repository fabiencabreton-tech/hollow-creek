<?php

namespace Tests\Feature;

use App\Enums\ClaimStatus;
use App\Enums\CharacterStatus;
use App\Filament\Pages\ManageBackups;
use App\Filament\Resources\Censuses\Pages\CreateCensus;
use App\Filament\Resources\Censuses\Pages\ViewCensus;
use App\Models\Census;
use App\Models\Character;
use App\Models\Faceclaim;
use App\Models\FaceclaimClaim;
use App\Models\LoreEntry;
use App\Models\Post;
use App\Models\Scene;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\MakesCharacters;
use Tests\TestCase;

class FinishingTest extends TestCase
{
    use MakesCharacters, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
    }

    #[Test]
    public function un_joueur_telecharge_ses_donnees(): void
    {
        [$alice, $ada] = $this->playerWithCharacter('Ada', 'Ashby');
        $this->openScene($alice, $ada, 'Brume', 'Une réponse à moi.');

        $this->actingAs($alice)->get(route('account.data'))->assertOk()->assertSee('Mes données');

        $json = $this->actingAs($alice)->get(route('account.data.json'))
            ->assertOk()
            ->assertHeader('Content-Disposition', 'attachment; filename="tsukimori-mes-donnees.json"')
            ->json();

        $this->assertSame($alice->email, $json['compte']['email']);
        $this->assertSame('Ada Ashby', $json['personnages'][0]['nom']);
        $this->assertSame('Une réponse à moi.', $json['reponses'][0]['texte']);

        $this->actingAs($alice)->get(route('account.data.html'))->assertOk()->assertSee('Une réponse à moi.');
    }

    #[Test]
    public function la_suppression_anonymise_les_reponses_par_defaut(): void
    {
        [$alice, $ada] = $this->playerWithCharacter('Ada', 'Ashby');
        $scene = $this->openScene($alice, $ada, 'Brume', 'Texte gardé pour les partenaires.');
        $claim = $this->claimFor($alice, $ada, 'Eva Green');

        $this->actingAs($alice)->delete(route('account.data.destroy'), ['password' => 'mauvais', 'confirmation' => 'SUPPRIMER', 'texts' => 'anonymiser'])
            ->assertSessionHasErrors('password', null, 'deleteAccount');
        $this->assertNotNull(User::query()->find($alice->id));

        $this->actingAs($alice)->delete(route('account.data.destroy'), ['password' => 'mot-de-passe-de-test', 'confirmation' => 'SUPPRIMER', 'texts' => 'anonymiser'])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('home'));

        $this->assertGuest();
        $this->assertNull(User::query()->find($alice->id));
        $this->assertNull(Character::withTrashed()->find($ada->id));
        $this->assertSame(ClaimStatus::Released, $claim->fresh()->status);

        $post = Post::query()->firstOrFail();
        $this->assertNull($post->user_id);
        $this->get(route('scenes.show', $scene))->assertSee('Texte gardé pour les partenaires.')->assertSee('Ancien membre');
        $this->assertDatabaseMissing('activity_log', ['description' => $alice->email]);
    }

    #[Test]
    public function la_suppression_peut_effacer_les_textes(): void
    {
        [$alice, $ada] = $this->playerWithCharacter('Ada', 'Ashby');
        $scene = $this->openScene($alice, $ada, 'Brume', 'Texte à effacer.');

        $this->actingAs($alice)->delete(route('account.data.destroy'), ['password' => 'mot-de-passe-de-test', 'confirmation' => 'SUPPRIMER', 'texts' => 'effacer'])
            ->assertRedirect(route('home'));

        $this->assertSame(0, Post::withTrashed()->count());
        $this->assertSame(0, $scene->fresh()->posts_count);
    }

    #[Test]
    public function un_compte_de_l_equipe_ne_se_supprime_pas_ainsi(): void
    {
        $admin = $this->staff('administrateur');

        $this->actingAs($admin)->delete(route('account.data.destroy'), ['password' => 'mot-de-passe-de-test', 'confirmation' => 'SUPPRIMER', 'texts' => 'anonymiser'])
            ->assertSessionHasErrors('password', null, 'deleteAccount');

        $this->assertNotNull($admin->fresh());
    }

    #[Test]
    public function la_recherche_trouve_sans_jamais_reveler_une_scene_privee(): void
    {
        [$alice, $ada] = $this->playerWithCharacter('Ada', 'Ashby');
        $this->openScene($alice, $ada, 'Brouillard public', 'Texte.');
        $this->openScene($alice, $ada, 'Brouillard secret', 'Texte.', 'privee');
        Faceclaim::query()->create(['name' => 'Brooke Shields']);

        $this->get(route('search', ['q' => 'ashby']))->assertOk()->assertSee('Ada Ashby');

        $this->get(route('search', ['q' => 'brouillard']))
            ->assertSee('Brouillard public')
            ->assertDontSee('Brouillard secret');

        $this->actingAs($this->player())->get(route('search', ['q' => 'brouillard']))->assertDontSee('Brouillard secret');
        $this->actingAs($alice)->get(route('search', ['q' => 'brouillard']))->assertSee('Brouillard secret');

        $this->get(route('search', ['q' => 'brooke']))->assertSee('Brooke Shields')->assertSee('Disponible');
        $this->get(route('search', ['q' => 'x']))->assertOk()->assertSee('au moins deux caractères');
    }

    #[Test]
    public function un_recensement_se_lance_se_remplit_et_archive_les_absents(): void
    {
        [$alice, $ada] = $this->playerWithCharacter('Ada', 'Ashby');
        [$bruno, $bram] = $this->playerWithCharacter('Bram', 'Holloway');
        [, $cora] = $this->playerWithCharacter('Cora', 'Vale');
        $cora->forceFill(['absence_note' => 'En voyage'])->save();
        $this->travel(1)->minutes();

        $admin = $this->staff('administrateur');
        Livewire::actingAs($admin)
            ->test(CreateCensus::class)
            ->fillForm(['title' => 'Recensement de novembre', 'ends_at' => now()->addDays(7)->format('Y-m-d H:i:s')])
            ->call('create')
            ->assertHasNoFormErrors();

        $census = Census::query()->firstOrFail();
        $this->assertDatabaseHas('user_notifications', ['user_id' => $alice->id, 'type' => 'recensement']);

        $this->actingAs($alice)->get(route('dashboard'))->assertSee('Recensement en cours');
        $this->actingAs($alice)->post(route('census.store'), ['characters' => [$ada->id, $bram->id]])->assertRedirect();
        $this->assertSame(1, $census->responses()->count(), 'On ne recense que ses propres personnages.');

        $this->travel(8)->days();

        Livewire::actingAs($admin)
            ->test(ViewCensus::class, ['record' => $census->id])
            ->callAction('archiveMissing')
            ->assertHasNoActionErrors();

        $this->assertSame(CharacterStatus::Accepted, $ada->fresh()->status);
        $this->assertSame(CharacterStatus::Archived, $bram->fresh()->status);
        $this->assertSame(CharacterStatus::Accepted, $cora->fresh()->status, 'Une absence annoncée protège le personnage.');
        $this->assertNotNull($census->fresh()->closed_at);
    }

    #[Test]
    public function les_archives_secretes_sont_reservees_aux_administrateurs(): void
    {
        LoreEntry::query()->create(['title' => 'La vérité sur 1996', 'body' => 'Secret de l’équipe.']);

        $this->actingAs($this->staff('administrateur'))->get('/admin/archives-secretes')->assertOk()->assertSee('La vérité sur 1996');
        $this->actingAs($this->staff('moderateur'))->get('/admin/archives-secretes')->assertForbidden();
        $this->actingAs($this->player())->get(route('search', ['q' => 'vérité']))->assertDontSee('La vérité sur 1996');
    }

    #[Test]
    public function l_equipe_telecharge_les_archives_lisibles_des_scenes(): void
    {
        [$alice, $ada] = $this->playerWithCharacter('Ada', 'Ashby');
        $this->openScene($alice, $ada, 'Brume', 'Texte archivé.');

        Livewire::actingAs($this->staff('administrateur'))
            ->test(ManageBackups::class)
            ->callAction('sceneArchive')
            ->assertFileDownloaded();
    }

    #[Test]
    public function la_barre_mobile_accompagne_l_espace_joueur(): void
    {
        $this->actingAs($this->player())->get(route('dashboard'))->assertSee('class="player-bar"', false);
        auth()->guard('web')->logout();
        $this->get(route('home'))->assertDontSee('class="player-bar"', false);
    }

    private function openScene(User $user, Character $character, string $title, string $text, string $visibility = 'publique'): Scene
    {
        $this->actingAs($user)->post(route('scenes.store'), [
            'character_id' => $character->id,
            'title' => $title,
            'rp_date' => 'Jeudi',
            'visibility' => $visibility,
            'turn_mode' => 'libre',
            'introduction' => $text,
        ])->assertSessionHasNoErrors();

        auth()->guard('web')->logout();

        return Scene::query()->latest('id')->firstOrFail();
    }

    private function claimFor(User $user, Character $character, string $name): FaceclaimClaim
    {
        $faceclaim = Faceclaim::query()->create(['name' => $name]);
        $claim = new FaceclaimClaim;
        $claim->forceFill(['faceclaim_id' => $faceclaim->id, 'user_id' => $user->id, 'character_id' => $character->id, 'status' => ClaimStatus::Taken])->save();
        $character->forceFill(['faceclaim_id' => $faceclaim->id])->save();

        return $claim;
    }
}
