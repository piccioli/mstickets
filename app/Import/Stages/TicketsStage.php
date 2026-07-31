<?php

declare(strict_types=1);

namespace App\Import\Stages;

use App\Domain\Ticketing\Enums\TicketPriority;
use App\Domain\Ticketing\Enums\TicketType;
use App\Import\Stages\Contracts\ImportStage;
use Illuminate\Support\Facades\DB;

/**
 * Stage 7 (§11.4 del PRD): importa `stories` → `tickets`, l'entità centrale del sistema.
 * Colonne v1 esplicitamente fuori mapping: `epic_id`, `project_id`, `history_log`,
 * `customer_request` (diventa `ticket_messages`, US-210), `hours` (`worked_minutes` è
 * ricalcolato da zero in `derive`, US-215) e `parent_id` (gerarchia ricostruita da
 * `ticket_hierarchy`, US-206).
 *
 * `status_changed_at`/`previous_status` sono derivati dai `story_logs` UNA TANTUM,
 * solo al primo inserimento del ticket: dopo l'import restano mantenuti
 * dall'applicazione (§11.4 nota), quindi una riesecuzione idempotente dello stage su
 * un ticket già presente non li ricalcola né li sovrascrive.
 */
final class TicketsStage implements ImportStage
{
    /** @var array<int, string> */
    private const LEGACY_COLUMNS = [
        'id', 'name', 'description', 'status', 'type', 'priority',
        'user_id', 'creator_id', 'tester_id', 'test_dev', 'test_prod',
        'estimated_hours', 'fundraising_project_id', 'waiting_reason', 'problem_reason',
        'released_at', 'done_at', 'created_at', 'updated_at',
    ];

    /** @var array<int, string> */
    private const STUCK_STATUSES = ['waiting', 'problem'];

    public function name(): string
    {
        return 'tickets';
    }

    public function dependencies(): array
    {
        return ['users'];
    }

    public function run(ImportContext $context): StageResult
    {
        $query = DB::connection('legacy')->table('stories')->select(self::LEGACY_COLUMNS)->orderBy('id');

        if ($context->limit() !== null) {
            $query->limit($context->limit());
        }

        $rows = $query->get();

        $statusLogsByStory = $this->statusLogsByStory($rows->pluck('id')->all());
        $existingUserIds = DB::table('users')->pluck('id')->all();

        $read = 0;
        $created = 0;
        $updated = 0;
        $skipped = 0;

        $unrecognizedTypes = [];
        $unrecognizedPriorityCount = 0;
        $statusChangedAtFallbackCount = 0;
        $previousStatusFallbackCount = 0;
        $orphanUserReferenceCount = 0;

        foreach ($rows as $row) {
            $read++;

            $type = $this->normalizeType($row->type, $unrecognizedTypes);
            $priority = $this->normalizePriority((int) $row->priority, $unrecognizedPriorityCount);
            $requesterId = $this->resolveUserReference($row->creator_id, $existingUserIds, $orphanUserReferenceCount);
            $assigneeId = $this->resolveUserReference($row->user_id, $existingUserIds, $orphanUserReferenceCount);
            $testerId = $this->resolveUserReference($row->tester_id, $existingUserIds, $orphanUserReferenceCount);

            if ($context->isDryRun()) {
                continue;
            }

            $attributes = [
                'title' => $row->name,
                'description' => $row->description,
                'status' => $row->status,
                'type' => $type,
                'priority' => $priority,
                'requester_id' => $requesterId,
                'assignee_id' => $assigneeId,
                'tester_id' => $testerId,
                'staging_url' => $row->test_dev,
                'production_url' => $row->test_prod,
                'estimated_hours' => $row->estimated_hours,
                'fundraising_project_id' => $row->fundraising_project_id,
                'waiting_reason' => $row->waiting_reason,
                'problem_reason' => $row->problem_reason,
                'released_at' => $row->released_at,
                'done_at' => $row->done_at,
                'updated_at' => $row->updated_at,
            ];

            $existing = DB::table('tickets')->where('id', $row->id)->first();

            if ($existing === null) {
                $statusLogs = $statusLogsByStory[$row->id] ?? [];

                [$statusChangedAt, $usedFallback] = $this->statusChangedAt($statusLogs, $row);
                if ($usedFallback) {
                    $statusChangedAtFallbackCount++;
                }

                $previousStatus = null;
                if (in_array($row->status, self::STUCK_STATUSES, true)) {
                    [$previousStatus, $usedFallback] = $this->previousStatus($statusLogs);
                    if ($usedFallback) {
                        $previousStatusFallbackCount++;
                    }
                }

                DB::table('tickets')->insert([
                    'id' => $row->id,
                    'parent_id' => null,
                    'worked_minutes' => 0,
                    'status_changed_at' => $statusChangedAt,
                    'previous_status' => $previousStatus,
                    'created_at' => $row->created_at,
                    ...$attributes,
                ]);
                $created++;

                continue;
            }

            if ($this->attributesDiffer($existing, $attributes)) {
                DB::table('tickets')->where('id', $row->id)->update($attributes);
                $updated++;
            } else {
                $skipped++;
            }
        }

        $warnings = $this->buildWarnings(
            $unrecognizedTypes,
            $unrecognizedPriorityCount,
            $statusChangedAtFallbackCount,
            $previousStatusFallbackCount,
            $orphanUserReferenceCount,
        );

        return new StageResult(read: $read, created: $created, updated: $updated, skipped: $skipped, warnings: $warnings);
    }

