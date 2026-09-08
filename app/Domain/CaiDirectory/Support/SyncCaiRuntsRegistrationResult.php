<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Support;

use App\Domain\CaiDirectory\Actions\SyncCaiRuntsRegistration;
use App\Domain\CaiDirectory\Models\CaiRuntsRegistration;

/**
 * Esito di {@see SyncCaiRuntsRegistration} (Fase 9, storia 3): un semplice
 * DTO readonly, stesso ruolo di `CaiImportTableResult` (Storia 1/US-802) ma per un'Action che sincronizza
 * un'unica entità invece di un batch — `queuedAnalysisCount` è il numero di documenti di bilancio nuovi per
 * cui è stata accodata l'analisi asincrona (Task 7), usato dalla notifica del bottone dashboard.
 */
final readonly class SyncCaiRuntsRegistrationResult
{
    private function __construct(
        public bool $found,
        public ?CaiRuntsRegistration $registration,
        public int $queuedAnalysisCount,
    ) {}

    public static function notFound(): self
    {
        return new self(found: false, registration: null, queuedAnalysisCount: 0);
    }

    public static function synced(CaiRuntsRegistration $registration, int $queuedAnalysisCount): self
    {
        return new self(found: true, registration: $registration, queuedAnalysisCount: $queuedAnalysisCount);
    }
}
