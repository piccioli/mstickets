<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Enums\CaiHutStatus;
use App\Domain\CaiDirectory\Enums\CaiHutType;
use App\Domain\Identity\Enums\CustomerType;
use App\Domain\Identity\Enums\Region;
use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use App\Filament\Pages\CaiHutDetail;
use App\Filament\Pages\RegionalGroupDashboard;
use App\Filament\Pages\RegionalGroupHuts;
use Carbon\CarbonImmutable;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Filament::setCurrentPanel('admin');
    $this->seed(RolePermissionSeeder::class);
    Carbon::setTestNow('2026-10-23 10:00:00');
    CarbonImmutable::setTestNow('2026-10-23 10:00:00');
});

afterEach(function (): void {
    Carbon::setTestNow();
    CarbonImmutable::setTestNow();
});

function grHutsCustomer(?Region $region = Region::Toscana, ?CustomerType $type = CustomerType::GruppoRegionale): User
{
    $customer = withRole(User::factory()->create(['name' => 'GR Toscana']), UserRole::Customer);
    $customer->forceFill(['customer_type' => $type, 'region' => $region])->save();

    return $customer;
}

/** HTML della pagina senza i commenti `<!--[if BLOCK]>` che Livewire mette attorno a ogni `@if`. */
function grHutsHtml(Testable $component): string
{
    return (string) preg_replace('/<!--.*?-->/s', '', $component->html());
}

/**
 * Nomi dei rifugi nell'ordine della tabella desktop (le card di mobile li ripetono dopo).
 *
 * @return list<string>
 */
function grHutsTableNames(Testable $component): array
{
    $html = grHutsHtml($component);
    $table = substr($html, (int) strpos($html, 'ms-gr-huts__rows'), (int) strpos($html, 'ms-gr-huts__cards') - (int) strpos($html, 'ms-gr-huts__rows'));
    preg_match_all('/class="ms-gr-huts__name">([^<]+)</', $table, $matches);

    return $matches[1];
}

/**
 * Toscana: 4 Sezioni (2 con rifugi), 4 rifugi con un contratto in scadenza (Rossi, tra 100 giorni), uno scaduto (Conti)
 * e uno valido (Isera); più un rifugio della Lombardia che non deve comparire. Nomi ASCII: dopo un update Livewire
 * l'HTML arriva nel JSON della risposta.
 */
function grHutsFixture(): void
{
    caiSection(['codice_cai' => '9226001', 'name' => 'SEZ. LUCCA', 'region' => 'TOSCANA']);
    caiSection(['codice_cai' => '9226002', 'name' => 'SEZ. MASSA', 'region' => 'TOSCANA']);
    caiSection(['codice_cai' => '9226003', 'name' => 'SEZ. PISA', 'region' => 'TOSCANA']);
    caiSection(['codice_cai' => '9226004', 'name' => 'SEZ. AREZZO', 'region' => 'TOSCANA']);
    caiSection(['codice_cai' => '9203001', 'name' => 'SEZ. MILANO', 'region' => 'LOMBARDIA']);

    caiHut(['id_cai' => 1, 'branch_code' => '9226001', 'name' => 'Rifugio Rossi', 'alias' => 'alla Pania', 'altitude' => 1609,
        'beds' => 26, 'management' => ['contract' => ['end_date' => '2027-01-31']], 'source_updated_at' => '2026-10-21 08:00:00']);
    caiHut(['id_cai' => 2, 'branch_code' => '9226001', 'name' => 'Bivacco Aronte', 'type' => CaiHutType::Bivouac,
        'altitude' => 1642, 'beds' => 4]);
    caiHut(['id_cai' => 3, 'branch_code' => '9226002', 'name' => 'Rifugio Conti', 'status' => CaiHutStatus::UnderRenovation,
        'altitude' => 1442, 'beds' => 22, 'management' => ['contract' => ['end_date' => '2026-06-30']]]);
    caiHut(['id_cai' => 4, 'branch_code' => '9226002', 'name' => 'Capanna Isera', 'type' => CaiHutType::SocialHut,
        'status' => CaiHutStatus::TemporarilyClosed, 'altitude' => null, 'beds' => null,
        'management' => ['contract' => ['end_date' => '2029-05-31']]]);
    caiHut(['id_cai' => 5, 'branch_code' => '9203001', 'name' => 'Rifugio Lombardo']);
}

