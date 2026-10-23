<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Support;

use App\Domain\CaiDirectory\Queries\CaiSectionDashboardMetrics;

/**
 * Metriche della Dashboard V2 di una Sezione o Sottosezione (Fase 10.1), calcolate da
 * {@see CaiSectionDashboardMetrics}. Ogni dato mancante è `null`, mai 0: la vista lo mostra come "Non disponibile".
 * `huts` è sempre valorizzato per una Sezione (anche 0, dato noto) e `null` per una Sottosezione (10.4);
 * `trailsKm` e `sicaiStages` restano `null` finché non esiste una sorgente dati (feature 10.6/10.8).
 * `thirdSectorRegistered` è `null` solo per una Sottosezione (nessun dato RUNTS proprio): la pillola non compare.
 */
final readonly class CaiSectionDashboardData
{
    public function __construct(
        public string $name,
        public string $codiceCai,
        public ?string $region,
        public ?int $members,
        public ?CaiIncomeStatementSummary $incomeStatement,
        public ?CaiBalanceSheetSummary $balanceSheet,
        public ?bool $thirdSectorRegistered = null,
        public ?int $huts = null,
        public ?float $trailsKm = null,
        public ?int $sicaiStages = null,
    ) {}
}
