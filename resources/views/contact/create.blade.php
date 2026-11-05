<x-layout title="Contacter l’équipe">
    <div class="container">
        <header class="page-header">
            <x-breadcrumbs :items="['Accueil' => route('home'), 'Contacter l’équipe' => null]" />
            <h1>Contacter l’équipe</h1>
            <p class="lede">Une personne de l’équipe lit chaque demande et y répond elle-même. Vous recevrez une référence et un lien pour suivre la conversation.</p>
        </header>

        <div class="stack page-body stack--loose">
            <x-errors-summary />

            @if ($motif === 'avatar')
                <div class="alert alert--warning"><p>Pour réserver un avatar, le plus rapide est le <a href="{{ route('faceclaims.index') }}">bottin</a> : vous y vérifiez la disponibilité et faites la demande, même sans compte.</p></div>
            @endif

            <form class="form card" method="POST" action="{{ route('contact.store') }}" novalidate>
                @csrf
                <x-honeypot />

                @guest
                    <div class="form-grid">
                        <x-field name="name" label="Nom ou pseudo" required maxlength="60" :value="old('name')" />
                        <x-field name="email" label="Adresse e-mail" type="email" required maxlength="190" :value="old('email')" autocomplete="email" hint="Pour recevoir la réponse et le lien de suivi." />
                    </div>
                @endguest

                <div class="field">
                    <label for="champ-motif">Motif</label>
                    <select id="champ-motif" name="motif">
                        @foreach ($motifs as $key => $label)
                            <option value="{{ $key }}" @selected(old('motif', $motif) === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <x-field name="subject" label="Sujet" required maxlength="150" :value="old('subject', $about)" />

                <div class="field">
                    <label for="champ-demande">Message</label>
                    <textarea id="champ-demande" name="body" rows="8" maxlength="5000" required>{{ old('body') }}</textarea>
                    @error('body')<p class="error">{{ $message }}</p>@enderror
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn">Envoyer</button>
                </div>
            </form>
        </div>
    </div>
</x-layout>
