<?php

declare(strict_types=1);

namespace App\Import\Stages;

use App\Domain\Fundraising\Enums\FundraisingEvaluationCriterion;
use App\Import\Stages\Contracts\ImportStage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Stage 18 (§11.4/§6.6.2 del PRD): normalizza le colonne v1 `evaluation_<criterio>_score`
 * di `fundraising_opportunities` in righe `fundraising_evaluation_scores` (una per
 * criterio non nullo). Il catalogo `FundraisingEvaluationCriterion` è l'UNICA fonte
 * dei nomi di colonna v1 attesi (`evaluation_{value}_score` / `evaluation_{value}_description`):
 * aggiungere un criterio al catalogo (US-011/§9) significa automaticamente cercare
 * la sua colonna v1 corrispondente, senza toccare questo stage.
 *
 * IMPORTANTE (scoperta in questa story, vedi CLAUDE.md): il dump reale di produzione
 * (v1dumps/production_dump_20260726_101158.sql) non ha MAI avuto nessuna colonna
 * `evaluation_*` su `fundraising_opportunities` (nessuna migrazione v1 le crea, `grep`
 * sul dump non compresso conferma) — la griglia di valutazione §6.6.2 non risulta mai
 * stata effettivamente usata in produzione. Per questo lo stage rileva DINAMICAMENTE
 * quali colonne esistono davvero sullo schema `legacy` (`Schema::hasColumn`, mai un
 * `select` letterale su colonne che potrebbero non esistere) e produce zero righe
 * contro il dump reale: non un bug, un fatto sui dati da confermare col committente
 * al checkpoint di fine fase (US-219), non nascosto da un errore SQL.
 *
 * Deviazione consapevole dalla semantica 1:1 `read`/`created`/`skipped` degli altri
 * stage (stessa nota già presente per TicketViewsStage/US-209, mapping inverso qui:
 * una riga v1 → fino a N righe v2): `read` conta le CELLE criterio esaminate
 * (opportunità × colonne evaluation_*_score effettivamente presenti nello schema
 * legacy), non le opportunità né le righe v2 create.
 */
final class FundraisingScoresStage implements ImportStage
{
    public function name(): string
    {
        return 'fundraising_scores';
    }

    public function dependencies(): array
    {
        return ['fundraising_opportunities'];
    }

    public function run(ImportContext $context): StageResult
    {
        [$scoreColumns, $descriptionColumns] = $this->detectLegacyColumns();

        if ($scoreColumns === []) {
            return new StageResult(read: 0, created: 0, updated: 0, skipped: 0, warnings: [
                'Nessuna colonna evaluation_*_score presente sullo schema legacy: la griglia di valutazione '.
                '§6.6.2 non risulta mai popolata in v1, nessuna riga fundraising_evaluation_scores generata.',
            ]);
        }

        $selectColumns = ['id', ...array_values($scoreColumns), ...array_values($descriptionColumns)];

        $query = DB::connection('legacy')->table('fundraising_opportunities')->select($selectColumns)->orderBy('id');

        if ($context->limit() !== null) {
            $query->limit($context->limit());
        }

        $rows = $query->get();

        $existingOpportunityIds = DB::table('fundraising_opportunities')->pluck('id')->all();

        /** @var array<int, string> $existingKeys "opportunity_id:criterion_key" già importati */
        $existingKeys = DB::table('fundraising_evaluation_scores')
            ->get(['fundraising_opportunity_id', 'criterion_key'])
            ->map(fn (object $row): string => "{$row->fundraising_opportunity_id}:{$row->criterion_key}")
            ->all();

        $read = 0;
        $created = 0;
        $skipped = 0;
        $clampedCount = 0;
        $orphanOpportunityCount = 0;

        foreach ($rows as $row) {
            if (! in_array($row->id, $existingOpportunityIds, true)) {
                $orphanOpportunityCount++;

                continue;
            }

            foreach ($scoreColumns as $criterionValue => $column) {
                $rawValue = $row->{$column} ?? null;

                if ($rawValue === null) {
                    continue;
                }

                $read++;

                $key = "{$row->id}:{$criterionValue}";

                if (in_array($key, $existingKeys, true)) {
                    $skipped++;

                    continue;
                }

                if ($context->isDryRun()) {
                    continue;
                }

                $criterion = FundraisingEvaluationCriterion::from($criterionValue);
                $score = (int) $rawValue;
                $clampedScore = max($criterion->min(), min($criterion->max(), $score));

                if ($clampedScore !== $score) {
                    $clampedCount++;
                }

                $descriptionColumn = $descriptionColumns[$criterionValue] ?? null;
                $notes = $descriptionColumn !== null ? ($row->{$descriptionColumn} ?? null) : null;

                DB::table('fundraising_evaluation_scores')->insert([
                    'fundraising_opportunity_id' => $row->id,
                    'criterion_key' => $criterionValue,
                    'score' => $clampedScore,
                    'notes' => $notes,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $created++;
            }
        }

        $warnings = [];

        if ($clampedCount > 0) {
            $warnings[] = sprintf('%d punteggi fuori range clampati al range del catalogo criteri (§6.6.2).', $clampedCount);
        }

        if ($orphanOpportunityCount > 0) {
            $warnings[] = sprintf(
                '%d opportunità v1 con colonne evaluation_* scartate: opportunità inesistente in v2 (probabile scarto per created_by/responsible_user_id orfano in FundraisingOpportunitiesStage).',
                $orphanOpportunityCount,
            );
        }

        return new StageResult(read: $read, created: $created, updated: 0, skipped: $skipped, warnings: $warnings);
    }

    /**
     * @return array{0: array<string, string>, 1: array<string, string>}
     */
    private function detectLegacyColumns(): array
    {
        $scoreColumns = [];
        $descriptionColumns = [];

        foreach (FundraisingEvaluationCriterion::cases() as $criterion) {
            $scoreColumn = "evaluation_{$criterion->value}_score";

            if (Schema::connection('legacy')->hasColumn('fundraising_opportunities', $scoreColumn)) {
                $scoreColumns[$criterion->value] = $scoreColumn;
            }

            $descriptionColumn = "evaluation_{$criterion->value}_description";

            if (Schema::connection('legacy')->hasColumn('fundraising_opportunities', $descriptionColumn)) {
                $descriptionColumns[$criterion->value] = $descriptionColumn;
            }
        }

        return [$scoreColumns, $descriptionColumns];
    }
}
