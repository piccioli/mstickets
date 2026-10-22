<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Domain\Identity\Enums\CustomerType;

/**
 * Pagine "in arrivo" della Dashboard V2 della Sezione (US-1003): solo un customer con `customer_type = Sezione`.
 */
abstract class SectionComingSoonPage extends CustomerComingSoonPage
{
    protected static function allowedCustomerType(): CustomerType
    {
        return CustomerType::Sezione;
    }
}
