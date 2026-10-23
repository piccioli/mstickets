<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Support;

/**
 * Settore osm2cai di una provincia del GR (Fase 10.7, US-1063): km intersecati e sentieri presenti per stato.
 * `hasTrails` è falso per i settori senza righe in `cai_trail_territory_km` ("settore senza sentieri").
 */
final readonly class CaiRegionalGroupTrailSectorRow
{
    public function __construct(
        public int $id,
        public ?string $code,
        public ?string $fullCode,
        public ?string $name,
        public CaiTrailKmStats $stats,
        public bool $hasTrails,
    ) {}
}
