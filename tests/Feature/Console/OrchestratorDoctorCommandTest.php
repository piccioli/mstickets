<?php

declare(strict_types=1);

use App\Domain\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('it exits successfully and reports every check when the environment is valid', function (): void {
    $this->artisan('orchestrator:doctor')
        ->expectsOutputToContain('[OK] Variabile env APP_KEY')
        ->expectsOutputToContain('[OK] Scrittura su storage/app')
        ->expectsOutputToContain('[OK] Utente di sistema')
        ->expectsOutputToContain('Tutti i controlli sono passati.')
        ->assertExitCode(0);
});

test('it creates the configured system user as a side effect, without a password or a role', function (): void {
    $email = config('orchestrator.system_user.email');
    expect(User::query()->where('email', $email)->exists())->toBeFalse();

    $this->artisan('orchestrator:doctor')->assertExitCode(0);

    $user = User::query()->where('email', $email)->sole();
    expect($user->password)->toBeNull()
        ->and($user->roles)->toBeEmpty();
});

test('it exits with a non-zero code and reports the failing check when a required env variable is missing', function (): void {
    config(['orchestrator.required_env' => ['APP_KEY' => null]]);

    $this->artisan('orchestrator:doctor')
        ->expectsOutputToContain('[FAIL] Variabile env APP_KEY (mancante o vuota)')
        ->expectsOutputToContain('Uno o più controlli sono falliti.')
        ->assertExitCode(1);
});

test('--help documents the command', function (): void {
    $this->artisan('orchestrator:doctor --help')
        ->expectsOutputToContain('orchestrator:doctor')
        ->assertExitCode(0);
});
