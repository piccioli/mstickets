<?php

declare(strict_types=1);

beforeEach(function (): void {
    $this->datapack = makeHutsDatapack([hutApiDetail(1, 'Rifugio Vecchio')]);
    $this->pdo = new PDO('sqlite:'.$this->datapack);
    $this->pdo->exec("INSERT INTO sezioni_cai (codice_cai) VALUES ('9218001'), ('9226005')");
    $this->csv = base_path('tests/Fixtures/osm2cai');
});

/**
 * Cartella temporanea con i CSV della fixture, `$overrides` nome file → contenuto (null = file assente).
 *
 * @param  array<string, string|null>  $overrides
 */
function osm2caiCsvDirectory(array $overrides): string
{
    $dir = sys_get_temp_dir().'/osm2cai-csv-'.bin2hex(random_bytes(4));
    mkdir($dir);

    foreach (['trails.csv', 'trail_sections.csv'] as $file) {
        $content = array_key_exists($file, $overrides) ? $overrides[$file] : file_get_contents(base_path('tests/Fixtures/osm2cai/'.$file));

        if ($content !== null) {
            file_put_contents($dir.'/'.$file, $content);
        }
    }

    return $dir;
}

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

test('fails without touching anything when the directory or the datapack do not exist', function (): void {
    $this->artisan('cai:import-osm2cai-extract', ['directory' => '/non/esiste', '--datapack' => $this->datapack])
        ->expectsOutputToContain('Cartella dei CSV non trovata')
        ->assertFailed();

    $this->artisan('cai:import-osm2cai-extract', ['directory' => $this->csv, '--datapack' => '/non/esiste.sqlite'])
        ->expectsOutputToContain('File datapack non trovato')
        ->assertFailed();

    expect($this->pdo->query("SELECT COUNT(*) FROM sqlite_master WHERE name LIKE 'snap_osm2cai_%'")->fetchColumn())->toBe(0);
});
