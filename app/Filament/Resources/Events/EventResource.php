<?php

namespace App\Filament\Resources\Events;

use App\Enums\EventStatus;
use App\Filament\Resources\Events\Pages\CreateEvent;
use App\Filament\Resources\Events\Pages\EditEvent;
use App\Filament\Resources\Events\Pages\ListEvents;
use App\Filament\Support\ContentFields;
use App\Models\Event;
use App\Support\Settings;
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
use Filament\Tables\Table;
use UnitEnum;

/**
 * Événements créés par l'équipe. La première publication prévient tous les joueurs.
 */
class EventResource extends Resource
{
    protected static ?string $model = Event::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|UnitEnum|null $navigationGroup = 'Jeu';

    protected static ?int $navigationSort = 20;

    protected static ?string $modelLabel = 'événement';

    protected static ?string $pluralModelLabel = 'événements';

    protected static ?string $navigationLabel = 'Événements';

    protected static ?string $slug = 'evenements';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('L’événement')
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    TextInput::make('title')->label('Titre')->required()->maxLength(150)->columnSpanFull(),
                    TextInput::make('rp_date')->label('Date RP')->default(fn (): string => app(Settings::class)->rpDate())->maxLength(80),
                    Select::make('location_id')->label('Lieu')->relationship('location', 'name')->searchable()->preload(),
                    Select::make('status')->label('Statut')->options(EventStatus::class)->default(EventStatus::Announced)->required()->native(false),
                    Toggle::make('registrations_open')->label('Inscriptions ouvertes')->default(true),
                    Textarea::make('summary')->label('Résumé')->rows(3)->maxLength(500)->columnSpanFull(),
                    ContentFields::markdown('body', 'Présentation')->columnSpanFull(),
                ]),
            ContentFields::imageSection('evenements')->columnSpanFull(),
            Section::make('Publication')
                ->columnSpanFull()
                ->description('À la première publication, chaque joueur reçoit une notification (et un e-mail selon ses préférences).')
                ->schema([
                    Toggle::make('is_published')->label('Publié sur le site'),
                    TextInput::make('slug')
                        ->label('Adresse')
                        ->helperText('Laissée vide, elle est créée à partir du titre.')
                        ->maxLength(70)
                        ->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')
                        ->unique(ignoreRecord: true),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('title')->label('Titre')->searchable(),
                TextColumn::make('rp_date')->label('Date RP')->placeholder('—'),
                TextColumn::make('status')->label('Statut')->badge(),
                TextColumn::make('participants_count')->label('Inscrits')->counts('participants'),
                IconColumn::make('is_published')->label('Publié')->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEvents::route('/'),
            'create' => CreateEvent::route('/nouveau'),
            'edit' => EditEvent::route('/{record}/modifier'),
        ];
    }
}
