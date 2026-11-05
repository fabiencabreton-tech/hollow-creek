<x-layout title="Mes scènes" robots="noindex">
    <div class="container">
        <header class="page-header">
            <x-breadcrumbs :items="['Tableau de bord' => route('dashboard'), 'Mes scènes' => null]" />
            <h1>Mes scènes</h1>
            <p class="lede">Invitations, scènes où c’est à vous d’écrire, brouillons à reprendre.</p>
        </header>

        <div class="stack page-body stack--loose">
            <x-flash />
            <x-errors-summary />

            <div class="cluster">
                <a class="btn" href="{{ route('scenes.create') }}">Ouvrir une scène</a>
                <a class="btn btn--ghost" href="{{ route('scenes.index') }}">Scènes publiques</a>
            </div>

            @if ($invitations->isNotEmpty())
                <section class="card stack" aria-labelledby="titre-invitations">
                    <h2 id="titre-invitations" class="card__title">Invitations</h2>
                    <ul class="plain-list stack">
                        @foreach ($invitations as $invitation)
                            <li class="cluster">
                                <a href="{{ route('scenes.show', $invitation->scene) }}">{{ $invitation->scene->title }}</a>
                                <span class="muted">de {{ $invitation->scene->creator?->pseudo ?? 'un ancien membre' }}</span>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            <section aria-labelledby="titre-en-cours">
                <h2 id="titre-en-cours" class="section__title">En cours</h2>
                @if ($active->isEmpty())
                    <p class="muted">Aucune scène en cours.</p>
                @else
                    <ul class="plain-list inbox">
                        @foreach ($active as $item)
                            <li @class(['inbox__item', 'inbox__item--turn' => $item['yourTurn']])>
                                <a class="inbox__title" href="{{ route('scenes.unread', $item['scene']) }}">{{ $item['scene']->title }}</a>
                                <span class="muted">{{ $item['participant']->character?->fullName() }}@if ($item['scene']->location) · {{ $item['scene']->location->name }}@endif</span>
                                @if ($item['yourTurn'])
                                    <span class="badge badge--wait">À vous</span>
                                @endif
                                @if ($item['unread'] > 0)
                                    <span class="badge">{{ $item['unread'] }} non {{ $item['unread'] > 1 ? 'lues' : 'lue' }}</span>
                                @endif
                                @if ($item['scene']->status === \App\Enums\SceneStatus::Paused)
                                    <span class="badge">En pause</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            @if ($drafts->isNotEmpty())
                <section aria-labelledby="titre-brouillons">
                    <h2 id="titre-brouillons" class="section__title">Brouillons à reprendre</h2>
                    <ul class="plain-list stack stack--tight">
                        @foreach ($drafts as $draft)
                            <li>
                                <a href="{{ route('scenes.show', ['scene' => $draft->scene, 'page' => max(1, (int) ceil($draft->scene->posts_count / config('hollow.scenes.posts_per_page')))]) }}#ecrire">{{ $draft->scene->title }}</a>
                                <span class="muted"> · {{ \App\Support\SceneText::wordCount($draft->body) }} mots, enregistré {{ $draft->updated_at->locale('fr')->diffForHumans() }}</span>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            <section aria-labelledby="titre-terminees">
                <h2 id="titre-terminees" class="section__title">Terminées</h2>
                @if ($finished->isEmpty())
                    <p class="muted">Aucune scène terminée pour le moment.</p>
                @else
                    <ul class="plain-list stack stack--tight">
                        @foreach ($finished as $scene)
                            <li>
                                <a href="{{ route('scenes.show', $scene) }}">{{ $scene->title }}</a>
                                <span class="muted"> · {{ $scene->rp_date }}</span>
                                <a class="link-button" href="{{ route('scenes.export', $scene) }}">Télécharger</a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>
    </div>
</x-layout>
