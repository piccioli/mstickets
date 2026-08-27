<?php

declare(strict_types=1);

namespace App\Filament\Resources\ActivityReports\Pages;

use App\Filament\Resources\ActivityReports\ActivityReportResource;
use Filament\Resources\Pages\ListRecords;

class ListActivityReports extends ListRecords
{
    protected static string $resource = ActivityReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Sola lettura (§6.5.4): la generazione è un servizio dedicato
            // (US-408/US-410), non un'azione manuale da qui.
        ];
    }
}
