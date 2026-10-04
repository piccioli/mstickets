<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Import;

/**
 * Contatori mutabili di {@see CaiSnapshotImporter} per una singola tabella snapshot.
 */
final class SnapshotCounts
{
    public int $read = 0;

    public int $created = 0;

    public int $updated = 0;

    public int $skipped = 0;

    public int $orphans = 0;

    public function toResult(string $label): CaiImportTableResult
    {
        return new CaiImportTableResult(
            read: $this->read,
            created: $this->created,
            updated: $this->updated,
            skipped: $this->skipped,
            warnings: $this->orphans > 0 ? ["{$this->orphans} righe {$label} saltate: genitore inesistente"] : [],
        );
    }
}
