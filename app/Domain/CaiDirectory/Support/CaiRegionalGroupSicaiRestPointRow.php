<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Support;

use App\Domain\CaiDirectory\Models\CaiSicaiRestPoint;

/**
 * Un punto di appoggio del GR (10.9): nella regione (`inRegion`, dal campo `region` della fonte) e/o vicino ad almeno una
 * tappa del GR. `stageCodes` = codici delle tappe del GR entro 1,5 km, in ordine di tappa.
 */
final readonly class CaiRegionalGroupSicaiRestPointRow
{
    /**
     * @param  list<string>  $stageCodes
     */
    public function __construct(
        public CaiSicaiRestPoint $point,
        public bool $inRegion,
        public array $stageCodes,
    ) {}
}
