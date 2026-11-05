<?php

namespace App\Actions\Fortify;

use App\Models\User;
use App\Support\Pseudo;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;

class UpdateUserProfileInformation implements UpdatesUserProfileInformation
{
    use PseudoValidationRules;

    /**
     * Met à jour le pseudo et l'adresse e-mail. Une nouvelle adresse doit être vérifiée.
     *
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     */
    public function update(User $user, array $input): void
    {
        $input['pseudo'] = Pseudo::clean((string) ($input['pseudo'] ?? ''));
        $input['email'] = mb_strtolower(trim((string) ($input['email'] ?? '')));

        Validator::make($input, [
            'pseudo' => $this->pseudoRules($user),
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class, 'email')->ignore($user->getKey())],
        ])->validateWithBag('updateProfileInformation');

        if ($input['email'] !== $user->email) {
            $user->forceFill([
                'pseudo' => $input['pseudo'],
                'email' => $input['email'],
                'email_verified_at' => null,
            ])->save();

            $user->sendEmailVerificationNotification();

            return;
        }

        $user->forceFill(['pseudo' => $input['pseudo']])->save();
    }
}
