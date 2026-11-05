<x-layout title="Murmures" description="Ce que les habitants de Tsukimori pensent, écrivent et affichent.">
    <div class="container">
        <header class="page-header">
            <x-breadcrumbs :items="['Accueil' => route('home'), 'Murmures' => null]" />
            <h1>Murmures</h1>
            <p class="lede">Pensées, courts textes, images et annonces des habitants de Tsukimori, chacun signé par un personnage.</p>
        </header>

        <div class="feed page-body">
            <div class="stack stack--loose">
                <x-flash />
                <x-errors-summary />

                @if ($myCharacters->isNotEmpty())
                    <form class="form card" method="POST" action="{{ route('feed.store') }}" enctype="multipart/form-data" novalidate>
                        @csrf
                        <div class="form-grid">
                            <div class="field">
                                <label for="champ-murmure-personnage">Signé par</label>
                                <select id="champ-murmure-personnage" name="character_id">
                                    @foreach ($myCharacters as $character)
                                        <option value="{{ $character->id }}" @selected((int) old('character_id') === $character->id)>{{ $character->fullName() }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="field">
                                <label for="champ-murmure-type">Type</label>
                                <select id="champ-murmure-type" name="kind">
                                    @foreach (\App\Enums\FeedKind::cases() as $kind)
                                        <option value="{{ $kind->value }}" @selected(old('kind', 'pensee') === $kind->value)>{{ $kind->getLabel() }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="field">
                            <label for="champ-murmure">Texte</label>
                            <textarea id="champ-murmure" name="body" rows="4" maxlength="3000">{{ old('body') }}</textarea>
                            @error('body')<p class="error">{{ $message }}</p>@enderror
                        </div>
                        <details @if ($errors->has('image') || $errors->has('image_credit')) open @endif>
                            <summary>Ajouter une image</summary>
                            <div class="stack">
                                <div class="field">
                                    <label for="champ-murmure-image">Image (JPEG, PNG, WebP ou GIF, 2 Mo)</label>
                                    <input id="champ-murmure-image" type="file" name="image" accept="image/jpeg,image/png,image/webp,image/gif">
                                    @error('image')<p class="error">{{ $message }}</p>@enderror
                                </div>
                                <x-field name="image_credit" label="Crédit de l’image" maxlength="255" :value="old('image_credit')" hint="Photographe, source et licence. Images dont vous avez le droit d’usage, jamais générées par une IA." />
                            </div>
                        </details>
                        <div class="form-actions"><button type="submit" class="btn">Publier</button></div>
                    </form>
                @elseauth
                    <p class="muted">Il faut un personnage accepté pour publier.</p>
                @endif

                @forelse ($posts as $post)
                    @include('feed.partials.post', ['post' => $post])
                @empty
                    <p class="empty-note">Aucun murmure pour l’instant. La ville retient son souffle.</p>
                @endforelse

                {{ $posts->links('partials.pagination') }}
            </div>
        </div>
    </div>
</x-layout>
