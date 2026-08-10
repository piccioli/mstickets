<?php

declare(strict_types=1);

namespace App\Import\Stages;

use App\Import\Stages\Contracts\ImportStage;
use Illuminate\Support\Facades\DB;

/**
 * Stage 19 (§11.4 del PRD): importa `fundraising_projects` dal v1, `id` conservato,
 * mapping diretto colonna per colonna. `submission_date`/`decision_date` v1 →
 * `submitted_at`/`decided_at` v2 (§0.3 del PRD principale: stesso valore, colonna
 * rinominata). `lead_user_id`/`responsible_user_id` sono `NOT NULL` in v1 ma
 * nullable in v2 (`nullOnDelete`): un riferimento orfano viene azzerato e
 * segnalato (mai un crash da vincolo FK), stesso pattern di `resolveUserReference`
 * in `TicketsStage`/US-205. `fundraising_opportunity_id`/`created_by` restano
 * `NOT NULL` anche in v2: un riferimento orfano scarta l'intera riga, stesso
 * pattern di `FundraisingOpportunitiesStage`/US-213.
 */
final class FundraisingProjectsStage implements ImportStage
{
    /** @var array<int, string> */
    private const LEGACY_COLUMNS = [
        'id', 'title', 'fundraising_opportunity_id', 'lead_user_id', 'created_by',
        'responsible_user_id', 'description', 'status', 'requested_amount', 'approved_amount',
        'submission_date', 'decision_date', 'created_at', 'updated_at',
    ];

    public function name(): string
    {
        return 'fundraising_projects';
    }

    public function dependencies(): array
    {
        return ['fundraising_opportunities', 'users'];
    }

    public function run(ImportContext $context): StageResult
    {
        $query = DB::connection('legacy')->table('fundraising_projects')->select(self::LEGACY_COLUMNS)->orderBy('id');

        if ($context->limit() !== null) {
            $query->limit($context->limit());
        }

        $rows = $query->get();

        $read = 0;
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $orphanOpportunityCount = 0;
        $orphanCreatorCount = 0;
        $orphanLeadUserCount = 0;
        $orphanResponsibleUserCount = 0;

        $existingOpportunityIds = DB::table('fundraising_opportunities')->pluck('id')->all();
        $existingUserIds = DB::table('users')->pluck('id')->all();

        foreach ($rows as $row) {
            $read++;

            if (! in_array($row->fundraising_opportunity_id, $existingOpportunityIds, true)) {
                $orphanOpportunityCount++;
                $skipped++;

                continue;
            }

            if (! in_array($row->created_by, $existingUserIds, true)) {
                $orphanCreatorCount++;
                $skipped++;

                continue;
            }

            $leadUserId = $this->resolveUserReference($row->lead_user_id, $existingUserIds, $orphanLeadUserCount);
            $responsibleUserId = $this->resolveUserReference($row->responsible_user_id, $existingUserIds, $orphanResponsibleUserCount);

            $attributes = [
                'title' => $row->title,
                'fundraising_opportunity_id' => $row->fundraising_opportunity_id,
                'lead_user_id' => $leadUserId,
                'created_by' => $row->created_by,
                'responsible_user_id' => $responsibleUserId,
                'description' => $row->description,
                'status' => $row->status,
                'requested_amount' => $row->requested_amount,
                'approved_amount' => $row->approved_amount,
                'submitted_at' => $row->submission_date,
                'decided_at' => $row->decision_date,
                'updated_at' => $row->updated_at,
            ];

            if ($context->isDryRun()) {
                continue;
            }

            $existing = DB::table('fundraising_projects')->where('id', $row->id)->first();

            if ($existing === null) {
                DB::table('fundraising_projects')->insert([
                    'id' => $row->id,
                    'created_at' => $row->created_at,
                    ...$attributes,
                ]);
                $created++;

                continue;
            }

            if ($this->attributesDiffer($existing, $attributes)) {
                DB::table('fundraising_projects')->where('id', $row->id)->update($attributes);
                $updated++;
            } else {
                $skipped++;
            }
        }

        $warnings = [];

        if ($orphanOpportunityCount > 0) {
            $warnings[] = sprintf(
                '%d progetti fundraising v1 scartati: fundraising_opportunity_id inesistente in v2.',
                $orphanOpportunityCount,
            );
        }

        if ($orphanCreatorCount > 0) {
            $warnings[] = sprintf(
                '%d progetti fundraising v1 scartati: created_by inesistente in v2.',
                $orphanCreatorCount,
            );
        }

        if ($orphanLeadUserCount > 0) {
            $warnings[] = sprintf(
                '%d progetti fundraising v1 con lead_user_id inesistente in v2: azzerato.',
                $orphanLeadUserCount,
            );
        }

        if ($orphanResponsibleUserCount > 0) {
            $warnings[] = sprintf(
                '%d progetti fundraising v1 con responsible_user_id inesistente in v2: azzerato.',
                $orphanResponsibleUserCount,
            );
        }

        return new StageResult(read: $read, created: $created, updated: $updated, skipped: $skipped, warnings: $warnings);
    }

    /**
     * @param  array<int, int>  $existingUserIds
     */
    private function resolveUserReference(?int $userId, array $existingUserIds, int &$orphanCount): ?int
    {
        if ($userId === null) {
            return null;
        }

        if (! in_array($userId, $existingUserIds, true)) {
            $orphanCount++;

            return null;
        }

        return $userId;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function attributesDiffer(object $existing, array $attributes): bool
    {
        foreach ($attributes as $column => $value) {
            if ((string) ($existing->{$column} ?? '') !== (string) ($value ?? '')) {
                return true;
            }
        }

        return false;
    }
}
