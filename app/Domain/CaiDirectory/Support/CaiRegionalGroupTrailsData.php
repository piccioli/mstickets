<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Support;

use App\Domain\CaiDirectory\Enums\CaiTrailPracticability;
use App\Domain\CaiDirectory\Queries\CaiRegionalGroupTrails;

/**
 * Numeri della pagina "Sentieri del GR" (Fase 10.7), calcolati da {@see CaiRegionalGroupTrails}:
 *
 * - `territory`: **km nel territorio regionale** (intersezione con la regione, anche sentieri senza Sezione) e sentieri
 *   **presenti** (≥ 50% della lunghezza nella regione), per stato;
 * - `managedKm` / `managedCount`: **km gestiti dalle Sezioni del GR** (`length_km`, ogni sentiero una sola volta);
 * - sui sentieri presenti: quelli senza alcuna Sezione, quelli condivisi fra più Sezioni, km e numero per percorribilità;
 * - righe per provincia (con i settori) e per Sezione del GR.
 *
 * `regionId` è `null` se la regione non ha dati osm2cai (tutto a zero); `codiceCai` è `null` se il GR non ha Sezioni.
 */
final readonly class CaiRegionalGroupTrailsData
{
    /**
     * @param  array<string, array{km: float, count: int}>  $practicability  valore di {@see CaiTrailPracticability} → km e sentieri, nell'ordine dell'enum
     * @param  list<CaiRegionalGroupTrailProvinceRow>  $provinces  ordinate per nome
     * @param  list<CaiRegionalGroupTrailSectionRow>  $sections  ordinate per nome
     */
    public function __construct(
        public ?int $regionId,
        public ?string $codiceCai,
        public string $groupName,
        public CaiTrailKmStats $territory,
        public float $managedKm,
        public int $managedCount,
        public int $presentWithoutSectionCount,
        public float $presentWithoutSectionKm,
        public int $presentSharedCount,
        public array $practicability,
        public array $provinces,
        public array $sections,
    ) {}

    public function sectorsCount(): int
    {
        return array_sum(array_map(fn (CaiRegionalGroupTrailProvinceRow $p): int => $p->sectorsCount(), $this->provinces));
    }

    /** Somma dei km delle Sezioni: supera {@see $managedKm} quando ci sono sentieri condivisi. */
    public function sectionsKm(): float
    {
        return array_sum(array_map(fn (CaiRegionalGroupTrailSectionRow $s): float => $s->stats->km(), $this->sections));
    }
}
