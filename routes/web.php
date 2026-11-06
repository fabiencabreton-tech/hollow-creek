<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AccountDataController;
use App\Http\Controllers\PushSubscriptionController;
use App\Http\Controllers\ActiveCharacterController;
use App\Http\Controllers\ArchiveController;
use App\Http\Controllers\BackupDownloadController;
use App\Http\Controllers\BlockController;
use App\Http\Controllers\CensusController;
use App\Http\Controllers\CharacterController;
use App\Http\Controllers\CityController;
use App\Http\Controllers\ClassifiedAdController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\FaceclaimController;
use App\Http\Controllers\FamilyController;
use App\Http\Controllers\FamilyMembershipController;
use App\Http\Controllers\FamilyPrelinkController;
use App\Http\Controllers\FeedController;
use App\Http\Controllers\GazetteController;
use App\Http\Controllers\KirisawaController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InvestigationController;
use App\Http\Controllers\JournalController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\MyCharactersController;
use App\Http\Controllers\MySceneController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PredefinedController;
use App\Http\Controllers\RelationshipController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ResidenceController;
use App\Http\Controllers\SceneController;
use App\Http\Controllers\SceneParticipationController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SpeciesController;
use App\Http\Controllers\UniverseController;
use App\Http\Middleware\RejectHoneypotSubmissions;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Site public
|--------------------------------------------------------------------------
| Les adresses de connexion, d'inscription, de mot de passe oublié et de double
| authentification sont déclarées par Fortify (voir config/fortify.php).
*/

Route::get('/', HomeController::class)->name('home');

// L'univers
Route::get('/univers', UniverseController::class)->name('universe.index');
Route::get('/univers/especes', [SpeciesController::class, 'index'])->name('species.index');
Route::get('/univers/especes/{species}', [SpeciesController::class, 'show'])->name('species.show');
Route::get('/univers/familles', [FamilyController::class, 'index'])->name('families.index');
Route::get('/univers/familles/{family}', [FamilyController::class, 'show'])->name('families.show');

// La ville
Route::get('/ville', [CityController::class, 'index'])->name('city.index');
Route::get('/ville/quartiers/{district}', [CityController::class, 'district'])->name('districts.show');
Route::get('/ville/lieux/{location}', [CityController::class, 'location'])->name('locations.show');

// The Hollow Creek Gazette
Route::get('/gazette', [GazetteController::class, 'index'])->name('gazette.index');
Route::get('/gazette/{article}', [GazetteController::class, 'show'])->name('gazette.show');

// Personnages, bottin des avatars et prédéfinis
Route::get('/personnages', [CharacterController::class, 'index'])->name('characters.index');
Route::get('/personnages/{character}', [CharacterController::class, 'show'])->name('characters.show');
Route::get('/bottin', [FaceclaimController::class, 'index'])->name('faceclaims.index');
Route::get('/predefinis', [PredefinedController::class, 'index'])->name('predefined.index');
Route::get('/predefinis/{predefined}', [PredefinedController::class, 'show'])->name('predefined.show');

// Scènes RP (lecture publique des scènes publiques ; une scène privée répond 404 aux autres)
Route::get('/registre-de-kirisawa', KirisawaController::class)->name('kirisawa.index');

// Immersion : archives, annonces, habitations, enquêtes, journaux.
Route::get('/archives', [ArchiveController::class, 'index'])->name('archives.index');
Route::get('/archives/{record}', [ArchiveController::class, 'show'])->name('archives.show');
Route::get('/annonces', [ClassifiedAdController::class, 'index'])->name('ads.index');
Route::get('/ville/habitations', [ResidenceController::class, 'index'])->name('residences.index');
Route::get('/ville/habitations/{residence}', [ResidenceController::class, 'show'])->name('residences.show');
Route::get('/enquetes', [InvestigationController::class, 'index'])->name('investigations.index');
Route::get('/enquetes/{investigation}', [InvestigationController::class, 'show'])->name('investigations.show');
Route::get('/enquetes/{investigation}/indices/{clue}', [InvestigationController::class, 'clue'])->whereNumber('clue')->name('clues.show');
Route::get('/personnages/{character}/journal', [JournalController::class, 'index'])->name('journal.index');
Route::get('/personnages/{character}/journal/{entry}', [JournalController::class, 'show'])->whereNumber('entry')->name('journal.show');

