<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Enums\CaiHutStatus;
use App\Domain\CaiDirectory\Enums\CaiHutType;
use App\Domain\CaiDirectory\Enums\CaiTrailPracticability;
use App\Domain\CaiDirectory\Enums\CaiTrailStatus;
use App\Domain\CaiDirectory\Models\CaiHut;
use App\Domain\CaiDirectory\Models\CaiTrail;
use App\Domain\CaiDirectory\Support\CaiMapArea;
use App\Domain\CaiDirectory\Support\CaiMapLine;
use App\Domain\CaiDirectory\Support\CaiMapMarker;
use App\Domain\CaiDirectory\Support\CaiRegionalGroupTrailSectorRow;
use App\Domain\CaiDirectory\Support\CaiTrailKmStats;

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

/**
 * @param  array<string, mixed>  $attributes
 */
function caiMapTrail(array $attributes = []): CaiTrail
{
    return new CaiTrail(array_merge([
        'id' => 4512,
        'ref' => '101',
        'name' => 'Sentiero dei "Fiori"',
        'from_place' => 'Passo Cereda',
        'to_place' => 'Malga Fosse',
        'length_km' => '12.345',
        'status' => CaiTrailStatus::PreValidated,
        'practicability' => CaiTrailPracticability::WithDifficulty,
        'geometry' => ['type' => 'MultiLineString', 'coordinates' => [[[11.81, 46.22], [11.82, 46.23]]]],
    ], $attributes));
}

test('the map config carries the line weights, casing, dimming and click tolerance of the design', function (): void {
    expect(caiMapPayload((string) $this->blade('<x-cai.map name="sentieri" />'), 'data-cai-map-config'))->toMatchArray([
        'lineHoverWeight' => 5,
        'lineSelectedWeight' => 5.5,
        'lineHoverCasing' => 8,
        'lineSelectedCasing' => 10,
        'lineDimmedOpacity' => 0.5,
        'lineTolerance' => 6,
    ]);
});

test('lines are drawn on canvas and emit their own selection and hover events', function (): void {
    $html = (string) $this->blade('<x-cai.map name="sentieri" />');

    expect($html)->toContain('L.canvas({ tolerance: config.lineTolerance })')
        ->toContain("this.select(line.id, true, 'line')")
        ->toContain("this.hover(line.id, 'line')")
        ->toContain('cai-map-${kind}-selected')
        ->toContain('cai-map-${kind}-hovered');
});

test('a trail line has the colour of its status, the design weight and the preview with the link', function (): void {
    $line = CaiMapLine::forTrail(caiMapTrail(), '/admin/sentieri/4512');

    expect($line)->not->toBeNull()
        ->and($line->id)->toBe(4512)
        ->and($line->color)->toBe('#a24cc7')
        ->and($line->weight)->toBe(CaiMapLine::TRAIL_WEIGHT)
        ->and($line->label)->toBe('101 · Sentiero dei "Fiori"')
        ->and($line->geojson)->toBe(['type' => 'MultiLineString', 'coordinates' => [[[11.81, 46.22], [11.82, 46.23]]]]);

    expect($line->popupHtml)
        ->toContain('ms-cai-trail-sign--sm">101</span>')
        ->toContain('Sentiero dei &quot;Fiori&quot;')
        ->toContain('Passo Cereda → Malga Fosse')
        ->toContain('--ms-cai-trail-status-color: #a24cc7">3 · Pre-validato</span>')
        ->toContain('background: #fdf1dc; color: #8a5600">Percorribile con difficoltà</span>')
        ->toContain('12,3 km')
        ->toContain('href="/admin/sentieri/4512"')
        ->toContain('Apri scheda →');
});

test('a trail preview without number, name or places shows dashes and no link', function (): void {
    $line = CaiMapLine::forTrail(caiMapTrail(['ref' => null, 'name' => null, 'from_place' => '', 'to_place' => null]));

    expect($line?->label)->toBeNull()
        ->and($line?->popupHtml)
        ->toContain('ms-cai-trail-sign--sm">—</span>')
        ->toContain('ms-cai-map-popup__title--missing">—</div>')
        ->toContain('— → —')
        ->not->toContain('Apri scheda');
});

