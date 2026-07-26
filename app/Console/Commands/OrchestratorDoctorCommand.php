<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\Doctor\Checks\EnvironmentVariablesCheck;
use App\Support\Doctor\Checks\FeatureFlagsCheck;
use App\Support\Doctor\Checks\StorageWritableCheck;
use App\Support\Doctor\Checks\SystemUserCheck;
use App\Support\Doctor\Contracts\DoctorCheck;
use App\Support\Doctor\DoctorCheckResult;
use Illuminate\Console\Command;

/**
 * Comando diagnostico (§12 del PRD): in questa fase copre solo i controlli
 * già significativi (variabili d'ambiente, permessi storage, utente di
 * sistema, feature flag). Le fasi successive estendono `self::CHECKS` con
 * nuovi controlli indipendenti (IMAP/SMTP, logo PDF, stato ultimo import),
 * senza riscrivere il comando.
 */
final class OrchestratorDoctorCommand extends Command
{
    protected $signature = 'orchestrator:doctor';

    protected $description = 'Esegue i controlli diagnostici dell\'applicazione (env, storage, utente di sistema, feature flag).';

    /**
     * @var list<class-string<DoctorCheck>>
     */
    private const CHECKS = [
        EnvironmentVariablesCheck::class,
        StorageWritableCheck::class,
        SystemUserCheck::class,
        FeatureFlagsCheck::class,
    ];

    public function handle(): int
    {
        $this->line('Orchestrator doctor');
        $this->line('');

        $allPassed = true;

        foreach (self::CHECKS as $checkClass) {
            /** @var DoctorCheck $check */
            $check = $this->laravel->make($checkClass);

            foreach ($check->run() as $result) {
                $allPassed = $allPassed && $result->passed;

                $this->reportResult($result);
            }
        }

        $this->line('');

        if (! $allPassed) {
            $this->error('Uno o più controlli sono falliti.');

            return self::FAILURE;
        }

        $this->info('Tutti i controlli sono passati.');

        return self::SUCCESS;
    }

    private function reportResult(DoctorCheckResult $result): void
    {
        $icon = $result->passed ? '<fg=green>[OK]</>' : '<fg=red>[FAIL]</>';
        $detail = $result->detail === '' ? '' : " ({$result->detail})";

        $this->line("{$icon} {$result->label}{$detail}");
    }
}
