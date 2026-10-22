<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Enums\CaiDocumentSource;
use App\Domain\CaiDirectory\Models\CaiDocument;
use App\Domain\CaiDirectory\Models\CaiFinancialStatement;
use App\Domain\Identity\Enums\CustomerType;
use App\Domain\Identity\Enums\Region;
use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use App\Filament\Pages\RegionalGroupDashboard;
use App\Filament\Pages\RegionalGroupFinancialStatements;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Filament::setCurrentPanel('admin');
    $this->seed(RolePermissionSeeder::class);
});

function consolidatedPageCustomer(
    ?Region $region = Region::Toscana,
    ?CustomerType $type = CustomerType::GruppoRegionale,
): User {
    $customer = withRole(User::factory()->create(['name' => 'GR Toscana']), UserRole::Customer);
    $customer->forceFill(['customer_type' => $type, 'region' => $region])->save();

    return $customer;
}

/**
 * @param  array<string, mixed>  $values
 */
function consolidatedPageStatement(string $code, int $year, array $values): CaiFinancialStatement
{
    return CaiFinancialStatement::create(array_merge(['cai_section_id' => $code, 'year' => $year], $values));
}

/** Toscana 2025: A con tutti i dati, B solo conto economico, C solo PDF, D nulla. */
function consolidatedPageFixture(): void
{
    caiSection(['codice_cai' => '9226001', 'name' => 'SEZ. A', 'region' => 'TOSCANA']);
    caiSection(['codice_cai' => '9226002', 'name' => 'SEZ. B', 'region' => 'TOSCANA']);
    caiSection(['codice_cai' => '9226003', 'name' => 'SEZ. C', 'region' => 'TOSCANA']);
    caiSection(['codice_cai' => '9226004', 'name' => 'SEZ. D', 'region' => 'TOSCANA']);
    consolidatedPageStatement('9226001', 2025, ['total_revenues' => 100000, 'total_expenses' => 90000,
        'net_result' => 10000, 'net_equity' => 50000, 'total_assets' => 120000]);
    consolidatedPageStatement('9226002', 2025, ['total_revenues' => 86420, 'total_expenses' => 82310, 'net_result' => 4110]);
    caiDocument(['title' => 'Rendiconto gestionale', 'cai_runts_registration_id' => null, 'cai_section_id' => '9226003',
        'year' => 2025, 'source' => CaiDocumentSource::Manual, 'file_path' => 'cai/c.pdf']);
}

test('the page shows the header, the consolidated cards with their notes and the coverage', function (): void {
    consolidatedPageFixture();

    $this->actingAs(consolidatedPageCustomer());

    Livewire::test(RegionalGroupFinancialStatements::class)
        ->assertSuccessful()
        ->assertSeeText('← Dashboard')
        ->assertSee('href="'.RegionalGroupDashboard::getUrl().'"', escape: false)
        ->assertSeeText('Bilancio consolidato Sezioni')
        ->assertSeeText('GR Toscana · Codice CAI 9226000')
        ->assertSeeTextInOrder(['2021', '2022', '2023', '2024', '2025'])
        ->assertSeeText('Conto economico consolidato')
        ->assertSeeText('Avanzo')
        ->assertSeeText('+14.110 €')
        ->assertSeeText('186.420 €')
        ->assertSeeText('172.310 €')
        ->assertSeeText('Somma di 2 Sezioni su 4')
        ->assertSeeText('Stato patrimoniale consolidato')
        ->assertSeeText('50.000 €')
        ->assertSeeText('120.000 €')
        ->assertSeeText('Somma di 1 Sezione su 4')
        ->assertSeeText('Esercizio 2025')
        ->assertDontSeeText('da verificare')
        ->assertSeeText('Copertura')
        ->assertSeeText('su 4 Sezioni hanno il bilancio con i dati')
        ->assertSeeTextInOrder(['Dati presenti', '2', 'Solo PDF', '1', 'Nessun bilancio', '1'])
        ->assertSeeText('I totali consolidati sono parziali: mancano i dati di 2 Sezioni, 1 delle quali con il solo PDF.')
        ->assertDontSeeText('Nessuna regione associata')
        ->assertDontSeeText('Vedi dettaglio');
});

