<?php

declare(strict_types=1);

namespace App\Domain\Mail\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

/**
 * Azioni amministrative tracciate su un'email (§7.3.8/§7.7, US-322): ogni
 * azione dell'amministrazione (riprocessa, assegna mittente, crea mittente,
 * collega a ticket, scarta, reinvia) scrive una riga in `email_message_logs`
 * con l'attore e il momento in cui è stata eseguita, mai una modifica silenziosa
 * dello stato del messaggio.
 */
enum EmailMessageLogEvent: string implements HasColor, HasIcon, HasLabel
{
    case Reprocessed = 'reprocessed';
    case SenderAssigned = 'sender_assigned';
    case SenderCreated = 'sender_created';
    case LinkedToTicket = 'linked_to_ticket';
    case Discarded = 'discarded';
    case Resent = 'resent';
    case ResendBlocked = 'resend_blocked';

    public function getLabel(): string
    {
        return match ($this) {
            self::Reprocessed => 'Riprocessato',
            self::SenderAssigned => 'Mittente associato',
            self::SenderCreated => 'Mittente creato',
            self::LinkedToTicket => 'Collegato a ticket',
            self::Discarded => 'Scartato manualmente',
            self::Resent => 'Reinviato',
            self::ResendBlocked => 'Reinvio bloccato',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Reprocessed => 'heroicon-o-arrow-path',
            self::SenderAssigned => 'heroicon-o-user',
            self::SenderCreated => 'heroicon-o-user-plus',
            self::LinkedToTicket => 'heroicon-o-link',
            self::Discarded => 'heroicon-o-trash',
            self::Resent => 'heroicon-o-paper-airplane',
            self::ResendBlocked => 'heroicon-o-no-symbol',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Reprocessed, self::SenderAssigned, self::SenderCreated, self::LinkedToTicket, self::Resent => 'success',
            self::Discarded, self::ResendBlocked => 'danger',
        };
    }
}
