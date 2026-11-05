<form class="form card" method="POST" action="{{ route('characters.update', [$character, 1]) }}" novalidate>
    @csrf
    @method('PUT')
    <h2 class="card__title">1. Identité</h2>

    <div class="form-grid">
        <x-field name="first_name" label="Prénom" required maxlength="60" :value="old('first_name', $character->first_name === 'Nouveau personnage' ? '' : $character->first_name)" />
        <x-field name="last_name" label="Nom" maxlength="60" :value="old('last_name', $character->last_name)" />
        <x-field name="age" label="Âge" type="number" min="18" max="130" required :value="old('age', $character->age)" hint="18 ans au minimum." />
        <x-field name="apparent_age" label="Âge apparent (facultatif)" type="number" min="18" max="130" :value="old('apparent_age', $character->apparent_age)" hint="Pour les Revenus, les Rappelés et les esprits : l’âge qu’ils semblent avoir, 18 ans au minimum." />
    </div>

    <div class="field">
        <label for="champ-family">Famille (facultatif)</label>
        <select id="champ-family" name="family_id">
            <option value="">Aucune famille du site</option>
            @foreach ($families as $family)
                <option value="{{ $family->id }}" @selected((int) old('family_id', $character->family_id) === $family->id)>{{ $family->name }}</option>
            @endforeach
        </select>
        @error('family_id')<p class="error">{{ $message }}</p>@enderror
    </div>

    <x-field name="profession" label="Profession" required maxlength="120" :value="old('profession', $character->profession)" />
    <x-field name="quote" label="Citation personnelle (facultatif)" maxlength="300" :value="old('quote', $character->quote)" hint="Une phrase qui lui ressemble, affichée en exergue sur son profil." />

    @include('characters.steps.actions')
</form>
