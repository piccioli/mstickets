<?php

declare(strict_types=1);

namespace App\Import\Stages;

use App\Domain\Ticketing\Enums\TicketMessageChannel;
use App\Domain\Ticketing\Enums\TicketMessageVisibility;
use App\Domain\Ticketing\Support\TicketMessageSanitizer;
use App\Import\Anonymization\Anonymizer;
use App\Import\Models\ImportMapping;
use App\Import\Parsers\CustomerRequestParser;
use App\Import\Parsers\ParsedTicketMessage;
use App\Import\Stages\Contracts\ImportStage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Stage 13 (§11.4/§11.5 del PRD, il più delicato): scompone `stories.customer_request`
 * nei messaggi strutturati di `ticket_messages` v2, tramite {@see CustomerRequestParser}
 * (parsing puro/testabile separato dalla risoluzione contro il DB v2 fatta qui).
 *
 * Risoluzione dell'autore: il messaggio "originale" (il contenuto con cui il ticket è
 * stato creato, sempre il più vecchio) eredita `tickets.requester_id`, già risolto da
 * `TicketsStage`; i blocchi di risposta portano solo un nome visualizzato nel v1 (mai
 * un id/email), risolto per corrispondenza case-insensitive su `users.name` **solo se
 * univoca** — un nome ambiguo o senza corrispondenza resta `author_id = null`
 * ("storico importato", come da AC).
 *
 * `posted_at`: il blocco originale usa direttamente `tickets.created_at` (derivazione
 * diretta, non un fallback); solo se un timestamp di un blocco di risposta non è
 * ricostruibile (data malformata, mai osservato nel dump reale ma gestito
 * difensivamente) si applica la distribuzione monotona tra `created_at`/`updated_at`
 * richiesta dall'AC.
 *
 * Con `--anonymize` (§11.8, US-217) il corpo è sostituito da {@see Anonymizer}
 * PRIMA della sanitizzazione, ma DOPO aver calcolato `$sourceKey` (hash del corpo
 * originale) e il `channel` (euristica sui segnali email nel corpo originale):
 * l'idempotenza e la classificazione del canale restano quindi identiche
 * indipendentemente da `--anonymize`, solo il contenuto salvato cambia.
 */
final class TicketMessagesStage implements ImportStage
{
    public function name(): string
    {
        return 'ticket_messages';
    }

    public function dependencies(): array
    {
        return ['tickets', 'users'];
    }

    public function run(ImportContext $context): StageResult
    {
        $query = DB::connection('legacy')->table('stories')
            ->select(['id', 'customer_request'])
            ->orderBy('id');

        if ($context->limit() !== null) {
            $query->limit($context->limit());
        }

        $rows = $query->get();

        $tickets = DB::table('tickets')
            ->whereIn('id', $rows->pluck('id'))
            ->get(['id', 'requester_id', 'created_at', 'updated_at'])
            ->keyBy('id');

        $userIdsByLowerName = $this->userIdsByLowerName();

        $anonymizer = $context->shouldAnonymize() ? Anonymizer::default() : null;

        $mappedSourceKeys = array_flip(
            ImportMapping::query()
                ->where('source_table', 'stories')
                ->where('target_table', 'ticket_messages')
                ->pluck('source_key')
                ->all(),
        );

        $read = 0;
        $created = 0;
        $skipped = 0;

        $orphanTicketCount = 0;
        $noConversationCount = 0;
        $fallbackSingleBlockCount = 0;
        $unresolvedAuthorCount = 0;
        $monotonicFallbackCount = 0;

        foreach ($rows as $row) {
            $read++;

            $raw = (string) $row->customer_request;

            if (trim($raw) === '') {
                $noConversationCount++;
                $skipped++;

                continue;
            }

            $ticket = $tickets->get($row->id);

            if ($ticket === null) {
                $orphanTicketCount++;
                $skipped++;

                continue;
            }

            $messages = CustomerRequestParser::parse($raw);

            if ($messages === []) {
                $noConversationCount++;
                $skipped++;

                continue;
            }

            if (count($messages) === 1 && $messages[0]->isOriginal) {
                $fallbackSingleBlockCount++;
            }

            $messages = $this->resolvePostedAt($messages, $ticket, $monotonicFallbackCount);

            foreach ($messages as $index => $message) {
                $sourceKey = sprintf('%d:%d:%s', $row->id, $index, substr(sha1($message->body), 0, 16));

                if (array_key_exists($sourceKey, $mappedSourceKeys)) {
                    $skipped++;

                    continue;
                }

                if ($context->isDryRun()) {
                    continue;
                }

                $authorId = $this->resolveAuthorId($message, $ticket->requester_id, $userIdsByLowerName, $unresolvedAuthorCount);
                $channel = $this->resolveChannel($message->body);

                $bodyToStore = $anonymizer === null
                    ? $message->body
                    : $anonymizer->bodyFor("{$row->id}:{$index}", strlen($message->body));

                $sanitizedHtml = TicketMessageSanitizer::sanitize($bodyToStore);

                $ticketMessageId = DB::table('ticket_messages')->insertGetId([
                    'ulid' => strtolower((string) Str::ulid()),
                    'ticket_id' => $row->id,
                    'author_id' => $authorId,
                    'author_email' => null,
                    'channel' => $channel,
                    'visibility' => TicketMessageVisibility::Public->value,
                    'body_html' => $sanitizedHtml,
                    'body_text' => TicketMessageSanitizer::toPlainText($sanitizedHtml),
                    'email_message_id' => null,
                    'is_legacy_import' => true,
                    'posted_at' => $message->postedAt,
                    'created_at' => $message->postedAt,
                    'updated_at' => $message->postedAt,
                ]);

                ImportMapping::create([
                    'source_table' => 'stories',
                    'source_key' => $sourceKey,
                    'target_table' => 'ticket_messages',
                    'target_id' => $ticketMessageId,
                ]);

                $mappedSourceKeys[$sourceKey] = true;
                $created++;
            }
        }

        $warnings = $this->buildWarnings(
            $created,
            $fallbackSingleBlockCount,
            $noConversationCount,
            $orphanTicketCount,
            $unresolvedAuthorCount,
            $monotonicFallbackCount,
        );

        return new StageResult(read: $read, created: $created, skipped: $skipped, warnings: $warnings);
    }

