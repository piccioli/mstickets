<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Enums\CaiTrailPracticability;
use App\Domain\CaiDirectory\Support\CaiMapLine;
use App\Domain\Identity\Enums\CustomerType;
use App\Domain\Identity\Enums\Region;
use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use App\Filament\Pages\CaiTrailDetail;
use App\Filament\Pages\RegionalGroupDashboard;
use App\Filament\Pages\RegionalGroupSicaiStages;
use App\Filament\Widgets\RegionalGroupSicaiStagesMap;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Filament::setCurrentPanel('admin');
    $this->seed(RolePermissionSeeder::class);
});

function grSicaiCustomer(?Region $region = Region::Piemonte, ?CustomerType $type = CustomerType::GruppoRegionale): User
{
    $customer = withRole(User::factory()->create(), UserRole::Customer);
    $customer->forceFill(['customer_type' => $type, 'region' => $region])->save();

    return $customer;
}

/** Testo della pagina senza tag né commenti Livewire, con gli spazi compattati. */
function grSicaiText(Testable $component): string
{
    $html = (string) preg_replace(['/<!--.*?-->/s', '/<script\b.*?<\/script>/s'], '', $component->html());

    return trim((string) preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5)));
}

/**
 * Codici delle tappe nell'ordine della tabella desktop (le card di mobile li ripetono dopo).
 *
 * @return list<string>
 */
function grSicaiListCodes(Testable $component): array
{
    $html = $component->html();
    $start = (int) strpos($html, 'aria-label="Tappe"');
    $table = substr($html, $start, (int) strpos($html, 'ms-gr-sicai__stage-cards') - $start);
    preg_match_all('/ms-gr-sicai__stage-row.*?ms-cai-trail-sign[^>]*>\s*([^<]*?)\s*</s', $table, $matches);

    return array_map('trim', $matches[1]);
}

/**
 * Piemonte di fixture: E01 (punto vicino), E02 non percorribile, la sua variante E02S, E03 con difficoltà e il dislivello
 * solo su E01; un rifugio collegato a rifugi.cai.it vicino a E01 ed E02, un hotel della regione lontano dalle tappe.
 * In Lombardia (D01) un punto della regione lombarda.
 *
 * @return array<string, int> codice → id del sentiero
 */
function grSicaiPageFixture(): array
{
    $e01 = grSicaiStage('E01', ['from_place' => 'Limone Piemonte', 'to_place' => 'Palanfrè', 'length_km' => 12.34, 'ascent' => 1150, 'descent' => 820, 'cai_scale' => 'EE']);
    $e02 = grSicaiStage('E02', ['from_place' => 'Palanfrè', 'to_place' => 'Entracque', 'length_km' => 10.0, 'practicability' => CaiTrailPracticability::NotPracticable, 'issues_status' => 'non percorribile']);
    $e02s = grSicaiStage('E02S', ['from_place' => 'Palanfrè', 'to_place' => 'Entracque', 'length_km' => 11.5]);
    $e03 = grSicaiStage('E03', ['from_place' => 'Entracque', 'to_place' => 'Terme di Valdieri', 'length_km' => 8.0, 'practicability' => CaiTrailPracticability::WithDifficulty]);
    $d01 = grSicaiStage('D01', ['from_place' => 'Maccagno', 'to_place' => 'Curiglia']);

    $hut = caiHut(['id_cai' => 921201004, 'name' => 'Rifugio Mondovì']);
    grSicaiRestPoint(1, 'Rifugio Mondovì', 'Piemonte', 'Rifugio', [$e01, $e02], [
        'municipality' => 'Roccaforte Mondovì', 'operator' => 'CAI Mondovì', 'phone' => '3483324202',
        'website' => 'https://www.rifugiomondovi.com/', 'cai_hut_id_cai' => $hut->id_cai,
    ]);
    grSicaiRestPoint(2, 'Albergo Alpi', 'Piemonte', 'Hotel', [], ['municipality' => 'Cuneo']);
    grSicaiRestPoint(3, 'Ostello Lombardo', 'Lombardia', 'Ostello', [$d01]);

    return ['E01' => $e01->cai_trail_id, 'E02' => $e02->cai_trail_id, 'E02S' => $e02s->cai_trail_id, 'E03' => $e03->cai_trail_id];
}

