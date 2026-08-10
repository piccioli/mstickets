<?php

declare(strict_types=1);

namespace App\Import\Stages;

use App\Import\Stages\Contracts\ImportStage;
use Illuminate\Support\Facades\DB;

/**
 * Stage 4 (§11.4 del PRD): importa la pivot utente↔organizzazione dal v1
 * (tabella `organization_user` in entrambe le versioni), idempotente sulla
 * chiave (organization_id, user_id) — non sull'`id` v1, che qui non ha alcun
 * significato applicativo da preservare.
 */
final class OrganizationMembersStage implements ImportStage
{
    public function name(): string
    {
        return 'organization_members';
    }

    public function dependencies(): array
    {
        return ['users', 'organizations'];
    }

    public function run(ImportContext $context): StageResult
    {
        $query = DB::connection('legacy')->table('organization_user')
            ->select(['organization_id', 'user_id', 'created_at', 'updated_at'])
            ->orderBy('id');

        if ($context->limit() !== null) {
            $query->limit($context->limit());
        }

        $rows = $query->get();

        $read = 0;
        $created = 0;
        $skipped = 0;
        $warnings = [];

        $existingOrganizationIds = DB::table('organizations')->pluck('id')->all();
        $existingUserIds = DB::table('users')->pluck('id')->all();

        foreach ($rows as $row) {
            $read++;

            if (! in_array($row->organization_id, $existingOrganizationIds, true)) {
                $warnings[] = sprintf(
                    'Appartenenza v1 organizzazione #%d ↔ utente #%d scartata: organizzazione inesistente in v2.',
                    $row->organization_id,
                    $row->user_id,
                );
                $skipped++;

                continue;
            }

            if (! in_array($row->user_id, $existingUserIds, true)) {
                $warnings[] = sprintf(
                    'Appartenenza v1 organizzazione #%d ↔ utente #%d scartata: utente inesistente in v2.',
                    $row->organization_id,
                    $row->user_id,
                );
                $skipped++;

                continue;
            }

            if ($context->isDryRun()) {
                continue;
            }

            $exists = DB::table('organization_user')
                ->where('organization_id', $row->organization_id)
                ->where('user_id', $row->user_id)
                ->exists();

            if ($exists) {
                $skipped++;

                continue;
            }

            DB::table('organization_user')->insert([
                'organization_id' => $row->organization_id,
                'user_id' => $row->user_id,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ]);
            $created++;
        }

        return new StageResult(read: $read, created: $created, skipped: $skipped, warnings: $warnings);
    }
}