test('the page shows the header, the summary, every hut of the group and the sections without huts', function (): void {
    grHutsFixture();
    $this->actingAs(grHutsCustomer());

    $component = Livewire::test(RegionalGroupHuts::class)
        ->assertSuccessful()
        ->assertSeeText('← Dashboard')
        ->assertSee('href="'.RegionalGroupDashboard::getUrl().'"', escape: false)
        ->assertSeeText('Rifugi del GR')
        ->assertSeeText('GR Toscana · Codice CAI 9226000')
        ->assertSeeText('Filtra elenco →')
        ->assertSeeText('alla Pania')
        ->assertSeeText('SEZ. LUCCA')
        ->assertSeeText('31/01/2027')
        ->assertSeeText('Scade tra 3 mesi')
        ->assertSeeText('30/06/2026')
        ->assertSeeText('Scaduto da 4 mesi')
        ->assertSeeText('4 rifugi · 2 Sezioni')
        ->assertSeeText('dati aggiornati al 21 ottobre 2026')
        ->assertSeeText('Sezioni senza rifugi')
        ->assertSeeText('2 su 4')
        ->assertSeeText('SEZ. AREZZO')
        ->assertSeeText('SEZ. PISA')
        ->assertSeeText('Raggruppa per Sezione')
        ->assertDontSeeText('Rifugio Lombardo');

    $html = grHutsHtml($component);

    expect(grHutsTableNames($component))->toBe(['Bivacco Aronte', 'Capanna Isera', 'Rifugio Conti', 'Rifugio Rossi'])
        ->and($html)->toMatch('/Rifugi totali<\/span>\s*<span class="ms-gr-huts__kpi-value">4</')
        ->and($html)->toMatch('/In attività<\/span>\s*<span class="ms-gr-huts__kpi-value">\s*2\s*<span class="ms-gr-huts__kpi-of">su 4</')
        ->and($html)->toMatch('/Posti letto totali<\/span>\s*<span class="ms-gr-huts__kpi-value">52</')
        ->and($html)->toMatch('/ms-gr-huts__kpi-value">\s*2\s*<span class="ms-gr-huts__kpi-of">su 4</')
        ->and($html)->toMatch('/ms-gr-huts__kpi-contract--expiring">\s*<span class="ms-gr-huts__kpi-value">1</')
        ->and($html)->toMatch('/ms-gr-huts__kpi-contract--expired">\s*<span class="ms-gr-huts__kpi-value">1</')
        ->and($html)->toContain('ms-gr-huts__contract-date--expiring')
        ->and($html)->toContain('ms-gr-huts__contract-date--expired')
        // Il riquadro delle Sezioni senza rifugi è chiuso se il GR ha rifugi.
        ->and($html)->toContain('x-data="{ open: false }"');
});

test('the map clusters a marker for every listed hut, with its section in the popup', function (): void {
    grHutsFixture();
    $this->actingAs(grHutsCustomer());

    $html = Livewire::test(RegionalGroupHuts::class)->html();

    preg_match('/data-cai-map-payload="([^"]*)"/', $html, $payloadMatch);
    preg_match('/data-cai-map-config="([^"]*)"/', $html, $configMatch);
    $payload = json_decode(html_entity_decode($payloadMatch[1]), true);
    $config = json_decode(html_entity_decode($configMatch[1]), true);
    $rossi = collect($payload['markers'])->firstWhere('id', 1);

    expect(collect($payload['markers'])->pluck('id')->sort()->values()->all())->toBe([1, 2, 3, 4])
        ->and($config['cluster'])->toBeTrue()
        ->and($rossi['popupHtml'])->toContain('SEZ. LUCCA')
        ->and($rossi['popupHtml'])->toContain(e(CaiHutDetail::getUrl(['idCai' => 1, 'da' => 'gr'])));
});

