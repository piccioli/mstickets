{{-- Anteprima di una tappa SICAI sulla mappa del GR (US-1095), design "Tappe SICAI del GR" §Mappa: codice della tappa
     (cartello rosso-bianco-rosso del Sentiero Italia), "Variante di …" facoltativo, partenza → arrivo, km · salita (se
     nota), pillola della percorribilità, "Apri scheda →". Si rende in stringa (CaiMapLine::forSicaiStage). --}}
@props([
    'code',
    'variantOf' => null,
    'route' => null,
    'km' => null,
    'ascent' => null,
    'practicability' => null,
    'url' => null,
])
<div class="ms-cai-map-popup ms-cai-map-popup--trail ms-cai-map-popup--sicai">
    <div class="ms-cai-map-popup__body">
        <div class="ms-cai-map-popup__pills">
            <x-cai.trail-sign :ref="$code" size="sm" class="ms-cai-trail-sign--sicai" />
            @if ($variantOf !== null)
                <span class="ms-cai-map-popup__variant">Variante di {{ $variantOf }}</span>
            @endif
        </div>
        <div class="ms-cai-map-popup__title">{{ $route }}</div>
        <div class="ms-cai-map-popup__pills">
            @if ($km !== null)
                <span class="ms-cai-map-popup__km">{{ $km }} km{{ $ascent !== null ? ' · +'.$ascent : '' }}</span>
            @endif
            @if ($practicability !== null)
                <span class="ms-cai-map-popup__status" style="background: {{ $practicability->background() }}; color: {{ $practicability->foreground() }}">{{ $practicability->getLabel() }}</span>
            @endif
        </div>
        @if ($url !== null)
            <a class="ms-cai-map-popup__link" href="{{ $url }}">Apri scheda →</a>
        @endif
    </div>
</div>
