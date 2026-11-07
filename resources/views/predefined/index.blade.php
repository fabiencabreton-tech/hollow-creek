<x-layout title="Les prédéfinis" description="Les rôles proposés par l’équipe de Tsukimori.">
    <x-page-hero image="shoji" title="Les prédéfinis" eyebrow="Rôles imaginés par l’équipe"
        lead="Liés aux familles, à la ville ou aux événements de Tsukimori, et assez ouverts pour que vous vous les appropriiez."
        :breadcrumbs="['Accueil' => route('home'), 'Les prédéfinis' => null]" />
    <div class="container">

        @if ($predefined->isEmpty())
            <p class="empty-note">Les premiers rôles prédéfinis seront bientôt proposés.</p>
        @else
            <div class="grid-cards page-body">
                @foreach ($predefined as $role)
                    <a class="tile" href="{{ route('predefined.show', $role) }}">
                        <span class="cluster"><span class="tile__eyebrow">{{ collect([$role->species?->name, $role->family?->name])->filter()->join(' · ') ?: 'Prédéfini' }}</span><x-status-badge :status="$role->status" /></span>
                        <span class="tile__title">{{ $role->title }}</span>
                        @if ($role->summary)
                            <span class="tile__text">{{ $role->summary }}</span>
                        @endif
                        <span class="tile__more">Découvrir le rôle</span>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</x-layout>
