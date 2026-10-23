<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Enums\CaiHutStatus;
use App\Domain\CaiDirectory\Enums\CaiHutType;
use App\Domain\CaiDirectory\Models\CaiHut;
use App\Domain\CaiDirectory\Support\CaiMapLine;
use App\Domain\CaiDirectory\Support\CaiMapMarker;

/**
 * @return array<string, mixed>
 */
function caiMapPayload(string $html, string $attribute = 'data-cai-map-payload'): array
{
    preg_match('/'.$attribute.'="([^"]*)"/', $html, $matches);

    expect($matches)->toHaveKey(1);

    return json_decode(html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5), true, flags: JSON_THROW_ON_ERROR);
}

/**
 * @param  array<string, mixed>  $attributes
 */
function caiMapHut(array $attributes = []): CaiHut
{
    return new CaiHut(array_merge([
        'id_cai' => 1201,
        'name' => 'Rifugio Tosa "Tommaso Pedrotti"',
        'type' => CaiHutType::Managed,
        'status' => CaiHutStatus::Active,
        'latitude' => '46.1745000',
        'longitude' => '10.8752000',
        'altitude' => 2491,
        'photos' => ['https://rifugi.cai.it/storage/1201/tosa.jpg'],
    ], $attributes));
}

test('the map renders the CAI tiles, zoom limits, attribution and the marker payload', function (): void {
    $markers = [
        new CaiMapMarker(id: 12, lat: 46.17, lng: 10.87, label: 'Rifugio Tosa', color: '#17a180', letter: 'R', popupHtml: '<b>Tosa</b>'),
        ['id' => 'b-7', 'lat' => 46.2, 'lng' => 10.9, 'label' => 'Bivacco', 'color' => '#d9822b', 'letter' => 'B', 'popupHtml' => null],
    ];

    $view = $this->blade('<x-cai.map name="huts" :markers="$markers" selected="12" height="500px" />', ['markers' => $markers]);

    expect(caiMapPayload((string) $view, 'data-cai-map-config'))->toMatchArray([
        'name' => 'huts',
        'selected' => '12',
        'tiles' => 'https://r3-it.storage.cloud.it/tiles/{z}/{x}/{y}.png',
        'attribution' => '© CAI · © OpenStreetMap contributors',
        'minZoom' => 6,
        'maxZoom' => 16,
        'fitMaxZoom' => 13,
        'singleZoom' => 13,
    ]);

    $view->assertSee('data-cai-map="huts"', false)
        ->assertSee('height: 500px', false)
        ->assertSee('wire:ignore', false)
        ->assertSee('leaflet@1.9.4/dist/leaflet.js', false);

    expect(caiMapPayload((string) $view))->toBe([
        'markers' => [
            ['id' => 12, 'lat' => 46.17, 'lng' => 10.87, 'label' => 'Rifugio Tosa', 'color' => '#17a180', 'letter' => 'R', 'popupHtml' => '<b>Tosa</b>'],
            ['id' => 'b-7', 'lat' => 46.2, 'lng' => 10.9, 'label' => 'Bivacco', 'color' => '#d9822b', 'letter' => 'B', 'popupHtml' => null],
        ],
        'lines' => [],
    ]);
});

test('the map without markers renders an empty payload', function (): void {
    $view = $this->blade('<x-cai.map name="vuota" />');

    expect(caiMapPayload((string) $view))->toBe(['markers' => [], 'lines' => []]);
});

test('the map payload carries GeoJSON lines with their colour', function (): void {
    $geojson = ['type' => 'LineString', 'coordinates' => [[10.1, 46.1], [10.2, 46.2]]];
    $lines = [new CaiMapLine(id: 'sentiero-1', geojson: $geojson, color: '#e0533f', label: 'Sentiero 101')];

    $payload = caiMapPayload((string) $this->blade('<x-cai.map name="sentieri" :lines="$lines" />', ['lines' => $lines]));

    expect($payload['lines'])->toBe([
        ['id' => 'sentiero-1', 'geojson' => $geojson, 'color' => '#e0533f', 'label' => 'Sentiero 101', 'weight' => 4, 'popupHtml' => null],
    ]);
});

test('the wire:key of the payload changes with the data so Livewire re-initialises the map', function (): void {
    $key = function (array $markers): string {
        preg_match('/wire:key="([^"]+)"/', (string) $this->blade('<x-cai.map name="huts" :markers="$markers" />', ['markers' => $markers]), $matches);

        return $matches[1];
    };
    $one = [new CaiMapMarker(id: 1, lat: 46.0, lng: 11.0, label: 'A', color: '#17a180', letter: 'R')];
    $two = [...$one, new CaiMapMarker(id: 2, lat: 46.1, lng: 11.1, label: 'B', color: '#2f6fb0', letter: 'I')];

    expect($key($one))->toStartWith('cai-map-huts-')
        ->and($key($one))->toBe($key($one))
        ->and($key($one))->not->toBe($key($two));
});

