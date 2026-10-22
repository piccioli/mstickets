<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Support;

/**
 * Stato patrimoniale consolidato delle Sezioni di un Gruppo Regionale su UN solo esercizio, scelto
 * indipendentemente dal conto economico (Fase 10.2). `sectionsCount` = Sezioni con dati di stato patrimoniale in
 * `year`, `totalSections` = tutte le Sezioni del GR ("Somma di N Sezioni su M").
 */
final readonly class CaiConsolidatedBalanceSheetSummary
{
    public function __construct(
        public int $year,
        public int $sectionsCount,
        public int $totalSections,
        public ?float $netEquity,
        public ?float $totalAssets,
    ) {}
}
