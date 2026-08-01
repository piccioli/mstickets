<?php

declare(strict_types=1);

namespace App\Import\Stages;

use App\Domain\Ticketing\Enums\TicketMessageChannel;
use App\Domain\Ticketing\Enums\TicketMessageVisibility;
use App\Domain\Ticketing\Models\TicketMessage;
use App\Import\Models\ImportMapping;
use App\Import\Stages\Contracts\ImportStage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileCannotBeAdded;

/**
 * Stage 14 (§11.4/§11.5 del PRD): sposta i `media` v1 (attaccati alla `Story`) sui
 * `ticket_messages` v2, sulla collection medialibrary `attachments` (disco privato
 * dedicato, mai `public`, stesso disco usato dall'app in US-107).
 *
 * Ogni media viene attaccato al primo messaggio "legacy" del ticket corrispondente
 * (creato da {@see TicketMessagesStage}, ordinato per `posted_at`/`id`); se il
 * ticket non ha ancora nessun messaggio legacy, questo stage ne crea uno di sistema
 * ("Allegati importati", `is_legacy_import = true`) — che diventa così il "primo
 * messaggio legacy" anche per una riesecuzione successiva, senza bisogno di
 * un'ulteriore chiave di idempotenza dedicata a quel messaggio.
 *
 * I file fisici vengono letti dal disco `legacy-media` (§11.3, stessa convenzione
 * piatta `<file_name>` già usata da `v1:inspect`): un media la cui riga esiste nel
 * dump ma il cui file non è presente su questo disco è un **compromesso**, mai un
 * crash — segnalato nel report, mai contato come importato con successo.
 */
final class TicketAttachmentsStage implements ImportStage
{
    private const LEGACY_MODEL_TYPE = 'App\\Models\\Story';

    public function name(): string
    {
        return 'ticket_attachments';
    }

    public function dependencies(): array
    {
        return ['ticket_messages'];
    }

    public function run(ImportContext $context): StageResult
    {
        $query = DB::connection('legacy')->table('media')
            ->select(['id', 'model_type', 'model_id', 'uuid', 'name', 'file_name', 'mime_type'])
            ->orderBy('id');

        if ($context->limit() !== null) {
            $query->limit($context->limit());
        }

        $rows = $query->get();

        $ticketIds = DB::table('tickets')->whereIn('id', $rows->pluck('model_id'))->pluck('id')->flip();

        $firstLegacyMessageIdByTicket = $this->firstLegacyMessageIdByTicket($rows->pluck('model_id')->unique());

        $mappedSourceKeys = array_flip(
            ImportMapping::query()
                ->where('source_table', 'media')
                ->where('target_table', 'media')
                ->pluck('source_key')
                ->all(),
        );

        $read = 0;
        $created = 0;
        $skipped = 0;

        $unsupportedModelTypeCount = 0;
        $orphanTicketCount = 0;
        $missingFileCount = 0;
        $systemMessageCreatedCount = 0;
        $attachFailureCount = 0;

        foreach ($rows as $row) {
            $read++;

            if ($row->model_type !== self::LEGACY_MODEL_TYPE) {
                $unsupportedModelTypeCount++;
                $skipped++;

                continue;
            }

            $sourceKey = (string) $row->uuid;

            if ($sourceKey !== '' && array_key_exists($sourceKey, $mappedSourceKeys)) {
                $skipped++;

                continue;
            }

            $ticketId = (int) $row->model_id;

            if (! $ticketIds->has($ticketId)) {
                $orphanTicketCount++;
                $skipped++;

                continue;
            }

            if (! Storage::disk('legacy-media')->exists($row->file_name)) {
                $missingFileCount++;
                $skipped++;

                continue;
            }

            if ($context->isDryRun()) {
                continue;
            }

            $messageId = $firstLegacyMessageIdByTicket[$ticketId] ?? null;

            if ($messageId === null) {
                $messageId = $this->createSystemMessage($ticketId);
                $firstLegacyMessageIdByTicket[$ticketId] = $messageId;
                $systemMessageCreatedCount++;
            }

            $absolutePath = Storage::disk('legacy-media')->path($row->file_name);

            try {
                $media = TicketMessage::query()->findOrFail($messageId)
                    ->addMedia($absolutePath)
                    ->preservingOriginal()
                    ->usingName((string) $row->name)
                    ->usingFileName((string) $row->file_name)
                    ->toMediaCollection('attachments');
            } catch (FileCannotBeAdded) {
                $attachFailureCount++;
                $skipped++;

                continue;
            }

            if ($sourceKey !== '') {
                ImportMapping::create([
                    'source_table' => 'media',
                    'source_key' => $sourceKey,
                    'target_table' => 'media',
                    'target_id' => $media->id,
                ]);

                $mappedSourceKeys[$sourceKey] = true;
            }

            $created++;
        }

        $warnings = $this->buildWarnings(
            $unsupportedModelTypeCount,
            $orphanTicketCount,
            $missingFileCount,
            $systemMessageCreatedCount,
            $attachFailureCount,
        );

        return new StageResult(read: $read, created: $created, skipped: $skipped, warnings: $warnings);
    }

    /**
     * @param  Collection<array-key, mixed>  $ticketIds
     * @return array<int, int>
     */
    private function firstLegacyMessageIdByTicket(Collection $ticketIds): array
    {
        return DB::table('ticket_messages')
            ->whereIn('ticket_id', $ticketIds)
            ->where('is_legacy_import', true)
            ->orderBy('posted_at')
            ->orderBy('id')
            ->get(['id', 'ticket_id'])
            ->groupBy('ticket_id')
            ->map(fn ($messages) => (int) $messages->first()->id)
            ->all();
    }

    private function createSystemMessage(int $ticketId): int
    {
        $ticket = DB::table('tickets')->where('id', $ticketId)->first(['created_at']);
        $postedAt = $ticket->created_at;

        return DB::table('ticket_messages')->insertGetId([
            'ulid' => strtolower((string) Str::ulid()),
            'ticket_id' => $ticketId,
            'author_id' => null,
            'author_email' => null,
            'channel' => TicketMessageChannel::System->value,
            'visibility' => TicketMessageVisibility::Public->value,
            'body_html' => 'Allegati importati',
            'body_text' => 'Allegati importati',
            'email_message_id' => null,
            'is_legacy_import' => true,
            'posted_at' => $postedAt,
            'created_at' => $postedAt,
            'updated_at' => $postedAt,
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function buildWarnings(
        int $unsupportedModelTypeCount,
        int $orphanTicketCount,
        int $missingFileCount,
        int $systemMessageCreatedCount,
        int $attachFailureCount,
    ): array {
        $warnings = [];

        if ($unsupportedModelTypeCount > 0) {
            $warnings[] = sprintf('%d media scartati: model_type v1 diverso da Story (fuori scope di questo stage).', $unsupportedModelTypeCount);
        }

        if ($orphanTicketCount > 0) {
            $warnings[] = sprintf('%d media scartati: ticket v1 inesistente in v2.', $orphanTicketCount);
        }

        if ($missingFileCount > 0) {
            $warnings[] = sprintf('%d media orfani: riga presente nel dump ma file assente su disco.', $missingFileCount);
        }

        if ($systemMessageCreatedCount > 0) {
            $warnings[] = sprintf('%d ticket senza messaggi: creato un messaggio di sistema "Allegati importati" per ospitare gli allegati.', $systemMessageCreatedCount);
        }

        if ($attachFailureCount > 0) {
            $warnings[] = sprintf('%d media scartati: tipo di file non ammesso dalla collection attachments.', $attachFailureCount);
        }

        return $warnings;
    }
}
