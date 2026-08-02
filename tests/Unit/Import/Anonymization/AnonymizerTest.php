<?php

declare(strict_types=1);

use App\Import\Anonymization\Anonymizer;
use Illuminate\Support\Facades\Hash;

test('the same seed always produces the same fake name and email', function (): void {
    $anonymizer = new Anonymizer('test.orchestrator.invalid');

    expect($anonymizer->nameFor(42))->toBe($anonymizer->nameFor(42))
        ->and($anonymizer->emailFor(42))->toBe($anonymizer->emailFor(42));
});

test('different seeds produce different fake names and emails', function (): void {
    $anonymizer = new Anonymizer('test.orchestrator.invalid');

    expect($anonymizer->nameFor(1))->not->toBe($anonymizer->nameFor(2))
        ->and($anonymizer->emailFor(1))->not->toBe($anonymizer->emailFor(2));
});

test('the fake email is always on the configured test domain, never a real one', function (): void {
    $anonymizer = new Anonymizer('test.orchestrator.invalid');

    expect($anonymizer->emailFor(7))->toEndWith('@test.orchestrator.invalid');
});

test('default() resolves the test domain from config, falling back to a safe default', function (): void {
    config(['orchestrator.anonymization.mail_test_domains' => ['clienti-fittizi.example.test']]);

    expect(Anonymizer::default()->emailFor(1))->toEndWith('@clienti-fittizi.example.test');

    config(['orchestrator.anonymization.mail_test_domains' => []]);

    expect(Anonymizer::default()->emailFor(1))->toEndWith('@test.orchestrator.invalid');
});

test('passwordHash returns a Laravel hash of the fixed known password, never the raw string', function (): void {
    $anonymizer = new Anonymizer('test.orchestrator.invalid');

    $hash = $anonymizer->passwordHash();

    expect($hash)->not->toBe('password')
        ->and(Hash::check('password', $hash))->toBeTrue();
});

test('bodyFor is deterministic for the same seed and scales roughly with the original length', function (): void {
    $anonymizer = new Anonymizer('test.orchestrator.invalid');

    expect($anonymizer->bodyFor('1641:0', 300))->toBe($anonymizer->bodyFor('1641:0', 300))
        ->and($anonymizer->bodyFor('1641:0', 300))->not->toBe($anonymizer->bodyFor('1641:1', 300));

    $shortBody = $anonymizer->bodyFor('short', 12);
    $longBody = $anonymizer->bodyFor('long', 600);

    expect(str_word_count($longBody))->toBeGreaterThan(str_word_count($shortBody));
});
