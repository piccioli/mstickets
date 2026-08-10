<?php

declare(strict_types=1);

namespace App\Import\Stages;

use App\Import\Stages\Contracts\ImportStage;
use Illuminate\Support\Facades\DB;

/**
 * Stage 16 (§11.4 del PRD): importa la pivot report attività↔ticket dal v1
 * (`activity_report_story`, `story_id` → `ticket_id` perché l'id è
 * conservato da TicketsStage/US-205) nella tabella v2 `activity_report_ticket`,
 * idempotente su (activity_report_id, ticket_id) — non sull'`id` v1 della riga
 * pivot, che qui non ha alcun significato applicativo da preservare (stesso
 * pattern di OrganizationMembersStage/US-203 e TicketTagsStage/US-207).
 */
final class ActivityReportTicketsStage implements ImportStage
{
    public function name(): string
    {
        return 'activity_report_tickets';
    }

    public function dependencies(): array
    {
        return ['activity_reports', 'tickets'];
    }

    public function run(ImportContext $context): StageResult
    {
        $query = DB::connection('legacy')->table('activity_report_story')
            ->select(['activity_report_id', 'story_id', 'created_at', 'updated_at'])
            ->orderBy('id');

        if ($context->limit() !== null) {
            $query->limit($context->limit());
        }

        $rows = $query->get();

        $read = 0;
        $created = 0;
        $skipped = 0;
        $warnings = [];

        $existingActivityReportIds = DB::table('activity_reports')->pluck('id')->all();
        $existingTicketIds = DB::table('tickets')->pluck('id')->all();

        foreach ($rows as $row) {
            $read++;

            if (! in_array($row->activity_report_id, $existingActivityReportIds, true)) {
                $warnings[] = sprintf(
                    'Associazione v1 report attività #%d ↔ ticket #%d scartata: report attività inesistente in v2.',
                    $row->activity_report_id,
                    $row->story_id,
                );
                $skipped++;

                continue;
            }

            if (! in_array($row->story_id, $existingTicketIds, true)) {
                $warnings[] = sprintf(
                    'Associazione v1 report attività #%d ↔ ticket #%d scartata: ticket inesistente in v2.',
                    $row->activity_report_id,
                    $row->story_id,
                );
                $skipped++;

                continue;
            }

            if ($context->isDryRun()) {
                continue;
            }

            $exists = DB::table('activity_report_ticket')
                ->where('activity_report_id', $row->activity_report_id)
                ->where('ticket_id', $row->story_id)
                ->exists();

            if ($exists) {
                $skipped++;

                continue;
            }

            DB::table('activity_report_ticket')->insert([
                'activity_report_id' => $row->activity_report_id,
                'ticket_id' => $row->story_id,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ]);
            $created++;
        }

        return new StageResult(read: $read, created: $created, skipped: $skipped, warnings: $warnings);
    }
}
