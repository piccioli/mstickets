<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Enums;

use Filament\Support\Contracts\HasLabel;

enum ActivityReportOwnerKind: string implements HasLabel
{
    case User = 'user';
    case Organization = 'organization';

    public function getLabel(): string
    {
        return match ($this) {
            self::User => 'Utente',
            self::Organization => 'Organizzazione',
        };
    }
}
