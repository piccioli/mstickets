<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Enums\CaiDocumentAnalysisStatus;
use App\Domain\CaiDirectory\Enums\CaiDocumentSource;
use App\Domain\CaiDirectory\Jobs\AnalyzeCaiFinancialStatementDocument;
use App\Domain\CaiDirectory\Models\CaiDocument;
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

test('handle never overwrites an already-populated field with null (a second, structurally different document for the same year must not clobber good data)', function (): void {
    Storage::fake('cai-documents');
    Storage::disk('cai-documents')->put('12345/stato-patrimoniale-2025.pdf', '%PDF-1.4 fixture');

    $document = caiDocument(['file_path' => '12345/stato-patrimoniale-2025.pdf', 'year' => 2025]);
    CaiFinancialStatement::create([
        'cai_runts_registration_id' => $document->cai_runts_registration_id,
        'year' => 2025,
        'total_expenses' => 172413.0,
        'total_revenues' => 200664.0,
        'net_result' => 24409.0,
    ]);

    // Simula un secondo documento per lo stesso anno con una struttura diversa (es. "Stato
    // Patrimoniale" invece di "Rendiconto Gestionale"): l'analizzatore non trova quasi nulla,
    // ma UN campo (totale_oneri) risulta comunque diverso — verifica che quel singolo campo
    // aggiorni davvero, mentre gli altri, già buoni, restano intatti.
    Http::fake([
        'http://cai-runts-scraper:8000/analyze/bilancio' => Http::response([
            'oneri_a_interesse_generale' => null, 'oneri_b_attivita_diverse' => null,
            'oneri_c_raccolta_fondi' => null, 'oneri_d_finanziarie_patrimoniali' => null,
            'oneri_e_supporto_generale' => null, 'totale_oneri' => 999999.0,
            'proventi_a_interesse_generale' => null, 'proventi_b_attivita_diverse' => null,
            'proventi_c_raccolta_fondi' => null, 'proventi_d_finanziarie_patrimoniali' => null,
            'proventi_e_supporto_generale' => null, 'totale_proventi' => null,
            'risultato_ante_imposte' => null, 'imposte' => null, 'risultato_esercizio' => null,
        ]),
    ]);

    (new AnalyzeCaiFinancialStatementDocument($document->id))->handle(app(CaiRuntsScraperClient::class));

    $statement = CaiFinancialStatement::query()
        ->where('cai_runts_registration_id', $document->cai_runts_registration_id)
        ->where('year', 2025)
        ->sole();

    expect($statement->total_expenses)->toEqual(999999.0);
    expect($statement->total_revenues)->toEqual(200664.0);
    expect($statement->net_result)->toEqual(24409.0);
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

test('handle marks the document itself as Extracted with the raw text excerpt and OCR flag when at least one field is found', function (): void {
    Storage::fake('cai-documents');
    Storage::disk('cai-documents')->put('12345/bilancio-2024.pdf', '%PDF-1.4 fixture');

    $document = caiDocument(['file_path' => '12345/bilancio-2024.pdf', 'year' => 2024, 'document_type' => 'bilancio_esercizio']);

    Http::fake([
        'http://cai-runts-scraper:8000/analyze/bilancio' => Http::response([
            'oneri_a_interesse_generale' => null, 'oneri_b_attivita_diverse' => null,
            'oneri_c_raccolta_fondi' => null, 'oneri_d_finanziarie_patrimoniali' => null,
            'oneri_e_supporto_generale' => null, 'totale_oneri' => 1200.0,
            'proventi_a_interesse_generale' => null, 'proventi_b_attivita_diverse' => null,
            'proventi_c_raccolta_fondi' => null, 'proventi_d_finanziarie_patrimoniali' => null,
            'proventi_e_supporto_generale' => null, 'totale_proventi' => null,
            'risultato_ante_imposte' => null, 'imposte' => null, 'risultato_esercizio' => null,
            'raw_text' => 'Totale oneri e costi € 1.200', 'ocr' => false,
        ]),
    ]);

    (new AnalyzeCaiFinancialStatementDocument($document->id))->handle(app(CaiRuntsScraperClient::class));

    $document = $document->fresh();
    expect($document->financial_analysis_status)->toBe(CaiDocumentAnalysisStatus::Extracted);
    expect($document->raw_text_excerpt)->toBe('Totale oneri e costi € 1.200');
    expect($document->extracted_via_ocr)->toBeFalse();
});

test('handle creates a CaiFinancialStatement keyed by section for a document with no registration', function (): void {
    Storage::fake('cai-documents');
    Storage::disk('cai-documents')->put('9216049/bilancio-2024.pdf', '%PDF-1.4 fixture');

    $section = caiSection(['codice_cai' => '9216049']);
    $document = CaiDocument::create([
        'cai_section_id' => $section->codice_cai,
        'document_type' => 'bilancio_esercizio',
        'year' => 2024,
        'file_path' => '9216049/bilancio-2024.pdf',
        'source' => CaiDocumentSource::Manual,
    ]);

    Http::fake([
        'http://cai-runts-scraper:8000/analyze/bilancio' => Http::response([
            'oneri_a_interesse_generale' => null, 'oneri_b_attivita_diverse' => null,
            'oneri_c_raccolta_fondi' => null, 'oneri_d_finanziarie_patrimoniali' => null,
            'oneri_e_supporto_generale' => null, 'totale_oneri' => 500.0,
            'proventi_a_interesse_generale' => null, 'proventi_b_attivita_diverse' => null,
            'proventi_c_raccolta_fondi' => null, 'proventi_d_finanziarie_patrimoniali' => null,
            'proventi_e_supporto_generale' => null, 'totale_proventi' => null,
            'risultato_ante_imposte' => null, 'imposte' => null, 'risultato_esercizio' => null,
        ]),
    ]);

    (new AnalyzeCaiFinancialStatementDocument($document->id))->handle(app(CaiRuntsScraperClient::class));

    $statement = CaiFinancialStatement::query()
        ->where('cai_section_id', $section->codice_cai)
        ->where('year', 2024)
        ->sole();

    expect($statement->total_expenses)->toEqual(500.0)
        ->and($statement->cai_runts_registration_id)->toBeNull();
});

test('handle updates an existing section-keyed CaiFinancialStatement without touching a registration-keyed one for the same year', function (): void {
    Storage::fake('cai-documents');
    Storage::disk('cai-documents')->put('9216049/bilancio-2024.pdf', '%PDF-1.4 fixture');

    $section = caiSection(['codice_cai' => '9216049']);
    $registration = caiRuntsRegistration(['cai_section_id' => $section->codice_cai]);
    $registrationStatement = CaiFinancialStatement::create([
        'cai_runts_registration_id' => $registration->id_runts,
        'year' => 2024,
        'total_expenses' => 1.0,
    ]);

    $document = CaiDocument::create([
        'cai_section_id' => $section->codice_cai,
        'document_type' => 'bilancio_esercizio',
        'year' => 2024,
        'file_path' => '9216049/bilancio-2024.pdf',
        'source' => CaiDocumentSource::Manual,
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

    expect(CaiFinancialStatement::query()->where('cai_section_id', $section->codice_cai)->where('year', 2024)->sole()->total_expenses)->toEqual(999.0);
    expect($registrationStatement->fresh()->total_expenses)->toEqual(1.0);
});

test('handle marks the document as NoDataExtracted when every financial field comes back null', function (): void {
    Storage::fake('cai-documents');
    Storage::disk('cai-documents')->put('12345/stato-patrimoniale.pdf', '%PDF-1.4 fixture');

    $document = caiDocument(['file_path' => '12345/stato-patrimoniale.pdf', 'year' => 2024, 'document_type' => 'bilancio_esercizio']);

    Http::fake([
        'http://cai-runts-scraper:8000/analyze/bilancio' => Http::response([
            'oneri_a_interesse_generale' => null, 'oneri_b_attivita_diverse' => null,
            'oneri_c_raccolta_fondi' => null, 'oneri_d_finanziarie_patrimoniali' => null,
            'oneri_e_supporto_generale' => null, 'totale_oneri' => null,
            'proventi_a_interesse_generale' => null, 'proventi_b_attivita_diverse' => null,
            'proventi_c_raccolta_fondi' => null, 'proventi_d_finanziarie_patrimoniali' => null,
            'proventi_e_supporto_generale' => null, 'totale_proventi' => null,
            'risultato_ante_imposte' => null, 'imposte' => null, 'risultato_esercizio' => null,
            'raw_text' => 'STATO PATRIMONIALE (layout non riconosciuto dai pattern attuali)', 'ocr' => true,
        ]),
    ]);

    (new AnalyzeCaiFinancialStatementDocument($document->id))->handle(app(CaiRuntsScraperClient::class));

    $document = $document->fresh();
    expect($document->financial_analysis_status)->toBe(CaiDocumentAnalysisStatus::NoDataExtracted);
    expect($document->raw_text_excerpt)->toBe('STATO PATRIMONIALE (layout non riconosciuto dai pattern attuali)');
    expect($document->extracted_via_ocr)->toBeTrue();
});

/**
 * @param  array<string, float|null>  $overrides
 * @return array<string, mixed>
 */
function analyzerResponse(array $overrides = []): array
{
    return [
        'oneri_a_interesse_generale' => null, 'oneri_b_attivita_diverse' => null,
        'oneri_c_raccolta_fondi' => null, 'oneri_d_finanziarie_patrimoniali' => null,
        'oneri_e_supporto_generale' => null, 'totale_oneri' => null,
        'proventi_a_interesse_generale' => null, 'proventi_b_attivita_diverse' => null,
        'proventi_c_raccolta_fondi' => null, 'proventi_d_finanziarie_patrimoniali' => null,
        'proventi_e_supporto_generale' => null, 'totale_proventi' => null,
        'risultato_ante_imposte' => null, 'imposte' => null, 'risultato_esercizio' => null,
        'totale_attivo' => null, 'totale_passivo' => null, 'patrimonio_netto' => null,
        ...$overrides,
    ];
}

test('a document with only balance sheet data is Extracted and fills the balance sheet columns', function (): void {
    Storage::fake('cai-documents');
    Storage::disk('cai-documents')->put('12345/sp.pdf', '%PDF-1.4 fixture');
    $document = caiDocument(['file_path' => '12345/sp.pdf', 'year' => 2025]);

    Http::fake(['http://cai-runts-scraper:8000/analyze/bilancio' => Http::response(analyzerResponse([
        'totale_attivo' => 50000.0, 'totale_passivo' => 50000.0, 'patrimonio_netto' => 30000.0,
    ]))]);

    (new AnalyzeCaiFinancialStatementDocument($document->id))->handle(app(CaiRuntsScraperClient::class));

    $statement = CaiFinancialStatement::query()->where('year', 2025)->sole();
    expect($document->fresh()->financial_analysis_status)->toBe(CaiDocumentAnalysisStatus::Extracted)
        ->and($statement->total_assets)->toEqual(50000.0)
        ->and($statement->total_liabilities)->toEqual(50000.0)
        ->and($statement->net_equity)->toEqual(30000.0)
        ->and($statement->hasBalanceSheetData())->toBeTrue()
        ->and($statement->hasIncomeStatementData())->toBeFalse();
});

test('a document with only income statement data leaves the balance sheet columns null', function (): void {
    Storage::fake('cai-documents');
    Storage::disk('cai-documents')->put('12345/ce.pdf', '%PDF-1.4 fixture');
    $document = caiDocument(['file_path' => '12345/ce.pdf', 'year' => 2025]);

    Http::fake(['http://cai-runts-scraper:8000/analyze/bilancio' => Http::response(analyzerResponse(['totale_oneri' => 100.0]))]);

    (new AnalyzeCaiFinancialStatementDocument($document->id))->handle(app(CaiRuntsScraperClient::class));

    $statement = CaiFinancialStatement::query()->where('year', 2025)->sole();
    expect($statement->total_assets)->toBeNull()
        ->and($statement->hasIncomeStatementData())->toBeTrue()
        ->and($statement->hasBalanceSheetData())->toBeFalse();
});

test('an income statement document and a balance sheet document for the same year merge into one record', function (): void {
    Storage::fake('cai-documents');
    Storage::disk('cai-documents')->put('12345/ce.pdf', '%PDF-1.4 fixture');
    Storage::disk('cai-documents')->put('12345/sp.pdf', '%PDF-1.4 fixture');
    $income = caiDocument(['file_path' => '12345/ce.pdf', 'year' => 2025]);
    $balance = caiDocument([
        'file_path' => '12345/sp.pdf', 'year' => 2025,
        'cai_runts_registration_id' => $income->cai_runts_registration_id,
    ]);

    Http::fake(['http://cai-runts-scraper:8000/analyze/bilancio' => Http::sequence()
        ->push(analyzerResponse(['totale_oneri' => 100.0, 'totale_proventi' => 150.0, 'risultato_esercizio' => 50.0]))
        ->push(analyzerResponse(['totale_attivo' => 9000.0, 'patrimonio_netto' => 4000.0]))]);

    (new AnalyzeCaiFinancialStatementDocument($income->id))->handle(app(CaiRuntsScraperClient::class));
    (new AnalyzeCaiFinancialStatementDocument($balance->id))->handle(app(CaiRuntsScraperClient::class));

    $statement = CaiFinancialStatement::query()->where('year', 2025)->sole();
    expect($statement->total_expenses)->toEqual(100.0)
        ->and($statement->net_result)->toEqual(50.0)
        ->and($statement->total_assets)->toEqual(9000.0)
        ->and($statement->net_equity)->toEqual(4000.0)
        ->and($statement->hasIncomeStatementData())->toBeTrue()
        ->and($statement->hasBalanceSheetData())->toBeTrue();
});
