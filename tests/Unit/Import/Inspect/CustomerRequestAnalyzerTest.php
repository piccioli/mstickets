<?php

declare(strict_types=1);

use App\Import\Inspect\Analyzers\CustomerRequestAnalyzer;

test('splits an HTML list customer_request into distinct messages', function (): void {
    $messages = CustomerRequestAnalyzer::parseMessages(
        '<ul><li><p>Parere positivo</p></li><li><p>Contattare Rita</p></li></ul>',
    );

    expect($messages)->toBe(['Parere positivo', 'Contattare Rita']);
});

test('treats plain text without <li> as a single message', function (): void {
    $messages = CustomerRequestAnalyzer::parseMessages('<p>Richiesta singola</p>');

    expect($messages)->toBe(['Richiesta singola']);
});

test('returns no messages for empty or whitespace-only markup', function (): void {
    expect(CustomerRequestAnalyzer::parseMessages('   '))->toBe([]);
});

test('counts non-empty and multi-message customer_request rows with samples', function (): void {
    $analysis = CustomerRequestAnalyzer::analyze([
        ['id' => 1, 'customer_request' => '<ul><li>Uno</li><li>Due</li></ul>'],
        ['id' => 2, 'customer_request' => '<p>Singola</p>'],
        ['id' => 3, 'customer_request' => null],
        ['id' => 4, 'customer_request' => ''],
    ]);

    expect($analysis['non_empty_count'])->toBe(2)
        ->and($analysis['multi_message_count'])->toBe(1)
        ->and($analysis['samples'])->toHaveCount(1)
        ->and($analysis['samples'][0]['id'])->toBe(1)
        ->and($analysis['samples'][0]['message_count'])->toBe(2);
});

test('respects the sample limit', function (): void {
    $rows = array_map(
        static fn (int $id): array => ['id' => $id, 'customer_request' => '<ul><li>A</li><li>B</li></ul>'],
        range(1, 10),
    );

    $analysis = CustomerRequestAnalyzer::analyze($rows, sampleLimit: 3);

    expect($analysis['multi_message_count'])->toBe(10)
        ->and($analysis['samples'])->toHaveCount(3);
});
