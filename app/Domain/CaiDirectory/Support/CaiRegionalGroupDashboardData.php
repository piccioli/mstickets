<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Support;

use App\Domain\CaiDirectory\Queries\CaiRegionalGroupDashboardMetrics;

/**
 * Metriche della Dashboard V2 di un Gruppo Regionale (Fase 10.2), calcolate da
 * {@see CaiRegionalGroupDashboardMetrics}. Ogni dato mancante è `null`, mai 0. Se la regione non ha Sezioni
 * tutto è `null`, anche `codiceCai` e `sectionsCount`.
 *
 * Il bilancio proprio del GR (`incomeStatement`/`balanceSheet`) non si raccoglie ancora: sempre `null`.
 * `huts`, `trailsKm` e `sicaiStages` restano `null` finché non esiste una sorgente dati (10.5/10.7/10.9).
 */
final readonly class CaiRegionalGroupDashboardData
{
    public function __construct(
        public ?string $codiceCai,
        public ?int $sectionsCount,
        public ?int $members,
        public ?int $membersSectionsCount,
        public ?CaiConsolidatedIncomeStatementSummary $consolidatedIncomeStatement,
        public ?CaiConsolidatedBalanceSheetSummary $consolidatedBalanceSheet,
        public ?CaiIncomeStatementSummary $incomeStatement = null,
        public ?CaiBalanceSheetSummary $balanceSheet = null,
        public ?int $huts = null,
        public ?float $trailsKm = null,
        public ?int $sicaiStages = null,
    ) {}
}
