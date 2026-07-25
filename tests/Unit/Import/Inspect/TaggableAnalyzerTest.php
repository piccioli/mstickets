<?php

declare(strict_types=1);

use App\Import\Inspect\Analyzers\TaggableAnalyzer;

test('groups taggable types and counts those different from Documentation', function (): void {
    $analysis = TaggableAnalyzer::analyze([
        'App\\Models\\Documentation',
        'App\\Models\\Documentation',
        'App\\Models\\Story',
        null,
    ]);

    expect($analysis['total'])->toBe(4)
        ->and($analysis['by_type']['App\\Models\\Documentation'])->toBe(2)
        ->and($analysis['by_type']['App\\Models\\Story'])->toBe(1)
        ->and($analysis['by_type']['(null)'])->toBe(1)
        ->and($analysis['non_documentation_count'])->toBe(2);
});
