{{-- Installer Tsukimori comme une application (PWA) : Android, iPhone, ordinateur. --}}
<x-layout title="Installer l’application" description="Installer Tsukimori sur votre téléphone ou votre ordinateur : une icône, le plein écran, sans passer par un magasin d’applications.">
    <div class="container">
        <header class="page-header app-install__header">
            <img class="app-install__icon" src="{{ asset('icones/icone-192.png') }}" width="96" height="96" alt="">
            <div class="stack stack--tight">
                <p class="eyebrow">Sur téléphone et ordinateur</p>
                <h1>Installer Tsukimori</h1>
                <p class="lede">Tsukimori s’installe comme une application, sans passer par un magasin : une icône sur l’écran d’accueil, l’ouverture en plein écran, sans barre d’adresse. C’est le même site, toujours à jour.</p>
            </div>
        </header>

        <div class="alert alert--success" role="status" data-install-status hidden>
            <p>L’application est installée : vous l’utilisez en ce moment.</p>
        </div>

        <div class="grid-cards app-install">
            <section class="card stack" aria-labelledby="titre-android">
                <h2 id="titre-android" class="card__title">Sur Android</h2>
                <p data-install-ready hidden>Votre navigateur peut installer Tsukimori directement.</p>
                <p><button type="button" class="btn btn--glow" data-install hidden>Installer l’application</button></p>
                <ol class="app-install__steps">
                    <li>Ouvrez Tsukimori dans <strong>Chrome</strong> (ou Edge, Samsung Internet).</li>
                    <li>Touchez le menu <span aria-hidden="true">⋮</span><span class="visually-hidden">(trois points)</span> en haut à droite.</li>
                    <li>Choisissez <strong>« Installer l’application »</strong> ou <strong>« Ajouter à l’écran d’accueil »</strong>.</li>
                </ol>
            </section>

            <section class="card stack" aria-labelledby="titre-iphone">
                <h2 id="titre-iphone" class="card__title">Sur iPhone et iPad</h2>
                <ol class="app-install__steps">
                    <li>Ouvrez Tsukimori dans <strong>Safari</strong>.</li>
                    <li>Touchez le bouton <strong>Partager</strong> (le carré d’où sort une flèche vers le haut).</li>
                    <li>Faites défiler et choisissez <strong>« Sur l’écran d’accueil »</strong>, puis <strong>Ajouter</strong>.</li>
                </ol>
                <p class="muted">L’icône du sceau 月森町 apparaît avec vos autres applications.</p>
            </section>

            <section class="card stack" aria-labelledby="titre-ordinateur">
                <h2 id="titre-ordinateur" class="card__title">Sur ordinateur</h2>
                <p>Dans <strong>Chrome</strong> ou <strong>Edge</strong>, cliquez sur l’icône d’installation à droite de la barre d’adresse, ou passez par le menu&nbsp;: <strong>« Installer Tsukimori »</strong>.</p>
                <p class="muted">Tsukimori s’ouvre alors dans sa propre fenêtre.</p>
            </section>
        </div>

        <section class="prose app-install__notes" aria-labelledby="titre-alertes">
            <h2 id="titre-alertes">Être prévenu, même application fermée</h2>
            <p>Une fois connecté, activez les notifications dans <a href="{{ route('account.edit') }}#notifications-appareils">Mon compte › Notifications sur vos appareils</a> : une réponse dans vos scènes, un message ou une décision de l’équipe s’affichent alors sur l’écran du téléphone. Vous choisissez les types d’alerte voulus, et vous pouvez les couper appareil par appareil.</p>
            <p>Sur iPhone et iPad, il faut d’abord ajouter Tsukimori à l’écran d’accueil (ci-dessus), puis l’ouvrir depuis son icône pour activer les notifications.</p>
        </section>

        <section class="prose app-install__notes" aria-labelledby="titre-garde">
            <h2 id="titre-garde">Ce que l’application garde sur l’appareil</h2>
            <p>Seulement de quoi s’ouvrir vite : les styles, les polices et les icônes. Vos scènes, vos messages et vos fiches ne sont pas copiés sur l’appareil : chaque page est demandée au serveur, qui vérifie qui a le droit de la lire. Un RP privé reste donc privé.</p>
            <p>Sans réseau, l’application affiche une page « hors connexion » ; un texte en cours d’écriture reste gardé sur l’appareil et vous le retrouvez en revenant sur sa page.</p>
            <p>Pour la retirer, désinstallez-la comme n’importe quelle application : votre compte n’est pas touché.</p>
        </section>
    </div>
</x-layout>
