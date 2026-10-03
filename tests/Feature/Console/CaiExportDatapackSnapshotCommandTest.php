<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Enums\CaiDocumentAnalysisStatus;
use App\Domain\CaiDirectory\Enums\CaiDocumentSource;
use App\Domain\CaiDirectory\Enums\CaiRuntsPresenceStatus;
use App\Domain\CaiDirectory\Models\CaiBoardMember;
use App\Domain\CaiDirectory\Models\CaiDocument;
use App\Domain\CaiDirectory\Models\CaiFinancialStatement;
use App\Domain\CaiDirectory\Models\CaiRuntsRegistration;
use App\Domain\CaiDirectory\Models\CaiSection;
use App\Domain\CaiDirectory\Models\CaiSubsection;
use App\Domain\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeSnapshotExportFixture(): string
{
    $sqlite = sys_get_temp_dir().'/snap-export-'.bin2hex(random_bytes(4)).'.sqlite';
    $pdo = new PDO('sqlite:'.$sqlite);
    $pdo->exec('CREATE TABLE sezioni_cai (codice_cai TEXT PRIMARY KEY, nome TEXT)');
    $pdo->exec('CREATE TABLE enti (id_runts INTEGER PRIMARY KEY)');
    $pdo->exec('CREATE TABLE bilanci_manuali (id INTEGER PRIMARY KEY)');
    $pdo->exec("INSERT INTO sezioni_cai VALUES ('9226005', 'x')");
    $pdo->exec('INSERT INTO enti VALUES (1)');
    $pdo->exec('INSERT INTO bilanci_manuali VALUES (7)');

    return $sqlite;
}

function seedSnapshotExportRows(): void
{
    $user = User::factory()->create();
    $section = CaiSection::create([
        'codice_cai' => '9226005', 'name' => 'CAI Carrara', 'region' => 'TOSCANA', 'latitude' => 44.0775,
        'user_id' => $user->id, 'runts_presence_status' => CaiRuntsPresenceStatus::Registered,
        'runts_presence_checked_at' => '2026-09-10 08:30:00', 'cai_last_synced_at' => '2026-09-09 10:00:00',
    ]);
    CaiSection::create(['codice_cai' => '9258001', 'name' => 'CAI Roma', 'region' => 'LAZIO']);
    CaiSubsection::create(['cai_codice' => '9226105', 'cai_section_id' => $section->codice_cai, 'name' => 'Sottosezione', 'user_id' => $user->id]);
    $registration = CaiRuntsRegistration::create([
        'id_runts' => '1234', 'cai_section_id' => $section->codice_cai, 'name' => 'CAI Carrara ETS',
        'registration_date' => '2022-03-04', 'runts_last_synced_at' => '2026-09-08 12:00:00',
    ]);
    CaiFinancialStatement::create(['cai_runts_registration_id' => '1234', 'year' => 2024, 'total_expenses' => 1234.5, 'total_assets' => 99]);
    CaiFinancialStatement::create(['cai_section_id' => '9258001', 'year' => 2025, 'net_result' => -10]);
    CaiBoardMember::create(['cai_runts_registration_id' => '1234', 'role' => 'Presidente', 'full_name' => 'Mario Rossi', 'valid_from' => '2023-01-01']);
    CaiDocument::create([
        'cai_runts_registration_id' => $registration->id_runts, 'document_type' => 'bilancio_esercizio', 'year' => 2024,
        'title' => 'Bilancio 2024', 'file_path' => '1234/a-bilancio.pdf', 'file_name' => 'bilancio.pdf',
        'mime_type' => 'application/pdf', 'size' => 10, 'hash' => 'abc',
        'financial_analysis_status' => CaiDocumentAnalysisStatus::Extracted, 'extracted_via_ocr' => true,
        'source' => CaiDocumentSource::Runts,
    ]);
    CaiDocument::create([
        'cai_section_id' => '9258001', 'document_type' => 'rendiconto', 'year' => 2025, 'title' => 'Rendiconto',
        'file_path' => '9258001/b.pdf', 'file_name' => 'b.pdf', 'mime_type' => 'application/pdf', 'size' => 5,
        'hash' => 'def', 'source' => CaiDocumentSource::Manual,
    ]);
}

function snapshotTable(string $sqlite, string $table): array
{
    $pdo = new PDO('sqlite:'.$sqlite);

    return $pdo->query("SELECT * FROM {$table}")->fetchAll(PDO::FETCH_ASSOC);
}

test('missing datapack fails with an explicit Italian message', function (): void {
    $this->artisan('cai:export-datapack-snapshot', ['--datapack' => '/tmp/nope-'.uniqid().'.sqlite'])
        ->expectsOutputToContain('File datapack non trovato')
        ->assertFailed();
});