test('a regional group customer sees the summary, the stages and the rest points of the region', function (): void {
    $ids = grSicaiPageFixture();

    $component = Livewire::actingAs(grSicaiCustomer())->test(RegionalGroupSicaiStages::class)->assertSuccessful();
    $text = grSicaiText($component);

    expect($text)
        ->toContain('Tappe SICAI')
        ->toContain('GR Piemonte · Sentiero Italia CAI in Piemonte')
        // 4 tappe, 3 principali + 1 variante; km principali 12,34 + 10 + 8 = 30, variante 11,5 → 12.
        ->toContain('Tappe 4 3 tappe principali · 1 variante')
        ->toContain('30 km Tappe principali · più 12 km di varianti')
        ->toContain('1.150 m In salita, su 1 di 3 tappe · in discesa 820 m')
        ->toContain('Punti di appoggio 2 1 rifugio · 1 hotel')
        ->toContain('Tappe con criticità 18,0 km coinvolti')
        ->toContain('1 tappa non percorribile E02 Palanfrè → Entracque')
        ->toContain('1 tappa percorribile con difficoltà E03')
        // Elenco: codice, partenza → arrivo, km, salita, discesa, difficoltà, percorribilità, punti vicini.
        ->toContain('E01 Limone Piemonte → Palanfrè 12,3 1.150 m 820 m EE Percorribile 1')
        ->toContain('E02S Palanfrè → Entracque Variante della tappa E02 11,5 — — E Percorribile 0')
        ->toContain('4 tappe Le varianti (suffisso S, N…) seguono la tappa principale e non entrano nei totali del riepilogo.')
        // Punti per tipo, con i dati di contatto e le tappe vicine.
        ->toContain('Rifugi 1 Rifugio Mondovì Roccaforte Mondovì CAI Mondovì 3483324202 www.rifugiomondovi.com E01 E02 Vedi su rifugi.cai.it ↗')
        ->toContain('Hotel 1 Albergo Alpi Cuneo — — —')
        ->toContain('2 punti di appoggio · 1 nella banca dati rifugi.cai.it')
        ->not->toContain('Ostello Lombardo')
        ->not->toContain('Maccagno');

    expect(grSicaiListCodes($component))->toBe(['E01', 'E02', 'E02S', 'E03']);

    $component
        ->assertSeeHtml('href="'.CaiTrailDetail::getUrl(['trailId' => $ids['E01'], 'da' => 'sicai']).'"')
        ->assertSeeHtml('href="https://rifugi.cai.it/shelters/921201004"')
        ->assertSeeHtml('href="https://www.rifugiomondovi.com/"')
        ->assertSeeHtml('href="'.RegionalGroupDashboard::getUrl().'"');
});

test('the practicability filter and the search narrow the list, and the counts follow', function (): void {
    grSicaiPageFixture();

    $component = Livewire::actingAs(grSicaiCustomer())->test(RegionalGroupSicaiStages::class);

    $component->call('filterPracticability', 'non-percorribile')
        ->assertSet('practicability', 'non-percorribile');
    expect(grSicaiListCodes($component))->toBe(['E02'])
        ->and(grSicaiText($component))->toContain('1 di 4 tappe');

    $component->call('filterPracticability', 'sconosciuto');
    expect(grSicaiListCodes($component))->toBe([])
        ->and(grSicaiText($component))->toContain('Nessuna tappa «sconosciuto» in Piemonte. Azzera filtri');

    $component->call('resetFilters')->set('search', 'entracque');
    expect(grSicaiListCodes($component))->toBe(['E02', 'E02S', 'E03']);

    $component->set('search', 'e0');
    expect(grSicaiListCodes($component))->toBe(['E01', 'E02', 'E02S', 'E03']);

    $component->set('search', 'zzz');
    expect(grSicaiText($component))->toContain('Nessuna tappa corrisponde alla ricerca o al filtro.');

    // Valore sconosciuto nell'URL = nessun filtro.
    $component->set('search', '')->set('practicability', 'boh');
    expect(grSicaiListCodes($component))->toBe(['E01', 'E02', 'E02S', 'E03']);
});

