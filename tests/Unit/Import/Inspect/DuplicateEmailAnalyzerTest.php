<?php

declare(strict_types=1);

use App\Import\Inspect\Analyzers\DuplicateEmailAnalyzer;

test('finds duplicate emails that differ only by case', function (): void {
    $duplicates = DuplicateEmailAnalyzer::analyze([
        ['id' => 1, 'email' => 'Mario.Rossi@example.com'],
        ['id' => 2, 'email' => 'mario.rossi@example.com'],
        ['id' => 3, 'email' => 'unique@example.com'],
    ]);

    expect($duplicates)->toHaveCount(1)
        ->and($duplicates[0]['email_lower'])->toBe('mario.rossi@example.com')
        ->and($duplicates[0]['count'])->toBe(2)
        ->and($duplicates[0]['ids'])->toBe([1, 2])
        ->and($duplicates[0]['examples'])->toBe(['Mario.Rossi@example.com', 'mario.rossi@example.com']);
});

test('returns an empty array when there are no duplicates', function (): void {
    $duplicates = DuplicateEmailAnalyzer::analyze([
        ['id' => 1, 'email' => 'a@example.com'],
        ['id' => 2, 'email' => 'b@example.com'],
    ]);

    expect($duplicates)->toBe([]);
});
