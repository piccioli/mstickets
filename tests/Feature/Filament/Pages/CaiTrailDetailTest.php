<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Enums\CaiTrailPracticability;
use App\Domain\CaiDirectory\Enums\CaiTrailStatus;
use App\Domain\Identity\Enums\CustomerType;
use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Enums\Region;
use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use App\Filament\Pages\CaiTrailDetail;
use App\Filament\Pages\RegionalGroupTrails;
use App\Filament\Pages\SectionTrails;
use App\Filament\Resources\CaiSections\CaiSectionResource;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Filament::setCurrentPanel('admin');
    $this->seed(RolePermissionSeeder::class);

    $this->owner = trailDetailCustomer(CustomerType::Sezione);
    caiSection(['codice_cai' => '9226019', 'name' => 'SEZ. LUCCA', 'region' => 'TOSCANA', 'user_id' => $this->owner->id]);
    caiSection(['codice_cai' => '9226005', 'name' => 'SEZ. CARRARA', 'region' => 'TOSCANA']);
    caiSection(['codice_cai' => '9224001', 'name' => 'SEZ. PARMA', 'region' => 'EMILIA-ROMAGNA']);

    $this->brenta = caiTrail([
        'id' => 1001, 'osmid' => 4455667, 'ref' => '151C', 'name' => 'Sentiero della Val Brenta Alta',
        'from_place' => 'Rifugio Brentei', 'to_place' => 'Bocca di Brenta', 'cai_scale' => 'EE', 'length_km' => 3.4,
        'ascent' => 1620, 'descent' => 40, 'status' => CaiTrailStatus::Validated,
        'practicability' => CaiTrailPracticability::Practicable, 'issues_last_update' => '2026-09-18',
        'validation_date' => '2025-06-12',
        'geometry' => ['type' => 'MultiLineString', 'coordinates' => [
            [[10.865, 46.186], [10.87, 46.19]],
            [[10.87, 46.19], [10.88, 46.2]],
        ]],
    ], ['9226019']);

    $this->mocheni = caiTrail([
        'id' => 1002, 'ref' => '322B', 'name' => null, 'from_place' => 'Palù del Fersina', 'to_place' => null,
        'cai_scale' => null, 'length_km' => 5.8, 'ascent' => 760, 'descent' => null, 'status' => CaiTrailStatus::Reconnaissance,
        'issues_status' => 'non percorribile', 'practicability' => CaiTrailPracticability::NotPracticable,
        'issues_last_update' => '2026-10-04', 'validation_date' => null,
    ], ['9226019']);

    $this->pasubio = caiTrail([
        'id' => 1003, 'ref' => '601', 'name' => 'Strada delle 52 Gallerie',
        'practicability' => CaiTrailPracticability::WithDifficulty, 'issues_last_update' => null,
    ], ['9226019', '9226005', '9224001', '9999999']);

    $this->parma = caiTrail(['id' => 1004, 'ref' => '700E', 'name' => 'Sentiero di Parma'], ['9224001']);
});

function trailDetailCustomer(CustomerType $type, ?Region $region = null): User
{
    $user = withRole(User::factory()->create(), UserRole::Customer);
    $user->forceFill(['customer_type' => $type, 'region' => $region])->save();

    return $user;
}

/** HTML della pagina senza i commenti `<!--[if BLOCK]>` che Livewire mette attorno a ogni `@if`. */
function trailDetailHtml(Testable $component): string
{
    return (string) preg_replace('/<!--.*?-->/s', '', $component->html());
}

/** @return array<string, mixed> attributo JSON della mappa (`data-cai-map-payload` o `data-cai-map-config`) */
function trailDetailMap(string $html, string $attribute): array
{
    preg_match('/'.$attribute.'="([^"]*)"/', $html, $matches);

    expect($matches)->toHaveKey(1);

    return json_decode(html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5), true, flags: JSON_THROW_ON_ERROR);
}

test('the section sees header, data, managing sections and source links of its trail', function (): void {
    $this->actingAs($this->owner);

    $html = trailDetailHtml(Livewire::test(CaiTrailDetail::class, ['trailId' => '1001'])->assertSuccessful());
    $text = (string) preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($html)));

    expect($html)->toContain('>151C</span>')
        ->toContain('ms-cai-trail-sign--xl')
        ->toContain('href="https://osm2cai.cai.it/hiking-route/id/1001"')
        ->toContain('href="https://www.openstreetmap.org/relation/4455667"')
        ->toContain('--ms-cai-trail-status-color: #2563c9')
        ->not->toContain('ms-trail__alert')
        ->not->toContain('ms-trail__shared');

    expect($text)->toContain('Sentiero 151C')
        ->toContain('Sentiero della Val Brenta Alta')
        ->toContain('Rifugio Brentei → Bocca di Brenta')
        ->toContain('Accatastamento: 4 · Validato')
        ->toContain('Percorribile')
        ->toContain('aggiornato il 18/09/2026')
        ->toContain('Lunghezza 3,4 km')
        ->toContain('Difficoltà CAI EE')
        ->toContain('Dislivello in salita +1.620 m')
        ->toContain('Dislivello in discesa −40 m')
        ->toContain('Stato di accatastamento 4 · Validato')
        ->toContain('Data di validazione 12/06/2025')
        ->toContain('Percorribilità aggiornata il 18/09/2026')
        ->toContain('Partenza Rifugio Brentei')
        ->toContain('Arrivo Bocca di Brenta')
        ->toContain('Sezioni che lo gestiscono 1')
        ->toContain('SEZ. LUCCA Questa Sezione')
        ->toContain('Vedi su osm2cai ↗')
        ->toContain('Vedi su OpenStreetMap ↗');
});

