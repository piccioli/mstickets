<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Support;

use App\Domain\CaiDirectory\Enums\CaiHutStatus;
use App\Domain\CaiDirectory\Enums\CaiHutType;
use App\Domain\CaiDirectory\Enums\CaiRegionalGroupHutSortColumn;

/**
 * Filtri, ordinamento e raggruppamento dell'elenco "Rifugi del GR" (Fase 10.5, US-1043), sulle righe già calcolate da
 * `CaiRegionalGroupHuts` (al più un paio di centinaia): Sezione (codice CAI, oppure {@see GROUP_KEY} per i rifugi
 * intestati al GR), tipo, stato e "contratto in scadenza o scaduto", in AND.
 */
final readonly class CaiRegionalGroupHutFilters
{
    /** Valore del filtro per Sezione che seleziona i rifugi intestati al GR. */
    public const string GROUP_KEY = 'gr';

    public function __construct(
        public ?string $section = null,
        public ?CaiHutType $type = null,
        public ?CaiHutStatus $status = null,
        public bool $contractAlerts = false,
        public CaiRegionalGroupHutSortColumn $sort = CaiRegionalGroupHutSortColumn::Name,
        public bool $descending = false,
    ) {}

    public function isFiltering(): bool
    {
        return $this->section !== null || $this->type !== null || $this->status !== null || $this->contractAlerts;
    }

    /** Chiave di Sezione di una riga: codice CAI della Sezione o {@see GROUP_KEY}. */
    public static function sectionKey(CaiRegionalGroupHutRow $row): string
    {
        return $row->ownedByGroup ? self::GROUP_KEY : (string) $row->hut->branch_code;
    }

    /**
     * @param  list<CaiRegionalGroupHutRow>  $rows
     * @return list<CaiRegionalGroupHutRow>
     */
    public function apply(array $rows): array
    {
        $filtered = array_values(array_filter($rows, fn (CaiRegionalGroupHutRow $row): bool => ($this->section === null || self::sectionKey($row) === $this->section)
            && ($this->type === null || $row->hut->type === $this->type)
            && ($this->status === null || $row->hut->status === $this->status)
            && (! $this->contractAlerts || $row->contractStatus?->isAlert() === true)));

        return $this->sort->sort($filtered, $this->descending);
    }

    /**
     * Gruppi per Sezione, nell'ordine del design: prima i rifugi intestati al GR, poi le Sezioni per nome; dentro ogni
     * gruppo l'ordine delle righe ricevute.
     *
     * @param  list<CaiRegionalGroupHutRow>  $rows
     * @return list<array{key: string, title: string, rows: list<CaiRegionalGroupHutRow>}>
     */
    public static function groups(array $rows): array
    {
        $groups = [];

        foreach ($rows as $row) {
            $key = self::sectionKey($row);
            $groups[$key] ??= [
                'key' => $key,
                'title' => $row->ownedByGroup ? 'Intestati al '.$row->sectionName : $row->sectionName,
                'name' => $row->sectionName,
                'owned' => $row->ownedByGroup,
                'rows' => [],
            ];
            $groups[$key]['rows'][] = $row;
        }

        usort($groups, fn (array $a, array $b): int => $b['owned'] <=> $a['owned'] ?: strnatcasecmp($a['name'], $b['name']));

        return array_map(fn (array $group): array => [
            'key' => $group['key'],
            'title' => $group['title'],
            'rows' => $group['rows'],
        ], $groups);
    }

    /**
     * Numero di gruppi di tutte le righe del GR (Sezioni con rifugi più, se ce ne sono, i rifugi intestati al GR).
     *
     * @param  list<CaiRegionalGroupHutRow>  $rows
     */
    public static function groupsCount(array $rows): int
    {
        return count(array_unique(array_map(self::sectionKey(...), $rows)));
    }
}
