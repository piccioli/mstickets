<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Import;

/**
 * Riepilogo di `cai:import-osm2cai-extract` (US-1051): sentieri scritti nel datapack, quanti collegati ad almeno una
 * Sezione di `sezioni_cai` e codici di Sezione osm2cai (`clubs.cai_code`) assenti da `sezioni_cai`, con il numero di
 * sentieri.
 */
final readonly class CaiOsm2caiExtractSummary
{
    /**
     * @param  array<string, int>  $unknownSectionCodes  codice → numero di sentieri
     */
    public function __construct(
        public int $trails,
        public int $linkedTrails,
        public array $unknownSectionCodes,
    ) {}
}
