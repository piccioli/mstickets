<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Domain\Identity\Enums\CustomerType;

/**
 * Pagine "in arrivo" della Dashboard V2 del Gruppo Regionale (US-1013): solo un customer con
 * `customer_type = GruppoRegionale`.
 */
abstract class RegionalGroupComingSoonPage extends CustomerComingSoonPage
{
    protected static function allowedCustomerType(): CustomerType
    {
        return CustomerType::GruppoRegionale;
    }
}
