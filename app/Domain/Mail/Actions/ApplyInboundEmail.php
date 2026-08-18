<?php

declare(strict_types=1);

namespace App\Domain\Mail\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Mail\Enums\EmailStatus;
use App\Domain\Mail\Events\InboundEmailApplied;
use App\Domain\Mail\Models\EmailMessage;
use App\Domain\Ticketing\Actions\CreateTicket;
use App\Domain\Ticketing\Actions\PostTicketMessage;
use App\Domain\Ticketing\Enums\TicketMessageChannel;
use App\Domain\Ticketing\Enums\TicketType;
use App\Domain\Ticketing\Models\Ticket;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Applica un'email inbound già classificata su un ticket (§7.3.7 del PRD,
 * US-307, R4): orchestra in coda `ResolveEmailSender` (US-305) e
 * `ResolveEmailThread` (US-306) — nessun'altra Action della pipeline chiama
 * ancora queste due in sequenza — per decidere se creare un nuovo ticket o
 * accodare un messaggio su uno esistente. Un mittente non identificato
 * (`status = quarantined`, impostato da `ResolveEmailSender`) esce da qui
 * senza creare nulla: la gestione vive in US-308, MAI in questa Action.
 *
 * La creazione/aggiornamento del ticket e l'aggiornamento di
 * `email_messages` (`status = applied`, `ticket_id`) avvengono in UNA SOLA
 * transazione (`DB::transaction()`, che avvolge anche le transazioni interne
 * di `CreateTicket`/`PostTicketMessage` via savepoint): un fallimento in
 * qualunque punto annulla entrambe. Le notifiche post-commit (E1/E3,
 * US-311/US-312) sono accodate SOLO dopo che la transazione è già stata
 * committata, mai al suo interno, ed eventuali eccezioni nella loro
 * gestione sono catturate e loggate qui: un fallimento nell'invio non deve
 * mai impedire di marcare l'email come applicata (a differenza del v1,
 * problema 2, dove un fallimento SMTP produceva duplicati infiniti).
 */
final class ApplyInboundEmail
{
    public static function run(EmailMessage $emailMessage): EmailMessage
    {
        if ($emailMessage->status !== EmailStatus::Classified) {
            return $emailMessage;
        }

        try {
            $emailMessage = ResolveEmailSender::run($emailMessage);

            $user = $emailMessage->status === EmailStatus::Classified && $emailMessage->user_id !== null
                ? User::query()->find($emailMessage->user_id)
                : null;

            if ($user === null) {
                // Mittente non identificato (o già fallito): status già impostato
                // da ResolveEmailSender (quarantined/failed), gestione in US-308.
                return $emailMessage;
            }

            $resolution = ResolveEmailThread::run($emailMessage);
            $isNewTicket = ! $resolution->isMatch();

            $ticket = DB::transaction(function () use ($emailMessage, $resolution, $isNewTicket, $user): Ticket {
                $ticket = $isNewTicket
                    ? CreateTicket::run([
                        'title' => (string) $emailMessage->subject,
                        'type' => TicketType::Helpdesk,
                        'requester_id' => $user->id,
                    ], $user)
                    : Ticket::query()->findOrFail($resolution->ticketId);

                PostTicketMessage::run($ticket, $user, self::bodyHtml($emailMessage), TicketMessageChannel::Email);

                $emailMessage->forceFill([
                    'status' => EmailStatus::Applied,
                    'ticket_id' => $ticket->id,
                ])->save();

                return $ticket;
            });

            self::queuePostCommitNotifications($ticket, $emailMessage, $isNewTicket);
        } catch (Throwable $exception) {
            Log::warning('mail.apply.failed', [
                'email_message_id' => $emailMessage->id,
                'error' => $exception->getMessage(),
            ]);

            $emailMessage->forceFill([
                'status' => EmailStatus::Failed,
                'failure_reason' => $exception->getMessage(),
            ])->save();
        }

        return $emailMessage;
    }

    /**
     * Il corpo dell'email (US-303) preferisce già `body_html`; quando manca
     * (email solo testo) lo si ricostruisce da `body_text` con un escaping
     * esplicito — mai testo grezzo interpolato come HTML — prima di passarlo
     * a `PostTicketMessage`, che lo sanitizza comunque di nuovo (idempotente).
     */
    private static function bodyHtml(EmailMessage $emailMessage): string
    {
        $html = trim((string) $emailMessage->body_html);

        if ($html !== '') {
            return $html;
        }

        return '<p>'.nl2br(e(trim((string) $emailMessage->body_text))).'</p>';
    }

    private static function queuePostCommitNotifications(Ticket $ticket, EmailMessage $emailMessage, bool $isNewTicket): void
    {
        try {
            event(new InboundEmailApplied($ticket, $emailMessage, $isNewTicket));
        } catch (Throwable $exception) {
            Log::warning('mail.apply.notify_failed', [
                'email_message_id' => $emailMessage->id,
                'ticket_id' => $ticket->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