Route::get('/scenes', [SceneController::class, 'index'])->name('scenes.index');
Route::get('/scenes/{scene}', [SceneController::class, 'show'])->whereNumber('scene')->name('scenes.show');
Route::get('/scenes/{scene}/non-lu', [SceneController::class, 'unread'])->whereNumber('scene')->name('scenes.unread');
Route::get('/scenes/{scene}/export', [SceneController::class, 'export'])->whereNumber('scene')->middleware('throttle:10,1')->name('scenes.export');

// Recherche
Route::get('/recherche', SearchController::class)->middleware('throttle:30,1')->name('search');

// Murmures, événements
Route::get('/murmures', [FeedController::class, 'index'])->name('feed.index');
Route::get('/murmures/{feedPost}', [FeedController::class, 'show'])->whereNumber('feedPost')->name('feed.show');
Route::get('/evenements', [EventController::class, 'index'])->name('events.index');
Route::get('/evenements/{event}', [EventController::class, 'show'])->name('events.show');

// Bulle de contact (formulaires publics : limite de débit et champ piège)
Route::get('/contact', [ContactController::class, 'create'])->name('contact.create');
Route::get('/contact/envoyee', [ContactController::class, 'sent'])->name('contact.sent');
Route::get('/contact/suivi', [ContactController::class, 'resendForm'])->name('contact.resend');
Route::get('/contact/suivi/{reference}/{token}', [ContactController::class, 'track'])->where('token', '[A-Za-z0-9]{48}')->name('contact.track');
Route::get('/bottin/confirmer/{claim}/{token}', [FaceclaimController::class, 'confirmGuest'])->where('token', '[A-Za-z0-9]{48}')->middleware('throttle:10,1')->name('faceclaims.confirm');
Route::middleware(['throttle:formulaires-publics', RejectHoneypotSubmissions::class])->group(function () {
    Route::post('/contact', [ContactController::class, 'store'])->name('contact.store');
    Route::post('/contact/suivi', [ContactController::class, 'resend'])->name('contact.resend.send');
    Route::post('/contact/suivi/{reference}/{token}', [ContactController::class, 'trackReply'])->where('token', '[A-Za-z0-9]{48}')->name('contact.track.reply');
    Route::post('/bottin/demandes-visiteur', [FaceclaimController::class, 'storeGuest'])->name('faceclaims.guest');
});

// Pages officielles (contenu modifiable dans l'administration)
Route::get('/rejoindre', [PageController::class, 'show'])->defaults('slug', 'rejoindre')->name('pages.rejoindre');
Route::get('/faq', [PageController::class, 'show'])->defaults('slug', 'faq')->name('pages.faq');
Route::get('/reglement', [PageController::class, 'show'])->defaults('slug', 'reglement')->name('pages.reglement');
Route::get('/mentions-legales', [PageController::class, 'show'])->defaults('slug', 'mentions-legales')->name('pages.mentions-legales');
Route::get('/confidentialite', [PageController::class, 'show'])->defaults('slug', 'confidentialite')->name('pages.confidentialite');
Route::get('/conditions', [PageController::class, 'show'])->defaults('slug', 'conditions')->name('pages.conditions');
Route::view('/application', 'pages.application')->name('pages.application');