test('the map draws the selected track with start and end markers, zoomed up to 15', function (): void {
    $this->actingAs($this->owner);

    $html = trailDetailHtml(Livewire::test(CaiTrailDetail::class, ['trailId' => '1001']));
    $payload = trailDetailMap($html, 'data-cai-map-payload');

    expect(trailDetailMap($html, 'data-cai-map-config'))->toMatchArray(['name' => 'trail', 'selected' => 1001, 'fitMaxZoom' => 15])
        ->and($payload['lines'])->toHaveCount(1)
        ->and($payload['lines'][0])->toMatchArray(['id' => 1001, 'color' => '#2563c9', 'weight' => 5, 'popupHtml' => null])
        ->and($payload['markers'])->toHaveCount(2)
        ->and($payload['markers'][0])->toMatchArray(['id' => 'start', 'lat' => 46.186, 'lng' => 10.865, 'letter' => 'A', 'label' => 'Partenza: Rifugio Brentei'])
        ->and($payload['markers'][1])->toMatchArray(['id' => 'end', 'lat' => 46.2, 'lng' => 10.88, 'letter' => 'B', 'label' => 'Arrivo: Bocca di Brenta']);
});

test('the endpoints are read from line strings, multi line strings and features', function (): void {
    expect(CaiTrailDetail::endpoints(['type' => 'LineString', 'coordinates' => [[1, 2], [3, 4], [5, 6]]]))
        ->toBe([[1.0, 2.0], [5.0, 6.0]])
        ->and(CaiTrailDetail::endpoints(['type' => 'Feature', 'geometry' => ['type' => 'MultiLineString', 'coordinates' => [[[1, 2], [3, 4]], [[7, 8]]]]]))
        ->toBe([[1.0, 2.0], [7.0, 8.0]])
        ->and(CaiTrailDetail::endpoints(null))->toBeNull()
        ->and(CaiTrailDetail::endpoints(['type' => 'Point', 'coordinates' => [1, 2]]))->toBeNull()
        ->and(CaiTrailDetail::endpoints(['type' => 'LineString', 'coordinates' => []]))->toBeNull();
});

test('a trail without geometry shows an empty map without markers', function (): void {
    $this->brenta->update(['geometry' => null]);
    $this->actingAs($this->owner);

    $payload = trailDetailMap(trailDetailHtml(Livewire::test(CaiTrailDetail::class, ['trailId' => '1001'])), 'data-cai-map-payload');

    expect($payload)->toBe(['markers' => [], 'lines' => []]);
});

test('a trail that is not practicable shows the red alert and dashes for missing data', function (): void {
    $this->actingAs($this->owner);

    $html = trailDetailHtml(Livewire::test(CaiTrailDetail::class, ['trailId' => '1002'])->assertSuccessful());
    $text = (string) preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($html)));

    expect($html)->toContain('class="ms-trail__alert" role="status" style="background: #fbe7e5; color: #b42318; border-color: #f3c4bf"');
    expect($text)->toContain('Sentiero non percorribile')
        ->toContain('Stato di percorribilità aggiornato il 04/10/2026 · fonte osm2cai')
        ->toContain('Sentiero senza nome')
        ->toContain('Palù del Fersina → —')
        ->toContain('Difficoltà CAI —')
        ->toContain('Dislivello in discesa —')
        ->toContain('Data di validazione —')
        ->toContain('Arrivo —');
});

test('a trail practicable with difficulties shows the amber alert, a practicable one none', function (): void {
    $this->actingAs($this->owner);

    $html = trailDetailHtml(Livewire::test(CaiTrailDetail::class, ['trailId' => '1003']));

    expect($html)->toContain('border-color: #f3d9a6')
        ->toContain('Sentiero percorribile con difficoltà')
        ->toContain('Stato di percorribilità aggiornato il — · fonte osm2cai');

    expect(CaiTrailPracticability::Practicable->alertTitle())->toBeNull()
        ->and(CaiTrailPracticability::Unknown->alertTitle())->toBeNull();
});

test('a shared trail lists every managing section and marks the user one', function (): void {
    $this->actingAs($this->owner);

    $html = trailDetailHtml(Livewire::test(CaiTrailDetail::class, ['trailId' => '1003']));
    $text = (string) preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($html)));

    expect($text)->toContain('Sezioni che lo gestiscono 3')
        ->toContain('Sentiero gestito in comune da 3 Sezioni')
        ->toContain('SEZ. CARRARA SEZ. LUCCA Questa Sezione SEZ. PARMA')
        ->and(substr_count($text, 'Questa Sezione'))->toBe(1);
});

