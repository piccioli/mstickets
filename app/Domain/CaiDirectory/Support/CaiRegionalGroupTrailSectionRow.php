<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Support;

/**
 * Sezione del GR nella tabella "Per Sezione" (Fase 10.7, US-1063): tutti i suoi sentieri (`length_km`, come nella 10.6),
 * un sentiero condiviso conta per intero in ogni Sezione; `sharedCount` = sentieri condivisi con almeno un'altra Sezione.
 * Le Sezioni senza sentieri ci sono, a zero.
 */
final readonly class CaiRegionalGroupTrailSectionRow
{
    public function __construct(
        public string $codiceCai,
        public string $name,
        public CaiTrailKmStats $stats,
        public int $sharedCount,
    ) {}
}
