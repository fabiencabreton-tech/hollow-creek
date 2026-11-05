<?php

namespace Tests;

use App\Models\User;
use App\Support\Settings;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Rôles, permissions et pages officielles sont créés avant chaque test.
     */
    protected bool $seed = true;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    protected function openRegistrations(bool $open = true): void
    {
        app(Settings::class)->set('registrations_open', $open);
    }

    protected function player(array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->assignRole('joueur');

        return $user;
    }

    protected function staff(string $role = 'administrateur', bool $twoFactor = true): User
    {
        $factory = User::factory();

        if ($twoFactor) {
            $factory = $factory->withTwoFactor();
        }

        $user = $factory->create();
        $user->assignRole($role);

        return $user;
    }
}
