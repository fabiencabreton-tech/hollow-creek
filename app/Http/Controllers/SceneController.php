<?php

namespace App\Http\Controllers;

use App\Enums\ParticipantStatus;
use App\Enums\SceneStatus;
use App\Enums\SceneVisibility;
use App\Enums\TurnMode;
use App\Models\Character;
use App\Models\Location;
use App\Models\Post;
use App\Models\Scene;
use App\Models\SceneDraft;
use App\Services\SceneService;
use App\Support\SceneText;
use App\Support\Settings;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Scènes RP : index public, ouverture, lecture, réponses, brouillons, export.
 */
class SceneController extends Controller
{
    public function __construct(private readonly SceneService $scenes) {}

    public function index(Request $request): View
    {
        $location = $request->filled('lieu')
            ? Location::query()->where('is_published', true)->where('slug', (string) $request->query('lieu'))->first()
            : null;
        $status = SceneStatus::tryFrom((string) $request->query('statut', ''));

        if ($status === SceneStatus::Archived) {
            $status = null;
        }

        $scenes = Scene::query()
            ->listed()
            ->with(['location', 'activeParticipants.character'])
            ->when($location, fn ($query) => $query->where('location_id', $location->getKey()))
            ->when($status, fn ($query) => $query->where('status', $status->value))
            ->orderByDesc('last_post_at')
            ->paginate(20)
            ->withQueryString();

        return view('scenes.index', [
            'scenes' => $scenes,
            'locations' => Location::query()->where('is_published', true)->orderBy('name')->get(),
            'locationFilter' => $location,
            'statusFilter' => $status,
        ]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if (! Gate::allows('create', Scene::class)) {
            return redirect()->route('characters.mine')
                ->withErrors(['scene' => 'Il faut un personnage accepté par l’équipe pour ouvrir une scène.']);
        }

        return view('scenes.create', [
            'characters' => $this->scenes->playableCharacters($user),
            'locations' => Location::query()->where('is_published', true)->orderBy('name')->get(),
            'selectedLocation' => Location::query()->where('is_published', true)->where('slug', (string) $request->query('lieu'))->value('id'),
            'partners' => $this->partnerOptions($request),
            'rpDate' => app(Settings::class)->rpDate(),
            'warnings' => config('hollow.scenes.content_warnings'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Scene::class);

        $data = $request->validate([
            'character_id' => ['required', 'integer'],
            'title' => ['required', 'string', 'min:3', 'max:150'],
            'rp_date' => ['required', 'string', 'max:80'],
            'location_id' => ['nullable', Rule::exists('locations', 'id')->where('is_published', true)],
            'visibility' => ['required', Rule::enum(SceneVisibility::class)],
            'is_open' => ['nullable', 'boolean'],
            'content_warnings' => ['nullable', 'array'],
            'content_warnings.*' => ['string', Rule::in(array_keys(config('hollow.scenes.content_warnings')))],
            'turn_mode' => ['required', Rule::enum(TurnMode::class)],
            'partners' => ['nullable', 'array', 'max:10'],
            'partners.*' => ['integer'],
            'introduction' => ['required', 'string', 'min:2', 'max:'.config('hollow.scenes.max_post_length')],
        ], [], [
            'character_id' => 'personnage',
            'title' => 'titre',
            'rp_date' => 'date RP',
            'location_id' => 'lieu',
            'visibility' => 'visibilité',
            'turn_mode' => 'ordre de passage',
            'partners' => 'partenaires',
            'introduction' => 'introduction',
        ]);

        $character = Character::query()->findOrFail($data['character_id']);
        $isPublic = $data['visibility'] === SceneVisibility::Public->value;

        $scene = $this->scenes->create($request->user(), $character, [
            'title' => trim($data['title']),
            'rp_date' => trim($data['rp_date']),
            'location_id' => $data['location_id'] ?? null,
            'visibility' => $data['visibility'],
            'is_open' => $isPublic && $request->boolean('is_open'),
            'content_warnings' => array_values(array_unique($data['content_warnings'] ?? [])),
            'turn_mode' => $data['turn_mode'],
        ], $data['partners'] ?? [], $data['introduction']);

        return redirect()->route('scenes.show', $scene)->with('status', 'Scène ouverte. Vos partenaires ont reçu leur invitation.')
            ->with('clear_local', 'hollow-creek-nouvelle-scene');
    }

    public function show(Request $request, Scene $scene): View
    {
        abort_unless(Gate::allows('view', $scene), 404);

        $user = $request->user();
        $scene->load(['location', 'creator', 'participants.character.species', 'participants.user']);
        $participant = $scene->participantFor($user);

        $posts = Post::query()
            ->where('scene_id', $scene->getKey())
            ->with(['character.species', 'author'])
            ->orderBy('id')
            ->paginate(config('hollow.scenes.posts_per_page'));

        // Évite de recharger la scène pour chaque réponse (droits de modification).
        $posts->getCollection()->each(fn (Post $post) => $post->setRelation('scene', $scene));

        $firstUnreadId = null;

        if ($participant && $participant->status === ParticipantStatus::Accepted) {
            $firstUnreadId = Post::query()
                ->where('scene_id', $scene->getKey())
                ->when($participant->last_read_post_id, fn ($query, $id) => $query->where('id', '>', $id))
                ->min('id');

            if ($posts->isNotEmpty()) {
                $this->scenes->markRead($participant, (int) $posts->max('id'));
            }
        }

        $draft = $user ? SceneDraft::query()->where('scene_id', $scene->getKey())->where('user_id', $user->getKey())->first() : null;

        return view('scenes.show', [
            'scene' => $scene,
            'posts' => $posts,
            'participant' => $participant,
            'firstUnreadId' => $firstUnreadId,
            'draft' => $draft,
            'preview' => session('preview') && $draft ? SceneText::render($draft->body) : null,
            'myCharacters' => $user ? $this->scenes->playableCharacters($user) : collect(),
            'partners' => $user && Gate::allows('invite', $scene) ? $this->partnerOptions($request, $scene) : collect(),
            'statuses' => SceneStatus::cases(),
        ]);
    }

    /**
     * Saut à la première réponse non lue (ou à la dernière si tout est lu).
     */
    public function unread(Request $request, Scene $scene): RedirectResponse
    {
        abort_unless(Gate::allows('view', $scene), 404);

        $participant = $scene->participantFor($request->user());
        $query = Post::query()->where('scene_id', $scene->getKey());

        $target = $participant?->last_read_post_id
            ? (clone $query)->where('id', '>', $participant->last_read_post_id)->min('id')
            : null;
        $target ??= $participant?->last_read_post_id ? $query->max('id') : $query->min('id');

        return $this->redirectToPost($scene, $target ? (int) $target : null);
    }

    public function reply(Request $request, Scene $scene): RedirectResponse
    {
        abort_unless(Gate::allows('view', $scene), 404);

        $data = $request->validate([
            'body' => ['required', 'string', 'min:2', 'max:'.config('hollow.scenes.max_post_length')],
        ], [], ['body' => 'réponse']);

        if ($request->input('action') === 'apercu') {
            Gate::authorize('draft', $scene);
            $this->scenes->saveDraft($scene, $request->user(), $data['body']);

            return redirect()->to(route('scenes.show', ['scene' => $scene, 'page' => $this->lastPage($scene)]).'#ecrire')->with('preview', true);
        }

        Gate::authorize('reply', $scene);

        $post = $this->scenes->reply($scene, $request->user(), $data['body']);

        return $this->redirectToPost($scene, $post->getKey())->with('status', 'Réponse publiée.')
            ->with('clear_local', 'hollow-creek-scene-'.$scene->getKey());
    }

    /**
     * Aperçu de la mise en forme pendant l'écriture (aucun enregistrement).
     */
    public function preview(Request $request): JsonResponse
    {
        $data = $request->validate(['body' => ['nullable', 'string', 'max:'.config('hollow.scenes.max_post_length')]]);

        return response()->json([
            'html' => SceneText::render($data['body'] ?? '')->toHtml(),
            'words' => SceneText::wordCount($data['body'] ?? ''),
        ]);
    }

    public function saveDraft(Request $request, Scene $scene): JsonResponse
    {
        Gate::authorize('draft', $scene);

        $data = $request->validate(['body' => ['nullable', 'string', 'max:'.config('hollow.scenes.max_post_length')]]);

        $draft = $this->scenes->saveDraft($scene, $request->user(), (string) ($data['body'] ?? ''));

        return response()->json([
            'saved' => true,
            'saved_at' => ($draft?->updated_at ?? now())->locale('fr')->isoFormat('LT'),
        ]);
    }

    public function editPost(Request $request, Scene $scene, Post $post): View
    {
        Gate::authorize('update', $post);

        return view('scenes.edit-post', ['scene' => $scene, 'post' => $post]);
    }

    public function updatePost(Request $request, Scene $scene, Post $post): RedirectResponse
    {
        Gate::authorize('update', $post);

        $data = $request->validate([
            'body' => ['required', 'string', 'min:2', 'max:'.config('hollow.scenes.max_post_length')],
        ], [], ['body' => 'réponse']);

        $this->scenes->editPost($post, $request->user(), $data['body']);

        return $this->redirectToPost($scene, $post->getKey())->with('status', 'Réponse modifiée.');
    }

    public function updateStatus(Request $request, Scene $scene): RedirectResponse
    {
        Gate::authorize('manage', $scene);

        $data = $request->validate(['status' => ['required', Rule::enum(SceneStatus::class)]]);

        $this->scenes->changeStatus($scene, SceneStatus::from($data['status']), $request->user());

        return redirect()->route('scenes.show', $scene)->with('status', 'Statut de la scène : '.$scene->status->getLabel().'.');
    }

    /**
     * Archive personnelle d'une scène terminée : une page HTML autonome, imprimable en PDF.
     */
    public function export(Request $request, Scene $scene): Response
    {
        abort_unless(Gate::allows('view', $scene), 404);
        Gate::authorize('export', $scene);

        $scene->load(['location', 'activeParticipants.character']);

        $html = view('scenes.export', [
            'scene' => $scene,
            'posts' => Post::query()->where('scene_id', $scene->getKey())->with('character')->orderBy('id')->get(),
            'participants' => $scene->participants()->with('character')->whereIn('status', [ParticipantStatus::Accepted->value, ParticipantStatus::Left->value])->get(),
        ])->render();

        $filename = 'hollow-creek-scene-'.$scene->getKey().'-'.(Str::slug($scene->title) ?: 'sans-titre').'.html';

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    private function redirectToPost(Scene $scene, ?int $postId): RedirectResponse
    {
        if ($postId === null) {
            return redirect()->route('scenes.show', $scene);
        }

        $position = Post::query()->where('scene_id', $scene->getKey())->where('id', '<=', $postId)->count();
        $page = max(1, (int) ceil($position / config('hollow.scenes.posts_per_page')));

        return redirect()->to(route('scenes.show', ['scene' => $scene, 'page' => $page]).'#reponse-'.$postId);
    }

    private function lastPage(Scene $scene): int
    {
        $count = Post::query()->where('scene_id', $scene->getKey())->count();

        return max(1, (int) ceil($count / config('hollow.scenes.posts_per_page')));
    }

    /**
     * Personnages acceptés des autres joueurs, pour les invitations.
     *
     * @return \Illuminate\Support\Collection<int, Character>
     */
    private function partnerOptions(Request $request, ?Scene $scene = null)
    {
        $excluded = $scene
            ? $scene->participants->whereIn('status', [ParticipantStatus::Invited, ParticipantStatus::Accepted])->pluck('user_id')->all()
            : [];

        return Character::query()
            ->accepted()
            ->with('user')
            ->where('user_id', '!=', $request->user()->getKey())
            ->whereNotIn('user_id', $excluded)
            ->whereHas('user', fn ($query) => $query->whereNull('suspended_at'))
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->limit(500)
            ->get();
    }
}
