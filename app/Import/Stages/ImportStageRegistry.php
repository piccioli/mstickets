<?php

declare(strict_types=1);

namespace App\Import\Stages;

use App\Import\Stages\Contracts\ImportStage;

/**
 * Elenco degli stage disponibili per il runner. Gli stage reali si registrano
 * elencando la propria classe in `config('import.stages')` (risolta via il
 * container): nessuna story successiva deve toccare questa classe o il
 * comando `v1:import`, solo il file di config.
 */
final class ImportStageRegistry
{
    /** @var array<string, ImportStage> */
    private array $stages = [];

    /**
     * @param  iterable<ImportStage>  $stages
     */
    public function __construct(iterable $stages = [])
    {
        foreach ($stages as $stage) {
            $this->register($stage);
        }
    }

    public function register(ImportStage $stage): self
    {
        $this->stages[$stage->name()] = $stage;

        return $this;
    }

    public function has(string $name): bool
    {
        return array_key_exists($name, $this->stages);
    }

    public function get(string $name): ImportStage
    {
        if (! $this->has($name)) {
            throw new ImportRunnerException("Stage sconosciuto: \"{$name}\".");
        }

        return $this->stages[$name];
    }

    /**
     * @return array<string, ImportStage>
     */
    public function all(): array
    {
        return $this->stages;
    }
}
