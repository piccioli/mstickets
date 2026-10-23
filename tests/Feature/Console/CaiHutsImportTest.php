<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Enums\CaiHutStatus;
use App\Domain\CaiDirectory\Enums\CaiHutType;
use App\Domain\CaiDirectory\Import\CaiDatapackImporter;
use App\Domain\CaiDirectory\Models\CaiHut;
use App\Domain\CaiDirectory\Models\CaiSection;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Dettaglio API rifugi.cai.it come salvato in `snap_cai_huts.payload`.
 *
 * @param  array<string, mixed>  $fields  nome → valore
 * @return array<string, mixed>
 */
function hutApiDetail(int $idCai, string $title, array $fields = []): array
{
    $fields += [
        'branch_id' => '9218001',
        'branch' => 'SAT',
        'type' => 'Rifugio custodito',
        'status' => 'In attività',
        'altitude_geo' => 2491,
        'municipality_geo' => 'San Lorenzo Dorsino',
        'posti_letto_service' => '150',
        'wifi_service' => '0',
        'elettricita_service' => '1',
        'contract_fee_management_property' => '48564,05',
        'contract_end_date_management_property' => '2027-03-31',
    ];

    return [
        'id' => $idCai,
        'id_cai' => $idCai,
        'title' => $title,
        'slug' => str($title)->slug()->value(),
        'geo' => ['type' => 'Point', 'coordinates' => [10.8764, 46.1697]],
        'updated_at' => '2026-09-12T08:30:00.000000Z',
        'fields' => collect($fields)->map(fn ($value, $name): array => ['name' => $name, 'value' => $value, 'data' => null])->values()->all(),
        'media' => [['original_url' => 'https://rifugi.cai.it/media/images/1/tosa.jpg', 'order_column' => 1]],
    ];
}

/**
 * Datapack con le tabelle legacy vuote e `snap_cai_huts` con i dettagli indicati.
 *
 * @param  list<array<string, mixed>>  $details
 */
function makeHutsDatapack(array $details, ?string $sqlite = null): string
{
    if ($sqlite === null) {
        $dir = sys_get_temp_dir().'/snap-huts-'.bin2hex(random_bytes(4));
        mkdir($dir);
        $sqlite = $dir.'/runts-cai.sqlite';
    }

    $pdo = new PDO('sqlite:'.$sqlite);
    $pdo->exec('CREATE TABLE IF NOT EXISTS sezioni_cai (codice_cai TEXT)');
    $pdo->exec('CREATE TABLE IF NOT EXISTS sottosezioni_cai (cai_codice TEXT, cai_sezione_codice TEXT)');
    $pdo->exec('CREATE TABLE IF NOT EXISTS enti (id_runts TEXT)');
    $pdo->exec('CREATE TABLE IF NOT EXISTS bilanci (id INTEGER)');
    $pdo->exec('CREATE TABLE IF NOT EXISTS cariche_sociali (id INTEGER)');
    $pdo->exec('CREATE TABLE IF NOT EXISTS allegati (id INTEGER)');
    $pdo->exec('DROP TABLE IF EXISTS snap_cai_huts');
    $pdo->exec('CREATE TABLE snap_cai_huts (id_cai INTEGER PRIMARY KEY, payload TEXT NOT NULL)');
    $insert = $pdo->prepare('INSERT INTO snap_cai_huts (id_cai, payload) VALUES (?, ?)');

    foreach ($details as $detail) {
        $insert->execute([$detail['id_cai'], json_encode($detail)]);
    }

    return $sqlite;
}

test('imports huts from the datapack with normalized columns', function (): void {
    caiSection(['codice_cai' => '9218001', 'name' => 'SAT']);
    $sqlite = makeHutsDatapack([hutApiDetail(921800101, 'Rifugio Tosa e Tommaso Pedrotti', ['alias' => 'Rifugio della Tosa'])]);

    $result = app(CaiDatapackImporter::class)->import($sqlite, false)['snapshot_rifugi'];

    expect($result->read)->toBe(1)->and($result->created)->toBe(1);

    $hut = CaiHut::query()->sole();
    expect($hut->id_cai)->toBe(921800101)
        ->and($hut->name)->toBe('Rifugio Tosa e Tommaso Pedrotti')
        ->and($hut->alias)->toBe('Rifugio della Tosa')
        ->and($hut->type)->toBe(CaiHutType::Managed)
        ->and($hut->status)->toBe(CaiHutStatus::Active)
        ->and($hut->latitude)->toBe('46.1697000')
        ->and($hut->longitude)->toBe('10.8764000')
        ->and($hut->altitude)->toBe(2491)
        ->and($hut->beds)->toBe(150)
        ->and($hut->photos)->toBe(['https://rifugi.cai.it/media/images/1/tosa.jpg'])
        ->and($hut->services)->toMatchArray(['wifi' => 0, 'elettricita' => 1])
        ->and($hut->management['contract']['fee'])->toBe(48564.05)
        ->and($hut->management['contract']['end_date'])->toBe('2027-03-31')
        ->and($hut->fields['branch'])->toBe('SAT')
        ->and($hut->source_updated_at?->format('Y-m-d H:i'))->toBe('2026-09-12 08:30')
        ->and(CaiSection::query()->find('9218001')->huts()->count())->toBe(1);
});

