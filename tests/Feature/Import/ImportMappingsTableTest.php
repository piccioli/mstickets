<?php

declare(strict_types=1);

use App\Import\Models\ImportMapping;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

test('import_mappings table has the columns required by §5.2', function (): void {
    expect(Schema::hasColumns('import_mappings', [
        'id', 'source_table', 'source_key', 'target_table', 'target_id', 'created_at', 'updated_at',
    ]))->toBeTrue();
});

test('is unique on (source_table, source_key, target_table)', function (): void {
    ImportMapping::create([
        'source_table' => 'stories',
        'source_key' => '123',
        'target_table' => 'tickets',
        'target_id' => 1,
    ]);

    expect(fn () => ImportMapping::create([
        'source_table' => 'stories',
        'source_key' => '123',
        'target_table' => 'tickets',
        'target_id' => 2,
    ]))->toThrow(QueryException::class);
});

test('the same source key may map to two different target tables', function (): void {
    ImportMapping::create([
        'source_table' => 'users',
        'source_key' => '1',
        'target_table' => 'users',
        'target_id' => 10,
    ]);

    $second = ImportMapping::create([
        'source_table' => 'users',
        'source_key' => '1',
        'target_table' => 'organizations',
        'target_id' => 20,
    ]);

    expect($second->exists)->toBeTrue();
});