test('the contracts card filters expiring and expired contracts, sorted by end date', function (): void {
    grHutsFixture();
    $this->actingAs(grHutsCustomer());

    $component = Livewire::test(RegionalGroupHuts::class)
        ->call('toggleContracts')
        ->assertSet('contracts', '1')
        ->assertSeeText('Filtro attivo · ×')
        ->assertSeeText('2 di 4 rifugi')
        ->assertSeeText('Azzera filtri');

    expect(grHutsTableNames($component))->toBe(['Rifugio Conti', 'Rifugio Rossi']);

    $component->call('toggleContracts')->assertSet('contracts', null)->assertSeeText('Filtra elenco →');

    expect(grHutsTableNames($component))->toHaveCount(4);
});

test('with the contracts filter the end date column sorts with the huts without a date last', function (): void {
    grHutsFixture();
    $this->actingAs(grHutsCustomer());

    $component = Livewire::test(RegionalGroupHuts::class)->call('sortBy', 'scadenza')->assertSet('sort', 'scadenza');

    expect(grHutsTableNames($component))->toBe(['Rifugio Conti', 'Rifugio Rossi', 'Capanna Isera', 'Bivacco Aronte']);

    $component->call('sortBy', 'scadenza')->assertSet('direction', 'desc');

    expect(grHutsTableNames($component))->toBe(['Capanna Isera', 'Rifugio Rossi', 'Rifugio Conti', 'Bivacco Aronte']);
});

test('section, type and status filters combine and reset restores the list', function (): void {
    grHutsFixture();
    $this->actingAs(grHutsCustomer());

    $component = Livewire::test(RegionalGroupHuts::class)
        ->call('filterSection', '9226002')
        ->assertSet('section', '9226002')
        ->assertDontSeeText('Raggruppa per Sezione');

    expect(grHutsTableNames($component))->toBe(['Capanna Isera', 'Rifugio Conti']);

    $component->call('filterStatus', 'in-ristrutturazione')->assertSet('status', 'in-ristrutturazione');

    expect(grHutsTableNames($component))->toBe(['Rifugio Conti']);

    $component->call('filterType', 'bivacco')->assertSet('type', 'bivacco')
        ->assertSeeText('Nessun rifugio corrisponde ai filtri selezionati.');

    expect(grHutsTableNames($component))->toBe([]);

    $component->call('resetFilters')
        ->assertSet('section', null)
        ->assertSet('type', null)
        ->assertSet('status', null)
        ->assertSeeText('4 rifugi · 2 Sezioni');
});

test('the filter menus list only sections, types and states present, with counts', function (): void {
    grHutsFixture();
    $this->actingAs(grHutsCustomer());

    Livewire::test(RegionalGroupHuts::class)
        ->assertSeeText('SEZ. LUCCA (2)')
        ->assertSeeText('SEZ. MASSA (2)')
        ->assertDontSeeText('SEZ. PISA (')
        ->assertSeeText('Rifugio custodito (2)')
        ->assertSeeText('Capanna sociale (1)')
        ->assertDontSeeText('Rifugio incustodito (')
        ->assertSeeText('In ristrutturazione (1)')
        ->assertDontSeeText('Alienato (');
});

test('filters are read from the url and an unknown value counts as absent', function (): void {
    grHutsFixture();
    $this->actingAs(grHutsCustomer());

    $component = Livewire::withQueryParams(['sezione' => '9226001', 'tipo' => 'bivacco'])->test(RegionalGroupHuts::class);

    expect(grHutsTableNames($component))->toBe(['Bivacco Aronte']);

    $component = Livewire::withQueryParams(['sezione' => '9999999', 'tipo' => 'castello', 'stato' => 'boh', 'ordina' => 'x'])
        ->test(RegionalGroupHuts::class);

    expect(grHutsTableNames($component))->toHaveCount(4);
});