test('the rest point type filter shows only that group', function (): void {
    grSicaiPageFixture();

    $component = Livewire::actingAs(grSicaiCustomer())->test(RegionalGroupSicaiStages::class)
        ->call('filterPointType', 'hotel')
        ->assertSet('pointType', 'hotel');

    expect(grSicaiText($component))->toContain('Albergo Alpi')->not->toContain('Rifugio Mondovì Roccaforte');

    $component->call('filterPointType', null);
    expect(grSicaiText($component))->toContain('Albergo Alpi')->toContain('Rifugio Mondovì Roccaforte');
});

test('Toscana and Emilia-Romagna see the same L stages', function (): void {
    grSicaiStage('L01', ['from_place' => 'Passo dei Due Santi']);
    grSicaiStage('L02', ['from_place' => 'Passo del Cerreto']);

    foreach ([Region::Toscana, Region::EmiliaRomagna] as $region) {
        $component = Livewire::actingAs(grSicaiCustomer($region))->test(RegionalGroupSicaiStages::class);

        expect(grSicaiListCodes($component))->toBe(['L01', 'L02']);
    }
});

test('a region with few stages and no rest points shows the empty state', function (): void {
    grSicaiStage('T01', ['from_place' => 'Lagonegro', 'to_place' => 'Nemoli']);
    grSicaiStage('T02', ['from_place' => 'Nemoli', 'to_place' => 'Rotonda']);

    $text = grSicaiText(Livewire::actingAs(grSicaiCustomer(Region::Basilicata))->test(RegionalGroupSicaiStages::class));

    expect($text)
        ->toContain('Tappe 2 2 tappe principali, nessuna variante')
        ->toContain('Punti di appoggio 0 Nessun punto di appoggio censito nella regione')
        ->toContain('Nessuna tappa non percorribile o con difficoltà.')
        ->toContain('Dislivello + — Dato non disponibile in osm2cai per queste tappe')
        ->toContain('Nessun punto di appoggio SICAI censito in Basilicata')
        ->toContain('Nessuna struttura risulta associata alle 2 tappe della regione.');
});

test('a regional group without a region sees the notice', function (): void {
    grSicaiPageFixture();

    Livewire::actingAs(grSicaiCustomer(null))->test(RegionalGroupSicaiStages::class)
        ->assertSuccessful()
        ->assertSee('Nessuna regione associata al tuo account: contatta Montagna Servizi.')
        ->assertDontSee('Elenco delle tappe');
});

test('mobile shows the first stages and points, then all of them on request', function (): void {
    foreach (range(1, 12) as $number) {
        grSicaiStage(sprintf('E%02d', $number));
    }

    $component = Livewire::actingAs(grSicaiCustomer())->test(RegionalGroupSicaiStages::class)
        ->assertSee('Mostra tutte le 12 tappe');

    expect(substr_count($component->html(), 'ms-gr-sicai__stage-card-route'))->toBe(RegionalGroupSicaiStages::MOBILE_STAGES);

    $component->set('showAllStages', true)->assertDontSee('Mostra tutte le 12 tappe');
    expect(substr_count($component->html(), 'ms-gr-sicai__stage-card-route'))->toBe(12);
});

