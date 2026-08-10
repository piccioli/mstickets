<?php

declare(strict_types=1);

namespace App\Providers;

use App\Import\Stages\Contracts\ImportStage;
use App\Import\Stages\ImportStageRegistry;
use Illuminate\Support\ServiceProvider;

/**
 * Costruisce il registro degli stage ETL (App\Import\Stages\ImportStageRegistry)
 * risolvendo `config('import.stages')` via il container: separato da
 * AppServiceProvider perché app/Import è un modulo isolato dal resto del
 * dominio (§4.3 del PRD, vedi anche orchestrator/CLAUDE.md).
 */
class ImportServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ImportStageRegistry::class, function (): ImportStageRegistry {
            /** @var array<int, class-string<ImportStage>> $stageClasses */
            $stageClasses = config('import.stages', []);

            $stages = array_map(
                fn (string $stageClass): ImportStage => $this->app->make($stageClass),
                $stageClasses,
            );

            return new ImportStageRegistry($stages);
        });
    }
}
