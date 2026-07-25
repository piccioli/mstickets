<?php

declare(strict_types=1);

namespace App\Domain\Documentation\Enums;

use Filament\Support\Contracts\HasLabel;

enum DocumentationCategory: string implements HasLabel
{
    case Internal = 'internal';
    case Customer = 'customer';

    public function getLabel(): string
    {
        return match ($this) {
            self::Internal => 'Interna',
            self::Customer => 'Cliente',
        };
    }
}
