<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Support;

use App\Domain\CaiDirectory\Enums\CaiTrailDifficulty;
use App\Domain\CaiDirectory\Enums\CaiTrailPracticability;
use App\Domain\CaiDirectory\Enums\CaiTrailSort;
use App\Domain\CaiDirectory\Enums\CaiTrailStatus;
use App\Domain\CaiDirectory\Models\CaiTrail;
use Illuminate\Support\Collection;

/**
 * Filtri e ordinamento dell'elenco dei sentieri (Fase 10.6, US-1054; riusabili dai sentieri del GR): stati di
 * accatastamento in OR (nessuno = tutti), una difficoltà e una percorribilità facoltative, un ordinamento. Si applicano
 * in PHP sui sentieri già caricati (al più qualche centinaio per Sezione).
 */
final readonly class CaiTrailFilters
{
    /**
     * @param  list<CaiTrailStatus>  $statuses
     */
    public function __construct(
        public array $statuses = [],
        public ?CaiTrailDifficulty $difficulty = null,
        public ?CaiTrailPracticability $practicability = null,
        public CaiTrailSort $sort = CaiTrailSort::NumberAsc,
    ) {}

    public function isFiltering(): bool
    {
        return $this->statuses !== [] || $this->difficulty !== null || $this->practicability !== null;
    }

    /**
     * @param  Collection<int, CaiTrail>  $trails
     * @return Collection<int, CaiTrail>
     */
    public function apply(Collection $trails): Collection
    {
        $filtered = $trails->filter(fn (CaiTrail $trail): bool => ($this->statuses === [] || in_array($trail->status, $this->statuses, true))
            && ($this->difficulty === null || $this->difficulty->matches($trail->cai_scale))
            && ($this->practicability === null || $trail->practicability === $this->practicability));

        return $this->sort->sort($filtered);
    }
}
