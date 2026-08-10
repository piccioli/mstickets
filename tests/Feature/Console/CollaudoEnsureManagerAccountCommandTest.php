<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    (new RolePermissionSeeder)->run();
});

test('creates the fixed manager reference account with the known password and the Manager role', function (): void {
    $this->artisan('collaudo:ensure-manager-account')->assertSuccessful();

    $user = User::query()->where('email', 'manager@oc.test')->first();

    expect($user)->not->toBeNull()
        ->and(Hash::check('password', $user->password))->toBeTrue()
        ->and($user->hasRole(UserRole::Manager))->toBeTrue()
        ->and($user->deactivated_at)->toBeNull();
});

test('is idempotent: re-running it does not create a second account nor change the role', function (): void {
    $this->artisan('collaudo:ensure-manager-account')->assertSuccessful();
    $this->artisan('collaudo:ensure-manager-account')->assertSuccessful();

    expect(User::query()->where('email', 'manager@oc.test')->count())->toBe(1);

    $user = User::query()->where('email', 'manager@oc.test')->first();

    expect($user->getRoleNames()->all())->toBe([UserRole::Manager->value]);
});

test('refuses to run in production', function (): void {
    app()->instance('env', 'production');

    $this->artisan('collaudo:ensure-manager-account')->assertFailed();

    expect(User::query()->where('email', 'manager@oc.test')->exists())->toBeFalse();
});
