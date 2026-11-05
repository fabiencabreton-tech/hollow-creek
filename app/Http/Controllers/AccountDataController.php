<?php

namespace App\Http\Controllers;

use App\Services\AccountData;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

/**
 * « Mes données » : export (fichier lisible et format réutilisable) et suppression du compte.
 */
class AccountDataController extends Controller
{
    public function __construct(private readonly AccountData $data) {}

    public function show(Request $request): View
    {
        return view('account.data', ['user' => $request->user()]);
    }

    public function exportJson(Request $request): Response
    {
        $json = json_encode($this->data->export($request->user()), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return response((string) $json, 200, [
            'Content-Type' => 'application/json; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="hollow-creek-mes-donnees.json"',
        ]);
    }

    public function exportHtml(Request $request): Response
    {
        $html = view('account.export', ['data' => $this->data->export($request->user())])->render();

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="hollow-creek-mes-textes.html"',
        ]);
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        $request->validateWithBag('deleteAccount', [
            'password' => ['required', 'string', 'current_password:web'],
            'confirmation' => ['required', 'in:SUPPRIMER'],
            'texts' => ['required', 'in:anonymiser,effacer'],
        ], [
            'password.current_password' => 'Le mot de passe est incorrect.',
            'confirmation.in' => 'Écrivez SUPPRIMER en majuscules pour confirmer.',
        ]);

        if ($user->isStaff()) {
            return back()->withErrors(['password' => 'Un compte de l’équipe ne peut pas être supprimé ainsi : demandez d’abord à un administrateur de retirer vos rôles.'], 'deleteAccount');
        }

        $this->data->delete($user, $request->input('texts') === 'effacer');

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('status', 'Votre compte est supprimé. Merci d’avoir écrit à Hollow Creek.');
    }
}
