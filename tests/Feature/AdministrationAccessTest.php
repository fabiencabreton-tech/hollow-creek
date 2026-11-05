<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * L'administration n'est ouverte qu'à l'équipe, avec la double authentification.
 */
class AdministrationAccessTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function un_visiteur_est_renvoye_vers_la_connexion(): void
    {
        $this->get('/admin')->assertRedirect('/connexion');
    }

    #[Test]
    public function un_joueur_n_a_pas_acces_a_l_administration(): void
    {
        $this->actingAs($this->player())->get('/admin')->assertForbidden();
    }

    #[Test]
    public function l_equipe_sans_double_authentification_est_renvoyee_vers_son_compte(): void
    {
        $admin = $this->staff('administrateur', twoFactor: false);

        $this->actingAs($admin)->get('/admin')->assertRedirect('/compte#double-authentification');
    }

    #[Test]
    public function un_administrateur_avec_double_authentification_entre(): void
    {
        $admin = $this->staff('administrateur');

        $this->actingAs($admin)->get('/admin')->assertOk();
        $this->actingAs($admin)->get('/admin/comptes')->assertOk();
        $this->actingAs($admin)->get('/admin/roles')->assertOk();
        $this->actingAs($admin)->get('/admin/pages')->assertOk();
        $this->actingAs($admin)->get('/admin/reglages')->assertOk();
        $this->actingAs($admin)->get('/admin/sauvegardes')->assertOk();
        $this->actingAs($admin)->get('/admin/journal')->assertOk();
    }

    #[Test]
    public function un_moderateur_n_accede_ni_aux_reglages_ni_aux_sauvegardes(): void
    {
        $moderator = $this->staff('moderateur');

        $this->actingAs($moderator)->get('/admin')->assertOk();
        $this->actingAs($moderator)->get('/admin/comptes')->assertOk();
        $this->actingAs($moderator)->get('/admin/reglages')->assertForbidden();
        $this->actingAs($moderator)->get('/admin/sauvegardes')->assertForbidden();
        $this->actingAs($moderator)->get('/admin/roles')->assertForbidden();
        $this->actingAs($moderator)->get('/admin/journal')->assertForbidden();
    }

    #[Test]
    public function un_compte_suspendu_perd_l_acces_a_l_administration(): void
    {
        $admin = $this->staff('administrateur');
        $admin->forceFill(['suspended_at' => now()])->save();

        $this->actingAs($admin)->get('/admin')->assertForbidden();
    }

    #[Test]
    public function le_telechargement_des_sauvegardes_est_reserve(): void
    {
        $url = '/admin/sauvegardes/telecharger/sauvegardes/hollow-creek-2026-11-05-03-00-00.zip';

        $this->get($url)->assertRedirect('/connexion');
        $this->actingAs($this->player())->get($url)->assertForbidden();
        $this->actingAs($this->staff('moderateur'))->get($url)->assertForbidden();
        $this->actingAs($this->staff('administrateur'))->get($url)->assertNotFound();
    }

    #[Test]
    public function un_membre_de_l_equipe_voit_le_lien_vers_l_administration(): void
    {
        $this->actingAs($this->staff('administrateur'))
            ->get('/tableau-de-bord')
            ->assertOk()
            ->assertSee('Ouvrir l’administration');
    }

    #[Test]
    public function la_commande_hollow_admin_promeut_un_compte_existant(): void
    {
        $user = $this->player(['email' => 'equipe@example.org']);

        $this->artisan('hollow:admin', ['--email' => 'equipe@example.org'])->assertSuccessful();

        $this->assertTrue($user->fresh()->hasRole('administrateur'));
        $this->assertTrue(User::query()->find($user->id)->isStaff());
    }
}
