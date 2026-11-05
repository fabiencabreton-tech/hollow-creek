<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\BackupDownloadController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Site public
|--------------------------------------------------------------------------
| Les adresses de connexion, d'inscription, de mot de passe oublié et de double
| authentification sont déclarées par Fortify (voir config/fortify.php).
*/

Route::get('/', HomeController::class)->name('home');

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
