<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Support;

use App\Domain\CaiDirectory\Enums\CaiTrailPracticability;
use App\Domain\CaiDirectory\Enums\CaiTrailSort;
use App\Domain\CaiDirectory\Enums\CaiTrailStatus;

/**
 * Filtri, ricerca e ordinamento dell'elenco "Sentieri del GR" (Fase 10.7, US-1063), applicati in SQL (fino a ~4.000
 * sentieri): provincia e settore (sentiero presente, ≥ 50%, in quel territorio), Sezione (codice CAI, oppure
 * {@see WITHOUT_SECTION} per i sentieri senza alcuna Sezione), stato, percorribilità, in AND. La ricerca confronta
 * l'inizio del numero e una parte di nome, partenza o arrivo, senza distinzione di maiuscole.
 */
final readonly class CaiRegionalGroupTrailFilters
{
    /** Valore del filtro per Sezione che seleziona i sentieri senza Sezione. */
    public const string WITHOUT_SECTION = 'nessuna';

    public function __construct(
        public ?int $provinceId = null,
        public ?int $sectorId = null,
        public ?string $section = null,
        public ?CaiTrailStatus $status = null,
        public ?CaiTrailPracticability $practicability = null,
        public string $search = '',
        public CaiTrailSort $sort = CaiTrailSort::NumberAsc,
    ) {}

    public function isFiltering(): bool
    {
        return $this->provinceId !== null || $this->sectorId !== null || $this->section !== null
            || $this->status !== null || $this->practicability !== null || trim($this->search) !== '';
    }
}
