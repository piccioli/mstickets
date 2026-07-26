<?php

declare(strict_types=1);

namespace App\Filament\Resources\Roles\Schemas;

use App\Domain\Identity\Enums\Permission as AppPermission;
use App\Domain\Identity\Enums\UserRole;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Spatie\Permission\Models\Role;

/**
 * Sola lettura (§6.7.1, US-021): elenca i permessi che un ruolo comporta, nessun modo
 * di modificarli da qui (si cambia solo nel seeder, `RolePermissionSeeder`/US-018).
 */
class RoleInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Ruolo')
                    ->schema([
                        TextEntry::make('name')
                            ->label('Etichetta')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => UserRole::tryFrom($state)?->getLabel() ?? $state)
                            ->color(fn (string $state): string => UserRole::tryFrom($state)?->getColor() ?? 'gray'),
                    ]),

                Section::make('Permessi (sola lettura)')
                    ->description('Elenco dei permessi che questo ruolo comporta. Per modificarlo, aggiornare la matrice in RolePermissionSeeder: non esiste alcuna azione di modifica da qui.')
                    ->schema([
                        TextEntry::make('permissions.name')
                            ->hiddenLabel()
                            ->state(static fn (Role $record): array => $record->permissions
                                ->map(function ($permission): string {
                                    $name = (string) $permission->getAttribute('name');

                                    return AppPermission::tryFrom($name)?->getLabel() ?? $name;
                                })
                                ->sort()
                                ->values()
                                ->all())
                            ->placeholder('Nessun permesso')
                            ->listWithLineBreaks()
                            ->bulleted(),
                    ]),
            ]);
    }
}
