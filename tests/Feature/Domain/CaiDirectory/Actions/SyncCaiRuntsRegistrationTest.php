<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Actions\SyncCaiRuntsRegistration;
use App\Domain\CaiDirectory\Jobs\AnalyzeCaiFinancialStatementDocument;
use App\Domain\CaiDirectory\Models\CaiBoardMember;
use App\Domain\CaiDirectory\Models\CaiDocument;
use App\Domain\CaiDirectory\Models\CaiRuntsRegistration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function fakeRuntsEntityFound(string $codiceFiscale, array $overrides = []): void
{
    Http::fake([
        'http://cai-runts-scraper:8000/scrape/runts-entity*' => Http::response(array_merge([
            'found' => true,
            'entity' => [
                'id_runts' => '12345',
                'codice_fiscale' => $codiceFiscale,
                'denominazione' => 'Sezione di Como',
                'forma_giuridica' => null, 'natura_giuridica' => null,
                'sede_indirizzo' => 'Via Roma', 'sede_civico' => '1', 'sede_comune' => 'Como',
                'sede_provincia' => 'CO', 'sede_regione' => 'LOMBARDIA', 'sede_cap' => '22100',
                'data_iscrizione' => '24/02/2023', 'sezione_registro' => 'APS',
                'settori_attivita' => null, 'rappresentante_legale' => 'Mario Rossi',
                'sito_web' => 'https://caicomo.it', 'pec' => 'como@pec.cai.it',
                'url_dettaglio' => 'https://servizi.lavoro.gov.it/detail/12345',
            ],
            'board_members' => [
                ['ruolo' => 'presidente', 'nome' => 'Mario', 'cognome' => 'Rossi', 'codice_fiscale' => 'RSSMRA80A01H501X', 'valid_from' => '24/02/2023', 'valid_to' => null],
            ],
            'documents' => [],
        ], $overrides)),
    ]);
}

test('run returns a not-found result and writes nothing when the scraper reports found: false', function (): void {
    $section = caiSection(['tax_code' => '01234567890']);

    Http::fake([
        'http://cai-runts-scraper:8000/scrape/runts-entity*' => Http::response(['found' => false]),
    ]);

    $result = app(SyncCaiRuntsRegistration::class)->run($section);

    expect($result->found)->toBeFalse();
    expect($result->registration)->toBeNull();
    expect(CaiRuntsRegistration::query()->count())->toBe(0);
});

test('run returns a not-found result without calling the scraper when the section has no tax_code', function (): void {
    $section = caiSection(['tax_code' => null]);

    Http::fake();

    $result = app(SyncCaiRuntsRegistration::class)->run($section);

    expect($result->found)->toBeFalse();
    Http::assertNothingSent();
});

test('run creates a new CaiRuntsRegistration and its board members, and bumps runts_last_synced_at', function (): void {
    $section = caiSection(['tax_code' => '01234567890']);
    fakeRuntsEntityFound('01234567890');

    $result = app(SyncCaiRuntsRegistration::class)->run($section);

    expect($result->found)->toBeTrue();

    $registration = CaiRuntsRegistration::query()->findOrFail('12345');
    expect($registration->name)->toBe('Sezione di Como');
    expect($registration->cai_section_id)->toBe($section->codice_cai);
    expect($registration->runts_last_synced_at)->not->toBeNull();

    $boardMember = CaiBoardMember::query()->where('cai_runts_registration_id', '12345')->sole();
    expect($boardMember->full_name)->toBe('Mario Rossi');
});

test('run updates an existing CaiRuntsRegistration and always bumps runts_last_synced_at', function (): void {
    $section = caiSection(['tax_code' => '01234567890']);
    $existing = caiRuntsRegistration(['id_runts' => '12345', 'cai_section_id' => $section->codice_cai, 'name' => 'Vecchio nome', 'runts_last_synced_at' => null]);
    fakeRuntsEntityFound('01234567890');

    app(SyncCaiRuntsRegistration::class)->run($section);

    $registration = $existing->fresh();
    expect($registration->name)->toBe('Sezione di Como');
    expect($registration->runts_last_synced_at)->not->toBeNull();
});

test('run downloads and stores a new document, and dispatches analysis only for bilancio_esercizio', function (): void {
    Storage::fake('cai-documents');
    Queue::fake();

    $section = caiSection(['tax_code' => '01234567890']);
    fakeRuntsEntityFound('01234567890', [
        'documents' => [
            [
                'documento' => 'Bilancio di esercizio 2024', 'codice_pratica' => 'B00',
                'tipo' => 'bilancio_esercizio', 'anno' => 2024, 'filename' => 'B00_2024.pdf',
                'mime' => 'application/pdf', 'size' => 33, 'hash_sha256' => 'abc123',
                'skip_reason' => null, 'content_base64' => base64_encode('%PDF-1.4 fixture bilancio'),
            ],
            [
                'documento' => 'Statuto', 'codice_pratica' => 'C02', 'tipo' => 'statuto',
                'anno' => null, 'filename' => 'C02_statuto.pdf', 'mime' => 'application/pdf',
                'size' => 10, 'hash_sha256' => 'def456', 'skip_reason' => null,
                'content_base64' => base64_encode('%PDF-1.4 statuto'),
            ],
            [
                'documento' => 'Non scaricato', 'codice_pratica' => 'D00', 'tipo' => 'altro',
                'anno' => null, 'filename' => null, 'mime' => null, 'size' => null,
                'hash_sha256' => null, 'skip_reason' => 'no_button', 'content_base64' => null,
            ],
        ],
    ]);

    $result = app(SyncCaiRuntsRegistration::class)->run($section);

    expect(CaiDocument::query()->count())->toBe(2);
    expect(Storage::disk('cai-documents')->get('12345/B00_2024.pdf'))->toBe('%PDF-1.4 fixture bilancio');

    $bilancio = CaiDocument::query()->where('file_name', 'B00_2024.pdf')->sole();
    expect($bilancio->title)->toBe('Bilancio di esercizio 2024');
    expect($bilancio->year)->toBe(2024);

    expect($result->queuedAnalysisCount)->toBe(1);
    Queue::assertPushed(
        AnalyzeCaiFinancialStatementDocument::class,
        fn ($job): bool => $job->caiDocumentId === $bilancio->id,
    );
});

test('run does not re-download or re-queue a document that already exists', function (): void {
    Storage::fake('cai-documents');
    Queue::fake();

    $section = caiSection(['tax_code' => '01234567890']);
    $registration = caiRuntsRegistration(['id_runts' => '12345', 'cai_section_id' => $section->codice_cai]);
    caiDocument(['cai_runts_registration_id' => $registration->id_runts, 'file_name' => 'B00_2024.pdf', 'document_type' => 'bilancio_esercizio']);

    fakeRuntsEntityFound('01234567890', [
        'documents' => [
            [
                'documento' => 'Bilancio di esercizio 2024', 'codice_pratica' => 'B00',
                'tipo' => 'bilancio_esercizio', 'anno' => 2024, 'filename' => 'B00_2024.pdf',
                'mime' => 'application/pdf', 'size' => 33, 'hash_sha256' => 'abc123',
                'skip_reason' => null, 'content_base64' => base64_encode('%PDF-1.4 fixture bilancio'),
            ],
        ],
    ]);

    app(SyncCaiRuntsRegistration::class)->run($section);

    expect(CaiDocument::query()->count())->toBe(1);
    Queue::assertNothingPushed();
});
