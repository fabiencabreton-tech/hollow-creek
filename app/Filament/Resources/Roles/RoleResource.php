<?php

namespace App\Filament\Resources\Roles;

use App\Filament\Resources\Roles\Pages\CreateRole;
use App\Filament\Resources\Roles\Pages\EditRole;
use App\Filament\Resources\Roles\Pages\ListRoles;
use App\Support\ActivityLogger;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use UnitEnum;

/**
 * Rôles de l'équipe et leurs permissions. Un nouveau rôle (par exemple
 * « rédaction de la Gazette ») se crée ici, sans toucher au code.
 */
class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Communauté';

    protected static ?int $navigationSort = 20;

    protected static ?string $modelLabel = 'rôle';

    protected static ?string $pluralModelLabel = 'rôles';

    protected static ?string $navigationLabel = 'Rôles et permissions';

    protected static ?string $slug = 'roles';

    protected static ?string $recordTitleAttribute = 'name';

    public static function label(string $name): string
    {
        return config("hollow.roles.{$name}.label", Str::headline($name));
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->schema([
                    TextInput::make('name')
                        ->label('Nom technique')
                        ->helperText('En minuscules, sans espace ni accent, par exemple « redaction-gazette ».')
                        ->required()
                        ->maxLength(60)
                        ->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')
                        ->unique(ignoreRecord: true)
                        ->disabled(fn (?Role $record): bool => $record !== null && array_key_exists($record->name, (array) config('hollow.roles'))),
                    CheckboxList::make('permissions')
                        ->label('Permissions')
                        ->relationship('permissions', 'name')
                        ->getOptionLabelFromRecordUsing(fn (Permission $record): string => config("hollow.permissions.{$record->name}", $record->name))
                        ->columns(1)
                        ->bulkToggleable(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Rôle')
                    ->formatStateUsing(fn (string $state): string => static::label($state))
                    ->description(fn (Role $record): string => $record->name)
                    ->searchable(),
                TextColumn::make('permissions_count')
                    ->label('Permissions')
                    ->counts('permissions'),
                TextColumn::make('users_count')
                    ->label('Comptes')
                    ->counts('users'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->after(function (Role $record): void {
                        app(PermissionRegistrar::class)->forgetCachedPermissions();
                        ActivityLogger::record('role.supprime', description: $record->name);
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRoles::route('/'),
            'create' => CreateRole::route('/nouveau'),
            'edit' => EditRole::route('/{record}/modifier'),
        ];
    }
}
