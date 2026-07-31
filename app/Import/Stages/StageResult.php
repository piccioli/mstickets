<?php

declare(strict_types=1);

namespace App\Import\Stages;

/**
 * Esito di un singolo stage, salvato in import_runs.stages (§5.2 del PRD).
 * Immutabile: uno stage lo costruisce una volta a fine esecuzione, non lo
 * aggiorna incrementalmente.
 */
final readonly class StageResult
{
    /**
     * @param  array<int, string>  $warnings  Segnalazioni non bloccanti (compromessi applicati, righe scartate, ecc.), riportate poi da v1:validate (US-216).
     */
    public function __construct(
        public int $read = 0,
        public int $created = 0,
        public int $updated = 0,
        public int $skipped = 0,
        public array $warnings = [],
    ) {}

    /**
     * @return array{read: int, created: int, updated: int, skipped: int, warnings: array<int, string>}
     */
    public function toArray(): array
    {
        return [
            'read' => $this->read,
            'created' => $this->created,
            'updated' => $this->updated,
            'skipped' => $this->skipped,
            'warnings' => $this->warnings,
        ];
    }
}
