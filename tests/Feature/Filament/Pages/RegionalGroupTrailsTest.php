<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Enums\CaiTrailStatus;
use App\Domain\CaiDirectory\Models\CaiTrail;
use App\Domain\Identity\Enums\CustomerType;
use App\Domain\Identity\Enums\Region;
use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use App\Filament\Pages\CaiTrailDetail;
use App\Filament\Pages\RegionalGroupDashboard;
use App\Filament\Pages\RegionalGroupSectionTrails;
use App\Filament\Pages\RegionalGroupTrails;
use App\Filament\Widgets\RegionalGroupTrailsMap;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Filament::setCurrentPanel('admin');
    $this->seed(RolePermissionSeeder::class);
});

function grTrailsCustomer(?Region $region = Region::Lombardia, ?CustomerType $type = CustomerType::GruppoRegionale): User
{
    $customer = withRole(User::factory()->create(['name' => 'GR Lombardia']), UserRole::Customer);
    $customer->forceFill(['customer_type' => $type, 'region' => $region])->save();

    return $customer;
}

/** Testo della pagina senza tag né commenti Livewire, con gli spazi compattati. */
function grTrailsText(Testable $component): string
{
    $html = (string) preg_replace(['/<!--.*?-->/s', '/<script\b.*?<\/script>/s'], '', $component->html());

    return trim((string) preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5)));
}

/**
 * Numeri dei sentieri nell'ordine della tabella desktop dell'elenco (le card di mobile li ripetono dopo).
 *
 * @return list<string>
 */
function grTrailsListNumbers(Testable $component): array
{
    $html = $component->html();
    $start = (int) strpos($html, 'ms-gr-trails__trails');
    $table = substr($html, $start, (int) strpos($html, 'ms-gr-trails__trail-cards') - $start);
    preg_match_all('/ms-gr-trails__trail-row".*?ms-cai-trail-sign[^>]*>\s*([^<]*?)\s*</s', $table, $matches);

    return array_map('trim', $matches[1]);
}

test('the page shows the two totals, the present trails, the status bar and the practicability', function (): void {
    grTrailsFixture();

    $component = Livewire::actingAs(grTrailsCustomer())->test(RegionalGroupTrails::class)->assertSuccessful();
    $text = grTrailsText($component);

    // Km nel territorio: 3,2 + 5,5 + 6,0 + 16,0 = 30,7 → 31; gestiti dalle Sezioni del GR: 10 + 6 + 20 = 36 (il "5" è al 30%
    // in regione ma è della Sezione di Bergamo: conta fra i gestiti, non fra i presenti).
    expect($text)
        ->toContain('Sentieri del GR')
        ->toContain('GR Lombardia · Codice CAI 9300000')
        ->toContain('Km nel territorio regionale 31 km Tutti i sentieri del catasto tagliati sul confine della regione, compresi quelli senza una Sezione.')
        ->toContain('Km gestiti dalle Sezioni del GR 36 km Sentieri assegnati ad almeno una Sezione. Un sentiero condiviso è contato una sola volta.')
        ->toContain('117% dei km nel territorio')
        ->toContain('· 4 km senza Sezione')
        ->toContain('Sentieri presenti')
        ->toContain('1 senza Sezione')
        ->toContain('1 condivisi tra più Sezioni')
        ->toContain('3 province · 4 settori')
        // Consolidati: (6 + 16) / 30,7 = 72%.
        ->toContain('Sentieri consolidati (stati 3–4): 72% · 22 km')
        ->toContain('4 · Validato 16 km 52% · 1 sentiero')
        ->toContain('Percorribile con difficoltà 4 km 1 sentiero')
        ->toContain('Sconosciuto 8 km 1 sentiero');
});

