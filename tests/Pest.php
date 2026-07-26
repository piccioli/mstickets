<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\Permission as PermissionEnum;
use App\Domain\Identity\Models\User;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

pest()->extend(TestCase::class)->in('Feature', 'Unit');

/**
 * Crea un utente a cui sono concessi esattamente i permessi indicati (creando la riga
 * `permissions` se non esiste ancora). Usata dai test delle policy (US-019) per verificare
 * il deny-by-default senza dover eseguire l'intero RolePermissionSeeder.
 */
function userWithPermissions(PermissionEnum ...$permissions): User
{
    $user = User::factory()->create();

    foreach ($permissions as $permission) {
        Permission::query()->firstOrCreate(['name' => $permission->value, 'guard_name' => 'web']);
    }

    if ($permissions !== []) {
        $user->givePermissionTo(array_map(
            static fn (PermissionEnum $permission): string => $permission->value,
            $permissions,
        ));
    }

    return $user;
}
