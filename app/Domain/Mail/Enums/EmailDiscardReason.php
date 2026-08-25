<?php

declare(strict_types=1);

namespace App\Domain\Mail\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Motivo per cui un'email inbound non prosegue verso il ticketing (§7.3.4,
 * US-304): salvato in `email_messages.failure_reason` quando `status =
 * discarded`, riusato dalla futura UI di amministrazione (registro email) per
 * mostrare perché un messaggio non è diventato un ticket.
 */
enum EmailDiscardReason: string implements HasLabel
{
    case DeliveryStatusNotification = 'delivery_status_notification';
    case AutoSubmitted = 'auto_submitted';
    case Precedence = 'precedence';
    case MailingList = 'mailing_list';
    case AutoResponseSuppressed = 'auto_response_suppressed';
    case SystemSender = 'system_sender';
    case Suppressed = 'suppressed';
    case SelfSender = 'self_sender';

    public function getLabel(): string
    {
        return match ($this) {
            self::DeliveryStatusNotification => 'Notifica di mancato recapito (DSN)',
            self::AutoSubmitted => 'Messaggio auto-generato (Auto-Submitted)',
            self::Precedence => 'Mailing list/bulk (Precedence)',
            self::MailingList => 'Mailing list (List-Id/List-Unsubscribe)',
            self::AutoResponseSuppressed => 'Auto-risposta soppressa dal mittente',
            self::SystemSender => 'Mittente di sistema (mailer-daemon/postmaster/no-reply)',
            self::Suppressed => 'Mittente in soppressione',
            self::SelfSender => 'Mittente coincidente con la piattaforma stessa',
        };
    }
}
