<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Support;

use App\Domain\CaiDirectory\Enums\CaiTrailPracticability;
use App\Domain\CaiDirectory\Models\CaiSicaiStage;
use App\Support\Formatting\ItalianNumberFormatter;

/**
 * Una tappa SICAI del GR (10.9), con il sentiero caricato (`stage->trail`, senza geometria). `isVariant` vale per ogni
 * tappa con lo stesso numero di una precedente nella stessa lettera (D18S dopo D18, D10S dopo D10N): `mainCode` è il
 * codice della principale, `variantCodes` quelli delle varianti che la seguono. `restPointsCount` = punti di appoggio
 * entro 1,5 km (US-1093).
 */
final readonly class CaiRegionalGroupSicaiStageRow
{
    /**
     * @param  list<string>  $variantCodes
     */
    public function __construct(
        public CaiSicaiStage $stage,
        public bool $isVariant,
        public ?string $mainCode,
        public array $variantCodes,
        public int $restPointsCount,
    ) {}

    public function code(): string
    {
        return $this->stage->code;
    }

    /** "Partenza → Arrivo" dal sentiero, "—" al posto del dato mancante (come nella scheda del sentiero). */
    public function route(): string
    {
        $trail = $this->stage->trail;

        return (filled($trail?->from_place) ? $trail->from_place : '—').' → '.(filled($trail?->to_place) ? $trail->to_place : '—');
    }

    public function practicability(): CaiTrailPracticability
    {
        return $this->stage->trail->practicability ?? CaiTrailPracticability::Unknown;
    }

    /** "12,4" (km, un decimale), `null` se osm2cai non ha la lunghezza. */
    public function km(): ?string
    {
        $km = $this->stage->trail?->length_km;

        return $km === null ? null : ItalianNumberFormatter::decimal((float) $km, 1);
    }

    /** "1.150 m" di salita, `null` se assente (osm2cai lo ha solo per una parte delle tappe). */
    public function ascent(): ?string
    {
        return self::meters($this->stage->trail?->ascent);
    }

    public function descent(): ?string
    {
        return self::meters($this->stage->trail?->descent);
    }

    /** Difficoltà CAI (T, E, EE, EEA), `null` se assente. */
    public function difficulty(): ?string
    {
        $scale = $this->stage->trail?->cai_scale;

        return filled($scale) ? (string) $scale : null;
    }

    private static function meters(mixed $value): ?string
    {
        return $value === null ? null : ItalianNumberFormatter::integer((int) $value).' m';
    }
}
