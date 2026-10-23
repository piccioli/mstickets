<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Enums;

use App\Domain\CaiDirectory\Support\CaiConsolidatedSectionRow;
use Filament\Support\Contracts\HasLabel;

/**
 * Colonna di ordinamento della tabella delle Sezioni nel bilancio consolidato del GR (Fase 10.3, US-1024). Il valore
 * è quello dell'URL (`?ordina=ricavi`). Il primo clic ordina crescente le colonne testuali e decrescente quelle
 * numeriche; un valore mancante (`null`) va sempre in fondo, a parità si ordina per nome.
 */
enum CaiConsolidatedSortColumn: string implements HasLabel
{
    case Name = 'nome';
    case Status = 'stato';
    case Source = 'fonte';
    case Revenues = 'ricavi';
    case Expenses = 'costi';
    case NetResult = 'risultato';
    case NetEquity = 'patrimonio';
    case TotalAssets = 'attivo';

    public function getLabel(): string
    {
        return match ($this) {
            self::Name => 'Sezione',
            self::Status => 'Stato',
            self::Source => 'Fonte',
            self::Revenues => 'Ricavi',
            self::Expenses => 'Costi',
            self::NetResult => 'Risultato netto',
            self::NetEquity => 'Patrimonio netto',
            self::TotalAssets => 'Totale attivo',
        };
    }

    public function isNumeric(): bool
    {
        return ! in_array($this, [self::Name, self::Status, self::Source], true);
    }

    /** Verso del primo clic: decrescente per i numeri, crescente per il resto. */
    public function defaultDescending(): bool
    {
        return $this->isNumeric();
    }

    /** Valore di confronto della riga, `null` se mancante (lo stato si confronta nell'ordine dei casi dell'enum). */
    public function valueOf(CaiConsolidatedSectionRow $row): string|int|float|null
    {
        return match ($this) {
            self::Name => $row->name,
            self::Status => array_search($row->status, CaiSectionStatementStatus::cases(), true),
            self::Source => $row->source?->getLabel(),
            self::Revenues => $row->revenues,
            self::Expenses => $row->expenses,
            self::NetResult => $row->netResult,
            self::NetEquity => $row->netEquity,
            self::TotalAssets => $row->totalAssets,
        };
    }

    /**
     * Ordina le righe; i mancanti restano in fondo in entrambi i versi, a parità vince il nome crescente.
     *
     * @param  list<CaiConsolidatedSectionRow>  $rows
     * @return list<CaiConsolidatedSectionRow>
     */
    public function sort(array $rows, bool $descending): array
    {
        usort($rows, function (CaiConsolidatedSectionRow $a, CaiConsolidatedSectionRow $b) use ($descending): int {
            $left = $this->valueOf($a);
            $right = $this->valueOf($b);

            if ($left === null || $right === null) {
                $missing = ($left === null) <=> ($right === null);

                return $missing !== 0 ? $missing : strnatcasecmp($a->name, $b->name);
            }

            $compared = is_string($left) && is_string($right) ? strnatcasecmp($left, $right) : $left <=> $right;

            if ($descending) {
                $compared = -$compared;
            }

            return $compared !== 0 ? $compared : strnatcasecmp($a->name, $b->name);
        });

        return $rows;
    }
}
