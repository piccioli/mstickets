<?php

declare(strict_types=1);

namespace App\Filament\Resources\FundraisingProjects\Pages;

use App\Filament\Resources\FundraisingProjects\FundraisingProjectResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditFundraisingProject extends EditRecord
{
    protected static string $resource = FundraisingProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
