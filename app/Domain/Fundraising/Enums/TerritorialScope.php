<?php

declare(strict_types=1);

namespace App\Domain\Fundraising\Enums;

use Filament\Support\Contracts\HasLabel;

enum TerritorialScope: string implements HasLabel
{
    case Cooperation = 'cooperation';
    case European = 'european';
    case National = 'national';
    case Regional = 'regional';
    case Territorial = 'territorial';
    case Municipalities = 'municipalities';

    public function getLabel(): string
    {
        return match ($this) {
            self::Cooperation => 'Cooperazione',
            self::European => 'Europeo',
            self::National => 'Nazionale',
            self::Regional => 'Regionale',
            self::Territorial => 'Territoriale',
            self::Municipalities => 'Comunale',
        };
    }
}
