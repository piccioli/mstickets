<?php

declare(strict_types=1);

namespace App\Domain\Fundraising\Enums;

use Filament\Support\Contracts\HasLabel;

enum FundraisingProjectStatus: string implements HasLabel
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Completed = 'completed';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draft => 'Bozza',
            self::Submitted => 'Presentato',
            self::Approved => 'Approvato',
            self::Rejected => 'Respinto',
            self::Completed => 'Concluso',
        };
    }
}
