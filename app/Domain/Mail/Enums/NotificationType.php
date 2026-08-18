<?php

declare(strict_types=1);

namespace App\Domain\Mail\Enums;

/**
 * Catalogo dei tipi di notifica email verificati da `notification_preferences`
 * (§7.5.1/§7.5.2 del PRD): un case per ogni comunicazione E1-E9 che rispetta
 * la preferenza dell'utente, aggiunto solo quando la comunicazione viene
 * effettivamente implementata (US-311+), non in anticipo per tutto il
 * catalogo.
 */
enum NotificationType: string
{
    case TicketReceivedByEmail = 'ticket_received_by_email';
    case TicketOpenedFromWeb = 'ticket_opened_from_web';
    case NewCustomerTicketStaff = 'new_customer_ticket_staff';
    case UnknownSenderStaff = 'unknown_sender_staff';
}
