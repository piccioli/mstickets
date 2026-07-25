<?php

declare(strict_types=1);

use App\Domain\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('the spatie/laravel-permission tables are published', function (): void {
    expect(Schema::hasTable('roles'))->toBeTrue()
        ->and(Schema::hasTable('permissions'))->toBeTrue()
        ->and(Schema::hasTable('model_has_roles'))->toBeTrue()
        ->and(Schema::hasTable('model_has_permissions'))->toBeTrue()
        ->and(Schema::hasTable('role_has_permissions'))->toBeTrue();
});

test('teams are disabled', function (): void {
    expect(config('permission.teams'))->toBeFalse();
});

test('roles and permissions default to the single web guard', function (): void {
    $role = Role::create(['name' => 'test-role']);
    $permission = Permission::create(['name' => 'test.permission']);

    expect($role->guard_name)->toBe('web')
        ->and($permission->guard_name)->toBe('web');
});

test('a user can be assigned a role and gains its permissions', function (): void {
    $role = Role::create(['name' => 'test-role']);
    $permission = Permission::create(['name' => 'test.permission']);
    $role->givePermissionTo($permission);

    $user = User::factory()->create();
    $user->assignRole($role);

    expect($user->hasRole('test-role'))->toBeTrue()
        ->and($user->hasPermissionTo('test.permission'))->toBeTrue();
});
