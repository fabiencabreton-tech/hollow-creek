@php
    $editing = $entry->exists;
@endphp
<x-layout :title="($editing ? 'Modifier une entrée' : 'Nouvelle entrée').' — '.$character->fullName()" robots="noindex">
    <div class="container container--narrow">
        <header class="page-header">
            <x-breadcrumbs :items="[$character->fullName() => route('characters.show', $character), 'Journal' => route('journal.index', $character), ($editing ? 'Modifier' : 'Nouvelle entrée') => null]" />
            <h1>{{ $editing ? 'Modifier une entrée' : 'Nouvelle entrée' }}</h1>
            <p class="lede">Le journal de {{ $character->fullName() }}. Écrit par vous, jamais par une machine.</p>
        </header>

        <form class="form" method="POST" action="{{ $editing ? route('journal.update', [$character, $entry]) : route('journal.store', $character) }}" novalidate>
            @csrf
            @if ($editing)
                @method('PUT')
            @endif
            <x-errors-summary />
            <div class="form-grid">
                <x-field name="title" label="Titre (facultatif)" maxlength="150" :value="old('title', $entry->title)" />
                <x-field name="rp_date" label="Date dans le jeu (facultatif)" maxlength="80" :value="old('rp_date', $entry->rp_date)" hint="Par exemple « 3 novembre 2026, au soir »." />
            </div>
            <div class="field">
                <label for="champ-journal-texte">Texte</label>
                <p class="hint" id="champ-journal-texte-aide">Markdown simple : **gras**, *italique*, > citation. 20 000 caractères au plus.</p>
                <textarea id="champ-journal-texte" name="body" rows="16" maxlength="{{ \App\Models\JournalEntry::MAX_LENGTH }}" aria-describedby="champ-journal-texte-aide @error('body') champ-journal-texte-erreur @enderror" @error('body') aria-invalid="true" @enderror required>{{ old('body', $entry->body) }}</textarea>
                @error('body')<p class="error" id="champ-journal-texte-erreur">{{ $message }}</p>@enderror
            </div>
            <fieldset class="field">
                <legend>Qui peut lire cette entrée ?</legend>
                @php $visibility = old('visibility', $entry->is_private ? 'prive' : 'public'); @endphp
                <label class="choice"><input type="radio" name="visibility" value="public" @checked($visibility === 'public')><span>Tout le monde<span class="choice__text">Visible sur le journal public du personnage.</span></span></label>
                <label class="choice"><input type="radio" name="visibility" value="prive" @checked($visibility === 'prive')><span>Moi seulement<span class="choice__text">Personne d’autre ne la lit, hormis la modération si nécessaire.</span></span></label>
            </fieldset>
            <div class="form-actions">
                <button type="submit" class="btn">{{ $editing ? 'Enregistrer' : 'Ajouter au journal' }}</button>
                <a href="{{ route('journal.index', $character) }}">Annuler</a>
            </div>
        </form>
    </div>
</x-layout>
