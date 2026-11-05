<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function l_espace_joueur_exige_une_connexion(): void
    {
        $this->get('/compte')->assertRedirect('/connexion');
        $this->get('/tableau-de-bord')->assertRedirect('/connexion');
    }

    #[Test]
    public function le_tableau_de_bord_accueille_le_joueur(): void
    {
        $user = $this->player(['pseudo' => 'Élise']);

        $this->actingAs($user)->get('/tableau-de-bord')
            ->assertOk()
            ->assertSee('Élise')
            ->assertDontSee('Ouvrir l’administration');
    }

    #[Test]
    public function la_page_mon_compte_s_affiche(): void
    {
        $this->actingAs($this->player())->get('/compte')
            ->assertOk()
            ->assertSee('Double authentification')
            ->assertSee('Appareils connectés');
    }

    #[Test]
    public function le_joueur_modifie_son_pseudo(): void
    {
        $user = $this->player(['pseudo' => 'Ancien']);

        $this->actingAs($user)
            ->put('/compte/profil', ['pseudo' => 'Nouveau Nom', 'email' => $user->email])
            ->assertSessionHasNoErrors();

        $this->assertSame('Nouveau Nom', $user->fresh()->pseudo);
        $this->assertSame('nouveaunom', $user->fresh()->pseudo_key);
    }

    #[Test]
    public function changer_d_adresse_e_mail_demande_une_nouvelle_verification(): void
    {
        $user = $this->player();

        $this->actingAs($user)
            ->put('/compte/profil', ['pseudo' => $user->pseudo, 'email' => 'nouvelle@example.org'])
            ->assertSessionHasNoErrors();

        $this->assertNull($user->fresh()->email_verified_at);
    }

    #[Test]
    public function deconnecter_les_autres_appareils_exige_le_mot_de_passe(): void
    {
        $user = $this->player();

        $this->actingAs($user)
            ->delete('/compte/autres-sessions', ['password' => 'faux'])
            ->assertSessionHasErrors('password', null, 'logoutOtherSessions');

        $this->actingAs($user)
            ->delete('/compte/autres-sessions', ['password' => 'mot-de-passe-de-test'])
            ->assertSessionHasNoErrors();
    }

    #[Test]
    public function gerer_la_double_authentification_demande_de_confirmer_le_mot_de_passe(): void
    {
        $this->actingAs($this->player())
            ->get('/compte/securite')
            ->assertRedirect('/compte/confirmer-mot-de-passe');
    }
}
