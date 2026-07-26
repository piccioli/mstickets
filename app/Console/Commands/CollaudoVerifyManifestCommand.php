<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;

final class CollaudoVerifyManifestCommand extends Command
{
    protected $signature = 'collaudo:verify-manifest {fase}';

    protected $description = 'Verifica che ogni test_automatico del manifest di collaudo esista davvero';

    public function handle(): int
    {
        $fase = (string) $this->argument('fase');
        $manifestPath = base_path("docs/collaudo/fase-{$fase}.php");

        if (! file_exists($manifestPath)) {
            $this->error("Manifest non trovato: {$manifestPath}");

            return self::FAILURE;
        }

        $manifest = require $manifestPath;
        $missing = [];

        foreach ($manifest['topics'] as $topic) {
            foreach ($topic['test'] as $test) {
                if (! $this->resolveTestReference($test['test_automatico'])) {
                    $missing[] = $test['id'].' -> '.$test['test_automatico'];
                }
            }
        }

        if ($missing !== []) {
            $this->error('Riferimenti mancanti: '.implode(', ', $missing));

            return self::FAILURE;
        }

        $this->info('Tutti i riferimenti del manifest esistono.');

        return self::SUCCESS;
    }

    public function resolveTestReference(string $reference): bool
    {
        return file_exists(base_path($reference));
    }
}
