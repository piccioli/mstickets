<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Events;

use App\Domain\Documentation\Events\DocumentationPageCreated;
use App\Domain\Reporting\Actions\GenerateActivityReportPdf;
use App\Domain\Reporting\Models\ActivityReport;

/**
 * Evento di dominio (US-615, §7.5.2 E10): dispatchato da
 * {@see GenerateActivityReportPdf} SOLO la
 * prima volta che `pdf_generated_at` viene valorizzato per un report — una
 * rigenerazione successiva dello stesso PDF non deve inviare un secondo
 * avviso. Stesso principio già applicato a
 * {@see DocumentationPageCreated} (Fase 4):
 * un evento di dominio esplicito, mai un hook Eloquent `updated`.
 */
final readonly class ActivityReportPdfGenerated
{
    public function __construct(public ActivityReport $report) {}
}