test('the 2025 year button is active and the cards are static, without links', function (): void {
    consolidatedPageFixture();

    $this->actingAs(consolidatedPageCustomer());

    $html = Livewire::test(RegionalGroupFinancialStatements::class)->html();

    expect(substr_count($html, 'ms-consolidated__year--active'))->toBe(1)
        ->and($html)->toMatch('/ms-consolidated__year--active"[^>]*>2025</')
        ->and(substr_count($html, 'ms-metric-badge--static'))->toBe(2)
        ->and($html)->not->toContain('ms-metric-badge__link');
});

test('a card that includes values to verify says how many', function (): void {
    foreach (['9226001', '9226002', '9226003', '9226004'] as $i => $code) {
        caiSection(['codice_cai' => $code, 'region' => 'TOSCANA']);
        consolidatedPageStatement($code, 2025, $i === 3
            ? ['total_revenues' => 202500000, 'total_expenses' => 202400000, 'net_result' => 100000, 'net_equity' => 1000000]
            : ['total_revenues' => 1000, 'total_expenses' => 900, 'net_result' => 100, 'net_equity' => 1000]);
    }

    $this->actingAs(consolidatedPageCustomer());

    Livewire::test(RegionalGroupFinancialStatements::class)
        ->assertSeeText('Include 2 valori da verificare')
        ->assertSeeText('Include 1 valore da verificare')
        ->assertSeeText('Tutte le Sezioni hanno fornito il bilancio con i dati.');
});

test('the coverage note handles a single missing section with only the PDF', function (): void {
    caiSection(['codice_cai' => '9226001', 'region' => 'TOSCANA']);
    caiSection(['codice_cai' => '9226002', 'region' => 'TOSCANA']);
    consolidatedPageStatement('9226001', 2025, ['total_revenues' => 1000]);
    caiDocument(['title' => 'Rendiconto gestionale', 'cai_runts_registration_id' => null, 'cai_section_id' => '9226002',
        'year' => 2025, 'source' => CaiDocumentSource::Manual, 'file_path' => 'cai/b.pdf']);

    $this->actingAs(consolidatedPageCustomer());

    Livewire::test(RegionalGroupFinancialStatements::class)
        ->assertSeeText('I totali consolidati sono parziali: mancano i dati di 1 Sezione, con il solo PDF.');
});

test('a year without data shows grey cards and the coverage note for no data', function (): void {
    consolidatedPageFixture();

    $this->actingAs(consolidatedPageCustomer());

    $html = Livewire::withQueryParams(['esercizio' => 2024])
        ->test(RegionalGroupFinancialStatements::class)
        ->assertSet('year', 2024)
        ->assertSeeText('Non disponibile · Esercizio 2024')
        ->assertSeeText('Nessuna Sezione con dati su 4')
        ->assertSeeText('Per l\'esercizio 2024 nessuna Sezione ha un bilancio con i dati.')
        ->assertDontSeeText('Somma di')
        ->html();

    expect(substr_count($html, 'ms-metric-badge--unavailable'))->toBe(2)
        ->and($html)->toMatch('/ms-consolidated__year--active"[^>]*>2024</');
});

test('changing the year from the selector reloads the consolidated statements', function (): void {
    consolidatedPageFixture();
    consolidatedPageStatement('9226004', 2023, ['total_revenues' => 5000, 'total_expenses' => 7000, 'net_result' => -2000]);

    $this->actingAs(consolidatedPageCustomer());

    Livewire::test(RegionalGroupFinancialStatements::class)
        ->assertSeeText('+14.110 €')
        ->call('selectYear', 2023)
        ->assertSet('year', 2023)
        ->assertSeeText('Disavanzo')
        ->assertSeeText('−2.000 €')
        ->assertSeeText('Somma di 1 Sezione su 4')
        ->assertSeeText('Esercizio 2023')
        ->assertDontSeeText('+14.110 €');
});

test('the year is read from the URL', function (): void {
    consolidatedPageFixture();
    consolidatedPageStatement('9226004', 2023, ['net_result' => -2000]);

    $this->actingAs(consolidatedPageCustomer())
        ->get(RegionalGroupFinancialStatements::getUrl().'?esercizio=2023')
        ->assertSuccessful()
        ->assertSeeText('−2.000 €')
        ->assertDontSeeText('+14.110 €');
});

