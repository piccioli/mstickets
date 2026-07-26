<?php

declare(strict_types=1);

namespace App\Filament\Resources\Roles;

use App\Domain\Identity\Enums\Permission as AppPermission;
use App\Filament\Resources\Roles\Pages\ListRoles;
use App\Filament\Resources\Roles\Pages\ViewRole;
use App\Filament\Resources\Roles\Schemas\RoleInfolist;
use App\Filament\Resources\Roles\Tables\RolesTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;
use UnitEnum;

/**
 * Sola lettura (§6.7.1/§9.2, US-021): nessuna pagina di creazione/modifica/eliminazione
 * registrata, e i metodi can* la negano esplicitamente. La matrice ruolo→permessi si
 * cambia solo nel seeder (`RolePermissionSeeder`, US-018), mai da qui. Il modello Spatie
 * `Role` non ha una Policy propria (deny-by-default nativo, §9.1): l'autorizzazione di
 * questa risorsa è quindi implementata direttamente nei metodi can* invece di una Policy.
 */
class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static ?string $modelLabel = 'ruolo';

    protected static ?string $pluralModelLabel = 'ruoli';

    protected static UnitEnum|string|null $navigationGroup = 'Utenti e permessi';

    public static function infolist(Schema $schema): Schema
    {
        return RoleInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RolesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRoles::route('/'),
            'view' => ViewRole::route('/{record}'),
        ];
    }

    public static function canViewAny(): bool
    {
        return static::canManageRolesOrPermissions();
    }

    public static function canView(Model $record): bool
    {
        return static::canManageRolesOrPermissions();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    protected static function canManageRolesOrPermissions(): bool
    {
        return (bool) Auth::user()?->canAny([AppPermission::UserAssignRoles, AppPermission::UserGrantPermissions]);
    }
}
