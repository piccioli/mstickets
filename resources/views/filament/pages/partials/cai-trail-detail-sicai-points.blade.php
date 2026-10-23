{{-- "Punti di appoggio vicini" della scheda di una tappa SICAI (US-1096), design tappe-sicai-del-gr.dc.html §"Scheda della
     tappa": striscia della tappa con i punti alla loro posizione, poi tabella (desktop) o card (mobile) in ordine di
     percorrenza; stato vuoto con il rimando alle tappe adiacenti. Mai i dati personali di `fields`. --}}
@use('App\Filament\Pages\CaiTrailDetail')
@use('App\Filament\Pages\RegionalGroupSicaiStages')
@use('App\Support\Formatting\ItalianNumberFormatter')
@php
    $nearPoints = $this->sicaiNearPoints();
    $empty = $nearPoints === [] ? $this->sicaiNoNearPoints() : null;
    $strip = $nearPoints !== [] && $nearPoints[0]['percent'] !== null;
    $lineColor = $trail->practicability->dot();
@endphp
<section class="ms-trail__card ms-trail-sicai__points" aria-labelledby="trail-sicai-points">
    <div class="ms-trail-sicai__points-head">
        <h2 id="trail-sicai-points" class="ms-trail__card-title">Punti di appoggio vicini</h2>
        <span class="ms-trail-sicai__points-count"><span class="ms-trail__desktop-inline">{{ CaiTrailDetail::sicaiNearCountLabel(count($nearPoints)) }}</span><span class="ms-trail__mobile-inline">{{ CaiTrailDetail::sicaiNearCountLabel(count($nearPoints), short: true) }}</span></span>
    </div>

    @if ($empty !== null)
        <div class="ms-trail-sicai__empty" role="status">
            <strong>{{ $empty['title'] }}</strong>
            <span>
                {{ $empty['text'] }}@if ($empty['adjacent'] !== []) ({!! collect($empty['adjacent'])->map(fn ($adjacent): string => '<a href="'.e($this->sicaiStageUrl($adjacent)).'">'.e($adjacent->code()).'</a>')->implode(', ') !!}).@endif
            </span>
        </div>
    @else
        @if ($strip)
            <div class="ms-trail-sicai__strip ms-trail__desktop" aria-hidden="true">
                <div class="ms-trail-sicai__strip-track">
                    <span class="ms-trail-sicai__strip-line" style="background: {{ $lineColor }}"></span>
                    <span class="ms-trail-sicai__strip-end ms-trail-sicai__strip-end--start"></span>
                    <span class="ms-trail-sicai__strip-end ms-trail-sicai__strip-end--end"></span>
                    @foreach ($nearPoints as $near)
                        <span class="ms-trail-sicai__strip-point" title="{{ $near['point']->name }}" style="left: {{ $near['percent'] }}%; background: {{ $near['type']->color() }}">
                            <svg viewBox="0 0 24 24"><path d="{{ $near['type']->icon() }}"></path></svg>
                        </span>
                    @endforeach
                    <span class="ms-trail-sicai__strip-label ms-trail-sicai__strip-label--start">{{ $start ?? '—' }} · km 0</span>
                    <span class="ms-trail-sicai__strip-label ms-trail-sicai__strip-label--end">{{ $end ?? '—' }} · km {{ $trail->length_km === null ? '—' : ItalianNumberFormatter::decimal((float) $trail->length_km, 1) }}</span>
                </div>
            </div>
        @endif

        <div class="ms-trail-sicai__table ms-trail__desktop" role="table" aria-label="Punti di appoggio vicini">
            <div class="ms-trail-sicai__grid ms-trail-sicai__table-head" role="row">
                <span role="columnheader">Al km</span>
                <span role="columnheader"><span class="sr-only">Tipo</span></span>
                <span role="columnheader">Nome</span>
                <span role="columnheader">Comune</span>
                <span role="columnheader">Gestore</span>
                <span role="columnheader">Contatti</span>
                <span role="columnheader"><span class="sr-only">Fonte</span></span>
            </div>
            @foreach ($nearPoints as $near)
                @php
                    $point = $near['point'];
                    $website = filled($point->website) ? (string) $point->website : null;
                    $hutUrl = $point->hutUrl();
                @endphp
                <div class="ms-trail-sicai__grid ms-trail-sicai__row" role="row">
                    <span role="cell" class="ms-trail-sicai__km">{{ $near['km'] }}</span>
                    <span role="cell"><span class="ms-trail-sicai__icon" style="background: {{ $near['type']->color() }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="{{ $near['type']->icon() }}"></path></svg></span></span>
                    <span role="cell" class="ms-trail-sicai__name-cell">
                        <span class="ms-trail-sicai__name">{{ $point->name }}</span>
                        <span class="ms-trail-sicai__secondary">{{ $near['type']->label() }} · {{ $near['distance'] }}</span>
                    </span>
                    <span role="cell" @class(['ms-trail__missing' => blank($point->municipality)])>{{ filled($point->municipality) ? $point->municipality : '—' }}</span>
                    <span role="cell" @class(['ms-trail__missing' => blank($point->operator)])>{{ filled($point->operator) ? $point->operator : '—' }}</span>
                    <span role="cell" class="ms-trail-sicai__contacts">
                        <span @class(['ms-trail__missing' => blank($point->phone)])>{{ filled($point->phone) ? $point->phone : '—' }}</span>
                        @if ($website !== null)
                            <a href="{{ RegionalGroupSicaiStages::websiteHref($website) }}" target="_blank" rel="noopener" class="ms-trail-sicai__website">{{ RegionalGroupSicaiStages::websiteLabel($website) }}</a>
                        @endif
                    </span>
                    <span role="cell" class="ms-trail-sicai__source">
                        @if ($hutUrl !== null)
                            <a href="{{ $hutUrl }}" target="_blank" rel="noopener">Vedi su rifugi.cai.it ↗</a>
                        @endif
                    </span>
                </div>
            @endforeach
        </div>

        <ul class="ms-trail-sicai__cards ms-trail__mobile">
            @foreach ($nearPoints as $near)
                @php
                    $point = $near['point'];
                    $website = filled($point->website) ? (string) $point->website : null;
                    $hutUrl = $point->hutUrl();
                @endphp
                <li class="ms-trail-sicai__card">
                    <span class="ms-trail-sicai__card-side">
                        <span class="ms-trail-sicai__icon ms-trail-sicai__icon--lg" style="background: {{ $near['type']->color() }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="{{ $near['type']->icon() }}"></path></svg></span>
                        <span class="ms-trail-sicai__card-km">km {{ $near['km'] }}</span>
                    </span>
                    <span class="ms-trail-sicai__card-body">
                        <span class="ms-trail-sicai__name">{{ $point->name }}</span>
                        <span class="ms-trail-sicai__secondary">{{ $near['type']->label() }} · {{ filled($point->municipality) ? $point->municipality : '—' }} · {{ $near['distance'] }}</span>
                        <span class="ms-trail-sicai__secondary"><span class="ms-trail-sicai__label">Gestore </span><strong @class(['ms-trail__missing' => blank($point->operator)])>{{ filled($point->operator) ? $point->operator : '—' }}</strong></span>
                        <span class="ms-trail-sicai__card-contacts">
                            <span @class(['ms-trail__missing' => blank($point->phone)])>{{ filled($point->phone) ? $point->phone : '—' }}</span>
                            @if ($website !== null)
                                <a href="{{ RegionalGroupSicaiStages::websiteHref($website) }}" target="_blank" rel="noopener" class="ms-trail-sicai__website">{{ RegionalGroupSicaiStages::websiteLabel($website) }}</a>
                            @endif
                        </span>
                        @if ($hutUrl !== null)
                            <a href="{{ $hutUrl }}" target="_blank" rel="noopener" class="ms-trail-sicai__tap">Vedi su rifugi.cai.it ↗</a>
                        @endif
                    </span>
                </li>
            @endforeach
        </ul>
    @endif
</section>