test('a trail without geometry has no line', function (): void {
    expect(CaiMapLine::forTrail(caiMapTrail(['geometry' => null])))->toBeNull();
});

test('trail lines reach the map payload as GeoJSON with their preview', function (): void {
    $lines = [CaiMapLine::forTrail(caiMapTrail(), '/admin/sentieri/4512')];

    $payload = caiMapPayload((string) $this->blade('<x-cai.map name="sentieri" :lines="$lines" selected="4512" />', ['lines' => $lines]));

    expect($payload['lines'])->toHaveCount(1)
        ->and($payload['lines'][0])->toMatchArray(['id' => 4512, 'color' => '#a24cc7', 'weight' => 3])
        ->and($payload['lines'][0]['geojson']['type'])->toBe('MultiLineString')
        ->and($payload['lines'][0]['popupHtml'])->toContain('Apri scheda →');
});

test('without areas the payload and config of the 10.4–10.6 maps keep their shape, lines are loaded only with an url', function (): void {
    $html = (string) $this->blade('<x-cai.map name="rifugi" />');

    expect(caiMapPayload($html))->toBe(['markers' => [], 'lines' => []])
        ->and(caiMapPayload($html, 'data-cai-map-config'))->toMatchArray(['linesUrl' => null, 'linesMinZoom' => 11])
        ->and($html)->toContain('if (! config.linesUrl || ! map) {')
        ->not->toContain('ms-trail-sectors-map');
});

test('areas reach the payload with colour, empty state and label, and the config carries the styles of the design', function (): void {
    $polygon = ['type' => 'MultiPolygon', 'coordinates' => [[[[9.6, 45.9], [9.7, 45.9], [9.7, 46.05], [9.6, 45.9]]]]];
    $areas = [
        new CaiMapArea(id: 'settore-100', geojson: $polygon, color: '#4a81d3', label: 'LBG01', popupHtml: '<b>BG01</b>'),
        new CaiMapArea(id: 'settore-101', geojson: $polygon, color: '#e3ecfa', empty: true, label: 'LBG02'),
    ];

    $html = (string) $this->blade(
        '<x-cai.map name="gr-sentieri" :areas="$areas" lines-url="/cai-trail-regions/20/map-trails" />',
        ['areas' => $areas],
    );

    expect(caiMapPayload($html)['areas'])->toBe([
        ['id' => 'settore-100', 'geojson' => $polygon, 'color' => '#4a81d3', 'empty' => false, 'label' => 'LBG01', 'popupHtml' => '<b>BG01</b>'],
        ['id' => 'settore-101', 'geojson' => $polygon, 'color' => '#e3ecfa', 'empty' => true, 'label' => 'LBG02', 'popupHtml' => null],
    ])->and(caiMapPayload($html, 'data-cai-map-config'))->toMatchArray([
        'linesUrl' => '/cai-trail-regions/20/map-trails',
        'linesMinZoom' => 11,
        'areaLabelsMinZoom' => 9,
        'areaFillOpacity' => ['low' => 0.74, 'high' => 0.06, 'emptyLow' => 0.85, 'emptyHigh' => 0.4],
        'areaStroke' => ['low' => '#ffffff', 'high' => '#5b6475', 'selected' => '#1f2633', 'empty' => '#b42318'],
        'areaEmptyFill' => '#fbe7e5',
        'areaEmptyDash' => '5 3',
    ])->and($html)
        // Aree sullo stesso canvas delle linee (sotto), linee per riquadro con cache e richiesta precedente annullata.
        ->toContain('L.geoJSON(area.geojson, { renderer: lineRenderer, style: areaStyle(area) })')
        ->toContain("url.searchParams.set('bbox', bbox)")
        ->toContain("url.searchParams.set('zoom', String(zoom))")
        ->toContain('linesCache.has(requestKey)')
        ->toContain('linesRequest?.abort()')
        ->toContain('cai-map-area-listed');
});

