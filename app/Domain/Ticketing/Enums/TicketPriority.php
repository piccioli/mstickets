<?php

declare(strict_types=1);

namespace App\Domain\Ticketing\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum TicketPriority: string implements HasColor, HasIcon, HasLabel
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';

    public function getLabel(): string
    {
        return match ($this) {
            self::Low => 'Bassa',
            self::Medium => 'Media',
            self::High => 'Alta',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Low => 'gray',
            self::Medium => 'warning',
            self::High => 'danger',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Low => 'heroicon-o-arrow-down',
            self::Medium => 'heroicon-o-minus',
            self::High => 'heroicon-o-arrow-up',
        };
    }
}