/*
|--------------------------------------------------------------------------
| Espace joueur
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->group(function () {
    Route::get('/compte', [AccountController::class, 'edit'])->name('account.edit');
    // Passage obligé par la confirmation du mot de passe avant de gérer la double authentification.
    Route::get('/compte/securite', [AccountController::class, 'security'])
        ->middleware('password.confirm')
        ->name('account.security');
    Route::get('/compte/donnees', [AccountDataController::class, 'show'])->name('account.data');
    Route::get('/compte/donnees/textes.html', [AccountDataController::class, 'exportHtml'])->middleware('throttle:6,1')->name('account.data.html');
    Route::get('/compte/donnees/donnees.json', [AccountDataController::class, 'exportJson'])->middleware('throttle:6,1')->name('account.data.json');
    Route::delete('/compte/donnees', [AccountDataController::class, 'destroy'])->middleware('throttle:6,1')->name('account.data.destroy');
    Route::put('/compte/preferences', [AccountController::class, 'updatePreferences'])->name('account.preferences');
    Route::delete('/compte/autres-sessions', [AccountController::class, 'destroyOtherSessions'])
        ->middleware('throttle:6,1')
        ->name('account.sessions.destroy');

    Route::get('/tableau-de-bord', DashboardController::class)
        ->middleware('verified')
        ->name('dashboard');

    // Fiches de personnage (adresse e-mail vérifiée exigée)
    Route::middleware('verified')->group(function () {
        Route::get('/mes-personnages', [MyCharactersController::class, 'index'])->name('characters.mine');
        Route::post('/mes-personnages', [MyCharactersController::class, 'store'])->middleware('throttle:10,1')->name('characters.store');
        Route::get('/mes-personnages/{character}/fiche/{step?}', [MyCharactersController::class, 'edit'])->whereNumber('step')->name('characters.edit');
        Route::put('/mes-personnages/{character}/fiche/{step}', [MyCharactersController::class, 'update'])->whereNumber('step')->name('characters.update');
        Route::post('/mes-personnages/{character}/envoyer', [MyCharactersController::class, 'submit'])->name('characters.submit');
        Route::post('/mes-personnages/{character}/archiver', [MyCharactersController::class, 'archive'])->name('characters.archive');
        Route::delete('/mes-personnages/{character}', [MyCharactersController::class, 'destroy'])->name('characters.destroy');

        Route::post('/bottin/demandes', [FaceclaimController::class, 'store'])->middleware('throttle:10,1')->name('faceclaims.store');
        Route::post('/bottin/demandes/{claim}/prolonger', [FaceclaimController::class, 'extend'])->name('faceclaims.extend');
        Route::delete('/bottin/demandes/{claim}', [FaceclaimController::class, 'destroy'])->name('faceclaims.destroy');

        Route::post('/predefinis/{predefined}/reprendre', [PredefinedController::class, 'claim'])->middleware('throttle:10,1')->name('predefined.claim');
        // Notifications, messagerie, blocages, signalements
        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::get('/notifications/compteurs', [NotificationController::class, 'counts'])->name('notifications.counts');
        Route::get('/notifications/{notification}', [NotificationController::class, 'open'])->whereNumber('notification')->name('notifications.open');
        Route::post('/notifications/tout-lire', [NotificationController::class, 'readAll'])->name('notifications.read-all');
        Route::post('/notifications/appareils', [PushSubscriptionController::class, 'store'])->middleware('throttle:10,1')->name('push.store');
        Route::delete('/notifications/appareils', [PushSubscriptionController::class, 'destroy'])->name('push.destroy');
        Route::delete('/notifications/appareils/{subscription}', [PushSubscriptionController::class, 'forget'])->whereNumber('subscription')->name('push.forget');
        Route::post('/notifications/appareils/essai', [PushSubscriptionController::class, 'test'])->middleware('throttle:5,1')->name('push.test');

        Route::get('/messages', [MessageController::class, 'index'])->name('messages.index');
        Route::get('/messages/nouveau', [MessageController::class, 'create'])->name('messages.create');
        Route::post('/messages', [MessageController::class, 'store'])->middleware('throttle:10,1')->name('messages.store');
        Route::get('/messages/{conversation}', [MessageController::class, 'show'])->whereNumber('conversation')->name('messages.show');
        Route::post('/messages/{conversation}', [MessageController::class, 'send'])->whereNumber('conversation')->middleware('throttle:30,1')->name('messages.send');
        Route::get('/messages/{conversation}/nouveaux', [MessageController::class, 'poll'])->whereNumber('conversation')->middleware('throttle:20,1')->name('messages.poll');
        Route::post('/messages/{conversation}/archiver', [MessageController::class, 'archive'])->whereNumber('conversation')->name('messages.archive');
        Route::post('/messages/{conversation}/quitter', [MessageController::class, 'leave'])->whereNumber('conversation')->name('messages.leave');

        Route::post('/compte/bloques', [BlockController::class, 'store'])->middleware('throttle:10,1')->name('blocks.store');
        Route::delete('/compte/bloques/{block}', [BlockController::class, 'destroy'])->name('blocks.destroy');

        Route::get('/signaler', [ReportController::class, 'create'])->name('reports.create');
        Route::post('/signaler', [ReportController::class, 'store'])->middleware('throttle:10,1')->name('reports.store');

        // Relations
        Route::get('/relations', [RelationshipController::class, 'index'])->name('relationships.index');
        Route::post('/relations', [RelationshipController::class, 'store'])->middleware('throttle:10,1')->name('relationships.store');
        Route::post('/relations/{relationship}/accepter', [RelationshipController::class, 'accept'])->name('relationships.accept');
        Route::post('/relations/{relationship}/decliner', [RelationshipController::class, 'decline'])->name('relationships.decline');
        Route::delete('/relations/{relationship}', [RelationshipController::class, 'withdraw'])->name('relationships.withdraw');
        Route::post('/relations/{relationship}/terminer', [RelationshipController::class, 'end'])->name('relationships.end');
        Route::put('/relations/{relationship}/description', [RelationshipController::class, 'describe'])->name('relationships.describe');

        // Murmures et événements
        Route::post('/murmures', [FeedController::class, 'store'])->middleware('throttle:10,1')->name('feed.store');
        Route::delete('/murmures/{feedPost}', [FeedController::class, 'destroy'])->name('feed.destroy');
        Route::post('/murmures/{feedPost}/commentaires', [FeedController::class, 'comment'])->middleware('throttle:20,1')->name('feed.comment');
        Route::delete('/murmures/commentaires/{comment}', [FeedController::class, 'destroyComment'])->name('feed.comment.destroy');
        Route::post('/evenements/{event}/inscription', [EventController::class, 'register'])->name('events.register');
        Route::delete('/evenements/{event}/inscription/{participant}', [EventController::class, 'unregister'])->name('events.unregister');

        // Recensement d'activité
        Route::get('/recensement', [CensusController::class, 'show'])->name('census.show');
        Route::post('/recensement', [CensusController::class, 'store'])->name('census.store');

        // Mes demandes à l'équipe
        Route::get('/mes-demandes', [ContactController::class, 'mine'])->name('contact.mine');
        Route::get('/mes-demandes/{ticket}', [ContactController::class, 'mineShow'])->name('contact.mine.show');
        Route::post('/mes-demandes/{ticket}', [ContactController::class, 'mineReply'])->middleware('throttle:20,1')->name('contact.mine.reply');

        // Profil personnalisé
        Route::get('/mes-personnages/{character}/profil', [MyCharactersController::class, 'editProfile'])->name('characters.profile.edit');
        Route::put('/mes-personnages/{character}/profil', [MyCharactersController::class, 'updateProfile'])->name('characters.profile.update');

        Route::put('/mes-personnages/{character}/couleur', [MyCharactersController::class, 'updateColor'])->name('characters.color');

        // Scènes RP
        Route::get('/mes-scenes', MySceneController::class)->name('scenes.mine');

        Route::post('/personnage-actif', ActiveCharacterController::class)->name('characters.activate');

        // Familles
        Route::post('/univers/familles/proposer', [FamilyMembershipController::class, 'propose'])->middleware('throttle:5,1')->name('families.propose');
        Route::post('/univers/familles/{family}/nouveau-personnage', [FamilyMembershipController::class, 'newCharacter'])->middleware('throttle:10,1')->name('families.new-character');
        Route::post('/univers/familles/{family}/rejoindre', [FamilyMembershipController::class, 'join'])->middleware('throttle:10,1')->name('families.join');
        Route::put('/univers/familles/{family}/arbre/{member}', [FamilyMembershipController::class, 'place'])->whereNumber('member')->name('families.place');
        Route::post('/univers/familles/{family}/preliens', [FamilyPrelinkController::class, 'store'])->middleware('throttle:10,1')->name('prelinks.store');
        Route::post('/preliens/{prelink}/pourvu', [FamilyPrelinkController::class, 'fill'])->whereNumber('prelink')->name('prelinks.fill');
        Route::delete('/preliens/{prelink}', [FamilyPrelinkController::class, 'destroy'])->whereNumber('prelink')->name('prelinks.destroy');
        Route::delete('/demandes-de-famille/{familyRequest}', [FamilyMembershipController::class, 'cancel'])->whereNumber('familyRequest')->name('family-requests.cancel');
        Route::post('/mes-personnages/{character}/quitter-la-famille', [FamilyMembershipController::class, 'leave'])->name('families.leave');

        // Immersion
        Route::post('/annonces', [ClassifiedAdController::class, 'store'])->middleware('throttle:10,1')->name('ads.store');
        Route::post('/annonces/{ad}/prolonger', [ClassifiedAdController::class, 'renew'])->whereNumber('ad')->name('ads.renew');
        Route::delete('/annonces/{ad}', [ClassifiedAdController::class, 'destroy'])->whereNumber('ad')->name('ads.destroy');
        Route::post('/ville/habitations/{residence}/emmenager', [ResidenceController::class, 'moveIn'])->middleware('throttle:10,1')->name('residences.move-in');
        Route::post('/mes-personnages/{character}/demenager', [ResidenceController::class, 'moveOut'])->name('residences.move-out');
        Route::get('/mes-indices', [InvestigationController::class, 'mine'])->name('clues.mine');
        Route::post('/enquetes/{investigation}/indices/{clue}/confier', [InvestigationController::class, 'share'])->whereNumber('clue')->middleware('throttle:20,1')->name('clues.share');
        Route::get('/personnages/{character}/journal/nouvelle', [JournalController::class, 'create'])->name('journal.create');
        Route::post('/personnages/{character}/journal', [JournalController::class, 'store'])->middleware('throttle:20,1')->name('journal.store');
        Route::get('/personnages/{character}/journal/{entry}/modifier', [JournalController::class, 'edit'])->whereNumber('entry')->name('journal.edit');
        Route::put('/personnages/{character}/journal/{entry}', [JournalController::class, 'update'])->whereNumber('entry')->middleware('throttle:30,1')->name('journal.update');
        Route::delete('/personnages/{character}/journal/{entry}', [JournalController::class, 'destroy'])->whereNumber('entry')->name('journal.destroy');
        Route::get('/scenes/nouvelle', [SceneController::class, 'create'])->name('scenes.create');
        Route::post('/scenes', [SceneController::class, 'store'])->middleware('throttle:10,1')->name('scenes.store');
        Route::post('/scenes/apercu', [SceneController::class, 'preview'])->middleware('throttle:60,1')->name('scenes.preview');

        Route::whereNumber('scene')->scopeBindings()->group(function () {
            Route::post('/scenes/{scene}/reponses', [SceneController::class, 'reply'])->middleware('throttle:20,1')->name('scenes.reply');
            Route::put('/scenes/{scene}/brouillon', [SceneController::class, 'saveDraft'])->middleware('throttle:60,1')->name('scenes.draft');
            Route::get('/scenes/{scene}/reponses/{post}/modifier', [SceneController::class, 'editPost'])->name('scenes.posts.edit');
            Route::put('/scenes/{scene}/reponses/{post}', [SceneController::class, 'updatePost'])->middleware('throttle:20,1')->name('scenes.posts.update');
            Route::post('/scenes/{scene}/statut', [SceneController::class, 'updateStatus'])->name('scenes.status');

            Route::post('/scenes/{scene}/invitations', [SceneParticipationController::class, 'invite'])->middleware('throttle:20,1')->name('scenes.invite');
            Route::post('/scenes/{scene}/invitations/{participant}/accepter', [SceneParticipationController::class, 'accept'])->name('scenes.invitations.accept');
            Route::post('/scenes/{scene}/invitations/{participant}/decliner', [SceneParticipationController::class, 'decline'])->name('scenes.invitations.decline');
            Route::delete('/scenes/{scene}/invitations/{participant}', [SceneParticipationController::class, 'cancel'])->name('scenes.invitations.cancel');
            Route::post('/scenes/{scene}/rejoindre', [SceneParticipationController::class, 'join'])->name('scenes.join');
            Route::post('/scenes/{scene}/quitter', [SceneParticipationController::class, 'leave'])->name('scenes.leave');
        });
    });
});

/*
|--------------------------------------------------------------------------
| Administration : téléchargement d'une archive de sauvegarde
|--------------------------------------------------------------------------
| Le panneau Filament (/admin) déclare ses propres pages ; seul le téléchargement
| passe par une route classique, pour envoyer le fichier sans le charger en mémoire.
*/

Route::middleware(['auth', 'verified', 'can:sauvegardes.gerer', 'staff.2fa'])
    ->get('/admin/sauvegardes/telecharger/{disk}/{file}', BackupDownloadController::class)
    ->where('file', '[A-Za-z0-9._-]+\.zip')
    ->name('admin.backups.download');
