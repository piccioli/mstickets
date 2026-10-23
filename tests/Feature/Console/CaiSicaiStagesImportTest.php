<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Import\CaiDatapackImporter;
use App\Domain\CaiDirectory\Import\CaiOsm2caiExtractWriter;
use App\Domain\CaiDirectory\Models\CaiSicaiStage;
use App\Domain\CaiDirectory\Models\CaiTrail;
use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/** Tappe in `cai_sicai_stages` come `sentiero|lettera|numero|variante|codice|chiave`, in ordine di chiave. */
function sicaiStageRows(): array
{
    return CaiSicaiStage::query()->orderBy('sort_key')->orderBy('cai_trail_id')->get()
        ->map(fn (CaiSicaiStage $stage): string => implode('|', [$stage->cai_trail_id, $stage->letter ?? '-', $stage->number ?? '-', $stage->variant ?? '-', $stage->code, $stage->sort_key]))
        ->all();
}

test('the extract writer derives the stage code from the OSM tags or the itinerary name', function (): void {
    $pdo = new PDO('sqlite:'.makeTrailTerritoriesDatapack());

    expect($pdo->query('SELECT trail_id, itinerary_name, letter, number, variant, code, sort_key FROM '.CaiOsm2caiExtractWriter::SICAI_STAGES_TABLE.' ORDER BY trail_id')->fetchAll(PDO::FETCH_NUM))
        ->toBe([
            [101, 'Sentiero Italia - Lombardia D00', 'D', 18, 'S', 'D18S', 'D018S'],
            [102, 'Sentiero Italia - Liguria G00', 'G', null, null, 'AV16', 'G999 AV16'],
            [103, 'Sentiero Italia - Toscana/Emilia-Romagna L00', 'L', 1, 'AP', 'L01AP', 'L001AP'],
        ]);
});

test('cai:import-osm2cai-extract prints the SICAI stage counts', function (): void {
    $datapack = makeHutsDatapack([]);

    $this->artisan('cai:import-osm2cai-extract', ['directory' => base_path('tests/Fixtures/osm2cai'), '--datapack' => $datapack])
        ->expectsOutputToContain('Tappe SICAI: 3 (senza numero nel nome: 1, senza lettera: 0)')
        ->assertSuccessful();
});

test('imports the SICAI stages after the trails', function (): void {
    $results = app(CaiDatapackImporter::class)->import(makeTrailTerritoriesDatapack(), false);

    expect($results['snapshot_tappe_sicai']->read)->toBe(3)
        ->and($results['snapshot_tappe_sicai']->created)->toBe(3)
        ->and($results['snapshot_tappe_sicai']->warnings)->toBe([])
        ->and(sicaiStageRows())->toBe(['101|D|18|S|D18S|D018S', '102|G|-|-|AV16|G999 AV16', '103|L|1|AP|L01AP|L001AP'])
        ->and(CaiTrail::query()->findOrFail(101)->sicaiStage?->code)->toBe('D18S')
        ->and(CaiSicaiStage::query()->findOrFail(103)->trail?->name)->toBe('Anello')
        ->and(CaiSicaiStage::query()->findOrFail(103)->itinerary_name)->toBe('Sentiero Italia - Toscana/Emilia-Romagna L00');
});

test('a second run writes nothing', function (): void {
    $sqlite = makeTrailTerritoriesDatapack();
    app(CaiDatapackImporter::class)->import($sqlite, false);

    $result = app(CaiDatapackImporter::class)->import($sqlite, false)['snapshot_tappe_sicai'];

    expect($result->created)->toBe(0)
        ->and($result->updated)->toBe(0)
        ->and($result->deleted)->toBe(0)
        ->and($result->skipped)->toBe(3);
});

