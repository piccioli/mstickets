{{-- Contenuto del popup di un marker di x-cai.map (US-1033), come nel design "Rifugi di Sezione": foto facoltativa,
     nome, Sezione proprietaria facoltativa (elenco del GR, US-1042), pallino e tipo · dato (es. quota), pillola di stato facoltativa, link "Apri scheda →" facoltativo. Si rende in
     stringa (`view(...)->render()`, vedi CaiMapMarker::forHut) e la mappa lo inserisce come HTML del popup. --}}
@props([
    'title',
    'section' => null,
    'photo' => null,
    'color' => null,
    'typeLabel' => null,
    'meta' => null,
    'statusLabel' => null,
    'statusBackground' => null,
    'statusForeground' => null,
    'url' => null,
    'linkLabel' => 'Apri scheda',
])
<div class="ms-cai-map-popup">
    @if ($photo !== null)
        <img class="ms-cai-map-popup__photo" src="{{ $photo }}" alt="" loading="lazy" onerror="this.remove()">
    @endif
    <div class="ms-cai-map-popup__body">
        <div class="ms-cai-map-popup__title">{{ $title }}</div>
        @if ($section !== null)
            <div class="ms-cai-map-popup__section">{{ $section }}</div>
        @endif
        @if ($typeLabel !== null || $meta !== null)
            <div class="ms-cai-map-popup__type">
                @if ($color !== null)
                    <span class="ms-cai-map-popup__dot" style="background: {{ $color }}"></span>
                @endif
                {{ collect([$typeLabel, $meta])->filter()->implode(' · ') }}
            </div>
        @endif
        @if ($statusLabel !== null)
            <span class="ms-cai-map-popup__status" style="background: {{ $statusBackground }}; color: {{ $statusForeground }}">{{ $statusLabel }}</span>
        @endif
        @if ($url !== null)
            <a class="ms-cai-map-popup__link" href="{{ $url }}">{{ $linkLabel }} →</a>
        @endif
    </div>
</div>