test('updates changed huts and deletes those no longer in the source', function (): void {
    $sqlite = makeHutsDatapack([hutApiDetail(1, 'Rifugio A'), hutApiDetail(2, 'Bivacco B', ['type' => 'Bivacco'])]);
    app(CaiDatapackImporter::class)->import($sqlite, false);

    makeHutsDatapack([hutApiDetail(1, 'Rifugio A rinominato')], $sqlite);
    $result = app(CaiDatapackImporter::class)->import($sqlite, false)['snapshot_rifugi'];

    expect($result->updated)->toBe(1)
        ->and($result->created)->toBe(0)
        ->and($result->deleted)->toBe(1)
        ->and(CaiHut::query()->pluck('name')->all())->toBe(['Rifugio A rinominato']);
});

test('a second run creates and updates nothing', function (): void {
    $sqlite = makeHutsDatapack([hutApiDetail(1, 'Rifugio A'), hutApiDetail(2, 'Bivacco B', ['type' => 'Bivacco'])]);
    app(CaiDatapackImporter::class)->import($sqlite, false);

    $second = app(CaiDatapackImporter::class)->import($sqlite, false)['snapshot_rifugi'];

    expect($second->created)->toBe(0)
        ->and($second->updated)->toBe(0)
        ->and($second->deleted)->toBe(0)
        ->and($second->skipped)->toBe(2);
});

test('dry-run counts without writing or deleting', function (): void {
    CaiHut::create(['id_cai' => 99, 'name' => 'Rifugio sparito']);
    $sqlite = makeHutsDatapack([hutApiDetail(1, 'Rifugio A')]);

    $result = app(CaiDatapackImporter::class)->import($sqlite, true)['snapshot_rifugi'];

    expect($result->created)->toBe(1)
        ->and($result->deleted)->toBe(1)
        ->and(CaiHut::query()->pluck('id_cai')->all())->toBe([99]);
});

test('an unknown type or status does not break the import', function (): void {
    $sqlite = makeHutsDatapack([hutApiDetail(1, 'Rudere X', ['type' => 'Malga', 'status' => 'Rudere'])]);

    $result = app(CaiDatapackImporter::class)->import($sqlite, false)['snapshot_rifugi'];

    $hut = CaiHut::query()->sole();
    expect($result->created)->toBe(1)
        ->and($result->warnings)->toHaveCount(2)
        ->and($hut->type)->toBeNull()
        ->and($hut->status)->toBeNull()
        ->and($hut->fields['type'])->toBe('Malga')
        ->and($hut->fields['status'])->toBe('Rudere');
});

test('a datapack without snap_cai_huts leaves the huts untouched', function (): void {
    CaiHut::create(['id_cai' => 99, 'name' => 'Rifugio esistente']);
    $sqlite = makeHutsDatapack([]);
    (new PDO('sqlite:'.$sqlite))->exec('DROP TABLE snap_cai_huts');

    $results = app(CaiDatapackImporter::class)->import($sqlite, false);

    expect($results)->not->toHaveKey('snapshot_rifugi')
        ->and(CaiHut::query()->count())->toBe(1);
});

test('cai:import-datapack prints the snapshot_rifugi summary line', function (): void {
    CaiHut::create(['id_cai' => 99, 'name' => 'Rifugio sparito']);
    $sqlite = makeHutsDatapack([hutApiDetail(1, 'Rifugio A')]);

    $this->artisan('cai:import-datapack', ['--path' => $sqlite])
        ->expectsOutputToContain('- snapshot_rifugi: letti 1, creati 1, aggiornati 0, saltati 0, eliminati 1')
        ->assertSuccessful();
});
