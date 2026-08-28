<?php

declare(strict_types=1);

namespace App\Domain\Mail\Mailables;

use App\Domain\Mail\Models\EmailMessage;
use App\Domain\Reporting\Models\ActivityReport;
use Illuminate\Mail\Mailables\Content;

/**
 * E10 (§7.5.2 del PRD, US-615): avviso all'owner (o a ogni membro
 * dell'organizzazione owner) quando il PDF di un {@see ActivityReport} viene
 * generato per la prima volta. Nessun ticket associato: estende
 * {@see OutboundMailable} direttamente, stesso principio già applicato da
 * {@see MailDigestMail} (E8).
 */
final class ActivityReportPdfGeneratedMail extends OutboundMailable
{
    public function __construct(
        public readonly ActivityReport $report,
        EmailMessage $outbound,
    ) {
        parent::__construct($outbound);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.activity-report-pdf-generated',
            text: 'emails.activity-report-pdf-generated-text',
            with: [
                'periodLabel' => $this->report->periodLabel(),
                'downloadUrl' => route('activity-reports.pdf-download', ['activityReport' => $this->report->id]),
            ],
        );
    }
}
