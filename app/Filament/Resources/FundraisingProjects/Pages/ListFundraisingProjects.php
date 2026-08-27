<?php

declare(strict_types=1);

namespace App\Filament\Resources\FundraisingProjects\Pages;

use App\Filament\Resources\FundraisingProjects\FundraisingProjectResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFundraisingProjects extends ListRecords
{
    protected static string $resource = FundraisingProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
