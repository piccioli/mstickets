{{-- Rifugi del GR (Fase 10.5, US-1043), design docs/project/design/fase-10/rifugi-del-gr.dc.html §1. Stili in
     resources/css/filament/admin/regional-group-huts.css (classi ms-gr-huts__*), più quelle condivise con "Rifugi di
     Sezione" (ms-huts__dot/status/empty/legend/reset di section-huts.css). Una sola mappa con cluster (`x-cai.map`, sempre
     resa) sopra l'elenco. Tabella (desktop) ed elenco di card (mobile, primi 12 + "Mostra altri N") sono resi entrambi,
     il CSS mostra l'uno o l'altro. Righe e marker si evidenziano a vicenda con gli eventi `cai-map-*`. --}}
@use('App\Domain\CaiDirectory\Enums\CaiHutContractStatus')
@use('App\Domain\CaiDirectory\Enums\CaiHutType')
@use('App\Domain\CaiDirectory\Enums\CaiRegionalGroupHutSortColumn')
@use('App\Filament\Pages\RegionalGroupHuts')
@php
    $data = $this->data();
    $allRows = $this->allRows();
    $hasHuts = $allRows !== [];
    $rows = $this->rows();
    $items = $this->items($rows);
    $mobileItems = $this->showAll ? $items : $this->items($rows, RegionalGroupHuts::MOBILE_PAGE_SIZE);
    $hiddenCount = $this->showAll ? 0 : max(0, count($rows) - RegionalGroupHuts::MOBILE_PAGE_SIZE);
    $filters = $this->filters();
    $sortColumn = $this->sortColumn();
    $sortDescending = $this->sortDescending();
    $contractsActive = $this->contractsFilterActive();
    $alerts = $this->contractAlertsCount();
    $grouped = $this->isGrouped();
    $lastUpdate = $this->lastUpdate();
    $mapName = RegionalGroupHuts::MAP_NAME;
    $placeholder = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 19l6-9 4 5 3-3 5 7z"></path></svg>';