test('the province table lists provinces, flags empty sectors and opens the sectors of a province', function (): void {
    grTrailsFixture();

    $component = Livewire::actingAs(grTrailsCustomer())->test(RegionalGroupTrails::class);
    $text = grTrailsText($component);

    expect($text)
        ->toContain('Bergamo 2 1 senza sentieri 2')
        ->toContain('Cremona 1 1 senza sentieri 0')
        ->toContain('Totale regione 4 4')
        ->not->toContain('Settori della provincia di');

    $component->call('toggleProvince', 10)->assertSet('openProvince', '10');
    $text = grTrailsText($component);

    expect($text)
        ->toContain('Settori della provincia di Bergamo')
        ->toContain('1 settore senza sentieri censiti')
        ->toContain('LBG01 Val Brembana 2 22,8')
        ->toContain('LBG02 Pianura 0 0 Nessun sentiero censito')
        ->not->toContain('Settori della provincia di Brescia');

    expect($component->html())->toContain('settore-100');

    $component->call('toggleProvince', 10)->assertSet('openProvince', null);
    $component->call('toggleProvince', 999)->assertSet('openProvince', null);
});

test('the open province is read from the URL', function (): void {
    grTrailsFixture();

    $component = Livewire::withQueryParams(['aperta' => '11'])->actingAs(grTrailsCustomer())->test(RegionalGroupTrails::class);

    expect(grTrailsText($component))->toContain('Settori della provincia di Brescia')->toContain('LBS01 Val Camonica');
});

test('the section table counts a shared trail for each section and explains the sum', function (): void {
    grTrailsFixture();

    $component = Livewire::actingAs(grTrailsCustomer())->test(RegionalGroupTrails::class);
    $text = grTrailsText($component);

    // Milano 10 + 6, Bergamo 6 + 20: 42 km contro i 36 gestiti, il "2" condiviso conta due volte.
    expect($text)
        ->toContain('3 Sezioni del GR · 1 senza sentieri assegnati')
        ->toContain('la somma dei km delle Sezioni (42 km) supera i Km gestiti dalle Sezioni del GR (36 km)')
        ->toContain('la differenza di 6 km viene dai sentieri condivisi')
        ->toContain('CAI Bergamo 2 1 26')
        ->toContain('CAI Milano 2 1 16')
        ->toContain('CAI Vuota Nessun sentiero assegnato 0 —')
        ->toContain('Somma delle Sezioni · supera i km gestiti dal GR di 6 km 4 2 42');

    // Ordine di default: km decrescenti.
    expect(mb_strpos($text, 'CAI Bergamo 2'))->toBeLessThan(mb_strpos($text, 'CAI Milano 2'));

    expect($component->html())->toContain(RegionalGroupSectionTrails::getUrl(['codice' => '9300001']));

    $component->call('filterSections', RegionalGroupTrails::SECTIONS_WITHOUT_TRAILS);
    expect(grTrailsText($component))->toContain('CAI Vuota Nessun sentiero assegnato')->not->toContain('CAI Milano 2');

    $component->call('filterSections', RegionalGroupTrails::SECTIONS_ALL)->set('sectionSearch', 'mil');
    expect(grTrailsText($component))->toContain('CAI Milano 2')->not->toContain('CAI Bergamo 2');

    $component->set('sectionSearch', 'zzz');
    expect(grTrailsText($component))->toContain('Nessuna Sezione corrisponde alla ricerca.');

    $component->set('sectionSearch', '')->call('sortSections', 'nome');
    $text = grTrailsText($component);
    expect(mb_strpos($text, 'CAI Bergamo 2'))->toBeLessThan(mb_strpos($text, 'CAI Milano 2'))
        ->and(mb_strpos($text, 'CAI Milano 2'))->toBeLessThan(mb_strpos($text, 'CAI Vuota'));
});