test('the consolidated colour follows the five bands of the design', function (?float $share, string $color): void {
    expect(CaiMapArea::consolidatedColor($share))->toBe($color);
})->with([
    'no km' => [null, '#e3ecfa'],
    '0%' => [0.0, '#e3ecfa'],
    '19%' => [0.19, '#e3ecfa'],
    '20%' => [0.2, '#b3cbf0'],
    '59%' => [0.59, '#7fa6e2'],
    '79%' => [0.79, '#4a81d3'],
    '80%' => [0.8, '#1f4fa8'],
    '100%' => [1.0, '#1f4fa8'],
]);

test('a sector area has the preview of the design: code, province, name, trails, km, bar and consolidated share', function (): void {
    $stats = CaiTrailKmStats::empty()->with(CaiTrailStatus::Difficulty, 2.5, 1)->with(CaiTrailStatus::Validated, 7.5, 2);
    $sector = new CaiRegionalGroupTrailSectorRow(id: 100, code: '1', fullCode: 'LBG01', name: 'Val Brembana', stats: $stats, hasTrails: true);
    $geometry = ['type' => 'MultiPolygon', 'coordinates' => [[[[9.6, 45.9], [9.7, 45.9], [9.7, 46.0], [9.6, 45.9]]]]];

    $area = CaiMapArea::forSector($sector, $geometry, 'Bergamo');

    expect($area?->id)->toBe('settore-100')
        ->and($area?->color)->toBe('#4a81d3')
        ->and($area?->empty)->toBeFalse()
        ->and($area?->label)->toBe('LBG01')
        ->and($area?->popupHtml)
        ->toContain('Settore LBG01 · BERGAMO')
        ->toContain('Val Brembana')
        ->toContain('3 sentieri · 10,0 km')
        ->toContain('width: 25%; background: #e8b500')
        ->toContain('width: 75%; background: #2563c9')
        ->toContain('Consolidati (stati 3–4): <strong>75%</strong>')
        ->toContain('data-cai-map-area-action="zoom">Zooma sul settore</button>')
        ->toContain('data-cai-map-area-action="list">Elenco sentieri →</button>')
        ->and(CaiMapArea::forSector($sector, $geometry, 'Bergamo', listable: false)?->popupHtml)->not->toContain('Elenco sentieri')
        ->and(CaiMapArea::forSector($sector, null, 'Bergamo'))->toBeNull();
});

test('an empty sector is pink with the dashed border and says no trail is recorded', function (): void {
    $sector = new CaiRegionalGroupTrailSectorRow(id: 101, code: '2', fullCode: 'LBG02', name: 'Pianura', stats: CaiTrailKmStats::empty(), hasTrails: false);

    $area = CaiMapArea::forSector($sector, ['type' => 'Polygon', 'coordinates' => [[[9.6, 45.6], [9.7, 45.6], [9.6, 45.6]]]], 'Bergamo');

    expect($area?->empty)->toBeTrue()
        ->and($area?->popupHtml)->toContain('Nessun sentiero censito in questo settore')
        ->not->toContain('Zooma sul settore')
        ->not->toContain('sentieri ·');
});

test('the trail sectors map renders the mode texts, buttons and legends of the design for desktop and mobile', function (): void {
    $html = (string) $this->blade('<x-cai.trail-sectors-map name="gr-sentieri" lines-url="/cai-trail-regions/20/map-trails" />');

    expect($html)
        ->toContain('Settori colorati per km consolidati · i sentieri compaiono da zoom 11')
        ->toContain('sentieri in vista, colorati per stato di accatastamento')
        ->toContain('Vista per settori · zooma per i sentieri')
        ->toContain("mode === 'lines' ? 'Vista per settori' : 'Mostra i sentieri'")
        ->toContain('title="Vista regione"')
        ->toContain('Km consolidati (stati 3–4) per settore')
        ->toContain('0–20%')
        ->toContain('80–100%')
        ->toContain('Settore senza sentieri')
        ->toContain('Stato di accatastamento')
        ->toContain('4 · Validato')
        ->toContain('zooma per vederli tutti')
        ->and(caiMapPayload($html, 'data-cai-map-config'))->toMatchArray([
            'linesUrl' => '/cai-trail-regions/20/map-trails',
            'attribution' => 'Cartografia CAI · osm2cai · © OpenStreetMap',
        ]);
});
