<?php

namespace App\Filament\Resources\Faceclaims;

use App\Filament\Resources\Faceclaims\Pages\CreateFaceclaim;
use App\Filament\Resources\Faceclaims\Pages\EditFaceclaim;
use App\Filament\Resources\Faceclaims\Pages\ListFaceclaims;
use App\Models\Faceclaim;
use BackedEnum;
use Closure;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Noms des personnalités du bottin. Seul le nom est conservé, jamais de photo.
 */
class FaceclaimResource extends Resource
{
    protected static ?string $model = Faceclaim::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserCircle;

    protected static string|UnitEnum|null $navigationGroup = 'Personnages';

    protected static ?int $navigationSort = 30;

    protected static ?string $modelLabel = 'célébrité';

    protected static ?string $pluralModelLabel = 'célébrités';

    protected static ?string $navigationLabel = 'Bottin';

    protected static ?string $slug = 'bottin';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('activeClaim.character');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Personnalité')
                ->columnSpanFull()
                ->schema([
                    TextInput::make('name')
                        ->label('Prénom et nom')
                        ->helperText('Orthographe d’usage. Deux écritures qui ne diffèrent que par les accents, les majuscules ou les espaces désignent la même personne.')
                        ->required()
                        ->minLength(3)
                        ->maxLength(120)
                        ->rule(fn (?Faceclaim $record): Closure => function (string $attribute, mixed $value, Closure $fail) use ($record): void {
                            $exists = Faceclaim::query()
                                ->where('name_key', Faceclaim::key((string) $value))
                                ->when($record, fn (Builder $query) => $query->whereKeyNot($record->getKey()))
                                ->exists();

                            if ($exists) {
                                $fail('Cette personnalité figure déjà dans le bottin.');
                            }
                        }),
                    TextInput::make('category')
                        ->label('Catégorie')
                        ->helperText('Facultatif : acteur, actrice, musicien, mannequin…')
                        ->maxLength(60),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')->label('Personnalité')->searchable()->sortable(),
                TextColumn::make('category')->label('Catégorie')->placeholder('—'),
                TextColumn::make('public_status')
                    ->label('Statut')
                    ->badge()
                    ->state(fn (Faceclaim $record): string => $record->publicStatus())
                    ->color(fn (Faceclaim $record): string => $record->activeClaim?->status->getColor() ?? 'success'),
                TextColumn::make('character')
                    ->label('Personnage')
                    ->state(fn (Faceclaim $record): ?string => $record->activeClaim?->character?->fullName())
                    ->placeholder('—'),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFaceclaims::route('/'),
            'create' => CreateFaceclaim::route('/nouveau'),
            'edit' => EditFaceclaim::route('/{record}/modifier'),
        ];
    }
}
