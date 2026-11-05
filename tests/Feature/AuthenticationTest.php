<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function la_page_de_connexion_s_affiche(): void
    {
        $this->get('/connexion')->assertOk()->assertSee('Adresse e-mail ou pseudo');
    }

    #[Test]
    public function connexion_avec_l_adresse_e_mail(): void
    {
        $user = $this->player(['email' => 'elise@example.org']);

        $this->post('/connexion', ['email' => 'Elise@Example.org', 'password' => 'mot-de-passe-de-test'])
            ->assertRedirect('/tableau-de-bord');

        $this->assertAuthenticatedAs($user);
    }

    #[Test]
    public function connexion_avec_le_pseudo(): void
    {
        $user = $this->player(['pseudo' => 'Élise']);

        $this->post('/connexion', ['email' => 'elise', 'password' => 'mot-de-passe-de-test'])
            ->assertRedirect('/tableau-de-bord');

        $this->assertAuthenticatedAs($user);
    }

    #[Test]
    public function un_mauvais_mot_de_passe_est_refuse(): void
    {
        $this->player(['email' => 'elise@example.org']);

        $this->post('/connexion', ['email' => 'elise@example.org', 'password' => 'faux mot de passe'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    #[Test]
    public function un_compte_suspendu_ne_peut_pas_se_connecter(): void
    {
        $user = $this->player(['email' => 'elise@example.org']);
        $user->forceFill(['suspended_at' => now()])->save();

        $this->post('/connexion', ['email' => 'elise@example.org', 'password' => 'mot-de-passe-de-test'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    #[Test]
    public function un_compte_suspendu_pendant_sa_session_est_deconnecte(): void
    {
        $user = $this->player();
        $this->actingAs($user);
        $user->forceFill(['suspended_at' => now()])->save();

        $this->get('/tableau-de-bord')->assertRedirect('/connexion');

        $this->assertGuest();
    }

    #[Test]
    public function la_double_authentification_est_demandee_a_la_connexion(): void
    {
        $user = $this->staff('administrateur', twoFactor: true);

        $this->post('/connexion', ['email' => $user->email, 'password' => 'mot-de-passe-de-test'])
            ->assertRedirect('/double-authentification');

        $this->assertGuest();
    }

    #[Test]
    public function la_page_mot_de_passe_oublie_s_affiche(): void
    {
        $this->get('/mot-de-passe-oublie')->assertOk();
    }

    #[Test]
    public function deconnexion(): void
    {
        $this->actingAs($this->player());

        $this->post('/deconnexion')->assertRedirect('/');

        $this->assertGuest();
    }
}
