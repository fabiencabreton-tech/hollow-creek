<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use App\Support\ActivityLogger;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = 'Communauté';

    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'compte';

    protected static ?string $pluralModelLabel = 'comptes';

    protected static ?string $navigationLabel = 'Comptes';

    protected static ?string $slug = 'comptes';

    protected static ?string $recordTitleAttribute = 'pseudo';

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('roles'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('pseudo')
                    ->label('Pseudo')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('Adresse e-mail')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('roles.name')
                    ->label('Rôles')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => config("hollow.roles.{$state}.label", Str::headline($state))),
                IconColumn::make('email_verified_at')
                    ->label('E-mail vérifié')
                    ->boolean()
                    ->getStateUsing(fn (User $record): bool => $record->email_verified_at !== null),
                IconColumn::make('two_factor_confirmed_at')
                    ->label('Double auth.')
                    ->boolean()
                    ->getStateUsing(fn (User $record): bool => $record->hasConfirmedTwoFactor()),
                TextColumn::make('suspended_at')
                    ->label('Suspension')
                    ->placeholder('—')
                    ->dateTime('d/m/Y H:i')
                    ->color('danger')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Inscription')
                    ->dateTime('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('roles')
                    ->label('Rôle')
                    ->relationship('roles', 'name')
                    ->getOptionLabelFromRecordUsing(fn (Role $record): string => config("hollow.roles.{$record->name}.label", Str::headline($record->name))),
                TernaryFilter::make('suspended_at')
                    ->label('Suspendus')
                    ->nullable(),
                TernaryFilter::make('email_verified_at')
                    ->label('E-mail vérifié')
                    ->nullable(),
            ])
            ->recordActions([
                ActionGroup::make([
                    static::rolesAction(),
                    static::verifyEmailAction(),
                    static::resetTwoFactorAction(),
                    static::suspendAction(),
                    static::reactivateAction(),
                ]),
            ]);
    }

    protected static function rolesAction(): Action
    {
        return Action::make('roles')
            ->label('Modifier les rôles')
            ->icon(Heroicon::OutlinedShieldCheck)
            ->visible(fn (User $record): bool => auth()->user()?->can('manageRoles', $record) ?? false)
            ->fillForm(fn (User $record): array => ['roles' => $record->roles->pluck('name')->all()])
            ->schema([
                CheckboxList::make('roles')
                    ->label('Rôles')
                    ->options(fn (): array => Role::query()
                        ->orderBy('name')
                        ->pluck('name', 'name')
                        ->map(fn (string $name): string => config("hollow.roles.{$name}.label", Str::headline($name)))
                        ->all())
                    ->disableOptionWhen(fn (string $value): bool => $value === 'administrateur' && ! auth()->user()?->hasRole('administrateur')),
            ])
            ->action(function (User $record, array $data): void {
                abort_unless(auth()->user()?->can('manageRoles', $record), 403);

                $before = $record->roles->pluck('name')->sort()->values()->all();
                $after = collect($data['roles'] ?? [])->sort()->values()->all();

                $record->syncRoles($after);

                ActivityLogger::record('compte.roles-modifies', $record, $record->pseudo, [
                    'avant' => $before,
                    'apres' => $after,
                ]);

                Notification::make()->title('Rôles enregistrés')->success()->send();
            });
    }

    protected static function verifyEmailAction(): Action
    {
        return Action::make('verifyEmail')
            ->label('Valider l’adresse e-mail')
            ->icon(Heroicon::OutlinedEnvelope)
            ->visible(fn (User $record): bool => $record->email_verified_at === null && (auth()->user()?->can('moderate', $record) ?? false))
            ->requiresConfirmation()
            ->modalDescription('À utiliser seulement si vous avez vérifié par un autre moyen que cette adresse appartient bien à la personne.')
            ->action(function (User $record): void {
                abort_unless(auth()->user()?->can('moderate', $record), 403);

                $record->forceFill(['email_verified_at' => now()])->save();
                ActivityLogger::record('compte.email-valide', $record, $record->pseudo);

                Notification::make()->title('Adresse e-mail validée')->success()->send();
            });
    }

    protected static function resetTwoFactorAction(): Action
    {
        return Action::make('resetTwoFactor')
            ->label('Réinitialiser la double authentification')
            ->icon(Heroicon::OutlinedKey)
            ->color('warning')
            ->visible(fn (User $record): bool => filled($record->two_factor_secret) && (auth()->user()?->can('moderate', $record) ?? false))
            ->requiresConfirmation()
            ->modalDescription('Pour une personne qui a perdu son téléphone et ses codes de secours. Vérifiez son identité avant de continuer : elle pourra se connecter avec son seul mot de passe, puis réactiver la protection.')
            ->action(function (User $record): void {
                abort_unless(auth()->user()?->can('moderate', $record), 403);

                $record->forceFill([
                    'two_factor_secret' => null,
                    'two_factor_recovery_codes' => null,
                    'two_factor_confirmed_at' => null,
                ])->save();

                ActivityLogger::record('compte.2fa-reinitialisee', $record, $record->pseudo);

                Notification::make()->title('Double authentification réinitialisée')->success()->send();
            });
    }

    protected static function suspendAction(): Action
    {
        return Action::make('suspend')
            ->label('Suspendre')
            ->icon(Heroicon::OutlinedNoSymbol)
            ->color('danger')
            ->visible(fn (User $record): bool => ! $record->isSuspended() && (auth()->user()?->can('moderate', $record) ?? false))
            ->modalHeading('Suspendre ce compte')
            ->modalDescription('La personne est déconnectée de tous ses appareils et ne peut plus se connecter. Le motif est conservé dans le journal.')
            ->schema([
                Textarea::make('reason')
                    ->label('Motif')
                    ->required()
                    ->maxLength(500),
            ])
            ->action(function (User $record, array $data): void {
                abort_unless(auth()->user()?->can('moderate', $record), 403);

                if ($record->hasRole('administrateur') && ! auth()->user()?->hasRole('administrateur')) {
                    abort(403);
                }

                $record->forceFill([
                    'suspended_at' => now(),
                    'suspension_reason' => $data['reason'],
                    'remember_token' => Str::random(60),
                ])->save();

                if (config('session.driver') === 'database') {
                    DB::connection(config('session.connection'))
                        ->table(config('session.table', 'sessions'))
                        ->where('user_id', $record->getKey())
                        ->delete();
                }

                ActivityLogger::record('compte.suspendu', $record, $data['reason']);

                Notification::make()->title('Compte suspendu')->success()->send();
            });
    }

    protected static function reactivateAction(): Action
    {
        return Action::make('reactivate')
            ->label('Réactiver')
            ->icon(Heroicon::OutlinedCheckCircle)
            ->color('success')
            ->visible(fn (User $record): bool => $record->isSuspended() && (auth()->user()?->can('moderate', $record) ?? false))
            ->requiresConfirmation()
            ->action(function (User $record): void {
                abort_unless(auth()->user()?->can('moderate', $record), 403);

                $record->forceFill([
                    'suspended_at' => null,
                    'suspension_reason' => null,
                ])->save();

                ActivityLogger::record('compte.reactive', $record, $record->pseudo);

                Notification::make()->title('Compte réactivé')->success()->send();
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
        ];
    }
}
