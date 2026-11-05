<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Archives des scènes · Hollow Creek</title>
    <style>
        body { margin: 0; background: #efe6d2; color: #0e1311; font-family: Georgia, serif; line-height: 1.6; }
        main { max-width: 80ch; margin: 0 auto; padding: 40px 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 8px; border-bottom: 1px solid rgba(14, 19, 17, 0.2); font-size: 0.95rem; }
        .meta { color: #46514c; font-family: system-ui, sans-serif; font-size: 0.85rem; }
    </style>
</head>
<body>
<main>
    <p class="meta">Hollow Creek — archives générées le {{ now()->locale('fr')->isoFormat('LLL') }}. Document de l’équipe : il contient aussi les scènes privées.</p>
    <h1>Archives des scènes</h1>
    <table>
        <thead><tr><th>N°</th><th>Titre</th><th>Date RP</th><th>Statut</th><th>Visibilité</th><th>Réponses</th></tr></thead>
        <tbody>
            @foreach ($entries as $entry)
                <tr>
                    <td>{{ $entry['scene']->id }}</td>
                    <td><a href="{{ $entry['file'] }}">{{ $entry['scene']->title }}</a></td>
                    <td>{{ $entry['scene']->rp_date }}</td>
                    <td>{{ $entry['scene']->status->getLabel() }}</td>
                    <td>{{ $entry['scene']->visibility->getLabel() }}</td>
                    <td>{{ $entry['scene']->posts_count }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</main>
</body>
</html>
