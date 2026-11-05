<x-layout title="Lien de suivi expiré" robots="noindex">
    <div class="container">
        <header class="page-header">
            <h1>Ce lien de suivi ne fonctionne plus</h1>
            <p class="lede">Il a peut-être expiré, ou un lien plus récent l’a remplacé. Demandez-en un nouveau avec votre référence et votre adresse e-mail.</p>
        </header>
        <p class="page-body"><a class="btn" href="{{ route('contact.resend') }}">Recevoir un nouveau lien</a></p>
    </div>
</x-layout>
