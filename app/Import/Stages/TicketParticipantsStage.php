<?php

declare(strict_types=1);

namespace App\Import\Stages;

use App\Import\Stages\Contracts\ImportStage;
use Illuminate\Support\Facades\DB;

/**
 * Stage 9b (§11.4 del PRD): importa la pivot ticket↔partecipante esplicito dal
 * v1 (`story_participants`), idempotente su (ticket_id, user_id), non sull'`id`
 * v1 della riga pivot. Il conteggio importato è riportato in un warning
 * informativo (atteso vicino a zero, §6.1.7 del PRD principale), non un
 * compromesso: serve a v1:validate (US-216) per confermare l'aspettativa.
 */
final class TicketParticipantsStage implements ImportStage
{
    public function name(): string
    {
        return 'ticket_participants';
    }

    public function dependencies(): array
    {
        return ['tickets', 'users'];
    }

    public function run(ImportContext $context): StageResult
    {
        $query = DB::connection('legacy')->table('story_participants')
            ->select(['story_id', 'user_id', 'created_at', 'updated_at'])
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
        $existingUserIds = DB::table('users')->pluck('id')->all();

        foreach ($rows as $row) {
            $read++;

            if (! in_array($row->story_id, $existingTicketIds, true)) {
                $warnings[] = sprintf(
                    'Partecipazione v1 ticket #%d ↔ utente #%d scartata: ticket inesistente in v2.',
                    $row->story_id,
                    $row->user_id,
                );
                $skipped++;

                continue;
            }

            if (! in_array($row->user_id, $existingUserIds, true)) {
                $warnings[] = sprintf(
                    'Partecipazione v1 ticket #%d ↔ utente #%d scartata: utente inesistente in v2.',
                    $row->story_id,
                    $row->user_id,
                );
                $skipped++;

                continue;
            }

            if ($context->isDryRun()) {
                continue;
            }

            $exists = DB::table('ticket_participants')
                ->where('ticket_id', $row->story_id)
                ->where('user_id', $row->user_id)
                ->exists();

            if ($exists) {
                $skipped++;

                continue;
            }

            DB::table('ticket_participants')->insert([
                'ticket_id' => $row->story_id,
                'user_id' => $row->user_id,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ]);
            $created++;
        }

        if ($read > 0) {
            $warnings[] = sprintf(
                '%d partecipazioni esplicite lette dal v1 (atteso vicino a zero, §6.1.7 del PRD principale).',
                $read,
            );
        }

        return new StageResult(read: $read, created: $created, skipped: $skipped, warnings: $warnings);
    }
}
