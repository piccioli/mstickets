<?php

declare(strict_types=1);

namespace App\Import\Stages;

use App\Import\Stages\Contracts\ImportStage;
use Illuminate\Support\Facades\DB;

/**
 * Stage 20 (§11.4 del PRD): importa la pivot `fundraising_project_partners` dal
 * v1, idempotente su (fundraising_project_id, user_id), non sull'`id` v1 della
 * riga pivot — stesso principio di `TicketParticipantsStage`/US-207.
 */
final class FundraisingPartnersStage implements ImportStage
{
    public function name(): string
    {
        return 'fundraising_partners';
    }

    public function dependencies(): array
    {
        return ['fundraising_projects', 'users'];
    }

    public function run(ImportContext $context): StageResult
    {
        $query = DB::connection('legacy')->table('fundraising_project_partners')
            ->select(['fundraising_project_id', 'user_id', 'created_at', 'updated_at'])
            ->orderBy('id');

        if ($context->limit() !== null) {
            $query->limit($context->limit());
        }

        $rows = $query->get();

        $read = 0;
        $created = 0;
        $skipped = 0;
        $warnings = [];

        $existingProjectIds = DB::table('fundraising_projects')->pluck('id')->all();
        $existingUserIds = DB::table('users')->pluck('id')->all();

        foreach ($rows as $row) {
            $read++;

            if (! in_array($row->fundraising_project_id, $existingProjectIds, true)) {
                $warnings[] = sprintf(
                    'Partner v1 progetto #%d ↔ utente #%d scartato: progetto fundraising inesistente in v2.',
                    $row->fundraising_project_id,
                    $row->user_id,
                );
                $skipped++;

                continue;
            }

            if (! in_array($row->user_id, $existingUserIds, true)) {
                $warnings[] = sprintf(
                    'Partner v1 progetto #%d ↔ utente #%d scartato: utente inesistente in v2.',
                    $row->fundraising_project_id,
                    $row->user_id,
                );
                $skipped++;

                continue;
            }

            if ($context->isDryRun()) {
                continue;
            }

            $exists = DB::table('fundraising_project_partners')
                ->where('fundraising_project_id', $row->fundraising_project_id)
                ->where('user_id', $row->user_id)
                ->exists();

            if ($exists) {
                $skipped++;

                continue;
            }

            DB::table('fundraising_project_partners')->insert([
                'fundraising_project_id' => $row->fundraising_project_id,
                'user_id' => $row->user_id,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ]);
            $created++;
        }

        return new StageResult(read: $read, created: $created, skipped: $skipped, warnings: $warnings);
    }
}
