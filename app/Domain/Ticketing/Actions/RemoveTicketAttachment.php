<?php

declare(strict_types=1);

namespace App\Domain\Ticketing\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\DTO\TicketLogChanges;
use App\Domain\Ticketing\Enums\TicketLogEvent;
use App\Domain\Ticketing\Models\TicketLog;
use App\Domain\Ticketing\Models\TicketMessage;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Unico punto di ingresso per rimuovere un allegato di un messaggio del ticket
 * (§9.6 del PRD, US-107): verifica che `$media` appartenga davvero alla collection
 * `attachments` del `$ticketMessage` indicato (mai fidarsi solo dell'id passato dal
 * chiamante), poi cancella il file e scrive il proprio `ticket_log`
 * `attachment_removed`.
 */
final class RemoveTicketAttachment
{
    public static function run(TicketMessage $ticketMessage, Media $media, User $user): void
    {
        self::guardMediaBelongsToMessage($ticketMessage, $media);

        DB::transaction(function () use ($ticketMessage, $media, $user): void {
            $fileName = $media->file_name;

            $media->delete();

            TicketLog::create([
                'ticket_id' => $ticketMessage->ticket_id,
                'user_id' => $user->id,
                'event' => TicketLogEvent::AttachmentRemoved,
                'changes' => TicketLogChanges::attachmentRemoved($fileName)->toArray(),
                'is_system' => $user->isSystem(),
                'occurred_at' => now(),
            ]);
        });
    }

    private static function guardMediaBelongsToMessage(TicketMessage $ticketMessage, Media $media): void
    {
        $belongs = $media->collection_name === 'attachments'
            && $media->model_type === TicketMessage::class
            && (int) $media->model_id === $ticketMessage->id;

        if (! $belongs) {
            throw ValidationException::withMessages([
                'media' => ['Questo allegato non appartiene al messaggio indicato.'],
            ]);
        }
    }
}
