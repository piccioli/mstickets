{{-- Anteprima di un sentiero sulla mappa (US-1053), come nel design "Sentieri di Sezione": segnavia con il numero, nome
     (grigio "—" se assente), partenza → arrivo, pillola dello stato di accatastamento, pillola della percorribilità, km,
     link "Apri scheda →" facoltativo. Si rende in stringa (CaiMapLine::forTrail) e la mappa lo usa come HTML del popup. --}}
@props([
    'ref' => null,
    'name' => null,
    'route' => null,
    'statusLabel' => null,
    'statusColor' => null,
    'practicabilityLabel' => null,
    'practicabilityBackground' => null,
    'practicabilityForeground' => null,
    'km' => null,
    'url' => null,
])
<div class="ms-cai-map-popup ms-cai-map-popup--trail">
    <div class="ms-cai-map-popup__body">
        <x-cai.trail-sign :ref="$ref" size="sm" />
        <div class="ms-cai-map-popup__heading">
            <div @class(['ms-cai-map-popup__title', 'ms-cai-map-popup__title--missing' => blank($name)])>{{ filled($name) ? $name : '—' }}</div>
            @if ($route !== null)
                <div class="ms-cai-map-popup__route">{{ $route }}</div>
            @endif
        </div>
        <div class="ms-cai-map-popup__pills">
            @if ($statusLabel !== null)
                <span class="ms-cai-trail-status ms-cai-trail-status--sm" style="--ms-cai-trail-status-color: {{ $statusColor }}">{{ $statusLabel }}</span>
            @endif
            @if ($practicabilityLabel !== null)
                <span class="ms-cai-map-popup__status" style="background: {{ $practicabilityBackground }}; color: {{ $practicabilityForeground }}">{{ $practicabilityLabel }}</span>
            @endif
        </div>
        @if ($km !== null)
            <div class="ms-cai-map-popup__km">{{ $km }}</div>
        @endif
        @if ($url !== null)
            <a class="ms-cai-map-popup__link" href="{{ $url }}">Apri scheda →</a>
        @endif
    </div>
</div>
