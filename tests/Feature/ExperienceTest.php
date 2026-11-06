<?php

namespace Tests\Feature;

use App\Enums\CharacterStatus;
use App\Filament\Pages\ValidationHistory;
use App\Filament\Pages\ValidationQueue;
use App\Models\ActivityLog;
use App\Models\Character;
use App\Models\Family;
use App\Models\FamilyRequest;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\CharacterWorkflow;
use App\Services\ValidationDesk;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\MakesCharacters;
use Tests\TestCase;

class ExperienceTest extends TestCase
{
    use MakesCharacters, RefreshDatabase;

    private function pendingCharacter(string $first = 'Aiko', string $last = 'Fujimori'): Character
    {
        $owner = $this->player();
        $character = app(CharacterWorkflow::class)->start($owner);
        $character->forceFill([
            'first_name' => $first,
            'last_name' => $last,
            'history' => str_repeat('Une enfance au bord du lac. ', 10),
            'status' => CharacterStatus::Pending,
            'submitted_at' => now(),
        ])->save();

        return $character;
    }

    // Vitrine ----------------------------------------------------------------

    #[Test]
    public function la_vitrine_presente_mysteres_categories_familles_et_ville(): void
    {
        app(Settings::class)->set('registrations_open', true);

        $response = $this->get('/')->assertOk();

        foreach (['L’incendie de Kirisawa', 'La quarante-quatrième lanterne', 'Ceux qui reviennent', 'Qui incarner ?', 'Yūrei', 'Les grandes familles', 'Minase', 'Katsuragi', 'La ville et son lac', 'data-city-map', 'Rejoindre Tsukimori', 'lake__lantern--white'] as $text) {
            $response->assertSee($text, false);
        }

        // 43 lanternes jaunes et une blanche.
        $this->assertSame(44, substr_count($response->getContent(), 'class="lake__lantern'));
    }

    #[Test]
    public function un_membre_connecte_arrive_sur_son_tableau_de_bord(): void
    {
        $this->actingAs($this->player())->get('/')->assertRedirect(route('dashboard'));
        $this->actingAs($this->player())->get('/?vitrine=1')->assertOk()->assertSee('Les mystères de Tsukimori');
    }

    // Tableau de bord -----------------------------------------------------------

    #[Test]
    public function un_compte_sans_personnage_accepte_voit_l_avancement_de_son_inscription(): void
    {
        $user = $this->player();

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Où en est votre inscription')
            ->assertSee('Créer un personnage')
            ->assertDontSee('Vos RP');

        $character = app(CharacterWorkflow::class)->start($user);
        $character->forceFill(['first_name' => 'Aiko', 'last_name' => 'Fujimori', 'status' => CharacterStatus::Pending, 'submitted_at' => now()])->save();
        $this->actingAs($user)->get(route('dashboard'))->assertSee('Votre fiche est en cours de lecture');

        app(CharacterWorkflow::class)->requestChanges($character->fresh(), $this->staff(), 'Précisez son lien avec le lac.');
        $this->actingAs($user)->get(route('dashboard'))
            ->assertSee('Message de l’équipe')
            ->assertSee('Précisez son lien avec le lac.')
            ->assertSee('Reprendre la fiche');
    }

    #[Test]
    public function le_joueur_change_de_personnage_actif(): void
    {
        [$user, $first] = $this->playerWithCharacter('Aki', 'Sato');
        $second = $this->acceptedCharacter($user, 'Ren', 'Abe');
        [, $foreign] = $this->playerWithCharacter('Yuki', 'Mori');

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Vos RP')
            ->assertSee('À vous de répondre')
            ->assertSee('Brouillons')
            ->assertSee('Recherches de liens et de RP')
            ->assertSee('Actualités et événements')
            ->assertSee('Changer de personnage');

        $this->actingAs($user)->post(route('characters.activate'), ['character_id' => $foreign->id])->assertSessionHasErrors('character_id');
        $this->actingAs($user)->post(route('characters.activate'), ['character_id' => $second->id])->assertSessionHasNoErrors();
        $this->assertSame($second->id, $user->fresh()->active_character_id);

        $this->actingAs($user->fresh())->get(route('dashboard'))->assertSee('class="active-chip"', false)->assertSee('Ren Abe');
        $this->actingAs($user->fresh())->get(route('scenes.create'))
            ->assertOk()
            ->assertSee('<option value="'.$second->id.'" selected', false);
    }

