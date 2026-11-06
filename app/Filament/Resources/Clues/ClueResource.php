<?php

namespace App\Filament\Resources\Clues;

use App\Filament\Resources\Clues\Pages\CreateClue;
use App\Filament\Resources\Clues\Pages\EditClue;
use App\Filament\Resources\Clues\Pages\ListClues;
use App\Enums\CharacterStatus;
use App\Filament\Support\ContentFields;
use App\Models\Character;
use App\Models\Clue;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class ClueResource extends Resource
{
    protected static ?string $model = Clue::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static string|UnitEnum|null $navigationGroup = 'Immersion';

    protected static ?int $navigationSort = 40;

    protected static ?string $modelLabel = 'indice';

    protected static ?string $pluralModelLabel = 'indices';

    protected static ?string $navigationLabel = 'Indices';

    protected static ?string $slug = 'indices';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Indice')
                ->columnSpanFull()
                ->schema([
                    Select::make('investigation_id')->label('Enquête')->relationship('investigation', 'title')->searchable()->preload()->required(),
                    TextInput::make('title')->label('Titre')->required()->maxLength(180),
                    ContentFields::markdown('body', 'Contenu'),
                ]),
            Section::make('Qui peut le lire ?')
                ->columnSpanFull()
                ->schema([
                    Toggle::make('is_public')->label('Public : affiché sur la page de l’enquête pour tout le monde'),
                    Select::make('characters')
                        ->label('Personnages qui le détiennent')
                        ->helperText('Leurs joueurs sont prévenus par une notification à l’enregistrement.')
                        ->relationship('characters', 'first_name', fn (Builder $query) => $query->where('status', CharacterStatus::Accepted->value)->orderBy('first_name'))
                        ->getOptionLabelFromRecordUsing(fn (Character $record): string => $record->fullName())
                        ->multiple()
                        ->searchable(['first_name', 'last_name'])
                        ->preload(),
                    Toggle::make('is_shareable')->label('Transmissible : un détenteur peut le confier à un autre personnage')->default(true),
                    TextInput::make('sort_order')->label('Ordre')->integer()->default(0),
                ]),
            ContentFields::imageSection('enquetes')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('investigation_id')
            ->columns([
                TextColumn::make('title')->label('Indice')->searchable()->limit(60),
                TextColumn::make('investigation.title')->label('Enquête')->limit(40),
                IconColumn::make('is_public')->label('Public')->boolean(),
                TextColumn::make('characters_count')->label('Détenteurs')->counts('characters'),
            ])
            ->filters([
                SelectFilter::make('investigation_id')->label('Enquête')->relationship('investigation', 'title'),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListClues::route('/'),
            'create' => CreateClue::route('/nouveau'),
            'edit' => EditClue::route('/{record}/modifier'),
        ];
    }
}
