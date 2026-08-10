<?php

declare(strict_types=1);

namespace App\Import\Stages;

use App\Import\Stages\Contracts\ImportStage;
use Illuminate\Support\Facades\DB;

/**
 * Stage 9a (§11.4 del PRD): importa la pivot ticket↔tag dal v1 (`taggables`,
 * solo lato `App\Models\Story` — il lato `Documentation` è già stato assorbito
 * come FK esplicita da `TagsStage`/US-204), idempotente su (ticket_id, tag_id),
 * non sull'`id` v1 della riga pivot (nessun significato applicativo da
 * preservare, stesso pattern di OrganizationMembersStage/US-203).
 */
final class TicketTagsStage implements ImportStage
{
    private const LEGACY_STORY_TAGGABLE_TYPE = 'App\\Models\\Story';

    public function name(): string
    {
        return 'ticket_tags';
    }

    public function dependencies(): array
    {
        return ['tickets', 'tags'];
    }

    public function run(ImportContext $context): StageResult
    {
        $query = DB::connection('legacy')->table('taggables')
            ->select(['tag_id', 'taggable_id', 'created_at', 'updated_at'])
            ->where('taggable_type', self::LEGACY_STORY_TAGGABLE_TYPE)
            ->orderBy('id');

        if ($context->limit() !== null) {
            $query->limit($context->limit());
        }

        $rows = $query->get();

        $read = 0;
        $created = 0;
        $skipped = 0;
        $warnings = [];

        $existingTicketIds = DB::table('tickets')->pluck('id')->all();
        $existingTagIds = DB::table('tags')->pluck('id')->all();

        foreach ($rows as $row) {
            $read++;

            if (! in_array($row->taggable_id, $existingTicketIds, true)) {
                $warnings[] = sprintf(
                    'Associazione v1 ticket #%d ↔ tag #%d scartata: ticket inesistente in v2.',
                    $row->taggable_id,
                    $row->tag_id,
                );
                $skipped++;

                continue;
            }

            if (! in_array($row->tag_id, $existingTagIds, true)) {
                $warnings[] = sprintf(
                    'Associazione v1 ticket #%d ↔ tag #%d scartata: tag inesistente in v2.',
                    $row->taggable_id,
                    $row->tag_id,
                );
                $skipped++;

                continue;
            }

            if ($context->isDryRun()) {
                continue;
            }

            $exists = DB::table('ticket_tag')
                ->where('ticket_id', $row->taggable_id)
                ->where('tag_id', $row->tag_id)
                ->exists();

            if ($exists) {
                $skipped++;

                continue;
            }

            DB::table('ticket_tag')->insert([
                'ticket_id' => $row->taggable_id,
                'tag_id' => $row->tag_id,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ]);
            $created++;
        }

        return new StageResult(read: $read, created: $created, skipped: $skipped, warnings: $warnings);
    }
}
