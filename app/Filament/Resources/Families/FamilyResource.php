<?php

namespace App\Filament\Resources\Families;

use App\Filament\Resources\Families\Pages\CreateFamily;
use App\Filament\Resources\Families\Pages\EditFamily;
use App\Filament\Resources\Families\Pages\ListFamilies;
use App\Filament\Support\ContentFields;
use App\Models\Family;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class FamilyResource extends Resource
{
    protected static ?string $model = Family::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHomeModern;

    protected static string|UnitEnum|null $navigationGroup = 'Univers';

    protected static ?int $navigationSort = 20;

    protected static ?string $modelLabel = 'famille';

    protected static ?string $pluralModelLabel = 'familles';

    protected static ?string $navigationLabel = 'Familles fondatrices';

    protected static ?string $slug = 'familles';

    protected static ?string $recordTitleAttribute = 'name';

    public static function publicUrl(Family $record): string
    {
        return route('families.show', $record);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Présentation')
                ->columnSpanFull()
                ->schema([
                    TextInput::make('name')
                        ->label('Nom')
                        ->helperText('Par exemple « Les Whitmore ».')
                        ->required()
                        ->maxLength(80),
                    Textarea::make('summary')
                        ->label('Résumé')
                        ->rows(3)
                        ->maxLength(600),
                    ContentFields::markdown('history', 'Histoire publique', 'Seulement ce que les habitants savent ou racontent : les secrets de l’intrigue restent dans les archives de l’équipe.'),
                    ContentFields::markdown('open_roles', 'Rôles ouverts aux joueurs'),
                ]),
            ContentFields::imageSection('univers')->columnSpanFull(),
            ContentFields::publication()->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('name')->label('Famille')->searchable(),
                IconColumn::make('is_published')->label('Publiée')->boolean(),
                TextColumn::make('updated_at')->label('Modifiée')->dateTime('d/m/Y H:i'),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFamilies::route('/'),
            'create' => CreateFamily::route('/nouvelle'),
            'edit' => EditFamily::route('/{record}/modifier'),
        ];
    }
}
