<?php

declare(strict_types=1);

namespace App\Support\Doctor\Checks;

use App\Support\Doctor\Contracts\DoctorCheck;
use App\Support\Doctor\DoctorCheckResult;

/**
 * Elenca lo stato dei feature flag delle automazioni schedulate
 * (`config('orchestrator.features')`, §10.1/§10.2). Puramente informativo:
 * un flag disattivo non è un errore di configurazione, quindi ogni riga è
 * sempre `passed`.
 */
final class FeatureFlagsCheck implements DoctorCheck
{
    public function run(): array
    {
        /** @var array<string, bool> $features */
        $features = config('orchestrator.features', []);

        return array_map(
            static fn (string $name, bool $enabled): DoctorCheckResult => new DoctorCheckResult(
                "Feature flag {$name}",
                true,
                $enabled ? 'attivo' : 'disattivo',
            ),
            array_keys($features),
            array_values($features),
        );
    }
}
