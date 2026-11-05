<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HostingProxyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::get('/_test/ip', fn () => response()->json([
            'ip' => request()->ip(),
            'secure' => request()->isSecure(),
        ]));
    }

    #[Test]
    public function l_adresse_reelle_est_lue_quand_la_requete_vient_du_proxy(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->withHeaders(['X-Real-IP' => '203.0.113.7', 'X-Forwarded-Proto' => 'https'])
            ->getJson('/_test/ip')
            ->assertJson(['ip' => '203.0.113.7', 'secure' => true]);
    }

    #[Test]
    public function un_visiteur_ne_peut_pas_usurper_une_adresse(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.20'])
            ->withHeaders(['X-Real-IP' => '203.0.113.7'])
            ->getJson('/_test/ip')
            ->assertJson(['ip' => '198.51.100.20']);
    }
}
