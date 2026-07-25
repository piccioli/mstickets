<?php

declare(strict_types=1);

namespace App\Domain\Ticketing\Enums;

use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum TicketMessageChannel: string implements HasIcon, HasLabel
{
    case Web = 'web';
    case Email = 'email';
    case System = 'system';

    public function getLabel(): string
    {
        return match ($this) {
            self::Web => 'Web',
            self::Email => 'Email',
            self::System => 'Sistema',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Web => 'heroicon-o-globe-alt',
            self::Email => 'heroicon-o-envelope',
            self::System => 'heroicon-o-cog-6-tooth',
        };
    }
}
