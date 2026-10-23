{{-- Legenda della mappa dei settori (US-1064): `areas` = scala dei km consolidati + "Settore senza sentieri", `lines` =
     stati di accatastamento. Inclusa da trail-sectors-map (sopra la mappa su desktop, sotto su mobile). --}}
@use('App\Domain\CaiDirectory\Support\CaiMapArea')
@if ($kind === 'areas')
    <div class="ms-trail-sectors-map__legend-title">Km consolidati (stati 3–4) per settore</div>
    <div class="ms-trail-sectors-map__ramp">
        @foreach ($ramp as $band)
            <div class="ms-trail-sectors-map__ramp-band">
                <span style="background: {{ $band['color'] }}"></span>
                {{ $band['label'] }}
            </div>
        @endforeach
    </div>
    <div class="ms-trail-sectors-map__legend-item">
        <span class="ms-trail-sectors-map__empty-swatch" style="background: {{ CaiMapArea::EMPTY_FILL }}; border-color: {{ CaiMapArea::EMPTY_STROKE }}"></span>
        Settore senza sentieri
    </div>
@else
    <div class="ms-trail-sectors-map__legend-title">Stato di accatastamento</div>
    <div class="ms-trail-sectors-map__statuses">
        @foreach ($statuses as $status)
            <div class="ms-trail-sectors-map__legend-item">
                <span class="ms-trail-sectors-map__line-swatch" style="background: {{ $status->color() }}"></span>
                {{ $status->getLabel() }}
            </div>
        @endforeach
    </div>
@endif
