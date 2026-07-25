<?php

declare(strict_types=1);

namespace App\Domain\Mail\Models;

use App\Domain\Mail\Enums\EmailAttachmentStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'email_message_id', 'filename', 'mime_type', 'size_bytes', 'disk', 'path', 'media_id',
    'status', 'rejection_reason',
])]
class EmailAttachment extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
            'status' => EmailAttachmentStatus::class,
        ];
    }

    /**
     * @return BelongsTo<EmailMessage, $this>
     */
    public function emailMessage(): BelongsTo
    {
        return $this->belongsTo(EmailMessage::class);
    }
}
