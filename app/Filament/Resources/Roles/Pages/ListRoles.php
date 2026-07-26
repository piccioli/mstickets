<?php

declare(strict_types=1);

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use Filament\Resources\Pages\ListRecords;

class ListRoles extends ListRecords
{
    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Sola lettura (§6.7.1): nessuna azione di creazione, i ruoli vengono
            // materializzati esclusivamente da RolePermissionSeeder (US-018).
        ];
    }
}
