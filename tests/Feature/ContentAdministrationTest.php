<?php

namespace Tests\Feature;

use App\Filament\Resources\GazetteArticles\Pages\CreateGazetteArticle;
use App\Filament\Resources\Species\Pages\CreateSpecies;
use App\Models\GazetteArticle;
use App\Models\Species;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ContentAdministrationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function un_administrateur_gere_l_univers_et_la_gazette(): void
    {
        $admin = $this->staff('administrateur');

        foreach (['/admin/especes', '/admin/familles', '/admin/quartiers', '/admin/lieux', '/admin/gazette', '/admin/especes/nouvelle', '/admin/gazette/nouveau'] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }

    #[Test]
    public function un_moderateur_n_y_a_pas_acces(): void
    {
        $moderator = $this->staff('moderateur');

        foreach (['/admin/especes', '/admin/lieux', '/admin/gazette'] as $url) {
            $this->actingAs($moderator)->get($url)->assertForbidden();
        }
    }

    #[Test]
    public function un_role_redaction_cree_sans_code_n_accede_qu_a_la_gazette(): void
    {
        $role = Role::findOrCreate('redaction-gazette', 'web');
        $role->syncPermissions(['administration.acceder', 'gazette.gerer']);

        $writer = User::factory()->withTwoFactor()->create();
        $writer->assignRole($role);

        $this->actingAs($writer)->get('/admin/gazette')->assertOk();
        $this->actingAs($writer)->get('/admin/especes')->assertForbidden();
        $this->actingAs($writer)->get('/admin/comptes')->assertForbidden();
    }

    #[Test]
    public function une_espece_se_cree_depuis_l_administration(): void
    {
        $this->actingAs($this->staff('administrateur'));

        Livewire::test(CreateSpecies::class)
            ->fillForm([
                'name' => 'Gardiens',
                'treatment' => 'medium',
                'summary' => 'Résumé écrit par l’équipe.',
                'is_published' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $species = Species::query()->where('name', 'Gardiens')->firstOrFail();
        $this->assertSame('gardiens', $species->slug);
        $this->assertTrue($species->is_published);

        $this->get('/univers/especes/gardiens')->assertOk()->assertSee('Résumé écrit par l’équipe.');
    }

    #[Test]
    public function un_article_se_publie_depuis_l_administration(): void
    {
        $admin = $this->staff('administrateur');
        $this->actingAs($admin);

        Livewire::test(CreateGazetteArticle::class)
            ->fillForm([
                'title' => 'La quarante-quatrième lanterne',
                'rubric' => 'a-la-une',
                'byline' => 'La rédaction',
                'body' => 'Texte de l’équipe.',
                'status' => GazetteArticle::STATUS_PUBLISHED,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $article = GazetteArticle::query()->where('title', 'La quarante-quatrième lanterne')->firstOrFail();
        $this->assertTrue($article->isPublished());
        $this->assertSame($admin->id, $article->author_id);

        $this->assertDatabaseHas('activity_log', ['action' => 'contenu.cree', 'user_id' => $admin->id]);
    }
}
