<?php

declare(strict_types=1);

namespace App\Domain\Ticketing\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum TicketType: string implements HasColor, HasIcon, HasLabel
{
    case Bug = 'bug';
    case Feature = 'feature';
    case Helpdesk = 'helpdesk';
    case Scrum = 'scrum';

    public function getLabel(): string
    {
        return match ($this) {
            self::Bug => 'Bug',
            self::Feature => 'Feature',
            self::Helpdesk => 'Helpdesk',
            self::Scrum => 'Scrum',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Bug => 'danger',
            self::Feature => 'info',
            self::Helpdesk => 'warning',
            self::Scrum => 'gray',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Bug => 'heroicon-o-bug-ant',
            self::Feature => 'heroicon-o-star',
            self::Helpdesk => 'heroicon-o-lifebuoy',
            self::Scrum => 'heroicon-o-arrow-path',
        };
    }
}