test('a year outside the available ones falls back to the default year', function (string $year): void {
    consolidatedPageFixture();

    $this->actingAs(consolidatedPageCustomer())
        ->get(RegionalGroupFinancialStatements::getUrl().'?esercizio='.$year)
        ->assertSuccessful()
        ->assertSeeText('+14.110 €')
        ->assertSeeText('Somma di 2 Sezioni su 4');
})->with(['too old' => ['1999'], 'not a number' => ['abc']]);

test('a regional group without a region sees only the notice', function (): void {
    consolidatedPageFixture();

    $this->actingAs(consolidatedPageCustomer(region: null));

    Livewire::test(RegionalGroupFinancialStatements::class)
        ->assertSuccessful()
        ->assertSeeText('Nessuna regione associata al tuo account: contatta Montagna Servizi.')
        ->assertDontSeeText('Codice CAI')
        ->assertDontSeeText('Esercizio')
        ->assertDontSeeText('Conto economico consolidato')
        ->assertDontSeeText('Copertura');
});

test('a staff user cannot access the consolidated statements page', function (): void {
    $this->actingAs(withRole(User::factory()->create(), UserRole::Developer))
        ->get(RegionalGroupFinancialStatements::getUrl())
        ->assertForbidden();
});

test('a customer of another type cannot access the consolidated statements page', function (?CustomerType $type): void {
    $this->actingAs(consolidatedPageCustomer(type: $type))
        ->get(RegionalGroupFinancialStatements::getUrl())
        ->assertForbidden();
})->with([
    'Sezione' => [CustomerType::Sezione],
    'Organo Tecnico' => [CustomerType::OrganoTecnicoStrutturaOperativa],
    'nessun tipo' => [null],
]);

test('the consolidated statements page keeps its slug and is not in the navigation', function (): void {
    expect(RegionalGroupFinancialStatements::getUrl())->toEndWith('/gr/bilancio')
        ->and(RegionalGroupFinancialStatements::shouldRegisterNavigation())->toBeFalse();
});

/** Nomi delle Sezioni nell'ordine della tabella desktop. */
function consolidatedTableNames(string $html): array
{
    preg_match_all('/ms-consolidated__name">([^<]+)</', $html, $matches);

    return array_map('trim', $matches[1]);
}

test('the sections table shows every section with status, source and values', function (): void {
    consolidatedPageFixture();

    $this->actingAs(consolidatedPageCustomer());

    $component = Livewire::test(RegionalGroupFinancialStatements::class)
        ->assertSeeText('Sezioni del GR')
        ->assertSeeText('4 Sezioni')
        ->assertSeeTextInOrder(['Sezione', 'Stato', 'Fonte', 'Ricavi', 'Costi', 'Risultato netto', 'Patrimonio netto',
            'Totale attivo', 'PDF'])
        ->assertSeeTextInOrder(['SEZ. A', 'Dati presenti', 'Caricato a mano', '100.000 €', '90.000 €', '+10.000 €',
            '50.000 €', '120.000 €'])
        ->assertSeeTextInOrder(['SEZ. B', 'Dati presenti', 'Caricato a mano', '86.420 €', '82.310 €', '+4.110 €', '—', '—'])
        ->assertSeeTextInOrder(['SEZ. C', 'Solo PDF', 'Caricato a mano'])
        ->assertSeeTextInOrder(['SEZ. D', 'Nessun bilancio'])
        ->assertSeeText('Dati non ancora estratti dal PDF.')
        ->assertSeeText('Fonte: Caricato a mano');

    expect(consolidatedTableNames($component->html()))->toBe(['SEZ. A', 'SEZ. B', 'SEZ. C', 'SEZ. D']);
});

