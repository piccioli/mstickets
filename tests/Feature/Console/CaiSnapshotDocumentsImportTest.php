<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Enums\CaiDocumentAnalysisStatus;
use App\Domain\CaiDirectory\Enums\CaiDocumentSource;
use App\Domain\CaiDirectory\Export\CaiSnapshotExporter;
use App\Domain\CaiDirectory\Import\CaiDatapackImporter;
use App\Domain\CaiDirectory\Models\CaiDocument;
use App\Domain\CaiDirectory\Models\CaiRuntsRegistration;
use App\Domain\CaiDirectory\Models\CaiSection;
use App\Domain\Identity\Enums\Permission as PermissionEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

/**
 * Crea una registrazione (genitore) e documenti RUNTS con file reale su `Storage::fake`, poi esporta lo
 * snapshot in un datapack temporaneo con le tabelle legacy vuote. Ritorna il percorso del file sqlite.
 *
 * @param  array<string, string>  $contents  file_name => contenuto
 */
function makeDocumentsSnapshot(array $contents): string
{
    CaiSection::create(['codice_cai' => '9226005', 'name' => 'CAI Carrara', 'region' => 'TOSCANA']);
    CaiRuntsRegistration::create(['id_runts' => '1234', 'cai_section_id' => '9226005', 'name' => 'CAI Carrara ETS']);

    foreach ($contents as $name => $content) {
        Storage::disk('cai-documents')->put("1234/old-{$name}", $content);
        CaiDocument::create([
            'cai_runts_registration_id' => '1234', 'document_type' => 'bilancio_esercizio', 'year' => 2024,
            'title' => "Bilancio {$name}", 'file_path' => "1234/old-{$name}", 'file_name' => $name,
            'mime_type' => 'application/pdf', 'size' => strlen($content), 'hash' => hash('sha256', $content),
            'financial_analysis_status' => CaiDocumentAnalysisStatus::Extracted, 'raw_text_excerpt' => 'estratto '.$name,
            'extracted_via_ocr' => true, 'source' => CaiDocumentSource::Runts,
        ]);
    }

    $dir = sys_get_temp_dir().'/snap-docs-'.bin2hex(random_bytes(4));
    mkdir($dir);
    $sqlite = $dir.'/runts-cai.sqlite';
    $pdo = new PDO('sqlite:'.$sqlite);
    $pdo->exec('CREATE TABLE sezioni_cai (codice_cai TEXT)');
    $pdo->exec('CREATE TABLE sottosezioni_cai (cai_codice TEXT, cai_sezione_codice TEXT)');
    $pdo->exec('CREATE TABLE enti (id_runts TEXT)');
    $pdo->exec('CREATE TABLE bilanci (id INTEGER)');
    $pdo->exec('CREATE TABLE cariche_sociali (id INTEGER)');
    $pdo->exec('CREATE TABLE allegati (id INTEGER)');
    app(CaiSnapshotExporter::class)->export($sqlite);

    return $sqlite;
}

function wipeSnapshotTarget(): void
{
    CaiDocument::query()->delete();
    CaiRuntsRegistration::query()->delete();
    CaiSection::query()->delete();
    Storage::fake('cai-documents');
}

beforeEach(fn () => Storage::fake('cai-documents'));

test('creates RUNTS documents copying files into the documents disk, never as manual', function (): void {
    $sqlite = makeDocumentsSnapshot(['a.pdf' => 'contenuto A', 'b.pdf' => 'contenuto B']);
    wipeSnapshotTarget();

    $results = app(CaiDatapackImporter::class)->import($sqlite, false);

    expect($results['snapshot_documenti']->created)->toBe(2)
        ->and($results['snapshot_documenti']->bytes)->toBe(strlen('contenuto A') + strlen('contenuto B'))
        ->and(CaiDocument::query()->count())->toBe(2);

    $document = CaiDocument::query()->where('file_name', 'a.pdf')->sole();
    expect($document->source)->toBe(CaiDocumentSource::Runts)
        ->and($document->cai_runts_registration_id)->toBe('1234')
        ->and($document->cai_section_id)->toBeNull()
        ->and($document->financial_analysis_status)->toBe(CaiDocumentAnalysisStatus::Extracted)
        ->and($document->raw_text_excerpt)->toBe('estratto a.pdf')
        ->and($document->extracted_via_ocr)->toBeTrue()
        ->and($document->file_path)->toMatch('#^1234/[0-9a-f-]{36}-a\.pdf$#');
    Storage::disk('cai-documents')->assertExists($document->file_path);
});