test('column headers sort the list, with numbers first from the highest and missing values last', function (): void {
    grHutsFixture();
    $this->actingAs(grHutsCustomer());

    $component = Livewire::test(RegionalGroupHuts::class)->call('sortBy', 'quota')->assertSet('sort', 'quota')->assertSet('direction', null);

    expect(grHutsTableNames($component))->toBe(['Bivacco Aronte', 'Rifugio Rossi', 'Rifugio Conti', 'Capanna Isera']);

    $component->call('sortBy', 'quota')->assertSet('direction', 'asc');

    expect(grHutsTableNames($component))->toBe(['Rifugio Conti', 'Rifugio Rossi', 'Bivacco Aronte', 'Capanna Isera']);

    $component->call('sortBy', 'posti')->assertSet('direction', null);

    expect(grHutsTableNames($component))->toBe(['Rifugio Rossi', 'Rifugio Conti', 'Bivacco Aronte', 'Capanna Isera']);

    $component->call('applySortOption', 'sezione:asc')->assertSet('sort', 'sezione');

    expect(grHutsTableNames($component))->toBe(['Bivacco Aronte', 'Rifugio Rossi', 'Capanna Isera', 'Rifugio Conti']);

    $component->call('sortBy', 'nome')->assertSet('sort', null)->assertSet('direction', null)
        ->call('sortBy', 'nome')->assertSet('direction', 'desc');

    expect(grHutsTableNames($component))->toBe(['Rifugio Rossi', 'Rifugio Conti', 'Capanna Isera', 'Bivacco Aronte']);
});

test('grouping by section adds a header per section, with the huts of the group first', function (): void {
    grHutsFixture();
    caiHut(['id_cai' => 6, 'branch_code' => '9500999', 'branch_name' => 'GR Toscana', 'name' => 'Punto GR']);
    $this->actingAs(grHutsCustomer());

    $component = Livewire::test(RegionalGroupHuts::class)
        ->assertDontSee('ms-gr-huts__group-name', escape: false)
        ->call('toggleGrouping')
        ->assertSet('group', '1');

    $html = grHutsHtml($component);
    preg_match_all('/class="ms-gr-huts__group-name">([^<]+)</', $html, $headers);

    expect(array_slice($headers[1], 0, 3))->toBe(['Intestati al GR Toscana', 'SEZ. LUCCA', 'SEZ. MASSA'])
        ->and($html)->toContain('2 rifugi')
        ->and($html)->toContain('1 rifugio')
        ->and(grHutsTableNames($component))->toBe(['Punto GR', 'Bivacco Aronte', 'Rifugio Rossi', 'Capanna Isera', 'Rifugio Conti']);

    $component->call('toggleGrouping')->assertSet('group', null);
});

test('a group with more than 40 huts is grouped by default, but not when filtered by section', function (): void {
    caiSection(['codice_cai' => '9226001', 'name' => 'SEZ. LUCCA', 'region' => 'TOSCANA']);
    caiSection(['codice_cai' => '9226002', 'name' => 'SEZ. MASSA', 'region' => 'TOSCANA']);

    foreach (range(1, 41) as $i) {
        caiHut(['branch_code' => $i % 2 === 0 ? '9226001' : '9226002', 'name' => 'Rifugio '.$i]);
    }

    $this->actingAs(grHutsCustomer());

    $component = Livewire::test(RegionalGroupHuts::class)->assertSee('ms-gr-huts__group-name', escape: false);

    $component->call('toggleGrouping')->assertSet('group', '0')->assertDontSee('ms-gr-huts__group-name', escape: false);

    $component->call('toggleGrouping')->assertSet('group', null)
        ->call('filterSection', '9226001')
        ->assertDontSee('ms-gr-huts__group-name', escape: false)
        ->assertDontSeeText('Raggruppa per Sezione');
});

test('on mobile only the first 12 huts are listed until "Mostra altri"', function (): void {
    caiSection(['codice_cai' => '9226001', 'name' => 'SEZ. LUCCA', 'region' => 'TOSCANA']);

    foreach (range(1, 15) as $i) {
        caiHut(['branch_code' => '9226001', 'name' => sprintf('Rifugio %02d', $i)]);
    }

    $this->actingAs(grHutsCustomer());

    $component = Livewire::test(RegionalGroupHuts::class)->assertSeeText('Mostra altri 3');

    expect(substr_count($component->html(), 'class="ms-gr-huts__card"'))->toBe(12);

    $component->set('showAll', true)->assertDontSeeText('Mostra altri');

    expect(substr_count($component->html(), 'class="ms-gr-huts__card"'))->toBe(15);
});

