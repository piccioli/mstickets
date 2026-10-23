{{-- Anteprima di un punto di appoggio SICAI sulla mappa del GR (US-1095), design "Tappe SICAI del GR" §Mappa: tipo con
     il pallino del colore, nome, comune · tappe vicine, "Vedi su rifugi.cai.it ↗" se è un rifugio importato,
     "Nell'elenco dei punti →" (ancora `listAnchor` della pagina). Si rende in stringa (CaiMapMarker::forSicaiRestPoint). --}}
@props([
    'type',
    'color',
    'name',
    'municipality' => null,
    'stageCodes' => [],
    'hutUrl' => null,
    'listAnchor' => null,
])
<div class="ms-cai-map-popup ms-cai-map-popup--sicai-point">
    <div class="ms-cai-map-popup__body">
        <div class="ms-cai-map-popup__eyebrow" style="color: {{ $color }}"><span class="ms-cai-map-popup__dot" style="background: {{ $color }}"></span>{{ $type }}</div>
        <div class="ms-cai-map-popup__title">{{ $name }}</div>
        <div class="ms-cai-map-popup__route">{{ filled($municipality) ? $municipality : '—' }} · tappe {{ $stageCodes === [] ? '—' : implode(', ', $stageCodes) }}</div>
        @if ($hutUrl !== null)
            <a class="ms-cai-map-popup__link" href="{{ $hutUrl }}" target="_blank" rel="noopener">Vedi su rifugi.cai.it ↗</a>
        @endif
        @if ($listAnchor !== null)
            <a class="ms-cai-map-popup__link" href="#{{ $listAnchor }}">Nell’elenco dei punti →</a>
        @endif
    </div>
</div>
