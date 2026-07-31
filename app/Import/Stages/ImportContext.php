<?php

declare(strict_types=1);

namespace App\Import\Stages;

use App\Import\Models\ImportRun;

/**
 * Opzioni della sessione di import corrente (§11.2 del PRD), passate a ogni
 * stage: uno stage non deve mai leggere direttamente le opzioni della console
 * (il comando resta l'unico punto che fa parsing di `--dry-run` ecc.).
 */
final readonly class ImportContext
{
    public function __construct(
        private ImportRun $importRun,
        private bool $dryRun = false,
        private ?int $limit = null,
        private bool $truncate = false,
        private bool $anonymize = false,
    ) {}

    public function importRun(): ImportRun
    {
        return $this->importRun;
    }

    public function isDryRun(): bool
    {
        return $this->dryRun;
    }

    public function limit(): ?int
    {
        return $this->limit;
    }

    public function shouldTruncate(): bool
    {
        return $this->truncate;
    }

    public function shouldAnonymize(): bool
    {
        return $this->anonymize;
    }
}
