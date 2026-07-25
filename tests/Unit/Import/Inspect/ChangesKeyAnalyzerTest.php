<?php

declare(strict_types=1);

use App\Import\Inspect\Analyzers\ChangesKeyAnalyzer;

test('counts interpretable JSON changes and their key distribution', function (): void {
    $analysis = ChangesKeyAnalyzer::analyze([
        '{"status":["new","assigned"]}',
        '{"status":["assigned","progress"],"assignee_id":[null,3]}',
        null,
        '',
        'not json',
    ]);

    expect($analysis['total'])->toBe(5)
        ->and($analysis['interpretable_count'])->toBe(2)
        ->and($analysis['non_interpretable_count'])->toBe(3)
        ->and($analysis['key_distribution']['status'])->toBe(2)
        ->and($analysis['key_distribution']['assignee_id'])->toBe(1);
});

test('treats a JSON scalar (not an object/array) as non interpretable', function (): void {
    $analysis = ChangesKeyAnalyzer::analyze(['"just a string"', '42']);

    expect($analysis['interpretable_count'])->toBe(0)
        ->and($analysis['non_interpretable_count'])->toBe(2);
});
