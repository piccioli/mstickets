<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Enums;

use App\Domain\CaiDirectory\Support\CaiRegionalGroupHutRow;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Support\Str;

/**
 * Colonna di ordinamento dell'elenco "Rifugi del GR" (Fase 10.5, US-1043), come nel design `rifugi-del-gr.dc.html`
 * (`cmps`/`cmp`): il valore è quello dell'URL (`?ordina=quota`). Primo clic: quota e posti letto dal più alto, le altre
 * crescenti. Il nome si confronta senza accenti né maiuscole e in ordine naturale; a parità vince il nome (A–Z).
 * Un valore mancante (quota, posti, stato, tipo, data di fine del contratto) va sempre in fondo, in entrambi i versi.
 */
enum CaiRegionalGroupHutSortColumn: string implements HasLabel
{
    case Name = 'nome';
    case Section = 'sezione';
    case Type = 'tipo';
    case Status = 'stato';
    case Altitude = 'quota';
    case Beds = 'posti';
    case ContractEnd = 'scadenza';

    /** Intestazione della colonna nella tabella. */
    public function getLabel(): string
    {
        return match ($this) {
            self::Name => 'Nome',
            self::Section => 'Sezione proprietaria',
            self::Type => 'Tipo',
            self::Status => 'Stato',
            self::Altitude => 'Quota (m)',
            self::Beds => 'Posti letto',
            self::ContractEnd => 'Scadenza contratto',
        };
    }

    public function defaultDescending(): bool
    {
        return $this === self::Altitude || $this === self::Beds;
    }

    public function isNumeric(): bool
    {
        return $this === self::Altitude || $this === self::Beds;
    }

    /**
     * @param  list<CaiRegionalGroupHutRow>  $rows
     * @return list<CaiRegionalGroupHutRow>
     */
    public function sort(array $rows, bool $descending): array
    {
        usort($rows, function (CaiRegionalGroupHutRow $a, CaiRegionalGroupHutRow $b) use ($descending): int {
            $valueA = $this->value($a);
            $valueB = $this->value($b);

            if ($valueA === null || $valueB === null) {
                return ($valueA === null) <=> ($valueB === null) ?: self::byName($a, $b);
            }

            $compared = is_string($valueA) && is_string($valueB) ? strnatcmp($valueA, $valueB) : $valueA <=> $valueB;

            return ($descending ? -$compared : $compared) ?: self::byName($a, $b);
        });

        return $rows;
    }

    private function value(CaiRegionalGroupHutRow $row): int|string|null
    {
        return match ($this) {
            self::Name => self::key($row->hut->name),
            self::Section => self::key($row->sectionName),
            self::Type => $row->hut->type === null ? null : (int) array_search($row->hut->type, CaiHutType::cases(), true),
            self::Status => $row->hut->status === null ? null : (int) array_search($row->hut->status, CaiHutStatus::cases(), true),
            self::Altitude => $row->hut->altitude,
            self::Beds => $row->hut->beds,
            self::ContractEnd => $row->contractEnd?->format('Y-m-d'),
        };
    }

    private static function byName(CaiRegionalGroupHutRow $a, CaiRegionalGroupHutRow $b): int
    {
        return strnatcmp(self::key($a->hut->name), self::key($b->hut->name));
    }

    private static function key(string $value): string
    {
        return Str::lower(Str::ascii($value));
    }
}
