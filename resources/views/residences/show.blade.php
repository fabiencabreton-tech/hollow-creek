@php
    $crumbs = ['La ville' => route('city.index'), 'Habitations' => route('residences.index')];
    $crumbs[$residence->name] = null;
@endphp
<x-layout :title="$residence->name" :description="$residence->summary" :robots="$isPreview ? 'noindex' : null">
    <div class="container">
        <header class="page-header">
            <x-breadcrumbs :items="$crumbs" />
            <x-preview-banner :show="$isPreview" />
            <p class="eyebrow">{{ $residence->kind->getLabel() }}{{ $district ? ' · '.$district->name : '' }}</p>
            <h1>{{ $residence->name }}</h1>
            @if ($residence->summary)
                <p class="lede">{{ $residence->summary }}</p>
            @endif
        </header>

        <div class="entry">
            <div class="stack stack--loose">
                <x-flash />
                <x-errors-summary />

                @if ($residence->description)
                    <div class="prose">{{ \App\Support\Markdown::render($residence->description) }}</div>
                @endif

                <section class="entry__section" aria-labelledby="habitants">
                    <h2 id="habitants">Habitants</h2>
                    <p class="muted">{{ $residence->residents->count() }} sur {{ $residence->capacity }} {{ $residence->capacity > 1 ? 'places' : 'place' }}.</p>
                    @if ($residence->residents->isEmpty())
                        <p>Personne n’habite ici pour le moment.</p>
                    @else
                        <ul class="plain-list cluster">
                            @foreach ($residence->residents as $resident)
                                <li><a href="{{ route('characters.show', $resident) }}">{{ $resident->fullName() }}</a></li>
                            @endforeach
                        </ul>
                    @endif
                </section>
            </div>

            <aside class="entry__aside" aria-label="Emménager">
                <x-figure :url="$residence->imageUrl()" :credit="$residence->image_credit" :alt="$residence->name" />

                @auth
                    @if (! $isPreview)
                        <div class="card stack">
                            @foreach ($livingHere as $character)
                                <form method="POST" action="{{ route('residences.move-out', $character) }}" class="cluster">
                                    @csrf
                                    <span>{{ $character->fullName() }} habite ici.</span>
                                    <button type="submit" class="link-button">Déménager</button>
                                </form>
                            @endforeach

                            @if ($candidates->isNotEmpty() && $residence->is_open && $freePlaces > 0)
                                <form method="POST" action="{{ route('residences.move-in', $residence) }}" class="stack">
                                    @csrf
                                    <div class="field">
                                        <label for="champ-emmenager">Emménager ici</label>
                                        <select id="champ-emmenager" name="character_id">
                                            @foreach ($candidates as $character)
                                                <option value="{{ $character->id }}">{{ $character->fullName() }}{{ $character->residence ? ' (quitte '.$character->residence->name.')' : '' }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div><button type="submit" class="btn">Emménager</button></div>
                                </form>
                            @elseif (! $residence->is_open)
                                <p class="muted">Cette habitation n’est pas libre. Pour y emménager, <a href="{{ route('contact.create') }}">écrivez à l’équipe</a>.</p>
                            @elseif ($freePlaces === 0)
                                <p class="muted">Il n’y a plus de place ici.</p>
                            @endif
                        </div>
                    @endif
                @endauth
            </aside>
        </div>
    </div>
</x-layout>
