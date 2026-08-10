<?php

declare(strict_types=1);

namespace App\Import\Stages;

use App\Domain\Identity\Enums\Permission as PermissionEnum;
use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use App\Import\Mappers\UserRolesMapper;
use App\Import\Stages\Contracts\ImportStage;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Spatie\Permission\Models\Role;

/**
 * Stage 2 (§11.4, §11.5 del PRD): traduce `users.roles` (v1) in ruoli/permessi
 * Spatie. Dipende da `users` (US-202: le righe utente devono già esistere) e
 * presuppone che `RolePermissionSeeder` (§9.2) abbia già popolato il catalogo
 * ruoli/permessi — fallisce esplicitamente se non è così, invece di un errore
 * Spatie criptico su un ruolo inesistente.
 */
final class RolesPermissionsStage implements ImportStage
{
    /** @var array<int, PermissionEnum> */
    private const DIRECT_PERMISSIONS_FOR_EDITOR = [
        PermissionEnum::DocumentationCreate,
        PermissionEnum::DocumentationUpdate,
    ];

    public function name(): string
    {
        return 'roles_permissions';
    }

    public function dependencies(): array
    {
        return ['users'];
    }

    public function run(ImportContext $context): StageResult
    {
        $this->assertRoleCatalogSeeded();

        $query = DB::connection('legacy')->table('users')->select(['id', 'email', 'roles'])->orderBy('id');

        if ($context->limit() !== null) {
            $query->limit($context->limit());
        }

        $rows = $query->get();

        $read = 0;
        $created = 0;
        $skipped = 0;
        $warnings = [];
        $developerIds = [];

        foreach ($rows as $row) {
            $read++;

            $mapped = UserRolesMapper::parse($row->roles);

            foreach ($mapped->unrecognized as $token) {
                $warnings[] = sprintf(
                    'Utente v1 #%d (%s): ruolo non riconosciuto "%s" scartato.',
                    $row->id,
                    $row->email,
                    $token,
                );
            }

            if ($mapped->parseFailed) {
                $warnings[] = sprintf(
                    'Utente v1 #%d (%s): valore users.roles non parsabile ("%s") — nessun ruolo assegnato.',
                    $row->id,
                    $row->email,
                    (string) $row->roles,
                );
            } elseif ($mapped->roles === [] && $mapped->hadEditor) {
                $warnings[] = sprintf(
                    'Utente v1 #%d (%s): "editor" era l\'unico ruolo v1 — nessun ruolo v2 assegnato (permessi diretti concessi), decidere manualmente il ruolo.',
                    $row->id,
                    $row->email,
                );
            } elseif ($mapped->roles === []) {
                $warnings[] = sprintf(
                    'Utente v1 #%d (%s): nessun ruolo v2 valido — non potrà accedere al pannello.',
                    $row->id,
                    $row->email,
                );
            }

            if (in_array(UserRole::Developer, $mapped->roles, true)) {
                $developerIds[] = $row->id;
            }

            if ($context->isDryRun()) {
                continue;
            }

            $user = User::query()->find($row->id);

            if ($user === null) {
                $warnings[] = sprintf(
                    'Utente v1 #%d (%s): riga users v2 non trovata, stage "users" non ancora eseguito per questo id.',
                    $row->id,
                    $row->email,
                );
                $skipped++;

                continue;
            }

            $changed = $this->syncRoles($user, $mapped->roles);

            if ($mapped->hadEditor && $this->grantEditorPermissions($user)) {
                $changed = true;
            }

            $changed ? $created++ : $skipped++;
        }

        if ($developerIds !== []) {
            $warnings[] = sprintf(
                'Developer esistenti (candidati manuali per horizon.access/logs.access, non assegnati automaticamente): id v1 [%s].',
                implode(', ', $developerIds),
            );
        }

        return new StageResult(read: $read, created: $created, skipped: $skipped, warnings: $warnings);
    }

    private function assertRoleCatalogSeeded(): void
    {
        foreach (UserRole::cases() as $role) {
            $exists = Role::query()->where('name', $role->value)->where('guard_name', 'web')->exists();

            if (! $exists) {
                throw new RuntimeException(
                    "Il ruolo \"{$role->value}\" non esiste nel catalogo Spatie: eseguire RolePermissionSeeder (§9.2) prima di v1:import.",
                );
            }
        }
    }

    /**
     * @param  list<UserRole>  $roles
     */
    private function syncRoles(User $user, array $roles): bool
    {
        $roleNames = array_map(static fn (UserRole $role): string => $role->value, $roles);

        $before = $user->roles()->pluck('name')->sort()->values()->all();
        $user->syncRoles($roleNames);
        $after = $user->roles()->pluck('name')->sort()->values()->all();

        return $before !== $after;
    }

    private function grantEditorPermissions(User $user): bool
    {
        $changed = false;

        foreach (self::DIRECT_PERMISSIONS_FOR_EDITOR as $permission) {
            if (! $user->hasDirectPermission($permission->value)) {
                $user->givePermissionTo($permission->value);
                $changed = true;
            }
        }

        return $changed;
    }
}
