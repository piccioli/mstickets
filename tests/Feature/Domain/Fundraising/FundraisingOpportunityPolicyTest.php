<?php

declare(strict_types=1);

use App\Domain\Fundraising\Models\FundraisingOpportunity;
use App\Domain\Identity\Enums\Permission as PermissionEnum;
use App\Domain\Identity\Models\User;
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
