<?php

declare(strict_types=1);

namespace App\Import\Stages;

use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\Enums\TicketLogEvent;
use App\Import\Models\ImportMapping;
use App\Import\Stages\Contracts\ImportStage;
use Illuminate\Support\Facades\DB;

/**
 * Stage 11 (§11.4/§11.5 del PRD): importa `story_logs` (esclusi i log con sola
 * chiave `watch`, che alimentano `ticket_views`, US-209) → `ticket_logs`,
 * traducendo il JSON libero `changes` del v1 nelle colonne esplicite di v2.
 * Regole di priorità mutuamente esclusive: `status` presente vince su
 * `user_id` presente, che vince sul fallback generico `updated`.
 *
 * Idempotenza tramite `import_mappings` su `story_logs.id` (non una chiave
 * naturale della riga v2: `ticket_logs` non ha alcun vincolo unique applicabile,
 * a differenza delle pivot già importate dagli stage precedenti) — è il primo
 * stage a usare questa tabella, finora mai popolata.
 */
final class TicketLogsStage implements ImportStage
{
    public function name(): string
    {
        return 'ticket_logs';
    }

    public function dependencies(): array
    {
        return ['tickets', 'users'];
    }

    public function run(ImportContext $context): StageResult
    {
        $query = DB::connection('legacy')->table('story_logs')
            ->select(['id', 'story_id', 'user_id', 'viewed_at', 'changes', 'created_at', 'updated_at'])
            ->orderBy('id');

        if ($context->limit() !== null) {
            $query->limit($context->limit());
        }

        $rows = $query->get();

        $existingTicketIds = DB::table('tickets')->pluck('id')->all();
        $existingUserIds = DB::table('users')->pluck('id')->all();
        $mappedSourceKeys = array_flip(
            ImportMapping::query()
                ->where('source_table', 'story_logs')
                ->where('target_table', 'ticket_logs')
                ->pluck('source_key')
                ->all(),
        );

        $read = 0;
        $created = 0;
        $skipped = 0;

        $watchOnlyCount = 0;
        $orphanTicketCount = 0;
        $systemUserFallbackCount = 0;

        $lastStatusByTicket = [];
        $systemUserId = null;

        foreach ($rows as $row) {
            $read++;

            $changes = json_decode((string) $row->changes, true);
            $changes = is_array($changes) ? $changes : [];

            if (array_keys($changes) === ['watch']) {
                $watchOnlyCount++;
                $skipped++;

                continue;
            }

            $ticketId = (int) $row->story_id;

            if (! in_array($ticketId, $existingTicketIds, true)) {
                $orphanTicketCount++;
                $skipped++;

                continue;
            }

            [$event, $fromStatus, $toStatus, $changesToStore] = $this->resolveEvent($changes, $lastStatusByTicket, $ticketId);

            $userId = $row->user_id;
            $isSystem = false;

            if ($userId === null || ! in_array($userId, $existingUserIds, true)) {
                $systemUserId ??= User::system()->id;
                $userId = $systemUserId;
                $isSystem = true;
                $systemUserFallbackCount++;
            }

            $sourceKey = (string) $row->id;

            if (array_key_exists($sourceKey, $mappedSourceKeys)) {
                $skipped++;

                continue;
            }

            if ($context->isDryRun()) {
                continue;
            }

            $occurredAt = $row->viewed_at;

            $ticketLogId = DB::table('ticket_logs')->insertGetId([
                'ticket_id' => $ticketId,
                'user_id' => $userId,
                'event' => $event,
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'changes' => $changesToStore === null ? null : json_encode($changesToStore),
                'is_system' => $isSystem,
                'occurred_at' => $occurredAt,
                'created_at' => $row->created_at ?? $occurredAt,
                'updated_at' => $row->updated_at ?? $occurredAt,
            ]);

            ImportMapping::create([
                'source_table' => 'story_logs',
                'source_key' => $sourceKey,
                'target_table' => 'ticket_logs',
                'target_id' => $ticketLogId,
            ]);

            $mappedSourceKeys[$sourceKey] = true;
            $created++;
        }

        $warnings = $this->buildWarnings($watchOnlyCount, $orphanTicketCount, $systemUserFallbackCount);

        return new StageResult(read: $read, created: $created, skipped: $skipped, warnings: $warnings);
    }

    /**
     * @param  array<string, mixed>  $changes
     * @param  array<int, string>  $lastStatusByTicket
     * @return array{0: string, 1: ?string, 2: ?string, 3: ?array<string, mixed>}
     */
    private function resolveEvent(array $changes, array &$lastStatusByTicket, int $ticketId): array
    {
        if (array_key_exists('status', $changes) && $changes['status'] !== null) {
            $toStatus = (string) $changes['status'];
            $fromStatus = $lastStatusByTicket[$ticketId] ?? null;
            $lastStatusByTicket[$ticketId] = $toStatus;

            return [TicketLogEvent::StatusChanged->value, $fromStatus, $toStatus, null];
        }

        if (array_key_exists('user_id', $changes) && $changes['user_id'] !== null) {
            return [TicketLogEvent::Assigned->value, null, null, null];
        }

        $diff = $changes;
        unset($diff['updated_at'], $diff['watch']);

        if (array_key_exists('description', $diff)) {
            $diff['description'] = 'changed';
        }

        return [TicketLogEvent::Updated->value, null, null, $diff === [] ? null : $diff];
    }

    /**
     * @return array<int, string>
     */
    private function buildWarnings(int $watchOnlyCount, int $orphanTicketCount, int $systemUserFallbackCount): array
    {
        $warnings = [];

        if ($watchOnlyCount > 0) {
            $warnings[] = sprintf(
                '%d righe story_logs con sola chiave "watch" escluse: verranno importate dallo stage ticket_views (US-209).',
                $watchOnlyCount,
            );
        }

        if ($orphanTicketCount > 0) {
            $warnings[] = sprintf(
                '%d log scartati: ticket v1 inesistente in v2.',
                $orphanTicketCount,
            );
        }

        if ($systemUserFallbackCount > 0) {
            $warnings[] = sprintf(
                '%d log senza autore risolvibile in v2: attribuiti all\'utente di sistema (User::system()).',
                $systemUserFallbackCount,
            );
        }

        return $warnings;
    }
}
