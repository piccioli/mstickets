<?php

declare(strict_types=1);

use App\Import\Inspect\Analyzers\RoleValueAnalyzer;

test('classifies JSON array roles, scalar roles, and null/empty values', function (): void {
    $analysis = RoleValueAnalyzer::analyze([
        '["admin","developer"]',
        '["admin"]',
        'manager',
        null,
        '',
    ]);

    expect($analysis['total'])->toBe(5)
        ->and($analysis['null_or_empty_count'])->toBe(2)
        ->and($analysis['json_array_count'])->toBe(2)
        ->and($analysis['scalar_count'])->toBe(1)
        ->and($analysis['distinct_raw']['manager'])->toBe(1)
        ->and($analysis['distinct_raw']['(null)'])->toBe(2)
        ->and($analysis['distinct_roles']['admin'])->toBe(2)
        ->and($analysis['distinct_roles']['developer'])->toBe(1)
        ->and($analysis['distinct_roles']['manager'])->toBe(1);
});

test('handles an empty dataset', function (): void {
    $analysis = RoleValueAnalyzer::analyze([]);

    expect($analysis['total'])->toBe(0)
        ->and($analysis['distinct_raw'])->toBe([])
        ->and($analysis['distinct_roles'])->toBe([]);
});
