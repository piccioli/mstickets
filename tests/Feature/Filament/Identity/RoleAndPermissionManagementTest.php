<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\Permission as PermissionEnum;
use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use App\Filament\Resources\Roles\Pages\ListRoles;
use App\Filament\Resources\Roles\Pages\ViewRole;
use App\Filament\Resources\Roles\RoleResource;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Schemas\UserInfolist;
use App\Filament\Resources\Users\UserResource;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission as SpatiePermission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Filament::setCurrentPanel('admin');
});

/**
 * Assegna un ruolo applicativo "vuoto" (nessun permesso derivato) solo per superare il
 * gate d'accesso al pannello (§9.1, US-020): isola il test sui SOLI permessi diretti
 * concessi da userWithPermissions(), senza permessi "bonus" derivati dal ruolo.
 */
function grantPanelAccess(User $user): User
{
    Role::query()->firstOrCreate(['name' => UserRole::Developer->value, 'guard_name' => 'web']);
    $user->assignRole(UserRole::Developer->value);

    return $user->fresh();
}

test('a user without user.view is denied access to the users list', function (): void {
    $user = grantPanelAccess(userWithPermissions());

    $this->actingAs($user)->get(UserResource::getUrl('index'))->assertForbidden();
});

test('a user with user.view can access the users list', function (): void {
    $user = grantPanelAccess(userWithPermissions(PermissionEnum::UserView));

    $this->actingAs($user)->get(UserResource::getUrl('index'))->assertOk();
});

test('a user without user.assign-roles or user.grant-permissions is denied access to the roles resource', function (): void {
    $user = grantPanelAccess(userWithPermissions());

    expect(RoleResource::canViewAny())->toBeFalse();

    $this->actingAs($user)->get(RoleResource::getUrl('index'))->assertForbidden();
});

test('a user with user.assign-roles can access the roles resource', function (): void {
    $user = grantPanelAccess(userWithPermissions(PermissionEnum::UserAssignRoles));

    $this->actingAs($user);

    expect(RoleResource::canViewAny())->toBeTrue();

    $this->get(RoleResource::getUrl('index'))->assertOk();
});

test('a user with user.grant-permissions can access the roles resource', function (): void {
    $user = grantPanelAccess(userWithPermissions(PermissionEnum::UserGrantPermissions));

    $this->actingAs($user);

    expect(RoleResource::canViewAny())->toBeTrue();
});

test('the roles resource has no create, edit or delete function', function (): void {
    $user = grantPanelAccess(userWithPermissions(PermissionEnum::UserAssignRoles));
    $role = Role::query()->firstOrCreate(['name' => UserRole::Manager->value, 'guard_name' => 'web']);

    expect(RoleResource::canCreate())->toBeFalse()
        ->and(RoleResource::canEdit($role))->toBeFalse()
        ->and(RoleResource::canDelete($role))->toBeFalse()
        ->and(RoleResource::canDeleteAny())->toBeFalse()
        ->and(Route::has('filament.admin.resources.roles.create'))->toBeFalse()
        ->and(Route::has('filament.admin.resources.roles.edit'))->toBeFalse();

    $this->actingAs($user);

    Livewire::test(ListRoles::class)->assertOk();
});

test('viewing a role lists its permissions read-only', function (): void {
    $user = grantPanelAccess(userWithPermissions(PermissionEnum::UserAssignRoles));

    $managerRole = Role::query()->firstOrCreate(['name' => UserRole::Manager->value, 'guard_name' => 'web']);
    $ticketViewAny = SpatiePermission::query()->firstOrCreate(['name' => PermissionEnum::TicketViewAny->value, 'guard_name' => 'web']);
    $managerRole->givePermissionTo($ticketViewAny);

    $this->actingAs($user);

    Livewire::test(ViewRole::class, ['record' => $managerRole->getKey()])
        ->assertOk()
        ->assertSee(PermissionEnum::TicketViewAny->getLabel());
});

