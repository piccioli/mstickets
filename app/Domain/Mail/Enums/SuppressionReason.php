<?php

declare(strict_types=1);

namespace App\Domain\Mail\Enums;

use Filament\Support\Contracts\HasLabel;

enum SuppressionReason: string implements HasLabel
{
    case HardBounce = 'hard_bounce';
    case SoftBounce = 'soft_bounce';
    case Complaint = 'complaint';
    case Manual = 'manual';
    case LoopProtection = 'loop_protection';

    public function getLabel(): string
    {
        return match ($this) {
            self::HardBounce => 'Hard bounce',
            self::SoftBounce => 'Soft bounce',
            self::Complaint => 'Reclamo',
            self::Manual => 'Manuale',
            self::LoopProtection => 'Protezione anti-loop',
        };
    }
}
