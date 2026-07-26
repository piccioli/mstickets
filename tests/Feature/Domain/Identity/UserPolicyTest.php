<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\Permission as PermissionEnum;
use App\Domain\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a user without the matching permission is denied on every UserPolicy ability', function (): void {
    $actor = userWithPermissions();
    $target = User::factory()->create();

    expect($actor->can('viewAny', User::class))->toBeFalse()
        ->and($actor->can('view', $target))->toBeFalse()
        ->and($actor->can('create', User::class))->toBeFalse()
        ->and($actor->can('update', $target))->toBeFalse()
        ->and($actor->can('delete', $target))->toBeFalse()
        ->and($actor->can('deactivate', $target))->toBeFalse()
        ->and($actor->can('assignRoles', $target))->toBeFalse()
        ->and($actor->can('grantPermissions', $target))->toBeFalse()
        ->and($actor->can('impersonate', $target))->toBeFalse();
});

test('a user with the matching permission is authorized on each UserPolicy ability', function (): void {
    $target = User::factory()->create();

    expect(userWithPermissions(PermissionEnum::UserView)->can('view', $target))->toBeTrue()
        ->and(userWithPermissions(PermissionEnum::UserCreate)->can('create', User::class))->toBeTrue()
        ->and(userWithPermissions(PermissionEnum::UserUpdate)->can('update', $target))->toBeTrue()
        ->and(userWithPermissions(PermissionEnum::UserDeactivate)->can('deactivate', $target))->toBeTrue()
        ->and(userWithPermissions(PermissionEnum::UserAssignRoles)->can('assignRoles', $target))->toBeTrue()
        ->and(userWithPermissions(PermissionEnum::UserGrantPermissions)->can('grantPermissions', $target))->toBeTrue()
        ->and(userWithPermissions(PermissionEnum::UserImpersonate)->can('impersonate', $target))->toBeTrue();
});

test('deleting a user is gated by user.deactivate (no user.delete in the §9.3 catalog) and force-delete is always denied', function (): void {
    $target = User::factory()->create();

    expect(userWithPermissions()->can('delete', $target))->toBeFalse()
        ->and(userWithPermissions(PermissionEnum::UserDeactivate)->can('delete', $target))->toBeTrue()
        ->and(userWithPermissions(PermissionEnum::UserDeactivate)->can('forceDelete', $target))->toBeFalse();
});
