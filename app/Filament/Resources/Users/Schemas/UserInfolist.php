<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Schemas;

use App\Domain\Identity\Enums\Permission as AppPermission;
use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Anagrafica')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('name'),
                        TextEntry::make('email'),
                        TextEntry::make('locale'),
                        TextEntry::make('deactivated_at')
                            ->label('Disattivato dal')
                            ->dateTime()
                            ->placeholder('Attivo'),
                    ]),

                Section::make('Ruoli assegnati')
                    ->visible(fn (): bool => (bool) Auth::user()?->canAny([AppPermission::UserAssignRoles, AppPermission::UserGrantPermissions]))
                    ->schema([
                        TextEntry::make('roles.name')
                            ->hiddenLabel()
                            ->badge()
                            ->placeholder('Nessun ruolo assegnato')
                            ->formatStateUsing(fn (string $state): string => UserRole::tryFrom($state)?->getLabel() ?? $state)
                            ->color(fn (string $state): string => UserRole::tryFrom($state)?->getColor() ?? 'gray'),
                    ]),

                Section::make('Permessi effettivi')
                    ->description('Ogni permesso effettivo dell\'utente con la sua provenienza: da uno o più ruoli, oppure concesso direttamente.')
                    ->visible(fn (): bool => (bool) Auth::user()?->canAny([AppPermission::UserAssignRoles, AppPermission::UserGrantPermissions]))
                    ->schema([
                        TextEntry::make('effective_permissions')
                            ->hiddenLabel()
                            ->state(static fn (User $record): array => self::effectivePermissionLines($record))
                            ->placeholder('Nessun permesso effettivo')
                            ->listWithLineBreaks()
                            ->bulleted(),
                    ]),
            ]);
    }

    /**
     * Elenca ogni permesso effettivo dell'utente (diretto + via ruoli, Spatie
     * `getAllPermissions()`), con la provenienza esplicita: uno o più nomi di ruolo
     * che lo concedono, e/o "diretto" se concesso direttamente all'utente.
     *
     * La mappa ruolo→permesso è letta con una query diretta sulle tabelle pivot
     * Spatie (invece di iterare `$role->permissions` per ogni ruolo, N+1 query):
     * i modelli `Spatie\Permission\Models\{Role,Permission}` vivono fuori da `app/`,
     * quindi Larastan non conosce i generici delle loro relazioni (§4.4, nessun
     * impatto sul comportamento, solo sulla forma della query).
     *
     * @return list<string>
     */
    public static function effectivePermissionLines(User $record): array
    {
        $directNames = $record->getDirectPermissions()->pluck('name')->all();

        $roleIds = $record->roles->pluck('id')->all();

        /** @var array<string, list<string>> $sourcesByPermission */
        $sourcesByPermission = [];

        if ($roleIds !== []) {
            $rolePermissionRows = DB::table('role_has_permissions')
                ->join('roles', 'roles.id', '=', 'role_has_permissions.role_id')
                ->join('permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
                ->whereIn('role_has_permissions.role_id', $roleIds)
                ->select(['roles.name as role_name', 'permissions.name as permission_name'])
                ->get();

            foreach ($rolePermissionRows as $row) {
                $roleName = (string) $row->role_name;
                $roleLabel = UserRole::tryFrom($roleName)?->getLabel() ?? $roleName;

                $sourcesByPermission[(string) $row->permission_name][] = $roleLabel;
            }
        }

        $lines = $record->getAllPermissions()
            ->map(function ($permission) use ($directNames, $sourcesByPermission): string {
                $permissionName = (string) $permission->getAttribute('name');
                $label = AppPermission::tryFrom($permissionName)?->getLabel() ?? $permissionName;

                $sources = $sourcesByPermission[$permissionName] ?? [];

                if (in_array($permissionName, $directNames, true)) {
                    $sources[] = 'diretto';
                }

                return sprintf('%s (%s)', $label, implode(', ', $sources));
            })
            ->sort()
            ->values()
            ->all();

        return $lines;
    }
}