    /**
     * @param  array<int, string>  $unrecognizedRawValues
     */
    private function normalizeType(?string $raw, array &$unrecognizedRawValues): string
    {
        $normalized = $raw === null ? '' : strtolower(trim((string) preg_replace('/\s+/', ' ', $raw)));

        $mapped = match ($normalized) {
            'bug' => TicketType::Bug->value,
            'feature' => TicketType::Feature->value,
            'help desk', 'helpdesk' => TicketType::Helpdesk->value,
            'scrum' => TicketType::Scrum->value,
            default => null,
        };

        if ($mapped !== null) {
            return $mapped;
        }

        $unrecognizedRawValues[] = $raw ?? '(null)';

        return TicketType::Helpdesk->value;
    }

    private function normalizePriority(int $raw, int &$unrecognizedCount): string
    {
        $mapped = match ($raw) {
            1 => TicketPriority::Low->value,
            2 => TicketPriority::Medium->value,
            3 => TicketPriority::High->value,
            default => null,
        };

        if ($mapped !== null) {
            return $mapped;
        }

        $unrecognizedCount++;

        return TicketPriority::Low->value;
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
     * Legge `story_logs.changes` per gli story indicati e ne estrae, in ordine
     * cronologico (`id` crescente), la sola sequenza di valori `status` presenti nel
     * JSON — è la stessa fonte che alimenterà `ticket_logs`/`ticket_views` (US-208/209),
     * qui letta in sola lettura per derivare `status_changed_at`/`previous_status`.
     *
     * @param  array<int, int>  $storyIds
     * @return array<int, array<int, array{status: string, viewed_at: string}>>
     */
    private function statusLogsByStory(array $storyIds): array
    {
        if ($storyIds === []) {
            return [];
        }

        $logs = DB::connection('legacy')->table('story_logs')
            ->select(['story_id', 'changes', 'viewed_at'])
            ->whereIn('story_id', $storyIds)
            ->orderBy('id')
            ->get();

        $byStory = [];

        foreach ($logs as $log) {
            $changes = json_decode((string) $log->changes, true);

            if (! is_array($changes) || ! array_key_exists('status', $changes) || $changes['status'] === null) {
                continue;
            }

            $byStory[$log->story_id][] = [
                'status' => (string) $changes['status'],
                'viewed_at' => $log->viewed_at,
            ];
        }

        return $byStory;
    }

    /**
     * @param  array<int, array{status: string, viewed_at: string}>  $statusLogs
     * @return array{0: string, 1: bool}
     */
    private function statusChangedAt(array $statusLogs, object $row): array
    {
        if ($statusLogs !== []) {
            return [end($statusLogs)['viewed_at'], false];
        }

        return [$row->updated_at ?? $row->created_at, true];
    }

    /**
     * @param  array<int, array{status: string, viewed_at: string}>  $statusLogs
     * @return array{0: string, 1: bool}
     */
    private function previousStatus(array $statusLogs): array
    {
        for ($i = count($statusLogs) - 1; $i >= 0; $i--) {
            if (! in_array($statusLogs[$i]['status'], self::STUCK_STATUSES, true)) {
                return [$statusLogs[$i]['status'], false];
            }
        }

        return ['new', true];
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

    /**
     * @param  array<int, string>  $unrecognizedTypes
     * @return array<int, string>
     */
    private function buildWarnings(
        array $unrecognizedTypes,
        int $unrecognizedPriorityCount,
        int $statusChangedAtFallbackCount,
        int $previousStatusFallbackCount,
        int $orphanUserReferenceCount,
    ): array {
        $warnings = [];

        if ($unrecognizedTypes !== []) {
            $distinct = array_values(array_unique($unrecognizedTypes));
            $warnings[] = sprintf(
                '%d ticket con tipo v1 non riconosciuto, normalizzato a "helpdesk" (valori: %s).',
                count($unrecognizedTypes),
                implode(', ', array_map(static fn (string $value): string => sprintf('"%s"', $value), $distinct)),
            );
        }

        if ($unrecognizedPriorityCount > 0) {
            $warnings[] = sprintf(
                '%d ticket con priorità v1 fuori dai valori noti (1/2/3), normalizzata a "low".',
                $unrecognizedPriorityCount,
            );
        }

        if ($statusChangedAtFallbackCount > 0) {
            $warnings[] = sprintf(
                '%d ticket senza un cambio di stato ricostruibile da story_logs: status_changed_at derivato da stories.updated_at (fallback).',
                $statusChangedAtFallbackCount,
            );
        }

        if ($previousStatusFallbackCount > 0) {
            $warnings[] = sprintf(
                '%d ticket in waiting/problem senza uno stato precedente ricostruibile da story_logs: previous_status impostato a "new" (fallback).',
                $previousStatusFallbackCount,
            );
        }

        if ($orphanUserReferenceCount > 0) {
            $warnings[] = sprintf(
                '%d riferimenti utente (richiedente/assegnatario/tester) azzerati: utente v1 inesistente in v2.',
                $orphanUserReferenceCount,
            );
        }

        return $warnings;
    }
}
