<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\Permission as PermissionEnum;
use App\Domain\Identity\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a user without organization.* permissions is denied every OrganizationPolicy ability', function (): void {
    $actor = userWithPermissions();
    $organization = Organization::create(['name' => 'CAI Sezione Test']);

    expect($actor->can('viewAny', Organization::class))->toBeFalse()
        ->and($actor->can('view', $organization))->toBeFalse()
        ->and($actor->can('create', Organization::class))->toBeFalse()
        ->and($actor->can('update', $organization))->toBeFalse()
        ->and($actor->can('delete', $organization))->toBeFalse();
});

test('a user with the matching organization.* permission is authorized', function (): void {
    $organization = Organization::create(['name' => 'CAI Sezione Test']);

    expect(userWithPermissions(PermissionEnum::OrganizationView)->can('view', $organization))->toBeTrue()
        ->and(userWithPermissions(PermissionEnum::OrganizationCreate)->can('create', Organization::class))->toBeTrue()
        ->and(userWithPermissions(PermissionEnum::OrganizationUpdate)->can('update', $organization))->toBeTrue()
        ->and(userWithPermissions(PermissionEnum::OrganizationDelete)->can('delete', $organization))->toBeTrue();
});
