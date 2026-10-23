<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\CustomerType;
use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Enums\Region;
use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function grMapCustomer(?Region $region, CustomerType $type = CustomerType::GruppoRegionale): User
{
    $user = withRole(User::factory()->create(), UserRole::Customer);
    $user->forceFill(['customer_type' => $type, 'region' => $region])->save();

    return $user;
}

/** URL dei sentieri della Lombardia della fixture (regione osm2cai 1) per il riquadro dato. */
function grMapUrl(string $bbox = '10.0,43.0,10.3,43.3', int $region = 1): string
{
    return route('cai-trail-regions.map-trails', ['caiTrailRegion' => $region, 'bbox' => $bbox]);
}

test('the GR of the region gets the present trails in the box as map lines with the preview of the design', function (): void {
    grTrailsFixture();
    DB::table('cai_trails')->where('ref', '2A')->update(['min_lat' => 46.0, 'min_lng' => 9.0, 'max_lat' => 46.1, 'max_lng' => 9.1]);

    $response = $this->actingAs(grMapCustomer(Region::Lombardia))->getJson(grMapUrl());

    $response->assertOk()
        ->assertHeader('Cache-Control', 'max-age=300, private')
        ->assertJsonPath('total', 3)
        ->assertJsonPath('limit', 600)
        ->assertJsonPath('truncated', false);

    $lines = collect($response->json('lines'))->keyBy(fn (array $line): string => strip_tags(explode('</span>', $line['popupHtml'])[0]));
    $ten = DB::table('cai_trails')->where('ref', '10')->value('id');

    // Più lunghi per primi: "10" (10 km), "E1" (8), "2" (6); "2A" è fuori dal riquadro, "5" e "" non sono presenti.
    expect(array_map(fn (array $line): string => trim(strip_tags(explode('</span>', $line['popupHtml'])[0])), $response->json('lines')))->toBe(['10', 'E1', '2'])
        ->and($response->json('lines.0'))->toMatchArray(['id' => $ten, 'color' => '#2563c9', 'weight' => 3])
        ->and($response->json('lines.0.geojson.type'))->toBe('MultiLineString')
        ->and($response->json('lines.0.popupHtml'))
        ->toContain('Sentiero delle Orobie')
        ->toContain('10,0 km · LBG01 Val Brembana')
        ->toContain('CAI Milano')
        ->toContain('href="'.url('/admin/sentieri/'.$ten).'?da=gr"')
        ->toContain('Apri scheda →')
        ->and($response->json('lines.2.popupHtml'))->toContain('CAI Bergamo, CAI Milano');

    // "E1" è solo di Torino (altro GR) e di un codice senza Sezione: compare Torino.
    expect($response->json('lines.1.popupHtml'))->toContain('CAI Torino');
});

test('a present trail without section says so in the preview', function (): void {
    grTrailsFixture();

    $lines = $this->actingAs(grMapCustomer(Region::Lombardia))->getJson(grMapUrl())->assertOk()->json('lines');
    $twoA = collect($lines)->first(fn (array $line): bool => str_contains($line['popupHtml'], 'ms-cai-trail-sign--sm">2A</span>'));

    expect($twoA['popupHtml'])->toContain('ms-cai-map-popup__sections--missing">Nessuna Sezione</div>');
});

test('above the limit the longest trails are returned and the response is marked as truncated', function (): void {
    grTrailsFixture();
    config(['cai_directory.trails_map.limit' => 2]);

    $response = $this->actingAs(grMapCustomer(Region::Lombardia))->getJson(grMapUrl())->assertOk();

    expect($response->json('lines'))->toHaveCount(2)
        ->and($response->json('total'))->toBe(4)
        ->and($response->json('limit'))->toBe(2)
        ->and($response->json('truncated'))->toBeTrue();
});

test('a box without trails returns no lines', function (): void {
    grTrailsFixture();

    $this->actingAs(grMapCustomer(Region::Lombardia))->getJson(grMapUrl('7.0,38.0,7.5,38.5'))
        ->assertOk()
        ->assertJson(['lines' => [], 'total' => 0, 'truncated' => false]);
});

test('only the GR of the region and the staff can read the map trails', function (User $user, int $status): void {
    grTrailsFixture();

    $this->actingAs($user)->getJson(grMapUrl())->assertStatus($status);
})->with([
    'GR of another region' => [fn (): User => grMapCustomer(Region::Molise), 403],
    'GR without region' => [fn (): User => grMapCustomer(null), 403],
    'section customer of the region' => [fn (): User => grMapCustomer(Region::Lombardia, CustomerType::Sezione), 403],
    'staff without the permission' => [fn (): User => userWithPermissions(), 403],
    'staff with CaiDirectoryView' => [fn (): User => userWithPermissions(Permission::CaiDirectoryView), 200],
]);

test('a guest is sent to the login', function (): void {
    grTrailsFixture();

    $this->getJson(grMapUrl())->assertRedirect(route('filament.admin.auth.login'));
});

test('the Molise GR reads only the Molise trails', function (): void {
    grTrailsFixture();

    $response = $this->actingAs(grMapCustomer(Region::Molise))->getJson(grMapUrl(region: 2))->assertOk();

    expect($response->json('total'))->toBe(1)
        ->and($response->json('lines.0.popupHtml'))->toContain('ms-cai-trail-sign--sm">M1</span>');
});

test('an invalid box is rejected', function (string $bbox): void {
    grTrailsFixture();

    $this->actingAs(grMapCustomer(Region::Lombardia))->getJson(grMapUrl($bbox))->assertStatus(422);
})->with([
    'missing' => [''],
    'three numbers' => ['10,43,11'],
    'text' => ['a,b,c,d'],
    'west after east' => ['11,43,10,44'],
    'south after north' => ['10,44,11,43'],
    'out of range' => ['10,43,11,95'],
]);

test('an unknown region is not found', function (): void {
    grTrailsFixture();

    $this->actingAs(grMapCustomer(Region::Lombardia))->getJson(grMapUrl(region: 999))->assertNotFound();
});

test('the geometry is simplified for the zoom of the map', function (?string $zoom, int $points): void {
    grTrailsFixture();
    // Zig-zag di 10 m (≈ 0,0001°): sparisce a zoom 11 (tolleranza ≈ 38 m), resta a zoom 16 (≈ 1 m).
    $coordinates = array_map(fn (int $i): array => [10.1 + $i * 0.001, 43.1 + ($i % 2) * 0.0001], range(0, 20));
    DB::table('cai_trails')->where('ref', '10')->update(['geometry' => json_encode(['type' => 'MultiLineString', 'coordinates' => [$coordinates]])]);

    $url = grMapUrl().($zoom === null ? '' : '&zoom='.$zoom);
    $lines = $this->actingAs(grMapCustomer(Region::Lombardia))->getJson($url)->assertOk()->json('lines');

    expect(count($lines[0]['geojson']['coordinates'][0]))->toBe($points);
})->with([
    'no zoom = 11' => [null, 2],
    'zoom 11' => ['11', 2],
    'zoom 16' => ['16', 21],
    'beyond 16' => ['18', 21],
    'not a number' => ['x', 2],
]);

test('a sector without its own name is shown by code only', function (): void {
    grTrailsFixture();
    DB::table('cai_trail_sectors')->where('id', 100)->update(['name' => 'LBG01']);

    $lines = $this->actingAs(grMapCustomer(Region::Lombardia))->getJson(grMapUrl())->assertOk()->json('lines');

    expect($lines[0]['popupHtml'])->toContain('10,0 km · LBG01</div>');
});
