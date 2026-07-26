<?php

declare(strict_types=1);

namespace App\Support\Doctor\Checks;

use App\Support\Doctor\Contracts\DoctorCheck;
use App\Support\Doctor\DoctorCheckResult;

/**
 * Verifica la presenza (non vuota) delle variabili d'ambiente già note in
 * questa fase (§4.2, `.env.example`/US-009): applicazione, database, Redis,
 * storage, coda/cache/sessione e mail/mailpit locale. Legge lo snapshot da
 * `config('orchestrator.required_env')` (mai `env()` qui, §13.3): le fasi
 * successive (IMAP/SMTP, ecc.) aggiungono le proprie voci in quel file.
 */
final class EnvironmentVariablesCheck implements DoctorCheck
{
    public function run(): array
    {
        /** @var array<string, string|null> $requiredEnv */
        $requiredEnv = config('orchestrator.required_env', []);

        return array_map(
            static function (string $name, ?string $value): DoctorCheckResult {
                $passed = $value !== null && $value !== '';

                return new DoctorCheckResult(
                    "Variabile env {$name}",
                    $passed,
                    $passed ? 'presente' : 'mancante o vuota',
                );
            },
            array_keys($requiredEnv),
            array_values($requiredEnv),
        );
    }
}
