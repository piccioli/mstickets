<?php

declare(strict_types=1);

namespace App\Support\Doctor;

/**
 * Esito di un singolo controllo diagnostico (§12 del PRD). `passed` determina
 * se il controllo contribuisce all'exit code fallito del comando; i controlli
 * puramente informativi (es. stato dei feature flag) sono sempre `passed`.
 */
final class DoctorCheckResult
{
    public function __construct(
        public readonly string $label,
        public readonly bool $passed,
        public readonly string $detail = '',
    ) {}
}
