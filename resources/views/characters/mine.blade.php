<x-layout title="Mes personnages" robots="noindex">
    <div class="container">
        <header class="page-header">
            <x-breadcrumbs :items="['Tableau de bord' => route('dashboard'), 'Mes personnages' => null]" />
            <h1>Mes personnages</h1>
            <p class="lede">Un seul compte, plusieurs personnages. Chaque fiche est relue par l’équipe avant de devenir jouable.</p>
        </header>

        <div class="stack page-body stack--loose">
            <x-flash />
            <x-errors-summary />

            <div class="cluster">
                @if ($remaining > 0)
                    <form method="POST" action="{{ route('characters.store') }}">
                        @csrf
                        <button type="submit" class="btn">Créer un personnage</button>
                    </form>
                    <a class="btn btn--ghost" href="{{ route('predefined.index') }}">Reprendre un prédéfini</a>
                    <span class="muted">Encore {{ $remaining }} {{ $remaining > 1 ? 'fiches possibles' : 'fiche possible' }}.</span>
                @else
                    <p class="muted">Vous avez atteint le nombre maximum de personnages pour un compte.</p>
                @endif
            </div>

            @if ($characters->isEmpty())
                <p class="empty-note">Aucun personnage pour le moment. Commencez par lire <a href="{{ route('universe.index') }}">l’univers</a> et choisir votre avatar dans <a href="{{ route('faceclaims.index') }}">le bottin</a>.</p>
            @else
                <div class="grid-cards">
                    @foreach ($characters as $character)
                        <article class="card character-card">
                            <x-portrait :character="$character" size="small" />
                            <div class="stack">
                                <div class="cluster">
                                    <h2 class="card__title">{{ $character->fullName() }}</h2>
                                    <x-status-badge :status="$character->status" />
                                </div>
                                <p class="muted">
                                    {{ $character->species?->name ?? 'Nature à choisir' }}@if ($character->faceclaim) · avatar : {{ $character->faceclaim->name }}@endif
                                </p>
                                @if ($character->status === \App\Enums\CharacterStatus::ChangesRequested && $character->reviews->first()?->comment)
                                    <div class="alert alert--warning"><p>« {{ $character->reviews->first()->comment }} »</p></div>
                                @endif
                                @if ($character->status === \App\Enums\CharacterStatus::Refused && $character->reviews->first()?->comment)
                                    <p class="muted">Motif : « {{ $character->reviews->first()->comment }} »</p>
                                @endif
                                <div class="cluster">
                                    @if ($character->isEditableByOwner())
                                        <a class="btn" href="{{ route('characters.edit', [$character, 1]) }}">{{ $character->status === \App\Enums\CharacterStatus::Draft ? 'Continuer la fiche' : 'Modifier la fiche' }}</a>
                                        <form method="POST" action="{{ route('characters.destroy', $character) }}" data-confirm="Supprimer ce brouillon ? Son avatar redeviendra disponible.">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="link-button">Supprimer</button>
                                        </form>
                                    @elseif ($character->status === \App\Enums\CharacterStatus::Accepted)
                                        <a class="btn btn--ghost" href="{{ route('characters.show', $character) }}">Voir le profil</a>
                                        <a class="link-button" href="{{ route('characters.profile.edit', $character) }}">Personnaliser</a>
                                        <form method="POST" action="{{ route('characters.archive', $character) }}" data-confirm="Archiver ce personnage ? Il ne sera plus jouable et sa célébrité redeviendra disponible.">
                                            @csrf
                                            <button type="submit" class="link-button">Archiver</button>
                                        </form>
                                    @elseif ($character->status === \App\Enums\CharacterStatus::Pending)
                                        <span class="muted">Envoyée le {{ $character->submitted_at?->locale('fr')->isoFormat('LL') }}, en cours de lecture.</span>
                                    @endif
                                </div>
                                @if ($character->status === \App\Enums\CharacterStatus::Accepted)
                                    <form method="POST" action="{{ route('characters.color', $character) }}" class="cluster">
                                        @csrf
                                        @method('PUT')
                                        <label for="couleur-{{ $character->id }}">Couleur de dialogue</label>
                                        <select id="couleur-{{ $character->id }}" name="dialogue_color">
                                            <option value="">Sans couleur</option>
                                            @foreach (config('hollow.scenes.dialogue_colors') as $key => $label)
                                                <option value="{{ $key }}" @selected($character->dialogue_color === $key)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="link-button">Enregistrer</button>
                                        <span class="dialogue-sample post--c-{{ $character->dialogue_color ?: 'aucune' }}"><span class="dialogue">« Exemple »</span></span>
                                    </form>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif

            @if ($reservations->isNotEmpty())
                <section class="card stack" aria-labelledby="titre-reservations">
                    <h2 id="titre-reservations" class="card__title">Mes demandes au bottin</h2>
                    <ul class="stack plain-list">
                        @foreach ($reservations as $claim)
                            <li>{{ $claim->faceclaim->name }} — <x-status-badge :status="$claim->status" />
                                @if ($claim->expires_at) <span class="muted">jusqu’au {{ $claim->expires_at->locale('fr')->isoFormat('LL') }}</span> @endif
                            </li>
                        @endforeach
                    </ul>
                    <p><a href="{{ route('faceclaims.index') }}">Gérer mes demandes dans le bottin</a></p>
                </section>
            @endif
        </div>
    </div>
</x-layout>
