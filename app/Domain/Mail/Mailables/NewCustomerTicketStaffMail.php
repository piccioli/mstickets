<?php

declare(strict_types=1);

namespace App\Domain\Mail\Mailables;

use App\Domain\Mail\Support\StaffNotificationGroup;
use App\Filament\Resources\Tickets\TicketResource;
use Illuminate\Mail\Mailables\Content;

/**
 * E3 (§7.5.2 del PRD, US-312, corregge il problema 10 del v1): notifica al
 * gruppo staff configurabile (`config('mail_pipeline.staff_notification_group')`,
 * US-301) quando un cliente apre un nuovo ticket, via web o via email. Il
 * gruppo destinatari è risolto da {@see StaffNotificationGroup},
 * mai un elenco hard-coded qui.
 */
final class NewCustomerTicketStaffMail extends TicketOutboundMailable
{
    public function content(): Content
    {
        return new Content(
            view: 'emails.new-customer-ticket-staff',
            text: 'emails.new-customer-ticket-staff-text',
            with: [
                'ticket' => $this->ticket,
                'portalUrl' => TicketResource::getUrl('view', ['record' => $this->ticket]),
            ],
        );
    }
}
