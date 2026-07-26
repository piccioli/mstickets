<?php

declare(strict_types=1);

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use Filament\Resources\Pages\ViewRecord;

class ViewRole extends ViewRecord
{
    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Sola lettura (§6.7.1): nessuna azione di modifica, la matrice ruolo→permessi
            // si cambia solo in RolePermissionSeeder (US-018).
        ];
    }
}
