<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Enums\CaiDocumentSource;
use App\Domain\CaiDirectory\Import\CaiDatapackImporter;
use App\Domain\CaiDirectory\Models\CaiDocument;
use App\Domain\CaiDirectory\Models\CaiSection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

/**
 * Aggiunge `bilanci_manuali` alla fixture del datapack, con file veri sotto `bilanci-sezioni-2026/`.
 *
 * @param  list<array{codice: string, name: string, content: string, create?: bool}>  $files
 */
function addManualBilanciTable(string $sqlitePath, array $files): void
{
    $datapackDir = dirname($sqlitePath);
    $pdo = new PDO("sqlite:{$sqlitePath}");
    $pdo->exec('CREATE TABLE bilanci_manuali (
        id INTEGER PRIMARY KEY, codice_cai TEXT NOT NULL, regione TEXT NOT NULL, anno INTEGER NOT NULL,
        tipo TEXT NOT NULL, titolo TEXT NOT NULL, filename TEXT NOT NULL, path TEXT NOT NULL,
        mime TEXT, size INTEGER, hash_sha256 TEXT NOT NULL
    )');
    $insert = $pdo->prepare('INSERT INTO bilanci_manuali (codice_cai, regione, anno, tipo, titolo, filename, path, mime, size, hash_sha256)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');

    foreach ($files as $file) {
        $relative = "bilanci-sezioni-2026/normalized/Lombardia/{$file['codice']}/{$file['name']}";

        if ($file['create'] ?? true) {
            @mkdir(dirname("{$datapackDir}/{$relative}"), 0755, true);
            file_put_contents("{$datapackDir}/{$relative}", $file['content']);
        }

        $insert->execute([$file['codice'], 'Lombardia', 2025, 'rendiconto_cassa', 'Mod D', $file['name'], $relative, 'application/pdf', strlen($file['content']), hash('sha256', $file['content'])]);
    }
}

function importManualFixture(array $files): array
{
    Storage::fake('cai-documents');
    $fixture = makeCaiDatapackFixture();
    addManualBilanciTable($fixture['sqlitePath'], $files);

    return $fixture;
}

test('creates manual documents linked to the section, idempotent on the second run', function (): void {
    $fixture = importManualFixture([['codice' => '9216049', 'name' => 'a.pdf', 'content' => 'AAA']]);

    $this->artisan('cai:import-datapack', ['--path' => $fixture['sqlitePath']])
        ->expectsOutputToContain('documenti_manuali: letti 1, creati 1, aggiornati 0, saltati 0')
        ->assertSuccessful()
        ->run();

    $document = CaiDocument::query()->where('source', CaiDocumentSource::Manual)->firstOrFail();
    expect($document->cai_section_id)->toBe('9216049')
        ->and($document->cai_runts_registration_id)->toBeNull()
        ->and($document->file_name)->toBe('a.pdf')
        ->and($document->hash)->toBe(hash('sha256', 'AAA'))
        ->and($document->financial_analysis_status)->toBeNull()
        ->and($document->file_path)->toStartWith('9216049/')->toEndWith('-a.pdf');
    Storage::disk('cai-documents')->assertExists($document->file_path);

    $this->artisan('cai:import-datapack', ['--path' => $fixture['sqlitePath']])
        ->expectsOutputToContain('documenti_manuali: letti 1, creati 0, aggiornati 0, saltati 1')
        ->assertSuccessful()
        ->run();

    expect(CaiDocument::query()->where('source', CaiDocumentSource::Manual)->count())->toBe(1);
});

test('unknown section is skipped and a missing file is skipped with a warning without stopping the others', function (): void {
    $fixture = importManualFixture([
        ['codice' => '9999999', 'name' => 'x.pdf', 'content' => 'XXX'],
        ['codice' => '9216049', 'name' => 'missing.pdf', 'content' => 'MMM', 'create' => false],
        ['codice' => '9216049', 'name' => 'ok.pdf', 'content' => 'OOO'],
    ]);

    $this->artisan('cai:import-datapack', ['--path' => $fixture['sqlitePath']])
        ->expectsOutputToContain('documenti_manuali: letti 3, creati 1, aggiornati 0, saltati 2')
        ->expectsOutputToContain('file mancante')
        ->assertSuccessful()
        ->run();

    expect(CaiDocument::query()->where('source', CaiDocumentSource::Manual)->pluck('file_name')->all())->toBe(['ok.pdf']);
});

test('dry-run writes nothing but reports the same counts', function (): void {
    $fixture = importManualFixture([['codice' => '9216049', 'name' => 'a.pdf', 'content' => 'AAA']]);

    $this->artisan('cai:import-datapack', ['--path' => $fixture['sqlitePath'], '--dry-run' => true])
        ->expectsOutputToContain('documenti_manuali: letti 1, creati 1, aggiornati 0, saltati 0')
        ->assertSuccessful()
        ->run();

    expect(CaiDocument::query()->count())->toBe(0);
    Storage::disk('cai-documents')->assertDirectoryEmpty('/');
});

test('absent bilanci_manuali table does nothing and prints no warning', function (): void {
    Storage::fake('cai-documents');
    $fixture = makeCaiDatapackFixture();

    $this->artisan('cai:import-datapack', ['--path' => $fixture['sqlitePath']])
        ->doesntExpectOutputToContain('documenti_manuali: errore')
        ->doesntExpectOutputToContain('file mancante')
        ->assertSuccessful()
        ->run();

    expect(CaiDocument::query()->where('source', CaiDocumentSource::Manual)->count())->toBe(0);
});

test('pre-existing RUNTS documents are left intact', function (): void {
    $fixture = importManualFixture([['codice' => '9216049', 'name' => 'a.pdf', 'content' => 'AAA']]);

    $this->artisan('cai:import-datapack', ['--path' => $fixture['sqlitePath']])->assertSuccessful()->run();

    expect(CaiDocument::query()->where('source', '!=', CaiDocumentSource::Manual)->count())->toBe(1);
});

test('scoped import (only section code / skip section fields) does not import manual documents', function (): void {
    $fixture = importManualFixture([['codice' => '9216049', 'name' => 'a.pdf', 'content' => 'AAA']]);
    foreach (['9216049', '9216050'] as $code) {
        CaiSection::create(['codice_cai' => $code, 'name' => 'X', 'region' => 'Lombardia']);
    }

    $results = app(CaiDatapackImporter::class)->import($fixture['sqlitePath'], false, '9216049');
    expect($results)->not->toHaveKey('documenti_manuali');

    $results = app(CaiDatapackImporter::class)->import($fixture['sqlitePath'], false, null, true);
    expect($results)->not->toHaveKey('documenti_manuali')
        ->and(CaiDocument::query()->where('source', CaiDocumentSource::Manual)->count())->toBe(0);
});
