<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $scene->title }} · Tsukimori</title>
    <style>
        body { margin: 0; background: #efe6d2; color: #0e1311; font-family: 'Literata', Georgia, 'Times New Roman', serif; font-size: 18px; line-height: 1.7; }
        main { max-width: 68ch; margin: 0 auto; padding: 48px 20px 72px; }
        h1 { font-family: 'Shippori Mincho', 'Yu Mincho', Georgia, serif; font-size: 2.4rem; line-height: 1.15; margin: 0 0 8px; }
        .meta, .post__meta, footer { color: #46514c; font-family: system-ui, sans-serif; font-size: 0.85rem; }
        .warnings { border-left: 3px solid #5e1a24; padding-left: 12px; }
        article { border-top: 1px solid rgba(14, 19, 17, 0.2); padding-top: 20px; margin-top: 28px; break-inside: avoid-page; }
        .post__character { font-family: 'Shippori Mincho', 'Yu Mincho', Georgia, serif; font-weight: 600; font-size: 1.35rem; margin: 0; }
        blockquote { margin: 1em 0; padding-left: 16px; border-left: 3px solid #7a2633; font-style: italic; }
        hr { border: 0; text-align: center; margin: 1.5em 0; }
        hr::after { content: '⁂'; color: #46514c; }
        .dialogue { font-weight: 500; }
        .post--c-lanterne .dialogue { color: #7a4f0e; }
        .post--c-vin .dialogue { color: #7a2633; }
        .post--c-sapin .dialogue { color: #1f5a35; }
        .post--c-brume .dialogue { color: #34506b; }
        .post--c-lilas .dialogue { color: #5a3d8a; }
        .post--c-cuivre .dialogue { color: #8a4a1e; }
        .post--c-argent .dialogue { color: #3f4a46; }
        @media print { body { background: #fff; } main { padding: 0; } }
    </style>
</head>
<body>
<main>
    <p class="meta">Tsukimori — Ceux qui reviennent</p>
    <h1>{{ $scene->title }}</h1>
    <p class="meta">
        {{ $scene->rp_date }}@if ($scene->location) · {{ $scene->location->name }}@endif
        · {{ $scene->status->getLabel() }} · {{ $posts->count() }} réponses · {{ $scene->words_count }} mots
    </p>
    <p class="meta">Avec {{ $participants->map(fn ($p) => $p->character?->fullName())->filter()->join(', ', ' et ') }}</p>
    @if ($scene->warningLabels() !== [])
        <p class="warnings meta">Avertissements : {{ implode(' · ', $scene->warningLabels()) }}</p>
    @endif

    @foreach ($posts as $post)
        <article @class(['post', 'post--c-'.$post->character?->dialogue_color => $post->character?->dialogue_color])>
            <p class="post__character">{{ $post->character_name }}</p>
            <p class="post__meta">{{ $post->created_at->locale('fr')->isoFormat('LLL') }}@if ($post->edited_at) · modifiée le {{ $post->edited_at->locale('fr')->isoFormat('LLL') }}@endif</p>
            <div>{{ $post->html() }}</div>
        </article>
    @endforeach

    <footer>
        <p>Exporté le {{ now()->locale('fr')->isoFormat('LLL') }}. Texte écrit par les joueurs de Tsukimori, qui en restent les auteurs ; à garder pour soi.</p>
    </footer>
</main>
</body>
</html>
