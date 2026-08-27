<?php

declare(strict_types=1);

use App\Domain\Fundraising\Models\FundraisingOpportunity;
use App\Domain\Fundraising\Models\FundraisingProject;
use App\Domain\Identity\Enums\Permission as PermissionEnum;
use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeFundraisingProjectForPolicyTest(): FundraisingProject
{
    $creator = User::factory()->create();
    $opportunity = FundraisingOpportunity::create([
        'name' => 'Bando Regione X',
        'deadline' => now()->addMonth()->toDateString(),
        'created_by' => $creator->id,
        'responsible_user_id' => $creator->id,
    ]);

    return FundraisingProject::create([
        'title' => 'Progetto rifugio A',
        'fundraising_opportunity_id' => $opportunity->id,
        'created_by' => $creator->id,
    ]);
}

/**
 * @param  array<string, int|null>  $involvement  chiavi ammesse: lead_user_id, responsible_user_id, created_by
 */
function makeFundraisingProjectInvolving(array $involvement = [], ?User $partner = null): FundraisingProject
{
    $creator = User::factory()->create();
    $opportunity = FundraisingOpportunity::create([
        'name' => 'Bando Regione X',
        'deadline' => now()->addMonth()->toDateString(),
        'created_by' => $creator->id,
        'responsible_user_id' => $creator->id,
    ]);

    $project = FundraisingProject::create(array_merge([
        'title' => 'Progetto rifugio A',
        'fundraising_opportunity_id' => $opportunity->id,
        'created_by' => $creator->id,
    ], $involvement));

    if ($partner !== null) {
        $project->partners()->attach($partner->id);
    }

    return $project;
}

test('a user without any fundraising.* permission is denied every FundraisingProjectPolicy ability', function (): void {
    $actor = userWithPermissions();
    $project = makeFundraisingProjectForPolicyTest();

    expect($actor->can('viewAny', FundraisingProject::class))->toBeFalse()
        ->and($actor->can('view', $project))->toBeFalse()
        ->and($actor->can('create', FundraisingProject::class))->toBeFalse()
        ->and($actor->can('update', $project))->toBeFalse()
        ->and($actor->can('delete', $project))->toBeFalse();
});

test('a user with the matching fundraising.* permission is authorized', function (): void {
    $project = makeFundraisingProjectForPolicyTest();

    expect(userWithPermissions(PermissionEnum::FundraisingViewAny)->can('view', $project))->toBeTrue()
        ->and(userWithPermissions(PermissionEnum::FundraisingCreate)->can('create', FundraisingProject::class))->toBeTrue()
        ->and(userWithPermissions(PermissionEnum::FundraisingUpdate)->can('update', $project))->toBeTrue()
        ->and(userWithPermissions(PermissionEnum::FundraisingDelete)->can('delete', $project))->toBeTrue();
});

test('FundraisingProjectPolicy per ruolo, riga per riga (§9.4), non coinvolto', function (UserRole $role, bool $viewAny, bool $view, bool $create, bool $update, bool $delete): void {
    $this->seed(RolePermissionSeeder::class);

    $project = makeFundraisingProjectForPolicyTest();
    $user = withRole(User::factory()->create(), $role);

    expect($user->can('viewAny', FundraisingProject::class))->toBe($viewAny)
        ->and($user->can('view', $project))->toBe($view)
        ->and($user->can('create', FundraisingProject::class))->toBe($create)
        ->and($user->can('update', $project))->toBe($update)
        ->and($user->can('delete', $project))->toBe($delete);
})->with([
    'admin — accesso completo' => [UserRole::Admin, true, true, true, true, true],
    'fundraising — accesso completo' => [UserRole::Fundraising, true, true, true, true, true],
    'manager — nessun accesso (mai fundraising, §9.4)' => [UserRole::Manager, false, false, false, false, false],
    'developer — nessun accesso (mai fundraising, §9.4)' => [UserRole::Developer, false, false, false, false, false],
    // customer: viewAny() resta true (gating di navigazione, §9.4 fundraising.view.involved),
    // ma view() sul singolo progetto è negata perché non coinvolto in questo (§6.6.3).
    'customer non coinvolto — viewAny true, view negata, mai create/update/delete' => [UserRole::Customer, true, false, false, false, false],
]);

test('un customer coinvolto come capofila vede il progetto ma non può scriverlo', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $customer = withRole(User::factory()->create(), UserRole::Customer);
    $project = makeFundraisingProjectInvolving(['lead_user_id' => $customer->id]);

    expect($customer->can('view', $project))->toBeTrue()
        ->and($customer->can('update', $project))->toBeFalse()
        ->and($customer->can('delete', $project))->toBeFalse();
});

test('un customer coinvolto come partner vede il progetto', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $customer = withRole(User::factory()->create(), UserRole::Customer);
    $project = makeFundraisingProjectInvolving(partner: $customer);

    expect($customer->can('view', $project))->toBeTrue();
});

test('un customer coinvolto come responsabile vede il progetto', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $customer = withRole(User::factory()->create(), UserRole::Customer);
    $project = makeFundraisingProjectInvolving(['responsible_user_id' => $customer->id]);

    expect($customer->can('view', $project))->toBeTrue();
});

test('un customer coinvolto come creatore vede il progetto', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $customer = withRole(User::factory()->create(), UserRole::Customer);
    $project = makeFundraisingProjectInvolving(['created_by' => $customer->id]);

    expect($customer->can('view', $project))->toBeTrue();
});

test('un customer non coinvolto in nessun modo non vede il progetto neanche via URL diretto', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $customer = withRole(User::factory()->create(), UserRole::Customer);
    $otherCustomer = withRole(User::factory()->create(), UserRole::Customer);
    $project = makeFundraisingProjectInvolving(partner: $otherCustomer);

    expect($customer->can('view', $project))->toBeFalse();
});
