<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Support;

use App\Domain\CaiDirectory\Queries\CaiRegionalGroupSicai;

/**
 * Tappe SICAI e punti di appoggio di un Gruppo Regionale con il riepilogo (10.9), calcolati da
 * {@see CaiRegionalGroupSicai}. Km e dislivelli del riepilogo sono delle sole tappe principali (le varianti a parte, come
 * nel design); non percorribili e con difficoltà includono anche le varianti. osm2cai ha il dislivello solo per una parte
 * dei sentieri (40 tappe su 525 a ottobre 2026): `mainWithAscentCount` dice su quante tappe principali è sommato.
 */
final readonly class CaiRegionalGroupSicaiData
{
    /**
     * @param  list<string>  $letters
     * @param  list<CaiRegionalGroupSicaiStageRow>  $stages  per lettera, numero, variante; senza numero in fondo alla lettera
     * @param  list<CaiRegionalGroupSicaiStageRow>  $notPracticable
     * @param  list<CaiRegionalGroupSicaiStageRow>  $withDifficulty
     * @param  array<string, list<CaiRegionalGroupSicaiRestPointRow>>  $restPointsByType  tipo → punti per nome
     */
    public function __construct(
        public string $groupName,
        public array $letters,
        public array $stages,
        public int $mainCount,
        public int $variantsCount,
        public float $mainKm,
        public float $variantsKm,
        public int $mainAscent,
        public int $mainDescent,
        public int $mainWithAscentCount,
        public array $notPracticable,
        public array $withDifficulty,
        public float $problemKm,
        public array $restPointsByType,
        public int $restPointsCount,
    ) {}

    public function stagesCount(): int
    {
        return count($this->stages);
    }
}
