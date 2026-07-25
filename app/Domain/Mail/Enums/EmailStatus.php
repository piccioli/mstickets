<?php

declare(strict_types=1);

namespace App\Domain\Mail\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Due percorsi distinti condividono questo unico enum (§7.3.2): inbound
 * `Received → Parsed → Classified → Applied | Quarantined | Discarded | Failed`;
 * outbound `Queued → Sent | Failed | Bounced | Suppressed`. `Failed` è condiviso da entrambi.
 */
enum EmailStatus: string implements HasColor, HasLabel
{
    case Received = 'received';
    case Parsed = 'parsed';
    case Classified = 'classified';
    case Applied = 'applied';
    case Quarantined = 'quarantined';
    case Discarded = 'discarded';
    case Failed = 'failed';
    case Queued = 'queued';
    case Sent = 'sent';
    case Bounced = 'bounced';
    case Suppressed = 'suppressed';

    public function getLabel(): string
    {
        return match ($this) {
            self::Received => 'Ricevuta',
            self::Parsed => 'Parsificata',
            self::Classified => 'Classificata',
            self::Applied => 'Applicata',
            self::Quarantined => 'In quarantena',
            self::Discarded => 'Scartata',
            self::Failed => 'Fallita',
            self::Queued => 'In coda',
            self::Sent => 'Inviata',
            self::Bounced => 'Respinta',
            self::Suppressed => 'Soppressa',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Received, self::Parsed, self::Classified, self::Queued => 'info',
            self::Applied, self::Sent => 'success',
            self::Quarantined, self::Bounced, self::Suppressed => 'warning',
            self::Discarded, self::Failed => 'danger',
        };
    }
}
