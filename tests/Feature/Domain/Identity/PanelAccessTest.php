<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use Filament\Panel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('a user with a valid application role can access the panel', function (): void {
    Role::query()->firstOrCreate(['name' => UserRole::Developer->value, 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->assignRole(UserRole::Developer->value);

    expect($user->canAccessPanel(new Panel))->toBeTrue();
});

test('a user without any of the 5 valid roles cannot access the panel', function (): void {
    $user = User::factory()->create();

    expect($user->canAccessPanel(new Panel))->toBeFalse();
});

test('a deactivated user cannot access the panel even with a valid role', function (): void {
    Role::query()->firstOrCreate(['name' => UserRole::Admin->value, 'guard_name' => 'web']);
    $user = User::factory()->create(['deactivated_at' => now()]);
    $user->assignRole(UserRole::Admin->value);

    expect($user->canAccessPanel(new Panel))->toBeFalse();
});

test('the active scope excludes deactivated users from a user selection query', function (): void {
    $active = User::factory()->create();
    $deactivated = User::factory()->create(['deactivated_at' => now()]);

    $ids = User::query()->active()->pluck('id');

    expect($ids)->toContain($active->id)
        ->and($ids)->not->toContain($deactivated->id);
});
