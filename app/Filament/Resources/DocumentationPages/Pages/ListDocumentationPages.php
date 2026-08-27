<?php

declare(strict_types=1);

namespace App\Filament\Resources\DocumentationPages\Pages;

use App\Filament\Resources\DocumentationPages\DocumentationPageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDocumentationPages extends ListRecords
{
    protected static string $resource = DocumentationPageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
