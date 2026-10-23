<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Support;

use App\Domain\CaiDirectory\Enums\CaiHutContractStatus;
use App\Domain\CaiDirectory\Models\CaiHut;
use Carbon\CarbonImmutable;

/**
 * Un rifugio dei rifugi del GR (10.5): `sectionName` è il nome della Sezione proprietaria o, per un rifugio intestato
 * al GR (`ownedByGroup`), "GR {regione}". Scadenza del contratto già calcolata con {@see CaiHutContractExpiry}.
 */
final readonly class CaiRegionalGroupHutRow
{
    public function __construct(
        public CaiHut $hut,
        public string $sectionName,
        public bool $ownedByGroup,
        public ?CarbonImmutable $contractEnd,
        public ?CaiHutContractStatus $contractStatus,
        public ?string $contractNote,
    ) {}
}