test('the status filters show their counts and filter the rows', function (): void {
    consolidatedPageFixture();

    $this->actingAs(consolidatedPageCustomer());

    $component = Livewire::test(RegionalGroupFinancialStatements::class);

    preg_match_all('/<button[^>]*ms-consolidated__filter[^>]*>\s*([^<]+?)\s*<span class="ms-consolidated__filter-count">(\d+)</', $component->html(), $matches);

    expect(array_combine($matches[1], $matches[2]))
        ->toBe(['Tutte' => '4', 'Dati presenti' => '2', 'Solo PDF' => '1', 'Nessun bilancio' => '1']);

    $component->call('filterStatus', 'solo-pdf')
        ->assertSet('status', 'solo-pdf')
        ->assertSeeText('1 di 4 Sezioni');

    expect(consolidatedTableNames($component->html()))->toBe(['SEZ. C']);

    $component->call('filterStatus', 'dati');
    expect(consolidatedTableNames($component->html()))->toBe(['SEZ. A', 'SEZ. B']);

    $component->call('filterStatus', null)
        ->assertSet('status', null)
        ->assertSeeText('4 Sezioni')
        ->assertDontSeeText('di 4 Sezioni');
});

test('a filter without rows says so for the year', function (): void {
    consolidatedPageFixture();

    $this->actingAs(consolidatedPageCustomer());

    $component = Livewire::withQueryParams(['esercizio' => 2024, 'stato' => 'dati'])
        ->test(RegionalGroupFinancialStatements::class)
        ->assertSeeText('0 di 4 Sezioni')
        ->assertSeeText('Nessuna Sezione in questo stato per l\'esercizio 2024.');

    expect(consolidatedTableNames($component->html()))->toBe([]);
});

test('sorting by name inverts on the second click', function (): void {
    consolidatedPageFixture();

    $this->actingAs(consolidatedPageCustomer());

    $component = Livewire::test(RegionalGroupFinancialStatements::class)
        ->call('sortBy', 'nome')
        ->assertSet('sort', null)
        ->assertSet('direction', 'desc');

    expect(consolidatedTableNames($component->html()))->toBe(['SEZ. D', 'SEZ. C', 'SEZ. B', 'SEZ. A']);

    $component->call('sortBy', 'nome')->assertSet('direction', null);
    expect(consolidatedTableNames($component->html()))->toBe(['SEZ. A', 'SEZ. B', 'SEZ. C', 'SEZ. D']);
});

test('sorting by a number starts descending, inverts, and always keeps missing values at the bottom', function (): void {
    consolidatedPageFixture();

    $this->actingAs(consolidatedPageCustomer());

    $component = Livewire::test(RegionalGroupFinancialStatements::class)
        ->call('sortBy', 'ricavi')
        ->assertSet('sort', 'ricavi')
        ->assertSet('direction', null);

    $html = $component->html();
    expect(consolidatedTableNames($html))->toBe(['SEZ. A', 'SEZ. B', 'SEZ. C', 'SEZ. D'])
        ->and($html)->toMatch('/aria-sort="descending"[^>]*>\s*<button[^>]*ms-consolidated__sort--active[^>]*>\s*Ricavi\s*<span[^>]*>↓</');

    $component->call('sortBy', 'ricavi')->assertSet('direction', 'asc');
    expect(consolidatedTableNames($component->html()))->toBe(['SEZ. B', 'SEZ. A', 'SEZ. C', 'SEZ. D']);

    $component->call('sortBy', 'patrimonio')->assertSet('sort', 'patrimonio')->assertSet('direction', null);
    expect(consolidatedTableNames($component->html()))->toBe(['SEZ. A', 'SEZ. B', 'SEZ. C', 'SEZ. D']);
});

test('sorting by status follows the status order and ties are broken by name', function (): void {
    consolidatedPageFixture();

    $this->actingAs(consolidatedPageCustomer());

    $component = Livewire::test(RegionalGroupFinancialStatements::class)->call('sortBy', 'stato');
    expect(consolidatedTableNames($component->html()))->toBe(['SEZ. A', 'SEZ. B', 'SEZ. C', 'SEZ. D']);

    $component->call('sortBy', 'stato');
    expect(consolidatedTableNames($component->html()))->toBe(['SEZ. D', 'SEZ. C', 'SEZ. A', 'SEZ. B']);
});

