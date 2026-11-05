<?php

namespace App\Actions\Fortify;

use App\Models\User;
use App\Support\Pseudo;
use App\Support\Settings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Spatie\Permission\Models\Role;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, PseudoValidationRules;

    public function __construct(private readonly Settings $settings) {}

    /**
     * Crée un compte joueur : pseudo, e-mail, mot de passe, attestation d'âge
     * et acceptation des conditions d'utilisation.
     *
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     */
    public function create(array $input): User
    {
        if (! $this->settings->registrationsOpen()) {
            throw ValidationException::withMessages([
                'email' => 'Les inscriptions sont fermées pour le moment.',
            ]);
        }

        $input['pseudo'] = Pseudo::clean((string) ($input['pseudo'] ?? ''));
        $input['email'] = mb_strtolower(trim((string) ($input['email'] ?? '')));

        Validator::make($input, [
            'pseudo' => $this->pseudoRules(),
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class, 'email')],
            'password' => $this->passwordRules(),
            'age' => ['accepted'],
            'terms' => ['accepted'],
        ], [
            'age.accepted' => 'Hollow Creek est réservé aux personnes majeures : merci de confirmer que vous avez au moins '.config('hollow.accounts.minimum_age', 18).' ans.',
            'terms.accepted' => 'Merci d’accepter le règlement, les conditions d’utilisation et la politique de confidentialité.',
        ])->validate();

        return DB::transaction(function () use ($input): User {
            $user = User::create([
                'pseudo' => $input['pseudo'],
                'email' => $input['email'],
                'password' => $input['password'],
                'age_confirmed_at' => now(),
                'terms_accepted_at' => now(),
            ]);

            if (Role::query()->where('name', User::DEFAULT_ROLE)->where('guard_name', 'web')->exists()) {
                $user->assignRole(User::DEFAULT_ROLE);
            }

            return $user;
        });
    }
}
