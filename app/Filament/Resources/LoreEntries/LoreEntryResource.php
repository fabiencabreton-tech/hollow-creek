<?php

namespace App\Filament\Resources\LoreEntries;

use App\Filament\Resources\LoreEntries\Pages\CreateLoreEntry;
use App\Filament\Resources\LoreEntries\Pages\EditLoreEntry;
use App\Filament\Resources\LoreEntries\Pages\ListLoreEntries;
use App\Filament\Support\ContentFields;
use App\Models\LoreEntry;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Archives secrètes : la vérité sur Hollow Creek, réservée aux administrateurs.
 * Rien de ce qui est écrit ici n'est affiché sur le site public.
 */
class LoreEntryResource extends Resource
{
    protected static ?string $model = LoreEntry::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLockClosed;

    protected static string|UnitEnum|null $navigationGroup = 'Univers';

    protected static ?int $navigationSort = 90;

    protected static ?string $modelLabel = 'archive secrète';

    protected static ?string $pluralModelLabel = 'archives secrètes';

    protected static ?string $navigationLabel = 'Archives secrètes';

    protected static ?string $slug = 'archives-secretes';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Archive')
                ->columnSpanFull()
                ->description('Réservé aux administrateurs. Jamais affiché sur le site, jamais visible des modérateurs.')
                ->schema([
                    TextInput::make('title')->label('Titre')->required()->maxLength(150),
                    TextInput::make('category')->label('Catégorie')->helperText('Par exemple « Intrigue », « Personnages non joueurs », « Chronologie ».')->maxLength(60),
                    ContentFields::markdown('body', 'Contenu'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('title')->label('Titre')->searchable(),
                TextColumn::make('category')->label('Catégorie')->placeholder('—'),
                TextColumn::make('editor.pseudo')->label('Modifiée par')->placeholder('—'),
                TextColumn::make('updated_at')->label('Modifiée le')->dateTime('d/m/Y H:i'),
            ])
            ->filters([
                SelectFilter::make('category')->label('Catégorie')->options(fn (): array => LoreEntry::query()->whereNotNull('category')->distinct()->orderBy('category')->pluck('category', 'category')->all()),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLoreEntries::route('/'),
            'create' => CreateLoreEntry::route('/nouvelle'),
            'edit' => EditLoreEntry::route('/{record}/modifier'),
        ];
    }
}