test('sorting and filtering are restored from the URL', function (): void {
    consolidatedPageFixture();

    $this->actingAs(consolidatedPageCustomer());

    $component = Livewire::withQueryParams(['ordina' => 'ricavi', 'verso' => 'asc', 'stato' => 'dati'])
        ->test(RegionalGroupFinancialStatements::class);

    expect(consolidatedTableNames($component->html()))->toBe(['SEZ. B', 'SEZ. A']);

    $component = Livewire::withQueryParams(['ordina' => 'boh', 'verso' => 'boh', 'stato' => 'boh'])
        ->test(RegionalGroupFinancialStatements::class)
        ->assertSeeText('4 Sezioni');

    expect(consolidatedTableNames($component->html()))->toBe(['SEZ. A', 'SEZ. B', 'SEZ. C', 'SEZ. D']);
});

test('the mobile sort menu lists its options and applies the chosen one', function (): void {
    consolidatedPageFixture();

    $this->actingAs(consolidatedPageCustomer());

    $component = Livewire::test(RegionalGroupFinancialStatements::class)
        ->assertSeeText('Ordina per')
        ->assertSeeTextInOrder(['Nome (A–Z)', 'Stato', 'Ricavi (dal più alto)', 'Costi (dal più alto)',
            'Risultato netto (dal più alto)', 'Risultato netto (dal più basso)', 'Patrimonio netto (dal più alto)',
            'Totale attivo (dal più alto)'])
        ->call('applySortOption', 'risultato:asc')
        ->assertSet('sort', 'risultato')
        ->assertSet('direction', 'asc');

    expect(consolidatedTableNames($component->html()))->toBe(['SEZ. B', 'SEZ. A', 'SEZ. C', 'SEZ. D'])
        ->and($component->html())->toContain('<option value="risultato:asc" selected');

    $component->call('applySortOption', 'nonsense')->assertSet('sort', 'risultato');
});

test('the PDF link is shown only for a section with a document', function (): void {
    consolidatedPageFixture();
    $document = CaiDocument::query()->where('cai_section_id', '9226003')->sole();

    $this->actingAs(consolidatedPageCustomer());

    $html = Livewire::test(RegionalGroupFinancialStatements::class)->html();
    $url = route('cai-documents.download', $document);

    expect(substr_count($html, 'href="'.$url.'" target="_blank"'))->toBe(2)
        ->and(substr_count($html, 'class="ms-consolidated__pdf"'))->toBe(1)
        ->and(substr_count($html, 'ms-consolidated__item-pdf'))->toBe(1);
});

test('a value to verify is highlighted in the table and in the mobile list', function (): void {
    foreach (['9226001', '9226002', '9226003', '9226004'] as $i => $code) {
        caiSection(['codice_cai' => $code, 'name' => 'SEZ. '.$i, 'region' => 'TOSCANA']);
        consolidatedPageStatement($code, 2025, $i === 3
            ? ['total_revenues' => 202500000, 'total_expenses' => 202400000, 'net_result' => 100000]
            : ['total_revenues' => 1000, 'total_expenses' => 900, 'net_result' => 100]);
    }

    $this->actingAs(consolidatedPageCustomer());

    $html = Livewire::test(RegionalGroupFinancialStatements::class)
        ->assertSeeText('valore da verificare')
        ->html();

    expect(substr_count($html, 'ms-consolidated__review-note'))->toBe(4)
        ->and($html)->toMatch('/ms-consolidated__review-value[^>]*>\s*<svg.*?<\/svg>\s*202\.500\.000 €/s');
});

test('the total row sums the sections with data and a negative result is red', function (): void {
    consolidatedPageFixture();
    consolidatedPageStatement('9226004', 2023, ['total_revenues' => 5000, 'total_expenses' => 7000, 'net_result' => -2000]);

    $this->actingAs(consolidatedPageCustomer());

    $component = Livewire::test(RegionalGroupFinancialStatements::class)
        ->assertSeeTextInOrder(['Totale', 'Somma di 2 Sezioni con dati', '186.420 €', '172.310 €', '+14.110 €', '50.000 €',
            '120.000 €']);

    expect($component->html())->toMatch('/ms-consolidated__table-row--total.*?ms-consolidated__total-note/s');

    $component->call('selectYear', 2023)
        ->assertSeeText('Somma di 1 Sezione con dati');

    expect($component->html())->toContain('<span class="ms-consolidated__negative">−2.000 €</span>');

    $component->call('selectYear', 2024)->assertSeeText('Nessuna Sezione con dati');
});
