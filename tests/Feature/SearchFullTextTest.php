<?php

namespace Tests\Feature;

use App\Models\Faceclaim;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Index plein texte de MariaDB : données réellement validées (pas de transaction de test).
 */
class SearchFullTextTest extends TestCase
{
    use DatabaseTruncation {
        truncateDatabaseTables as baseTruncateDatabaseTables;
    }

    protected function truncateDatabaseTables(): void
    {
        if (! $this->usesMariaDb()) {
            $this->markTestSkipped('Index plein texte : MariaDB uniquement.');
        }

        $this->baseTruncateDatabaseTables();
    }

    protected function tearDown(): void
    {
        // Les lignes sont réellement validées : on les retire pour les tests suivants.
        if ($this->usesMariaDb()) {
            Faceclaim::query()->whereIn('name', ['Cillian Murphy', 'Eddie Murphy', 'Cate Blanchett'])->delete();
        }

        parent::tearDown();
    }

    private function usesMariaDb(): bool
    {
        return in_array(DB::getDriverName(), ['mysql', 'mariadb'], true);
    }

    #[Test]
    public function la_recherche_utilise_l_index_plein_texte_de_mariadb(): void
    {
        config(['hollow.search_fulltext' => true]);

        Faceclaim::query()->create(['name' => 'Cillian Murphy']);
        Faceclaim::query()->create(['name' => 'Eddie Murphy']);
        Faceclaim::query()->create(['name' => 'Cate Blanchett']);

        $this->get(route('search', ['q' => 'cill murph']))
            ->assertOk()
            ->assertSee('Cillian Murphy')
            ->assertDontSee('Eddie Murphy')
            ->assertDontSee('Cate Blanchett');

        $this->get(route('search', ['q' => 'murphy']))->assertSee('Eddie Murphy')->assertSee('Cillian Murphy');
    }
}
