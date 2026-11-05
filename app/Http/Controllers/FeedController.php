<?php

namespace App\Http\Controllers;

use App\Enums\CharacterStatus;
use App\Enums\FeedKind;
use App\Models\Character;
use App\Models\FeedComment;
use App\Models\FeedPost;
use App\Models\UserBlock;
use App\Services\Notifier;
use App\Support\ImageStore;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * « Murmures » : publications des personnages et commentaires signés.
 */
class FeedController extends Controller
{
    public function index(Request $request): View
    {
        return view('feed.index', [
            'posts' => FeedPost::query()
                ->with(['character.species', 'comments' => fn ($query) => $query->latest('id')->limit(3)])
                ->latest('id')
                ->paginate(15),
            'myCharacters' => $this->myCharacters($request),
        ]);
    }

    public function show(Request $request, FeedPost $feedPost): View
    {
        $feedPost->load(['character.species', 'comments.character.species']);

        return view('feed.show', [
            'post' => $feedPost,
            'myCharacters' => $this->myCharacters($request),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'character_id' => ['required', 'integer'],
            'kind' => ['required', Rule::enum(FeedKind::class)],
            'body' => [Rule::requiredIf(fn () => $request->input('kind') !== FeedKind::Image->value), 'nullable', 'string', 'max:3000'],
            'image' => [Rule::requiredIf(fn () => $request->input('kind') === FeedKind::Image->value), 'nullable', 'file', 'image', 'mimes:jpeg,png,webp,gif', 'max:2048'],
            'image_credit' => ['required_with:image', 'nullable', 'string', 'max:255'],
        ], [], [
            'character_id' => 'personnage',
            'kind' => 'type',
            'body' => 'texte',
            'image' => 'image',
            'image_credit' => 'crédit de l’image',
        ]);

        $character = $this->ownCharacter($request, (int) $data['character_id']);

        $post = new FeedPost;
        $post->forceFill([
            'user_id' => $request->user()->getKey(),
            'character_id' => $character->getKey(),
            'character_name' => $character->fullName(),
            'kind' => $data['kind'],
            'body' => filled($data['body'] ?? null) ? trim($data['body']) : null,
            'image_path' => $request->hasFile('image') ? app(ImageStore::class)->store($request->file('image'), 'murmures', 1400) : null,
            'image_credit' => filled($data['image_credit'] ?? null) ? trim($data['image_credit']) : null,
        ])->save();

        return redirect()->route('feed.index')->with('status', 'Publication partagée.');
    }

    public function destroy(Request $request, FeedPost $feedPost): RedirectResponse
    {
        abort_unless($feedPost->user_id === $request->user()->getKey(), 403);

        $feedPost->delete();

        return redirect()->route('feed.index')->with('status', 'Publication supprimée.');
    }

    public function comment(Request $request, FeedPost $feedPost): RedirectResponse
    {
        $data = $request->validate([
            'character_id' => ['required', 'integer'],
            'body' => ['required', 'string', 'max:2000'],
        ], [], ['character_id' => 'personnage', 'body' => 'commentaire']);

        $character = $this->ownCharacter($request, (int) $data['character_id']);
        $author = $feedPost->author;

        if ($author && UserBlock::between($request->user(), $author)) {
            throw ValidationException::withMessages(['body' => 'Un blocage existe entre vos deux comptes.']);
        }

        $comment = DB::transaction(function () use ($request, $feedPost, $character, $data): FeedComment {
            $comment = new FeedComment;
            $comment->forceFill([
                'feed_post_id' => $feedPost->getKey(),
                'user_id' => $request->user()->getKey(),
                'character_id' => $character->getKey(),
                'character_name' => $character->fullName(),
                'body' => trim($data['body']),
            ])->save();

            $feedPost->increment('comments_count');

            return $comment;
        });

        if ($author && ! $author->is($request->user())) {
            app(Notifier::class)->send(
                $author,
                'murmure',
                $character->fullName().' a commenté la publication de '.$feedPost->character_name,
                route('feed.show', $feedPost, absolute: false).'#commentaire-'.$comment->getKey(),
                Str::limit($comment->body, 200),
            );
        }

        return redirect()->to(route('feed.show', $feedPost).'#commentaire-'.$comment->getKey());
    }

    public function destroyComment(Request $request, FeedComment $comment): RedirectResponse
    {
        abort_unless($comment->user_id === $request->user()->getKey(), 403);

        $post = $comment->feedPost;
        $comment->delete();
        $post?->decrement('comments_count');

        return redirect()->route('feed.show', $post)->with('status', 'Commentaire supprimé.');
    }

    private function ownCharacter(Request $request, int $id): Character
    {
        $character = Character::query()->find($id);

        if ($character === null || $character->user_id !== $request->user()->getKey() || $character->status !== CharacterStatus::Accepted) {
            throw ValidationException::withMessages(['character_id' => 'Choisissez l’un de vos personnages acceptés.']);
        }

        return $character;
    }

    /**
     * @return \Illuminate\Support\Collection<int, Character>
     */
    private function myCharacters(Request $request)
    {
        $user = $request->user();

        return $user ? Character::query()->where('user_id', $user->getKey())->accepted()->orderBy('first_name')->get() : collect();
    }
}
