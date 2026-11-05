<?php

namespace Tests\Feature;

use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function l_accueil_s_affiche_avec_la_presentation_et_la_date_rp(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Hollow Creek')
            ->assertSee('Ceux qui reviennent')
            ->assertSee('Quarante-trois personnes')
            ->assertSee(config('hollow.defaults.rp_date'));
    }

    #[Test]
    public function les_pages_officielles_sont_publiees(): void
    {
        foreach (['/reglement', '/mentions-legales', '/confidentialite', '/conditions'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    #[Test]
    public function une_page_depubliee_est_introuvable(): void
    {
        Page::query()->where('slug', 'reglement')->update(['is_published' => false]);

        $this->get('/reglement')->assertNotFound();
    }

    #[Test]
    public function le_html_saisi_dans_une_page_est_neutralise(): void
    {
        Page::query()->where('slug', 'conditions')->update([
            'body' => "<script>alert('x')</script>\n\n[piège](javascript:alert(1))",
        ]);

        $this->get('/conditions')
            ->assertOk()
            ->assertDontSee("<script>alert('x')</script>", false)
            ->assertDontSee('href="javascript:', false);
    }

    #[Test]
    public function les_en_tetes_de_securite_sont_envoyes(): void
    {
        $response = $this->get('/');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');

        $csp = (string) $response->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("script-src 'self' 'nonce-", $csp);
        $this->assertStringContainsString("frame-ancestors 'none'", $csp);
        $this->assertStringNotContainsString('unsafe-eval', $csp);
    }

    #[Test]
    public function une_adresse_inconnue_affiche_la_page_404_du_site(): void
    {
        $this->get('/nulle-part')
            ->assertNotFound()
            ->assertSee('Rien ne se trouve ici');
    }
}
