<?php

declare(strict_types=1);

namespace App\Filament\Resources\CaiSections\Pages;

use App\Filament\Resources\CaiSections\CaiSectionResource;
use Filament\Resources\Pages\ListRecords;

class ListCaiSections extends ListRecords
{
    protected static string $resource = CaiSectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Sola lettura (US-804): nessuna azione di creazione, l'anagrafica CAI viene
            // materializzata esclusivamente dall'importer datapack RUNTS-CAI (US-802).
        ];
    }
}
