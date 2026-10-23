{{-- Sentieri del GR (Fase 10.7, US-1065), design docs/project/design/fase-10/sentieri-del-gr.dc.html. Stili in
     resources/css/filament/admin/regional-group-trails.css (classi ms-gr-trails__*); segnavia, pillola dello stato e mappa
     per settori in cai-trails.css, pillola della percorribilità in section-trails.css (ms-trails__practicability).
     La mappa è il widget App\Filament\Widgets\RegionalGroupTrailsMap (componente Livewire figlio, sempre reso fuori dagli
     @if): filtri e pagine dell'elenco non rispediscono i settori. Tabelle desktop e card mobile sono rese entrambe, il CSS
     mostra le une o le altre. "Elenco sentieri →" di un settore (tabella o mappa, evento cai-map-area-listed) filtra
     l'elenco; "Sulla mappa" seleziona il settore (cai-map-select). --}}
@use('App\Domain\CaiDirectory\Enums\CaiTrailPracticability')
@use('App\Domain\CaiDirectory\Enums\CaiTrailStatus')
@use('App\Domain\CaiDirectory\Support\CaiRegionalGroupTrailFilters')
@use('App\Filament\Pages\RegionalGroupTrails')
@use('App\Filament\Pages\SectionTrails')
@use('App\Support\Formatting\ItalianNumberFormatter')
@use('App\Filament\Widgets\RegionalGroupTrailsMap')
@php
    $data = $this->data();
    $hasData = $data !== null && $data->regionId !== null;
    $mapName = RegionalGroupTrails::MAP_NAME;
    $statuses = CaiTrailStatus::cases();
    $sharedIcon = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM2 21v-1a6 6 0 0 1 12 0v1M16 3.5a4 4 0 0 1 0 7M22 21v-1a6 6 0 0 0-4-5.6"></path></svg>';
    $scrollTo = fn (string $id): string => "document.getElementById('{$id}')?.scrollIntoView({ behavior: 'smooth', block: 'start' })";
@endphp
<x-filament-panels::page>
    <div
        class="ms-gr-trails"
        x-data="{ area: null }"
        x-on:cai-map-area-selected.window="if ($event.detail.map === @js($mapName)) area = $event.detail.id"
        x-on:cai-map-area-listed.window="if ($event.detail.map === @js($mapName)) { $wire.listSector($event.detail.id); {{ $scrollTo('gr-trails-list') }} }"
    >
        <div class="ms-gr-trails__top">
            <a href="{{ $this->dashboardUrl() }}" class="ms-gr-trails__back">← Dashboard</a>
            <div class="ms-gr-trails__heading">
                <header class="ms-gr-trails__title-block">
                    <h1 class="ms-gr-trails__title">Sentieri del GR</h1>
                    @if ($data !== null)
                        <p class="ms-gr-trails__subtitle">{{ $this->subtitle($data) }}</p>
                    @endif
                </header>
                <p class="ms-gr-trails__source">Fonte: <a href="https://osm2cai.cai.it" target="_blank" rel="noopener">osm2cai</a> · catasto sentieri CAI</p>
            </div>
        </div>

        @if ($data === null)
            <p class="ms-section-dashboard__notice" role="status">
                Nessuna regione associata al tuo account: contatta Montagna Servizi.
            </p>
        @elseif (! $hasData)
            <p class="ms-section-dashboard__notice" role="status">
                Nessun dato del catasto osm2cai per la regione del {{ $data->groupName }}: contatta Montagna Servizi.
            </p>
        @else
            @php
                $summary = $this->summary($data);
                $statusSummary = $this->statusSummary($data);
                $practicabilitySummary = $this->practicabilitySummary($data);
            @endphp
            {{-- ---- Riepilogo regionale ---- --}}
            <section class="ms-gr-trails__block" aria-labelledby="gr-trails-summary">
                <h2 id="gr-trails-summary" class="ms-gr-trails__block-title ms-gr-trails__desktop">Riepilogo regionale</h2>
                <div class="ms-gr-trails__kpis">
                    <div class="ms-gr-trails__card ms-gr-trails__kpi">
                        <span class="ms-gr-trails__kpi-label"><span class="ms-gr-trails__kpi-swatch ms-gr-trails__kpi-swatch--territory"></span>Km nel territorio regionale</span>
                        <span class="ms-gr-trails__kpi-value">{{ $summary['territoryKm'] }} <span class="ms-gr-trails__kpi-unit">km</span></span>
                        <span class="ms-gr-trails__kpi-text ms-gr-trails__desktop">Tutti i sentieri del catasto tagliati sul confine della regione, compresi quelli senza una Sezione.</span>
                        <span class="ms-gr-trails__kpi-text ms-gr-trails__mobile">Tutti i sentieri tagliati sul confine della regione, anche quelli senza una Sezione.</span>
                    </div>
                    <div class="ms-gr-trails__card ms-gr-trails__kpi">
                        <span class="ms-gr-trails__kpi-label ms-gr-trails__kpi-label--managed"><span class="ms-gr-trails__kpi-swatch ms-gr-trails__kpi-swatch--managed"></span>Km gestiti dalle Sezioni del GR</span>
                        <span class="ms-gr-trails__kpi-value">{{ $summary['managedKm'] }} <span class="ms-gr-trails__kpi-unit">km</span></span>
                        <span class="ms-gr-trails__kpi-text ms-gr-trails__desktop">Sentieri assegnati ad almeno una Sezione. Un sentiero condiviso è contato una sola volta.</span>
                        <span class="ms-gr-trails__kpi-text ms-gr-trails__mobile">Sentieri assegnati alle Sezioni, ognuno contato una volta anche se condiviso.</span>
                        <span class="ms-gr-trails__managed-bar" aria-hidden="true"><span style="width: {{ $summary['managedWidth'] }}%"></span></span>
                        <span class="ms-gr-trails__managed-note">
                            {{ $summary['managedShare'] }} <span class="ms-gr-trails__desktop">dei km nel territorio</span><span class="ms-gr-trails__mobile">del territorio</span>
                            · {{ $summary['withoutSectionKm'] }} km senza Sezione
                        </span>
                    </div>
                    <div class="ms-gr-trails__card ms-gr-trails__kpi ms-gr-trails__kpi--present">
                        <div class="ms-gr-trails__present-text">
                            <span class="ms-gr-trails__kpi-label">Sentieri presenti</span>
                            <span class="ms-gr-trails__present-mobile ms-gr-trails__mobile">{{ $summary['withoutSection'] }} senza Sezione · {{ $summary['shared'] }} condivisi</span>
                        </div>
                        <span class="ms-gr-trails__kpi-value">{{ $summary['present'] }}</span>
                        <span class="ms-gr-trails__present-list ms-gr-trails__desktop">
                            <span>{{ $summary['withoutSection'] }} senza Sezione</span>
                            <span>{{ $summary['shared'] }} condivisi tra più Sezioni</span>
                            <span>{{ $summary['provinces'] }} {{ $summary['provinces'] === 1 ? 'provincia' : 'province' }} · {{ $summary['sectors'] }} {{ $summary['sectors'] === 1 ? 'settore' : 'settori' }}</span>
                        </span>
                    </div>
                </div>

                <div class="ms-gr-trails__card ms-gr-trails__status">
                    <div class="ms-gr-trails__status-main">
                        <div class="ms-gr-trails__status-heading">
                            <h3 class="ms-gr-trails__card-title">Km per stato di accatastamento</h3>
                            <span class="ms-gr-trails__hint ms-gr-trails__desktop">km nel territorio regionale · da 1 · Difficoltà a 4 · Validato</span>
                        </div>
                        <div class="ms-gr-trails__bar" role="img" aria-label="Km nel territorio regionale per stato di accatastamento">
                            @foreach ($statusSummary['segments'] as $segment)
                                <div class="ms-gr-trails__bar-segment" title="{{ $segment['title'] }}" style="width: {{ $segment['width'] }}%; background: {{ $segment['status']->color() }}; color: {{ $segment['status']->foreground() }}"><span class="ms-gr-trails__desktop">{{ $segment['label'] }}</span></div>
                            @endforeach
                        </div>
                        <div class="ms-gr-trails__bracket" aria-hidden="true">
                            <span style="width: {{ $statusSummary['consolidatedOffset'] }}%"></span>
                            <span class="ms-gr-trails__bracket-line" style="width: {{ $statusSummary['consolidatedWidth'] }}%"></span>
                        </div>
                        <p class="ms-gr-trails__consolidated">
                            <span class="ms-gr-trails__desktop">Sentieri consolidati (stati 3–4): {{ $statusSummary['consolidatedShare'] }} <span class="ms-gr-trails__muted">· {{ $statusSummary['consolidatedKm'] }} km</span></span>
                            <span class="ms-gr-trails__mobile">Consolidati (3–4): {{ $statusSummary['consolidatedShare'] }}</span>
                        </p>
                        <ul class="ms-gr-trails__legend">
                            @foreach ($statusSummary['segments'] as $segment)
                                <li class="ms-gr-trails__legend-entry">
                                    <span class="ms-gr-trails__legend-label"><span class="ms-gr-trails__line-swatch" style="background: {{ $segment['status']->color() }}"></span>{{ $segment['status']->getLabel() }}</span>
                                    <span class="ms-gr-trails__legend-value">{{ $segment['km'] }} km <span class="ms-gr-trails__legend-note">{{ $segment['share'] }}<span class="ms-gr-trails__desktop"> · {{ $segment['count'] }}</span></span></span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                    <div class="ms-gr-trails__practicability">
                        <h3 class="ms-gr-trails__card-title ms-gr-trails__desktop">Percorribilità</h3>
                        <ul class="ms-gr-trails__practicability-grid">
                            @foreach ($practicabilitySummary as $item)
                                <li class="ms-gr-trails__legend-entry">
                                    <span class="ms-gr-trails__legend-label"><span class="ms-gr-trails__dot" style="background: {{ $item['practicability']->dot() }}"></span>{{ $item['practicability']->getLabel() }}</span>
                                    <span class="ms-gr-trails__legend-value">{{ $item['km'] }} km <span class="ms-gr-trails__legend-note ms-gr-trails__desktop">{{ $item['count'] }}</span></span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </section>
        @endif

        {{-- ---- Mappa (sempre resa: componente figlio) ---- --}}
        <section id="gr-trails-map" @class(['ms-gr-trails__block', 'ms-gr-trails__block--hidden' => ! $hasData]) aria-labelledby="gr-trails-map-title">
            <div class="ms-gr-trails__block-heading">
                <h2 id="gr-trails-map-title" class="ms-gr-trails__block-title">Mappa</h2>
                <span class="ms-gr-trails__hint ms-gr-trails__desktop">Trascina per spostarti · doppio clic o + per zoomare · clic su un settore o su un sentiero per i dettagli</span>
            </div>
            @livewire(RegionalGroupTrailsMap::class, [], key('gr-trails-map'))
        </section>

        @if ($hasData)
            {{-- ---- Per provincia ---- --}}
            <section id="gr-trails-provinces" class="ms-gr-trails__block" aria-labelledby="gr-trails-provinces-title">
                <div class="ms-gr-trails__block-heading">
                    <h2 id="gr-trails-provinces-title" class="ms-gr-trails__block-title">Per provincia</h2>
                    <span class="ms-gr-trails__hint ms-gr-trails__desktop">Km nel territorio regionale per stato di accatastamento · clic su una provincia per i settori</span>
                    <span class="ms-gr-trails__hint ms-gr-trails__mobile">tocca per i settori</span>
                </div>

                {{-- Desktop --}}
                <div class="ms-gr-trails__card ms-gr-trails__table ms-gr-trails__provinces" role="table" aria-label="Km per provincia">
                    <div class="ms-gr-trails__prov-grid ms-gr-trails__table-head" role="row">
                        <span role="columnheader">Provincia</span>
                        <span role="columnheader">Settori</span>
                        <span role="columnheader" class="ms-gr-trails__numeric">Sentieri</span>
                        @foreach ($statuses as $status)
                            <span role="columnheader" class="ms-gr-trails__numeric ms-gr-trails__status-head"><span class="ms-gr-trails__line-swatch ms-gr-trails__line-swatch--sm" style="background: {{ $status->color() }}"></span>{{ $status->value }}</span>
                        @endforeach
                        <span role="columnheader" class="ms-gr-trails__numeric">Totale km</span>
                        <span role="columnheader" class="ms-gr-trails__cons-head">Consolidati (3–4)</span>
                        <span role="columnheader"></span>
                    </div>

                    @forelse ($data->provinces as $province)
                        @php
                            $open = $this->isOpen($province);
                        @endphp
                        <div class="ms-gr-trails__prov" wire:key="province-{{ $province->id }}">
                            <button
                                type="button"
                                wire:click="toggleProvince({{ $province->id }})"
                                aria-expanded="{{ $open ? 'true' : 'false' }}"
                                @class(['ms-gr-trails__prov-grid', 'ms-gr-trails__prov-row', 'ms-gr-trails__prov-row--open' => $open])
                            >
                                <span class="ms-gr-trails__prov-name"><span class="ms-gr-trails__prov-code">{{ $province->code }}</span><span>{{ $province->name }}</span></span>
                                <span class="ms-gr-trails__prov-sectors">
                                    {{ $province->sectorsCount() }}
                                    @if ($province->emptySectorsCount() > 0)
                                        <span class="ms-gr-trails__empty-pill">{{ RegionalGroupTrails::emptySectorsLabel($province) }}</span>
                                    @endif
                                </span>
                                <span class="ms-gr-trails__numeric ms-gr-trails__strong">{{ ItalianNumberFormatter::integer($province->stats->trailsCount()) }}</span>
                                @foreach (RegionalGroupTrails::kmCells($province->stats) as $cell)
                                    <span @class(['ms-gr-trails__numeric', 'ms-gr-trails__zero' => $cell === null])>{{ $cell ?? '—' }}</span>
                                @endforeach
                                <span class="ms-gr-trails__numeric ms-gr-trails__bold">{{ RegionalGroupTrails::kmRounded($province->stats->km()) }}</span>
                                <span class="ms-gr-trails__cons">
                                    <span class="ms-gr-trails__mini-bar">
                                        @foreach (RegionalGroupTrails::bar($province->stats) as $piece)
                                            <span style="width: {{ $piece['width'] }}%; background: {{ $piece['color'] }}"></span>
                                        @endforeach
                                    </span>
                                    <span class="ms-gr-trails__cons-value">{{ RegionalGroupTrails::consolidated($province->stats) }}</span>
                                </span>
                                <span class="ms-gr-trails__chevron" aria-hidden="true">{{ $open ? '▾' : '▸' }}</span>
                            </button>

                            @if ($open)
                                <div class="ms-gr-trails__sectors">
                                    <div class="ms-gr-trails__sectors-heading">
                                        <h3 class="ms-gr-trails__sectors-title">Settori della provincia di {{ $province->name }}</h3>
                                        @if ($province->emptySectorsCount() > 0)
                                            <span class="ms-gr-trails__sectors-note">{{ RegionalGroupTrails::emptySectorsNote($province) }}</span>
                                        @endif
                                    </div>
                                    <div class="ms-gr-trails__sector-grid ms-gr-trails__sector-head">
                                        <span>Codice</span><span>Settore</span><span class="ms-gr-trails__numeric">Sentieri</span><span class="ms-gr-trails__numeric">Km</span><span>Km per stato</span><span class="ms-gr-trails__numeric">3–4</span><span></span>
                                    </div>
                                    @forelse ($province->sectors as $sector)
                                        @php
                                            $areaId = RegionalGroupTrails::mapAreaId($sector);
                                        @endphp
                                        <div
                                            wire:key="sector-{{ $sector->id }}"
                                            @class(['ms-gr-trails__sector-grid', 'ms-gr-trails__sector', 'ms-gr-trails__sector--empty' => ! $sector->hasTrails])
                                            x-bind:class="{ 'ms-gr-trails__sector--selected': area === @js($areaId) }"
                                        >
                                            <span class="ms-gr-trails__sector-code">{{ RegionalGroupTrails::sectorCode($sector) }}</span>
                                            <span @class(['ms-gr-trails__sector-name', 'ms-gr-trails__missing' => RegionalGroupTrails::sectorName($sector) === null])>{{ RegionalGroupTrails::sectorName($sector) ?? '—' }}</span>
                                            <span class="ms-gr-trails__numeric ms-gr-trails__sector-value">{{ $sector->hasTrails ? ItalianNumberFormatter::integer($sector->stats->trailsCount()) : '0' }}</span>
                                            <span class="ms-gr-trails__numeric ms-gr-trails__sector-value">{{ $sector->hasTrails ? RegionalGroupTrails::km($sector->stats->km()) : '0' }}</span>
                                            @if ($sector->hasTrails)
                                                <span class="ms-gr-trails__mini-bar">
                                                    @foreach (RegionalGroupTrails::bar($sector->stats) as $piece)
                                                        <span style="width: {{ $piece['width'] }}%; background: {{ $piece['color'] }}"></span>
                                                    @endforeach
                                                </span>
                                            @else
                                                <span class="ms-gr-trails__no-trails">Nessun sentiero censito</span>
                                            @endif
                                            <span class="ms-gr-trails__numeric ms-gr-trails__sector-value ms-gr-trails__bold">{{ $sector->hasTrails ? RegionalGroupTrails::consolidated($sector->stats) : '—' }}</span>
                                            <span class="ms-gr-trails__sector-actions">
                                                <button type="button" class="ms-gr-trails__link" x-on:click="$dispatch('cai-map-select', @js(['map' => $mapName, 'id' => $areaId])); {{ $scrollTo('gr-trails-map') }}">Sulla mappa</button>
                                                @if ($sector->hasTrails)
                                                    <button type="button" class="ms-gr-trails__link" wire:click="listSector({{ $sector->id }})" x-on:click="{{ $scrollTo('gr-trails-list') }}">Elenco sentieri →</button>
                                                @endif
                                            </span>
                                        </div>
                                    @empty
                                        <p class="ms-gr-trails__none">Nessun settore in questa provincia.</p>
                                    @endforelse
                                </div>
                            @endif
                        </div>
                    @empty
                        <p class="ms-gr-trails__none">Nessuna provincia nei dati osm2cai della regione.</p>
                    @endforelse

                    <div class="ms-gr-trails__prov-grid ms-gr-trails__table-total" role="row">
                        <span class="ms-gr-trails__total-label">Totale regione</span>
                        <span>{{ $data->sectorsCount() }}</span>
                        <span class="ms-gr-trails__numeric">{{ $summary['present'] }}</span>
                        @foreach ($statusSummary['segments'] as $segment)
                            <span class="ms-gr-trails__numeric">{{ $segment['km'] }}</span>
                        @endforeach
                        <span class="ms-gr-trails__numeric">{{ $summary['territoryKm'] }}</span>
                        <span class="ms-gr-trails__cons-total">{{ $statusSummary['consolidatedShare'] }}</span>
                        <span></span>
                    </div>
                </div>

                {{-- Mobile --}}
                <ul class="ms-gr-trails__cards" aria-label="Km per provincia">
                    @foreach ($data->provinces as $province)
                        @php
                            $open = $this->isOpen($province);
                        @endphp
                        <li wire:key="province-card-{{ $province->id }}" @class(['ms-gr-trails__card', 'ms-gr-trails__prov-card', 'ms-gr-trails__prov-card--open' => $open])>
                            <button type="button" class="ms-gr-trails__prov-card-button" wire:click="toggleProvince({{ $province->id }})" aria-expanded="{{ $open ? 'true' : 'false' }}">
                                <span class="ms-gr-trails__prov-card-heading">
                                    <span class="ms-gr-trails__prov-card-name">{{ $province->name }}</span>
                                    <span class="ms-gr-trails__prov-card-km">{{ RegionalGroupTrails::kmRounded($province->stats->km()) }} km <span class="ms-gr-trails__chevron">{{ $open ? '▾' : '▸' }}</span></span>
                                </span>
                                <span class="ms-gr-trails__mini-bar">
                                    @foreach (RegionalGroupTrails::bar($province->stats) as $piece)
                                        <span style="width: {{ $piece['width'] }}%; background: {{ $piece['color'] }}"></span>
                                    @endforeach
                                </span>
                                <span class="ms-gr-trails__prov-card-meta">
                                    <span>
                                        {{ RegionalGroupTrails::trailsNoun($province->stats->trailsCount()) }} · {{ $province->sectorsCount() }} {{ $province->sectorsCount() === 1 ? 'settore' : 'settori' }}
                                        @if ($province->emptySectorsCount() > 0)
                                            <span class="ms-gr-trails__danger"> · {{ RegionalGroupTrails::emptySectorsLabel($province) }}</span>
                                        @endif
                                    </span>
                                    <span class="ms-gr-trails__bold">3–4: {{ RegionalGroupTrails::consolidated($province->stats) }}</span>
                                </span>
                            </button>
                            @if ($open)
                                <div class="ms-gr-trails__prov-card-sectors">
                                    @foreach ($province->sectors as $sector)
                                        @php
                                            $areaId = RegionalGroupTrails::mapAreaId($sector);
                                        @endphp
                                        <div wire:key="sector-card-{{ $sector->id }}" @class(['ms-gr-trails__sector-card', 'ms-gr-trails__sector--empty' => ! $sector->hasTrails])>
                                            <span class="ms-gr-trails__sector-card-body">
                                                <span class="ms-gr-trails__sector-card-name"><span class="ms-gr-trails__sector-code">{{ RegionalGroupTrails::sectorCode($sector) }}</span> {{ RegionalGroupTrails::sectorName($sector) }}</span>
                                                @if ($sector->hasTrails)
                                                    <span class="ms-gr-trails__sector-card-meta">{{ RegionalGroupTrails::trailsNoun($sector->stats->trailsCount()) }} · {{ RegionalGroupTrails::km($sector->stats->km()) }} km · 3–4: {{ RegionalGroupTrails::consolidated($sector->stats) }}</span>
                                                @else
                                                    <span class="ms-gr-trails__sector-card-meta ms-gr-trails__danger ms-gr-trails__bold">Nessun sentiero censito</span>
                                                @endif
                                            </span>
                                            <button type="button" class="ms-gr-trails__link" x-on:click="$dispatch('cai-map-select', @js(['map' => $mapName, 'id' => $areaId])); {{ $scrollTo('gr-trails-map') }}">Mappa</button>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </li>
                    @endforeach
                </ul>

                <p class="ms-gr-trails__footnote">Km dei tratti che ricadono nel territorio; un sentiero è contato nella provincia o nel settore in cui ricade per almeno metà del tracciato.</p>
            </section>

            {{-- ---- Per Sezione ---- --}}
            @php
                $sectionRows = $this->sectionRows($data);
                $mobileSectionRows = $this->showAllSections ? $sectionRows : array_slice($sectionRows, 0, RegionalGroupTrails::MOBILE_SECTIONS);
                $sectionsTotal = $this->sectionsTotal($data);
                $sectionArrow = fn (string $column): string => $this->sectionSort === $column ? ($this->sectionSortDescending ? '↓' : '↑') : '';
            @endphp
            <section id="gr-trails-sections" class="ms-gr-trails__block" aria-labelledby="gr-trails-sections-title">
                <div class="ms-gr-trails__block-heading">
                    <h2 id="gr-trails-sections-title" class="ms-gr-trails__block-title">Per Sezione</h2>
                    <span class="ms-gr-trails__hint ms-gr-trails__desktop">{{ $this->sectionsSubtitle($data) }} · clic su una Sezione per il suo elenco sentieri</span>
                </div>

                <div class="ms-gr-trails__info">
                    {!! $sharedIcon !!}
                    <div class="ms-gr-trails__info-text">
                        <strong class="ms-gr-trails__desktop">Un sentiero condiviso conta per ciascuna Sezione che lo gestisce</strong>
                        <strong class="ms-gr-trails__mobile">Un sentiero condiviso conta per ogni Sezione che lo gestisce</strong>
                        @if ($sectionsTotal['exceeds'])
                            <span class="ms-gr-trails__desktop">Per questo la somma dei km delle Sezioni ({{ $sectionsTotal['km'] }} km) supera i Km gestiti dalle Sezioni del GR ({{ $summary['managedKm'] }} km), dove ogni sentiero è contato una volta: la differenza di {{ $sectionsTotal['difference'] }} km viene dai sentieri condivisi.</span>
                            <span class="ms-gr-trails__mobile">Somma delle Sezioni {{ $sectionsTotal['km'] }} km, superiore ai {{ $summary['managedKm'] }} km gestiti dal GR.</span>
                        @else
                            <span>Nessun sentiero è condiviso fra le Sezioni del GR: la somma dei km delle Sezioni ({{ $sectionsTotal['km'] }} km) coincide con i Km gestiti dalle Sezioni del GR.</span>
                        @endif
                    </div>
                </div>

                <div class="ms-gr-trails__section-tools">
                    <input
                        type="search"
                        wire:model.live.debounce.300ms="sectionSearch"
                        placeholder="Cerca Sezione"
                        aria-label="Cerca Sezione"
                        class="ms-gr-trails__input ms-gr-trails__section-search"
                    >
                    <div class="ms-gr-trails__chips" role="group" aria-label="Filtra le Sezioni">
                        @foreach ($this->sectionChips($data) as $chip)
                            @php
                                $on = $this->sectionFilter === $chip['key'];
                            @endphp
                            <button type="button" wire:click="filterSections('{{ $chip['key'] }}')" aria-pressed="{{ $on ? 'true' : 'false' }}" @class(['ms-gr-trails__chip', 'ms-gr-trails__chip--active' => $on])>
                                <span class="ms-gr-trails__desktop">{{ $chip['label'] }} <span class="ms-gr-trails__chip-count">{{ $chip['count'] }}</span></span>
                                <span class="ms-gr-trails__mobile">{{ $chip['short'] }} ({{ $chip['count'] }})</span>
                            </button>
                        @endforeach
                    </div>
                </div>

                {{-- Desktop --}}
                <div class="ms-gr-trails__card ms-gr-trails__table ms-gr-trails__sections" role="table" aria-label="Sentieri per Sezione">
                    <div class="ms-gr-trails__section-grid ms-gr-trails__table-head" role="row">
                        <button type="button" role="columnheader" wire:click="sortSections('nome')" @class(['ms-gr-trails__sort', 'ms-gr-trails__sort--active' => $this->sectionSort === 'nome'])>Sezione {{ $sectionArrow('nome') }}</button>
                        <button type="button" role="columnheader" wire:click="sortSections('sentieri')" @class(['ms-gr-trails__sort', 'ms-gr-trails__numeric', 'ms-gr-trails__sort--active' => $this->sectionSort === 'sentieri'])>Sentieri {{ $sectionArrow('sentieri') }}</button>
                        <span role="columnheader" class="ms-gr-trails__numeric">di cui condivisi</span>
                        <button type="button" role="columnheader" wire:click="sortSections('km')" @class(['ms-gr-trails__sort', 'ms-gr-trails__numeric', 'ms-gr-trails__sort--active' => $this->sectionSort === 'km'])>Km gestiti {{ $sectionArrow('km') }}</button>
                        @foreach ($statuses as $status)
                            <span role="columnheader" class="ms-gr-trails__numeric ms-gr-trails__status-head"><span class="ms-gr-trails__line-swatch ms-gr-trails__line-swatch--sm" style="background: {{ $status->color() }}"></span>{{ $status->value }}</span>
                        @endforeach
                        <span role="columnheader"></span>
                    </div>
                    <div class="ms-gr-trails__section-rows">
                        @foreach ($sectionRows as $row)
                            @php
                                $hasTrails = $row->stats->trailsCount() > 0;
                            @endphp
                            <a href="{{ $this->sectionUrl($row) }}" wire:key="section-{{ $row->codiceCai }}" role="row" @class(['ms-gr-trails__section-grid', 'ms-gr-trails__section-row', 'ms-gr-trails__section-row--empty' => ! $hasTrails])>
                                <span class="ms-gr-trails__section-name">
                                    <span>{{ RegionalGroupTrails::sectionName($row) }}</span>
                                    @if (! $hasTrails)
                                        <span class="ms-gr-trails__neutral-pill">Nessun sentiero assegnato</span>
                                    @endif
                                </span>
                                <span class="ms-gr-trails__numeric ms-gr-trails__strong">{{ $hasTrails ? ItalianNumberFormatter::integer($row->stats->trailsCount()) : '0' }}</span>
                                <span class="ms-gr-trails__numeric">{{ $hasTrails ? ItalianNumberFormatter::integer($row->sharedCount) : '—' }}</span>
                                <span class="ms-gr-trails__numeric ms-gr-trails__bold">{{ $hasTrails ? RegionalGroupTrails::kmRounded($row->stats->km()) : '—' }}</span>
                                @foreach (RegionalGroupTrails::kmCells($row->stats) as $cell)
                                    <span @class(['ms-gr-trails__numeric', 'ms-gr-trails__zero' => $cell === null])>{{ $cell ?? '—' }}</span>
                                @endforeach
                                <span class="ms-gr-trails__row-chevron" aria-hidden="true">›</span>
                            </a>
                        @endforeach
                        @if ($sectionRows === [])
                            <p class="ms-gr-trails__none">{{ $this->noSectionsText($data) }}</p>
                        @endif
                    </div>
                    <div class="ms-gr-trails__section-grid ms-gr-trails__table-total" role="row">
                        <span>Somma delle Sezioni @if ($sectionsTotal['exceeds'])<span class="ms-gr-trails__total-note">· supera i km gestiti dal GR di {{ $sectionsTotal['difference'] }} km</span>@endif</span>
                        <span class="ms-gr-trails__numeric">{{ $sectionsTotal['trails'] }}</span>
                        <span class="ms-gr-trails__numeric">{{ $sectionsTotal['shared'] }}</span>
                        <span class="ms-gr-trails__numeric">{{ $sectionsTotal['km'] }}</span>
                        @foreach ($sectionsTotal['kmCells'] as $cell)
                            <span class="ms-gr-trails__numeric">{{ $cell }}</span>
                        @endforeach
                        <span></span>
                    </div>
                </div>

                {{-- Mobile --}}
                <ul class="ms-gr-trails__cards" aria-label="Sentieri per Sezione">
                    @foreach ($mobileSectionRows as $row)
                        @php
                            $hasTrails = $row->stats->trailsCount() > 0;
                        @endphp
                        <li wire:key="section-card-{{ $row->codiceCai }}">
                            <a href="{{ $this->sectionUrl($row) }}" class="ms-gr-trails__card ms-gr-trails__section-card">
                                <span class="ms-gr-trails__section-card-body">
                                    <span class="ms-gr-trails__section-card-heading">
                                        <span @class(['ms-gr-trails__bold', 'ms-gr-trails__muted' => ! $hasTrails])>{{ RegionalGroupTrails::sectionName($row) }}</span>
                                        <span @class(['ms-gr-trails__bold', 'ms-gr-trails__zero' => ! $hasTrails])>{{ $hasTrails ? RegionalGroupTrails::kmRounded($row->stats->km()).' km' : '0 km' }}</span>
                                    </span>
                                    @if ($hasTrails)
                                        <span class="ms-gr-trails__mini-bar">
                                            @foreach (RegionalGroupTrails::bar($row->stats) as $piece)
                                                <span style="width: {{ $piece['width'] }}%; background: {{ $piece['color'] }}"></span>
                                            @endforeach
                                        </span>
                                        <span class="ms-gr-trails__section-card-meta">{{ RegionalGroupTrails::trailsNoun($row->stats->trailsCount()) }} · {{ $row->sharedCount }} condivisi</span>
                                    @else
                                        <span class="ms-gr-trails__section-card-meta ms-gr-trails__bold">Nessun sentiero assegnato</span>
                                    @endif
                                </span>
                                <span class="ms-gr-trails__row-chevron" aria-hidden="true">›</span>
                            </a>
                        </li>
                    @endforeach
                    @if ($sectionRows === [])
                        <li class="ms-gr-trails__card ms-gr-trails__none">{{ $this->noSectionsText($data) }}</li>
                    @endif
                    @if (count($sectionRows) > count($mobileSectionRows))
                        <li>
                            <button type="button" wire:click="$set('showAllSections', true)" class="ms-gr-trails__more">Mostra tutte le {{ count($sectionRows) }} Sezioni</button>
                        </li>
                    @endif
                </ul>
            </section>

            {{-- ---- Elenco sentieri del GR ---- --}}
            @php
                $trails = $this->trails();
                $filters = $this->filters();
                $activeSort = $filters->sort;
                $sortArrow = fn (string $column): string => $activeSort->column() === $column ? ($activeSort->isDescending() ? '↓' : '↑') : '';
                $ariaSort = fn (string $column): string => $activeSort->column() === $column ? ($activeSort->isDescending() ? 'descending' : 'ascending') : 'none';
                $countLabel = $this->countLabel($data);
                $filtering = $filters->isFiltering();
            @endphp
            <section id="gr-trails-list" class="ms-gr-trails__block" aria-labelledby="gr-trails-list-title">
                <div class="ms-gr-trails__block-heading">
                    <h2 id="gr-trails-list-title" class="ms-gr-trails__block-title">Elenco sentieri del GR</h2>
                    <span class="ms-gr-trails__hint ms-gr-trails__desktop">Clic su un sentiero per aprire la scheda</span>
                </div>

                <div class="ms-gr-trails__card ms-gr-trails__list">
                    <div class="ms-gr-trails__filters">
                        <input
                            type="search"
                            wire:model.live.debounce.400ms="search"
                            placeholder="Cerca per numero o nome"
                            aria-label="Cerca per numero o nome"
                            class="ms-gr-trails__input ms-gr-trails__search"
                        >
                        <div class="ms-gr-trails__selects">
                            <select wire:change="filterProvince($event.target.value)" aria-label="Filtra per provincia" @class(['ms-gr-trails__select', 'ms-gr-trails__select--active' => $filters->provinceId !== null])>
                                <option value="">Tutte le province</option>
                                @foreach ($this->provinceOptions($data) as $id => $label)
                                    <option value="{{ $id }}" @selected($filters->provinceId === $id)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <select wire:change="filterSector($event.target.value)" aria-label="Filtra per settore" @class(['ms-gr-trails__select', 'ms-gr-trails__select--wide', 'ms-gr-trails__select--active' => $filters->sectorId !== null])>
                                <option value="">Tutti i settori</option>
                                @foreach ($this->sectorOptions($data) as $id => $label)
                                    <option value="{{ $id }}" @selected($filters->sectorId === $id)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <select wire:change="filterSection($event.target.value)" aria-label="Filtra per Sezione" @class(['ms-gr-trails__select', 'ms-gr-trails__select--wide', 'ms-gr-trails__select--active' => $filters->section !== null])>
                                <option value="">Tutte le Sezioni</option>
                                <option value="{{ CaiRegionalGroupTrailFilters::WITHOUT_SECTION }}" @selected($filters->section === CaiRegionalGroupTrailFilters::WITHOUT_SECTION)>Nessuna Sezione</option>
                                @foreach ($this->sectionOptions($data) as $code => $label)
                                    <option value="{{ $code }}" @selected($filters->section === (string) $code)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <select wire:change="filterStatus($event.target.value)" aria-label="Filtra per stato di accatastamento" @class(['ms-gr-trails__select', 'ms-gr-trails__select--active' => $filters->status !== null])>
                                <option value="">Tutti gli stati</option>
                                @foreach ($statuses as $status)
                                    <option value="{{ $status->value }}" @selected($filters->status === $status)>{{ $status->getLabel() }}</option>
                                @endforeach
                            </select>
                            <select wire:change="filterPracticability($event.target.value)" aria-label="Filtra per percorribilità" @class(['ms-gr-trails__select', 'ms-gr-trails__select--full', 'ms-gr-trails__select--active' => $filters->practicability !== null])>
                                <option value="">Tutte le percorribilità</option>
                                @foreach (CaiTrailPracticability::cases() as $practicability)
                                    <option value="{{ $practicability->key() }}" @selected($filters->practicability === $practicability)>{{ $practicability->getLabel() }}</option>
                                @endforeach
                            </select>
                        </div>
                        @if ($filtering)
                            <button type="button" wire:click="resetFilters" class="ms-gr-trails__link ms-gr-trails__reset ms-gr-trails__desktop">Azzera filtri</button>
                        @endif
                    </div>

                    <div class="ms-gr-trails__count-bar ms-gr-trails__mobile">
                        <span>{{ $countLabel }}</span>
                        @if ($filtering)
                            <button type="button" wire:click="resetFilters" class="ms-gr-trails__link">Azzera filtri</button>
                        @endif
                    </div>

                    {{-- Desktop --}}
                    <div class="ms-gr-trails__table ms-gr-trails__trails" role="table" aria-label="Sentieri del GR">
                        <div class="ms-gr-trails__trail-grid ms-gr-trails__table-head" role="row">
                            <span role="columnheader" aria-sort="{{ $ariaSort('numero') }}">
                                <button type="button" wire:click="sortBy('numero')" @class(['ms-gr-trails__sort', 'ms-gr-trails__sort--active' => $activeSort->column() === 'numero'])>N° {{ $sortArrow('numero') }}</button>
                            </span>
                            <span role="columnheader">Nome · da – a</span>
                            <span role="columnheader">Provincia · Settore</span>
                            <span role="columnheader">Sezioni</span>
                            <span role="columnheader" class="ms-gr-trails__numeric" aria-sort="{{ $ariaSort('km') }}">
                                <button type="button" wire:click="sortBy('km')" @class(['ms-gr-trails__sort', 'ms-gr-trails__sort--active' => $activeSort->column() === 'km'])>Km {{ $sortArrow('km') }}</button>
                            </span>
                            <span role="columnheader" aria-sort="{{ $ariaSort('stato') }}">
                                <button type="button" wire:click="sortBy('stato')" @class(['ms-gr-trails__sort', 'ms-gr-trails__sort--active' => $activeSort->column() === 'stato'])>Accatast. {{ $sortArrow('stato') }}</button>
                            </span>
                            <span role="columnheader">Percorribilità</span>
                            <span role="columnheader"></span>
                        </div>

                        @foreach ($trails as $row)
                            @php
                                $trail = $row->trail;
                                $territory = RegionalGroupTrails::territory($row);
                                $firstSection = RegionalGroupTrails::firstSection($row);
                                $shared = RegionalGroupTrails::sharedWith($row);
                            @endphp
                            <a href="{{ $this->trailUrl($trail) }}" wire:key="trail-{{ $trail->id }}" role="row" class="ms-gr-trails__trail-grid ms-gr-trails__trail-row">
                                <span role="cell"><x-cai.trail-sign :ref="$trail->ref" /></span>
                                <span role="cell" class="ms-gr-trails__stack">
                                    <span @class(['ms-gr-trails__trail-name', 'ms-gr-trails__missing' => blank($trail->name)])>{{ filled($trail->name) ? $trail->name : '—' }}</span>
                                    <span class="ms-gr-trails__secondary">{{ SectionTrails::route($trail) }}</span>
                                </span>
                                <span role="cell" class="ms-gr-trails__stack">
                                    <span class="ms-gr-trails__bold ms-gr-trails__small">{{ $territory['province'] }}</span>
                                    <span class="ms-gr-trails__secondary">{{ $territory['sector'] }}</span>
                                </span>
                                <span role="cell" class="ms-gr-trails__stack">
                                    <span @class(['ms-gr-trails__bold', 'ms-gr-trails__small', 'ms-gr-trails__missing' => $firstSection === null])>{{ $firstSection ?? 'Nessuna Sezione' }}</span>
                                    @if ($shared !== '')
                                        <span class="ms-gr-trails__shared">{!! $sharedIcon !!}{{ $shared }}</span>
                                    @endif
                                </span>
                                <span role="cell" class="ms-gr-trails__numeric ms-gr-trails__strong">{{ RegionalGroupTrails::trailKm($trail) }}</span>
                                <span role="cell"><span class="ms-cai-trail-status" style="--ms-cai-trail-status-color: {{ $trail->status->color() }}">{{ $trail->status->getLabel() }}</span></span>
                                <span role="cell"><span class="ms-trails__practicability" style="background: {{ $trail->practicability->background() }}; color: {{ $trail->practicability->foreground() }}">{{ $trail->practicability->getLabel() }}</span></span>
                                <span role="cell" class="ms-gr-trails__row-chevron" aria-hidden="true">›</span>
                            </a>
                        @endforeach
                    </div>

                    {{-- Mobile --}}
                    <ul class="ms-gr-trails__cards ms-gr-trails__trail-cards" aria-label="Sentieri del GR">
                        @foreach ($trails as $row)
                            @php
                                $trail = $row->trail;
                                $territory = RegionalGroupTrails::territory($row);
                                $sections = RegionalGroupTrails::allSections($row);
                            @endphp
                            <li wire:key="trail-card-{{ $trail->id }}">
                                <a href="{{ $this->trailUrl($trail) }}" class="ms-gr-trails__card ms-gr-trails__trail-card">
                                    <x-cai.trail-sign :ref="$trail->ref" size="lg" />
                                    <span class="ms-gr-trails__trail-card-body">
                                        <span class="ms-gr-trails__stack">
                                            <span @class(['ms-gr-trails__trail-card-name', 'ms-gr-trails__missing' => blank($trail->name)])>{{ filled($trail->name) ? $trail->name : '—' }}</span>
                                            <span class="ms-gr-trails__secondary">{{ SectionTrails::route($trail) }}</span>
                                        </span>
                                        <span class="ms-gr-trails__tags">
                                            <span class="ms-cai-trail-status ms-cai-trail-status--sm" style="--ms-cai-trail-status-color: {{ $trail->status->color() }}">{{ $trail->status->getLabel() }}</span>
                                            <span class="ms-trails__practicability ms-trails__practicability--sm" style="background: {{ $trail->practicability->background() }}; color: {{ $trail->practicability->foreground() }}">{{ $trail->practicability->getLabel() }}</span>
                                        </span>
                                        <span class="ms-gr-trails__secondary">{{ RegionalGroupTrails::trailKm($trail) }} km{{ $territory['sectorShort'] !== '' ? ' · '.$territory['sectorShort'] : '' }}</span>
                                        <span @class(['ms-gr-trails__bold', 'ms-gr-trails__small', 'ms-gr-trails__missing' => $sections === null])>{{ $sections ?? 'Nessuna Sezione' }}</span>
                                    </span>
                                    <span class="ms-gr-trails__row-chevron" aria-hidden="true">›</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>

                    @if ($trails->isEmpty())
                        <div class="ms-gr-trails__no-match">
                            <span class="ms-gr-trails__desktop">Nessun sentiero corrisponde alla ricerca o ai filtri selezionati.</span>
                            <span class="ms-gr-trails__mobile">Nessun sentiero corrisponde ai filtri.</span>
                            @if ($filtering)
                                <button type="button" wire:click="resetFilters" class="ms-gr-trails__link ms-gr-trails__desktop">Azzera filtri</button>
                            @endif
                        </div>
                    @endif

                    <div class="ms-gr-trails__pager">
                        <span class="ms-gr-trails__count ms-gr-trails__desktop">{{ $countLabel }}</span>
                        @if ($trails->lastPage() > 1)
                            <nav class="ms-gr-trails__pages ms-gr-trails__desktop" aria-label="Pagine dell'elenco">
                                <button type="button" wire:click="goToPage({{ $trails->currentPage() - 1 }})" @disabled($trails->onFirstPage()) class="ms-gr-trails__page" aria-label="Pagina precedente">‹</button>
                                @foreach ($this->pageNumbers() as $number)
                                    @if ($number === null)
                                        <span class="ms-gr-trails__page ms-gr-trails__page--gap">…</span>
                                    @else
                                        <button type="button" wire:click="goToPage({{ $number }})" @class(['ms-gr-trails__page', 'ms-gr-trails__page--current' => $number === $trails->currentPage()]) @if ($number === $trails->currentPage()) aria-current="page" @endif>{{ $number }}</button>
                                    @endif
                                @endforeach
                                <button type="button" wire:click="goToPage({{ $trails->currentPage() + 1 }})" @disabled(! $trails->hasMorePages()) class="ms-gr-trails__page" aria-label="Pagina successiva">›</button>
                            </nav>
                            <nav class="ms-gr-trails__mobile-pages ms-gr-trails__mobile" aria-label="Pagine dell'elenco">
                                <button type="button" wire:click="goToPage({{ $trails->currentPage() - 1 }})" @disabled($trails->onFirstPage()) class="ms-gr-trails__mobile-page">‹ Precedenti</button>
                                <span>Pagina {{ $trails->currentPage() }} di {{ ItalianNumberFormatter::integer($trails->lastPage()) }}</span>
                                <button type="button" wire:click="goToPage({{ $trails->currentPage() + 1 }})" @disabled(! $trails->hasMorePages()) class="ms-gr-trails__mobile-page">Successivi ›</button>
                            </nav>
                        @endif
                    </div>
                </div>
                <p class="ms-gr-trails__footnote ms-gr-trails__mobile">Fonte: osm2cai · catasto sentieri CAI</p>
            </section>
        @endif
    </div>
</x-filament-panels::page>
