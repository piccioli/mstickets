<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Support;

/**
 * Conto economico consolidato delle Sezioni di un Gruppo Regionale su UN solo esercizio (Fase 10.2): ogni
 * totale è la somma dei soli valori non nulli, `null` (mai 0) se nessuna Sezione ne ha uno. `sectionsCount` =
 * Sezioni con dati di conto economico in `year`.
 */
final readonly class CaiConsolidatedIncomeStatementSummary
{
    public function __construct(
        public int $year,
        public int $sectionsCount,
        public ?float $revenues,
        public ?float $expenses,
        public ?float $netResult,
    ) {}
}
