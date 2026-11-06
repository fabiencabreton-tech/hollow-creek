<x-layout :title="'Murmure de '.$post->character_name">
    <div class="container">
        <header class="page-header">
            <x-breadcrumbs :items="['Murmures' => route('feed.index'), $post->character_name => null]" />
        </header>

        <div class="feed page-body">
            <div class="stack stack--loose">
                <x-flash />
                <x-errors-summary />

                @include('feed.partials.post', ['post' => $post])

                <section id="commentaires" aria-labelledby="titre-commentaires" class="stack">
                    <h2 id="titre-commentaires" class="section__title">Commentaires</h2>
                    @forelse ($post->comments as $comment)
                        <article id="commentaire-{{ $comment->id }}" class="feed-comment">
                            <p class="post__meta">
                                <strong>{{ $comment->character_name }}</strong> · {{ $comment->created_at->locale('fr')->diffForHumans() }}
                                <x-report-link type="commentaire" :id="$comment->id" :owner="$comment->user_id" />
                            </p>
                            <div>{{ $comment->html() }}</div>
                            @auth
                                @if ($comment->user_id === auth()->id())
                                    <form method="POST" action="{{ route('feed.comment.destroy', $comment) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="link-button">Supprimer</button>
                                    </form>
                                @endif
                            @endauth
                        </article>
                    @empty
                        <p class="muted">Aucun commentaire.</p>
                    @endforelse

                    @if ($myCharacters->isNotEmpty())
                        <form class="form card" method="POST" action="{{ route('feed.comment', $post) }}" novalidate>
                            @csrf
                            <div class="field">
                                <label for="champ-commentaire-personnage">Signé par</label>
                                <select id="champ-commentaire-personnage" name="character_id">
                                    @foreach ($myCharacters as $character)
                                        <option value="{{ $character->id }}" @selected(auth()->user()?->active_character_id === $character->id)>{{ $character->fullName() }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="field">
                                <label for="champ-commentaire">Commentaire</label>
                                <textarea id="champ-commentaire" name="body" rows="3" maxlength="2000" required>{{ old('body') }}</textarea>
                                @error('body')<p class="error">{{ $message }}</p>@enderror
                            </div>
                            <div class="form-actions"><button type="submit" class="btn">Commenter</button></div>
                        </form>
                    @endif
                </section>
            </div>
        </div>
    </div>
</x-layout>
