<?php

declare(strict_types=1);

namespace App\Domain\Mail\Enums;

use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use Filament\Support\Contracts\HasLabel;

/**
 * Catalogo dei tipi di notifica email verificati da `notification_preferences`
 * (§7.5.1/§7.5.2 del PRD): un case per ogni comunicazione E1-E9 che rispetta
 * la preferenza dell'utente, aggiunto solo quando la comunicazione viene
 * effettivamente implementata (US-311+), non in anticipo per tutto il
 * catalogo.
 */
enum NotificationType: string implements HasLabel
{
    case TicketReceivedByEmail = 'ticket_received_by_email';
    case TicketOpenedFromWeb = 'ticket_opened_from_web';
    case NewCustomerTicketStaff = 'new_customer_ticket_staff';
    case UnknownSenderStaff = 'unknown_sender_staff';
    case TicketStatusChanged = 'ticket_status_changed';
    case NewTicketMessage = 'new_ticket_message';
    case TicketAssigned = 'ticket_assigned';
    case TicketWaitingReminder = 'ticket_waiting_reminder';
    case MailDigest = 'mail_digest';
    case ActivityReportPdfGenerated = 'activity_report_pdf_generated';

    public function getLabel(): string
    {
        return match ($this) {
            self::TicketReceivedByEmail => 'Conferma di ricezione ticket (email)',
            self::TicketOpenedFromWeb => 'Conferma di apertura ticket (portale)',
            self::NewCustomerTicketStaff => 'Nuovo ticket cliente (notifica staff)',
            self::UnknownSenderStaff => 'Messaggio da mittente sconosciuto (notifica staff)',
            self::TicketStatusChanged => 'Cambio di stato del ticket',
            self::NewTicketMessage => 'Nuovo messaggio sul ticket',
            self::TicketAssigned => 'Assegnazione ticket',
            self::TicketWaitingReminder => 'Promemoria ticket in attesa',
            self::MailDigest => 'Digest giornaliero attività ticket',
            self::ActivityReportPdfGenerated => 'Report attività disponibile',
        };
    }

    /**
     * §6.7.4/US-605: un cliente non deve mai vedere il toggle per un tipo di
     * comunicazione che non lo riguarda mai (es. E6 "Assegnazione", che
     * arriva solo a chi può essere assegnatario/tester — sempre staff), e
     * viceversa. E4/E5 riguardano entrambi i lati (chiunque sia
     * richiedente/assegnatario/tester/partecipante del ticket), quindi
     * restano visibili a tutti i ruoli. Riceve l'utente (non un singolo
     * ruolo isolato) perché l'unica distinzione rilevante oggi è
     * customer/non-customer — non serve un elenco diverso per ogni ruolo
     * staff (admin/developer/manager/fundraising).
     */
    public function appliesToUser(User $user): bool
    {
        if ($user->hasRole(UserRole::Customer->value)) {
            return in_array($this, [
                self::TicketReceivedByEmail,
                self::TicketOpenedFromWeb,
                self::TicketStatusChanged,
                self::NewTicketMessage,
                self::TicketWaitingReminder,
                self::MailDigest,
                self::ActivityReportPdfGenerated,
            ], true);
        }

        return in_array($this, [
            self::NewCustomerTicketStaff,
            self::UnknownSenderStaff,
            self::TicketStatusChanged,
            self::NewTicketMessage,
            self::TicketAssigned,
        ], true);
    }
}
