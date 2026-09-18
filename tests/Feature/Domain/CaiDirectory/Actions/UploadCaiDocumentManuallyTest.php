<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Actions\UploadCaiDocumentManually;
use App\Domain\CaiDirectory\Enums\CaiDocumentSource;
use App\Domain\CaiDirectory\Enums\CaiDocumentType;
use App\Domain\CaiDirectory\Jobs\AnalyzeCaiFinancialStatementDocument;
use App\Domain\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('run always attaches the document directly to the section, never to a registration', function (): void {
    Storage::fake('cai-documents');

    $section = caiSection();
    caiRuntsRegistration(['cai_section_id' => $section->codice_cai]);
    $file = UploadedFile::fake()->create('documento.pdf', 5, 'application/pdf');

    $document = UploadCaiDocumentManually::run(
        User::factory()->create(),
        $section,
        CaiDocumentType::Altro,
        null,
        'Documento di prova',
        $file,
    );

    expect($document->cai_section_id)->toBe($section->codice_cai)
        ->and($document->cai_runts_registration_id)->toBeNull()
        ->and($document->source)->toBe(CaiDocumentSource::Manual)
        ->and($document->document_type)->toBe('altro')
        ->and($document->title)->toBe('Documento di prova')
        ->and($document->file_name)->toBe('documento.pdf')
        ->and($document->mime_type)->toBe('application/pdf')
        ->and($document->hash)->not->toBeNull();

    Storage::disk('cai-documents')->assertExists($document->file_path);
});

test('run dispatches the financial-statement analysis job for a Mod A/B/D type or a combination that contains one', function (): void {
    Storage::fake('cai-documents');
    Queue::fake();

    $section = caiSection();

    foreach ([CaiDocumentType::ModA, CaiDocumentType::ModB, CaiDocumentType::ModD, CaiDocumentType::ModAB, CaiDocumentType::CompletoABC, CaiDocumentType::CompletoDRevisori] as $type) {
        $file = UploadedFile::fake()->create('bilancio.pdf', 10, 'application/pdf');

        $document = UploadCaiDocumentManually::run(User::factory()->create(), $section, $type, 2025, 'Bilancio', $file);

        Queue::assertPushed(
            AnalyzeCaiFinancialStatementDocument::class,
            fn ($job): bool => $job->caiDocumentId === $document->id,
        );
    }
});

test('run never dispatches the analysis job for a narrative type (relazioni, verbali, bilancio sociale, altro)', function (): void {
    Storage::fake('cai-documents');
    Queue::fake();

    $section = caiSection();

    foreach ([
        CaiDocumentType::RelazioneMissione, CaiDocumentType::RelazioneRevisori, CaiDocumentType::RelazioneAttivita,
        CaiDocumentType::VerbaleAssemblea, CaiDocumentType::BilancioSociale, CaiDocumentType::RelazioneBilancio,
        CaiDocumentType::BilancioAnalitico, CaiDocumentType::BilancioRiclassificato,
        CaiDocumentType::BilancioEconomicoFinanziario, CaiDocumentType::Altro,
    ] as $type) {
        $file = UploadedFile::fake()->create('documento.pdf', 5, 'application/pdf');

        UploadCaiDocumentManually::run(User::factory()->create(), $section, $type, null, 'Documento', $file);
    }

    Queue::assertNotPushed(AnalyzeCaiFinancialStatementDocument::class);
});