test('does not duplicate a legacy document with same parent, hash and source but aligns the analysis', function (): void {
    $sqlite = makeDocumentsSnapshot(['a.pdf' => 'contenuto A']);
    wipeSnapshotTarget();

    $legacy = null;
    CaiSection::create(['codice_cai' => '9226005', 'name' => 'CAI Carrara', 'region' => 'TOSCANA']);
    CaiRuntsRegistration::create(['id_runts' => '1234', 'cai_section_id' => '9226005', 'name' => 'CAI Carrara ETS']);
    Storage::disk('cai-documents')->put('1234/a.pdf', 'contenuto A');
    $legacy = CaiDocument::create([
        'cai_runts_registration_id' => '1234', 'document_type' => 'bilancio_esercizio', 'year' => 2024,
        'title' => 'Bilancio', 'file_path' => '1234/a.pdf', 'file_name' => 'a.pdf', 'mime_type' => 'application/pdf',
        'size' => 11, 'hash' => hash('sha256', 'contenuto A'), 'source' => CaiDocumentSource::Runts,
    ]);

    $results = app(CaiDatapackImporter::class)->import($sqlite, false);

    expect($results['snapshot_documenti']->created)->toBe(0)
        ->and($results['snapshot_documenti']->updated)->toBe(1)
        ->and($results['snapshot_documenti']->bytes)->toBe(0)
        ->and(CaiDocument::query()->count())->toBe(1);

    $legacy->refresh();
    expect($legacy->file_path)->toBe('1234/a.pdf')
        ->and($legacy->financial_analysis_status)->toBe(CaiDocumentAnalysisStatus::Extracted)
        ->and($legacy->raw_text_excerpt)->toBe('estratto a.pdf')
        ->and($legacy->extracted_via_ocr)->toBeTrue();
});

test('second run creates and updates nothing', function (): void {
    $sqlite = makeDocumentsSnapshot(['a.pdf' => 'contenuto A', 'b.pdf' => 'contenuto B']);
    wipeSnapshotTarget();

    app(CaiDatapackImporter::class)->import($sqlite, false);
    $second = app(CaiDatapackImporter::class)->import($sqlite, false)['snapshot_documenti'];

    expect($second->created)->toBe(0)
        ->and($second->updated)->toBe(0)
        ->and($second->skipped)->toBe(2)
        ->and($second->bytes)->toBe(0)
        ->and(CaiDocument::query()->count())->toBe(2)
        ->and(Storage::disk('cai-documents')->allFiles())->toHaveCount(2);
});

test('a missing source file is skipped with a warning without stopping the others', function (): void {
    $sqlite = makeDocumentsSnapshot(['a.pdf' => 'contenuto A', 'b.pdf' => 'contenuto B']);
    wipeSnapshotTarget();

    $hash = hash('sha256', 'contenuto A');
    unlink(dirname($sqlite).'/snapshot-files/'.substr($hash, 0, 2).'/'.$hash.'.pdf');

    $result = app(CaiDatapackImporter::class)->import($sqlite, false)['snapshot_documenti'];

    expect($result->created)->toBe(1)
        ->and($result->skipped)->toBe(1)
        ->and($result->warnings[0])->toContain('File mancante')
        ->and($result->error)->toBeNull()
        ->and(CaiDocument::query()->sole()->file_name)->toBe('b.pdf');
});

