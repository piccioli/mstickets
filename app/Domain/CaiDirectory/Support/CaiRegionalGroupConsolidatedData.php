<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Support;

use App\Domain\CaiDirectory\Enums\CaiSectionStatementStatus;
use App\Domain\CaiDirectory\Queries\CaiRegionalGroupConsolidatedStatements;

/**
 * Bilancio consolidato delle Sezioni di un Gruppo Regionale per UN esercizio (Fase 10.3), calcolato da
 * {@see CaiRegionalGroupConsolidatedStatements}. `rows` contiene tutte le `sectionsCount` Sezioni del GR (ordinate
 * per nome). I totali sommano solo i valori non nulli delle Sezioni in "Dati presenti" (`null`, mai 0, se nessuno);
 * `incomeStatementSectionsCount`/`balanceSheetSectionsCount` sono le Sezioni sommate da ciascuna card,
 * `*FlaggedCount` i valori "da verificare" che ciascuna card include.
 */
final readonly class CaiRegionalGroupConsolidatedData
{
    /**
     * @param  list<int>  $availableYears  in ordine crescente
     * @param  list<CaiConsolidatedSectionRow>  $rows
     */
    public function __construct(
        public ?string $codiceCai,
        public int $year,
        public array $availableYears,
        public int $sectionsCount,
        public array $rows,
        public ?float $revenues,
        public ?float $expenses,
        public ?float $netResult,
        public ?float $netEquity,
        public ?float $totalAssets,
        public int $incomeStatementSectionsCount,
        public int $balanceSheetSectionsCount,
        public int $incomeStatementFlaggedCount,
        public int $balanceSheetFlaggedCount,
        public int $dataCount,
        public int $pdfOnlyCount,
        public int $noneCount,
    ) {}

    public function countFor(CaiSectionStatementStatus $status): int
    {
        return match ($status) {
            CaiSectionStatementStatus::Data => $this->dataCount,
            CaiSectionStatementStatus::PdfOnly => $this->pdfOnlyCount,
            CaiSectionStatementStatus::None => $this->noneCount,
        };
    }
}
