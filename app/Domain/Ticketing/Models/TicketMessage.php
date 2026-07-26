<?php

declare(strict_types=1);

namespace App\Domain\Ticketing\Models;

use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Models\User;
use App\Domain\Mail\Models\EmailMessage;
use App\Domain\Ticketing\Enums\TicketMessageChannel;
use App\Domain\Ticketing\Enums\TicketMessageVisibility;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[Fillable([
    'ticket_id', 'author_id', 'author_email', 'channel', 'visibility', 'body_html', 'body_text',
    'email_message_id', 'is_legacy_import', 'posted_at',
])]
class TicketMessage extends Model implements HasMedia
{
    use HasUlids, InteractsWithMedia;

    /**
     * L'identificativo pubblico è `ulid` (§5.2), non la chiave primaria `id` (bigserial,
     * conservata per FK/ordinamento interno): sovrascrive la convenzione di default di
     * `HasUlids`, che assume come colonna unica la primary key.
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
            'channel' => TicketMessageChannel::class,
            'visibility' => TicketMessageVisibility::class,
            'is_legacy_import' => 'boolean',
            'posted_at' => 'datetime',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('attachments');
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
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * @return BelongsTo<EmailMessage, $this>
     */
    public function emailMessage(): BelongsTo
    {
        return $this->belongsTo(EmailMessage::class);
    }

    /**
     * Esclude i messaggi `visibility = internal` per chi non ha
     * `ticket-message.view.internal` (§9.5): uno scope sulla relazione, da incatenare
     * a `$ticket->messages()` in qualunque query futura, non un filtro applicato solo
     * in vista — un cliente non può raggiungere un messaggio interno nemmeno con un
     * accesso diretto by-id.
     *
     * @param  Builder<TicketMessage>  $query
     * @return Builder<TicketMessage>
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->can(Permission::TicketMessageViewInternal)) {
            return $query;
        }

        return $query->where('visibility', TicketMessageVisibility::Public);
    }
}
