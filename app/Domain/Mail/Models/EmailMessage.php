<?php

declare(strict_types=1);

namespace App\Domain\Mail\Models;

use App\Domain\Identity\Models\User;
use App\Domain\Mail\Enums\EmailDirection;
use App\Domain\Mail\Enums\EmailStatus;
use App\Domain\Ticketing\Models\Ticket;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'direction', 'message_id', 'in_reply_to', 'references', 'thread_id', 'ticket_id', 'user_id',
    'from_email', 'from_name', 'to', 'cc', 'bcc', 'reply_to', 'subject', 'body_text', 'body_html',
    'raw_path', 'status', 'failure_reason', 'attempts', 'mailable_class', 'provider_message_id',
    'imap_uid', 'imap_folder', 'content_hash', 'received_at', 'sent_at',
])]
class EmailMessage extends Model
{
    use HasUlids;

    /**
     * L'identificativo pubblico è `ulid` (§5.2), non la chiave primaria `id`: sovrascrive la
     * convenzione di default di `HasUlids`, che assume come colonna unica la primary key.
     *
     * @return array<int, string>
     */
    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'direction' => EmailDirection::class,
            'status' => EmailStatus::class,
            'to' => 'array',
            'cc' => 'array',
            'bcc' => 'array',
            'attempts' => 'integer',
            'imap_uid' => 'integer',
            'received_at' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<EmailThread, $this>
     */
    public function thread(): BelongsTo
    {
        return $this->belongsTo(EmailThread::class, 'thread_id');
    }

    /**
     * @return BelongsTo<Ticket, $this>
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<EmailAttachment, $this>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(EmailAttachment::class);
    }

    /**
     * @return HasMany<EmailMessageLog, $this>
     */
    public function logs(): HasMany
    {
        return $this->hasMany(EmailMessageLog::class);
    }
}
