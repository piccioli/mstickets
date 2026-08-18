<?php

declare(strict_types=1);

namespace App\Domain\Ticketing\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\Enums\TicketLogEvent;
use App\Domain\Ticketing\Enums\TicketMessageChannel;
use App\Domain\Ticketing\Enums\TicketMessageVisibility;
use App\Domain\Ticketing\Events\TicketMessagePosted;
use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Models\TicketLog;
use App\Domain\Ticketing\Models\TicketMessage;
use App\Domain\Ticketing\Support\TicketMessageSanitizer;
use Illuminate\Support\Facades\DB;

/**
 * Unico punto di ingresso per pubblicare un messaggio nella conversazione del ticket
 * (A1 del PRD, §6.1.7): sanitizza il corpo HTML con l'allowlist di
 * {@see TicketMessageSanitizer} (mai `{!! !!}` su input utente, §8.7), aggiunge
 * l'autore ai partecipanti se non già presente, scrive il `ticket_log`
 * `message_posted` ed emette `TicketMessagePosted`. Il canale predefinito è `web`
 * (compatibilità con le fasi precedenti); da Fase 3 (US-307) il chiamante può passare
 * `TicketMessageChannel::Email` per un messaggio originato dalla pipeline email. La
 * visibilità resta sempre `public` (§15.2: l'estensione per messaggi `internal` resta
 * fuori scope, nessun parametro la aggira). La regola T7 (§6.1.5, decisione Q14) NON
 * vive qui: reagisce all'evento emesso da un listener dedicato, per non mischiare
 * l'orchestrazione della pubblicazione con la regola di cambio di stato — vale
 * indipendentemente dal canale, compreso `email`.
 */
final class PostTicketMessage
{
    public static function run(
        Ticket $ticket,
        User $author,
        string $bodyHtml,
        TicketMessageChannel $channel = TicketMessageChannel::Web,
    ): TicketMessage {
        return DB::transaction(function () use ($ticket, $author, $bodyHtml, $channel): TicketMessage {
            $sanitizedHtml = TicketMessageSanitizer::sanitize($bodyHtml);

            $message = TicketMessage::create([
                'ticket_id' => $ticket->id,
                'author_id' => $author->id,
                'channel' => $channel,
                'visibility' => TicketMessageVisibility::Public,
                'body_html' => $sanitizedHtml,
                'body_text' => TicketMessageSanitizer::toPlainText($sanitizedHtml),
                'is_legacy_import' => false,
                'posted_at' => now(),
            ]);

            $ticket->participants()->syncWithoutDetaching([$author->id]);

            TicketLog::create([
                'ticket_id' => $ticket->id,
                'user_id' => $author->id,
                'event' => TicketLogEvent::MessagePosted,
                'is_system' => $author->isSystem(),
                'occurred_at' => now(),
            ]);

            event(new TicketMessagePosted($ticket, $message));

            return $message;
        });
    }
}
