<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Enums\CaiDocumentSource;
use App\Domain\CaiDirectory\Export\CaiSnapshotExporter;
use App\Domain\CaiDirectory\Export\CaiSnapshotFileExporter;
use App\Domain\CaiDirectory\Models\CaiDocument;
use App\Domain\CaiDirectory\Models\CaiRuntsRegistration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function makeSnapshotFilesDatapack(): string
{
    $dir = sys_get_temp_dir().'/snap-files-'.bin2hex(random_bytes(4));
    mkdir($dir);
    $pdo = new PDO('sqlite:'.$dir.'/runts-cai.sqlite');
    $pdo->exec('CREATE TABLE sezioni_cai (codice_cai TEXT PRIMARY KEY)');

    return $dir.'/runts-cai.sqlite';
}

function makeSnapshotDocument(string $path, ?string $content, CaiDocumentSource $source = CaiDocumentSource::Runts, string $name = 'bilancio.pdf'): void
{
    if ($content !== null) {
        Storage::disk('cai-documents')->put($path, $content);
    }

    $attributes = [
        'document_type' => 'bilancio_esercizio', 'year' => 2024, 'title' => 'T', 'file_path' => $path,
        'file_name' => $name, 'mime_type' => 'application/pdf', 'size' => strlen((string) $content),
        'hash' => 'ignored-'.$path, 'source' => $source,
    ];

    if ($source === CaiDocumentSource::Runts) {
        CaiRuntsRegistration::query()->firstOrCreate(['id_runts' => '1'], ['name' => 'Ente']);
        $attributes['cai_runts_registration_id'] = '1';
    } else {
        $attributes['cai_section_id'] = null;
    }

    CaiDocument::create($attributes);
}

function snapshotDocumentRows(string $sqlite): array
{
    return (new PDO('sqlite:'.$sqlite))->query('SELECT file_path, snapshot_file, file_in_datapack FROM snap_cai_documents ORDER BY file_path')->fetchAll(PDO::FETCH_ASSOC);
}

beforeEach(fn () => Storage::fake('cai-documents'));

test('identical content is copied once, named by recomputed sha256', function (): void {
    makeSnapshotDocument('1/a.pdf', 'contenuto');
    makeSnapshotDocument('1/b.pdf', 'contenuto');
    makeSnapshotDocument('1/c.pdf', 'altro');
    $sqlite = makeSnapshotFilesDatapack();

    $this->artisan('cai:export-datapack-snapshot', ['--datapack' => $sqlite])
        ->expectsOutputToContain('2 copiati, 0 già presenti, 0 mancanti')
        ->assertSuccessful();

    $hash = hash('sha256', 'contenuto');
    $rows = snapshotDocumentRows($sqlite);
    expect($rows[0]['snapshot_file'])->toBe('snapshot-files/'.substr($hash, 0, 2)."/{$hash}.pdf")
        ->and($rows[1]['snapshot_file'])->toBe($rows[0]['snapshot_file'])
        ->and($rows[0]['file_in_datapack'])->toBe(1)
        ->and(file_get_contents(dirname($sqlite).'/'.$rows[0]['snapshot_file']))->toBe('contenuto')
        ->and(glob(dirname($sqlite).'/snapshot-files/*/*'))->toHaveCount(2);
});

test('a missing file exports the row with file_in_datapack 0 and a warning', function (): void {
    makeSnapshotDocument('1/ok.pdf', 'ok');
    makeSnapshotDocument('1/gone.pdf', null);
    $sqlite = makeSnapshotFilesDatapack();

    $this->artisan('cai:export-datapack-snapshot', ['--datapack' => $sqlite])
        ->expectsOutputToContain('1 mancanti')
        ->expectsOutputToContain('1/gone.pdf')
        ->assertSuccessful();

    $rows = snapshotDocumentRows($sqlite);
    expect($rows[0]['file_path'])->toBe('1/gone.pdf')
        ->and($rows[0]['snapshot_file'])->toBeNull()
        ->and($rows[0]['file_in_datapack'])->toBe(0)
        ->and($rows[1]['file_in_datapack'])->toBe(1);
});

test('manual documents are never copied', function (): void {
    makeSnapshotDocument('9/m.pdf', 'manuale', CaiDocumentSource::Manual);
    $sqlite = makeSnapshotFilesDatapack();

    $this->artisan('cai:export-datapack-snapshot', ['--datapack' => $sqlite])->assertSuccessful();

    expect(snapshotDocumentRows($sqlite)[0])->toMatchArray(['snapshot_file' => null, 'file_in_datapack' => 0])
        ->and(is_dir(dirname($sqlite).'/snapshot-files'))->toBeFalse();
});

test('a second run does not recopy files already present', function (): void {
    makeSnapshotDocument('1/a.pdf', 'contenuto');
    $sqlite = makeSnapshotFilesDatapack();
    $this->artisan('cai:export-datapack-snapshot', ['--datapack' => $sqlite])->assertSuccessful();
    $file = glob(dirname($sqlite).'/snapshot-files/*/*')[0];
    $mtime = filemtime($file);
    touch($file, $mtime - 1000);

    $this->artisan('cai:export-datapack-snapshot', ['--datapack' => $sqlite])
        ->expectsOutputToContain('0 copiati, 1 già presenti')
        ->assertSuccessful();

    expect(filemtime($file))->toBe($mtime - 1000)
        ->and(snapshotDocumentRows($sqlite)[0]['file_in_datapack'])->toBe(1);
});

test('insufficient free space aborts with an Italian error before writing anything', function (): void {
    makeSnapshotDocument('1/a.pdf', str_repeat('x', 2048));
    $sqlite = makeSnapshotFilesDatapack();
    $before = hash_file('sha256', $sqlite);
    $this->app->bind(CaiSnapshotExporter::class, fn () => new CaiSnapshotExporter(new CaiSnapshotFileExporter(fn (): int => 100)));

    $this->artisan('cai:export-datapack-snapshot', ['--datapack' => $sqlite])
        ->expectsOutputToContain('Spazio insufficiente')
        ->assertFailed();

    expect(hash_file('sha256', $sqlite))->toBe($before)
        ->and(is_dir(dirname($sqlite).'/snapshot-files'))->toBeFalse();
});

test('--dry-run copies no file', function (): void {
    makeSnapshotDocument('1/a.pdf', 'contenuto');
    $sqlite = makeSnapshotFilesDatapack();

    $this->artisan('cai:export-datapack-snapshot', ['--datapack' => $sqlite, '--dry-run' => true])
        ->expectsOutputToContain('1 da copiare')
        ->assertSuccessful();

    expect(is_dir(dirname($sqlite).'/snapshot-files'))->toBeFalse();
});
