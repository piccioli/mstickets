<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Jobs\AnalyzeCaiFinancialStatementDocument;
use App\Domain\CaiDirectory\Models\CaiFinancialStatement;
use App\Domain\CaiDirectory\Support\CaiRuntsScraperClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('handle downloads the stored PDF, analyzes it, and creates a CaiFinancialStatement', function (): void {
    Storage::fake('cai-documents');
    Storage::disk('cai-documents')->put('12345/bilancio-2024.pdf', '%PDF-1.4 fixture');

    $document = caiDocument(['file_path' => '12345/bilancio-2024.pdf', 'year' => 2024]);

    Http::fake([
        'http://cai-runts-scraper:8000/analyze/bilancio' => Http::response([
            'oneri_a_interesse_generale' => 1000.0, 'oneri_b_attivita_diverse' => null,
            'oneri_c_raccolta_fondi' => null, 'oneri_d_finanziarie_patrimoniali' => null,
            'oneri_e_supporto_generale' => 200.0, 'totale_oneri' => 1200.0,
            'proventi_a_interesse_generale' => 1500.0, 'proventi_b_attivita_diverse' => null,
            'proventi_c_raccolta_fondi' => null, 'proventi_d_finanziarie_patrimoniali' => null,
            'proventi_e_supporto_generale' => null, 'totale_proventi' => 1500.0,
            'risultato_ante_imposte' => 300.0, 'imposte' => 50.0, 'risultato_esercizio' => 250.0,
            'raw_text' => '...', 'ocr' => false,
        ]),
    ]);

    (new AnalyzeCaiFinancialStatementDocument($document->id))->handle(app(CaiRuntsScraperClient::class));

    $statement = CaiFinancialStatement::query()
        ->where('cai_runts_registration_id', $document->cai_runts_registration_id)
        ->where('year', 2024)
        ->sole();

    expect($statement->total_expenses)->toEqual(1200.0);
    expect($statement->net_result)->toEqual(250.0);

    Http::assertSent(fn ($request): bool => $request->hasFile('file'));
});

test('handle updates an existing CaiFinancialStatement for the same registration+year', function (): void {
    Storage::fake('cai-documents');
    Storage::disk('cai-documents')->put('12345/bilancio-2024.pdf', '%PDF-1.4 fixture');

    $document = caiDocument(['file_path' => '12345/bilancio-2024.pdf', 'year' => 2024]);
    CaiFinancialStatement::create([
        'cai_runts_registration_id' => $document->cai_runts_registration_id,
        'year' => 2024,
        'total_expenses' => 1.0,
    ]);

    Http::fake([
        'http://cai-runts-scraper:8000/analyze/bilancio' => Http::response([
            'oneri_a_interesse_generale' => null, 'oneri_b_attivita_diverse' => null,
            'oneri_c_raccolta_fondi' => null, 'oneri_d_finanziarie_patrimoniali' => null,
            'oneri_e_supporto_generale' => null, 'totale_oneri' => 999.0,
            'proventi_a_interesse_generale' => null, 'proventi_b_attivita_diverse' => null,
            'proventi_c_raccolta_fondi' => null, 'proventi_d_finanziarie_patrimoniali' => null,
            'proventi_e_supporto_generale' => null, 'totale_proventi' => null,
            'risultato_ante_imposte' => null, 'imposte' => null, 'risultato_esercizio' => null,
        ]),
    ]);

    (new AnalyzeCaiFinancialStatementDocument($document->id))->handle(app(CaiRuntsScraperClient::class));

    expect(CaiFinancialStatement::query()->where('cai_runts_registration_id', $document->cai_runts_registration_id)->where('year', 2024)->count())->toBe(1);
});

test('handle does nothing when the CaiDocument no longer exists', function (): void {
    Http::fake();

    (new AnalyzeCaiFinancialStatementDocument(999999))->handle(app(CaiRuntsScraperClient::class));

    Http::assertNothingSent();
});

test('handle does nothing when the document has no extractable year', function (): void {
    Storage::fake('cai-documents');
    Storage::disk('cai-documents')->put('12345/bilancio-senza-anno.pdf', '%PDF-1.4 fixture');

    $document = caiDocument(['file_path' => '12345/bilancio-senza-anno.pdf', 'year' => null]);

    Http::fake();

    (new AnalyzeCaiFinancialStatementDocument($document->id))->handle(app(CaiRuntsScraperClient::class));

    Http::assertNothingSent();
    expect(CaiFinancialStatement::query()->where('cai_runts_registration_id', $document->cai_runts_registration_id)->count())->toBe(0);
});
