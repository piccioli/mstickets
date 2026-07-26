<?php

declare(strict_types=1);

use App\Domain\Identity\Models\User;
use App\Support\Doctor\Checks\SystemUserCheck;
use Filament\Panel;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('it creates the system user when it does not exist yet', function (): void {
    config(['orchestrator.system_user' => ['email' => 'system@example.test', 'name' => 'Sistema']]);

    expect(User::query()->where('email', 'system@example.test')->exists())->toBeFalse();

    $results = (new SystemUserCheck)->run();

    expect($results)->toHaveCount(1)
        ->and($results[0]->passed)->toBeTrue()
        ->and($results[0]->detail)->toBe('creato (system@example.test)');

    $user = User::query()->where('email', 'system@example.test')->sole();
    expect($user->password)->toBeNull();
});

test('it leaves an existing system user untouched and reports it as already present', function (): void {
    config(['orchestrator.system_user' => ['email' => 'system@example.test', 'name' => 'Sistema']]);
    $existing = User::factory()->create(['email' => 'system@example.test']);

    $results = (new SystemUserCheck)->run();

    expect($results[0]->passed)->toBeTrue()
        ->and($results[0]->detail)->toBe('già presente (system@example.test)')
        ->and(User::query()->where('email', 'system@example.test')->count())->toBe(1)
        ->and($existing->fresh()->id)->toBe($existing->id);
});

test('the system user cannot access the panel (no role) nor authenticate (no password)', function (): void {
    config(['orchestrator.system_user' => ['email' => 'system@example.test', 'name' => 'Sistema']]);

    (new SystemUserCheck)->run();

    $user = User::query()->where('email', 'system@example.test')->sole();

    expect($user->canAccessPanel(new Panel))->toBeFalse()
        ->and($user->password)->toBeNull();
});
