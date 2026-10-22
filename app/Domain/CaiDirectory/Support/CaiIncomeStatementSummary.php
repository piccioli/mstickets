<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Support;

/**
 * Conto economico dell'ultimo esercizio che ne ha almeno un valore. Il risultato netto è quello letto dal
 * bilancio, mai ricalcolato da ricavi e costi.
 */
final readonly class CaiIncomeStatementSummary
{
    public function __construct(
        public int $year,
        public ?float $revenues,
        public ?float $expenses,
        public ?float $netResult,
    ) {}
}
