<?php

declare(strict_types=1);

use App\Support\Doctor\Checks\FeatureFlagsCheck;

test('every feature flag is reported as always passed, whether active or not', function (): void {
    config(['orchestrator.features' => [
        'mail_digest' => true,
        'reports_monthly' => false,
    ]]);

    $results = (new FeatureFlagsCheck)->run();

    expect($results)->toHaveCount(2)
        ->and($results[0]->passed)->toBeTrue()
        ->and($results[0]->detail)->toBe('attivo')
        ->and($results[1]->passed)->toBeTrue()
        ->and($results[1]->detail)->toBe('disattivo');
});
