<?php

declare(strict_types=1);

use App\Import\Stages\ImportRunner;
use App\Import\Stages\ImportRunnerException;
use App\Import\Stages\ImportStageRegistry;
use Tests\Feature\Import\Fixtures\FakeImportStage;

function importRunnerFixtureChain(): ImportStageRegistry
{
    // Registrati in ordine sparso apposta: l'ordine di esecuzione deve
    // derivare dalle dipendenze dichiarate, non dall'ordine di registrazione.
    return new ImportStageRegistry([
        new FakeImportStage('fixture_c', ['fixture_b']),
        new FakeImportStage('fixture_a'),
        new FakeImportStage('fixture_b', ['fixture_a']),
    ]);
}

test('resolves execution order from declared dependencies, not registration order', function (): void {
    $runner = new ImportRunner(importRunnerFixtureChain());

    $names = array_map(fn ($stage) => $stage->name(), $runner->plan());

    expect($names)->toBe(['fixture_a', 'fixture_b', 'fixture_c']);
});

test('errors explicitly on a dependency that is not registered', function (): void {
    $registry = new ImportStageRegistry([
        new FakeImportStage('fixture_a', ['ghost']),
    ]);
    $runner = new ImportRunner($registry);

    expect(fn () => $runner->plan())->toThrow(ImportRunnerException::class, 'ghost');
});

test('errors explicitly on a circular dependency', function (): void {
    $registry = new ImportStageRegistry([
        new FakeImportStage('fixture_a', ['fixture_b']),
        new FakeImportStage('fixture_b', ['fixture_a']),
    ]);
    $runner = new ImportRunner($registry);

    expect(fn () => $runner->plan())->toThrow(ImportRunnerException::class);
});

test('--stage runs only the requested stage when it has no dependencies', function (): void {
    $runner = new ImportRunner(importRunnerFixtureChain());

    $names = array_map(fn ($stage) => $stage->name(), $runner->plan(only: 'fixture_a'));

    expect($names)->toBe(['fixture_a']);
});

test('--stage errors explicitly when the requested stage has dependencies excluded from this session', function (): void {
    $runner = new ImportRunner(importRunnerFixtureChain());

    expect(fn () => $runner->plan(only: 'fixture_c'))
        ->toThrow(ImportRunnerException::class, 'fixture_b');
});

test('--from-stage runs the stage and everything after it in dependency order', function (): void {
    $runner = new ImportRunner(importRunnerFixtureChain());

    $names = array_map(fn ($stage) => $stage->name(), $runner->plan(from: 'fixture_b'));

    expect($names)->toBe(['fixture_b', 'fixture_c']);
});

test('--stage on an unknown stage name errors explicitly', function (): void {
    $runner = new ImportRunner(importRunnerFixtureChain());

    expect(fn () => $runner->plan(only: 'does_not_exist'))->toThrow(ImportRunnerException::class);
});
