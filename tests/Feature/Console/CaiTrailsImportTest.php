<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Enums\CaiTrailPracticability;
use App\Domain\CaiDirectory\Enums\CaiTrailStatus;
use App\Domain\CaiDirectory\Import\CaiDatapackImporter;
use App\Domain\CaiDirectory\Models\CaiTrail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('imports trails and their section links from the datapack', function (): void {
    $sat = caiSection(['codice_cai' => '9218001', 'name' => 'SAT']);
    caiSection(['codice_cai' => '9218002', 'name' => 'SAT Rovereto']);
    $sqlite = makeTrailsDatapack(
        [osm2caiTrailRow(101, ['name' => 'Sentiero "Alto"']), osm2caiTrailRow(102, ['ref' => null, 'status' => 1, 'issues_status' => 'sconosciuto', 'ascent' => null])],
        [[101, '9218001'], [101, '9218002'], [102, '9218001'], [102, '9999999']],
    );

    $results = app(CaiDatapackImporter::class)->import($sqlite, false);

    expect($results['snapshot_sentieri']->read)->toBe(2)
        ->and($results['snapshot_sentieri']->created)->toBe(2)
        ->and($results['snapshot_sentieri_sezioni']->read)->toBe(4)
        ->and($results['snapshot_sentieri_sezioni']->created)->toBe(4);

    $trail = CaiTrail::query()->findOrFail(101);
    expect($trail->osmid)->toBe(7101)
        ->and($trail->ref)->toBe('101')
        ->and($trail->name)->toBe('Sentiero "Alto"')
        ->and($trail->from_place)->toBe('Rifugio A')
        ->and($trail->to_place)->toBe('Passo B')
        ->and($trail->cai_scale)->toBe('E')
        ->and($trail->length_km)->toBe('5.432')
        ->and($trail->ascent)->toBe(640)
        ->and($trail->descent)->toBe(12)
        ->and($trail->status)->toBe(CaiTrailStatus::Validated)
        ->and($trail->practicability)->toBe(CaiTrailPracticability::Practicable)
        ->and($trail->issues_last_update?->format('Y-m-d'))->toBe('2025-06-01')
        ->and($trail->validation_date?->format('Y-m-d'))->toBe('2024-09-15')
        ->and($trail->geometry)->toBe(['type' => 'MultiLineString', 'coordinates' => [[[10.1, 43.1], [10.2, 43.2]]]])
        ->and($trail->sections()->pluck('name')->sort()->values()->all())->toBe(['SAT', 'SAT Rovereto'])
        ->and($sat->trails()->pluck('cai_trails.id')->sort()->values()->all())->toBe([101, 102])
        ->and(CaiTrail::query()->findOrFail(102)->ref)->toBeNull()
        ->and(CaiTrail::query()->findOrFail(102)->ascent)->toBeNull()
        // Il codice osm2cai senza Sezione resta nel legame ma non è una Sezione del sentiero.
        ->and(DB::table('cai_trail_section')->where('section_code', '9999999')->count())->toBe(1)
        ->and(CaiTrail::query()->findOrFail(102)->sections()->count())->toBe(1);
});

test('updates changed trails, deletes those no longer in the source and syncs the section links', function (): void {
    $sqlite = makeTrailsDatapack(
        [osm2caiTrailRow(1), osm2caiTrailRow(2), osm2caiTrailRow(3)],
        [[1, '9218001'], [2, '9218001'], [3, '9218001']],
    );
    app(CaiDatapackImporter::class)->import($sqlite, false);

    makeTrailsDatapack(
        [osm2caiTrailRow(1, ['status' => 3, 'length_km' => 6.1]), osm2caiTrailRow(2)],
        [[1, '9218001'], [1, '9218002']],
        $sqlite,
    );
    $results = app(CaiDatapackImporter::class)->import($sqlite, false);

    expect($results['snapshot_sentieri']->updated)->toBe(1)
        ->and($results['snapshot_sentieri']->created)->toBe(0)
        ->and($results['snapshot_sentieri']->skipped)->toBe(1)
        ->and($results['snapshot_sentieri']->deleted)->toBe(1)
        ->and($results['snapshot_sentieri_sezioni']->created)->toBe(1)
        ->and($results['snapshot_sentieri_sezioni']->deleted)->toBe(2)
        ->and(CaiTrail::query()->orderBy('id')->pluck('id')->all())->toBe([1, 2])
        ->and(CaiTrail::query()->findOrFail(1)->status)->toBe(CaiTrailStatus::PreValidated)
        ->and(CaiTrail::query()->findOrFail(1)->length_km)->toBe('6.100')
        ->and(DB::table('cai_trail_section')->orderBy('cai_trail_id')->orderBy('section_code')->get()->map(fn ($row): string => $row->cai_trail_id.'|'.$row->section_code)->all())
        ->toBe(['1|9218001', '1|9218002']);
});