test('the list shows the present trails in natural order with province, sector and sections', function (): void {
    grTrailsFixture();

    $component = Livewire::actingAs(grTrailsCustomer())->test(RegionalGroupTrails::class);

    // "5" (30%) e il sentiero senza numero (49,99%) non sono presenti.
    expect(grTrailsListNumbers($component))->toBe(['2', '2A', '10', 'E1']);

    expect(grTrailsText($component))
        ->toContain('4 sentieri · 28 km')
        ->toContain('Sentiero dei Laghi Rifugio A → Passo B Bergamo LBG01 · Val Brembana CAI Bergamo Condiviso con CAI Milano 6,0')
        ->toContain('— Passo Gavia → Passo B Brescia LBS01 · Val Camonica Nessuna Sezione 4,0');

    $component->call('sortBy', 'km');
    expect(grTrailsListNumbers($component))->toBe(['10', 'E1', '2', '2A'])
        ->and($component->get('sort'))->toBe('km-desc');
});

test('filters and search narrow the list in SQL and the reset clears them', function (): void {
    grTrailsFixture();

    $component = Livewire::actingAs(grTrailsCustomer())->test(RegionalGroupTrails::class);

    $component->call('filterProvince', '11');
    expect(grTrailsListNumbers($component))->toBe(['2A', 'E1']);
    expect(grTrailsText($component))->toContain('2 di 4 sentieri · 12 km');

    // Il settore imposta anche la provincia; E1 è solo al 40% nel settore 102.
    $component->call('filterProvince', null)->call('filterSector', '102')->assertSet('province', '11');
    expect(grTrailsListNumbers($component))->toBe(['2A']);

    // Una provincia diversa toglie il settore.
    $component->call('filterProvince', '10')->assertSet('sector', null);
    expect(grTrailsListNumbers($component))->toBe(['2', '10']);

    $component->call('resetFilters')->call('filterSection', 'nessuna');
    expect(grTrailsListNumbers($component))->toBe(['2A']);

    $component->call('filterSection', '9300002');
    expect(grTrailsListNumbers($component))->toBe(['2']);

    $component->call('filterSection', '9230001')->assertSet('section', null);

    $component->call('filterStatus', (string) CaiTrailStatus::Validated->value);
    expect(grTrailsListNumbers($component))->toBe(['10']);

    $component->call('resetFilters')->call('filterPracticability', 'sconosciuto');
    expect(grTrailsListNumbers($component))->toBe(['E1']);

    $component->call('resetFilters')->set('search', 'laghi');
    expect(grTrailsListNumbers($component))->toBe(['2']);

    $component->set('search', 'gavia');
    expect(grTrailsListNumbers($component))->toBe(['2A']);

    $component->set('search', 'nessuno');
    expect(grTrailsText($component))->toContain('Nessun sentiero corrisponde alla ricerca o ai filtri selezionati.')
        ->toContain('Azzera filtri');

    $component->call('resetFilters')->assertSet('search', '');
    expect(grTrailsListNumbers($component))->toBe(['2', '2A', '10', 'E1']);
});

test('the list of a sector opens from the map or the sector table', function (): void {
    grTrailsFixture();

    $component = Livewire::actingAs(grTrailsCustomer())->test(RegionalGroupTrails::class);

    $component->call('listSector', 'settore-100')->assertSet('province', '10')->assertSet('sector', '100');
    expect(grTrailsListNumbers($component))->toBe(['2', '10']);

    $component->call('listSector', 'settore-999')->assertSet('sector', null);
});

test('search, filters, sort and page are read from the URL and unknown values count as absent', function (): void {
    grTrailsFixture();

    $component = Livewire::withQueryParams([
        'provincia' => '11', 'stato' => '1', 'ordina' => 'km-desc', 'cerca' => 'gavia', 'sezione' => 'nessuna',
    ])->actingAs(grTrailsCustomer())->test(RegionalGroupTrails::class);

    expect(grTrailsListNumbers($component))->toBe(['2A']);

    $component = Livewire::withQueryParams([
        'provincia' => '20', 'settore' => 'x', 'stato' => '9', 'percorribilita' => 'boh', 'sezione' => '9230001', 'ordina' => '?', 'pagina' => '7',
    ])->actingAs(grTrailsCustomer())->test(RegionalGroupTrails::class);

    expect(grTrailsListNumbers($component))->toBe(['2', '2A', '10', 'E1'])
        ->and($component->instance()->listQuery())->toBe([]);
});

