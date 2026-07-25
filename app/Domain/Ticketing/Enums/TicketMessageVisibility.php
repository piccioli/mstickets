<?php

declare(strict_types=1);

namespace App\Domain\Ticketing\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * In questa release l'UI espone solo `Public` (§5.2): `Internal` è già modellato come punto
 * di estensione (§15.2) ma non ha ancora una schermata dedicata.
 */
enum TicketMessageVisibility: string implements HasColor, HasLabel
{
    case Public = 'public';
    case Internal = 'internal';

    public function getLabel(): string
    {
        return match ($this) {
            self::Public => 'Pubblico',
            self::Internal => 'Interno',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Public => 'gray',
            self::Internal => 'warning',
        };
    }
}
