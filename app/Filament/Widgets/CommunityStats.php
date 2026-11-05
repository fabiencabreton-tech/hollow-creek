<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Support\Settings;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CommunityStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $pollingInterval = null;

    protected ?string $heading = 'Communauté';

    protected function getStats(): array
    {
        $settings = app(Settings::class);

        return [
            Stat::make('Comptes', User::query()->count())
                ->description(User::query()->whereNull('email_verified_at')->count().' en attente de vérification d’e-mail'),
            Stat::make('Comptes suspendus', User::query()->whereNotNull('suspended_at')->count()),
            Stat::make('Inscriptions', $settings->registrationsOpen() ? 'Ouvertes' : 'Fermées')
                ->description('Date RP : '.$settings->rpDate())
                ->color($settings->registrationsOpen() ? 'success' : 'gray'),
        ];
    }
}
