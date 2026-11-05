<?php

namespace App\Filament\Resources\Scenes;

use App\Enums\SceneStatus;
use App\Enums\SceneVisibility;
use App\Filament\Resources\Scenes\Pages\ListScenes;
use App\Filament\Resources\Scenes\Pages\ViewScene;
use App\Filament\Resources\Scenes\RelationManagers\PostsRelationManager;
use App\Models\Scene;
use App\Models\SceneParticipant;
use App\Services\SceneService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\HtmlString;
use UnitEnum;

/**
 * Modération des scènes : lecture (scènes privées comprises, consultation journalisée),
 * versions des réponses modifiées, masquage d'une réponse, statut, suppression.
 */
class SceneResource extends Resource
{
    protected static ?string $model = Scene::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static string|UnitEnum|null $navigationGroup = 'Jeu';

    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'scène';

    protected static ?string $pluralModelLabel = 'scènes';

    protected static ?string $navigationLabel = 'Scènes';

    protected static ?string $slug = 'scenes';

    protected static ?string $recordTitleAttribute = 'title';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->with(['creator', 'location']);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('La scène')
                ->columnSpanFull()
                ->columns(['default' => 1, 'md' => 3])
                ->schema([
                    TextEntry::make('title')->label('Titre')->columnSpanFull(),
                    TextEntry::make('status')->label('Statut')->badge(),
                    TextEntry::make('visibility')->label('Visibilité')->badge(),
                    TextEntry::make('turn_mode')->label('Ordre de passage'),
                    TextEntry::make('rp_date')->label('Date RP')->placeholder('—'),
                    TextEntry::make('location.name')->label('Lieu')->placeholder('—'),
                    TextEntry::make('creator.pseudo')->label('Ouverte par')->placeholder('Ancien membre'),
                    TextEntry::make('posts_count')->label('Réponses'),
                    TextEntry::make('words_count')->label('Mots'),
                    TextEntry::make('last_post_at')->label('Dernière réponse')->dateTime('d/m/Y H:i')->placeholder('—'),
                    TextEntry::make('warnings')
                        ->label('Avertissements')
                        ->state(fn (Scene $record): string => implode(' · ', $record->warningLabels()))
                        ->placeholder('Aucun')
                        ->columnSpanFull(),
                    TextEntry::make('cast')
                        ->label('Participants')
                        ->columnSpanFull()
                        ->state(fn (Scene $record): HtmlString => static::castList($record)),
                ]),
        ]);
    }

    protected static function castList(Scene $record): HtmlString
    {
        $items = $record->participants()->with(['user', 'character'])->get()->map(
            fn (SceneParticipant $participant): string => '<li>'.e($participant->character?->fullName() ?? '—')
                .' · '.e($participant->user?->pseudo ?? '—')
                .' · '.e($participant->status->getLabel()).'</li>'
        );

        return new HtmlString('<ul>'.$items->implode('').'</ul>');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('last_post_at', 'desc')
            ->columns([
                TextColumn::make('id')->label('N°')->sortable(),
                TextColumn::make('title')->label('Titre')->searchable()->limit(50),
                TextColumn::make('creator.pseudo')->label('Ouverte par')->placeholder('—'),
                TextColumn::make('visibility')->label('Visibilité')->badge(),
                TextColumn::make('status')->label('Statut')->badge(),
                TextColumn::make('posts_count')->label('Réponses')->sortable(),
                TextColumn::make('last_post_at')->label('Dernière réponse')->dateTime('d/m/Y H:i')->placeholder('—')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Statut')->options(SceneStatus::class),
                SelectFilter::make('visibility')->label('Visibilité')->options(SceneVisibility::class),
                TrashedFilter::make()->label('Supprimées'),
            ])
            ->recordActions([
                ViewAction::make()->label('Lire'),
            ]);
    }

    /**
     * @return array<Action>
     */
    public static function headerActions(): array
    {
        return [
            Action::make('site')
                ->label('Voir sur le site')
                ->icon(Heroicon::OutlinedEye)
                ->visible(fn (Scene $record): bool => ! $record->trashed())
                ->url(fn (Scene $record): string => route('scenes.show', $record))
                ->openUrlInNewTab(),
            Action::make('status')
                ->label('Changer le statut')
                ->icon(Heroicon::OutlinedAdjustmentsHorizontal)
                ->visible(fn (Scene $record): bool => ! $record->trashed() && (auth()->user()?->can('manage', $record) ?? false))
                ->fillForm(fn (Scene $record): array => ['status' => $record->status->value])
                ->schema([
                    Select::make('status')->label('Statut')->options(SceneStatus::class)->required()->native(false),
                ])
                ->action(function (Scene $record, array $data): void {
                    abort_unless(auth()->user()?->can('manage', $record), 403);
                    app(SceneService::class)->changeStatus($record, SceneStatus::from($data['status'] instanceof SceneStatus ? $data['status']->value : $data['status']), auth()->user());
                    Notification::make()->title('Statut modifié')->success()->send();
                }),
            DeleteAction::make()
                ->label('Supprimer la scène')
                ->modalDescription('La scène disparaît du site pour tout le monde. Elle reste restaurable ici.'),
            RestoreAction::make(),
        ];
    }

    public static function getRelations(): array
    {
        return [
            PostsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListScenes::route('/'),
            'view' => ViewScene::route('/{record}'),
        ];
    }
}
