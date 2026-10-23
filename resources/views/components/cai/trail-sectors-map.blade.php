{{-- Mappa dei sentieri di una regione (US-1064), design "Sentieri del GR" §Mappa: x-cai.map con i settori colorati per
     km consolidati (CaiMapArea::forSector, da CaiRegionalGroupTrails::sectorAreas) e, da zoom 11, le linee dell'area
     visibile da `linesUrl` (CaiRegionalGroupTrailsMapController). Riquadro del modo in alto a sinistra con il pulsante
     "Mostra i sentieri" / "Vista per settori", "Vista regione" sotto lo zoom, legenda in basso a sinistra (scala dei
     settori o stati di accatastamento); su mobile modo e legenda sotto la mappa, con i testi brevi del design.
     Eventi come x-cai.map (`cai-map-area-selected`, `cai-map-area-listed`, `cai-map-line-selected`, …). --}}
@props([
    'name',
    'areas' => [],
    'linesUrl' => null,
])
@use('App\Domain\CaiDirectory\Enums\CaiTrailStatus')
@use('App\Domain\CaiDirectory\Support\CaiMapArea')
@php
    $ramp = CaiMapArea::CONSOLIDATED_RAMP;
    $statuses = CaiTrailStatus::cases();
@endphp
<x-cai.map
    :name="$name"
    :areas="$areas"
    :lines-url="$linesUrl"
    height="var(--ms-trail-sectors-map-height)"
    :fit-max-zoom="12"
    attribution="Cartografia CAI · osm2cai · © OpenStreetMap"
    {{ $attributes->class(['ms-trail-sectors-map']) }}
>
    <x-slot:overlay>
        <div class="ms-trail-sectors-map__mode">
            <div class="ms-trail-sectors-map__mode-text">
                <span x-show="mode !== 'lines'">Settori colorati per km consolidati · i sentieri compaiono da zoom 11</span>
                <span x-show="mode === 'lines'" x-cloak><span x-text="lineCount.toLocaleString('it-IT')"></span> sentieri in vista, colorati per stato di accatastamento</span>
                <span x-show="mode === 'lines' && linesLoading" x-cloak class="ms-trail-sectors-map__note">Caricamento dei sentieri…</span>
                <span x-show="mode === 'lines' && ! linesLoading && linesFailed" x-cloak class="ms-trail-sectors-map__note ms-trail-sectors-map__note--error">Sentieri non caricati: sposta la mappa per riprovare</span>
                <span x-show="mode === 'lines' && ! linesLoading && linesTruncated" x-cloak class="ms-trail-sectors-map__note">Mostrati i <span x-text="lineLimit.toLocaleString('it-IT')"></span> sentieri più lunghi dell'area: zooma per vederli tutti</span>
            </div>
            <button type="button" class="ms-trail-sectors-map__mode-button" x-on:click="mode === 'lines' ? showAreas() : showLines()" x-text="mode === 'lines' ? 'Vista per settori' : 'Mostra i sentieri'">Mostra i sentieri</button>
        </div>
        <button type="button" class="ms-trail-sectors-map__reset" title="Vista regione" aria-label="Vista regione" x-on:click="resetView()">
            <svg width="16" height="16" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 9V4h5M20 9V4h-5M4 15v5h5M20 15v5h-5"></path></svg>
        </button>
        <div class="ms-trail-sectors-map__legend" x-show="mode !== 'lines'">
            @include('components.cai.trail-sectors-map-legend', ['kind' => 'areas', 'ramp' => $ramp, 'statuses' => $statuses])
        </div>
        <div class="ms-trail-sectors-map__legend" x-show="mode === 'lines'" x-cloak>
            @include('components.cai.trail-sectors-map-legend', ['kind' => 'lines', 'ramp' => $ramp, 'statuses' => $statuses])
        </div>
    </x-slot:overlay>
    <x-slot:footer>
        <div class="ms-trail-sectors-map__footer">
            <div class="ms-trail-sectors-map__footer-bar">
                <span x-show="mode !== 'lines'">Vista per settori · zooma per i sentieri</span>
                <span x-show="mode === 'lines'" x-cloak><span x-text="lineCount.toLocaleString('it-IT')"></span> sentieri in vista<span x-show="linesTruncated"> (i più lunghi)</span></span>
                <button type="button" class="ms-trail-sectors-map__mode-button" x-on:click="mode === 'lines' ? showAreas() : showLines()" x-text="mode === 'lines' ? 'Vista per settori' : 'Mostra i sentieri'">Mostra i sentieri</button>
            </div>
            <div x-show="mode !== 'lines'">
                @include('components.cai.trail-sectors-map-legend', ['kind' => 'areas', 'ramp' => $ramp, 'statuses' => $statuses])
            </div>
            <div x-show="mode === 'lines'" x-cloak>
                @include('components.cai.trail-sectors-map-legend', ['kind' => 'lines', 'ramp' => $ramp, 'statuses' => $statuses])
            </div>
        </div>
    </x-slot:footer>
</x-cai.map>
