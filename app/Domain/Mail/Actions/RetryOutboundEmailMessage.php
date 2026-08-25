<?php

declare(strict_types=1);

namespace App\Domain\Mail\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Mail\Enums\EmailDirection;
use App\Domain\Mail\Enums\EmailMessageLogEvent;
use App\Domain\Mail\Enums\EmailStatus;
use App\Domain\Mail\Mailables\NewCustomerTicketStaffMail;
use App\Domain\Mail\Mailables\TicketOpenedFromWebMail;
use App\Domain\Mail\Mailables\TicketOutboundMailable;
use App\Domain\Mail\Mailables\TicketReceivedByEmailMail;
use App\Domain\Mail\Mailables\TicketWaitingReminderMail;
use App\Domain\Mail\Models\EmailMessage;
use App\Domain\Mail\Models\EmailMessageLog;
use App\Domain\Mail\Models\EmailSuppression;
use App\Domain\Mail\Support\RecipientLocale;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

/**
 * Azione amministrativa "reinvia" (§7.5.1/§7.7, US-322) per un outbound
 * `failed`/`bounced`: riaccoda lo stesso `EmailMessage` (stesso Message-ID/
 * Reply-To VERP, {@see SendOutboundTicketMail}), mai una nuova riga —
 * l'audit trail resta un'unica riga per invio/tentativo.
 *
 * **Limite noto, documentato invece di un reinvio silenziosamente
 * approssimato**: ogni Mailable del catalogo E1-E9 prende in costruttore, oltre
 * a `$outbound` (questa stessa riga), il `Ticket` e — per alcuni — dati storici
 * MAI persistiti su `email_messages` (es. `previousStatus`/`newStatus` di E4,
 * l'autore/corpo del messaggio di E5): per quelli non è possibile ricostruire
 * un reinvio fedele senza una modifica di schema più ampia, fuori scope qui.
 * Il reinvio automatico è quindi limitato ai soli Mailable la cui unica
 * dipendenza di contenuto oltre a `$outbound` è il `Ticket` stesso
 * (`TicketOutboundMailable` diretto, {@see self::RESENDABLE_MAILABLES}); per
 * gli altri l'azione fallisce esplicitamente invece di inviare un contenuto
 * potenzialmente errato — un futuro ampliamento di `email_messages` (es.
 * persistere i parametri del Mailable) è il modo corretto per estendere questo
 * elenco, non un tentativo di ricostruzione euristica.
 */
final class RetryOutboundEmailMessage
{
    /**
     * @var list<class-string<TicketOutboundMailable>>
     */
    private const RESENDABLE_MAILABLES = [
        NewCustomerTicketStaffMail::class,
        TicketOpenedFromWebMail::class,
        TicketReceivedByEmailMail::class,
        TicketWaitingReminderMail::class,
    ];

    public static function run(EmailMessage $emailMessage, User $actor): EmailMessage
    {
        if ($emailMessage->direction !== EmailDirection::Outbound
            || ! in_array($emailMessage->status, [EmailStatus::Failed, EmailStatus::Bounced], true)) {
            throw new RuntimeException('Solo un\'email outbound fallita o respinta può essere reinviata.');
        }

        $recipient = $emailMessage->user;

        if ($recipient === null) {
            throw new RuntimeException('Il destinatario originale non esiste più.');
        }

        if (EmailSuppression::query()->active()->whereRaw('lower(email) = ?', [mb_strtolower($recipient->email)])->exists()) {
            self::log($emailMessage, $actor, EmailMessageLogEvent::ResendBlocked, 'Destinatario in soppressione attiva');

            return $emailMessage;
        }

        $mailableClass = $emailMessage->mailable_class;
        $ticket = $emailMessage->ticket;

        if ($mailableClass === null || $ticket === null || ! in_array($mailableClass, self::RESENDABLE_MAILABLES, true)) {
            throw new RuntimeException("Il messaggio non può essere ricostruito automaticamente per il reinvio (tipo: {$mailableClass}).");
        }

        Mail::to($recipient->email)
            ->locale(RecipientLocale::resolve($recipient))
            ->queue(new $mailableClass($ticket, $emailMessage));

        $emailMessage->forceFill(['status' => EmailStatus::Queued, 'failure_reason' => null])->save();

        self::log($emailMessage, $actor, EmailMessageLogEvent::Resent);

        return $emailMessage;
    }

    private static function log(EmailMessage $emailMessage, User $actor, EmailMessageLogEvent $event, ?string $notes = null): void
    {
        EmailMessageLog::create([
            'email_message_id' => $emailMessage->id,
            'user_id' => $actor->id,
            'action' => $event,
            'notes' => $notes,
            'occurred_at' => now(),
        ]);
    }
}