test('the map widget draws the stages with alternating colours and the rest points by type', function (): void {
    $ids = grSicaiPageFixture();

    $html = Livewire::actingAs(grSicaiCustomer())->test(RegionalGroupSicaiStagesMap::class)->html();
    preg_match('/data-cai-map-payload="([^"]*)"/', $html, $match);
    $payload = json_decode(html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5), true);
    $lines = collect($payload['lines'])->keyBy('id');

    expect($lines->keys()->all())->toBe(array_values($ids))
        ->and($lines[$ids['E01']])->toMatchArray(['color' => CaiMapLine::SICAI_COLOR, 'label' => 'E01'])
        ->and($lines[$ids['E01']])->not->toHaveKey('dash')
        ->and($lines[$ids['E02']])->toMatchArray(['color' => '#d64545', 'dash' => CaiMapLine::SICAI_NOT_PRACTICABLE_DASH])
        ->and($lines[$ids['E02S']])->toMatchArray(['color' => CaiMapLine::SICAI_COLOR, 'dash' => CaiMapLine::SICAI_VARIANT_DASH])
        ->and($lines[$ids['E03']])->toMatchArray(['color' => '#e0a526'])
        ->and($lines[$ids['E01']]['popupHtml'])->toContain('Limone Piemonte → Palanfrè')->toContain('12,3 km · +1.150 m')
        ->toContain(e(CaiTrailDetail::getUrl(['trailId' => $ids['E01'], 'da' => 'sicai'])))
        ->and($lines[$ids['E02S']]['popupHtml'])->toContain('Variante di E02');

    $markers = collect($payload['markers'])->keyBy('id');

    expect($markers->keys()->all())->toBe(['punto-1', 'punto-2'])
        ->and($markers['punto-1'])->toMatchArray(['color' => '#0f7d63', 'letter' => 'R'])
        ->and($markers['punto-1']['icon'])->toStartWith('M3 11')
        ->and($markers['punto-1']['popupHtml'])->toContain('Roccaforte Mondovì · tappe E01, E02')
        ->toContain('https://rifugi.cai.it/shelters/921201004')
        ->toContain('#'.RegionalGroupSicaiStages::POINTS_ANCHOR)
        ->and($markers['punto-2']['popupHtml'])->toContain('Cuneo · tappe —')->not->toContain('rifugi.cai.it');

    expect(grSicaiText(Livewire::actingAs(grSicaiCustomer())->test(RegionalGroupSicaiStagesMap::class)))
        ->toContain('4 tappe · 2 punti di appoggio · clic su una tappa per l’anteprima')
        ->toContain('Tracciato Tappe, a colori alternati Percorribile con difficoltà Non percorribile Variante')
        ->toContain('Punti di appoggio Rifugi Hotel');
});

test('main stages alternate the two greens on the map, variants excluded', function (): void {
    $ids = [];

    foreach (['E01', 'E01S', 'E02', 'E03'] as $code) {
        $ids[$code] = grSicaiStage($code)->cai_trail_id;
    }

    $html = Livewire::actingAs(grSicaiCustomer())->test(RegionalGroupSicaiStagesMap::class)->html();
    preg_match('/data-cai-map-payload="([^"]*)"/', $html, $match);
    $colors = collect(json_decode(html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5), true)['lines'])->pluck('color', 'id');

    expect($colors[$ids['E01']])->toBe(CaiMapLine::SICAI_COLOR)
        ->and($colors[$ids['E02']])->toBe(CaiMapLine::SICAI_ALTERNATE_COLOR)
        ->and($colors[$ids['E03']])->toBe(CaiMapLine::SICAI_COLOR);
});

test('only regional group customers can open the page and the map', function (?CustomerType $type, UserRole $role): void {
    $user = withRole(User::factory()->create(), $role);
    $user->forceFill(['customer_type' => $type, 'region' => Region::Piemonte])->save();

    $this->actingAs($user)->get(RegionalGroupSicaiStages::getUrl())->assertForbidden();
    expect(RegionalGroupSicaiStagesMap::canView())->toBeFalse();
})->with([
    'Sezione' => [CustomerType::Sezione, UserRole::Customer],
    'nessun tipo' => [null, UserRole::Customer],
    'staff' => [null, UserRole::Developer],
]);

test('the page answers over HTTP at its slug and stays out of the navigation', function (): void {
    grSicaiPageFixture();

    expect(RegionalGroupSicaiStages::getUrl())->toEndWith('/gr/tappe-sicai')
        ->and(RegionalGroupSicaiStages::shouldRegisterNavigation())->toBeFalse();

    $this->actingAs(grSicaiCustomer())->get(RegionalGroupSicaiStages::getUrl())
        ->assertSuccessful()
        ->assertSee('Elenco delle tappe')
        ->assertSee('data-cai-map="gr-sicai"', escape: false);
});
