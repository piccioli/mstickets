<?php

declare(strict_types=1);

namespace App\Filament\Resources\CustomerFundraisingProjects\Pages;

use App\Filament\Resources\CustomerFundraisingProjects\CustomerFundraisingProjectResource;
use Filament\Resources\Pages\ListRecords;

class ListCustomerFundraisingProjects extends ListRecords
{
    protected static string $resource = CustomerFundraisingProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Sola lettura (§6.6.4): nessuna CreateAction per il cliente.
        ];
    }
}
