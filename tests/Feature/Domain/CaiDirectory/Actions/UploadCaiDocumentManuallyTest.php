<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Actions\UploadCaiDocumentManually;
use App\Domain\CaiDirectory\Enums\CaiDocumentSource;
use App\Domain\CaiDirectory\Jobs\AnalyzeCaiFinancialStatementDocument;
use App\Domain\CaiDirectory\Models\CaiDocument;
use App\Domain\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

test('run attaches the document to the given registration when one is provided', function (): void {
    Storage::fake('cai-documents');

    $section = caiSection();
    $registration = caiRuntsRegistration(['cai_section_id' => $section->codice_cai]);
    $file = UploadedFile::fake()->create('statuto.pdf', 10, 'application/pdf');

    $document = UploadCaiDocumentManually::run(
        User::factory()->create(),
        $section,
        $registration,
        'statuto',
        null,
        'Statuto sociale',
        $file,
    );

    expect($document->cai_runts_registration_id)->toBe($registration->id_runts)
        ->and($document->cai_section_id)->toBeNull()
        ->and($document->source)->toBe(CaiDocumentSource::Manual)
        ->and($document->document_type)->toBe('statuto')
        ->and($document->title)->toBe('Statuto sociale')
        ->and($document->file_name)->toBe('statuto.pdf')
        ->and($document->mime_type)->toBe('application/pdf')
        ->and($document->hash)->not->toBeNull();

    Storage::disk('cai-documents')->assertExists($document->file_path);
});

test('run attaches the document directly to the section when no registration is given', function (): void {
    Storage::fake('cai-documents');

    $section = caiSection();
    $file = UploadedFile::fake()->create('documento.pdf', 5, 'application/pdf');

    $document = UploadCaiDocumentManually::run(
        User::factory()->create(),
        $section,
        null,
        'altro',
        null,
        null,
        $file,
    );

    expect($document->cai_runts_registration_id)->toBeNull()
        ->and($document->cai_section_id)->toBe($section->codice_cai)
        ->and($document->source)->toBe(CaiDocumentSource::Manual);

    Storage::disk('cai-documents')->assertExists($document->file_path);
});

test('run rejects a registration that does not belong to the given section', function (): void {
    Storage::fake('cai-documents');

    $section = caiSection();
    $otherSection = caiSection();
    $foreignRegistration = caiRuntsRegistration(['cai_section_id' => $otherSection->codice_cai]);
    $file = UploadedFile::fake()->create('documento.pdf', 5, 'application/pdf');

    expect(fn () => UploadCaiDocumentManually::run(
        User::factory()->create(),
        $section,
        $foreignRegistration,
        'altro',
        null,
        null,
        $file,
    ))->toThrow(ValidationException::class);

    expect(CaiDocument::query()->count())->toBe(0);
});

test('run dispatches the financial-statement analysis job for a bilancio_esercizio document, with or without a registration', function (): void {
    Storage::fake('cai-documents');
    Queue::fake();

    $section = caiSection();
    $file = UploadedFile::fake()->create('bilancio.pdf', 10, 'application/pdf');

    $document = UploadCaiDocumentManually::run(
        User::factory()->create(),
        $section,
        null,
        'bilancio_esercizio',
        2025,
        null,
        $file,
    );

    Queue::assertPushed(
        AnalyzeCaiFinancialStatementDocument::class,
        fn ($job): bool => $job->caiDocumentId === $document->id,
    );
});

test('run never dispatches the analysis job for a non-bilancio document type', function (): void {
    Storage::fake('cai-documents');
    Queue::fake();

    $section = caiSection();
    $file = UploadedFile::fake()->create('statuto.pdf', 10, 'application/pdf');

    UploadCaiDocumentManually::run(User::factory()->create(), $section, null, 'statuto', null, null, $file);

    Queue::assertNotPushed(AnalyzeCaiFinancialStatementDocument::class);
});
