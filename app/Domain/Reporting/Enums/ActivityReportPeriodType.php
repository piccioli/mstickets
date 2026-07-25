<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Enums;

use Filament\Support\Contracts\HasLabel;

enum ActivityReportPeriodType: string implements HasLabel
{
    case Monthly = 'monthly';
    case Annual = 'annual';

    public function getLabel(): string
    {
        return match ($this) {
            self::Monthly => 'Mensile',
            self::Annual => 'Annuale',
        };
    }
}
