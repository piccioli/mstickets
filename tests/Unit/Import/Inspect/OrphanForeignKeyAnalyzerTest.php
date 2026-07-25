<?php

declare(strict_types=1);

use App\Import\Inspect\Analyzers\OrphanForeignKeyAnalyzer;

test('counts orphan values and ignores nulls', function (): void {
    $analysis = OrphanForeignKeyAnalyzer::analyze(
        childValues: [1, 2, null, 99, 100],
        parentIds: [1, 2, 3],
    );

    expect($analysis['checked'])->toBe(4)
        ->and($analysis['orphan_count'])->toBe(2)
        ->and($analysis['orphan_values'])->toBe([99, 100]);
});

test('returns zero orphans when every child value has a matching parent', function (): void {
    $analysis = OrphanForeignKeyAnalyzer::analyze(
        childValues: [1, 2, null],
        parentIds: [1, 2, 3],
    );

    expect($analysis['orphan_count'])->toBe(0)
        ->and($analysis['orphan_values'])->toBe([]);
});

test('truncates orphan samples at the sample limit but keeps the full count', function (): void {
    $analysis = OrphanForeignKeyAnalyzer::analyze(
        childValues: range(1, 20),
        parentIds: [],
        sampleLimit: 5,
    );

    expect($analysis['orphan_count'])->toBe(20)
        ->and($analysis['orphan_values'])->toHaveCount(5);
});
