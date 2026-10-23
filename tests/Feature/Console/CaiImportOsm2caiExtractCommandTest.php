<?php

declare(strict_types=1);

beforeEach(function (): void {
    $this->datapack = makeHutsDatapack([hutApiDetail(1, 'Rifugio Vecchio')]);
    $this->pdo = new PDO('sqlite:'.$this->datapack);
    $this->pdo->exec("INSERT INTO sezioni_cai (codice_cai) VALUES ('9218001'), ('9226005')");
    $this->csv = base_path('tests/Fixtures/osm2cai');
});

test('writes snap_osm2cai_trails and snap_osm2cai_trail_sections with typed values and a summary', function (): void {
    $this->artisan('cai:import-osm2cai-extract', ['directory' => $this->csv, '--datapack' => $this->datapack])
        ->expectsOutputToContain('Sentieri estratti: 3')
        ->expectsOutputToContain('Collegati ad almeno una Sezione: 1')
        ->expectsOutputToContain('Codici di Sezione non presenti in sezioni_cai: 1')
        ->expectsOutputToContain('9999999: 1 sentieri')
        ->assertSuccessful();

    $trails = $this->pdo->query('SELECT * FROM snap_osm2cai_trails ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);

    expect($trails)->toHaveCount(3)
        ->and($trails[0])->toMatchArray([
            'id' => 101,
            'osmid' => 7001,
            'ref' => '10',
            'name' => 'Sentiero "Alto"',
            'from_place' => 'Rifugio A',
            'to_place' => 'Passo B',
            'cai_scale' => 'E',
            'length_km' => 5.432,
            'ascent' => 640,
            'descent' => 12,
            'status' => 4,
            'issues_status' => 'percorribile',
            'issues_last_update' => '2025-06-01',
            'validation_date' => '2024-09-15 10:00:00',
        ])
        ->and(json_decode($trails[0]['geometry_geojson'], true)['type'])->toBe('MultiLineString')
        ->and($trails[1])->toMatchArray(['ref' => null, 'name' => null, 'cai_scale' => null, 'ascent' => null, 'validation_date' => null])
        ->and($this->pdo->query('SELECT trail_id, section_code FROM snap_osm2cai_trail_sections ORDER BY trail_id, section_code')->fetchAll(PDO::FETCH_NUM))
        ->toBe([[101, '9218001'], [101, '9226005'], [103, '9999999']]);
});

test('writes the Italian territories with their statistics, drops the foreign ones and prints the totals per region', function (): void {
    $this->artisan('cai:import-osm2cai-extract', ['directory' => $this->csv, '--datapack' => $this->datapack])
        ->expectsOutputToContain('Province: 2 · settori: 2 · legami sentiero–territorio: 4')
        ->expectsOutputToContain('Regioni estere scartate: Ticino')
        ->expectsOutputToContain('Lombardia: 5,8 km · 1 sentieri')
        ->expectsOutputToContain('Trentino-Alto Adige: 0,0 km · 0 sentieri')
        ->expectsOutputToContain("Valle d'Aosta: 0,0 km · 0 sentieri")
        ->expectsOutputToContain('Regione assente dalla fonte: Abruzzo')
        ->assertSuccessful();

    expect($this->pdo->query('SELECT id, name, region_key FROM snap_osm2cai_regions ORDER BY id')->fetchAll(PDO::FETCH_NUM))
        ->toBe([[1, 'Lombardia', 'lombardia'], [2, 'Trentino-Alto Adige/Südtirol', 'trentino_alto_adige'], [4, "Valle d'Aosta / Vallée d'Aoste", 'valle_d_aosta']])
        ->and($this->pdo->query('SELECT id, region_id, name, code FROM snap_osm2cai_provinces ORDER BY id')->fetchAll(PDO::FETCH_NUM))
        ->toBe([[10, 1, 'Sondrio', 'SO'], [11, 2, 'Trento', 'TN']])
        ->and($this->pdo->query('SELECT id, province_id, code, full_code, name FROM snap_osm2cai_sectors ORDER BY id')->fetchAll(PDO::FETCH_NUM))
        ->toBe([[100, 10, 'A', 'DSOA1', 'Valtellina'], [101, 10, 'B', 'DSOB2', 'DSOB2']])
        ->and(json_decode((string) $this->pdo->query('SELECT geometry_geojson FROM snap_osm2cai_sectors WHERE id = 100')->fetchColumn(), true)['type'])->toBe('MultiPolygon')
        ->and($this->pdo->query('SELECT territory_type, territory_id, status, km, present_routes FROM snap_osm2cai_territory_km ORDER BY territory_type, territory_id, status')->fetchAll(PDO::FETCH_NUM))
        ->toBe([['province', 10, 4, 5.432, 1], ['region', 1, 1, 0.4, 0], ['region', 1, 4, 5.432, 1], ['sector', 100, 4, 5.432, 1]])
        ->and($this->pdo->query('SELECT trail_id, region_id, province_id, sector_id, percentage FROM snap_osm2cai_trail_territory ORDER BY trail_id, province_id, sector_id')->fetchAll(PDO::FETCH_NUM))
        ->toBe([[101, 1, null, null, 100.0], [101, 1, 10, null, 100.0], [101, 1, 10, 100, 100.0], [102, 1, null, null, 50.0]]);
});

test('rolls back when two source regions match the same Italian region', function (): void {
    $this->artisan('cai:import-osm2cai-extract', ['directory' => $this->csv, '--datapack' => $this->datapack])->assertSuccessful();

    $dir = osm2caiCsvDirectory(['regions.csv' => "id,name\n1,Lombardia\n5,LOMBARDIA\n"]);

    $this->artisan('cai:import-osm2cai-extract', ['directory' => $dir, '--datapack' => $this->datapack])
        ->expectsOutputToContain('corrispondono entrambe a Lombardia')
        ->assertFailed();

    expect($this->pdo->query('SELECT id FROM snap_osm2cai_regions ORDER BY id')->fetchAll(PDO::FETCH_COLUMN))->toBe([1, 2, 4])
        ->and($this->pdo->query('SELECT COUNT(*) FROM snap_osm2cai_trail_territory')->fetchColumn())->toBe(4);
});

test('rewrites the snap_osm2cai tables on every run and leaves the other datapack tables intact', function (): void {
    $this->artisan('cai:import-osm2cai-extract', ['directory' => $this->csv, '--datapack' => $this->datapack])->assertSuccessful();

    $dir = osm2caiCsvDirectory(['trail_sections.csv' => "trail_id,section_code\n102,9218001\n"]);
    $this->artisan('cai:import-osm2cai-extract', ['directory' => $dir, '--datapack' => $this->datapack])->assertSuccessful();

    expect($this->pdo->query('SELECT COUNT(*) FROM snap_osm2cai_trails')->fetchColumn())->toBe(3)
        ->and($this->pdo->query('SELECT trail_id FROM snap_osm2cai_trail_sections')->fetchAll(PDO::FETCH_COLUMN))->toBe([102])
        ->and($this->pdo->query('SELECT codice_cai FROM sezioni_cai ORDER BY codice_cai')->fetchAll(PDO::FETCH_COLUMN))->toBe(['9218001', '9226005'])
        ->and($this->pdo->query('SELECT id_cai FROM snap_cai_huts')->fetchAll(PDO::FETCH_COLUMN))->toBe([1]);
});

test('rolls back and keeps the previous snap_osm2cai tables when a CSV is broken', function (?string $trailSections, string $message): void {
    $this->artisan('cai:import-osm2cai-extract', ['directory' => $this->csv, '--datapack' => $this->datapack])->assertSuccessful();

    $dir = osm2caiCsvDirectory([
        'trails.csv' => "id,osmid,ref,name,from_place,to_place,cai_scale,length_km,ascent,descent,status,issues_status,issues_last_update,validation_date,geometry_geojson\n999,1,,,,,,1,,,3,,,,\n",
        'trail_sections.csv' => $trailSections,
    ]);

    $this->artisan('cai:import-osm2cai-extract', ['directory' => $dir, '--datapack' => $this->datapack])
        ->expectsOutputToContain($message)
        ->assertFailed();

    expect($this->pdo->query('SELECT id FROM snap_osm2cai_trails ORDER BY id')->fetchAll(PDO::FETCH_COLUMN))->toBe([101, 102, 103])
        ->and($this->pdo->query('SELECT COUNT(*) FROM snap_osm2cai_trail_sections')->fetchColumn())->toBe(3)
        ->and($this->pdo->query('SELECT id_cai FROM snap_cai_huts')->fetchAll(PDO::FETCH_COLUMN))->toBe([1]);
})->with([
    'missing file' => [null, 'CSV non trovato'],
    'unexpected header' => ["trail_id,club_code\n999,9218001\n", 'Intestazione inattesa'],
]);

test('rolls back when a territorial CSV is missing', function (): void {
    $this->artisan('cai:import-osm2cai-extract', ['directory' => $this->csv, '--datapack' => $this->datapack])->assertSuccessful();

    $dir = osm2caiCsvDirectory(['trails.csv' => "id,osmid,ref,name,from_place,to_place,cai_scale,length_km,ascent,descent,status,issues_status,issues_last_update,validation_date,geometry_geojson\n999,1,,,,,,1,,,3,,,,\n", 'trail_territory.csv' => null]);

    $this->artisan('cai:import-osm2cai-extract', ['directory' => $dir, '--datapack' => $this->datapack])
        ->expectsOutputToContain('CSV non trovato')
        ->assertFailed();

    expect($this->pdo->query('SELECT id FROM snap_osm2cai_trails ORDER BY id')->fetchAll(PDO::FETCH_COLUMN))->toBe([101, 102, 103])
        ->and($this->pdo->query('SELECT COUNT(*) FROM snap_osm2cai_trail_territory')->fetchColumn())->toBe(4);
});

test('fails without touching anything when the directory or the datapack do not exist', function (): void {
    $this->artisan('cai:import-osm2cai-extract', ['directory' => '/non/esiste', '--datapack' => $this->datapack])
        ->expectsOutputToContain('Cartella dei CSV non trovata')
        ->assertFailed();

    $this->artisan('cai:import-osm2cai-extract', ['directory' => $this->csv, '--datapack' => '/non/esiste.sqlite'])
        ->expectsOutputToContain('File datapack non trovato')
        ->assertFailed();

    expect($this->pdo->query("SELECT COUNT(*) FROM sqlite_master WHERE name LIKE 'snap_osm2cai_%'")->fetchColumn())->toBe(0);
});
