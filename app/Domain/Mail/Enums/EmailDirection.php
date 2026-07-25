<?php

declare(strict_types=1);

namespace App\Domain\Mail\Enums;

use Filament\Support\Contracts\HasLabel;

enum EmailDirection: string implements HasLabel
{
    case Inbound = 'inbound';
    case Outbound = 'outbound';

    public function getLabel(): string
    {
        return match ($this) {
            self::Inbound => 'In ingresso',
            self::Outbound => 'In uscita',
        };
    }
}
