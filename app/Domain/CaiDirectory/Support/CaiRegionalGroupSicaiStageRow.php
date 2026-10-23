<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Support;

use App\Domain\CaiDirectory\Models\CaiSicaiStage;

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
}
