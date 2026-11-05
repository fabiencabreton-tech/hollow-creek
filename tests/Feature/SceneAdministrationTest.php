<?php

namespace Tests\Feature;

use App\Enums\CharacterStatus;
use App\Enums\ParticipantStatus;
use App\Enums\SceneStatus;
use App\Filament\Resources\Scenes\Pages\ViewScene;
use App\Filament\Resources\Scenes\RelationManagers\PostsRelationManager;
use App\Models\Character;
use App\Models\Post;
use App\Models\Scene;
use App\Models\SceneParticipant;
use App\Models\Species;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SceneAdministrationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function seule_l_equipe_habilitee_modere_les_scenes(): void
    {
        $scene = $this->scene();

        $this->actingAs($this->staff('administrateur'))->get('/admin/scenes')->assertOk()->assertSee($scene->title);
        $admin = $this->staff('administrateur');
        $this->actingAs($admin)->get('/admin/scenes/'.$scene->id)->assertOk()->assertSee('Le pont couvert');

        // Les réponses se chargent dans leur propre composant.
        Livewire::actingAs($admin)
            ->test(PostsRelationManager::class, ['ownerRecord' => $scene, 'pageClass' => ViewScene::class])
            ->assertSee('Une réponse écrite à la main');
        $this->actingAs($this->staff('moderateur'))->get('/admin/scenes')->assertForbidden();
    }

    #[Test]
    public function la_lecture_d_une_scene_privee_est_journalisee(): void
    {
        $admin = $this->staff('administrateur');
        $scene = $this->scene('privee');

        $this->actingAs($admin)->get('/admin/scenes/'.$scene->id)->assertOk();

        $this->assertDatabaseHas('activity_log', ['action' => 'scene.consultation-privee', 'user_id' => $admin->id]);
    }

    #[Test]
    public function une_reponse_se_masque_et_se_restaure(): void
    {
        $admin = $this->staff('administrateur');
        $scene = $this->scene();
        $post = $scene->posts()->firstOrFail();

        Livewire::actingAs($admin)
            ->test(PostsRelationManager::class, ['ownerRecord' => $scene, 'pageClass' => ViewScene::class])
            ->callTableAction('delete', $post)
            ->assertHasNoTableActionErrors();

        $this->assertSoftDeleted($post);
        $this->assertSame(0, $scene->fresh()->posts_count);
        $this->get(route('scenes.show', $scene))->assertDontSee('Une réponse écrite à la main');

        Livewire::actingAs($admin)
            ->test(PostsRelationManager::class, ['ownerRecord' => $scene, 'pageClass' => ViewScene::class])
            ->callTableAction('restore', Post::withTrashed()->find($post->id))
            ->assertHasNoTableActionErrors();

        $this->assertNotSoftDeleted($post);
        $this->assertSame(1, $scene->fresh()->posts_count);
    }

    #[Test]
    public function l_equipe_change_le_statut_d_une_scene(): void
    {
        $admin = $this->staff('administrateur');
        $scene = $this->scene();

        Livewire::actingAs($admin)
            ->test(ViewScene::class, ['record' => $scene->id])
            ->callAction('status', ['status' => 'archivee'])
            ->assertHasNoActionErrors();

        $this->assertSame(SceneStatus::Archived, $scene->fresh()->status);
    }

    private function scene(string $visibility = 'publique'): Scene
    {
        $user = $this->player();
        $character = new Character(['first_name' => 'Ada', 'last_name' => 'Ashby', 'age' => 30, 'species_id' => Species::query()->value('id'), 'profession' => 'Libraire']);
        $character->forceFill(['user_id' => $user->id, 'status' => CharacterStatus::Accepted])->save();

        $scene = new Scene(['title' => 'Le pont couvert', 'visibility' => $visibility, 'turn_mode' => 'libre']);
        $scene->forceFill(['user_id' => $user->id, 'status' => SceneStatus::Ongoing, 'posts_count' => 1, 'words_count' => 5])->save();

        $participant = new SceneParticipant;
        $participant->forceFill(['scene_id' => $scene->id, 'user_id' => $user->id, 'character_id' => $character->id, 'status' => ParticipantStatus::Accepted, 'position' => 1])->save();

        $post = new Post;
        $post->forceFill([
            'scene_id' => $scene->id,
            'user_id' => $user->id,
            'character_id' => $character->id,
            'character_name' => 'Ada Ashby',
            'body' => 'Une réponse écrite à la main.',
            'word_count' => 5,
        ])->save();

        return $scene;
    }
}
