<?php

declare(strict_types=1);

use App\Domain\Fundraising\Models\FundraisingOpportunity;
use App\Domain\Fundraising\Models\FundraisingProject;
use App\Domain\Identity\Enums\Permission as PermissionEnum;
use App\Domain\Identity\Models\User;
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
