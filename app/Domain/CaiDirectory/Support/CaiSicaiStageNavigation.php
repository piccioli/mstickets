<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Support;

use App\Domain\CaiDirectory\Models\CaiSicaiStage;
use App\Domain\CaiDirectory\Queries\CaiRegionalGroupSicai;

/**
 * Posizione di una tappa SICAI nella sua lettera, per la navigazione della scheda (US-1096, design "Tappe SICAI del GR"
 * §"Scheda della tappa"): l'ordine è quello dell'elenco del GR ({@see CaiRegionalGroupSicai::stageRowsForLetters()}).
 * Precedente e successiva sono tappe **principali**: una variante naviga come la sua principale (`main`) e non ha una
 * posizione propria (`position` = null); `variants` sono le varianti della tappa corrente, se è una principale.
 * `letterHasRestPoints` dice se almeno una tappa della lettera ha punti di appoggio entro 1,5 km (testo dello stato vuoto).
 */
final readonly class CaiSicaiStageNavigation
{
    /**
     * @param  list<CaiRegionalGroupSicaiStageRow>  $variants
     */
    public function __construct(
        public CaiRegionalGroupSicaiStageRow $current,
        public CaiRegionalGroupSicaiStageRow $main,
        public ?CaiRegionalGroupSicaiStageRow $previous,
        public ?CaiRegionalGroupSicaiStageRow $next,
        public array $variants,
        public ?int $position,
        public int $mainCount,
        public bool $letterHasRestPoints,
    ) {}

    /** Una query per le tappe della lettera; `null` se la tappa non è fra le righe (lettera assente). */
    public static function for(CaiSicaiStage $stage): ?self
    {
        return self::fromRows($stage->cai_trail_id, CaiRegionalGroupSicai::stageRowsForLetters([$stage->letter]));
    }

    /**
     * @param  list<CaiRegionalGroupSicaiStageRow>  $rows  tappe di una lettera, in ordine
     */
    public static function fromRows(int $trailId, array $rows): ?self
    {
        $current = null;
        $main = [];

        foreach ($rows as $row) {
            if ($row->stage->cai_trail_id === $trailId) {
                $current = $row;
            }

            if (! $row->isVariant) {
                $main[] = $row;
            }
        }

        if ($current === null) {
            return null;
        }

        $base = $current;

        if ($current->isVariant) {
            foreach ($main as $row) {
                if ($row->code() === $current->mainCode) {
                    $base = $row;
                    break;
                }
            }
        }

        $index = (int) array_search($base, $main, true);

        return new self(
            current: $current,
            main: $base,
            previous: $main[$index - 1] ?? null,
            next: $main[$index + 1] ?? null,
            variants: $current->isVariant ? [] : array_values(array_filter(
                $rows,
                fn (CaiRegionalGroupSicaiStageRow $row): bool => $row->isVariant && $row->mainCode === $current->code(),
            )),
            position: $current->isVariant ? null : $index + 1,
            mainCount: count($main),
            letterHasRestPoints: array_filter($rows, fn (CaiRegionalGroupSicaiStageRow $row): bool => $row->restPointsCount > 0) !== [],
        );
    }

    /**
     * Tappe adiacenti (precedente e successiva) con almeno un punto di appoggio vicino: "I più vicini sono nelle tappe
     * adiacenti (E02, E04)".
     *
     * @return list<CaiRegionalGroupSicaiStageRow>
     */
    public function adjacentWithRestPoints(): array
    {
        return array_values(array_filter(
            [$this->previous, $this->next],
            fn (?CaiRegionalGroupSicaiStageRow $row): bool => $row !== null && $row->restPointsCount > 0,
        ));
    }
}
