<?php

declare(strict_types=1);

namespace App\Import\Stages;

use App\Import\Enums\ImportRunStatus;
use App\Import\Models\ImportRun;
use App\Import\Stages\Contracts\ImportStage;
use Throwable;

/**
 * Risolve l'ordine di esecuzione degli stage registrati dalle dipendenze
 * dichiarate (ordinamento topologico) ed esegue la sessione, aggiornando
 * import_runs.stages (§5.2 del PRD) man mano che ogni stage completa.
 *
 * Semantica di --stage/--from-stage (unica lettura possibile senza ambiguità,
 * documentata qui perché non ovvia dal solo PRD):
 * - senza filtri: gira l'intero registro nell'ordine topologico completo.
 * - --from-stage=X: esegue X e tutti gli stage successivi nell'ordine
 *   topologico completo (compresi quelli di rami indipendenti). Gli stage
 *   PRIMA di X sono assunti già eseguiti in una sessione precedente (è il
 *   punto di ripresa dopo un errore parziale): per costruzione dell'ordine
 *   topologico, ogni dipendenza di uno stage incluso è o prima di X (assunta
 *   già eseguita) o inclusa a sua volta nella sessione, quindi non serve un
 *   controllo esplicito.
 * - --stage=X: esegue SOLO X, nessun altro stage nella sessione. Se X
 *   dichiara almeno una dipendenza, quella dipendenza non fa parte di questa
 *   sessione: errore esplicito invece di assumere silenziosamente che sia
 *   già stata eseguita altrove (usare --from-stage o un run completo).
 */
final class ImportRunner
{
    public function __construct(private readonly ImportStageRegistry $registry) {}

    /**
     * @return array<int, ImportStage>
     */
    public function plan(?string $only = null, ?string $from = null): array
    {
        $order = $this->topologicalOrder();

        if ($only !== null) {
            $stage = $this->registry->get($only);
            $missingDependencies = $stage->dependencies();

            if ($missingDependencies !== []) {
                $list = implode(', ', $missingDependencies);

                throw new ImportRunnerException(
                    "Lo stage \"{$only}\" dipende da [{$list}], che non vengono eseguiti in questa sessione. ".
                    'Usa --from-stage=<nome> a partire dalla dipendenza più a monte, oppure esegui l\'import completo.',
                );
            }

            return [$stage];
        }

        if ($from !== null) {
            $this->registry->get($from);
            $index = array_search($from, array_map(static fn (ImportStage $stage): string => $stage->name(), $order), true);

            return array_slice($order, $index);
        }

        return $order;
    }

    /**
     * @return array<int, ImportStage>
     */
    private function topologicalOrder(): array
    {
        $stages = $this->registry->all();

        foreach ($stages as $stage) {
            foreach ($stage->dependencies() as $dependency) {
                if (! $this->registry->has($dependency)) {
                    throw new ImportRunnerException(
                        "Lo stage \"{$stage->name()}\" dichiara una dipendenza sconosciuta: \"{$dependency}\".",
                    );
                }
            }
        }

        /** @var array<int, ImportStage> $ordered */
        $ordered = [];
        $visited = [];
        $visiting = [];

        $visit = function (ImportStage $stage) use (&$visit, &$ordered, &$visited, &$visiting, $stages): void {
            $name = $stage->name();

            if (isset($visited[$name])) {
                return;
            }

            if (isset($visiting[$name])) {
                throw new ImportRunnerException("Dipendenza ciclica rilevata sullo stage \"{$name}\".");
            }

            $visiting[$name] = true;

            foreach ($stage->dependencies() as $dependency) {
                $visit($stages[$dependency]);
            }

            unset($visiting[$name]);
            $visited[$name] = true;
            $ordered[] = $stage;
        };

        foreach ($stages as $stage) {
            $visit($stage);
        }

        return $ordered;
    }

    /**
     * @param  array<int, ImportStage>  $stages
     */
    public function run(array $stages, ImportContext $context): ImportRun
    {
        $importRun = $context->importRun();
        /** @var array<string, array{read: int, created: int, updated: int, skipped: int, warnings: array<int, string>}> $report */
        $report = $importRun->stages ?? [];

        foreach ($stages as $stage) {
            try {
                $result = $stage->run($context);
            } catch (Throwable $exception) {
                $report[$stage->name()] = [
                    'read' => 0,
                    'created' => 0,
                    'updated' => 0,
                    'skipped' => 0,
                    'warnings' => ["Stage fallito: {$exception->getMessage()}"],
                ];

                $importRun->forceFill([
                    'stages' => $report,
                    'status' => ImportRunStatus::Failed,
                    'finished_at' => now(),
                ])->save();

                throw $exception;
            }

            $report[$stage->name()] = $result->toArray();
            $importRun->forceFill(['stages' => $report])->save();
        }

        $importRun->forceFill([
            'status' => ImportRunStatus::Completed,
            'finished_at' => now(),
        ])->save();

        return $importRun;
    }
}
