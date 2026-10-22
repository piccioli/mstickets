<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Support;

/**
 * Stato patrimoniale dell'ultimo esercizio che ne ha almeno un valore (scelto indipendentemente dal conto
 * economico: i due esercizi possono differire).
 */
final readonly class CaiBalanceSheetSummary
{
    public function __construct(
        public int $year,
        public ?float $netEquity,
        public ?float $totalAssets,
    ) {}
}
