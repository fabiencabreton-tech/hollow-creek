<x-layout :title="'Profil de '.$character->fullName()" robots="noindex">
    <div class="container">
        <header class="page-header">
            <x-breadcrumbs :items="['Mes personnages' => route('characters.mine'), $character->fullName() => route('characters.show', $character), 'Personnaliser' => null]" />
            <h1>Personnaliser le profil</h1>
            <p class="lede">Bannière, couleur d’accent, mise en page et statut d’absence de {{ $character->fullName() }}.</p>
        </header>

        <div class="stack page-body stack--loose">
            <x-errors-summary />

            <form class="form card" method="POST" action="{{ route('characters.profile.update', $character) }}" enctype="multipart/form-data" novalidate>
                @csrf
                @method('PUT')

                <fieldset class="field">
                    <legend>Bannière</legend>
                    @if ($character->bannerUrl())
                        <img class="banner-preview" src="{{ $character->bannerUrl() }}" alt="Bannière actuelle">
                        <label class="checkbox"><input type="checkbox" name="remove_banner" value="1"> <span>Retirer la bannière</span></label>
                    @endif
                    <label for="champ-banniere">Nouvelle image (JPEG, PNG, WebP ou GIF, 2 Mo ; format large conseillé)</label>
                    <input id="champ-banniere" type="file" name="banner" accept="image/jpeg,image/png,image/webp,image/gif">
                    @error('banner')<p class="error">{{ $message }}</p>@enderror
                </fieldset>
                <x-field name="banner_credit" label="Crédit de la bannière" maxlength="255" :value="old('banner_credit', $character->banner_credit)" hint="Photographe, source et licence. Jamais d’image générée par une IA." />

                <div class="form-grid">
                    <div class="field">
                        <label for="champ-accent">Couleur d’accent</label>
                        <select id="champ-accent" name="accent_color">
                            <option value="">Celle du site</option>
                            @foreach (config('hollow.profiles.accent_colors') as $key => $label)
                                <option value="{{ $key }}" @selected(old('accent_color', $character->accent_color) === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <fieldset class="field">
                        <legend>Mise en page</legend>
                        @foreach (config('hollow.profiles.layouts') as $key => $label)
                            <label class="checkbox"><input type="radio" name="profile_layout" value="{{ $key }}" @checked(old('profile_layout', $character->profile_layout ?: 'classique') === $key)> <span>{{ $label }}</span></label>
                        @endforeach
                    </fieldset>
                </div>

                <fieldset class="field">
                    <legend>Absence</legend>
                    <x-field name="absence_note" label="Message (laisser vide si présent)" maxlength="200" :value="old('absence_note', $character->absence_note)" hint="Par exemple « En voyage, retour en décembre »." />
                    <x-field name="absence_until" label="Date de retour (facultatif)" type="date" :value="old('absence_until', $character->absence_until?->format('Y-m-d'))" />
                </fieldset>

                <div class="form-actions">
                    <button type="submit" class="btn">Enregistrer</button>
                    <a href="{{ route('characters.show', $character) }}">Annuler</a>
                </div>
            </form>
        </div>
    </div>
</x-layout>
