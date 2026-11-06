<form class="form card" method="POST" action="{{ route('characters.update', [$character, 3]) }}" enctype="multipart/form-data" novalidate>
    @csrf
    @method('PUT')
    <h2 class="card__title">3. Avatar</h2>
    <p class="muted">L’avatar représente une personnalité réelle (acteur, actrice, chanteur, chanteuse, mannequin…), jamais un visage inventé ou généré par une intelligence artificielle. Choisissez une photographie d’apparition publique dont l’usage est permis ; indiquez son crédit si la licence le demande.</p>

    @if ($reservations->isNotEmpty())
        <div class="alert" role="note">
            <p>Vos réservations : {{ $reservations->map(fn ($claim) => $claim->faceclaim->name)->join(', ', ' et ') }}. Indiquez ce nom ci-dessous pour l’utiliser.</p>
        </div>
    @endif

    <x-field name="faceclaim" label="Célébrité" required maxlength="120" :value="old('faceclaim', $character->faceclaim?->name)"
        hint="Prénom et nom. Une célébrité libre est mise de côté pour vous le temps de terminer la fiche ({{ app(\App\Support\Settings::class)->reservationDays() }} jours), puis pendant l’examen." />
    @error('name')<p class="error">{{ $message }}</p>@enderror

    <div class="avatar-field">
        @if ($character->avatarUrl())
            <x-portrait :character="$character" />
        @endif
        <div class="field">
            <label for="champ-avatar">{{ $character->avatar_path ? 'Remplacer l’image' : 'Image de l’avatar' }}</label>
            <input id="champ-avatar" type="file" name="avatar" accept="image/jpeg,image/png,image/webp,image/gif" @error('avatar') aria-invalid="true" @enderror>
            <p class="hint">JPEG, PNG, WebP ou GIF, 2 Mo maximum. Format portrait (2 : 3) conseillé. L’image est ré-encodée et ses métadonnées supprimées.</p>
            @error('avatar')<p class="error">{{ $message }}</p>@enderror
            @error('image')<p class="error">{{ $message }}</p>@enderror
        </div>
    </div>

    <x-field name="avatar_credit" label="Crédit de l’image (facultatif)" maxlength="255" :value="old('avatar_credit', $character->avatar_credit)" hint="Facultatif. À indiquer quand la licence l’exige (Creative Commons BY, par exemple : auteur et licence) ou quand le photographe le demande." />

    @include('characters.steps.actions')
</form>