@endphp
<x-filament-panels::page>
    <div class="ms-gr-huts">
        <div class="ms-gr-huts__top">
            <a href="{{ $this->dashboardUrl() }}" class="ms-huts__back">← Dashboard</a>
            <header class="ms-gr-huts__title-block">
                <h1 class="ms-gr-huts__title">Rifugi del GR</h1>
                @if ($data !== null)
                    <p class="ms-gr-huts__subtitle">{{ $this->subtitle($data) }}</p>
                @endif
            </header>
        </div>

        @if ($data === null)
            <p class="ms-section-dashboard__notice" role="status">
                Nessuna regione associata al tuo account: contatta Montagna Servizi.
            </p>
        @else
            <div class="ms-gr-huts__kpis">
                <div class="ms-gr-huts__kpi">
                    <span class="ms-gr-huts__kpi-label">Rifugi totali</span>
                    <span class="ms-gr-huts__kpi-value">{{ $hasHuts ? RegionalGroupHuts::number($data->total) : '—' }}</span>
                </div>
                <div class="ms-gr-huts__kpi">
                    <span class="ms-gr-huts__kpi-label">In attività</span>
                    <span class="ms-gr-huts__kpi-value">
                        {{ $hasHuts ? RegionalGroupHuts::number($data->active) : '—' }}
                        @if ($hasHuts)
                            <span class="ms-gr-huts__kpi-of">su {{ RegionalGroupHuts::number($data->total) }}</span>
                        @endif
                    </span>
                </div>
                <div class="ms-gr-huts__kpi">
                    <span class="ms-gr-huts__kpi-label">Posti letto totali</span>
                    <span class="ms-gr-huts__kpi-value">{{ $hasHuts ? RegionalGroupHuts::number($data->beds) : '—' }}</span>
                </div>
                <div class="ms-gr-huts__kpi">
                    <span class="ms-gr-huts__kpi-label">
                        <span class="ms-gr-huts__long">Sezioni con almeno un rifugio</span>
                        <span class="ms-gr-huts__short">Sezioni con rifugi</span>
                    </span>
                    <span class="ms-gr-huts__kpi-value">
                        {{ $hasHuts ? $data->sectionsWithHutsCount : '—' }}
                        @if ($hasHuts)
                            <span class="ms-gr-huts__kpi-of">su {{ $data->sectionsCount }}</span>
                        @endif
                    </span>
                </div>
                @if ($alerts > 0)
                    <button
                        type="button"
                        wire:click="toggleContracts"
                        aria-pressed="{{ $contractsActive ? 'true' : 'false' }}"
                        @class(['ms-gr-huts__kpi', 'ms-gr-huts__kpi--contracts', 'ms-gr-huts__kpi--active' => $contractsActive])
                    >
                        <span class="ms-gr-huts__kpi-head">
                            <span class="ms-gr-huts__kpi-label">Contratti di gestione</span>
                            <span class="ms-gr-huts__kpi-link">{{ $contractsActive ? 'Filtro attivo · ×' : 'Filtra elenco →' }}</span>
                        </span>
                        <span class="ms-gr-huts__kpi-contracts">
                            <span class="ms-gr-huts__kpi-contract ms-gr-huts__kpi-contract--expiring">
                                <span class="ms-gr-huts__kpi-value">{{ $data->expiringContracts }}</span>
                                <span class="ms-gr-huts__long">in scadenza entro 6 mesi</span>
                                <span class="ms-gr-huts__short">in scadenza</span>
                            </span>
                            <span class="ms-gr-huts__kpi-contract ms-gr-huts__kpi-contract--expired">
                                <span class="ms-gr-huts__kpi-value">{{ $data->expiredContracts }}</span>
                                scaduti
                            </span>
                        </span>
                    </button>
                @else
                    <div class="ms-gr-huts__kpi ms-gr-huts__kpi--contracts">
                        <span class="ms-gr-huts__kpi-label">Contratti in scadenza o scaduti</span>
                        <span class="ms-gr-huts__kpi-value ms-gr-huts__kpi-value--none">{{ $hasHuts ? 'Nessuno' : '—' }}</span>
                    </div>
                @endif
            </div>

            @if (! $hasHuts)
                <div class="ms-huts__empty">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 11l9-7 9 7M5 9.5V20h14V9.5M10 20v-5h4v5"></path></svg>
                    <p class="ms-huts__empty-title">Nessun rifugio del {{ $data->groupName }} nella banca dati rifugi.cai.it</p>
                    <p class="ms-gr-huts__empty-text">Nessuna delle Sezioni del GR risulta proprietaria di un rifugio, e non ci sono rifugi intestati al GR.</p>
                    <a href="https://rifugi.cai.it" target="_blank" rel="noopener" class="ms-huts__empty-link">Vai a rifugi.cai.it ↗</a>
                </div>
            @endif
        @endif

        {{-- Mappa ed elenco: resi sempre (la mappa deve esserci dal primo rendering), nascosti senza rifugi. --}}
        <div
            @class(['ms-gr-huts__body', 'ms-gr-huts__body--hidden' => ! $hasHuts])
            x-data="{ active: null, hovered: null }"
            x-on:cai-map-marker-selected.window="if ($event.detail.map === @js($mapName)) active = $event.detail.id"
            x-on:cai-map-marker-hovered.window="if ($event.detail.map === @js($mapName)) hovered = $event.detail.id"
        >
            <div class="ms-gr-huts__map-card">
                <x-cai.map :name="$mapName" :markers="$this->markers($rows)" cluster height="var(--ms-gr-huts-map-height)" />
                <ul class="ms-huts__legend" aria-label="Legenda">
                    @foreach (CaiHutType::cases() as $legendType)
                        <li class="ms-huts__legend-item">
                            <span class="ms-huts__legend-marker" style="background: {{ $legendType->color() }}">{{ $legendType->letter() }}</span>
                            {{ $legendType->getLabel() }}
                        </li>
                    @endforeach
                    <li class="ms-huts__legend-item ms-gr-huts__legend-cluster">
                        <span class="ms-huts__legend-marker ms-gr-huts__legend-cluster-marker">n</span>
                        Gruppo di rifugi
                    </li>
                </ul>
            </div>

            <section class="ms-gr-huts__panel" aria-label="Elenco dei rifugi">
                <div class="ms-gr-huts__toolbar">
                    <div class="ms-gr-huts__filters">
                        <select
                            wire:change="filterSection($event.target.value)"
                            @class(['ms-gr-huts__select', 'ms-gr-huts__select--section', 'ms-gr-huts__select--active' => $filters->section !== null])
                            aria-label="Filtra per Sezione"
                        >
                            <option value="">Tutte le Sezioni</option>
                            @foreach ($this->sectionOptions() as $key => $label)
                                <option value="{{ $key }}" @selected($key === $filters->section)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <select
                            wire:change="filterType($event.target.value)"
                            @class(['ms-gr-huts__select', 'ms-gr-huts__select--active' => $filters->type !== null])
                            aria-label="Filtra per tipo"
                        >
                            <option value="">Tutti i tipi</option>
                            @foreach ($this->typeOptions() as $key => $label)
                                <option value="{{ $key }}" @selected($key === $filters->type?->key())>{{ $label }}</option>
                            @endforeach
                        </select>
                        <select
                            wire:change="filterStatus($event.target.value)"
                            @class(['ms-gr-huts__select', 'ms-gr-huts__select--active' => $filters->status !== null])
                            aria-label="Filtra per stato"
                        >
                            <option value="">Tutti gli stati</option>
                            @foreach ($this->statusOptions() as $key => $label)
                                <option value="{{ $key }}" @selected($key === $filters->status?->key())>{{ $label }}</option>
                            @endforeach
                        </select>
                        <button
                            type="button"
                            wire:click="toggleContracts"
                            aria-pressed="{{ $contractsActive ? 'true' : 'false' }}"
                            @class(['ms-gr-huts__pill', 'ms-gr-huts__pill--active' => $contractsActive])
                        >
                            <span class="ms-gr-huts__pill-dot"></span>
                            Contratto in scadenza / scaduto
                            <span class="ms-gr-huts__pill-count">{{ $alerts > 0 ? $alerts : '—' }}</span>
                        </button>
                        <select wire:change="applySortOption($event.target.value)" class="ms-gr-huts__select ms-gr-huts__select--sort" aria-label="Ordina">
                            @foreach (RegionalGroupHuts::sortOptions() as $value => $label)
                                <option value="{{ $value }}" @selected($value === $this->sortOption())>{{ $label }}</option>
                            @endforeach
                        </select>
                        @if ($this->groupingAvailable())
                            <button
                                type="button"
                                wire:click="toggleGrouping"
                                role="switch"
                                aria-checked="{{ $grouped ? 'true' : 'false' }}"
                                @class(['ms-gr-huts__switch', 'ms-gr-huts__switch--mobile', 'ms-gr-huts__switch--on' => $grouped])
                            >
                                <span class="ms-gr-huts__switch-track"><span class="ms-gr-huts__switch-knob"></span></span>
                                Per Sezione
                            </button>
                        @endif
                        @if ($filters->isFiltering())
                            <button type="button" wire:click="resetFilters" class="ms-huts__reset ms-gr-huts__reset--desktop">Azzera filtri</button>
                        @endif
                    </div>

                    @if ($this->groupingAvailable())
                        <button
                            type="button"
                            wire:click="toggleGrouping"
                            role="switch"
                            aria-checked="{{ $grouped ? 'true' : 'false' }}"
                            @class(['ms-gr-huts__switch', 'ms-gr-huts__switch--desktop', 'ms-gr-huts__switch--on' => $grouped])
                        >
                            <span class="ms-gr-huts__switch-track"><span class="ms-gr-huts__switch-knob"></span></span>
                            Raggruppa per Sezione
                        </button>
                    @endif
                </div>

                <div class="ms-gr-huts__count-mobile">
                    <span>{{ $this->countLabel(count($rows)) }}</span>
                    @if ($filters->isFiltering())
                        <button type="button" wire:click="resetFilters" class="ms-huts__reset">Azzera filtri</button>
                    @endif
                </div>

                {{-- Desktop --}}
                <div class="ms-gr-huts__table" role="table" aria-label="Rifugi del GR">
                    <div class="ms-gr-huts__row ms-gr-huts__row--head" role="row">
                        @foreach (CaiRegionalGroupHutSortColumn::cases() as $column)
                            @php
                                $active = $column === $sortColumn;
                            @endphp
                            <span
                                role="columnheader"
                                @class(['ms-gr-huts__numeric' => $column->isNumeric()])
                                aria-sort="{{ $active ? ($sortDescending ? 'descending' : 'ascending') : 'none' }}"
                            >
                                <button type="button" wire:click="sortBy('{{ $column->value }}')" @class(['ms-gr-huts__sort', 'ms-gr-huts__sort--active' => $active])>
                                    {{ $column->getLabel() }} {{ $active ? ($sortDescending ? '↓' : '↑') : '' }}
                                </button>
                            </span>
                        @endforeach
                    </div>

                    <div class="ms-gr-huts__rows">
                        @foreach ($items as $item)
                            @if ($item['header'] !== null)
                                <div class="ms-gr-huts__group" role="row" wire:key="group-{{ $item['header']['key'] }}">
                                    <span class="ms-gr-huts__group-name">{{ $item['header']['title'] }}</span>
                                    <span class="ms-gr-huts__group-count">{{ $item['header']['count'] }}</span>
                                </div>
                            @else
                                @php
                                    $row = $item['row'];
                                    $hut = $row->hut;
                                    $hutEvent = ['map' => $mapName, 'id' => $hut->id_cai];
                                    $alert = $row->contractStatus?->isAlert() === true ? $row->contractStatus : null;
                                @endphp
                                <a
                                    href="{{ $this->hutUrl($hut) }}"
                                    class="ms-gr-huts__row"
                                    role="row"
                                    wire:key="row-{{ $hut->id_cai }}"
                                    data-hut="{{ $hut->id_cai }}"
                                    x-bind:class="{ 'ms-gr-huts__row--active': active === {{ $hut->id_cai }} || hovered === {{ $hut->id_cai }} }"
                                    x-on:mouseenter="hovered = {{ $hut->id_cai }}; $dispatch('cai-map-highlight', @js($hutEvent))"
                                    x-on:mouseleave="hovered = null; $dispatch('cai-map-highlight', @js(['map' => $mapName, 'id' => null]))"
                                >
                                    <span role="cell" class="ms-gr-huts__name-cell">
                                        <span class="ms-gr-huts__name">{{ $hut->name }}</span>
                                        @if (filled($hut->alias))
                                            <span class="ms-gr-huts__alias">{{ $hut->alias }}</span>
                                        @endif
                                    </span>
                                    <span role="cell" class="ms-gr-huts__section">{{ $row->sectionName }}</span>
                                    <span role="cell" class="ms-gr-huts__type">
                                        <span class="ms-huts__dot" style="background: {{ $hut->type?->color() ?? '#5b6475' }}"></span>
                                        {{ $hut->type?->getLabel() ?? '—' }}
                                    </span>
                                    <span role="cell">
                                        @if ($hut->status !== null)
                                            <span class="ms-huts__status" style="background: {{ $hut->status->background() }}; color: {{ $hut->status->foreground() }}">{{ $hut->status->getLabel() }}</span>
                                        @else
                                            <span class="ms-gr-huts__missing">—</span>
                                        @endif
                                    </span>
                                    <span role="cell" @class(['ms-gr-huts__numeric', 'ms-gr-huts__strong', 'ms-gr-huts__missing' => $hut->altitude === null])>{{ RegionalGroupHuts::number($hut->altitude) }}</span>
                                    <span role="cell" @class(['ms-gr-huts__numeric', 'ms-gr-huts__strong', 'ms-gr-huts__missing' => $hut->beds === null])>{{ RegionalGroupHuts::number($hut->beds) }}</span>
                                    <span role="cell" class="ms-gr-huts__contract">
                                        <span @class([
                                            'ms-gr-huts__contract-date',
                                            'ms-gr-huts__missing' => $row->contractEnd === null,
                                            'ms-gr-huts__contract-date--expired' => $alert === CaiHutContractStatus::Expired,
                                            'ms-gr-huts__contract-date--expiring' => $alert === CaiHutContractStatus::Expiring,
                                        ])>{{ $row->contractEnd?->format('d/m/Y') ?? '—' }}</span>
                                        @if ($alert !== null && $row->contractNote !== null)
                                            <span @class(['ms-gr-huts__contract-note', 'ms-gr-huts__contract-note--expired' => $alert === CaiHutContractStatus::Expired])>{{ $row->contractNote }}</span>
                                        @endif
                                    </span>
                                </a>
                            @endif
                        @endforeach

                        @if ($rows === [] && $hasHuts)
                            <div class="ms-huts__no-match">
                                Nessun rifugio corrisponde ai filtri selezionati.
                                <button type="button" wire:click="resetFilters" class="ms-huts__reset">Azzera filtri</button>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Mobile --}}
                <ul class="ms-gr-huts__cards" aria-label="Rifugi del GR">
                    @foreach ($mobileItems as $item)
                        @if ($item['header'] !== null)
                            <li class="ms-gr-huts__card-group" wire:key="card-group-{{ $item['header']['key'] }}">
                                <span class="ms-gr-huts__group-name">{{ $item['header']['title'] }}</span>
                                <span class="ms-gr-huts__group-count">{{ $item['header']['count'] }}</span>
                            </li>
                        @else
                            @php
                                $row = $item['row'];
                                $hut = $row->hut;
                                $alert = $row->contractStatus?->isAlert() === true ? $row->contractStatus : null;
                            @endphp
                            <li wire:key="card-{{ $hut->id_cai }}">
                                <a href="{{ $this->hutUrl($hut) }}" class="ms-gr-huts__card">
                                    <span class="ms-gr-huts__card-top">
                                        <span class="ms-gr-huts__card-name">{{ $hut->name }}</span>
                                        @if ($hut->status !== null)
                                            <span class="ms-huts__status ms-gr-huts__card-status" style="background: {{ $hut->status->background() }}; color: {{ $hut->status->foreground() }}">{{ $hut->status->getLabel() }}</span>
                                        @endif
                                    </span>
                                    <span class="ms-gr-huts__card-section">{{ $row->sectionName }}</span>
                                    <span class="ms-gr-huts__card-meta">
                                        <span class="ms-huts__dot" style="background: {{ $hut->type?->color() ?? '#5b6475' }}"></span>
                                        {{ $hut->type?->getLabel() ?? '—' }} · {{ RegionalGroupHuts::number($hut->altitude) }} m · {{ RegionalGroupHuts::number($hut->beds) }} posti
                                    </span>
                                    @if ($row->contractEnd !== null)
                                        <span @class([
                                            'ms-gr-huts__card-contract',
                                            'ms-gr-huts__card-contract--expired' => $alert === CaiHutContractStatus::Expired,
                                            'ms-gr-huts__card-contract--expiring' => $alert === CaiHutContractStatus::Expiring,
                                        ])>
                                            <span class="ms-gr-huts__card-contract-label">Contratto di gestione</span>
                                            <span class="ms-gr-huts__card-contract-value">
                                                {{ $row->contractEnd->format('d/m/Y') }}{{ match ($alert) {
                                                    CaiHutContractStatus::Expired => ' · scaduto',
                                                    CaiHutContractStatus::Expiring => ' · in scadenza',
                                                    default => '',
                                                } }}
                                            </span>
                                        </span>
                                    @endif
                                </a>
                            </li>
                        @endif
                    @endforeach

                    @if ($rows === [] && $hasHuts)
                        <li class="ms-huts__no-match ms-gr-huts__no-match-card">
                            Nessun rifugio corrisponde ai filtri.
                            <button type="button" wire:click="resetFilters" class="ms-huts__reset">Azzera filtri</button>
                        </li>
                    @endif

                    @if ($hiddenCount > 0)
                        <li>
                            <button type="button" wire:click="$set('showAll', true)" class="ms-gr-huts__more">Mostra altri {{ $hiddenCount }}</button>
                        </li>
                    @endif
                </ul>

                <footer class="ms-gr-huts__footer">
                    <span class="ms-gr-huts__footer-count">{{ $this->countLabel(count($rows)) }}</span>
                    <span>
                        Fonte: <a href="https://rifugi.cai.it" target="_blank" rel="noopener">rifugi.cai.it</a>
                        @if ($lastUpdate !== null)
                            · dati aggiornati al {{ $lastUpdate->locale('it')->translatedFormat('j F Y') }}
                        @endif
                    </span>
                </footer>
            </section>
        </div>

        @if ($data !== null && $data->sectionsWithoutHuts !== [])
            @php
                $without = count($data->sectionsWithoutHuts);
            @endphp
            <section class="ms-gr-huts__without" x-data="{ open: @js(! $hasHuts) }" aria-label="Sezioni senza rifugi">
                <button type="button" class="ms-gr-huts__without-toggle" x-on:click="open = ! open" x-bind:aria-expanded="open ? 'true' : 'false'">
                    <span class="ms-gr-huts__without-heading">
                        <span class="ms-gr-huts__without-title">Sezioni senza rifugi</span>
                        <span class="ms-gr-huts__without-count">{{ $without }} su {{ $data->sectionsCount }}</span>
                    </span>
                    <span class="ms-gr-huts__without-action">
                        <span class="ms-gr-huts__long" x-text="open ? 'Chiudi' : 'Mostra elenco'">{{ $hasHuts ? 'Mostra elenco' : 'Chiudi' }}</span>
                        <svg viewBox="0 0 24 24" aria-hidden="true" x-bind:class="{ 'ms-gr-huts__chevron--open': open }" @class(['ms-gr-huts__chevron', 'ms-gr-huts__chevron--open' => ! $hasHuts])><path d="M6 9l6 6 6-6"></path></svg>
                    </span>
                </button>
                <div class="ms-gr-huts__without-body" x-show="open" @if ($hasHuts) x-cloak @endif>
                    <p class="ms-gr-huts__without-text">
                        <span class="ms-gr-huts__long">Sezioni del GR che non risultano proprietarie di alcun rifugio nella banca dati rifugi.cai.it. Se una di queste Sezioni gestisce un rifugio, il dato va segnalato o corretto su</span>
                        <span class="ms-gr-huts__short">Nessun rifugio nella banca dati. Dati mancanti? Segnalali su</span>
                        <a href="https://rifugi.cai.it" target="_blank" rel="noopener">rifugi.cai.it ↗</a>
                    </p>
                    <ul class="ms-gr-huts__without-list">
                        @foreach ($data->sectionsWithoutHuts as $withoutSection)
                            <li title="Codice CAI {{ $withoutSection['codiceCai'] }}">{{ $withoutSection['name'] }}</li>
                        @endforeach
                    </ul>
                </div>
            </section>
        @endif
    </div>
</x-filament-panels::page>
