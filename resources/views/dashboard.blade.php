<x-layout title="Tableau de bord" robots="noindex">
    <div class="container">
        <header class="page-header">
            <p class="eyebrow">{{ $rpDate }} · date RP</p>
            <h1>{{ $greeting }}, {{ $user->pseudo }}</h1>
        </header>

        <div class="stack">
            @if (request()->boolean('verified'))
                <div class="alert alert--success" role="status"><p>Votre adresse e-mail est vérifiée : bienvenue à Tsukimori.</p></div>
            @endif
            <x-flash />
            @if ($census && $characters->contains(fn ($character) => $character->status === \App\Enums\CharacterStatus::Accepted))
                <div class="alert alert--warning" role="status"><p><strong>Recensement en cours</strong> jusqu’au {{ $census->ends_at->locale('fr')->isoFormat('LL') }} : <a href="{{ route('census.show') }}">confirmez les personnages que vous jouez toujours</a>.</p></div>
            @endif
        </div>

        <div class="dashboard-grid">
            <section class="card stack" aria-labelledby="titre-etapes">
                <h2 id="titre-etapes" class="card__title">Premiers pas</h2>
                <ul class="checklist">
                    <li>
                        <span class="state state--done" aria-hidden="true"></span>
                        <span>Compte créé et adresse e-mail vérifiée.</span>
                    </li>
                    <li>
                        <span class="state" aria-hidden="true"></span>
                        <span><a href="{{ route('pages.reglement') }}">Lire le règlement</a> de Tsukimori.</span>
                    </li>
                    <li>
                        <span class="state {{ $hasTwoFactor ? 'state--done' : '' }}" aria-hidden="true"></span>
                        <span>
                            @if ($hasTwoFactor)
                                Double authentification activée.
                            @else
                                Protéger votre compte avec la <a href="{{ route('account.edit') }}#double-authentification">double authentification</a> (conseillé).
                            @endif
                        </span>
                    </li>
                </ul>
            </section>

            <section class="card stack" aria-labelledby="titre-scenes">
                <h2 id="titre-scenes" class="card__title">Vos scènes</h2>
                @php($turns = $activeScenes->where('yourTurn', true))
                @if ($invitations->isEmpty() && $activeScenes->isEmpty())
                    <p class="muted">Aucune scène en cours. Ouvrez-en une depuis un lieu de la ville, ou acceptez une invitation.</p>
                @else
                    <ul class="stack stack--tight plain-list">
                        @foreach ($invitations->take(3) as $invitation)
                            <li class="cluster"><span class="badge badge--wait">Invitation</span> <a href="{{ route('scenes.show', $invitation->scene) }}">{{ $invitation->scene->title }}</a></li>
                        @endforeach
                        @foreach ($turns->take(5) as $item)
                            <li class="cluster"><span class="badge badge--wait">À vous</span> <a href="{{ route('scenes.unread', $item['scene']) }}">{{ $item['scene']->title }}</a></li>
                        @endforeach
                        @foreach ($activeScenes->where('yourTurn', false)->where('unread', '>', 0)->take(5) as $item)
                            <li class="cluster"><span class="badge">{{ $item['unread'] }} non {{ $item['unread'] > 1 ? 'lues' : 'lue' }}</span> <a href="{{ route('scenes.unread', $item['scene']) }}">{{ $item['scene']->title }}</a></li>
                        @endforeach
                    </ul>
                @endif
                @if ($drafts->isNotEmpty())
                    <p class="muted">{{ $drafts->count() }} {{ $drafts->count() > 1 ? 'brouillons' : 'brouillon' }} à reprendre.</p>
                @endif
                <div class="cluster">
                    <a class="btn" href="{{ route('scenes.mine') }}">Mes scènes</a>
                    <a class="link-button" href="{{ route('scenes.create') }}">Ouvrir une scène</a>
                </div>
            </section>

            <section class="card stack" aria-labelledby="titre-communaute">
                <h2 id="titre-communaute" class="card__title">Communauté</h2>
                <ul class="plain-list stack stack--tight">
                    <li><a href="{{ route('messages.index') }}">Messages</a>@if ($unreadMessages > 0) <span class="badge badge--wait">{{ $unreadMessages }} non {{ $unreadMessages > 1 ? 'lues' : 'lue' }}</span>@endif</li>
                    <li><a href="{{ route('relationships.index') }}">Relations</a>@if ($pendingRelations > 0) <span class="badge badge--wait">{{ $pendingRelations }} {{ $pendingRelations > 1 ? 'propositions' : 'proposition' }}</span>@endif</li>
                    <li><a href="{{ route('feed.index') }}">Murmures</a></li>
                    <li><a href="{{ route('events.index') }}">Événements</a></li>
                    <li><a href="{{ route('contact.mine') }}">Mes demandes à l’équipe</a>@if ($openTickets > 0) <span class="badge badge--wait">Réponse reçue</span>@endif</li>
                </ul>
            </section>

            <section class="card stack" aria-labelledby="titre-personnages">
                <h2 id="titre-personnages" class="card__title">Vos personnages</h2>
                @if ($characters->isEmpty())
                    <p class="muted">Aucun personnage pour l’instant. Un compte peut en tenir plusieurs, chacun relu par l’équipe.</p>
                @else
                    <ul class="stack stack--tight plain-list">
                        @foreach ($characters as $character)
                            <li class="cluster">
                                <span>{{ $character->fullName() }}</span>
                                <x-status-badge :status="$character->status" />
                            </li>
                        @endforeach
                    </ul>
                @endif
                <div class="cluster">
                    <a class="btn" href="{{ route('characters.mine') }}">Mes personnages</a>
                    @if ($remaining > 0)
                        <a class="link-button" href="{{ route('faceclaims.index') }}">Chercher un avatar</a>
                    @endif
                </div>
            </section>

            @if ($isStaff)
                <section class="card stack" aria-labelledby="titre-equipe">
                    <h2 id="titre-equipe" class="card__title">Équipe</h2>
                    @if ($hasTwoFactor)
                        <p class="muted">Votre accès à l’administration est actif.</p>
                        <div><a class="btn" href="{{ url('/admin') }}">Ouvrir l’administration</a></div>
                    @else
                        <p class="muted">L’administration exige la double authentification. Activez-la sur votre compte pour y accéder.</p>
                        <div><a class="btn" href="{{ route('account.edit') }}#double-authentification">Activer la double authentification</a></div>
                    @endif
                </section>
            @endif
        </div>
    </div>
</x-layout>
