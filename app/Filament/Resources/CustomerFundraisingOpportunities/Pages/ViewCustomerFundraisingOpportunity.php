<?php

declare(strict_types=1);

namespace App\Filament\Resources\CustomerFundraisingOpportunities\Pages;

use App\Filament\Resources\CustomerFundraisingOpportunities\CustomerFundraisingOpportunityResource;
use Filament\Resources\Pages\ViewRecord;

class ViewCustomerFundraisingOpportunity extends ViewRecord
{
    protected static string $resource = CustomerFundraisingOpportunityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Sola lettura (§6.6.4): nessuna EditAction per il cliente.
        ];
    }
}
