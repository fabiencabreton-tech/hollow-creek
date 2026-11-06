<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ApplicationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function le_manifeste_decrit_une_application_installable(): void
    {
        $manifest = json_decode((string) file_get_contents(public_path('manifest.webmanifest')), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame('Tsukimori', $manifest['short_name']);
        $this->assertSame('standalone', $manifest['display']);
        $this->assertStringStartsWith('/', $manifest['start_url']);

        $sizes = [];
        foreach ($manifest['icons'] as $icon) {
            $path = public_path(ltrim($icon['src'], '/'));
            $this->assertFileExists($path);
            [$width, $height] = getimagesize($path);
            $this->assertSame($icon['sizes'], "{$width}x{$height}");
            $sizes[] = $icon['sizes'].' '.($icon['purpose'] ?? 'any');
        }
        $this->assertContains('192x192 any', $sizes);
        $this->assertContains('512x512 any', $sizes);
        $this->assertContains('512x512 maskable', $sizes);

        foreach ($manifest['shortcuts'] as $shortcut) {
            $this->get($shortcut['url'])->assertRedirect(route('login'));
        }
    }

    #[Test]
    public function chaque_page_annonce_le_manifeste_et_l_icone_apple(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('rel="manifest"', false)
            ->assertSee('apple-touch-icon.png', false)
            ->assertSee(route('pages.application'), false);

        $this->assertFileExists(public_path('icones/apple-touch-icon.png'));
    }

    #[Test]
    public function la_page_d_installation_explique_android_iphone_et_ordinateur(): void
    {
        $this->get(route('pages.application'))
            ->assertOk()
            ->assertSee('Installer Tsukimori')
            ->assertSee('Sur Android')
            ->assertSee('Sur iPhone et iPad')
            ->assertSee('Sur ordinateur')
            ->assertSee('data-install', false);
    }

    #[Test]
    public function le_script_d_arriere_plan_ne_garde_aucune_page_du_site(): void
    {
        $worker = (string) file_get_contents(public_path('sw.js'));

        // Seule la page hors connexion est mise de côté ; les pages (scènes privées, messages)
        // passent toujours par le serveur.
        $this->assertStringContainsString("const OFFLINE_URL = '/hors-ligne.html'", $worker);
        $this->assertStringContainsString("request.mode === 'navigate'", $worker);
        $this->assertFileExists(public_path('hors-ligne.html'));
        $this->assertStringContainsString('Pas de connexion', (string) file_get_contents(public_path('hors-ligne.html')));
    }
}
