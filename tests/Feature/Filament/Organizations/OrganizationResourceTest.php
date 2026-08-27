<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\Permission as PermissionEnum;
use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\Organization;
use App\Domain\Identity\Models\User;
use App\Filament\Resources\Organizations\OrganizationResource;
use App\Filament\Resources\Organizations\Pages\EditOrganization;
use App\Filament\Resources\Organizations\Pages\ListOrganizations;
use App\Filament\Resources\Organizations\RelationManagers\UsersRelationManager;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Filament::setCurrentPanel('admin');
});

/**
 * Assegna un ruolo applicativo "vuoto" solo per superare il gate d'accesso al pannello
 * (§9.1, US-020), isolando il test sui soli permessi diretti concessi da
 * userWithPermissions() — stessa convenzione già in uso da TagResourceTest/EmailMessageResourceTest.
 */
function grantOrganizationPanelAccess(User $user, UserRole $role = UserRole::Developer): User
{
    Role::query()->firstOrCreate(['name' => $role->value, 'guard_name' => 'web']);
    $user->assignRole($role->value);

    return $user->fresh();
}

test('a user without organization.view is denied access to the organizations resource', function (): void {
    $user = grantOrganizationPanelAccess(userWithPermissions());

    expect(OrganizationResource::canViewAny())->toBeFalse();

    $this->actingAs($user)->get(OrganizationResource::getUrl('index'))->assertForbidden();
});

test('a user with organization.view can access the organizations registry', function (): void {
    $user = grantOrganizationPanelAccess(userWithPermissions(PermissionEnum::OrganizationView));

    $this->actingAs($user);

    expect(OrganizationResource::canViewAny())->toBeTrue();

    $this->get(OrganizationResource::getUrl('index'))->assertOk();
});

test('the list shows name, locale and member count', function (): void {
    $user = grantOrganizationPanelAccess(userWithPermissions(PermissionEnum::OrganizationView));
    $organization = Organization::create(['name' => 'CAI Sezione Test', 'locale' => 'it']);
    $organization->users()->attach(User::factory()->count(2)->create());

    $this->actingAs($user);

    Livewire::test(ListOrganizations::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$organization])
        ->assertTableColumnStateSet('name', 'CAI Sezione Test', record: $organization)
        ->assertTableColumnStateSet('locale', 'it', record: $organization)
        ->assertTableColumnStateSet('users_count', 2, record: $organization);
});

test('a user without organization.create cannot see the create action', function (): void {
    $user = grantOrganizationPanelAccess(userWithPermissions(PermissionEnum::OrganizationView));

    $this->actingAs($user);

    expect(OrganizationResource::canCreate())->toBeFalse();
    $this->get(OrganizationResource::getUrl('create'))->assertForbidden();
});

test('a user with organization.create can create an organization', function (): void {
    $user = grantOrganizationPanelAccess(userWithPermissions(PermissionEnum::OrganizationCreate, PermissionEnum::OrganizationView));

    $this->actingAs($user);

    expect(OrganizationResource::canCreate())->toBeTrue();
    $this->get(OrganizationResource::getUrl('create'))->assertOk();
});

test('a user without organization.update cannot reach the edit page', function (): void {
    $user = grantOrganizationPanelAccess(userWithPermissions(PermissionEnum::OrganizationView));
    $organization = Organization::create(['name' => 'CAI Sezione Test']);

    $this->actingAs($user)
        ->get(OrganizationResource::getUrl('edit', ['record' => $organization]))
        ->assertForbidden();
});

test('adding a user via the members relation manager attaches it to the organization', function (): void {
    // Il RelationManager ri-autorizza ad ogni hydrate con `authorize('viewAny', User::class)`
    // (UserPolicy, `user.view`): serve oltre a organization.update/.view, non basta la Policy
    // di Organization da sola.
    $user = grantOrganizationPanelAccess(userWithPermissions(PermissionEnum::OrganizationUpdate, PermissionEnum::OrganizationView, PermissionEnum::UserView));
    $organization = Organization::create(['name' => 'CAI Sezione Test']);
    $member = User::factory()->create();

    $this->actingAs($user);

    Livewire::test(UsersRelationManager::class, [
        'ownerRecord' => $organization,
        'pageClass' => EditOrganization::class,
    ])
        ->callTableAction('attach', data: ['recordId' => $member->id]);

    expect($organization->users()->count())->toBe(1)
        ->and($organization->users()->first()->id)->toBe($member->id);
});

test('removing a user via the members relation manager detaches it from the organization', function (): void {
    $user = grantOrganizationPanelAccess(userWithPermissions(PermissionEnum::OrganizationUpdate, PermissionEnum::OrganizationView, PermissionEnum::UserView));
    $organization = Organization::create(['name' => 'CAI Sezione Test']);
    $member = User::factory()->create();
    $organization->users()->attach($member);

    $this->actingAs($user);

    Livewire::test(UsersRelationManager::class, [
        'ownerRecord' => $organization,
        'pageClass' => EditOrganization::class,
    ])
        ->callTableAction('detach', record: $member);

    expect($organization->users()->count())->toBe(0);
});

test('OrganizationResource per ruolo, riga per riga (§9.4): manager vede l\'elenco ma non ha CRUD', function (): void {
    $this->seed(RolePermissionSeeder::class);

    $manager = grantOrganizationPanelAccess(User::factory()->create(), UserRole::Manager);
    $organization = Organization::create(['name' => 'CAI Sezione Test']);

    $this->actingAs($manager);

    expect(OrganizationResource::canViewAny())->toBeTrue()
        ->and($manager->can('create', Organization::class))->toBeFalse()
        ->and($manager->can('update', $organization))->toBeFalse()
        ->and($manager->can('delete', $organization))->toBeFalse();
});

test('OrganizationResource per ruolo, riga per riga (§9.4): admin ha CRUD completo', function (): void {
    $this->seed(RolePermissionSeeder::class);

    $admin = grantOrganizationPanelAccess(User::factory()->create(), UserRole::Admin);
    $organization = Organization::create(['name' => 'CAI Sezione Test']);

    $this->actingAs($admin);

    expect(OrganizationResource::canViewAny())->toBeTrue()
        ->and($admin->can('create', Organization::class))->toBeTrue()
        ->and($admin->can('update', $organization))->toBeTrue()
        ->and($admin->can('delete', $organization))->toBeTrue();
});

test('OrganizationResource per ruolo, riga per riga (§9.4): customer e fundraising non hanno alcun permesso organization.*', function (UserRole $role): void {
    $this->seed(RolePermissionSeeder::class);

    $user = grantOrganizationPanelAccess(User::factory()->create(), $role);
    $organization = Organization::create(['name' => 'CAI Sezione Test']);

    $this->actingAs($user);

    expect(OrganizationResource::canViewAny())->toBeFalse()
        ->and($user->can('create', Organization::class))->toBeFalse()
        ->and($user->can('update', $organization))->toBeFalse()
        ->and($user->can('delete', $organization))->toBeFalse();
})->with([UserRole::Customer, UserRole::Fundraising]);

test('the organizations resource has no view route (only index/create/edit)', function (): void {
    expect(Route::has('filament.admin.resources.organizations.view'))->toBeFalse();
});