test('the list is paginated in SQL with 25 rows and the page links keep the filters', function (): void {
    grTrailsFixture();

    for ($i = 1; $i <= 26; $i++) {
        $trail = caiTrail(['ref' => (string) (100 + $i), 'length_km' => 1]);
        DB::table('cai_trail_territory')->insert(['cai_trail_id' => $trail->id, 'region_id' => 1, 'province_id' => null, 'sector_id' => null, 'percentage' => 100]);
    }

    $component = Livewire::actingAs(grTrailsCustomer())->test(RegionalGroupTrails::class);

    expect(grTrailsListNumbers($component))->toHaveCount(25);
    expect(grTrailsText($component))->toContain('Pagina 1 di 2');

    $component->call('goToPage', 2)->assertSet('listPage', '2');
    // 2, 2A, 10, 101…126, E1: la seconda pagina ha gli ultimi cinque.
    expect(grTrailsListNumbers($component))->toBe(['123', '124', '125', '126', 'E1'])
        ->and(grTrailsText($component))->toContain('Pagina 2 di 2');

    $component->call('goToPage', 9)->assertSet('listPage', '2');
    $component->call('goToPage', 1)->assertSet('listPage', null);

    // Un filtro riporta alla prima pagina.
    $component->call('goToPage', 2)->call('filterStatus', '4')->assertSet('listPage', null);
});

test('a trail opens its card with da=gr and the list parameters, which the back link brings back', function (): void {
    grTrailsFixture();
    $user = grTrailsCustomer();
    $trail = CaiTrail::query()->where('ref', '2A')->firstOrFail();

    $component = Livewire::withQueryParams(['provincia' => '11', 'cerca' => 'gavia'])->actingAs($user)->test(RegionalGroupTrails::class);
    $url = CaiTrailDetail::getUrl(['trailId' => $trail->id, 'da' => 'gr', 'cerca' => 'gavia', 'provincia' => '11']);

    expect($component->html())->toContain(e($url));

    $this->actingAs($user)
        ->get($url)
        ->assertSuccessful()
        ->assertSee(e(RegionalGroupTrails::getUrl(['cerca' => 'gavia', 'provincia' => '11'])), escape: false);
});

test('the page renders the sector map as a child component that list updates do not resend', function (): void {
    grTrailsFixture();

    $component = Livewire::actingAs(grTrailsCustomer())->test(RegionalGroupTrails::class);

    expect($component->html())
        ->toContain('data-cai-map="gr-trails"')
        ->toContain(e(route('cai-trail-regions.map-trails', ['caiTrailRegion' => 1])));

    $component->call('filterStatus', '4');

    expect($component->html())->not->toContain('data-cai-map-payload');

    Livewire::actingAs(grTrailsCustomer())->test(RegionalGroupTrailsMap::class)
        ->assertSee('Settori colorati per km consolidati');
});

test('a small region shows its own numbers', function (): void {
    grTrailsFixture();

    $component = Livewire::actingAs(grTrailsCustomer(Region::Molise))->test(RegionalGroupTrails::class);

    expect(grTrailsText($component))
        ->toContain('GR Molise · Codice CAI 9720000')
        ->toContain('Km nel territorio regionale 7 km')
        ->toContain('Km gestiti dalle Sezioni del GR 7 km')
        ->toContain('1 provincia · 1 settore')
        ->toContain('CAI Campobasso 1 0 7')
        ->toContain('Nessun sentiero è condiviso fra le Sezioni del GR');
    expect(grTrailsListNumbers($component))->toBe(['M1']);
});

test('a group without region or without osm2cai data sees a notice and no error', function (): void {
    grTrailsFixture();

    Livewire::actingAs(grTrailsCustomer(null))->test(RegionalGroupTrails::class)
        ->assertSuccessful()
        ->assertSee('Nessuna regione associata al tuo account: contatta Montagna Servizi.')
        ->assertDontSee('Per provincia');

    Livewire::actingAs(grTrailsCustomer(Region::Toscana))->test(RegionalGroupTrails::class)
        ->assertSuccessful()
        ->assertSee('Nessun dato del catasto osm2cai per la regione del GR Toscana')
        ->assertDontSee('Elenco sentieri del GR');
});

