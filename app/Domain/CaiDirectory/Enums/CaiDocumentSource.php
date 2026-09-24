<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Come un `CaiDocument`/`CaiFinancialStatement` è arrivato in anagrafica. `Veryfico` è un valore
 * riservato per una futura integrazione via API con quel portale esterno — nessuna integrazione reale
 * oggi, nessun percorso applicativo lo assegna ancora.
 */
enum CaiDocumentSource: string implements HasColor, HasLabel
{
    case Runts = 'runts';
    case Manual = 'manual';
    case Veryfico = 'veryfico';

    public function getLabel(): string
    {
        return match ($this) {
            self::Runts => 'RUNTS',
            self::Manual => 'Caricamento manuale',
            self::Veryfico => 'Veryfico',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Runts => 'success',
            self::Manual => 'gray',
            self::Veryfico => 'info',
        };
    }
}
