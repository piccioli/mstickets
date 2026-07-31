<?php

declare(strict_types=1);

namespace App\Import\Stages;

use App\Import\Stages\Contracts\ImportStage;
use Illuminate\Support\Facades\DB;

/**
 * Stage `ticket_hierarchy` (§11.4 del PRD): ricostruisce `tickets.parent_id` da due
 * fonti v1 potenzialmente in conflitto, `stories.parent_id` (fonte primaria) e la
 * pivot `story_story` (eliminata in v2). Una riga `story_story` che il v1 non ha mai
 * riflesso in `stories.parent_id` viene applicata SOLO se non crea conflitto (cioè
 * solo quando `stories.parent_id` è null per quel figlio); un figlio con un padre
 * diverso tra le due fonti mantiene `stories.parent_id` e viene segnalato, mai un
 * merge silenzioso. Una catena risultante a 2+ livelli (vincolo v2: profondità
 * massima 1, `TicketParentDepthRule`) viene appiattita sul capostipite e segnalata.
 */
final class TicketHierarchyStage implements ImportStage
{
    public function name(): string
    {
        return 'ticket_hierarchy';
    }

    public function dependencies(): array
    {
        return ['tickets'];
    }

    public function run(ImportContext $context): StageResult
    {
        $query = DB::connection('legacy')->table('stories')->select(['id', 'parent_id'])->orderBy('id');

        if ($context->limit() !== null) {
            $query->limit($context->limit());
        }

        $stories = $query->get();
        $storyStoryRows = DB::connection('legacy')->table('story_story')->select(['parent_id', 'child_id'])->get();

        $primaryParentByChild = [];
        foreach ($stories as $story) {
            if ($story->parent_id !== null) {
                $primaryParentByChild[(int) $story->id] = (int) $story->parent_id;
            }
        }

        $pivotParentByChild = [];
        foreach ($storyStoryRows as $row) {
            $pivotParentByChild[(int) $row->child_id] = (int) $row->parent_id;
        }

        $conflictCount = 0;
        $rawParentByChild = [];

        foreach (array_unique([...array_keys($primaryParentByChild), ...array_keys($pivotParentByChild)]) as $childId) {
            $primary = $primaryParentByChild[$childId] ?? null;
            $pivot = $pivotParentByChild[$childId] ?? null;

            if ($primary !== null) {
                if ($pivot !== null && $pivot !== $primary) {
                    $conflictCount++;
                }

                $rawParentByChild[$childId] = $primary;

                continue;
            }

            $rawParentByChild[$childId] = $pivot;
        }

        $existingTicketIds = DB::table('tickets')->pluck('id')->all();

        $read = 0;
        $updated = 0;
        $skipped = 0;
        $flattenedCount = 0;
        $orphanParentCount = 0;

        foreach ($rawParentByChild as $childId => $parentId) {
            if (! in_array($childId, $existingTicketIds, true)) {
                continue;
            }

            $read++;

            [$resolvedParentId, $hops] = $this->resolveRoot($childId, $rawParentByChild);

            if ($hops > 1) {
                $flattenedCount++;
            }

            if (! in_array($resolvedParentId, $existingTicketIds, true)) {
                $orphanParentCount++;
                $resolvedParentId = null;
            }

            if ($context->isDryRun()) {
                continue;
            }

            $currentParentId = DB::table('tickets')->where('id', $childId)->value('parent_id');
            $currentParentId = $currentParentId === null ? null : (int) $currentParentId;

            if ($currentParentId === $resolvedParentId) {
                $skipped++;

                continue;
            }

            DB::table('tickets')->where('id', $childId)->update(['parent_id' => $resolvedParentId]);
            $updated++;
        }

        $warnings = $this->buildWarnings($conflictCount, $flattenedCount, $orphanParentCount);

        return new StageResult(read: $read, updated: $updated, skipped: $skipped, warnings: $warnings);
    }

    /**
     * Risale la catena di padri a partire dal padre diretto di `$childId`, finché il
     * padre corrente è a sua volta un figlio nella mappa (catena di 2+ livelli):
     * il risultato è il capostipite della catena, con il numero di salti percorsi
     * (1 = gerarchia già a un livello, nessun appiattimento necessario).
     *
     * @param  array<int, int>  $rawParentByChild
     * @return array{0: int, 1: int}
     */
    private function resolveRoot(int $childId, array $rawParentByChild): array
    {
        $current = $rawParentByChild[$childId];
        $hops = 1;
        $visited = [$childId => true];

        while (isset($rawParentByChild[$current]) && ! isset($visited[$current])) {
            $visited[$current] = true;
            $current = $rawParentByChild[$current];
            $hops++;
        }

        return [$current, $hops];
    }

    /**
     * @return array<int, string>
     */
    private function buildWarnings(int $conflictCount, int $flattenedCount, int $orphanParentCount): array
    {
        $warnings = [];

        if ($conflictCount > 0) {
            $warnings[] = sprintf(
                '%d ticket con padre diverso tra stories.parent_id e story_story: mantenuto stories.parent_id.',
                $conflictCount,
            );
        }

        if ($flattenedCount > 0) {
            $warnings[] = sprintf(
                '%d ticket con una gerarchia a più di un livello, appiattita sul capostipite della catena.',
                $flattenedCount,
            );
        }

        if ($orphanParentCount > 0) {
            $warnings[] = sprintf(
                '%d riferimenti a un ticket padre inesistente in v2, azzerati.',
                $orphanParentCount,
            );
        }

        return $warnings;
    }
}