test('updates changed stages and deletes the ones gone from the source', function (): void {
    $sqlite = makeTrailTerritoriesDatapack();
    app(CaiDatapackImporter::class)->import($sqlite, false);

    makeTrailTerritoriesDatapack(['sicai_stages.csv' => "trail_id,itinerary_name,osm_name,osm_description,ref\n101,Sentiero Italia - Lombardia D00,Sentiero Italia - Tappa D19,,SI\n103,Sentiero Italia - Toscana/Emilia-Romagna L00,Sentiero Italia - Tappa SI L01AP,,SI\n"], $sqlite);
    $result = app(CaiDatapackImporter::class)->import($sqlite, false)['snapshot_tappe_sicai'];

    expect($result->updated)->toBe(1)
        ->and($result->skipped)->toBe(1)
        ->and($result->deleted)->toBe(1)
        ->and(sicaiStageRows())->toBe(['101|D|19|-|D19|D019', '103|L|1|AP|L01AP|L001AP']);
});

test('a deleted trail takes its stage with it', function (): void {
    $sqlite = makeTrailTerritoriesDatapack();
    app(CaiDatapackImporter::class)->import($sqlite, false);

    makeTrailTerritoriesDatapack([
        'trails.csv' => implode("\n", array_slice(explode("\n", (string) file_get_contents(base_path('tests/Fixtures/osm2cai/trails.csv'))), 0, 3))."\n",
        'trail_territory.csv' => "trail_id,region_id,province_id,sector_id,percentage\n",
        'sicai_stages.csv' => "trail_id,itinerary_name,osm_name,osm_description,ref\n101,Sentiero Italia - Lombardia D00,,Sentiero Italia - Tappa D18S,SI\n102,Sentiero Italia - Liguria G00,,,AV16\n",
    ], $sqlite);
    app(CaiDatapackImporter::class)->import($sqlite, false);

    expect(CaiTrail::query()->pluck('id')->sort()->values()->all())->toBe([101, 102])
        ->and(CaiSicaiStage::query()->pluck('cai_trail_id')->sort()->values()->all())->toBe([101, 102]);
});

test('skips stages of trails that were not imported with a warning', function (): void {
    $sqlite = makeTrailTerritoriesDatapack();
    (new PDO('sqlite:'.$sqlite))->exec('INSERT INTO '.CaiOsm2caiExtractWriter::SICAI_STAGES_TABLE." VALUES (999, 'Sentiero Italia - Lombardia D00', 'D', 1, NULL, 'D01', 'D001')");

    $result = app(CaiDatapackImporter::class)->import($sqlite, false)['snapshot_tappe_sicai'];

    expect($result->warnings)->toBe(['1 tappe SICAI di sentieri non importati saltate'])
        ->and($result->created)->toBe(3)
        ->and(CaiSicaiStage::query()->count())->toBe(3);
});

test('dry-run counts without writing', function (): void {
    $result = app(CaiDatapackImporter::class)->import(makeTrailTerritoriesDatapack(), true)['snapshot_tappe_sicai'];

    expect($result->created)->toBe(3)
        ->and(CaiSicaiStage::query()->count())->toBe(0);
});

test('without the stages table the step is skipped with a warning and the stages are kept', function (): void {
    $sqlite = makeTrailTerritoriesDatapack();
    app(CaiDatapackImporter::class)->import($sqlite, false);
    (new PDO('sqlite:'.$sqlite))->exec('DROP TABLE '.CaiOsm2caiExtractWriter::SICAI_STAGES_TABLE);

    $result = app(CaiDatapackImporter::class)->import($sqlite, false)['snapshot_tappe_sicai'];

    expect($result->warnings)->toBe(['Tabella snap_osm2cai_sicai_stages assente nel datapack: tappe SICAI non aggiornate (bin/update-osm2cai-datapack)'])
        ->and(CaiSicaiStage::query()->count())->toBe(3);
});

test('stages are read-only: staff see them, nobody writes', function (): void {
    app(CaiDatapackImporter::class)->import(makeTrailTerritoriesDatapack(), false);
    $stage = CaiSicaiStage::query()->findOrFail(101);
    $staff = userWithPermissions(Permission::CaiDirectoryView);
    $other = User::factory()->create();

    expect($staff->can('view', $stage))->toBeTrue()
        ->and($other->can('view', $stage))->toBeFalse()
        ->and($staff->can('update', $stage))->toBeFalse()
        ->and($staff->can('delete', $stage))->toBeFalse()
        ->and($staff->can('create', CaiSicaiStage::class))->toBeFalse();
});