test('an admin with user.assign-roles can assign a role to a user via the edit form', function (): void {
    $admin = grantPanelAccess(userWithPermissions(
        PermissionEnum::UserView,
        PermissionEnum::UserUpdate,
        PermissionEnum::UserAssignRoles,
    ));
    $customerRole = Role::query()->firstOrCreate(['name' => UserRole::Customer->value, 'guard_name' => 'web']);
    $target = User::factory()->create();

    $this->actingAs($admin);

    Livewire::test(EditUser::class, ['record' => $target->getKey()])
        ->fillForm(['roles' => [$customerRole->getKey()]])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($target->fresh()->hasRole(UserRole::Customer->value))->toBeTrue();
});

test('an admin with user.grant-permissions can grant a direct permission to a user via the edit form', function (): void {
    $admin = grantPanelAccess(userWithPermissions(
        PermissionEnum::UserView,
        PermissionEnum::UserUpdate,
        PermissionEnum::UserGrantPermissions,
    ));
    $ticketCreate = SpatiePermission::query()->firstOrCreate(['name' => PermissionEnum::TicketCreate->value, 'guard_name' => 'web']);
    $target = User::factory()->create();

    $this->actingAs($admin);

    Livewire::test(EditUser::class, ['record' => $target->getKey()])
        ->fillForm(['permissions' => [$ticketCreate->getKey()]])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($target->fresh()->hasDirectPermission(PermissionEnum::TicketCreate->value))->toBeTrue();
});

test('the roles section is hidden from an admin without user.assign-roles', function (): void {
    $admin = grantPanelAccess(userWithPermissions(PermissionEnum::UserView, PermissionEnum::UserUpdate));
    $target = User::factory()->create();

    $this->actingAs($admin);

    Livewire::test(EditUser::class, ['record' => $target->getKey()])
        ->assertDontSee('Ruoli');
});

test('the direct permissions section is hidden from an admin without user.grant-permissions', function (): void {
    $admin = grantPanelAccess(userWithPermissions(PermissionEnum::UserView, PermissionEnum::UserUpdate));
    $target = User::factory()->create();

    $this->actingAs($admin);

    Livewire::test(EditUser::class, ['record' => $target->getKey()])
        ->assertDontSee('Permessi diretti');
});

test('effective permissions are listed with their provenance (role vs direct)', function (): void {
    $developerRole = Role::query()->firstOrCreate(['name' => UserRole::Developer->value, 'guard_name' => 'web']);
    $ticketViewAny = SpatiePermission::query()->firstOrCreate(['name' => PermissionEnum::TicketViewAny->value, 'guard_name' => 'web']);
    $ticketCreate = SpatiePermission::query()->firstOrCreate(['name' => PermissionEnum::TicketCreate->value, 'guard_name' => 'web']);
    $developerRole->givePermissionTo($ticketViewAny);

    $user = User::factory()->create();
    $user->assignRole($developerRole);
    $user->givePermissionTo($ticketCreate);

    $lines = UserInfolist::effectivePermissionLines($user->fresh());

    expect($lines)->toHaveCount(2);

    $viaRoleLine = collect($lines)->first(fn (string $line): bool => str_starts_with($line, PermissionEnum::TicketViewAny->getLabel()));
    $directLine = collect($lines)->first(fn (string $line): bool => str_starts_with($line, PermissionEnum::TicketCreate->getLabel()));

    expect($viaRoleLine)->toContain(UserRole::Developer->getLabel())
        ->and($viaRoleLine)->not->toContain('diretto')
        ->and($directLine)->toContain('diretto');
});

test('a permission granted both via a role and directly lists both sources', function (): void {
    $developerRole = Role::query()->firstOrCreate(['name' => UserRole::Developer->value, 'guard_name' => 'web']);
    $ticketCreate = SpatiePermission::query()->firstOrCreate(['name' => PermissionEnum::TicketCreate->value, 'guard_name' => 'web']);
    $developerRole->givePermissionTo($ticketCreate);

    $user = User::factory()->create();
    $user->assignRole($developerRole);
    $user->givePermissionTo($ticketCreate);

    $lines = UserInfolist::effectivePermissionLines($user->fresh());

    expect($lines)->toHaveCount(1);
    expect($lines[0])->toContain(UserRole::Developer->getLabel())
        ->and($lines[0])->toContain('diretto');
});
