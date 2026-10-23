{{-- Riquadro di un settore sulla mappa del GR (US-1064), come nel design "Sentieri del GR": "SETTORE codice · PROVINCIA",
     nome, "Nessun sentiero censito in questo settore" se vuoto, altrimenti sentieri · km, barra dei km per stato,
     percentuale consolidati e le due azioni. I pulsanti con `data-cai-map-area-action` li gestisce x-cai.map: `zoom`
     inquadra il settore (almeno allo zoom dei sentieri), `list` emette `cai-map-area-listed`. Si rende in stringa
     (CaiMapArea::forSector). --}}
@props([
    'code' => null,
    'province' => null,
    'name' => null,
    'empty' => false,
    'trails' => null,
    'km' => null,
    'bar' => [],
    'consolidated' => null,
    'listable' => true,
])
<div class="ms-cai-map-popup ms-cai-map-popup--sector">
    <div class="ms-cai-map-popup__body">
        <div class="ms-cai-map-popup__eyebrow">Settore {{ $code }}@if (filled($province)) · {{ $province }}@endif</div>
        <div class="ms-cai-map-popup__title">{{ $name }}</div>
        @if ($empty)
            <div class="ms-cai-map-popup__empty">Nessun sentiero censito in questo settore</div>
        @else
            <div class="ms-cai-map-popup__km">{{ $trails }} · {{ $km }}</div>
            <div class="ms-cai-map-popup__bar" role="img" aria-label="Km per stato di accatastamento">
                @foreach ($bar as $segment)
                    @if ($segment['width'] > 0)
                        <span style="width: {{ $segment['width'] }}%; background: {{ $segment['color'] }}" title="{{ $segment['label'] }}"></span>
                    @endif
                @endforeach
            </div>
            <div class="ms-cai-map-popup__consolidated">Consolidati (stati 3–4): <strong>{{ $consolidated }}</strong></div>
            <div class="ms-cai-map-popup__actions">
                <button type="button" class="ms-cai-map-popup__link" data-cai-map-area-action="zoom">Zooma sul settore</button>
                @if ($listable)
                    <button type="button" class="ms-cai-map-popup__link" data-cai-map-area-action="list">Elenco sentieri →</button>
                @endif
            </div>
        @endif
    </div>
</div>
