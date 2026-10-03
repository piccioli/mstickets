<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Models\CaiDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

dataset('cai financial documents', fn () => caiFinancialDocumentRows());

test('instance methods classify a document', function (string $type, string $title, bool $income, bool $balance): void {
    $document = new CaiDocument(['document_type' => $type, 'title' => $title]);

    expect($document->isIncomeStatement())->toBe($income)
        ->and($document->isBalanceSheet())->toBe($balance);
})->with('cai financial documents');

test('scopes agree with the instance methods on the same rows', function (): void {
    $rows = collect(array_values(caiFinancialDocumentRows()))->map(fn (array $row, int $i) => caiDocument([
        'document_type' => $row[0],
        'title' => $row[1],
        'hash' => 'kind-'.$i,
    ]));

    $incomeIds = CaiDocument::query()->incomeStatement()->pluck('id')->all();
    $balanceIds = CaiDocument::query()->balanceSheet()->pluck('id')->all();

    foreach ($rows as $doc) {
        expect(in_array($doc->id, $incomeIds, true))->toBe($doc->isIncomeStatement(), $doc->title)
            ->and(in_array($doc->id, $balanceIds, true))->toBe($doc->isBalanceSheet(), $doc->title);
    }

    expect(count($incomeIds))->toBeGreaterThan(0)
        ->and(count($balanceIds))->toBeGreaterThan(0);
});

test('scopes match the expected classification of the dataset', function (): void {
    foreach (array_values(caiFinancialDocumentRows()) as $i => $row) {
        caiDocument(['document_type' => $row[0], 'title' => $row[1], 'hash' => 'exp-'.$i]);
    }

    $income = CaiDocument::query()->incomeStatement()->pluck('title')->all();
    $balance = CaiDocument::query()->balanceSheet()->pluck('title')->all();

    $expectedIncome = array_column(array_filter(caiFinancialDocumentRows(), fn (array $r) => $r[2]), 1);
    $expectedBalance = array_column(array_filter(caiFinancialDocumentRows(), fn (array $r) => $r[3]), 1);

    expect($income)->toEqualCanonicalizing($expectedIncome)
        ->and($balance)->toEqualCanonicalizing($expectedBalance);
});

test('forYear filters on the year column', function (): void {
    caiDocument(['year' => 2025, 'hash' => 'y1']);
    caiDocument(['year' => 2024, 'hash' => 'y2']);

    expect(CaiDocument::query()->forYear(2025)->count())->toBe(1);
});

/**
 * Titoli reali dall'import dei bilanci 2025: etichetta => [document_type, title, isIncomeStatement, isBalanceSheet].
 *
 * @return array<string, array{string, string, bool, bool}>
 */
function caiFinancialDocumentRows(): array
{
    return [
        'bilancio consuntivo' => ['altro', 'Bilancio consuntivo', true, false],
        'mod d' => ['mod_d', 'Mod D - Rendiconto per cassa', true, false],
        'conto economico' => ['altro', 'Conto economico', true, false],
        'sp e ce' => ['altro', 'Stato Patrimoniale e conto economico', true, true],
        'stato patrimoniale' => ['altro', 'Stato Patrimoniale', false, true],
        'rendiconto per cassa' => ['altro', 'Rendiconto per cassa', true, false],
        'mod a e b' => ['mod_a_b', 'Mod A e B - Stato Patrimoniale e Rendiconto gestionale', true, true],
        'mod a' => ['mod_a', 'Mod A - Stato Patrimoniale', false, true],
        'mod b' => ['mod_b', 'Mod B - Rendiconto gestionale', true, false],
        'completo abc' => ['completo_a_b_c', 'Bilancio completo - Mod A  Mod B e Relazione di missione', true, true],
        'economico finanziario' => ['bilancio_economico_finanziario', 'Bilancio economico e finanziario', true, false],
        'situazione patrimoniale' => ['situazione_patrimoniale', 'SITUAZIONE PATRIMONIALE', false, true],
        'bilancio patrimoniale' => ['altro', 'Bilancio patrimoniale', false, true],
        'patrimoniale ed economico' => ['altro', 'Bilancio patrimoniale ed economico', true, true],
        'situazione economica' => ['altro', 'Situazione economica', true, false],
        'rendiconto economico' => ['altro', 'Rendiconto economico', true, false],
        'rendiconto finanziario' => ['altro', 'Documentazione assemblea - Verbale e Rendiconto finanziario', true, false],
        'completo sp ce' => ['altro', 'Bilancio completo - Stato Patrimoniale, Conto economico e Nota integrativa', true, true],
        'sp e revisori' => ['altro', 'Stato Patrimoniale e Relazione revisori', false, true],
        'runts mod b' => ['bilancio_esercizio', 'Mod. B - Rendiconto Gestionale', true, false],
        'runts mod a' => ['bilancio_esercizio', 'Mod. A - Stato Patrimoniale', false, true],
        'runts generico' => ['bilancio_esercizio', "BILANCIO D'ESERCIZIO", false, false],
        'relazione di missione' => ['relazione_missione', 'Relazione di missione', false, false],
        'verbale assemblea' => ['verbale_assemblea', 'Verbale assemblea', false, false],
        'quote associative' => ['altro', 'Quote associative 2026', false, false],
        'statuto' => ['statuto', 'STATUTO', false, false],
        'bilancio sociale' => ['bilancio_sociale', 'BILANCIO SOCIALE', false, false],
        'relazione attivita' => ['relazione_attivita', 'Relazione attività', false, false],
        'provvedimento' => ['altro', 'PROVVEDIMENTO DI VARIAZIONE', false, false],
    ];
}