test('a group with one hut shows singular labels and no grouping switch', function (): void {
    caiSection(['codice_cai' => '9216001', 'name' => 'SEZ. FOGGIA', 'region' => 'PUGLIA']);
    caiSection(['codice_cai' => '9216002', 'name' => 'SEZ. BARI', 'region' => 'PUGLIA']);
    caiHut(['branch_code' => '9216001', 'name' => 'Rifugio Cornacchia']);
    $this->actingAs(grHutsCustomer(Region::Puglia));

    Livewire::test(RegionalGroupHuts::class)
        ->assertSeeText('Rifugio Cornacchia')
        ->assertSeeText('1 rifugio · 1 Sezione')
        ->assertSeeText('Nessuno')
        ->assertDontSeeText('Filtra elenco →')
        ->assertDontSeeText('Raggruppa per Sezione');
});

test('a group without huts shows the empty state, dashes and the open list of sections', function (): void {
    caiSection(['codice_cai' => '9226001', 'name' => 'SEZ. LUCCA', 'region' => 'TOSCANA']);
    caiSection(['codice_cai' => '9226002', 'name' => 'SEZ. MASSA', 'region' => 'TOSCANA']);
    $this->actingAs(grHutsCustomer());

    $html = grHutsHtml(Livewire::test(RegionalGroupHuts::class)
        ->assertSeeText('Nessun rifugio del GR Toscana nella banca dati rifugi.cai.it')
        ->assertSeeText('Vai a rifugi.cai.it ↗')
        ->assertSeeText('2 su 2')
        ->assertSeeText('SEZ. LUCCA'));

    expect($html)->toMatch('/Rifugi totali<\/span>\s*<span class="ms-gr-huts__kpi-value">—</')
        ->and($html)->toContain('x-data="{ open: true }"')
        ->and($html)->toContain('ms-gr-huts__body--hidden');
});

test('a group user without a region sees a notice and no error', function (): void {
    $this->actingAs(grHutsCustomer(null));

    Livewire::test(RegionalGroupHuts::class)
        ->assertSuccessful()
        ->assertSeeText('Nessuna regione associata al tuo account: contatta Montagna Servizi.')
        ->assertDontSeeText('Sezioni senza rifugi');
});

test('a hut opens its sheet from the group with the list parameters', function (): void {
    grHutsFixture();
    $this->actingAs(grHutsCustomer());

    $component = Livewire::withQueryParams(['tipo' => 'bivacco', 'ordina' => 'quota'])
        ->test(RegionalGroupHuts::class);

    expect($component->instance()->listQuery())->toBe(['tipo' => 'bivacco', 'ordina' => 'quota'])
        ->and(grHutsHtml($component))->toContain('href="'.e(CaiHutDetail::getUrl(['idCai' => 2, 'da' => 'gr', 'tipo' => 'bivacco', 'ordina' => 'quota'])).'"');
});

test('only a regional group customer can open the page, which is not in the navigation', function (?CustomerType $type): void {
    $this->actingAs(grHutsCustomer(Region::Toscana, $type))
        ->get(RegionalGroupHuts::getUrl())
        ->assertForbidden();

    expect(RegionalGroupHuts::shouldRegisterNavigation())->toBeFalse();
})->with([
    'Sezione' => [CustomerType::Sezione],
    'Organo Tecnico' => [CustomerType::OrganoTecnicoStrutturaOperativa],
    'nessun tipo' => [null],
]);

test('staff cannot open the page and a regional group customer can', function (): void {
    grHutsFixture();

    $this->actingAs(withRole(User::factory()->create(), UserRole::Developer))
        ->get(RegionalGroupHuts::getUrl())
        ->assertForbidden();

    $this->actingAs(grHutsCustomer())
        ->get(RegionalGroupHuts::getUrl())
        ->assertSuccessful()
        ->assertSeeText('Rifugi del GR')
        ->assertDontSeeText('Questa sezione è in arrivo.');
});
