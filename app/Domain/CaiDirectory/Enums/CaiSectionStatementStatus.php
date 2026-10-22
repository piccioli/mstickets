<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Stato del bilancio di una Sezione per un esercizio nel consolidato del Gruppo Regionale (Fase 10.3):
 * `Data` se il bilancio dell'anno ha cifre di conto economico o stato patrimoniale, altrimenti `PdfOnly` se la
 * Sezione ha un file di bilancio dell'anno, altrimenti `None`.
 */
enum CaiSectionStatementStatus: string implements HasLabel
{
    case Data = 'dati';
    case PdfOnly = 'solo-pdf';
    case None = 'nessuno';

    public function getLabel(): string
    {
        return match ($this) {
            self::Data => 'Dati presenti',
            self::PdfOnly => 'Solo PDF',
            self::None => 'Nessun bilancio',
        };
    }
}