    /**
     * @return array<string, list<int>>
     */
    private function userIdsByLowerName(): array
    {
        $byName = [];

        foreach (DB::table('users')->select(['id', 'name'])->get() as $user) {
            $byName[strtolower(trim((string) $user->name))][] = (int) $user->id;
        }

        return $byName;
    }

    /**
     * @param  list<ParsedTicketMessage>  $messages
     * @return list<ParsedTicketMessage>
     */
    private function resolvePostedAt(array $messages, object $ticket, int &$monotonicFallbackCount): array
    {
        $ticketCreatedAt = Carbon::parse($ticket->created_at);
        $ticketUpdatedAt = Carbon::parse($ticket->updated_at);

        foreach ($messages as $index => $message) {
            if ($message->isOriginal && $message->postedAt === null) {
                $messages[$index] = $message->withPostedAt($ticketCreatedAt);
            }
        }

        $missingIndexes = array_keys(array_filter($messages, static fn (ParsedTicketMessage $m): bool => $m->postedAt === null));

        if ($missingIndexes === []) {
            return $messages;
        }

        $spanSeconds = $ticketUpdatedAt->diffInSeconds($ticketCreatedAt, true);
        $slotCount = count($missingIndexes);

        foreach ($missingIndexes as $position => $index) {
            $fraction = ($position + 1) / ($slotCount + 1);
            $messages[$index] = $messages[$index]->withPostedAt(
                $ticketCreatedAt->copy()->addSeconds((int) round($spanSeconds * $fraction)),
            );
            $monotonicFallbackCount++;
        }

        return $messages;
    }

    /**
     * @param  array<string, list<int>>  $userIdsByLowerName
     */
    private function resolveAuthorId(
        ParsedTicketMessage $message,
        ?int $requesterId,
        array $userIdsByLowerName,
        int &$unresolvedAuthorCount,
    ): ?int {
        if ($message->isOriginal) {
            return $requesterId;
        }

        $candidates = $message->author === null
            ? []
            : $userIdsByLowerName[strtolower(trim($message->author))] ?? [];

        if (count($candidates) === 1) {
            return $candidates[0];
        }

        $unresolvedAuthorCount++;

        return null;
    }

    /**
     * Un blocco è considerato "email" solo se porta segnali espliciti di
     * inoltro/citazione email (intestazioni "Da:"/"Date:", "ha scritto:"/"wrote:",
     * `<blockquote>` annidato) — i blocchi di risposta nativi del v1 (il template
     * "ha risposto il:") non li portano mai, quindi restano `system` per default.
     */
    private function resolveChannel(string $rawBody): string
    {
        $looksLikeEmail = str_contains($rawBody, '<blockquote')
            || preg_match('/\bha scritto:/u', $rawBody) === 1
            || preg_match('/\bwrote:/u', $rawBody) === 1
            || (
                (str_contains($rawBody, 'Da:') || str_contains($rawBody, 'From:'))
                && (str_contains($rawBody, 'Date:') || str_contains($rawBody, 'Oggetto:') || str_contains($rawBody, 'Subject:'))
            );

        return $looksLikeEmail ? TicketMessageChannel::Email->value : TicketMessageChannel::System->value;
    }

    /**
     * @return array<int, string>
     */
    private function buildWarnings(
        int $reconstructedCount,
        int $fallbackSingleBlockCount,
        int $noConversationCount,
        int $orphanTicketCount,
        int $unresolvedAuthorCount,
        int $monotonicFallbackCount,
    ): array {
        $warnings = [];

        if ($reconstructedCount > 0) {
            $warnings[] = sprintf('%d messaggi ricostruiti da customer_request.', $reconstructedCount);
        }

        if ($fallbackSingleBlockCount > 0) {
            $warnings[] = sprintf(
                '%d ticket con conversazione non scomponibile: importati come unico messaggio con l\'HTML integrale sanitizzato (fallback).',
                $fallbackSingleBlockCount,
            );
        }

        if ($noConversationCount > 0) {
            $warnings[] = sprintf('%d ticket senza alcuna conversazione (customer_request vuoto).', $noConversationCount);
        }

        if ($orphanTicketCount > 0) {
            $warnings[] = sprintf('%d conversazioni scartate: ticket v1 inesistente in v2.', $orphanTicketCount);
        }

        if ($unresolvedAuthorCount > 0) {
            $warnings[] = sprintf(
                '%d messaggi senza autore risolvibile in v2: author_id nullo (attribuiti a "storico importato").',
                $unresolvedAuthorCount,
            );
        }

        if ($monotonicFallbackCount > 0) {
            $warnings[] = sprintf(
                '%d messaggi senza timestamp ricostruibile: posted_at distribuito monotonamente tra created_at/updated_at del ticket.',
                $monotonicFallbackCount,
            );
        }

        return $warnings;
    }
}
