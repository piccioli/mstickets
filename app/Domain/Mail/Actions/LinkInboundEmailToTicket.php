<?php

declare(strict_types=1);

namespace App\Domain\Mail\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Mail\Enums\EmailDirection;
use App\Domain\Mail\Enums\EmailMessageLogEvent;
use App\Domain\Mail\Enums\EmailStatus;
use App\Domain\Mail\Models\EmailMessage;
use App\Domain\Mail\Models\EmailMessageLog;
use App\Domain\Ticketing\Actions\PostTicketMessage;
use App\Domain\Ticketing\Enums\TicketMessageChannel;
use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Models\TicketMessage;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Azione amministrativa "collega a ticket" (§7.3.6/§7.7, US-322): override
 * manuale della risoluzione del thread di {@see ResolveEmailThread}, per il
 * caso (esplicitamente euristico o comunque sbagliato) in cui l'email sia
 * finita sul ticket sbagliato o non sia mai stata applicata. Richiede un
 * mittente già risolto (`user_id` valorizzato): un messaggio ancora in
 * quarantena va prima associato a un utente (US-322, azioni dedicate).
 *
 * Due casi distinti:
 * - messaggio già applicato (un `TicketMessage` con `email_message_id`
 *   corrispondente esiste già, {@see PostTicketMessage}): SPOSTA quel
 *   messaggio sul nuovo ticket, non ne crea un secondo;
 * - messaggio non ancora applicato (`classified`/`quarantined` con mittente
 *   risolto, o `discarded`/`failed`): pubblica il messaggio direttamente sul
 *   ticket indicato, saltando la risoluzione automatica del thread.
 */
final class LinkInboundEmailToTicket
{
    public static function run(EmailMessage $emailMessage, Ticket $ticket, User $actor): EmailMessage
    {
        if ($emailMessage->direction !== EmailDirection::Inbound || $emailMessage->user_id === null) {
            throw new RuntimeException('Il mittente deve essere risolto prima di collegare l\'email a un ticket.');
        }

        $emailMessage = DB::transaction(function () use ($emailMessage, $ticket): EmailMessage {
            $existingMessage = TicketMessage::query()->where('email_message_id', $emailMessage->id)->first();

            if ($existingMessage !== null) {
                $existingMessage->update(['ticket_id' => $ticket->id]);
                $ticket->participants()->syncWithoutDetaching([$existingMessage->author_id]);
            } else {
                $sender = $emailMessage->user;

                if ($sender === null) {
                    throw new RuntimeException('Il mittente associato al messaggio non esiste più.');
                }

                $message = PostTicketMessage::run($ticket, $sender, self::bodyHtml($emailMessage), TicketMessageChannel::Email, $emailMessage);

                ImportInboundEmailAttachments::run($emailMessage, $message);
            }

            $emailMessage->forceFill(['status' => EmailStatus::Applied, 'ticket_id' => $ticket->id])->save();

            return $emailMessage;
        });

        EmailMessageLog::create([
            'email_message_id' => $emailMessage->id,
            'user_id' => $actor->id,
            'action' => EmailMessageLogEvent::LinkedToTicket,
            'notes' => "Collegato al ticket #{$ticket->id}",
            'occurred_at' => now(),
        ]);

        return $emailMessage;
    }

    /**
     * Stessa regola di fallback di {@see ApplyInboundEmail::bodyHtml()}: quando
     * l'email non ha un `body_html` (solo testo), lo ricostruisce con escaping
     * esplicito prima di passarlo a `PostTicketMessage`, che lo sanitizza comunque.
     */
    private static function bodyHtml(EmailMessage $emailMessage): string
    {
        $html = trim((string) $emailMessage->body_html);

        if ($html !== '') {
            return $html;
        }

        return '<p>'.nl2br(e(trim((string) $emailMessage->body_text))).'</p>';
    }
}
