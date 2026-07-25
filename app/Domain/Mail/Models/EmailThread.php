<?php

declare(strict_types=1);

namespace App\Domain\Mail\Models;

use App\Domain\Ticketing\Models\Ticket;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['ticket_id', 'subject_normalized', 'participants', 'last_message_at'])]
class EmailThread extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'participants' => 'array',
            'last_message_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Ticket, $this>
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * @return HasMany<EmailMessage, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(EmailMessage::class, 'thread_id');
    }
}
