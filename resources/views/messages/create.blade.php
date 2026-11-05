<x-layout title="Nouvelle conversation" robots="noindex">
    <div class="container">
        <header class="page-header">
            <x-breadcrumbs :items="['Messages' => route('messages.index'), 'Nouvelle conversation' => null]" />
            <h1>Nouvelle conversation</h1>
        </header>

        <div class="stack page-body stack--loose">
            <x-errors-summary />

            <form class="form card" method="POST" action="{{ route('messages.store') }}" novalidate>
                @csrf
                <x-field name="recipients" label="Destinataires" required maxlength="300" :value="old('recipients', $recipients)" hint="Un ou plusieurs pseudos, séparés par des virgules (7 au plus)." />
                <x-field name="subject" label="Sujet (facultatif)" maxlength="150" :value="old('subject')" hint="Utile pour une conversation de groupe, par exemple pour préparer une scène." />
                <div class="field">
                    <label for="champ-message">Message</label>
                    <textarea id="champ-message" name="body" rows="8" maxlength="5000" required>{{ old('body') }}</textarea>
                    @error('body')<p class="error">{{ $message }}</p>@enderror
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn">Envoyer</button>
                </div>
            </form>
        </div>
    </div>
</x-layout>
