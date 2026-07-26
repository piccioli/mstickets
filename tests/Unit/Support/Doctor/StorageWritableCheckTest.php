<?php

declare(strict_types=1);

use App\Support\Doctor\Checks\StorageWritableCheck;

test('the relevant storage directories of a fresh install are writable', function (): void {
    $results = (new StorageWritableCheck)->run();

    expect($results)->not->toBeEmpty();

    foreach ($results as $result) {
        expect($result->passed)->toBeTrue()->and($result->detail)->toBe('scrivibile');
    }
});
