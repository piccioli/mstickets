<?php

declare(strict_types=1);

use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

function makeManualBilanciCommandFixture(): array
{
    $root = sys_get_temp_dir().'/bilanci-cmd-'.bin2hex(random_bytes(4));
    $staging = $root.'/bilanci-sezioni-2026';
    $put = static function (string $rel, string $content) use ($staging): void {
        $path = $staging.'/normalized/'.$rel;
        @mkdir(dirname($path), 0777, true);
        file_put_contents($path, $content);
    };

    $put('Toscana/9226005 - CAI Carrara/9226005 - CAI Carrara - Mod D - Rendiconto per cassa.pdf', '%PDF uno');
    $put('Toscana/9226005 - CAI Carrara/9226005 - CAI Carrara - Relazione attività.pdf', '%PDF due');
    $put('Lazio/9258001 - CAI Roma/9258001 - CAI Roma - Bilancio consuntivo.pdf', '%PDF tre');

    $writer = new Writer;
    $writer->openToFile($staging.'/2026_Campagna_Sezioni.xlsx');
    $writer->getCurrentSheet()->setName('Sezioni');
    foreach ([
        ['', 'Nome', 'Regione', 'Bilanci'],
        ['9226005', 'CAI Carrara', 'TOSCANA', 'RICEVUTO'],
        ['9258001', 'CAI Roma', 'LAZIO', 'RICEVUTO'],
        ['9216157', 'CAI Iseo', 'LOMBARDIA', 'RICEVUTO'],
    ] as $row) {
        $writer->addRow(Row::fromValues($row));
    }
    $writer->close();

    $sqlite = $root.'/runts-cai.sqlite';
    $pdo = new PDO('sqlite:'.$sqlite);
    $pdo->exec('CREATE TABLE sezioni_cai (codice_cai TEXT PRIMARY KEY, nome TEXT)');
    $pdo->exec('CREATE TABLE enti (id_runts INTEGER PRIMARY KEY)');
    $pdo->exec('CREATE TABLE allegati (id INTEGER PRIMARY KEY)');
    foreach (['9226005', '9258001', '9216157'] as $code) {
        $pdo->exec("INSERT INTO sezioni_cai VALUES ('{$code}', 'x')");
    }
    $pdo->exec('INSERT INTO enti VALUES (1)');
    $pdo->exec('INSERT INTO allegati VALUES (1)');
    $pdo = null;

    return ['sqlite' => $sqlite, 'staging' => $staging];
}

function manualBilanciTable(string $sqlite, string $table): array
{
    $pdo = new PDO('sqlite:'.$sqlite);

    return $pdo->query("SELECT * FROM {$table} ORDER BY 1")->fetchAll(PDO::FETCH_ASSOC);
}

test('missing datapack or staging folder fail with an explicit Italian message', function (): void {
    $fx = makeManualBilanciCommandFixture();

    $this->artisan('cai:build-manual-bilanci-datapack', ['--datapack' => '/tmp/nope-'.uniqid().'.sqlite', '--staging' => $fx['staging']])
        ->expectsOutputToContain('File datapack non trovato')
        ->assertFailed();

    $this->artisan('cai:build-manual-bilanci-datapack', ['--datapack' => $fx['sqlite'], '--staging' => '/tmp/nope-'.uniqid()])
        ->expectsOutputToContain('Cartella dei bilanci non trovata')
        ->assertFailed();
});

test('build writes bilanci_manuali, is idempotent and leaves other tables untouched', function (): void {
    $fx = makeManualBilanciCommandFixture();
    $before = [
        'sezioni_cai' => manualBilanciTable($fx['sqlite'], 'sezioni_cai'),
        'enti' => manualBilanciTable($fx['sqlite'], 'enti'),
        'allegati' => manualBilanciTable($fx['sqlite'], 'allegati'),
    ];

    $this->artisan('cai:build-manual-bilanci-datapack', ['--datapack' => $fx['sqlite'], '--staging' => $fx['staging']])
        ->expectsOutputToContain('Totali: 3 file, 2 sezioni')
        ->expectsOutputToContain('ricevuto_senza_file: 1')
        ->assertSuccessful();

    $rows = manualBilanciTable($fx['sqlite'], 'bilanci_manuali');
    expect($rows)->toHaveCount(3)
        ->and($rows[0]['codice_cai'])->toBe('9258001')
        ->and($rows[0]['tipo'])->toBe('altro')
        ->and($rows[0]['path'])->toStartWith('bilanci-sezioni-2026/normalized/Lazio/')
        ->and($rows[0]['hash_sha256'])->toBe(hash('sha256', '%PDF tre'));

    $this->artisan('cai:build-manual-bilanci-datapack', ['--datapack' => $fx['sqlite'], '--staging' => $fx['staging']])
        ->assertSuccessful();

    expect(manualBilanciTable($fx['sqlite'], 'bilanci_manuali'))->toBe($rows);
    foreach ($before as $table => $content) {
        expect(manualBilanciTable($fx['sqlite'], $table))->toBe($content);
    }
});

test('--dry-run does not modify the datapack file', function (): void {
    $fx = makeManualBilanciCommandFixture();
    $hash = hash_file('sha256', $fx['sqlite']);

    $this->artisan('cai:build-manual-bilanci-datapack', ['--datapack' => $fx['sqlite'], '--staging' => $fx['staging'], '--dry-run' => true])
        ->expectsOutputToContain('Totali: 3 file')
        ->assertSuccessful();

    expect(hash_file('sha256', $fx['sqlite']))->toBe($hash);
});
