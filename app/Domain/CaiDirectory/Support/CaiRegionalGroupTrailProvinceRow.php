<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Support;

/**
 * Provincia della regione del GR (Fase 10.7, US-1063): km intersecati e sentieri presenti per stato, con i suoi settori
 * ordinati per codice completo (`full_code`, es. "LBG01": `code` della fonte è solo il progressivo).
 */
final readonly class CaiRegionalGroupTrailProvinceRow
{
    /**
     * @param  list<CaiRegionalGroupTrailSectorRow>  $sectors
     */
    public function __construct(
        public int $id,
        public string $name,
        public ?string $code,
        public CaiTrailKmStats $stats,
        public array $sectors,
    ) {}

    public function sectorsCount(): int
    {
        return count($this->sectors);
    }

    public function sectorsWithTrailsCount(): int
    {
        return count(array_filter($this->sectors, fn (CaiRegionalGroupTrailSectorRow $s): bool => $s->hasTrails));
    }

    public function emptySectorsCount(): int
    {
        return $this->sectorsCount() - $this->sectorsWithTrailsCount();
    }
}
