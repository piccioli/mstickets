<?php

declare(strict_types=1);

namespace App\Filament\Resources\CaiSections\Pages;

use App\Filament\Resources\CaiSections\CaiSectionResource;
use Filament\Resources\Pages\ViewRecord;

class ViewCaiSection extends ViewRecord
{
    protected static string $resource = CaiSectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Sola lettura (US-804): nessuna azione di modifica, l'anagrafica CAI viene
            // aggiornata solo da una nuova esecuzione dell'importer datapack RUNTS-CAI.
        ];
    }
}
