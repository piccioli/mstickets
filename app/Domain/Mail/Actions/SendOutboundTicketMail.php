<?php

declare(strict_types=1);

namespace App\Domain\Mail\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Mail\Enums\EmailDirection;
use App\Domain\Mail\Enums\EmailStatus;
use App\Domain\Mail\Enums\NotificationType;
use App\Domain\Mail\Models\EmailMessage;
use App\Domain\Mail\Models\EmailSuppression;
use App\Domain\Mail\Support\NotificationGate;
use App\Domain\Mail\Support\RecipientLocale;
use App\Domain\Ticketing\Models\Ticket;
use Closure;
use Illuminate\Contracts\Mail\Mailable as MailableContract;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Punto unico di invio per qualunque Mailable del catalogo E1-E9 (§7.5.1,
 * requisiti trasversali): genera Message-ID e Reply-To VERP (§7.3.6, US-306)
 * dallo stesso ULID della riga `email_messages` outbound creata qui —
 * {@see ResolveEmailThread::run()} risolve un token
 * `ticket+<ulid>@dominio` sia contro `ticket_messages.ulid` (risposta a un
 * messaggio reale) sia contro `email_messages.ulid` (risposta a una
 * notifica senza un ticket_message associato, es. E2) — mai un secondo
 * schema di indirizzo.
 *
 * La riga `email_messages` outbound è SEMPRE creata, anche quando l'invio è
 * bloccato (`status = Suppressed`, mai un salto silenzioso: §7.7, ogni email
 * deve restare ispezionabile dall'amministrazione). Il Mailable è accodato
 * SOLO quando il destinatario non è in `email_suppressions` e non ha
 * disattivato questo tipo di notifica in `notification_preferences`.
 *
 * `$ticket` è nullable (US-312): una comunicazione del catalogo non sempre
 * si riferisce a un ticket esistente (es. E9, notifica staff per un mittente
 * mai identificato — nessun ticket è mai stato creato). `ticket_id` resta
 * `null` sulla riga outbound in quel caso (colonna nullable da Fase 0).
 */
final class SendOutboundTicketMail
{
    /**
     * @param  Closure(EmailMessage): MailableContract  $mailableFactory
     */
    public static function run(
        ?Ticket $ticket,
        User $recipient,
        NotificationType $notificationType,
        string $subject,
        string $mailableClass,
        Closure $mailableFactory,
    ): EmailMessage {
        $ulid = strtolower((string) Str::ulid());
        $domain = self::domain();
        $blockedReason = self::blockedReason($recipient, $notificationType);

        // forceCreate(), non create(): 'ulid' non è nel #[Fillable] del modello
        // (l'unico altro chiamante, HasUlids::setUniqueIds(), lo genera da sé
        // quando manca) — con create() l'assegnazione verrebbe silenziosamente
        // ignorata e il modello ne genererebbe un secondo, diverso da quello già
        // incorporato in message_id/reply_to qui sopra.
        $outbound = EmailMessage::query()->forceCreate([
            'ulid' => $ulid,
            'direction' => EmailDirection::Outbound,
            'message_id' => "{$ulid}@{$domain}",
            'ticket_id' => $ticket?->id,
            'user_id' => $recipient->id,
            'from_email' => (string) config('mail.from.address'),
            'from_name' => (string) config('mail.from.name'),
            'to' => [$recipient->email],
            'reply_to' => "ticket+{$ulid}@{$domain}",
            'subject' => $subject,
            'status' => $blockedReason !== null ? EmailStatus::Suppressed : EmailStatus::Queued,
            'failure_reason' => $blockedReason,
            'mailable_class' => $mailableClass,
        ]);

        if ($blockedReason === null) {
            Mail::to($recipient->email)->locale(RecipientLocale::resolve($recipient))->queue($mailableFactory($outbound));
        }

        return $outbound;
    }

    private static function blockedReason(User $recipient, NotificationType $notificationType): ?string
    {
        $email = mb_strtolower($recipient->email);

        if (EmailSuppression::query()->active()->whereRaw('lower(email) = ?', [$email])->exists()) {
            return 'destinatario in email_suppressions';
        }

        if (! NotificationGate::allows($recipient, $notificationType)) {
            return 'notifica disabilitata dalle preferenze utente';
        }

        return null;
    }

    /**
     * Dominio usato per Message-ID/Reply-To: quello della casella di
     * supporto monitorata da IMAP (§7.4, US-301), che supporta il
     * plus-addressing richiesto dal PRD per il VERP — mai un dominio diverso
     * da quello che riceverà davvero la risposta. Fallback al dominio del
     * mittente SMTP di default solo se `MAIL_SUPPORT_ADDRESS` non è
     * configurato (es. ambiente locale senza casella reale).
     */
    private static function domain(): string
    {
        $candidate = (string) config('mail_pipeline.support_address');

        if ($candidate === '' || ! str_contains($candidate, '@')) {
            $candidate = (string) config('mail.from.address');
        }

        return Str::after($candidate, '@');
    }
}
