<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\BackupDownloadController;
use App\Http\Controllers\CharacterController;
use App\Http\Controllers\CityController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FaceclaimController;
use App\Http\Controllers\FamilyController;
use App\Http\Controllers\GazetteController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MyCharactersController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PredefinedController;
use App\Http\Controllers\SpeciesController;
use App\Http\Controllers\UniverseController;
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

// Pages officielles (contenu modifiable dans l'administration)
Route::get('/rejoindre', [PageController::class, 'show'])->defaults('slug', 'rejoindre')->name('pages.rejoindre');
Route::get('/faq', [PageController::class, 'show'])->defaults('slug', 'faq')->name('pages.faq');
Route::get('/reglement', [PageController::class, 'show'])->defaults('slug', 'reglement')->name('pages.reglement');
Route::get('/mentions-legales', [PageController::class, 'show'])->defaults('slug', 'mentions-legales')->name('pages.mentions-legales');
Route::get('/confidentialite', [PageController::class, 'show'])->defaults('slug', 'confidentialite')->name('pages.confidentialite');
Route::get('/conditions', [PageController::class, 'show'])->defaults('slug', 'conditions')->name('pages.conditions');

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
