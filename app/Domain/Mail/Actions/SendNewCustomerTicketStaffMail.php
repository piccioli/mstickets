<?php

declare(strict_types=1);

namespace App\Domain\Mail\Actions;

use App\Domain\Identity\Enums\UserRole;
use App\Domain\Mail\Enums\NotificationType;
use App\Domain\Mail\Listeners\NotifyStaffOfNewCustomerTicketFromEmail;
use App\Domain\Mail\Listeners\NotifyStaffOfNewCustomerTicketFromWeb;
use App\Domain\Mail\Mailables\NewCustomerTicketStaffMail;
use App\Domain\Mail\Models\EmailMessage;
use App\Domain\Mail\Support\RecipientLocale;
use App\Domain\Mail\Support\StaffDatabaseNotification;
use App\Domain\Mail\Support\StaffNotificationGroup;
use App\Domain\Ticketing\Models\Ticket;
use App\Filament\Resources\Tickets\Pages\CreateTicket;
use App\Filament\Resources\Tickets\TicketResource;

/**
 * E3 (§7.5.2 del PRD, US-312): invia la notifica al gruppo staff quando
 * `$ticket` è stato aperto da un cliente, indipendentemente dal canale
 * (web o email) — richiamata da entrambi i listener trigger
 * ({@see NotifyStaffOfNewCustomerTicketFromWeb},
 * {@see NotifyStaffOfNewCustomerTicketFromEmail})
 * per non duplicare il guard "richiedente cliente" + il loop sui
 * destinatari in due classi. Un ticket il cui richiedente NON ha il ruolo
 * customer (es. staff che apre un ticket per sé dal pannello, ammesso da
 * {@see CreateTicket}) non genera
 * questa notifica.
 */
final class SendNewCustomerTicketStaffMail
{
    public static function run(Ticket $ticket): void
    {
        $requester = $ticket->requester;

        if ($requester === null || ! $requester->hasRole(UserRole::Customer->value)) {
            return;
        }

        $portalUrl = TicketResource::getUrl('view', ['record' => $ticket]);

        foreach (StaffNotificationGroup::recipients() as $staffUser) {
            SendOutboundTicketMail::run(
                ticket: $ticket,
                recipient: $staffUser,
                notificationType: NotificationType::NewCustomerTicketStaff,
                subject: __('[#:id] New customer ticket: :title', ['id' => $ticket->id, 'title' => $ticket->title], RecipientLocale::resolve($staffUser)),
                mailableClass: NewCustomerTicketStaffMail::class,
                mailableFactory: fn (EmailMessage $outbound): NewCustomerTicketStaffMail => new NewCustomerTicketStaffMail($ticket, $outbound),
            );

            StaffDatabaseNotification::send(
                recipient: $staffUser,
                title: 'Nuovo ticket cliente',
                body: "#{$ticket->id} {$ticket->title}",
                url: $portalUrl,
            );
        }
    }
}
