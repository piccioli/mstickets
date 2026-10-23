<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Support;

use App\Domain\CaiDirectory\Models\CaiSection;
use App\Domain\CaiDirectory\Models\CaiTrail;
use App\Domain\CaiDirectory\Models\CaiTrailProvince;
use App\Domain\CaiDirectory\Models\CaiTrailSector;

/**
 * Riga dell'elenco dei sentieri del GR (Fase 10.7, US-1063): il sentiero (senza geometria, con le Sezioni caricate e
 * ordinate per nome), la provincia e il settore della regione in cui ricade la quota maggiore della sua lunghezza.
 */
final readonly class CaiRegionalGroupTrailRow
{
    public function __construct(
        public CaiTrail $trail,
        public ?CaiTrailProvince $province,
        public ?CaiTrailSector $sector,
    ) {}

    /** @return list<CaiSection> */
    public function sections(): array
    {
        return array_values($this->trail->sections->all());
    }

    public function isShared(): bool
    {
        return $this->trail->sections->count() > 1;
    }
}
