<x-layout title="Les prédéfinis" description="Les rôles proposés par l’équipe de Tsukimori.">
    <div class="container">
        <header class="page-header">
            <x-breadcrumbs :items="['Accueil' => route('home'), 'Les prédéfinis' => null]" />
            <h1>Les prédéfinis</h1>
            <p class="lede">Des rôles imaginés par l’équipe, liés aux familles, à la ville ou aux événements de Tsukimori, et assez ouverts pour que vous vous les appropriiez.</p>
        </header>

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
