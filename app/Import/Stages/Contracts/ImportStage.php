<?php

declare(strict_types=1);

namespace App\Import\Stages\Contracts;

use App\Import\Stages\ImportContext;
use App\Import\Stages\StageResult;

/**
 * Ogni stage dell'ETL v1→v2 (§11.4 del PRD) implementa questo contratto e si
 * registra in `config('import.stages')` (vedi ImportStageRegistry): il runner
 * (ImportRunner) si occupa di risolvere l'ordine di esecuzione dalle dipendenze
 * dichiarate, il parsing delle opzioni CLI e l'audit su import_runs, così che
 * uno stage si limiti alla propria logica di importazione.
 */
interface ImportStage
{
    /**
     * Nome univoco dello stage nel registro (es. "users", "organizations"),
     * usato da --stage/--from-stage e come chiave in import_runs.stages.
     */
    public function name(): string;

    /**
     * Nomi degli stage che devono essere eseguiti prima di questo, nella stessa
     * sessione di import (vedi ImportRunner::plan()).
     *
     * @return array<int, string>
     */
    public function dependencies(): array;

    public function run(ImportContext $context): StageResult;
}
