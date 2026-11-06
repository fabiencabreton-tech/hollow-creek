<x-layout title="Petites annonces" description="Emplois, ventes, logements, objets perdus et messages personnels des habitants de Tsukimori.">
    <div class="container">
        <header class="page-header">
            <x-breadcrumbs :items="['Accueil' => route('home'), 'Petites annonces' => null]" />
            <h1>Petites annonces</h1>
            <p class="lede">Le panneau de liège du konbini, en ligne. Chaque annonce est passée par un personnage et disparaît d’elle-même à sa date d’expiration.</p>
        </header>

        <nav aria-label="Rubriques des annonces">
            <ul class="rubrics">
                <li><a href="{{ route('ads.index') }}" @if (! $category) aria-current="page" @endif>Toutes</a></li>
                @foreach ($categories as $item)
                    <li><a href="{{ route('ads.index', ['rubrique' => $item->value]) }}" @if ($category === $item) aria-current="page" @endif>{{ $item->getLabel() }}</a></li>
                @endforeach
            </ul>
        </nav>

        <div class="ads-layout page-body">
            <div class="stack stack--loose">
                <x-flash />

                @if ($ads->isEmpty())
                    <p class="empty-note">{{ $category ? 'Aucune annonce dans cette rubrique.' : 'Le panneau est vide pour le moment.' }}</p>
                @else
                    <div class="ad-board">
                        @foreach ($ads as $ad)
                            <article id="annonce-{{ $ad->id }}" class="ad card">
                                <p class="eyebrow">{{ $ad->category->getLabel() }}</p>
                                <h2 class="ad__title">{{ $ad->title }}</h2>
                                <div class="prose ad__body">{{ $ad->html() }}</div>
                                @if ($ad->contact)
                                    <p class="ad__contact"><span class="visually-hidden">Pour répondre : </span>{{ $ad->contact }}</p>
                                @endif
                                <footer class="ad__footer">
                                    <p class="muted">
                                        @if ($ad->character?->status === \App\Enums\CharacterStatus::Accepted)
                                            <a href="{{ route('characters.show', $ad->character) }}">{{ $ad->character_name }}</a>
                                        @else
                                            {{ $ad->character_name }}
                                        @endif
                                        · jusqu’au <time datetime="{{ $ad->expires_at->toDateString() }}">{{ $ad->expires_at->locale('fr')->isoFormat('LL') }}</time>
                                    </p>
                                    <p class="cluster">
                                        @auth
                                            @if ($ad->author && $ad->user_id !== auth()->id())
                                                <a href="{{ route('messages.create', ['a' => $ad->author->pseudo]) }}">Répondre en message privé</a>
                                            @endif
                                        @endauth
                                        <x-report-link type="annonce" :id="$ad->id" :owner="$ad->user_id" />
                                    </p>
                                </footer>
                            </article>
                        @endforeach
                    </div>

                    {{ $ads->links('partials.pagination') }}
                @endif
            </div>

            <aside class="stack stack--loose" aria-label="Passer une annonce">
                @if ($myCharacters->isNotEmpty())
                    <form class="form card" method="POST" action="{{ route('ads.store') }}" novalidate>
                        @csrf
                        <h2 class="card__title">Passer une annonce</h2>
                        <x-errors-summary />
                        <div class="field">
                            <label for="champ-annonce-personnage">Signée par</label>
                            <select id="champ-annonce-personnage" name="character_id">
                                @foreach ($myCharacters as $character)
                                    <option value="{{ $character->id }}" @selected((int) old('character_id') === $character->id)>{{ $character->fullName() }}</option>
                                @endforeach
                            </select>
                            @error('character_id')<p class="error">{{ $message }}</p>@enderror
                        </div>
                        <div class="field">
                            <label for="champ-annonce-rubrique">Rubrique</label>
                            <select id="champ-annonce-rubrique" name="category">
                                @foreach ($categories as $item)
                                    <option value="{{ $item->value }}" @selected(old('category', $category?->value) === $item->value)>{{ $item->getLabel() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <x-field name="title" label="Titre" maxlength="120" required />
                        <div class="field">
                            <label for="champ-annonce-texte">Texte</label>
                            <textarea id="champ-annonce-texte" name="body" rows="5" maxlength="1500" required @error('body') aria-invalid="true" aria-describedby="champ-annonce-texte-erreur" @enderror>{{ old('body') }}</textarea>
                            @error('body')<p class="error" id="champ-annonce-texte-erreur">{{ $message }}</p>@enderror
                        </div>
                        <x-field name="contact" label="Pour répondre (facultatif)" maxlength="200" hint="Dans l’univers : « Demander Hana au Kissa Komorebi », un numéro fictif…" />
                        <div class="field">
                            <label for="champ-annonce-duree">Durée</label>
                            <select id="champ-annonce-duree" name="duration">
                                @foreach ($durations as $days)
                                    <option value="{{ $days }}" @selected((int) old('duration', 14) === $days)>{{ $days }} jours</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-actions"><button type="submit" class="btn">Publier l’annonce</button></div>
                    </form>
                @elseauth
                    <p class="muted card">Il faut un personnage accepté pour passer une annonce.</p>
                @else
                    <p class="muted card"><a href="{{ route('login') }}">Connectez-vous</a> pour passer une annonce.</p>
                @endif

                @if ($myAds->isNotEmpty())
                    <section class="card stack" aria-labelledby="mes-annonces">
                        <h2 id="mes-annonces" class="card__title">Mes annonces</h2>
                        <ul class="plain-list stack">
                            @foreach ($myAds as $ad)
                                <li class="stack stack--tight">
                                    <p><strong>{{ $ad->title }}</strong><br>
                                        <span class="muted">{{ $ad->character_name }} · {{ $ad->isActive() ? 'en ligne jusqu’au '.$ad->expires_at->locale('fr')->isoFormat('LL') : 'expirée' }}</span></p>
                                    <div class="cluster">
                                        <form method="POST" action="{{ route('ads.renew', $ad) }}" class="cluster">
                                            @csrf
                                            <input type="hidden" name="duration" value="14">
                                            <button type="submit" class="link-button">{{ $ad->isActive() ? 'Prolonger de 14 jours' : 'Republier 14 jours' }}</button>
                                        </form>
                                        <form method="POST" action="{{ route('ads.destroy', $ad) }}" data-confirm="Retirer cette annonce ?">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="link-button">Retirer</button>
                                        </form>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif
            </aside>
        </div>
    </div>
</x-layout>
