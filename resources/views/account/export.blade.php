<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mes textes · Tsukimori</title>
    <style>
        body { margin: 0; background: #efe6d2; color: #0e1311; font-family: Georgia, 'Times New Roman', serif; font-size: 17px; line-height: 1.65; }
        main { max-width: 70ch; margin: 0 auto; padding: 40px 20px 72px; }
        h1, h2, h3 { font-family: Garamond, Georgia, serif; line-height: 1.2; }
        h2 { margin-top: 2.5em; border-bottom: 1px solid rgba(14, 19, 17, 0.25); padding-bottom: 4px; }
        .meta { color: #46514c; font-family: system-ui, sans-serif; font-size: 0.85rem; }
        .text { white-space: pre-wrap; }
        article { margin-top: 1.5em; }
    </style>
</head>
<body>
<main>
    <p class="meta">Tsukimori — export du {{ \Illuminate\Support\Carbon::parse($data['exporte_le'])->locale('fr')->isoFormat('LLL') }}</p>
    <h1>Les textes de {{ $data['compte']['pseudo'] }}</h1>

    <h2>Personnages</h2>
    @forelse ($data['personnages'] as $character)
        <article>
            <h3>{{ $character['nom'] }}</h3>
            <p class="meta">{{ $character['statut'] }} · {{ $character['espece'] }}@if ($character['famille']) · {{ $character['famille'] }}@endif · {{ $character['age'] }} ans · {{ $character['profession'] }}</p>
            @if ($character['citation'])<p><em>« {{ $character['citation'] }} »</em></p>@endif
            @if ($character['histoire'])<h4>Histoire</h4><div class="text">{{ $character['histoire'] }}</div>@endif
            @if ($character['caractere'])<h4>Caractère</h4><div class="text">{{ $character['caractere'] }}</div>@endif
        </article>
    @empty
        <p>Aucun personnage.</p>
    @endforelse

    <h2>Réponses dans les scènes</h2>
    @forelse (collect($data['reponses'])->groupBy('scene') as $scene => $posts)
        <article>
            <h3>{{ $scene ?: 'Scène supprimée' }}</h3>
            @foreach ($posts as $post)
                <p class="meta">{{ $post['personnage'] }} · {{ \Illuminate\Support\Carbon::parse($post['publiee_le'])->locale('fr')->isoFormat('LLL') }}</p>
                <div class="text">{{ $post['texte'] }}</div>
            @endforeach
        </article>
    @empty
        <p>Aucune réponse.</p>
    @endforelse

    @if ($data['murmures'] !== [])
        <h2>Murmures</h2>
        @foreach ($data['murmures'] as $post)
            <article>
                <p class="meta">{{ $post['personnage'] }} · {{ $post['type'] }}</p>
                <div class="text">{{ $post['texte'] }}</div>
            </article>
        @endforeach
    @endif

    @if ($data['journal'] !== [])
        <h2>Journaux personnels</h2>
        @foreach ($data['journal'] as $entry)
            <article>
                <h3>{{ $entry['titre'] ?: ($entry['date_rp'] ?: 'Entrée') }}</h3>
                <p class="meta">{{ $entry['personnage'] }}@if ($entry['date_rp']) · {{ $entry['date_rp'] }}@endif{{ $entry['privee'] ? ' · privée' : '' }}</p>
                <div class="text">{{ $entry['texte'] }}</div>
            </article>
        @endforeach
    @endif

    @if ($data['annonces'] !== [])
        <h2>Petites annonces</h2>
        @foreach ($data['annonces'] as $ad)
            <article>
                <h3>{{ $ad['titre'] }}</h3>
                <p class="meta">{{ $ad['personnage'] }} · {{ $ad['rubrique'] }}</p>
                <div class="text">{{ $ad['texte'] }}</div>
            </article>
        @endforeach
    @endif

    @if ($data['brouillons'] !== [])
        <h2>Brouillons</h2>
        @foreach ($data['brouillons'] as $draft)
            <article>
                <p class="meta">{{ $draft['scene'] }}</p>
                <div class="text">{{ $draft['texte'] }}</div>
            </article>
        @endforeach
    @endif

    <p class="meta">Les messages privés, les liens et les demandes à l’équipe figurent dans l’export au format réutilisable (JSON).</p>
</main>
</body>
</html>
