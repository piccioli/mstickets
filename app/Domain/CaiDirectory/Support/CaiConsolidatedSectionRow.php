<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Support;

use App\Domain\CaiDirectory\Enums\CaiSectionStatementStatus;
use App\Domain\CaiDirectory\Enums\CaiStatementSource;

/**
 * Una Sezione del GR nel bilancio consolidato di un esercizio (Fase 10.3). I valori sono `null` se mancanti (e
 * sempre `null` fuori da "Dati presenti"); `documentId` è il PDF di bilancio dell'anno, se c'è. `flagged` elenca le
 * voci "da verificare" (`revenues`, `expenses`, `netEquity`, `totalAssets`; mai `netResult`).
 */
final readonly class CaiConsolidatedSectionRow
{
    public const REVENUES = 'revenues';

    public const EXPENSES = 'expenses';

    public const NET_EQUITY = 'netEquity';

    public const TOTAL_ASSETS = 'totalAssets';

    /**
     * @param  list<string>  $flagged
     */
    public function __construct(
        public string $name,
        public string $codiceCai,
        public CaiSectionStatementStatus $status,
        public ?CaiStatementSource $source,
        public ?float $revenues,
        public ?float $expenses,
        public ?float $netResult,
        public ?float $netEquity,
        public ?float $totalAssets,
        public ?int $documentId,
        public array $flagged = [],
    ) {}

    public function isFlagged(string $item): bool
    {
        return in_array($item, $this->flagged, true);
    }

    public function hasIncomeStatementValues(): bool
    {
        return $this->revenues !== null || $this->expenses !== null || $this->netResult !== null;
    }

    public function hasBalanceSheetValues(): bool
    {
        return $this->netEquity !== null || $this->totalAssets !== null;
    }
}
