<?php

namespace Tests\Feature;

use App\Enums\CharacterStatus;
use App\Enums\ClaimStatus;
use App\Filament\Pages\ManageSettings;
use App\Filament\Resources\Characters\Pages\ViewCharacter;
use App\Filament\Resources\FaceclaimClaims\Pages\ListFaceclaimClaims;
use App\Filament\Resources\PredefinedCharacters\Pages\CreatePredefinedCharacter;
use App\Models\Character;
use App\Models\Faceclaim;
use App\Models\FaceclaimClaim;
use App\Models\PredefinedCharacter;
use App\Models\Species;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CharacterAdministrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
    }

    #[Test]
    public function un_administrateur_accede_aux_ecrans_des_personnages(): void
    {
        $admin = $this->staff('administrateur');

        foreach (['/admin/fiches', '/admin/reservations', '/admin/bottin', '/admin/bottin/nouveau', '/admin/predefinis', '/admin/predefinis/nouveau', '/admin/reglages'] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }

    #[Test]
    public function un_moderateur_sans_permission_n_y_a_pas_acces(): void
    {
        $moderator = $this->staff('moderateur');

        foreach (['/admin/fiches', '/admin/reservations', '/admin/bottin', '/admin/predefinis'] as $url) {
            $this->actingAs($moderator)->get($url)->assertForbidden();
        }
    }

    #[Test]
    public function une_fiche_s_accepte_depuis_l_administration(): void
    {
        $admin = $this->staff('administrateur');
        $character = $this->pendingCharacter();

        $this->actingAs($admin)->get('/admin/fiches/'.$character->slug)->assertOk()->assertSee('Une histoire écrite par le joueur');

        Livewire::actingAs($admin)
            ->test(ViewCharacter::class, ['record' => $character->getRouteKey()])
            ->callAction('accept', ['comment' => 'Bienvenue'])
            ->assertHasNoActionErrors();

        $this->assertSame(CharacterStatus::Accepted, $character->fresh()->status);
    }

    #[Test]
    public function un_refus_exige_un_motif(): void
    {
        $admin = $this->staff('administrateur');
        $character = $this->pendingCharacter();

        Livewire::actingAs($admin)
            ->test(ViewCharacter::class, ['record' => $character->getRouteKey()])
            ->callAction('refuse', ['comment' => ''])
            ->assertHasActionErrors(['comment' => 'required']);

        $this->assertSame(CharacterStatus::Pending, $character->fresh()->status);
    }

    #[Test]
    public function une_reservation_du_bottin_s_accepte_depuis_l_administration(): void
    {
        $admin = $this->staff('administrateur');
        $faceclaim = Faceclaim::query()->create(['name' => 'Anya Taylor-Joy']);
        $claim = new FaceclaimClaim;
        $claim->forceFill(['faceclaim_id' => $faceclaim->id, 'user_id' => $this->player()->id, 'status' => ClaimStatus::Pending])->save();

        Livewire::actingAs($admin)
            ->test(ListFaceclaimClaims::class)
            ->callTableAction('accept', $claim)
            ->assertHasNoTableActionErrors();

        $claim->refresh();
        $this->assertSame(ClaimStatus::Reserved, $claim->status);
        $this->assertNotNull($claim->expires_at);
    }

    #[Test]
    public function un_predefini_se_cree_depuis_l_administration(): void
    {
        $admin = $this->staff('administrateur');

        Livewire::actingAs($admin)
            ->test(CreatePredefinedCharacter::class)
            ->fillForm([
                'title' => 'La sœur cadette',
                'summary' => 'Un rôle de l’équipe.',
                'history' => 'Ce que le joueur doit savoir.',
                'is_published' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $predefined = PredefinedCharacter::query()->where('title', 'La sœur cadette')->firstOrFail();
        $this->get(route('predefined.show', $predefined))->assertOk()->assertSee('Ce que le joueur doit savoir.');
    }

    #[Test]
    public function les_reglages_des_personnages_se_modifient(): void
    {
        $admin = $this->staff('administrateur');

        Livewire::actingAs($admin)
            ->test(ManageSettings::class)
            ->fillForm(['max_characters' => 5, 'reservation_days' => 10])
            ->call('save')
            ->assertHasNoFormErrors();

        $settings = app(Settings::class);
        $settings->flush();
        $this->assertSame(5, $settings->maxCharacters());
        $this->assertSame(10, $settings->reservationDays());
    }

    private function pendingCharacter(): Character
    {
        $player = $this->player();
        $faceclaim = Faceclaim::query()->create(['name' => 'Jessica Chastain']);

        $character = new Character([
            'first_name' => 'Margot',
            'last_name' => 'Wren',
            'age' => 40,
            'species_id' => Species::query()->where('slug', 'medium')->value('id') ?? Species::query()->value('id'),
            'profession' => 'Antiquaire',
            'history' => 'Une histoire écrite par le joueur.',
            'personality' => 'Patiente.',
        ]);
        $character->forceFill([
            'user_id' => $player->id,
            'faceclaim_id' => $faceclaim->id,
            'status' => CharacterStatus::Pending,
            'submitted_at' => now(),
        ])->save();

        $claim = new FaceclaimClaim;
        $claim->forceFill([
            'faceclaim_id' => $faceclaim->id,
            'user_id' => $player->id,
            'character_id' => $character->id,
            'status' => ClaimStatus::Pending,
        ])->save();

        return $character->refresh();
    }
}
