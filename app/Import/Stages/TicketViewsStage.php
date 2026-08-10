<?php

declare(strict_types=1);

namespace App\Import\Stages;

use App\Import\Stages\Contracts\ImportStage;
use Illuminate\Support\Facades\DB;

/**
 * Stage 12 (§11.4/§11.5 del PRD): separa dai `story_logs` (stessa sorgente di
 * `TicketLogsStage`, US-208, filtro mutuamente esclusivo) le righe con SOLA
 * chiave `watch` verso la tabella dedicata `ticket_views` di v2, aggregando
 * per (ticket_id, user_id, giorno) in un unico `view_count`/`last_viewed_at`
 * invece di una riga per visualizzazione.
 *
 * Idempotenza sul vincolo unique applicativo (ticket_id, user_id, viewed_on),
 * già esistente in v2 da Fase 0/1: un gruppo il cui `ticket_views` esiste già
 * viene saltato per intero (mai un secondo insert/aggiornamento), stesso
 * principio delle pivot semplici (OrganizationMembersStage, US-203).
 */
final class TicketViewsStage implements ImportStage
{
    public function name(): string
    {
        return 'ticket_views';
    }

    public function dependencies(): array
    {
        return ['tickets', 'users'];
    }

    public function run(ImportContext $context): StageResult
    {
        $query = DB::connection('legacy')->table('story_logs')
            ->select(['id', 'story_id', 'user_id', 'viewed_at', 'changes'])
            ->orderBy('id');

        if ($context->limit() !== null) {
            $query->limit($context->limit());
        }

        $rows = $query->get();

        $existingTicketIds = DB::table('tickets')->pluck('id')->all();
        $existingUserIds = DB::table('users')->pluck('id')->all();

        $read = 0;
        $created = 0;
        $skipped = 0;

        $notWatchOnlyCount = 0;
        $orphanTicketCount = 0;
        $orphanUserCount = 0;

        /** @var array<string, array{ticket_id: int, user_id: int, viewed_on: string, view_count: int, last_viewed_at: string}> $groups */
        $groups = [];

        foreach ($rows as $row) {
            $read++;

            $changes = json_decode((string) $row->changes, true);
            $changes = is_array($changes) ? $changes : [];

            if (array_keys($changes) !== ['watch']) {
                $notWatchOnlyCount++;
                $skipped++;

                continue;
            }

            $ticketId = (int) $row->story_id;
            $userId = (int) $row->user_id;

            if (! in_array($ticketId, $existingTicketIds, true)) {
                $orphanTicketCount++;
                $skipped++;

                continue;
            }

            if (! in_array($userId, $existingUserIds, true)) {
                $orphanUserCount++;
                $skipped++;

                continue;
            }

            $viewedAt = (string) $row->viewed_at;
            $viewedOn = substr($viewedAt, 0, 10);
            $key = "{$ticketId}|{$userId}|{$viewedOn}";

            if (! isset($groups[$key])) {
                $groups[$key] = [
                    'ticket_id' => $ticketId,
                    'user_id' => $userId,
                    'viewed_on' => $viewedOn,
                    'view_count' => 0,
                    'last_viewed_at' => $viewedAt,
                ];
            }

            $groups[$key]['view_count']++;

            if ($viewedAt > $groups[$key]['last_viewed_at']) {
                $groups[$key]['last_viewed_at'] = $viewedAt;
            }
        }

        foreach ($groups as $group) {
            $exists = DB::table('ticket_views')
                ->where('ticket_id', $group['ticket_id'])
                ->where('user_id', $group['user_id'])
                ->whereDate('viewed_on', $group['viewed_on'])
                ->exists();

            if ($exists) {
                $skipped += $group['view_count'];

                continue;
            }

            if ($context->isDryRun()) {
                continue;
            }

            DB::table('ticket_views')->insert([
                'ticket_id' => $group['ticket_id'],
                'user_id' => $group['user_id'],
                'viewed_on' => $group['viewed_on'],
                'last_viewed_at' => $group['last_viewed_at'],
                'view_count' => $group['view_count'],
                'created_at' => $group['last_viewed_at'],
                'updated_at' => $group['last_viewed_at'],
            ]);

            $created++;
        }

        $warnings = $this->buildWarnings($notWatchOnlyCount, $orphanTicketCount, $orphanUserCount);

        return new StageResult(read: $read, created: $created, skipped: $skipped, warnings: $warnings);
    }

    /**
     * @return array<int, string>
     */
    private function buildWarnings(int $notWatchOnlyCount, int $orphanTicketCount, int $orphanUserCount): array
    {
        $warnings = [];

        if ($notWatchOnlyCount > 0) {
            $warnings[] = sprintf(
                '%d righe story_logs escluse: non hanno sola chiave "watch" (importate dallo stage ticket_logs, US-208).',
                $notWatchOnlyCount,
            );
        }

        if ($orphanTicketCount > 0) {
            $warnings[] = sprintf(
                '%d visualizzazioni scartate: ticket v1 inesistente in v2.',
                $orphanTicketCount,
            );
        }

        if ($orphanUserCount > 0) {
            $warnings[] = sprintf(
                '%d visualizzazioni scartate: utente v1 inesistente in v2.',
                $orphanUserCount,
            );
        }

        return $warnings;
    }
}
