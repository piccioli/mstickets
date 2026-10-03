<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Enums\CaiDocumentAnalysisStatus;
use App\Domain\CaiDirectory\Jobs\AnalyzeCaiFinancialStatementDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

function caiAnalyzeFixture(): array
{
    $toscana = caiSection(['codice_cai' => 'TOS1', 'region' => 'Toscana']);
    $veneto = caiSection(['codice_cai' => 'VEN1', 'region' => 'Veneto']);
    $regVeneto = caiRuntsRegistration(['cai_section_id' => $veneto->codice_cai]);

    return [
        'ce' => caiDocument(['cai_runts_registration_id' => null, 'cai_section_id' => 'TOS1', 'year' => 2025,
            'document_type' => 'altro', 'title' => 'Conto economico']),
        'sp' => caiDocument(['cai_runts_registration_id' => $regVeneto->id_runts, 'year' => 2025, 'title' => 'Mod. A - Stato Patrimoniale']),
        'analyzed' => caiDocument(['cai_runts_registration_id' => null, 'cai_section_id' => 'TOS1', 'year' => 2025,
            'document_type' => 'altro', 'title' => 'Conto economico bis', 'financial_analysis_status' => CaiDocumentAnalysisStatus::Extracted]),
        'other_year' => caiDocument(['cai_runts_registration_id' => null, 'cai_section_id' => 'TOS1', 'year' => 2024,
            'document_type' => 'altro', 'title' => 'Conto economico']),
        'no_year' => caiDocument(['cai_runts_registration_id' => null, 'cai_section_id' => 'TOS1', 'year' => null,
            'document_type' => 'altro', 'title' => 'Conto economico']),
        'unrelated' => caiDocument(['cai_runts_registration_id' => null, 'cai_section_id' => 'TOS1', 'year' => 2025,
            'document_type' => 'altro', 'title' => 'Verbale assemblea']),
    ];
}

function caiQueuedIds(): array
{
    $ids = [];
    Queue::assertPushedOn('cai-runts-analysis', AnalyzeCaiFinancialStatementDocument::class, function ($job) use (&$ids) {
        $ids[] = $job->caiDocumentId;

        return true;
    });
    sort($ids);

    return $ids;
}

test('queues only never-analyzed income statement and balance sheet documents of the year', function (): void {
    Queue::fake();
    $d = caiAnalyzeFixture();

    $this->artisan('cai:analyze-financial-documents', ['--year' => 2025])
        ->expectsOutputToContain('2 documenti accodati, 1 saltati')
        ->expectsOutputToContain('cai-runts-analysis')
        ->assertExitCode(0);

    expect(caiQueuedIds())->toBe([$d['ce']->id, $d['sp']->id]);
    Queue::assertPushed(AnalyzeCaiFinancialStatementDocument::class, 2);
});

test('force also queues already analyzed documents', function (): void {
    Queue::fake();
    caiAnalyzeFixture();

    $this->artisan('cai:analyze-financial-documents', ['--year' => 2025, '--force' => true])->assertExitCode(0);

    Queue::assertPushed(AnalyzeCaiFinancialStatementDocument::class, 3);
});

test('section option limits to one section, including documents linked via registration', function (): void {
    Queue::fake();
    $d = caiAnalyzeFixture();

    $this->artisan('cai:analyze-financial-documents', ['--year' => 2025, '--section' => 'VEN1'])->assertExitCode(0);

    expect(caiQueuedIds())->toBe([$d['sp']->id]);
});

test('dry run queues nothing and prints the per-region breakdown', function (): void {
    Queue::fake();
    caiAnalyzeFixture();

    $this->artisan('cai:analyze-financial-documents', ['--year' => 2025, '--dry-run' => true])
        ->expectsOutputToContain('2 documenti verrebbero accodati')
        ->expectsOutputToContain('Toscana: 1')
        ->expectsOutputToContain('Veneto: 1')
        ->assertExitCode(0);

    Queue::assertNothingPushed();
});

test('missing year fails explicitly', function (): void {
    Queue::fake();

    $this->artisan('cai:analyze-financial-documents')
        ->expectsOutputToContain('--year')
        ->assertExitCode(1);

    Queue::assertNothingPushed();
});
