<?php

declare(strict_types=1);

namespace App\Filament\Resources\CustomerFundraisingOpportunities\Pages;

use App\Filament\Resources\CustomerFundraisingOpportunities\CustomerFundraisingOpportunityResource;
use Filament\Resources\Pages\ListRecords;

class ListCustomerFundraisingOpportunities extends ListRecords
{
    protected static string $resource = CustomerFundraisingOpportunityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Sola lettura (§6.6.4): nessuna CreateAction per il cliente.
        ];
    }
}
