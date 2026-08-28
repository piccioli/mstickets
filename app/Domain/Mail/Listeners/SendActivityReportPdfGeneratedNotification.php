<?php

declare(strict_types=1);

namespace App\Domain\Mail\Listeners;

use App\Domain\Mail\Actions\SendActivityReportPdfGeneratedMail;
use App\Domain\Reporting\Events\ActivityReportPdfGenerated;
use Illuminate\Contracts\Queue\ShouldQueue;

final class SendActivityReportPdfGeneratedNotification implements ShouldQueue
{
    public function handle(ActivityReportPdfGenerated $event): void
    {
        SendActivityReportPdfGeneratedMail::run($event);
    }
}
