<?php

declare(strict_types=1);

namespace App\Import\Stages;

use App\Domain\Mail\Parsers\SubjectNormalizer;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Models\TicketLog;
use App\Domain\TimeTracking\Actions\RecalculateWorkedTime;
use App\Import\Stages\Concerns\GeneratesProvisionalSlugs;
use App\Import\Stages\Contracts\ImportStage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Stage `derive` (§11.4/§14 del PRD, ultimo stage dell'ETL): ricalcola da zero ogni
 * valore derivato di v2 mai importato direttamente dal v1 (A9, "sempre rigenerabile"),
 * dopo che tutte le entità primarie sono state importate dagli stage precedenti.
 * Bundle di 6 derivazioni indipendenti (non un mapping 1:1 da un'unica sorgente v1
 * come tutti gli stage precedenti): `StageResult` qui aggrega `read`/`updated`/`skipped`
 * attraverso derivazioni eterogenee (ticket, opportunità fundraising, tag/pagine,
 * thread email), il dettaglio per derivazione vive nei `warnings`, non nei contatori
 * — stessa deviazione consapevole già documentata per `TicketViewsStage`/US-209 e
 * `FundraisingScoresStage`/US-213.
 *
 * `--limit` si applica SOLO al ricalcolo ticket (timestamp status + ore lavorate,
 * la derivazione più costosa): le altre sotto-derivazioni sono manutenzioni globali
 * non incrementali riga-per-riga (rigenerare metà degli slug o metà delle sequenze
 * lascerebbe lo stato inconsistente), quindi operano sempre sull'intero dataset.
 *
 * `--dry-run` segue la stessa convenzione di ogni stage precedente: durante un
 * dry-run si incrementa solo `read`, mai `created`/`updated`/`skipped` (nessuna
 * scrittura simulata contata), coerente con `TicketHierarchyStage`/`FundraisingOpportunitiesStage`.
 */
final class DeriveStage implements ImportStage
{
    use GeneratesProvisionalSlugs;

    /** @var array<int, string> Tabelle v2 con `id` v1 conservato (§14 del PRD), da riallineare su Postgres. */
    private const SEQUENCE_TABLES = [
        'users', 'tickets', 'tags', 'documentation_pages', 'organizations',
        'activity_reports', 'fundraising_opportunities', 'fundraising_projects',
    ];

    public function name(): string
    {
        return 'derive';
    }

    public function dependencies(): array
    {
        return ['tickets', 'ticket_logs', 'tags', 'documentation', 'fundraising_opportunities', 'fundraising_scores', 'ticket_messages'];
    }

    public function run(ImportContext $context): StageResult
    {
        [$ticketRead, $ticketUpdated, $ticketSkipped, $timestampsBackfilled, $timestampsMissingLog] = $this->processTickets($context);
        [$fundraisingRead, $fundraisingUpdated, $fundraisingSkipped] = $this->recalculateFundraisingTotals($context);
        [$tagsRead, $tagsUpdated, $tagsSkipped] = $this->regenerateSlugs($context, 'tags', 'name');
        [$docsRead, $docsUpdated, $docsSkipped] = $this->regenerateSlugs($context, 'documentation_pages', 'title');
        [$threadsRead, $threadsCreated, $threadsUpdated, $threadsSkipped] = $this->generateEmailThreads($context);

        $sequencesRealigned = 0;

        if (! $context->isDryRun() && DB::connection()->getDriverName() === 'pgsql') {
            $sequencesRealigned = $this->realignSequences();
        }

        $warnings = $this->buildWarnings(
            $timestampsBackfilled,
            $timestampsMissingLog,
            $fundraisingUpdated,
            $tagsUpdated,
            $docsUpdated,
            $threadsCreated,
            $threadsUpdated,
            $sequencesRealigned,
        );

        return new StageResult(
            read: $ticketRead + $fundraisingRead + $tagsRead + $docsRead + $threadsRead,
            created: $threadsCreated,
            updated: $ticketUpdated + $fundraisingUpdated + $tagsUpdated + $docsUpdated + $threadsUpdated,
            skipped: $ticketSkipped + $fundraisingSkipped + $tagsSkipped + $docsSkipped + $threadsSkipped,
            warnings: $warnings,
        );
    }

    /**
     * Ricostruisce `tickets.released_at`/`done_at` mancanti dai `ticket_logs` già
     * importati (US-208) e ricalcola `worked_minutes`/`ticket_work_logs` per l'intero
     * storico riusando `RecalculateWorkedTime` (Fase 1, riuso diretto, nessuna seconda
     * implementazione qui). Entrambe le derivazioni condividono la stessa lettura dei
     * log del ticket, per questo vivono nello stesso loop invece di due passate separate.
     *
     * @return array{0: int, 1: int, 2: int, 3: int, 4: int} read, updated, skipped, timestamp backfillati, timestamp non ricostruibili
     */
    private function processTickets(ImportContext $context): array
    {
        $statusColumns = ['released_at' => TicketStatus::Released, 'done_at' => TicketStatus::Done];

        $query = Ticket::query()->orderBy('id');

        if ($context->limit() !== null) {
            $query->limit($context->limit());
        }

        $read = 0;
        $updated = 0;
        $skipped = 0;
        $timestampsBackfilled = 0;
        $timestampsMissingLog = 0;

        foreach ($query->get() as $ticket) {
            $read++;

            if ($context->isDryRun()) {
                continue;
            }

            $logs = $ticket->logs()->orderBy('occurred_at')->orderBy('id')->get();

            $timestampChanges = [];

            foreach ($statusColumns as $column => $status) {
                if ($ticket->status !== $status || $ticket->{$column} !== null) {
                    continue;
                }

                $match = $logs->last(fn (TicketLog $log): bool => $log->to_status === $status);

                if ($match === null) {
                    $timestampsMissingLog++;

                    continue;
                }

                $timestampChanges[$column] = $match->occurred_at;
            }

            if ($timestampChanges !== []) {
                $ticket->forceFill($timestampChanges)->save();
                $timestampsBackfilled += count($timestampChanges);
            }

            $workedMinutesBefore = $ticket->worked_minutes;
            RecalculateWorkedTime::run($ticket);
            $workedMinutesAfter = $ticket->fresh()->worked_minutes;

            if ($timestampChanges !== [] || $workedMinutesAfter !== $workedMinutesBefore) {
                $updated++;
            } else {
                $skipped++;
            }
        }

        return [$read, $updated, $skipped, $timestampsBackfilled, $timestampsMissingLog];
    }

    /**
     * Ricalcola `evaluation_positive_total`/`evaluation_negative_total`/`evaluation_total`
     * dalle righe `fundraising_evaluation_scores` (US-213): somma dei punteggi >= 0 per il
     * totale positivo, somma dei punteggi < 0 per il totale negativo (resta negativo),
     * totale = somma dei due. Nessuna Action dedicata esiste già per questa formula (a
     * differenza di `RecalculateWorkedTime`): stessa somma già usata nei seeder di
     * sviluppo/UAT, qui estratta come unico punto di calcolo per l'ETL. Un'opportunità
     * senza alcuna riga di punteggio non viene toccata (resta `null`, mai valutata).
     *
     * @return array{0: int, 1: int, 2: int} read, updated, skipped
     */
    private function recalculateFundraisingTotals(ImportContext $context): array
    {
        $opportunityIds = DB::table('fundraising_evaluation_scores')
            ->select('fundraising_opportunity_id')
            ->distinct()
            ->orderBy('fundraising_opportunity_id')
            ->pluck('fundraising_opportunity_id');

        $read = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($opportunityIds as $opportunityId) {
            $read++;

            if ($context->isDryRun()) {
                continue;
            }

            $scores = DB::table('fundraising_evaluation_scores')
                ->where('fundraising_opportunity_id', $opportunityId)
                ->pluck('score')
                ->map(static fn (mixed $score): int => (int) $score);

            $positiveTotal = (int) $scores->filter(static fn (int $score): bool => $score >= 0)->sum();
            $negativeTotal = (int) $scores->filter(static fn (int $score): bool => $score < 0)->sum();
            $total = $positiveTotal + $negativeTotal;

            $opportunity = DB::table('fundraising_opportunities')->where('id', $opportunityId)->first();

            if ($opportunity === null) {
                $skipped++;

                continue;
            }

            $needsUpdate = $opportunity->evaluation_positive_total === null
                || $opportunity->evaluation_negative_total === null
                || $opportunity->evaluation_total === null
                || (int) $opportunity->evaluation_positive_total !== $positiveTotal
                || (int) $opportunity->evaluation_negative_total !== $negativeTotal
                || (int) $opportunity->evaluation_total !== $total;

            if (! $needsUpdate) {
                $skipped++;

                continue;
            }

            DB::table('fundraising_opportunities')->where('id', $opportunityId)->update([
                'evaluation_positive_total' => $positiveTotal,
                'evaluation_negative_total' => $negativeTotal,
                'evaluation_total' => $total,
            ]);
            $updated++;
        }

        return [$read, $updated, $skipped];
    }

    /**
     * Rigenera da zero lo slug definitivo di ogni riga di `$table` (in ordine di `id`,
     * deterministico), con suffisso numerico sui duplicati (stesso trait
     * `GeneratesProvisionalSlugs` già usato per lo slug provvisorio da `TagsStage`/
     * `DocumentationStage`, US-204): a differenza del provvisorio, qui si ricomputano
     * TUTTI gli slug della tabella ogni esecuzione, non solo quelli mancanti.
     *
     * @return array{0: int, 1: int, 2: int} read, updated, skipped
     */
    private function regenerateSlugs(ImportContext $context, string $table, string $sourceColumn): array
    {
        $rows = DB::table($table)->orderBy('id')->get(['id', $sourceColumn, 'slug']);

        $seenSlugs = [];
        $read = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $read++;

            $newSlug = $this->uniqueSlug((string) ($row->{$sourceColumn} ?? ''), $seenSlugs);

            if ($context->isDryRun()) {
                continue;
            }

            if ($newSlug === $row->slug) {
                $skipped++;

                continue;
            }

            DB::table($table)->where('id', $row->id)->update(['slug' => $newSlug]);
            $updated++;
        }

        return [$read, $updated, $skipped];
    }

    /**
     * Genera un `email_thread` per ogni ticket con almeno un `ticket_messages` importato
     * (US-210), prerequisito di Fase 3 per far funzionare il threading anche quando un
     * cliente risponde a una vecchia email su un ticket storico. Nessuna Action/Observer
     * esiste già per questo (a differenza di `RecalculateWorkedTime`): logica nuova,
     * un solo thread per ticket (`firstOrCreate`-style su `ticket_id`, che non ha un
     * vincolo unique a livello di schema ma lo stage lo garantisce comunque).
     *
     * @return array{0: int, 1: int, 2: int, 3: int} read, created, updated, skipped
     */
    private function generateEmailThreads(ImportContext $context): array
    {
        $ticketIds = DB::table('ticket_messages')->select('ticket_id')->distinct()->orderBy('ticket_id')->pluck('ticket_id');

        $read = 0;
        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($ticketIds as $ticketId) {
            $read++;

            if ($context->isDryRun()) {
                continue;
            }

            $ticket = DB::table('tickets')->where('id', $ticketId)->first();

            if ($ticket === null) {
                $skipped++;

                continue;
            }

            $messages = DB::table('ticket_messages')
                ->leftJoin('users', 'users.id', '=', 'ticket_messages.author_id')
                ->where('ticket_messages.ticket_id', $ticketId)
                ->select(['ticket_messages.author_email', 'users.email as user_email', 'ticket_messages.posted_at'])
                ->get();

            $participants = $messages
                ->map(static fn (object $message): ?string => $message->user_email ?? $message->author_email)
                ->filter()
                ->unique()
                ->sort()
                ->values()
                ->all();

            $lastMessageAt = $messages->max('posted_at');
            $subjectNormalized = $this->normalizeSubject((string) $ticket->title);

            $existing = DB::table('email_threads')->where('ticket_id', $ticketId)->first();

            if ($existing === null) {
                DB::table('email_threads')->insert([
                    'ticket_id' => $ticketId,
                    'subject_normalized' => $subjectNormalized,
                    'participants' => json_encode($participants, JSON_THROW_ON_ERROR),
                    'last_message_at' => $lastMessageAt,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $created++;

                continue;
            }

            $existingParticipants = $existing->participants !== null
                ? json_decode((string) $existing->participants, true, flags: JSON_THROW_ON_ERROR)
                : [];
            sort($existingParticipants);

            $needsUpdate = $existing->subject_normalized !== $subjectNormalized
                || $existingParticipants !== $participants
                || ! $this->sameTimestamp($existing->last_message_at, $lastMessageAt);

            if (! $needsUpdate) {
                $skipped++;

                continue;
            }

            DB::table('email_threads')->where('ticket_id', $ticketId)->update([
                'subject_normalized' => $subjectNormalized,
                'participants' => json_encode($participants, JSON_THROW_ON_ERROR),
                'last_message_at' => $lastMessageAt,
            ]);
            $updated++;
        }

        return [$read, $created, $updated, $skipped];
    }

    /**
     * Normalizzazione minima del subject di un ticket per il matching euristico del
     * thread lato inbound (§7.3.6, US-306, livello 4): delega a
     * {@see SubjectNormalizer::normalizeForThreadMatching()}, la STESSA funzione usata
     * da `App\Domain\Mail\Actions\ResolveEmailThread` sul subject dell'email in arrivo —
     * un'unica normalizzazione condivisa sui due lati del confronto, mai due leggermente
     * diverse.
     */
    private function normalizeSubject(string $subject): string
    {
        return SubjectNormalizer::normalizeForThreadMatching($subject);
    }

    private function sameTimestamp(mixed $existing, mixed $new): bool
    {
        if ($existing === null || $new === null) {
            return $existing === $new;
        }

        return Carbon::parse($existing)->equalTo(Carbon::parse($new));
    }

    /**
     * Riallinea le sequenze PostgreSQL delle tabelle con `id` v1 conservato (§14 del
     * PRD): senza questo passo, il primo INSERT applicativo dopo l'import (un utente/
     * ticket/tag creato normalmente in v2, non dall'ETL) riprenderebbe la sequenza da 1
     * e andrebbe in conflitto con un id già occupato dai dati importati. Guardia sul driver
     * (`pgsql`, mai sqlite: nessun `pg_get_serial_sequence` equivalente) stesso idioma
     * già usato nelle migrazioni Postgres-only di questo repo.
     */
    private function realignSequences(): int
    {
        foreach (self::SEQUENCE_TABLES as $table) {
            DB::statement(
                "select setval(pg_get_serial_sequence('{$table}', 'id'), coalesce((select max(id) from {$table}), 1))"
            );
        }

        return count(self::SEQUENCE_TABLES);
    }

    /**
     * @return array<int, string>
     */
    private function buildWarnings(
        int $timestampsBackfilled,
        int $timestampsMissingLog,
        int $fundraisingUpdated,
        int $tagsUpdated,
        int $docsUpdated,
        int $threadsCreated,
        int $threadsUpdated,
        int $sequencesRealigned,
    ): array {
        $warnings = [];

        if ($timestampsBackfilled > 0) {
            $warnings[] = sprintf('%d timestamp released_at/done_at ricostruiti dai ticket_logs importati.', $timestampsBackfilled);
        }

        if ($timestampsMissingLog > 0) {
            $warnings[] = sprintf(
                '%d ticket in stato released/done senza un log di transizione corrispondente: timestamp non ricostruibile, rimasto null.',
                $timestampsMissingLog,
            );
        }

        if ($fundraisingUpdated > 0) {
            $warnings[] = sprintf('%d opportunità fundraising con totali di valutazione ricalcolati da fundraising_evaluation_scores.', $fundraisingUpdated);
        }

        if ($tagsUpdated > 0) {
            $warnings[] = sprintf('%d slug definitivi rigenerati su tags.', $tagsUpdated);
        }

        if ($docsUpdated > 0) {
            $warnings[] = sprintf('%d slug definitivi rigenerati su documentation_pages.', $docsUpdated);
        }

        if ($threadsCreated > 0 || $threadsUpdated > 0) {
            $warnings[] = sprintf('%d email_threads creati, %d aggiornati per ticket con conversazione importata.', $threadsCreated, $threadsUpdated);
        }

        if ($sequencesRealigned > 0) {
            $warnings[] = sprintf('Sequenze PostgreSQL riallineate per %d tabelle con id v1 conservato.', $sequencesRealigned);
        }

        return $warnings;
    }
}
