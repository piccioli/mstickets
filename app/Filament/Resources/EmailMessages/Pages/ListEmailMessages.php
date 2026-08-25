<?php

declare(strict_types=1);

namespace App\Filament\Resources\EmailMessages\Pages;

use App\Filament\Resources\EmailMessages\EmailMessageResource;
use Filament\Resources\Pages\ListRecords;

class ListEmailMessages extends ListRecords
{
    protected static string $resource = EmailMessageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Sola lettura (US-321): le email transitano nel registro solo attraverso
            // la pipeline inbound/outbound, mai da una creazione manuale qui.
        ];
    }
}
