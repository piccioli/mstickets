<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Support;

use App\Domain\CaiDirectory\Enums\CaiTrailStatus;

/**
 * Km e numero di sentieri per stato di accatastamento (Fase 10.7, US-1063), per un territorio (km intersecati e sentieri
 * presenti, da `cai_trail_territory_km`) o per una Sezione (`length_km` e sentieri della Sezione). Le chiavi sono sempre
 * i quattro valori di {@see CaiTrailStatus}. "Consolidati" = stati 3 e 4.
 */
final readonly class CaiTrailKmStats
{
    /**
     * @param  array<int, float>  $kmByStatus  valore dello stato (1–4) → km
     * @param  array<int, int>  $countByStatus  valore dello stato (1–4) → sentieri
     */
    public function __construct(
        public array $kmByStatus,
        public array $countByStatus,
    ) {}

    public static function empty(): self
    {
        $zeros = array_fill_keys(array_map(fn (CaiTrailStatus $s): int => $s->value, CaiTrailStatus::cases()), 0);

        return new self(array_map(fn (int $zero): float => 0.0, $zeros), $zeros);
    }

    /** Aggiunge km e sentieri di uno stato, restituendo una nuova istanza. */
    public function with(CaiTrailStatus $status, float $km, int $count): self
    {
        $kmByStatus = $this->kmByStatus;
        $countByStatus = $this->countByStatus;
        $kmByStatus[$status->value] += $km;
        $countByStatus[$status->value] += $count;

        return new self($kmByStatus, $countByStatus);
    }

    public function km(): float
    {
        return array_sum($this->kmByStatus);
    }

    public function trailsCount(): int
    {
        return array_sum($this->countByStatus);
    }

    public function kmOf(CaiTrailStatus $status): float
    {
        return $this->kmByStatus[$status->value];
    }

    public function countOf(CaiTrailStatus $status): int
    {
        return $this->countByStatus[$status->value];
    }

    public function consolidatedKm(): float
    {
        return $this->kmOf(CaiTrailStatus::PreValidated) + $this->kmOf(CaiTrailStatus::Validated);
    }

    /** Quota (0–1) dei km consolidati sul totale, `null` senza km. */
    public function consolidatedShare(): ?float
    {
        $km = $this->km();

        return $km > 0 ? $this->consolidatedKm() / $km : null;
    }
}
