<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Support\Markdown;
use Illuminate\Support\HtmlString;

#[Fillable(['slug', 'title', 'body', 'is_published', 'updated_by'])]
class Page extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Le texte est rédigé en Markdown. Tout HTML saisi est échappé et les liens
     * dangereux (javascript:, data:) sont neutralisés : rien ne peut s'exécuter.
     */
    public function renderedBody(): HtmlString
    {
        return Markdown::render($this->body);
    }
}
