{{-- Tableau de bord d'un joueur qui a au moins un personnage accepté : le jeu du jour d'abord. --}}
<x-layout title="Tableau de bord" robots="noindex">
    <section class="dash-hero">
        <x-ambiance name="foret-brume" eager class="dash-hero__image" />
        <div class="container">
            <header class="dash-header">
                <div class="stack stack--tight">
                    <p class="eyebrow">{{ $rpDate }} · date RP</p>
                    <h1>{{ $greeting }}, {{ $user->pseudo }}</h1>
                </div>
                <nav class="dash-quick" aria-label="Actions rapides">
                    <a class="btn" href="{{ route('scenes.create') }}">Ouvrir une scène</a>
                    <a class="btn btn--ghost" href="{{ route('feed.index') }}">Murmurer</a>
                    <a class="btn btn--ghost" href="{{ route('journal.create', $active) }}">Écrire au journal</a>
                </nav>
            </header>
        </div>
    </section>
    <div class="container">

        <div class="stack">
            @if (request()->boolean('verified'))
                <div class="alert alert--success" role="status"><p>Votre adresse e-mail est vérifiée : bienvenue à Tsukimori.</p></div>
            @endif
            <x-flash />
            <x-errors-summary />
            {{-- Sur téléphone, tant que Tsukimori n'est pas installé. --}}
            <p class="app-promo"><img src="{{ asset('icones/icone-192.png') }}" width="32" height="32" alt=""><a href="{{ route('pages.application') }}">Installer Tsukimori sur votre téléphone</a></p>
            @if ($census)
                <div class="alert alert--warning" role="status"><p><strong>Recensement en cours</strong> jusqu’au {{ $census->ends_at->locale('fr')->isoFormat('LL') }} : <a href="{{ route('census.show') }}">confirmez les personnages que vous jouez toujours</a>.</p></div>
            @endif
        </div>

        <div class="dash">
            {{-- Personnage actif --}}
            <section class="dash-card dash-active" id="personnage" aria-labelledby="titre-actif">
                <h2 id="titre-actif" class="visually-hidden">Personnage actif</h2>
                <div class="dash-active__who">
                    <x-portrait :character="$active" size="small" />
                    <div class="stack stack--tight">
                        <p class="eyebrow">Vous jouez</p>
                        <p class="dash-active__name"><a href="{{ route('characters.show', $active) }}">{{ $active->fullName() }}</a></p>
                        <p class="muted dash-active__meta">
                            {{ collect([$active->species?->name, $active->family?->shortName(), $active->residence?->name])->filter()->join(' · ') }}
                        </p>
                    </div>
                </div>
                @if ($accepted->count() > 1)
                    <form class="dash-active__switch" method="POST" action="{{ route('characters.activate') }}">
                        @csrf
                        <label for="champ-actif">Changer de personnage</label>
                        <div class="cluster">
                            <select id="champ-actif" name="character_id">
                                @foreach ($accepted as $character)
                                    <option value="{{ $character->id }}" @selected($character->id === $active->id)>{{ $character->fullName() }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn btn--ghost">Jouer</button>
                        </div>
                        <p class="hint">Le personnage actif est proposé en premier dans tous les formulaires.</p>
                    </form>
                @endif
                <ul class="dash-links">
                    <li><a href="{{ route('journal.index', $active) }}">Journal</a></li>
                    <li><a href="{{ route('characters.profile.edit', $active) }}">Profil</a></li>
                    <li><a href="{{ route('relationships.index') }}">Relations</a>@if ($pendingRelations > 0) <span class="badge badge--wait">{{ $pendingRelations }}</span>@endif</li>
                    <li><a href="{{ route('clues.mine') }}">Indices</a></li>
                    <li><a href="{{ route('characters.mine') }}">Tous mes personnages</a></li>
                </ul>
            </section>

            {{-- RP --}}
            <section class="dash-card dash-rp" aria-labelledby="titre-rp">
                <div class="dash-card__head">
                    <h2 id="titre-rp" class="card__title">Vos RP</h2>
                    <a href="{{ route('scenes.mine') }}">Toutes mes scènes</a>
                </div>
                <div class="dash-rp__columns">
                    <div>
                        <h3 class="dash-rp__title">À vous de répondre <span class="badge {{ $yourTurn->count() + $invitations->count() > 0 ? 'badge--wait' : '' }}">{{ $yourTurn->count() + $invitations->count() }}</span></h3>
                        @if ($yourTurn->isEmpty() && $invitations->isEmpty())
                            <p class="muted">Rien ne vous attend. Profitez-en pour ouvrir une scène.</p>
                        @else
                            <ul class="dash-list">
                                @foreach ($invitations as $invitation)
                                    <li><span class="badge badge--wait">Invitation</span> <a href="{{ route('scenes.show', $invitation->scene) }}">{{ $invitation->scene->title }}</a></li>
                                @endforeach
                                @foreach ($yourTurn as $item)
                                    <li>
                                        <a href="{{ route('scenes.unread', $item['scene']) }}">{{ $item['scene']->title }}</a>
                                        @if ($item['unread'] > 0)<span class="muted"> · {{ $item['unread'] }} {{ $item['unread'] > 1 ? 'nouvelles réponses' : 'nouvelle réponse' }}</span>@endif
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                    <div>
                        <h3 class="dash-rp__title">En attente des autres <span class="badge">{{ $waiting->count() }}</span></h3>
                        @if ($waiting->isEmpty())
                            <p class="muted">Aucune scène en attente.</p>
                        @else
                            <ul class="dash-list">
                                @foreach ($waiting->take(6) as $item)
                                    <li><a href="{{ route('scenes.show', $item['scene']) }}">{{ $item['scene']->title }}</a></li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                    <div>
                        <h3 class="dash-rp__title">Brouillons <span class="badge">{{ $drafts->count() }}</span></h3>
                        @if ($drafts->isEmpty())
                            <p class="muted">Aucun brouillon. Vos réponses en cours s’enregistrent toutes seules.</p>
                        @else
                            <ul class="dash-list">
                                @foreach ($drafts->take(6) as $draft)
                                    <li><a href="{{ route('scenes.show', $draft->scene) }}">{{ $draft->scene->title }}</a> <span class="muted">· {{ $draft->updated_at->locale('fr')->diffForHumans() }}</span></li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
            </section>

            {{-- Messages et notifications --}}
            <section class="dash-card" aria-labelledby="titre-courrier">
                <div class="dash-card__head">
                    <h2 id="titre-courrier" class="card__title">Courrier</h2>
                </div>
                <ul class="dash-counters">
                    <li><a href="{{ route('messages.index') }}"><span class="dash-counters__value">{{ $unreadMessages }}</span> {{ $unreadMessages > 1 ? 'messages non lus' : 'message non lu' }}</a></li>
                    <li><a href="{{ route('notifications.index') }}"><span class="dash-counters__value">{{ $unreadNotifications }}</span> {{ $unreadNotifications > 1 ? 'notifications' : 'notification' }}</a></li>
                    @if ($openTickets > 0)
                        <li><a href="{{ route('contact.mine') }}"><span class="dash-counters__value">{{ $openTickets }}</span> réponse de l’équipe</a></li>
                    @endif
                </ul>
                @if ($notifications->isNotEmpty())
                    <ul class="dash-list dash-list--notifications">
                        @foreach ($notifications as $notification)
                            <li @class(['is-unread' => $notification->read_at === null])>
                                <a href="{{ route('notifications.open', $notification) }}">{{ $notification->title }}</a>
                                <span class="muted"> · {{ $notification->created_at?->locale('fr')->diffForHumans() }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            {{-- Recherches de liens et de RP --}}
            <section class="dash-card" aria-labelledby="titre-recherches">
                <div class="dash-card__head">
                    <h2 id="titre-recherches" class="card__title">Recherches de liens et de RP</h2>
                </div>
                <h3 class="dash-rp__title">Scènes ouvertes à rejoindre</h3>
                @if ($openScenes->isEmpty())
                    <p class="muted">Aucune scène ouverte pour l’instant. <a href="{{ route('feed.index') }}">Lancez un appel dans les murmures</a>.</p>
                @else
                    <ul class="dash-list">
                        @foreach ($openScenes as $scene)
                            <li><a href="{{ route('scenes.show', $scene) }}">{{ $scene->title }}</a>@if ($scene->location)<span class="muted"> · {{ $scene->location->name }}</span>@endif</li>
                        @endforeach
                    </ul>
                @endif
                <h3 class="dash-rp__title">Préliens familiaux</h3>
                @if ($prelinks->isEmpty())
                    <p class="muted">Aucun prélien ouvert. <a href="{{ route('families.index') }}">Voir les familles</a>.</p>
                @else
                    <ul class="dash-list">
                        @foreach ($prelinks as $prelink)
                            <li><a href="{{ route('families.show', $prelink->family) }}#prelien-{{ $prelink->id }}">{{ $prelink->title }}</a><span class="muted"> · {{ $prelink->relation->getLabel() }}, {{ $prelink->family->shortName() }}</span></li>
                        @endforeach
                    </ul>
                @endif
                <p class="cluster dash-more">
                    <a href="{{ route('scenes.index') }}">Scènes publiques</a>
                    <a href="{{ route('predefined.index') }}">Prédéfinis</a>
                    <a href="{{ route('ads.index') }}">Petites annonces</a>
                </p>
            </section>

            {{-- Actualités et événements --}}
            <section class="dash-card" aria-labelledby="titre-actualites">
                <div class="dash-card__head">
                    <h2 id="titre-actualites" class="card__title">Actualités et événements</h2>
                </div>
                @if ($events->isEmpty() && $news->isEmpty())
                    <p class="muted">Rien de neuf à Tsukimori. Pour l’instant.</p>
                @endif
                @if ($events->isNotEmpty())
                    <ul class="dash-list">
                        @foreach ($events as $event)
                            <li><span class="badge badge--wait">Événement</span> <a href="{{ route('events.show', $event) }}">{{ $event->title }}</a>@if ($event->rp_date)<span class="muted"> · {{ $event->rp_date }}</span>@endif</li>
                        @endforeach
                    </ul>
                @endif
                @if ($news->isNotEmpty())
                    <ul class="dash-list">
                        @foreach ($news as $article)
                            <li><span class="badge">Gazette</span> <a href="{{ route('gazette.show', $article) }}">{{ $article->title }}</a></li>
                        @endforeach
                    </ul>
                @endif
                <p class="cluster dash-more">
                    <a href="{{ route('gazette.index') }}">La Gazette</a>
                    <a href="{{ route('events.index') }}">Événements</a>
                    <a href="{{ route('investigations.index') }}">Enquêtes</a>
                </p>
            </section>

            {{-- Personnages --}}
            <section class="dash-card" aria-labelledby="titre-personnages">
                <div class="dash-card__head">
                    <h2 id="titre-personnages" class="card__title">Vos personnages</h2>
                    <a href="{{ route('characters.mine') }}">Gérer</a>
                </div>
                <ul class="dash-list">
                    @foreach ($characters as $character)
                        <li class="cluster">
                            <span>{{ $character->fullName() }}</span>
                            <x-status-badge :status="$character->status" />
                        </li>
                    @endforeach
                </ul>
                @if ($remaining > 0)
                    <form method="POST" action="{{ route('characters.store') }}">
                        @csrf
                        <button type="submit" class="link-button">Créer un nouveau personnage ({{ $remaining }} {{ $remaining > 1 ? 'places libres' : 'place libre' }})</button>
                    </form>
                @endif
            </section>

            @if ($isStaff)
                <section class="dash-card" aria-labelledby="titre-equipe">
                    <h2 id="titre-equipe" class="card__title">Équipe</h2>
                    @if ($hasTwoFactor)
                        <div class="cluster">
                            <a class="btn" href="{{ url('/admin/a-valider') }}">Ouvrir la file de validation</a>
                            <a href="{{ url('/admin') }}">Ouvrir l’administration</a>
                        </div>
                    @else
                        <p class="muted">L’administration exige la double authentification.</p>
                        <div><a class="btn" href="{{ route('account.edit') }}#double-authentification">Activer la double authentification</a></div>
                    @endif
                </section>
            @endif
        </div>
    </div>
</x-layout>
