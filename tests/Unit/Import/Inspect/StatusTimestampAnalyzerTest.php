<?php

declare(strict_types=1);

use App\Import\Inspect\Analyzers\StatusTimestampAnalyzer;

test('finds rows in the target status with a missing timestamp', function (): void {
    $rows = [
        ['id' => 1, 'status' => 'done', 'timestamp' => '2025-01-01 00:00:00'],
        ['id' => 2, 'status' => 'done', 'timestamp' => null],
        ['id' => 3, 'status' => 'progress', 'timestamp' => null],
    ];

    $analysis = StatusTimestampAnalyzer::analyze($rows, 'done');

    expect($analysis['status'])->toBe('done')
        ->and($analysis['checked'])->toBe(2)
        ->and($analysis['missing_count'])->toBe(1)
        ->and($analysis['missing_ids'])->toBe([2]);
});

test('ignores rows with a different status', function (): void {
    $rows = [
        ['id' => 1, 'status' => 'released', 'timestamp' => null],
    ];

    $analysis = StatusTimestampAnalyzer::analyze($rows, 'done');

    expect($analysis['checked'])->toBe(0)
        ->and($analysis['missing_count'])->toBe(0);
});
