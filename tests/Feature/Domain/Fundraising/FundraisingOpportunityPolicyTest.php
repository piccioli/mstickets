<?php

declare(strict_types=1);

use App\Domain\Fundraising\Models\FundraisingOpportunity;
use App\Domain\Identity\Enums\Permission as PermissionEnum;
use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeFundraisingOpportunityForPolicyTest(): FundraisingOpportunity
{
    $creator = User::factory()->create();

    return FundraisingOpportunity::create([
        'name' => 'Bando Regione X',
        'deadline' => now()->addMonth()->toDateString(),
        'created_by' => $creator->id,
        'responsible_user_id' => $creator->id,
    ]);
}

test('a user without any fundraising.* permission is denied every FundraisingOpportunityPolicy ability', function (): void {
    $actor = userWithPermissions();
    $opportunity = makeFundraisingOpportunityForPolicyTest();

    expect($actor->can('viewAny', FundraisingOpportunity::class))->toBeFalse()
        ->and($actor->can('view', $opportunity))->toBeFalse()
        ->and($actor->can('create', FundraisingOpportunity::class))->toBeFalse()
        ->and($actor->can('update', $opportunity))->toBeFalse()
        ->and($actor->can('delete', $opportunity))->toBeFalse()
        ->and($actor->can('evaluate', $opportunity))->toBeFalse();
});

test('a user with the matching fundraising.* permission is authorized', function (): void {
    $opportunity = makeFundraisingOpportunityForPolicyTest();

    expect(userWithPermissions(PermissionEnum::FundraisingViewInvolved)->can('view', $opportunity))->toBeTrue()
        ->and(userWithPermissions(PermissionEnum::FundraisingCreate)->can('create', FundraisingOpportunity::class))->toBeTrue()
        ->and(userWithPermissions(PermissionEnum::FundraisingUpdate)->can('update', $opportunity))->toBeTrue()
        ->and(userWithPermissions(PermissionEnum::FundraisingDelete)->can('delete', $opportunity))->toBeTrue()
        ->and(userWithPermissions(PermissionEnum::FundraisingEvaluate)->can('evaluate', $opportunity))->toBeTrue();
});

test('FundraisingOpportunityPolicy per ruolo, riga per riga (§9.4)', function (UserRole $role, bool $view, bool $create, bool $update, bool $delete): void {
    $this->seed(RolePermissionSeeder::class);

    $opportunity = makeFundraisingOpportunityForPolicyTest();
    $user = withRole(User::factory()->create(), $role);

    expect($user->can('viewAny', FundraisingOpportunity::class))->toBe($view)
        ->and($user->can('view', $opportunity))->toBe($view)
        ->and($user->can('create', FundraisingOpportunity::class))->toBe($create)
        ->and($user->can('update', $opportunity))->toBe($update)
        ->and($user->can('delete', $opportunity))->toBe($delete);
})->with([
    'admin — accesso completo' => [UserRole::Admin, true, true, true, true],
    'fundraising — accesso completo' => [UserRole::Fundraising, true, true, true, true],
    'manager — nessun accesso (mai fundraising, §9.4)' => [UserRole::Manager, false, false, false, false],
    'developer — nessun accesso (mai fundraising, §9.4)' => [UserRole::Developer, false, false, false, false],
    'customer — solo view.involved, mai create/update/delete' => [UserRole::Customer, true, false, false, false],
]);