test('exports every table with counts matching the database', function (): void {
    seedSnapshotExportRows();
    $sqlite = makeSnapshotExportFixture();

    $this->artisan('cai:export-datapack-snapshot', ['--datapack' => $sqlite])->assertSuccessful();

    expect(snapshotTable($sqlite, 'snap_cai_sections'))->toHaveCount(2)
        ->and(snapshotTable($sqlite, 'snap_cai_subsections'))->toHaveCount(1)
        ->and(snapshotTable($sqlite, 'snap_cai_runts_registrations'))->toHaveCount(1)
        ->and(snapshotTable($sqlite, 'snap_cai_financial_statements'))->toHaveCount(2)
        ->and(snapshotTable($sqlite, 'snap_cai_board_members'))->toHaveCount(1)
        ->and(snapshotTable($sqlite, 'snap_cai_documents'))->toHaveCount(2);
});

test('values are raw column values, never user_id, never an autoincrement id', function (): void {
    seedSnapshotExportRows();
    $sqlite = makeSnapshotExportFixture();
    $this->artisan('cai:export-datapack-snapshot', ['--datapack' => $sqlite])->assertSuccessful();

    $section = snapshotTable($sqlite, 'snap_cai_sections')[0];
    expect($section)->not->toHaveKeys(['id', 'user_id'])
        ->and($section['runts_presence_status'])->toBe('registered')
        ->and((float) $section['latitude'])->toBe(44.0775)
        ->and($section['cai_last_synced_at'])->toBe('2026-09-09 10:00:00')
        ->and($section['created_at'])->not->toBeNull();

    $subsection = snapshotTable($sqlite, 'snap_cai_subsections')[0];
    expect($subsection)->not->toHaveKeys(['id', 'user_id']);

    expect(snapshotTable($sqlite, 'snap_cai_runts_registrations')[0]['registration_date'])->toStartWith('2022-03-04');

    $statements = snapshotTable($sqlite, 'snap_cai_financial_statements');
    expect($statements[0]['cai_runts_registration_id'])->toBe('1234')
        ->and($statements[0]['cai_section_id'])->toBeNull()
        ->and((float) $statements[0]['total_expenses'])->toBe(1234.5)
        ->and($statements[1]['cai_section_id'])->toBe('9258001')
        ->and($statements[1]['cai_runts_registration_id'])->toBeNull();

    $documents = snapshotTable($sqlite, 'snap_cai_documents');
    expect($documents[0]['source'])->toBe('runts')
        ->and($documents[0]['financial_analysis_status'])->toBe(CaiDocumentAnalysisStatus::Extracted->value)
        ->and($documents[0]['extracted_via_ocr'])->toBe(1)
        ->and($documents[0]['snapshot_file'])->toBeNull()
        ->and($documents[0]['file_in_datapack'])->toBe(0)
        ->and($documents[1]['source'])->toBe('manual')
        ->and($documents[1]['cai_section_id'])->toBe('9258001');
});

test('is idempotent: two runs produce the same content in the same order', function (): void {
    seedSnapshotExportRows();
    $sqlite = makeSnapshotExportFixture();

    $this->artisan('cai:export-datapack-snapshot', ['--datapack' => $sqlite])->assertSuccessful();
    $first = array_map(fn (string $t) => snapshotTable($sqlite, $t), ['snap_cai_sections', 'snap_cai_financial_statements', 'snap_cai_documents']);

    $this->artisan('cai:export-datapack-snapshot', ['--datapack' => $sqlite])->assertSuccessful();
    $second = array_map(fn (string $t) => snapshotTable($sqlite, $t), ['snap_cai_sections', 'snap_cai_financial_statements', 'snap_cai_documents']);

    expect($second)->toBe($first);
});

test('--dry-run writes nothing and the datapack file is unchanged', function (): void {
    seedSnapshotExportRows();
    $sqlite = makeSnapshotExportFixture();
    $before = hash_file('sha256', $sqlite);

    $this->artisan('cai:export-datapack-snapshot', ['--datapack' => $sqlite, '--dry-run' => true])
        ->expectsOutputToContain('dry-run')
        ->assertSuccessful();

    expect(hash_file('sha256', $sqlite))->toBe($before);
    $tables = (new PDO('sqlite:'.$sqlite))->query("SELECT name FROM sqlite_master WHERE name LIKE 'snap_%'")->fetchAll(PDO::FETCH_COLUMN);
    expect($tables)->toBe([]);
});

test('other datapack tables are left untouched', function (): void {
    seedSnapshotExportRows();
    $sqlite = makeSnapshotExportFixture();

    $this->artisan('cai:export-datapack-snapshot', ['--datapack' => $sqlite])->assertSuccessful();

    expect(snapshotTable($sqlite, 'sezioni_cai'))->toBe([['codice_cai' => '9226005', 'nome' => 'x']])
        ->and(snapshotTable($sqlite, 'enti'))->toBe([['id_runts' => 1]])
        ->and(snapshotTable($sqlite, 'bilanci_manuali'))->toBe([['id' => 7]]);
});
