<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Support;

use App\Domain\CaiDirectory\Queries\CaiRegionalGroupHuts;

/**
 * Rifugi di un Gruppo Regionale e numeri del riepilogo (10.5), calcolati da {@see CaiRegionalGroupHuts}.
 * `sectionsCount` (M) sono le Sezioni del GR per prefisso; `sectionsWithHutsCount` quelle con almeno un rifugio
 * proprio (un rifugio intestato al GR non conta come Sezione); `sectionsWithoutHuts` le altre, ordinate per nome.
 * `codiceCai` è `null` se la regione non ha Sezioni.
 */
final readonly class CaiRegionalGroupHutsData
{
    /**
     * @param  list<CaiRegionalGroupHutRow>  $huts  ordinati per nome
     * @param  list<array{codiceCai: string, name: string}>  $sectionsWithoutHuts
     */
    public function __construct(
        public ?string $codiceCai,
        public string $groupName,
        public array $huts,
        public int $sectionsCount,
        public int $sectionsWithHutsCount,
        public array $sectionsWithoutHuts,
        public int $total,
        public int $active,
        public int $beds,
        public int $expiringContracts,
        public int $expiredContracts,
    ) {}
}
