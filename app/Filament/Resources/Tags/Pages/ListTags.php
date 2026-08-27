<?php

declare(strict_types=1);

namespace App\Filament\Resources\Tags\Pages;

use App\Filament\Resources\Tags\TagResource;
use Filament\Resources\Pages\ListRecords;

class ListTags extends ListRecords
{
    protected static string $resource = TagResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Nessuna azione di creazione qui (US-403): una commessa nasce
            // dall'azione "Crea commessa" su un ticket (US-402).
        ];
    }
}