test('a row whose parent does not exist is skipped and counted', function (): void {
    $sqlite = makeDocumentsSnapshot(['a.pdf' => 'contenuto A']);
    wipeSnapshotTarget();
    (new PDO('sqlite:'.$sqlite))->exec("UPDATE snap_cai_documents SET cai_runts_registration_id = '9999'");
    (new PDO('sqlite:'.$sqlite))->exec('DELETE FROM snap_cai_runts_registrations');
    (new PDO('sqlite:'.$sqlite))->exec("DELETE FROM snap_cai_sections WHERE codice_cai = 'nessuna'");

    $result = app(CaiDatapackImporter::class)->import($sqlite, false)['snapshot_documenti'];

    expect($result->created)->toBe(0)
        ->and($result->skipped)->toBe(1)
        ->and($result->warnings[0])->toContain('genitore inesistente')
        ->and(CaiDocument::query()->count())->toBe(0);
});

test('insufficient disk space copies nothing and reports an explicit Italian error', function (): void {
    $sqlite = makeDocumentsSnapshot(['a.pdf' => 'contenuto A']);
    wipeSnapshotTarget();
    config(['cai_directory.snapshot.disk_margin_percent' => 1.0e15]);

    $result = app(CaiDatapackImporter::class)->import($sqlite, false);

    expect($result['snapshot_documenti']->error)->toContain('Spazio insufficiente')
        ->and($result['snapshot_documenti']->created)->toBe(0)
        ->and(CaiDocument::query()->count())->toBe(0)
        ->and(Storage::disk('cai-documents')->allFiles())->toBe([])
        // le fasi precedenti restano
        ->and(CaiRuntsRegistration::query()->count())->toBe(1)
        ->and($result['snapshot_registrazioni']->created)->toBe(1);
});

test('the command fails with the space error', function (): void {
    $sqlite = makeDocumentsSnapshot(['a.pdf' => 'contenuto A']);
    wipeSnapshotTarget();
    config(['cai_directory.snapshot.disk_margin_percent' => 1.0e15]);

    $this->artisan('cai:import-datapack', ['--path' => $sqlite])
        ->expectsOutputToContain('Spazio insufficiente')
        ->assertFailed();
});

test('dry-run writes and copies nothing but reports counts and bytes', function (): void {
    $sqlite = makeDocumentsSnapshot(['a.pdf' => 'contenuto A', 'b.pdf' => 'contenuto B']);
    wipeSnapshotTarget();

    $result = app(CaiDatapackImporter::class)->import($sqlite, true)['snapshot_documenti'];

    expect($result->created)->toBe(2)
        ->and($result->bytes)->toBe(strlen('contenuto A') + strlen('contenuto B'))
        ->and(CaiDocument::query()->count())->toBe(0)
        ->and(Storage::disk('cai-documents')->allFiles())->toBe([]);
});

test('the summary line reports the megabytes copied', function (): void {
    $sqlite = makeDocumentsSnapshot(['a.pdf' => 'contenuto A']);
    wipeSnapshotTarget();

    $this->artisan('cai:import-datapack', ['--path' => $sqlite])
        ->expectsOutputToContain('snapshot_documenti: letti 1, creati 1, aggiornati 0, saltati 0, 0,0 MB copiati')
        ->assertSuccessful();
});

test('the downloaded file of an imported document has the sha256 of the source', function (): void {
    $content = '%PDF-1.4 '.str_repeat('contenuto reale ', 50);
    $sqlite = makeDocumentsSnapshot(['a.pdf' => $content]);
    wipeSnapshotTarget();

    app(CaiDatapackImporter::class)->import($sqlite, false);
    $document = CaiDocument::query()->sole();

    $response = $this->actingAs(userWithPermissions(PermissionEnum::CaiDirectoryView))
        ->get(route('cai-documents.download', $document));

    $response->assertOk();
    expect(hash('sha256', $response->streamedContent()))->toBe(hash('sha256', $content));
});

test('no analysis job is queued by the documents import', function (): void {
    Queue::fake();
    $sqlite = makeDocumentsSnapshot(['a.pdf' => 'contenuto A']);
    wipeSnapshotTarget();

    app(CaiDatapackImporter::class)->import($sqlite, false);

    Queue::assertNothingPushed();
});
