<article id="murmure-{{ $post->id }}" class="feed-post card">
    <header class="post__header">
        @if ($post->character)
            <x-portrait :character="$post->character" size="small" />
        @endif
        <div>
            <p class="post__character">
                @if ($post->character?->status === \App\Enums\CharacterStatus::Accepted)
                    <a href="{{ route('characters.show', $post->character) }}">{{ $post->character_name }}</a>
                @else
                    {{ $post->character_name }}
                @endif
            </p>
            <p class="post__meta">{{ $post->kind->getLabel() }} · <a href="{{ route('feed.show', $post) }}"><time datetime="{{ $post->created_at->toIso8601String() }}">{{ $post->created_at->locale('fr')->diffForHumans() }}</time></a></p>
        </div>
    </header>
    @if ($post->imageUrl())
        <figure class="feed-post__image">
            <img src="{{ $post->imageUrl() }}" alt="" loading="lazy" decoding="async">
            @if ($post->image_credit)<figcaption>{{ $post->image_credit }}</figcaption>@endif
        </figure>
    @endif
    @if ($post->body)
        <div @class(['post__body', 'prose', 'feed-post__announce' => $post->kind === \App\Enums\FeedKind::Announcement])>{{ $post->html() }}</div>
    @endif
    <footer class="cluster feed-post__footer">
        <a href="{{ route('feed.show', $post) }}#commentaires">{{ $post->comments_count }} {{ $post->comments_count > 1 ? 'commentaires' : 'commentaire' }}</a>
        <x-report-link type="murmure" :id="$post->id" :owner="$post->user_id" />
        @auth
            @if ($post->user_id === auth()->id())
                <form method="POST" action="{{ route('feed.destroy', $post) }}" data-confirm="Supprimer cette publication ?">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="link-button">Supprimer</button>
                </form>
            @endif
        @endauth
    </footer>
</article>
