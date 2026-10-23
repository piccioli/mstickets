<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Import;

use App\Domain\Identity\Enums\Region;

/**
 * Riepilogo di `cai:import-osm2cai-extract` (US-1051, territori US-1061): sentieri scritti nel datapack, quanti collegati
 * ad almeno una Sezione di `sezioni_cai`, codici di Sezione osm2cai (`clubs.cai_code`) assenti da `sezioni_cai` con il
 * numero di sentieri, e per ogni regione italiana i km nel territorio e i sentieri presenti (≥ 50%), confrontabili con
 * `riepilogo_regioni.json` di OSM2CAIReport, e le tappe del Sentiero Italia CAI (US-1091).
 */
final readonly class CaiOsm2caiExtractSummary
{
    /**
     * @param  array<string, int>  $unknownSectionCodes  codice → numero di sentieri
     * @param  list<array{region: Region, km: float, presentRoutes: int}>  $regions  in ordine di nome
     * @param  list<string>  $foreignRegions  regioni della fonte non abbinate all'enum (estere), scartate
     * @param  array{total: int, withoutNumber: int, withoutLetter: int}  $sicaiStages  tappe SICAI (US-1091)
     */
    public function __construct(
        public int $trails,
        public int $linkedTrails,
        public array $unknownSectionCodes,
        public array $regions = [],
        public array $foreignRegions = [],
        public int $provinces = 0,
        public int $sectors = 0,
        public int $trailTerritories = 0,
        public array $sicaiStages = ['total' => 0, 'withoutNumber' => 0, 'withoutLetter' => 0],
    ) {}

    /**
     * Regioni italiane assenti dalla fonte (nessuna regione di `regions` abbinata).
     *
     * @return list<Region>
     */
    public function missingRegions(): array
    {
        $found = array_map(fn (array $row): Region => $row['region'], $this->regions);

        return array_values(array_filter(Region::cases(), fn (Region $region): bool => ! in_array($region, $found, true)));
    }
}
