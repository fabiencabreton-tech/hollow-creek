<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\HtmlString;

class ContactMessage extends Model
{
    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_internal' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<ContactTicket, $this>
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(ContactTicket::class, 'contact_ticket_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function html(): HtmlString
    {
        return new HtmlString(nl2br(e($this->body), false));
    }
}
