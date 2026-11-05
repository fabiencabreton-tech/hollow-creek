<?php

namespace App\Filament\Resources\Scenes\RelationManagers;

use App\Models\Post;
use App\Models\PostRevision;
use App\Models\Scene;
use App\Support\ActivityLogger;
use App\Support\SceneText;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

/**
 * Réponses d'une scène : lecture, versions précédentes, masquage.
 * L'équipe ne réécrit jamais le texte d'un joueur.
 */
class PostsRelationManager extends RelationManager
{
    protected static string $relationship = 'posts';

    protected static ?string $title = 'Réponses';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withoutGlobalScopes([SoftDeletingScope::class])->with('author')->withCount('revisions'))
            ->defaultSort('id')
            ->columns([
                TextColumn::make('id')->label('N°'),
                TextColumn::make('character_name')->label('Personnage'),
                TextColumn::make('author.pseudo')->label('Joueur')->placeholder('Ancien membre'),
                TextColumn::make('body')
                    ->label('Début')
                    ->formatStateUsing(fn (string $state): string => Str::limit(trim(strip_tags($state)), 90)),
                TextColumn::make('word_count')->label('Mots'),
                TextColumn::make('created_at')->label('Publiée')->dateTime('d/m/Y H:i'),
                TextColumn::make('revisions_count')->label('Versions')->placeholder('0'),
                TextColumn::make('deleted_at')->label('Masquée le')->dateTime('d/m/Y H:i')->placeholder('—'),
            ])
            ->filters([
                TrashedFilter::make()->label('Masquées'),
            ])
            ->recordActions([
                Action::make('read')
                    ->label('Lire')
                    ->icon(Heroicon::OutlinedEye)
                    ->modalHeading(fn (Post $record): string => $record->character_name)
                    ->modalContent(fn (Post $record): HtmlString => static::readable($record))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Fermer'),
                DeleteAction::make()
                    ->label('Masquer')
                    ->modalHeading('Masquer cette réponse')
                    ->modalDescription('La réponse disparaît de la scène pour tout le monde. Elle reste restaurable ici.')
                    ->after(function (Post $record): void {
                        static::adjustCounters($record, -1);
                        ActivityLogger::record('scene.reponse-masquee', $record, $record->character_name, causer: auth()->user());
                    }),
                RestoreAction::make()
                    ->after(function (Post $record): void {
                        static::adjustCounters($record, 1);
                        ActivityLogger::record('scene.reponse-restauree', $record, $record->character_name, causer: auth()->user());
                    }),
            ]);
    }

    protected static function readable(Post $record): HtmlString
    {
        $html = '<div class="prose max-w-none">'.SceneText::render($record->body)->toHtml().'</div>';

        $revisions = $record->revisions()->with('editor')->get();

        if ($revisions->isNotEmpty()) {
            $html .= '<h3 style="margin-top:1.5rem;font-weight:600">Versions précédentes</h3>';

            foreach ($revisions as $revision) {
                /** @var PostRevision $revision */
                $html .= '<details style="margin-top:.75rem"><summary>Remplacée le '.e($revision->created_at?->format('d/m/Y H:i') ?? '').'</summary>'
                    .'<div class="prose max-w-none">'.SceneText::render($revision->body)->toHtml().'</div></details>';
            }
        }

        return new HtmlString($html);
    }

    protected static function adjustCounters(Post $record, int $direction): void
    {
        $scene = Scene::withTrashed()->find($record->scene_id);

        if ($scene === null) {
            return;
        }

        $scene->forceFill([
            'posts_count' => max(0, $scene->posts_count + $direction),
            'words_count' => max(0, $scene->words_count + $direction * $record->word_count),
        ])->save();
    }
}
