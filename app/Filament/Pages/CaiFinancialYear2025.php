<?php

declare(strict_types=1);

namespace App\Filament\Pages;

class CaiFinancialYear2025 extends CaiFinancialYearPage
{
    protected static ?int $navigationSort = 22;

    public static function year(): int
    {
        return 2025;
    }
}
