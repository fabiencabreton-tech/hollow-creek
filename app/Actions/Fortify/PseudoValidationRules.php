<?php

namespace App\Actions\Fortify;

use App\Models\User;
use App\Support\Pseudo;
use Closure;

trait PseudoValidationRules
{
    /**
     * @return array<int, mixed>
     */
    protected function pseudoRules(?User $ignore = null): array
    {
        return [
            'required',
            'string',
            'min:'.config('hollow.accounts.pseudo_min', 3),
            'max:'.config('hollow.accounts.pseudo_max', 30),
            'regex:'.Pseudo::PATTERN,
            function (string $attribute, mixed $value, Closure $fail) use ($ignore): void {
                $key = Pseudo::key((string) $value);

                if (strlen($key) < 2) {
                    $fail('Le pseudo doit contenir au moins deux lettres ou chiffres.');

                    return;
                }

                $taken = User::query()
                    ->where('pseudo_key', $key)
                    ->when($ignore, fn ($query) => $query->whereKeyNot($ignore->getKey()))
                    ->exists();

                if ($taken) {
                    $fail('Ce pseudo, ou un pseudo trop proche, est déjà utilisé.');
                }
            },
        ];
    }
}
