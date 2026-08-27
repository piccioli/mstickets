<?php

declare(strict_types=1);

namespace App\Filament\Resources\FundraisingOpportunities\Pages;

use App\Filament\Resources\FundraisingOpportunities\FundraisingOpportunityResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditFundraisingOpportunity extends EditRecord
{
    protected static string $resource = FundraisingOpportunityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
