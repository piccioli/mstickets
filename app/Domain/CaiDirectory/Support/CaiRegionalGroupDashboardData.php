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
 * `huts` (10.5) è sempre valorizzato se la regione ha Sezioni (anche 0, dato noto), con `hutsSectionsCount` = Sezioni
 * con almeno un rifugio (quelli intestati al GR non contano come Sezione). `trailsKm` (10.7) = km nel territorio
 * regionale, sempre valorizzato se la regione ha Sezioni (0 senza dati). `sicaiStages` (10.9) = tappe SICAI delle lettere
 * della regione (principali e varianti, come il riepilogo della pagina), `sicaiStagesKm` = km delle sole principali.
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
        public ?int $hutsSectionsCount = null,
        public ?float $trailsKm = null,
        public ?int $sicaiStages = null,
        public ?float $sicaiStagesKm = null,
    ) {}
}
