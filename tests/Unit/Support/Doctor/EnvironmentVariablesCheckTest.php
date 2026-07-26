<?php

declare(strict_types=1);

use App\Support\Doctor\Checks\EnvironmentVariablesCheck;

test('every required variable that is present and non-empty passes', function (): void {
    config(['orchestrator.required_env' => [
        'APP_KEY' => 'base64:something',
        'DB_HOST' => 'db',
    ]]);

    $results = (new EnvironmentVariablesCheck)->run();

    expect($results)->toHaveCount(2)
        ->and($results[0]->passed)->toBeTrue()
        ->and($results[0]->label)->toBe('Variabile env APP_KEY')
        ->and($results[1]->passed)->toBeTrue();
});

test('a missing or empty variable fails', function (): void {
    config(['orchestrator.required_env' => [
        'APP_KEY' => null,
        'DB_HOST' => '',
        'DB_PORT' => '5432',
    ]]);

    $results = (new EnvironmentVariablesCheck)->run();

    expect($results[0]->passed)->toBeFalse()
        ->and($results[0]->detail)->toBe('mancante o vuota')
        ->and($results[1]->passed)->toBeFalse()
        ->and($results[2]->passed)->toBeTrue();
});
