<?php

declare(strict_types=1);

use App\Import\Enums\ImportRunStatus;
use App\Import\Models\ImportRun;
use App\Import\Stages\ImportContext;
use App\Import\Stages\ImportRunner;
use App\Import\Stages\ImportStageRegistry;
use App\Import\Stages\StageResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Import\Fixtures\FakeImportStage;

uses(RefreshDatabase::class);

function importRunnerRunStartRun(bool $dryRun = false): ImportRun
{
    return ImportRun::create([
        'started_at' => now(),
        'dump_label' => 'test-dump',
        'stages' => [],
        'status' => ImportRunStatus::Running,
        'is_dry_run' => $dryRun,
    ]);
}

test('executes stages in dependency order and records counts on import_runs.stages', function (): void {
    $registry = new ImportStageRegistry([
        new FakeImportStage('fixture_b', ['fixture_a'], fn () => new StageResult(read: 2, created: 1, updated: 1)),
        new FakeImportStage('fixture_a', [], fn () => new StageResult(read: 3, created: 3)),
    ]);
    $runner = new ImportRunner($registry);
    $importRun = importRunnerRunStartRun();
    $context = new ImportContext(importRun: $importRun);

    $result = $runner->run($runner->plan(), $context);

    expect($result->status)->toBe(ImportRunStatus::Completed)
        ->and($result->finished_at)->not->toBeNull()
        ->and($result->stages['fixture_a'])->toBe(['read' => 3, 'created' => 3, 'updated' => 0, 'skipped' => 0, 'warnings' => []])
        ->and($result->stages['fixture_b'])->toBe(['read' => 2, 'created' => 1, 'updated' => 1, 'skipped' => 0, 'warnings' => []]);
});

test('dry-run does not write rows to the destination table', function (): void {
    $writingStage = new FakeImportStage('fixture_a', [], function (ImportContext $context): StageResult {
        if (! $context->isDryRun()) {
            DB::table('import_mappings')->insert([
                'source_table' => 'stories',
                'source_key' => '1',
                'target_table' => 'tickets',
                'target_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return new StageResult(read: 1, created: $context->isDryRun() ? 0 : 1);
    });
    $runner = new ImportRunner(new ImportStageRegistry([$writingStage]));

    $before = DB::table('import_mappings')->count();

    $dryRunContext = new ImportContext(importRun: importRunnerRunStartRun(dryRun: true), dryRun: true);
    $runner->run($runner->plan(), $dryRunContext);

    expect(DB::table('import_mappings')->count())->toBe($before);

    $realContext = new ImportContext(importRun: importRunnerRunStartRun(dryRun: false), dryRun: false);
    $runner->run($runner->plan(), $realContext);

    expect(DB::table('import_mappings')->count())->toBe($before + 1);
});

test('a failing stage marks the import run as failed and stops the remaining stages', function (): void {
    $ranStages = [];
    $registry = new ImportStageRegistry([
        new FakeImportStage('fixture_a', [], function () use (&$ranStages): StageResult {
            $ranStages[] = 'fixture_a';

            throw new RuntimeException('boom');
        }),
        new FakeImportStage('fixture_b', ['fixture_a'], function () use (&$ranStages): StageResult {
            $ranStages[] = 'fixture_b';

            return new StageResult;
        }),
    ]);
    $runner = new ImportRunner($registry);
    $importRun = importRunnerRunStartRun();
    $context = new ImportContext(importRun: $importRun);

    expect(fn () => $runner->run($runner->plan(), $context))->toThrow(RuntimeException::class, 'boom');

    expect($ranStages)->toBe(['fixture_a'])
        ->and($importRun->fresh()->status)->toBe(ImportRunStatus::Failed)
        ->and($importRun->fresh()->finished_at)->not->toBeNull();
});