test('a second run creates, updates and deletes nothing', function (): void {
    $sqlite = makeTrailsDatapack(
        [osm2caiTrailRow(1), osm2caiTrailRow(2, ['ref' => null, 'issues_last_update' => null, 'validation_date' => '2024-09-15 10:00:00'])],
        [[1, '9218001'], [2, '9218002']],
    );
    app(CaiDatapackImporter::class)->import($sqlite, false);
    $updatedAt = CaiTrail::query()->findOrFail(1)->updated_at;

    $this->travel(1)->hours();
    $second = app(CaiDatapackImporter::class)->import($sqlite, false);

    expect($second['snapshot_sentieri']->created)->toBe(0)
        ->and($second['snapshot_sentieri']->updated)->toBe(0)
        ->and($second['snapshot_sentieri']->deleted)->toBe(0)
        ->and($second['snapshot_sentieri']->skipped)->toBe(2)
        ->and($second['snapshot_sentieri_sezioni']->created)->toBe(0)
        ->and($second['snapshot_sentieri_sezioni']->deleted)->toBe(0)
        ->and($second['snapshot_sentieri_sezioni']->skipped)->toBe(2)
        ->and(CaiTrail::query()->findOrFail(1)->updated_at?->equalTo($updatedAt))->toBeTrue();
});

test('dry-run counts without writing or deleting', function (): void {
    CaiTrail::create(['id' => 99, 'status' => 4, 'practicability' => 'sconosciuto']);
    DB::table('cai_trail_section')->insert(['cai_trail_id' => 99, 'section_code' => '9218001']);
    $sqlite = makeTrailsDatapack([osm2caiTrailRow(1)], [[1, '9218001']]);

    $results = app(CaiDatapackImporter::class)->import($sqlite, true);

    expect($results['snapshot_sentieri']->created)->toBe(1)
        ->and($results['snapshot_sentieri']->deleted)->toBe(1)
        ->and($results['snapshot_sentieri_sezioni']->created)->toBe(1)
        ->and($results['snapshot_sentieri_sezioni']->deleted)->toBe(1)
        ->and(CaiTrail::query()->pluck('id')->all())->toBe([99])
        ->and(DB::table('cai_trail_section')->count())->toBe(1);
});

test('a datapack without the snap_osm2cai tables skips the step with a warning and keeps the trails', function (): void {
    CaiTrail::create(['id' => 99, 'status' => 4, 'practicability' => 'sconosciuto']);
    $sqlite = makeHutsDatapack([]);

    $results = app(CaiDatapackImporter::class)->import($sqlite, false);

    expect($results['snapshot_sentieri']->read)->toBe(0)
        ->and($results['snapshot_sentieri']->warnings[0])->toContain('assenti nel datapack')
        ->and($results)->not->toHaveKey('snapshot_sentieri_sezioni')
        ->and(CaiTrail::query()->count())->toBe(1);
});

test('maps the osm2cai issues_status to the practicability', function (?string $issuesStatus, CaiTrailPracticability $expected): void {
    expect(CaiTrailPracticability::fromIssuesStatus($issuesStatus))->toBe($expected);
})->with([
    ['percorribile', CaiTrailPracticability::Practicable],
    [' Percorribile ', CaiTrailPracticability::Practicable],
    ['percorribile parzialmente', CaiTrailPracticability::WithDifficulty],
    ['percorribile con difficoltà', CaiTrailPracticability::WithDifficulty],
    ['non percorribile', CaiTrailPracticability::NotPracticable],
    ['sconosciuto', CaiTrailPracticability::Unknown],
    ['chiuso per frana', CaiTrailPracticability::Unknown],
    [null, CaiTrailPracticability::Unknown],
]);

test('the imported practicability follows the mapping', function (): void {
    $sqlite = makeTrailsDatapack([
        osm2caiTrailRow(1, ['issues_status' => 'percorribile parzialmente']),
        osm2caiTrailRow(2, ['issues_status' => 'non percorribile']),
        osm2caiTrailRow(3, ['issues_status' => 'valore nuovo']),
        osm2caiTrailRow(4, ['issues_status' => null]),
    ], []);

    app(CaiDatapackImporter::class)->import($sqlite, false);

    expect(CaiTrail::query()->orderBy('id')->get()->map(fn (CaiTrail $trail): CaiTrailPracticability => $trail->practicability)->all())
        ->toBe([CaiTrailPracticability::WithDifficulty, CaiTrailPracticability::NotPracticable, CaiTrailPracticability::Unknown, CaiTrailPracticability::Unknown]);
});

test('trail statuses carry the names and colors of the design', function (): void {
    expect(array_map(fn (CaiTrailStatus $status): array => [$status->getLabel(), $status->color()], CaiTrailStatus::cases()))->toBe([
        ['1 · Difficoltà', '#e8b500'],
        ['2 · Ricognizione', '#f07c22'],
        ['3 · Pre-validato', '#a24cc7'],
        ['4 · Validato', '#2563c9'],
    ]);
});

test('cai:import-datapack prints the trails summary lines', function (): void {
    $sqlite = makeTrailsDatapack([osm2caiTrailRow(1)], [[1, '9218001']]);

    $this->artisan('cai:import-datapack', ['--path' => $sqlite])
        ->expectsOutputToContain('- snapshot_sentieri: letti 1, creati 1, aggiornati 0, saltati 0')
        ->expectsOutputToContain('- snapshot_sentieri_sezioni: letti 1, creati 1, aggiornati 0, saltati 0')
        ->assertSuccessful();
});
