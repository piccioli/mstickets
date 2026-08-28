<?php

declare(strict_types=1);

namespace App\Domain\Mail\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Mail\Enums\NotificationType;
use App\Domain\Mail\Mailables\ActivityReportPdfGeneratedMail;
use App\Domain\Mail\Models\EmailMessage;
use App\Domain\Mail\Support\RecipientLocale;
use App\Domain\Reporting\Enums\ActivityReportOwnerKind;
use App\Domain\Reporting\Events\ActivityReportPdfGenerated;
use App\Domain\Reporting\Models\ActivityReport;
use Illuminate\Support\Collection;

/**
 * E10 (§7.5.2 del PRD, US-615): invia l'avviso di PDF pronto all'owner del
 * report — l'utente diretto (`ActivityReportOwnerKind::User`), oppure OGNI
 * membro dell'organizzazione owner (`ActivityReportOwnerKind::Organization`,
 * stesso principio di {@see ActivityReport::isOwnedBy()}: un report di
 * organizzazione appartiene a tutti i suoi membri, non a un singolo utente).
 * Soppressioni/preferenze restano un'unica responsabilità di
 * {@see SendOutboundTicketMail::run()}, non duplicate qui.
 */
final class SendActivityReportPdfGeneratedMail
{
    public static function run(ActivityReportPdfGenerated $event): void
    {
        $report = $event->report;

        foreach (self::recipients($report) as $recipient) {
            SendOutboundTicketMail::run(
                ticket: null,
                recipient: $recipient,
                notificationType: NotificationType::ActivityReportPdfGenerated,
                subject: __('Your activity report for :period is ready.', ['period' => $report->periodLabel()], RecipientLocale::resolve($recipient)),
                mailableClass: ActivityReportPdfGeneratedMail::class,
                mailableFactory: fn (EmailMessage $outbound): ActivityReportPdfGeneratedMail => new ActivityReportPdfGeneratedMail($report, $outbound),
            );
        }
    }

    /**
     * @return Collection<int, User>
     */
    private static function recipients(ActivityReport $report): Collection
    {
        return match ($report->owner_kind) {
            ActivityReportOwnerKind::User => $report->ownerUser !== null ? collect([$report->ownerUser]) : collect(),
            ActivityReportOwnerKind::Organization => $report->ownerOrganization->users,
        };
    }
}
