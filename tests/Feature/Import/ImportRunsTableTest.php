<?php

declare(strict_types=1);

use App\Import\Enums\ImportRunStatus;
use App\Import\Models\ImportRun;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

test('import_runs table has the columns required by §5.2', function (): void {
    expect(Schema::hasColumns('import_runs', [
        'id', 'started_at', 'finished_at', 'dump_label', 'stages',
        'status', 'is_dry_run', 'notes', 'created_at', 'updated_at',
    ]))->toBeTrue();
});

test('casts stages to array, status to enum and is_dry_run to boolean', function (): void {
    $run = ImportRun::create([
        'started_at' => now(),
        'dump_label' => 'dump-2026-07-26',
        'stages' => ['users', 'stories'],
        'status' => ImportRunStatus::Running,
        'is_dry_run' => true,
    ])->fresh();

    expect($run->stages)->toBe(['users', 'stories'])
        ->and($run->status)->toBe(ImportRunStatus::Running)
        ->and($run->is_dry_run)->toBeTrue()
        ->and($run->finished_at)->toBeNull();
});

test('status defaults to running when not specified', function (): void {
    $run = ImportRun::create([
        'started_at' => now(),
        'dump_label' => 'dump-2026-07-26',
    ])->fresh();

    expect($run->status)->toBe(ImportRunStatus::Running)
        ->and($run->is_dry_run)->toBeFalse();
});
