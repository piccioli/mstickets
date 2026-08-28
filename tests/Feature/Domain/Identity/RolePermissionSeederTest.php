<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\Permission as PermissionEnum;
use App\Domain\Identity\Enums\UserRole;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

/**
 * @return array<string, list<string>>
 */
function expectedRolePermissionMatrix(): array
{
    $allPermissions = array_map(static fn (PermissionEnum $permission): string => $permission->value, PermissionEnum::cases());

    return [
        UserRole::Admin->value => $allPermissions,
        UserRole::Manager->value => [
            'ticket.view.any', 'ticket.view.own', 'ticket.create', 'ticket.update.any',
            'ticket.update.own', 'ticket.update.assigned', 'ticket.assign', 'ticket.transition.any',
            'ticket.manage-internal-fields', 'ticket-message.create', 'ticket-message.view.internal',
            'ticket-message.create.internal', 'ticket-log.view', 'tag.view', 'tag.create', 'tag.update',
            'documentation.view.customer', 'documentation.view.internal', 'documentation.create',
            'documentation.update', 'activity-report.view.any', 'activity-report.view.own',
            'organization.view', 'cai-directory.view',
        ],
        UserRole::Developer->value => [
            'ticket.view.any', 'ticket.view.own', 'ticket.create', 'ticket.update.assigned',
            'ticket.assign', 'ticket.manage-internal-fields', 'ticket-message.create',
            'ticket-message.view.internal', 'ticket-message.create.internal', 'ticket-log.view',
            'tag.view', 'documentation.view.customer', 'documentation.view.internal',
            'documentation.create', 'documentation.update', 'cai-directory.view',
        ],
        UserRole::Customer->value => [
            'ticket.view.own', 'ticket.create', 'ticket.update.own', 'ticket-message.create',
            'documentation.view.customer', 'activity-report.view.own', 'fundraising.view.involved',
        ],
        UserRole::Fundraising->value => [
            'documentation.view.customer', 'documentation.view.internal', 'fundraising.view.any',
            'fundraising.view.involved', 'fundraising.create', 'fundraising.update',
            'fundraising.evaluate', 'fundraising.delete', 'user.view',
        ],
    ];
}

test('the seeder materializes exactly the §9.4 role/permission matrix', function (): void {
    (new RolePermissionSeeder)->run();

    expect(Permission::query()->where('guard_name', 'web')->count())->toBe(count(PermissionEnum::cases()))
        ->and(Role::query()->where('guard_name', 'web')->count())->toBe(count(UserRole::cases()));

    foreach (expectedRolePermissionMatrix() as $roleName => $expectedPermissions) {
        $role = Role::query()->where('name', $roleName)->where('guard_name', 'web')->firstOrFail();

        expect($role->permissions()->pluck('name')->sort()->values()->all())
            ->toBe(collect($expectedPermissions)->sort()->values()->all());
    }
});

test('horizon.access, logs.access and import.view are not granted to any role except admin', function (): void {
    (new RolePermissionSeeder)->run();

    foreach (['horizon.access', 'logs.access', 'import.view'] as $adminOnlyPermission) {
        $rolesWithPermission = Role::query()
            ->whereHas('permissions', fn ($query) => $query->where('name', $adminOnlyPermission))
            ->pluck('name')
            ->all();

        expect($rolesWithPermission)->toBe([UserRole::Admin->value]);
    }
});

test('running the seeder twice is idempotent', function (): void {
    (new RolePermissionSeeder)->run();
    (new RolePermissionSeeder)->run();

    expect(Permission::query()->count())->toBe(count(PermissionEnum::cases()))
        ->and(Role::query()->count())->toBe(count(UserRole::cases()));

    $managerRole = Role::query()->where('name', UserRole::Manager->value)->firstOrFail();
    expect($managerRole->permissions()->count())->toBe(count(expectedRolePermissionMatrix()[UserRole::Manager->value]));
});

test('a permission removed from the enum catalog is revoked, not left orphaned', function (): void {
    (new RolePermissionSeeder)->run();

    $orphan = Permission::query()->create(['name' => 'legacy.orphan-permission', 'guard_name' => 'web']);
    $managerRole = Role::query()->where('name', UserRole::Manager->value)->firstOrFail();
    $managerRole->givePermissionTo($orphan);

    expect(Permission::query()->where('name', 'legacy.orphan-permission')->exists())->toBeTrue();

    (new RolePermissionSeeder)->run();

    expect(Permission::query()->where('name', 'legacy.orphan-permission')->exists())->toBeFalse()
        ->and($managerRole->fresh()->permissions()->where('name', 'legacy.orphan-permission')->exists())->toBeFalse();
});

test('a role removed from the enum catalog is revoked, not left orphaned', function (): void {
    (new RolePermissionSeeder)->run();

    Role::query()->create(['name' => 'legacy-orphan-role', 'guard_name' => 'web']);
    expect(Role::query()->where('name', 'legacy-orphan-role')->exists())->toBeTrue();

    (new RolePermissionSeeder)->run();

    expect(Role::query()->where('name', 'legacy-orphan-role')->exists())->toBeFalse();
});
