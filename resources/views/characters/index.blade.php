<x-layout title="Les personnages" description="Les habitants de Tsukimori, incarnés par les joueurs.">
    <div class="container">
        <header class="page-header">
            <x-breadcrumbs :items="['Accueil' => route('home'), 'Les personnages' => null]" />
            <h1>Les personnages</h1>
            <p class="lede">Les habitants de Tsukimori, vivants ou revenus, chacun incarné par un joueur.</p>
        </header>

        <form class="filters" method="GET" action="{{ route('characters.index') }}">
            <div class="field">
                <label for="filtre-espece">Nature</label>
                <select id="filtre-espece" name="espece">
                    <option value="">Toutes</option>
                    @foreach ($species as $item)
                        <option value="{{ $item->slug }}" @selected($speciesFilter?->is($item))>{{ $item->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="filtre-famille">Famille</label>
                <select id="filtre-famille" name="famille">
                    <option value="">Toutes</option>
                    @foreach ($families as $family)
                        <option value="{{ $family->slug }}" @selected($familyFilter?->is($family))>{{ $family->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn--ghost">Filtrer</button>
            </div>
        </form>

        @if ($characters->isEmpty())
            <p class="empty-note">{{ $speciesFilter || $familyFilter ? 'Aucun personnage ne correspond à ces critères.' : 'Les premiers habitants arriveront bientôt.' }}</p>
        @else
            <ul class="portrait-grid plain-list">
                @foreach ($characters as $character)
                    <li>
                        <a class="portrait-card" href="{{ route('characters.show', $character) }}">
                            <x-portrait :character="$character" />
                            <span class="portrait-card__name">{{ $character->fullName() }}</span>
                            <span class="portrait-card__meta">{{ $character->species?->name }}@if ($character->family) · {{ $character->family->name }}@endif</span>
                        </a>
                    </li>
                @endforeach
            </ul>

            {{ $characters->links('partials.pagination') }}
        @endif
    </div>
</x-layout>
