<?php

declare(strict_types=1);

namespace App\Domain\Mail\Listeners;

use App\Domain\Mail\Actions\SendOutboundTicketMail;
use App\Domain\Mail\Enums\NotificationType;
use App\Domain\Mail\Events\EmailQuarantined;
use App\Domain\Mail\Mailables\UnknownSenderStaffMail;
use App\Domain\Mail\Models\EmailMessage;
use App\Domain\Mail\Support\StaffDatabaseNotification;
use App\Domain\Mail\Support\StaffNotificationGroup;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Str;

/**
 * E9 (§7.3.8/§7.5.2 del PRD, US-308/US-312): notifica al gruppo staff quando
 * un messaggio va in quarantena (mittente non identificato). Nessun `Ticket`
 * a cui riferirsi: `SendOutboundTicketMail::run()` riceve `ticket: null`
 * (US-312, `ticket_id` nullable su `email_messages` da Fase 0).
 */
final class NotifyStaffOfUnknownSender implements ShouldQueue
{
    public function handle(EmailQuarantined $event): void
    {
        $quarantinedMessage = $event->emailMessage;
        $reviewUrl = UnknownSenderStaffMail::reviewUrl($quarantinedMessage);
        $subjectExcerpt = Str::limit((string) $quarantinedMessage->subject, 60);

        foreach (StaffNotificationGroup::recipients() as $staffUser) {
            SendOutboundTicketMail::run(
                ticket: null,
                recipient: $staffUser,
                notificationType: NotificationType::UnknownSenderStaff,
                subject: "Mittente sconosciuto: {$quarantinedMessage->from_email}",
                mailableClass: UnknownSenderStaffMail::class,
                mailableFactory: fn (EmailMessage $outbound): UnknownSenderStaffMail => new UnknownSenderStaffMail($quarantinedMessage, $outbound),
            );

            StaffDatabaseNotification::send(
                recipient: $staffUser,
                title: 'Mittente sconosciuto',
                body: "{$quarantinedMessage->from_email} - {$subjectExcerpt}",
                url: $reviewUrl,
            );
        }
    }
}
