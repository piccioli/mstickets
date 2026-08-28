<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Identity\Enums\Permission as PermissionEnum;
use App\Domain\Identity\Enums\UserRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Materializza la matrice ruolo → permessi di §9.4 del PRD. Idempotente: eseguibile ad ogni
 * deploy, produce sempre la stessa matrice e revoca ruoli/permessi rimossi dal catalogo enum
 * (mai lasciati orfani in role_has_permissions/model_has_permissions).
 */
class RolePermissionSeeder extends Seeder
{
    /**
     * @var array<string, list<PermissionEnum>>
     */
    private const ROLE_PERMISSIONS = [
        'manager' => [
            PermissionEnum::TicketViewAny,
            PermissionEnum::TicketViewOwn,
            PermissionEnum::TicketCreate,
            PermissionEnum::TicketUpdateAny,
            PermissionEnum::TicketUpdateOwn,
            PermissionEnum::TicketUpdateAssigned,
            PermissionEnum::TicketAssign,
            PermissionEnum::TicketTransitionAny,
            PermissionEnum::TicketManageInternalFields,
            PermissionEnum::TicketMessageCreate,
            PermissionEnum::TicketMessageViewInternal,
            PermissionEnum::TicketMessageCreateInternal,
            PermissionEnum::TicketLogView,
            PermissionEnum::TagView,
            PermissionEnum::TagCreate,
            PermissionEnum::TagUpdate,
            PermissionEnum::DocumentationViewCustomer,
            PermissionEnum::DocumentationViewInternal,
            PermissionEnum::DocumentationCreate,
            PermissionEnum::DocumentationUpdate,
            PermissionEnum::ActivityReportViewAny,
            PermissionEnum::ActivityReportViewOwn,
            PermissionEnum::OrganizationView,
            PermissionEnum::CaiDirectoryView,
        ],
        'developer' => [
            PermissionEnum::TicketViewAny,
            PermissionEnum::TicketViewOwn,
            PermissionEnum::TicketCreate,
            PermissionEnum::TicketUpdateAssigned,
            PermissionEnum::TicketAssign,
            PermissionEnum::TicketManageInternalFields,
            PermissionEnum::TicketMessageCreate,
            PermissionEnum::TicketMessageViewInternal,
            PermissionEnum::TicketMessageCreateInternal,
            PermissionEnum::TicketLogView,
            PermissionEnum::TagView,
            PermissionEnum::DocumentationViewCustomer,
            PermissionEnum::DocumentationViewInternal,
            PermissionEnum::DocumentationCreate,
            PermissionEnum::DocumentationUpdate,
            PermissionEnum::CaiDirectoryView,
        ],
        'customer' => [
            PermissionEnum::TicketViewOwn,
            PermissionEnum::TicketCreate,
            PermissionEnum::TicketUpdateOwn,
            PermissionEnum::TicketMessageCreate,
            PermissionEnum::DocumentationViewCustomer,
            PermissionEnum::ActivityReportViewOwn,
            PermissionEnum::FundraisingViewInvolved,
        ],
        'fundraising' => [
            PermissionEnum::DocumentationViewCustomer,
            PermissionEnum::DocumentationViewInternal,
            PermissionEnum::FundraisingViewAny,
            PermissionEnum::FundraisingViewInvolved,
            PermissionEnum::FundraisingCreate,
            PermissionEnum::FundraisingUpdate,
            PermissionEnum::FundraisingEvaluate,
            PermissionEnum::FundraisingDelete,
            PermissionEnum::UserView,
        ],
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $catalogPermissionNames = array_map(
            static fn (PermissionEnum $permission): string => $permission->value,
            PermissionEnum::cases(),
        );

        foreach ($catalogPermissionNames as $permissionName) {
            Permission::query()->firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
        }

        // Revoca (delete, cascade su role_has_permissions/model_has_permissions) i permessi
        // non più presenti nel catalogo enum: nessun permesso orfano.
        Permission::query()
            ->where('guard_name', 'web')
            ->whereNotIn('name', $catalogPermissionNames)
            ->delete();

        $catalogRoleNames = array_map(
            static fn (UserRole $role): string => $role->value,
            UserRole::cases(),
        );

        foreach (UserRole::cases() as $userRole) {
            $role = Role::query()->firstOrCreate(['name' => $userRole->value, 'guard_name' => 'web']);

            $rolePermissionNames = $userRole === UserRole::Admin
                ? $catalogPermissionNames
                : array_map(
                    static fn (PermissionEnum $permission): string => $permission->value,
                    self::ROLE_PERMISSIONS[$userRole->value],
                );

            $role->syncPermissions($rolePermissionNames);
        }

        // Revoca (delete, cascade) i ruoli non più presenti nel catalogo enum.
        Role::query()
            ->where('guard_name', 'web')
            ->whereNotIn('name', $catalogRoleNames)
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
