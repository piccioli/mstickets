<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Import;

/**
 * Esito dell'import di una singola tabella CAI, stampato da `cai:import-datapack`
 * (US-802). Stesso schema di `App\Import\Stages\StageResult` (letti/creati/aggiornati/
 * saltati), duplicato qui invece di riusato per non far dipendere il dominio
 * `App\Domain\CaiDirectory` dal namespace `App\Import\*`, che resta di proprietà
 * esclusiva della pipeline ETL v1→v2 (§11 del PRD) — due fonti dati distinte (dump v1
 * Postgres vs. datapack RUNTS-CAI SQLite) con lo stesso bisogno di riepilogo, non lo
 * stesso bisogno di accoppiamento.
 */
final readonly class CaiImportTableResult
{
    /**
     * @param  array<int, string>  $warnings
     */
    public function __construct(
        public int $read = 0,
        public int $created = 0,
        public int $updated = 0,
        public int $skipped = 0,
        public array $warnings = [],
    ) {}
}
