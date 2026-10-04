<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Enums\CaiDocumentAnalysisStatus;
use App\Domain\CaiDirectory\Enums\CaiDocumentSource;
use App\Domain\CaiDirectory\Export\CaiSnapshotExporter;
use App\Domain\CaiDirectory\Import\CaiDatapackImporter;
use App\Domain\CaiDirectory\Models\CaiDocument;
use App\Domain\CaiDirectory\Models\CaiFinancialStatement;
use App\Domain\CaiDirectory\Models\CaiSection;
use App\Domain\CaiDirectory\Queries\CaiSectionFinancialYearQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

/**
 * Sezione con due documenti manuali 2025 (CE e SP) già analizzati + bilancio interpretato, esportati in
 * un datapack temporaneo con le tabelle legacy vuote. Ritorna il percorso del file sqlite.
 */
function makeManualAnalysisSnapshot(): string
{
    CaiSection::create(['codice_cai' => '9226005', 'name' => 'CAI Carrara', 'region' => 'TOSCANA']);

    foreach (['mod_b' => 'ce', 'mod_a' => 'sp'] as $type => $name) {
        CaiDocument::create([
            'cai_section_id' => '9226005', 'document_type' => $type, 'year' => 2025, 'title' => "Bilancio {$name}",
            'file_path' => "9226005/{$name}.pdf", 'file_name' => "{$name}.pdf", 'mime_type' => 'application/pdf',
            'size' => 10, 'hash' => hash('sha256', $name), 'source' => CaiDocumentSource::Manual,
            'financial_analysis_status' => CaiDocumentAnalysisStatus::Extracted, 'raw_text_excerpt' => "estratto {$name}",
            'extracted_via_ocr' => $name === 'sp',
        ]);
    }
    CaiFinancialStatement::create(['cai_section_id' => '9226005', 'year' => 2025, 'total_expenses' => 100, 'total_assets' => 500]);

    $dir = sys_get_temp_dir().'/snap-manual-'.bin2hex(random_bytes(4));
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

/** Simula lo stato dopo `documenti_manuali`: documenti presenti ma senza esito di analisi. */
function resetManualAnalysis(): void
{
    CaiDocument::query()->update(['financial_analysis_status' => null, 'raw_text_excerpt' => null, 'extracted_via_ocr' => false]);
}

test('restores the analysis of manual documents matched by section and hash, without creating or queuing', function (): void {
    Queue::fake();
    $sqlite = makeManualAnalysisSnapshot();
    resetManualAnalysis();

    $result = app(CaiDatapackImporter::class)->import($sqlite, false)['snapshot_manuali'];

    expect($result->read)->toBe(2)
        ->and($result->updated)->toBe(2)
        ->and($result->created)->toBe(0)
        ->and($result->warnings)->toBe([])
        ->and(CaiDocument::query()->count())->toBe(2);

    $sp = CaiDocument::query()->where('file_name', 'sp.pdf')->sole();
    expect($sp->financial_analysis_status)->toBe(CaiDocumentAnalysisStatus::Extracted)
        ->and($sp->raw_text_excerpt)->toBe('estratto sp')
        ->and($sp->extracted_via_ocr)->toBeTrue()
        ->and($sp->source)->toBe(CaiDocumentSource::Manual);

    Queue::assertNothingPushed();
});

test('a snapshot manual row without a matching document is skipped and counted', function (): void {
    $sqlite = makeManualAnalysisSnapshot();
    resetManualAnalysis();
    CaiDocument::query()->where('file_name', 'sp.pdf')->delete();

    $result = app(CaiDatapackImporter::class)->import($sqlite, false)['snapshot_manuali'];

    expect($result->updated)->toBe(1)
        ->and($result->skipped)->toBe(1)
        ->and($result->warnings[0])->toContain('snapshot_manuali_senza_match: 1')
        ->and(CaiDocument::query()->count())->toBe(1);
});

test('second run updates nothing and dry-run writes nothing', function (): void {
    $sqlite = makeManualAnalysisSnapshot();
    resetManualAnalysis();

    $dry = app(CaiDatapackImporter::class)->import($sqlite, true)['snapshot_manuali'];
    expect($dry->updated)->toBe(2)
        ->and(CaiDocument::query()->whereNotNull('financial_analysis_status')->count())->toBe(0);

    app(CaiDatapackImporter::class)->import($sqlite, false);
    $second = app(CaiDatapackImporter::class)->import($sqlite, false)['snapshot_manuali'];

    expect($second->updated)->toBe(0)->and($second->skipped)->toBe(2);
});

test('the Bilancio 2025 flags are the same as in the local database after the import', function (): void {
    $sqlite = makeManualAnalysisSnapshot();
    resetManualAnalysis();

    app(CaiDatapackImporter::class)->import($sqlite, false);

    $section = CaiSectionFinancialYearQuery::forYear(2025)->where('codice_cai', '9226005')->sole();

    expect((bool) $section->has_income_statement_file)->toBeTrue()
        ->and((bool) $section->has_balance_sheet_file)->toBeTrue()
        ->and((bool) $section->income_statement_parsed)->toBeTrue()
        ->and((bool) $section->balance_sheet_parsed)->toBeTrue()
        ->and(CaiDocument::query()->forYear(2025)->where('financial_analysis_status', CaiDocumentAnalysisStatus::Extracted)->count())->toBe(2);
});