    // Crédits facultatifs -------------------------------------------------------

    #[Test]
    public function le_credit_d_un_avatar_est_facultatif(): void
    {
        $character = new Character(['first_name' => 'Aiko', 'last_name' => 'Fujimori']);
        $character->forceFill(['avatar_path' => 'avatars/test.jpg', 'avatar_credit' => null]);

        $this->assertNotContains('Avatar : l’image', $character->missingForSubmission());
        foreach ($character->missingForSubmission() as $line) {
            $this->assertStringNotContainsString('crédit', $line);
        }
    }

    // Validation -----------------------------------------------------------------

    #[Test]
    public function la_file_de_validation_rassemble_les_demandes_en_attente(): void
    {
        $character = $this->pendingCharacter();
        [$user, $member] = $this->playerWithCharacter('Ren', 'Abe');
        $request = new FamilyRequest;
        $request->forceFill(['user_id' => $user->id, 'character_id' => $member->id, 'family_id' => Family::query()->where('slug', 'minase')->value('id'), 'message' => 'Mariage.', 'status' => 'en-attente'])->save();
        $unverified = User::factory()->unverified()->create(['pseudo' => 'SansAdresse']);

        $this->assertGreaterThanOrEqual(3, app(ValidationDesk::class)->count());

        $this->actingAs($this->staff());
        Livewire::test(ValidationQueue::class)
            ->assertCanSeeTableRecords(app(ValidationDesk::class)->query()->get())
            ->assertSee('Aiko Fujimori')
            ->assertSee('Rejoindre Famille Minase')
            ->assertSee('SansAdresse');

        $this->actingAs($this->player())->get('/admin/a-valider')->assertForbidden();
    }

    #[Test]
    public function une_demande_de_modification_exige_un_motif_previent_le_membre_et_rejoint_l_historique(): void
    {
        $character = $this->pendingCharacter();
        $uid = $character->id * 10 + 1;
        $this->actingAs($this->staff());

        Livewire::test(ValidationQueue::class)
            ->callTableAction('review', $uid, ['decision' => ValidationDesk::CHANGES, 'reason' => ''])
            ->assertHasTableActionErrors(['reason' => 'required']);

        Livewire::test(ValidationQueue::class)
            ->callTableAction('review', $uid, ['decision' => ValidationDesk::CHANGES, 'reason' => 'Ajoutez un souvenir de 1996.'])
            ->assertHasNoTableActionErrors();

        $this->assertSame(CharacterStatus::ChangesRequested, $character->fresh()->status);
        $this->assertTrue(UserNotification::query()->where('user_id', $character->user_id)->exists());
        $this->assertSame('Ajoutez un souvenir de 1996.', ActivityLog::query()->where('action', 'fiche.modifications')->firstOrFail()->properties['motif']);

        Livewire::test(ValidationHistory::class)->assertSee('Modifications demandées')->assertSee('Ajoutez un souvenir de 1996.');
    }

    #[Test]
    public function accepter_en_un_clic_et_refuser_une_inscription(): void
    {
        $character = $this->pendingCharacter();
        $unverified = User::factory()->unverified()->create(['pseudo' => 'Robot42']);
        $this->actingAs($this->staff());

        Livewire::test(ValidationQueue::class)->callTableAction('quickAccept', $character->id * 10 + 1)->assertHasNoTableActionErrors();
        $this->assertSame(CharacterStatus::Accepted, $character->fresh()->status);

        Livewire::test(ValidationQueue::class)
            ->callTableAction('review', $unverified->id * 10 + 4, ['decision' => ValidationDesk::REFUSE, 'reason' => 'Inscription automatisée.'])
            ->assertHasNoTableActionErrors();
        $this->assertNull(User::query()->find($unverified->id));
        $this->assertSame('Inscription automatisée.', ActivityLog::query()->where('action', 'inscription.refusee')->firstOrFail()->properties['motif']);
    }
}
