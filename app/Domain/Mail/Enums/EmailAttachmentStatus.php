<?php

declare(strict_types=1);

namespace App\Domain\Mail\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum EmailAttachmentStatus: string implements HasColor, HasLabel
{
    case Stored = 'stored';
    case RejectedMime = 'rejected_mime';
    case RejectedSize = 'rejected_size';
    case Failed = 'failed';

    public function getLabel(): string
    {
        return match ($this) {
            self::Stored => 'Salvato',
            self::RejectedMime => 'Rifiutato (tipo file)',
            self::RejectedSize => 'Rifiutato (dimensione)',
            self::Failed => 'Fallito',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Stored => 'success',
            self::RejectedMime, self::RejectedSize => 'warning',
            self::Failed => 'danger',
        };
    }
}
