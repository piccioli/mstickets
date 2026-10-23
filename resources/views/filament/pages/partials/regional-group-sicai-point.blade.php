{{-- Punto di appoggio SICAI di "Tappe SICAI del GR" (US-1095): `layout` = `row` (riga della tabella desktop: icona, nome,
     comune, gestore, contatti, tappe vicine, "Vedi su rifugi.cai.it") o `card` (card mobile). `type` =
     CaiSicaiRestPointType, `pointRow` = CaiRegionalGroupSicaiRestPointRow, `stageIds` = codice → id del sentiero della tappa.
     Mai i dati personali di `fields` (nome, codice fiscale, PEC del gestore). --}}
@use('App\Filament\Pages\RegionalGroupSicaiStages')
@php
    $point = $pointRow->point;
    $website = filled($point->website) ? (string) $point->website : null;
    $hutUrl = $point->hutUrl();
    $icon = '<span class="ms-gr-sicai__type-icon'.($layout === 'card' ? ' ms-gr-sicai__type-icon--lg' : '').'" style="background: '.e($type->color()).'"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="'.e($type->icon()).'"></path></svg></span>';
@endphp
@if ($layout === 'row')
    <div class="ms-gr-sicai__point-grid ms-gr-sicai__point-row" role="row" wire:key="point-{{ $point->id }}">
        <span role="cell">{!! $icon !!}</span>
        <span role="cell" class="ms-gr-sicai__point-name">{{ $point->name }}</span>
        <span role="cell" @class(['ms-gr-sicai__small', 'ms-gr-sicai__missing' => blank($point->municipality)])>{{ filled($point->municipality) ? $point->municipality : '—' }}</span>
        <span role="cell" @class(['ms-gr-sicai__small', 'ms-gr-sicai__missing' => blank($point->operator)])>{{ filled($point->operator) ? $point->operator : '—' }}</span>
        <span role="cell" class="ms-gr-sicai__contacts">
            <span @class(['ms-gr-sicai__numeric-text', 'ms-gr-sicai__missing' => blank($point->phone)])>{{ filled($point->phone) ? $point->phone : '—' }}</span>
            @if ($website !== null)
                <a href="{{ RegionalGroupSicaiStages::websiteHref($website) }}" target="_blank" rel="noopener" class="ms-gr-sicai__website">{{ RegionalGroupSicaiStages::websiteLabel($website) }}</a>
            @else
                <span class="ms-gr-sicai__missing">—</span>
            @endif
        </span>
        <span role="cell" class="ms-gr-sicai__stage-links">
            @foreach ($pointRow->stageCodes as $code)
                @isset($stageIds[$code])
                    <a href="{{ RegionalGroupSicaiStages::stageUrl($stageIds[$code]) }}" class="ms-gr-sicai__stage-link">{{ $code }}</a>
                @endisset
            @endforeach
        </span>
        <span role="cell" class="ms-gr-sicai__point-source">
            @if ($hutUrl !== null)
                <a href="{{ $hutUrl }}" target="_blank" rel="noopener" class="ms-gr-sicai__link">Vedi su rifugi.cai.it ↗</a>
            @endif
        </span>
    </div>
@else
    <div class="ms-gr-sicai__card ms-gr-sicai__point-card" wire:key="point-card-{{ $point->id }}">
        {!! $icon !!}
        <div class="ms-gr-sicai__point-card-body">
            <span class="ms-gr-sicai__point-name">{{ $point->name }}</span>
            <span class="ms-gr-sicai__secondary">{{ $type->label() }} · {{ filled($point->municipality) ? $point->municipality : '—' }}</span>
            <span class="ms-gr-sicai__secondary-dark"><span class="ms-gr-sicai__label">Gestore </span><span @class(['ms-gr-sicai__strong', 'ms-gr-sicai__missing' => blank($point->operator)])>{{ filled($point->operator) ? $point->operator : '—' }}</span></span>
            <span class="ms-gr-sicai__point-card-contacts">
                <span @class(['ms-gr-sicai__missing' => blank($point->phone)])>{{ filled($point->phone) ? $point->phone : '—' }}</span>
                @if ($website !== null)
                    <a href="{{ RegionalGroupSicaiStages::websiteHref($website) }}" target="_blank" rel="noopener" class="ms-gr-sicai__website">{{ RegionalGroupSicaiStages::websiteLabel($website) }}</a>
                @endif
            </span>
            @if ($pointRow->stageCodes !== [])
                <span class="ms-gr-sicai__stage-links">
                    <span class="ms-gr-sicai__label">Tappe</span>
                    @foreach ($pointRow->stageCodes as $code)
                        @isset($stageIds[$code])
                            <a href="{{ RegionalGroupSicaiStages::stageUrl($stageIds[$code]) }}" class="ms-gr-sicai__stage-link">{{ $code }}</a>
                        @endisset
                    @endforeach
                </span>
            @endif
            @if ($hutUrl !== null)
                <a href="{{ $hutUrl }}" target="_blank" rel="noopener" class="ms-gr-sicai__link ms-gr-sicai__link--tap">Vedi su rifugi.cai.it ↗</a>
            @endif
        </div>
    </div>
@endif