test('the link back returns to the section list keeping its filters and sort', function (): void {
    $list = ['stato' => ['2', '4'], 'difficolta' => 'ee', 'percorribilita' => 'non-percorribile', 'ordina' => 'km-desc'];

    $this->actingAs($this->owner)
        ->get(CaiTrailDetail::getUrl(['trailId' => 1001, ...$list, 'estraneo' => 'x']))
        ->assertOk()
        ->assertSeeText('← Sentieri di Sezione')
        ->assertSee('href="'.e(SectionTrails::getUrl($list)).'"', escape: false)
        ->assertDontSee('estraneo');

    expect(CaiTrailDetail::listQueryFrom([
        'stato' => ['4', ['x'], ''], 'difficolta' => '', 'percorribilita' => str_repeat('x', 101), 'ordina' => 'km', 'da' => 'gr',
    ]))->toBe(['stato' => ['4'], 'ordina' => 'km']);
});

test('the section list links every trail to its sheet with the current filters', function (): void {
    $this->actingAs($this->owner);

    Livewire::withQueryParams(['ordina' => 'km-desc'])->test(SectionTrails::class)
        ->call('toggleStatus', 4)
        ->assertSee('href="'.e(CaiTrailDetail::getUrl(['trailId' => 1001, 'stato' => ['4'], 'ordina' => 'km-desc'])).'"', escape: false);

    // I parametri di `withQueryParams` restano per i `Livewire::test` successivi: vanno azzerati.
    Livewire::withQueryParams([])->test(SectionTrails::class)
        ->assertSee('href="'.e(CaiTrailDetail::getUrl(['trailId' => 1002])).'"', escape: false);
});

test('the regional group opens the sheets of its sections and goes back to its list', function (): void {
    $tuscany = trailDetailCustomer(CustomerType::GruppoRegionale, Region::Toscana);
    $list = ['stato' => ['4'], 'ordina' => 'km'];

    $this->actingAs($tuscany)->get(CaiTrailDetail::getUrl(['trailId' => 1001]))
        ->assertOk()
        ->assertSeeText('← Sentieri del GR')
        ->assertSee('href="'.RegionalGroupTrails::getUrl().'"', escape: false)
        ->assertDontSeeText('Questa Sezione');

    $this->actingAs($tuscany)->get(CaiTrailDetail::getUrl(['trailId' => 1003, 'da' => 'gr', ...$list]))
        ->assertOk()
        ->assertSee('href="'.e(RegionalGroupTrails::getUrl($list)).'"', escape: false);
});

test('staff with the directory permission opens any trail and goes back to the first managing section', function (): void {
    $staff = withRole(userWithPermissions(Permission::CaiDirectoryView), UserRole::Manager);

    $this->actingAs($staff)->get(CaiTrailDetail::getUrl(['trailId' => 1003]))
        ->assertOk()
        ->assertSeeText('Strada delle 52 Gallerie')
        ->assertSee('href="'.CaiSectionResource::getUrl('view', ['record' => '9226005']).'"', escape: false);
});

test('other sections, other regional groups and other customers get 403, also with a manipulated id', function (): void {
    $pisa = trailDetailCustomer(CustomerType::Sezione);
    caiSection(['codice_cai' => '9226002', 'name' => 'SEZ. PISA', 'region' => 'TOSCANA', 'user_id' => $pisa->id]);
    $emilia = trailDetailCustomer(CustomerType::GruppoRegionale, Region::EmiliaRomagna);
    $generic = trailDetailCustomer(CustomerType::Generico);

    $this->actingAs($pisa)->get(CaiTrailDetail::getUrl(['trailId' => 1001]))
        ->assertForbidden()
        ->assertDontSeeText('Sentiero della Val Brenta Alta');
    $this->actingAs($emilia)->get(CaiTrailDetail::getUrl(['trailId' => 1001]))->assertForbidden();
    $this->actingAs($generic)->get(CaiTrailDetail::getUrl(['trailId' => 1001]))->assertForbidden();

    // La Sezione di Lucca non apre il sentiero di Parma cambiando l'id nell'URL; il GR Emilia-Romagna sì.
    $this->actingAs($this->owner)->get(CaiTrailDetail::getUrl(['trailId' => 1004]))->assertForbidden();
    $this->actingAs($emilia)->get(CaiTrailDetail::getUrl(['trailId' => 1004]))->assertOk();
    $this->actingAs($emilia)->get(CaiTrailDetail::getUrl(['trailId' => 1003]))->assertOk();
});

test('an unknown or malformed trail id is a 404', function (): void {
    $this->actingAs($this->owner)->get(CaiTrailDetail::getUrl(['trailId' => 999]))->assertNotFound();
    $this->actingAs($this->owner)->get(CaiTrailDetail::getUrl(['trailId' => 'abc']))->assertNotFound();
});
