<?php

declare(strict_types=1);

namespace App\Domain\Mail\Models;

use App\Domain\Identity\Models\User;
use App\Domain\Mail\Enums\EmailMessageLogEvent;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['email_message_id', 'user_id', 'action', 'notes', 'occurred_at'])]
class EmailMessageLog extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'action' => EmailMessageLogEvent::class,
            'occurred_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<EmailMessage, $this>
     */
    public function emailMessage(): BelongsTo
    {
        return $this->belongsTo(EmailMessage::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
