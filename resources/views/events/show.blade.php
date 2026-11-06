<x-layout :title="$event->title" :description="$event->summary" :robots="$isPreview ? 'noindex' : null">
    <div class="container">
        <header class="page-header">
            <x-breadcrumbs :items="['Événements' => route('events.index'), $event->title => null]" />
            <x-preview-banner :show="$isPreview" />
            <div class="cluster">
                <p class="eyebrow">{{ $event->rp_date ?: 'Événement' }}@if ($event->location) · <a href="{{ route('locations.show', $event->location) }}">{{ $event->location->name }}</a>@endif</p>
                <x-status-badge :status="$event->status" />
            </div>
            <h1>{{ $event->title }}</h1>
            @if ($event->summary)
                <p class="lede">{{ $event->summary }}</p>
            @endif
        </header>

        <div class="stack">
            <x-flash />
            <x-errors-summary />
        </div>

        <div class="entry">
            <div>
                @if ($event->body)
                    <div class="prose">{{ \App\Support\Markdown::render($event->body) }}</div>
                @endif

                <section class="entry__section">
                    <h2>Scènes</h2>
                    @if ($scenes->isEmpty())
                        <p class="muted">Aucune scène publique rattachée pour le moment.</p>
                    @else
                        <div class="scene-list">
                            @foreach ($scenes as $scene)
                                @include('scenes.partials.card', ['scene' => $scene])
                            @endforeach
                        </div>
                    @endif
                    @auth
                        @if ($event->status !== \App\Enums\EventStatus::Finished)
                            <p><a class="btn btn--ghost" href="{{ route('scenes.create', ['evenement' => $event->slug, 'lieu' => $event->location?->slug]) }}">Ouvrir une scène pour cet événement</a></p>
                        @endif
                    @endauth
                </section>

                @if ($articles->isNotEmpty())
                    <section class="entry__section">
                        <h2>Dans la Gazette</h2>
                        <ul class="plain-list stack stack--tight">
                            @foreach ($articles as $article)
                                <li><a href="{{ route('gazette.show', $article) }}">{{ $article->title }}</a></li>
                            @endforeach
                        </ul>
                    </section>
                @endif
            </div>

            <aside class="entry__aside" aria-label="Participants">
                <x-figure :url="$event->imageUrl()" :credit="$event->image_credit" :alt="$event->title" />
                <div class="card stack">
                    <p class="eyebrow">Participants ({{ $event->participants->count() }})</p>
                    @if ($event->participants->isEmpty())
                        <p class="muted">Personne n’est encore inscrit.</p>
                    @else
                        <ul class="plain-list stack stack--tight">
                            @foreach ($event->participants as $participant)
                                @if ($participant->character)
                                    <li class="cluster">
                                        <a href="{{ route('characters.show', $participant->character) }}">{{ $participant->character->fullName() }}</a>
                                        @if ($myRegistrations->contains('id', $participant->id))
                                            <form method="POST" action="{{ route('events.unregister', [$event, $participant]) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="link-button">Se retirer</button>
                                            </form>
                                        @endif
                                    </li>
                                @endif
                            @endforeach
                        </ul>
                    @endif

                    @if ($event->acceptsRegistrations() && $myCharacters->isNotEmpty())
                        <form method="POST" action="{{ route('events.register', $event) }}" class="stack stack--tight">
                            @csrf
                            <label for="champ-inscription">Inscrire</label>
                            <select id="champ-inscription" name="character_id">
                                @foreach ($myCharacters as $character)
                                    <option value="{{ $character->id }}" @selected(auth()->user()?->active_character_id === $character->id)>{{ $character->fullName() }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn">S’inscrire</button>
                        </form>
                    @elseif (! $event->acceptsRegistrations())
                        <p class="muted">Inscriptions closes.</p>
                    @endif
                </div>
            </aside>
        </div>
    </div>
</x-layout>
