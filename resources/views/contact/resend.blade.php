<x-layout title="Suivre une demande" robots="noindex">
    <div class="container">
        <header class="page-header">
            <h1>Suivre une demande</h1>
            <p class="lede">Indiquez la référence (HC-…) et l’adresse e-mail utilisées : un nouveau lien de suivi vous sera envoyé.</p>
        </header>

        <div class="stack page-body stack--loose">
            <x-flash />
            <x-errors-summary />

            <form class="form card" method="POST" action="{{ route('contact.resend.send') }}" novalidate>
                @csrf
                <x-honeypot />
                <div class="form-grid">
                    <x-field name="reference" label="Référence" required maxlength="12" :value="old('reference')" placeholder="HC-7F3K" />
                    <x-field name="email" label="Adresse e-mail" type="email" required maxlength="190" :value="old('email')" autocomplete="email" />
                </div>
                <div class="form-actions"><button type="submit" class="btn">Recevoir un nouveau lien</button></div>
            </form>
        </div>
    </div>
</x-layout>
