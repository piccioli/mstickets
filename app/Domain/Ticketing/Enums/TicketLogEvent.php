<?php

declare(strict_types=1);

namespace App\Domain\Ticketing\Enums;

use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum TicketLogEvent: string implements HasIcon, HasLabel
{
    case Created = 'created';
    case StatusChanged = 'status_changed';
    case Assigned = 'assigned';
    case Updated = 'updated';
    case MessagePosted = 'message_posted';
    case AttachmentAdded = 'attachment_added';
    case AttachmentRemoved = 'attachment_removed';
    case System = 'system';

    public function getLabel(): string
    {
        return match ($this) {
            self::Created => 'Creato',
            self::StatusChanged => 'Cambio di stato',
            self::Assigned => 'Assegnato',
            self::Updated => 'Aggiornato',
            self::MessagePosted => 'Messaggio pubblicato',
            self::AttachmentAdded => 'Allegato aggiunto',
            self::AttachmentRemoved => 'Allegato rimosso',
            self::System => 'Sistema',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Created => 'heroicon-o-plus-circle',
            self::StatusChanged => 'heroicon-o-arrow-path-rounded-square',
            self::Assigned => 'heroicon-o-user-plus',
            self::Updated => 'heroicon-o-pencil-square',
            self::MessagePosted => 'heroicon-o-chat-bubble-left-right',
            self::AttachmentAdded => 'heroicon-o-paper-clip',
            self::AttachmentRemoved => 'heroicon-o-x-mark',
            self::System => 'heroicon-o-cog-6-tooth',
        };
    }
}
