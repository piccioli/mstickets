<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Esito dell'estrazione finanziaria per il SINGOLO `CaiDocument` (non per il record aggregato
 * `cai_financial_statements`, che è un merge campo-per-campo fra più documenti dello stesso anno).
 * `null` sulla colonna `cai_documents.financial_analysis_status` significa "mai analizzato" (documento
 * non di tipo bilancio_esercizio, o job non ancora eseguito) — distinto da entrambi i case qui sotto.
 */
enum CaiDocumentAnalysisStatus: string implements HasColor, HasLabel
{
    case Extracted = 'extracted';
    case NoDataExtracted = 'no_data_extracted';

    public function getLabel(): string
    {
        return match ($this) {
            self::Extracted => 'Dati estratti',
            self::NoDataExtracted => 'Nessun dato estratto',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Extracted => 'success',
            self::NoDataExtracted => 'warning',
        };
    }
}
