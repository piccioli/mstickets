<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Support;

use App\Domain\CaiDirectory\Enums\CaiHutSort;
use App\Domain\CaiDirectory\Enums\CaiHutStatus;
use App\Domain\CaiDirectory\Enums\CaiHutType;
use App\Domain\CaiDirectory\Models\CaiHut;
use Illuminate\Support\Collection;

/**
 * Filtri e ordinamento dell'elenco dei rifugi (Fase 10.4, US-1034; riusabili dai rifugi del GR): tipi in OR (nessun
 * tipo = tutti; un rifugio con tipo sconosciuto compare solo senza filtro di tipo), uno stato facoltativo, un
 * ordinamento. Si applicano in PHP sui rifugi già caricati (al più qualche decina per Sezione).
 */
final readonly class CaiHutFilters
{
    /**
     * @param  list<CaiHutType>  $types
     */
    public function __construct(
        public array $types = [],
        public ?CaiHutStatus $status = null,
        public CaiHutSort $sort = CaiHutSort::NameAsc,
    ) {}

    public function isFiltering(): bool
    {
        return $this->types !== [] || $this->status !== null;
    }

    /**
     * @param  Collection<int, CaiHut>  $huts
     * @return Collection<int, CaiHut>
     */
    public function apply(Collection $huts): Collection
    {
        $filtered = $huts->filter(fn (CaiHut $hut): bool => ($this->types === [] || in_array($hut->type, $this->types, true))
            && ($this->status === null || $hut->status === $this->status));

        return $this->sort->sort($filtered);
    }
}