test('only a regional group customer can open the page, at the slug of the coming soon page', function (?CustomerType $type): void {
    expect(RegionalGroupTrails::getUrl())->toEndWith('/gr/sentieri');

    $this->actingAs(grTrailsCustomer(Region::Lombardia, $type))->get(RegionalGroupTrails::getUrl())->assertForbidden();
})->with([
    'Sezione' => [CustomerType::Sezione],
    'senza tipo' => [null],
]);

test('staff cannot open the page and the dashboard badge links to it', function (): void {
    grTrailsFixture();

    $this->actingAs(withRole(User::factory()->create(), UserRole::Developer))->get(RegionalGroupTrails::getUrl())->assertForbidden();

    $this->actingAs(grTrailsCustomer())->get(RegionalGroupTrails::getUrl())
        ->assertSuccessful()
        ->assertSee('Riepilogo regionale')
        ->assertSee(RegionalGroupDashboard::getUrl(), escape: false);
});

test('the section of the group opens its trails seen from the group, with the back link to the group page', function (): void {
    grTrailsFixture();
    $user = grTrailsCustomer();
    $two = CaiTrail::query()->where('ref', '2')->firstOrFail();

    $component = Livewire::actingAs($user)
        ->test(RegionalGroupSectionTrails::class, ['codice' => '9300001'])
        ->assertSuccessful();
    $text = grTrailsText($component);

    expect($text)
        ->toContain('← Sentieri del GR')
        ->toContain('Sentieri di Sezione')
        ->toContain('SEZ. MILANO')
        ->toContain('Sentiero delle Orobie')
        ->toContain('Sentiero dei Laghi')
        ->toContain('Condiviso con CAI Bergamo')
        ->not->toContain('Alta Via');

    $trailUrl = CaiTrailDetail::getUrl(['trailId' => $two->id, 'da' => 'sezione-gr', 'codice' => '9300001']);
    expect($component->html())->toContain(e($trailUrl))->toContain(e(RegionalGroupTrails::getUrl()));

    // Filtri come nella 10.6.
    $component->call('toggleStatus', CaiTrailStatus::Validated->value);
    expect(grTrailsText($component))->toContain('Sentiero delle Orobie')->not->toContain('Sentiero dei Laghi Rifugio');

    $this->actingAs($user)->get(RegionalGroupSectionTrails::getUrl(['codice' => '9300001']))->assertSuccessful();
    $this->actingAs($user)->get($trailUrl)
        ->assertSuccessful()
        ->assertSee(e(RegionalGroupSectionTrails::getUrl(['codice' => '9300001'])), escape: false);
});

test('the section page of another group, of a missing section or for a non-group user is refused', function (): void {
    grTrailsFixture();

    $this->actingAs(grTrailsCustomer())->get(RegionalGroupSectionTrails::getUrl(['codice' => '9230001']))->assertForbidden();
    $this->actingAs(grTrailsCustomer(Region::Molise))->get(RegionalGroupSectionTrails::getUrl(['codice' => '9300001']))->assertForbidden();
    $this->actingAs(grTrailsCustomer(null))->get(RegionalGroupSectionTrails::getUrl(['codice' => '9300001']))->assertForbidden();
    $this->actingAs(grTrailsCustomer())->get(RegionalGroupSectionTrails::getUrl(['codice' => '9300999']))->assertNotFound();
    $this->actingAs(grTrailsCustomer(Region::Lombardia, CustomerType::Sezione))->get(RegionalGroupSectionTrails::getUrl(['codice' => '9300001']))->assertForbidden();
    $this->actingAs(withRole(User::factory()->create(), UserRole::Developer))->get(RegionalGroupSectionTrails::getUrl(['codice' => '9300001']))->assertForbidden();
});