test('Leaflet is loaded once per page even with two maps', function (): void {
    $html = (string) $this->blade('<x-cai.map name="uno" /><x-cai.map name="due" />');

    expect(substr_count($html, 'leaflet@1.9.4/dist/leaflet.js'))->toBe(1)
        ->and(substr_count($html, 'leaflet@1.9.4/dist/leaflet.css'))->toBe(1)
        ->and(substr_count($html, 'window.caiMapComponent ??='))->toBe(1)
        ->and(substr_count($html, 'data-cai-map="'))->toBe(2);
});

test('a hut marker has the colour and letter of its type and the popup of the design', function (): void {
    $marker = CaiMapMarker::forHut(caiMapHut(), '/admin/rifugi/1201');

    expect($marker)->not->toBeNull()
        ->and($marker->id)->toBe(1201)
        ->and($marker->lat)->toBe(46.1745)
        ->and($marker->lng)->toBe(10.8752)
        ->and($marker->color)->toBe('#17a180')
        ->and($marker->letter)->toBe('R')
        ->and($marker->label)->toBe('Rifugio Tosa "Tommaso Pedrotti"');

    expect($marker->popupHtml)
        ->toContain('src="https://rifugi.cai.it/storage/1201/tosa.jpg"')
        ->toContain('Rifugio Tosa &quot;Tommaso Pedrotti&quot;')
        ->toContain('Rifugio custodito · 2.491 m')
        ->toContain('background: #e3f4ee; color: #0f7d63')
        ->toContain('In attività')
        ->toContain('href="/admin/rifugi/1201"')
        ->toContain('Apri scheda →');
});

test('a hut popup without photo, status or link omits them', function (): void {
    $marker = CaiMapMarker::forHut(caiMapHut(['photos' => [], 'status' => null, 'altitude' => null, 'type' => CaiHutType::Bivouac]));

    expect($marker?->letter)->toBe('B')
        ->and($marker?->color)->toBe('#d9822b')
        ->and($marker?->popupHtml)
        ->not->toContain('<img')
        ->not->toContain('ms-cai-map-popup__status')
        ->not->toContain('Apri scheda')
        ->toContain('Bivacco');
});

test('a hut with unknown type gets the neutral marker and a hut without coordinates has none', function (): void {
    expect(CaiMapMarker::forHut(caiMapHut(['type' => null]))?->letter)->toBe(CaiMapMarker::UNKNOWN_LETTER)
        ->and(CaiMapMarker::forHut(caiMapHut(['type' => null]))?->color)->toBe(CaiMapMarker::UNKNOWN_COLOR)
        ->and(CaiMapMarker::forHut(caiMapHut(['latitude' => null])))->toBeNull();
});

test('without the cluster option the map does not load leaflet.markercluster', function (): void {
    $view = $this->blade('<x-cai.map name="huts" />');

    expect(caiMapPayload((string) $view, 'data-cai-map-config'))->toMatchArray(['cluster' => false])
        ->and((string) $view)->not->toContain('leaflet.markercluster');
});

test('the cluster option loads leaflet.markercluster 1.5.3 once and configures the groups of the design', function (): void {
    $html = (string) $this->blade('<x-cai.map name="uno" cluster /><x-cai.map name="due" cluster cluster-noun="punti" />');

    expect(substr_count($html, 'leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js'))->toBe(1)
        ->and(substr_count($html, 'leaflet.markercluster@1.5.3/dist/MarkerCluster.css'))->toBe(1)
        ->and(strpos($html, 'leaflet@1.9.4/dist/leaflet.js'))->toBeLessThan(strpos($html, 'leaflet.markercluster.js'))
        ->and($html)->toContain('clic per ingrandire');

    expect(caiMapPayload($html, 'data-cai-map-config'))->toMatchArray([
        'name' => 'uno',
        'cluster' => true,
        'clusterNoun' => 'rifugi',
        'clusterUntilZoom' => 11,
        'clusterZoomStep' => 2,
    ]);

    preg_match_all('/data-cai-map-config="([^"]*)"/', $html, $configs);
    $second = json_decode(html_entity_decode($configs[1][1], ENT_QUOTES | ENT_HTML5), true, flags: JSON_THROW_ON_ERROR);

    expect($second)->toMatchArray(['name' => 'due', 'cluster' => true, 'clusterNoun' => 'punti']);
});

test('a hut popup can show the owning section under the name', function (): void {
    $withSection = CaiMapMarker::forHut(caiMapHut(), '/admin/rifugi/1201', 'SEZ. DI MILANO');

    expect($withSection?->popupHtml)
        ->toContain('ms-cai-map-popup__section">SEZ. DI MILANO</div>')
        ->and(CaiMapMarker::forHut(caiMapHut())?->popupHtml)->not->toContain('ms-cai-map-popup__section');
});
