{{-- Accueil d'un compte qui n'a pas encore de personnage accepté : où en est sa fiche, et quoi faire en attendant. --}}
@php
    use App\Enums\CharacterStatus;
@endphp
<x-layout title="Tableau de bord" robots="noindex">
    <section class="dash-hero">
        <x-ambiance name="foret-brume" eager class="dash-hero__image" />
        <div class="container">
            <header class="dash-header">
                <div class="stack stack--tight">
                    <p class="eyebrow">{{ $rpDate }} · date RP</p>
                    <h1>{{ $greeting }}, {{ $user->pseudo }}</h1>
                    <p class="lede">Bienvenue à Tsukimori. Votre compte est actif ; vous pourrez jouer dès qu’un de vos personnages sera accepté par l’équipe.</p>
                </div>
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
        </div>

        <div class="dash dash--pending">
            <section class="dash-card dash-status" aria-labelledby="titre-statut">
                <h2 id="titre-statut" class="card__title">Où en est votre inscription</h2>
                <ol class="timeline">
                    <li class="timeline__step is-done">
                        <span class="timeline__label">Compte créé</span>
                        <span class="muted">Le {{ $user->created_at->locale('fr')->isoFormat('LL') }}.</span>
                    </li>
                    <li class="timeline__step is-done">
                        <span class="timeline__label">Adresse e-mail vérifiée</span>
                    </li>
                    @php
                        $submitted = $characters->firstWhere('status', CharacterStatus::Pending);
                        $changes = $characters->firstWhere('status', CharacterStatus::ChangesRequested);
                        $draft = $characters->firstWhere('status', CharacterStatus::Draft);
                    @endphp
                    <li @class(['timeline__step', 'is-done' => $submitted || $changes, 'is-current' => ! $submitted && ! $changes])>
                        <span class="timeline__label">Fiche de personnage envoyée</span>
                        @if (! $submitted && ! $changes)
                            <span class="muted">{{ $draft ? 'Votre fiche est en brouillon.' : 'Créez votre premier personnage.' }}</span>
                        @endif
                    </li>
                    <li @class(['timeline__step', 'is-current' => $submitted || $changes])>
                        <span class="timeline__label">Lecture par l’équipe</span>
                        @if ($changes)
                            <span class="muted">Des modifications vous sont demandées.</span>
                        @elseif ($submitted)
                            <span class="muted">Votre fiche est en cours de lecture. Vous serez prévenu par notification.</span>
                        @endif
                    </li>
                    <li class="timeline__step">
                        <span class="timeline__label">Première scène</span>
                    </li>
                </ol>
            </section>

            <section class="dash-card" aria-labelledby="titre-fiches">
                <div class="dash-card__head">
                    <h2 id="titre-fiches" class="card__title">Vos fiches</h2>
                    <a href="{{ route('characters.mine') }}">Mes personnages</a>
                </div>
                @if ($progress->isEmpty())
                    <p>Vous n’avez pas encore de personnage. Choisissez une nature, une famille ou un rôle proposé par l’équipe, puis remplissez la fiche en six étapes.</p>
                    @if ($remaining > 0)
                        <form method="POST" action="{{ route('characters.store') }}">
                            @csrf
                            <button type="submit" class="btn">Créer un personnage</button>
                        </form>
                    @endif
                @else
                    <ul class="pending-list">
                        @foreach ($progress as $row)
                            @php($character = $row['character'])
                            <li class="pending-item">
                                <div class="cluster">
                                    <strong>{{ $character->first_name ? $character->fullName() : 'Personnage sans nom' }}</strong>
                                    <x-status-badge :status="$character->status" />
                                </div>
                                @if ($character->status === CharacterStatus::ChangesRequested && $row['lastReview']?->comment)
                                    <blockquote class="pending-item__comment"><p class="eyebrow">Message de l’équipe</p>{{ $row['lastReview']->comment }}</blockquote>
                                @elseif ($character->status === CharacterStatus::Refused && $row['lastReview']?->comment)
                                    <blockquote class="pending-item__comment"><p class="eyebrow">Motif du refus</p>{{ $row['lastReview']->comment }}</blockquote>
                                @endif
                                @if ($row['missing'] !== [])
                                    <p class="muted">Encore {{ count($row['missing']) }} {{ count($row['missing']) > 1 ? 'éléments' : 'élément' }} avant l’envoi : {{ \Illuminate\Support\Str::limit(implode(' ; ', $row['missing']), 180) }}</p>
                                @endif
                                @if (in_array($character->status, [CharacterStatus::Draft, CharacterStatus::ChangesRequested], true))
                                    <a class="btn btn--ghost" href="{{ route('characters.edit', [$character, 1]) }}">Reprendre la fiche</a>
                                @elseif ($character->status === CharacterStatus::Pending)
                                    <p class="muted">Envoyée le {{ $character->submitted_at?->locale('fr')->isoFormat('LL') }}.</p>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            <section class="dash-card" aria-labelledby="titre-attente">
                <h2 id="titre-attente" class="card__title">En attendant</h2>
                <ul class="dash-explore">
                    <li><a href="{{ route('universe.index') }}">L’univers et ses mystères</a></li>
                    <li><a href="{{ route('species.index') }}">Le bestiaire</a></li>
                    <li><a href="{{ route('families.index') }}">Les grandes familles</a></li>
                    <li><a href="{{ route('predefined.index') }}">Les rôles prédéfinis</a></li>
                    <li><a href="{{ route('city.index') }}">La ville et sa carte</a></li>
                    <li><a href="{{ route('scenes.index') }}">Lire les scènes publiques</a></li>
                    <li><a href="{{ route('pages.reglement') }}">Le règlement</a></li>
                </ul>
                <p class="cluster dash-more">
                    <a href="{{ route('messages.index') }}">Messages{{ $unreadMessages > 0 ? ' ('.$unreadMessages.')' : '' }}</a>
                    <a href="{{ route('contact.mine') }}">Mes demandes à l’équipe{{ $openTickets > 0 ? ' (réponse reçue)' : '' }}</a>
                    @unless ($hasTwoFactor)
                        <a href="{{ route('account.edit') }}#double-authentification">Protéger mon compte</a>
                    @endunless
                </p>
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
