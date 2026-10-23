{{-- Sentieri di Sezione (Fase 10.6, US-1054), design docs/project/design/fase-10/sentieri-di-sezione.dc.html §1. Stili in
     resources/css/filament/admin/section-trails.css (segnavia e pillola dello stato in cai-trails.css). Una sola mappa
     (`x-cai.map`, sempre resa) per desktop e mobile: il CSS la mette a destra dell'elenco su desktop e sopra su mobile
     (< 640px). Tabella (desktop) ed elenco di card (mobile, primi 12 + "Mostra altri N") sono resi entrambi, il CSS
     mostra l'uno o l'altro. Righe e linee si evidenziano a vicenda con gli eventi `cai-map-*` (vedi il componente).
     Usata anche da RegionalGroupSectionTrails (10.7, Sezione vista dal GR): link di ritorno da backLink(). --}}
@use('App\Domain\CaiDirectory\Enums\CaiTrailDifficulty')
@use('App\Domain\CaiDirectory\Enums\CaiTrailPracticability')
@use('App\Domain\CaiDirectory\Enums\CaiTrailSort')
@use('App\Domain\CaiDirectory\Enums\CaiTrailStatus')
@use('App\Filament\Pages\SectionTrails')
@php
    $section = $this->section();
    $subsection = $section === null ? $this->subsection() : null;
    $trails = $this->trails();
    $rows = $this->rows();
    $activeStatuses = $this->activeStatuses();
    $activeDifficulty = $this->activeDifficulty();
    $activePracticability = $this->activePracticability();
    $activeSort = $this->activeSort();
    $mobileRows = $this->showAll ? $rows : $rows->take(SectionTrails::MOBILE_PAGE_SIZE);
    $hiddenCount = $rows->count() - $mobileRows->count();
    $mapName = SectionTrails::MAP_NAME;
    $sortArrow = fn (string $column): string => $activeSort->column() === $column ? ($activeSort->isDescending() ? '↓' : '↑') : '';
    $ariaSort = fn (string $column): string => $activeSort->column() === $column ? ($activeSort->isDescending() ? 'descending' : 'ascending') : 'none';
    $sharedIcon = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM2 21v-1a6 6 0 0 1 12 0v1M16 3.5a4 4 0 0 1 0 7M22 21v-1a6 6 0 0 0-4-5.6"></path></svg>';
@endphp
<x-filament-panels::page>
    <div class="ms-trails">
        <div class="ms-trails__top">
            @php
                $backLink = $this->backLink();
            @endphp
            <a href="{{ $backLink['url'] }}" class="ms-trails__back">← {{ $backLink['label'] }}</a>

            <div class="ms-trails__heading">
                <header class="ms-trails__title-block">
                    <h1 class="ms-trails__title">Sentieri di Sezione</h1>
                    <p class="ms-trails__subtitle">{{ $section?->name ?? $subsection?->name ?? auth()->user()?->name }}</p>
                </header>

                <div class="ms-trails__kpis">
                    <div class="ms-trails__kpi ms-trails__kpi--km">
                        <span class="ms-trails__kpi-label">Km totali</span>
                        <span class="ms-trails__kpi-value">{{ $this->totalKmLabel() }}</span>
                    </div>
                    <div class="ms-trails__kpi">
                        <span class="ms-trails__kpi-label">Sentieri</span>
                        <span class="ms-trails__kpi-value">{{ $this->trailsCountLabel() }}</span>
                    </div>
                </div>
            </div>
        </div>

        @if ($section === null)
            <p class="ms-section-dashboard__notice" role="status">
                @if ($subsection !== null)
                    I sentieri sono intestati alle Sezioni: per la Sottosezione non sono disponibili.
                @else
                    Nessuna Sezione CAI collegata al tuo account: contatta Montagna Servizi.
                @endif
            </p>
        @elseif ($trails->isEmpty())
            <div class="ms-trails__empty">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 20c2-3 6-2 7-6s4-4 7-9M5 20v.01M19 5v.01"></path><path d="M4 20h2M18 5h2"></path></svg>
                <p class="ms-trails__empty-title">Nessun sentiero associato a questa Sezione nel catasto osm2cai</p>
                <a href="https://osm2cai.cai.it" target="_blank" rel="noopener" class="ms-trails__empty-link">Vai a osm2cai ↗</a>
            </div>
        @else
            <section class="ms-trails__summary" aria-label="Riepilogo dei sentieri">
                <div class="ms-trails__summary-status">
                    <div class="ms-trails__summary-heading">
                        <h2 class="ms-trails__summary-title">Km per stato di accatastamento</h2>
                        <span class="ms-trails__summary-hint">da 1 · Difficoltà a 4 · Validato (il più completo)</span>
                    </div>
                    <div class="ms-trails__bar" role="img" aria-label="Km per stato di accatastamento">
                        @foreach ($this->statusSummary() as $item)
                            <div class="ms-trails__bar-segment" title="{{ $item['status']->getLabel() }} · {{ $item['km'] }} km" style="width: {{ $item['percent'] }}%; background: {{ $item['status']->color() }}; color: {{ $item['status']->foreground() }}">{{ $item['label'] }}</div>
                        @endforeach
                    </div>
                    <ul class="ms-trails__legend-grid">
                        @foreach ($this->statusSummary() as $item)
                            <li class="ms-trails__legend-entry">
                                <span class="ms-trails__legend-label"><span class="ms-trails__swatch" style="background: {{ $item['status']->color() }}"></span>{{ $item['status']->getLabel() }}</span>
                                <span class="ms-trails__legend-value">{{ $item['km'] }} km <span class="ms-trails__legend-note">{{ $item['share'] }}<span class="ms-trails__legend-count"> · {{ $item['count'] }}</span></span></span>
                            </li>
                        @endforeach
                    </ul>
                </div>
                <div class="ms-trails__summary-practicability">
                    <h2 class="ms-trails__summary-title">Percorribilità</h2>
                    <ul class="ms-trails__practicability-grid">
                        @foreach ($this->practicabilitySummary() as $item)
                            <li class="ms-trails__legend-entry">
                                <span class="ms-trails__legend-label"><span class="ms-trails__dot" style="background: {{ $item['practicability']->dot() }}"></span>{{ $item['practicability']->getLabel() }}</span>
                                <span class="ms-trails__legend-value">{{ $item['km'] }} km <span class="ms-trails__legend-note ms-trails__legend-count">{{ $item['count'] }}</span></span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </section>
        @endif

        <div
            @class(['ms-trails__body', 'ms-trails__body--hidden' => $trails->isEmpty()])
            x-data="{ active: null, hovered: null }"
            x-on:cai-map-line-selected.window="if ($event.detail.map === @js($mapName)) active = $event.detail.id"
            x-on:cai-map-line-hovered.window="if ($event.detail.map === @js($mapName)) hovered = $event.detail.id"
        >
            <section class="ms-trails__panel" aria-label="Elenco dei sentieri">
                <div class="ms-trails__filters">
                    <div class="ms-trails__chips" role="group" aria-label="Filtra per stato di accatastamento">
                        <button
                            type="button"
                            wire:click="clearStatuses"
                            aria-pressed="{{ $activeStatuses === [] ? 'true' : 'false' }}"
                            @class(['ms-trails__chip', 'ms-trails__chip--active' => $activeStatuses === []])
                        >
                            <span class="ms-trails__chip-label--long">Tutti gli stati</span>
                            <span class="ms-trails__chip-label--short">Tutti</span>
                        </button>
                        @foreach (CaiTrailStatus::cases() as $status)
                            @php
                                $on = in_array($status, $activeStatuses, true);
                            @endphp
                            <button
                                type="button"
                                wire:click="toggleStatus({{ $status->value }})"
                                aria-pressed="{{ $on ? 'true' : 'false' }}"
                                @class(['ms-trails__chip', 'ms-trails__chip--active' => $on])
                            >
                                <span class="ms-trails__swatch" style="background: {{ $status->color() }}"></span>
                                {{ $status->getLabel() }}
                                <span class="ms-trails__chip-count">{{ $this->statusCount($status) }}</span>
                            </button>
                        @endforeach
                    </div>

                    <div class="ms-trails__selects">
                        <select wire:change="filterDifficulty($event.target.value)" class="ms-trails__select" aria-label="Filtra per difficoltà">
                            <option value="">Tutte le difficoltà</option>
                            @foreach (CaiTrailDifficulty::cases() as $difficultyOption)
                                <option value="{{ $difficultyOption->value }}" @selected($difficultyOption === $activeDifficulty)>{{ $difficultyOption->getLabel() }}</option>
                            @endforeach
                        </select>
                        <select wire:change="filterPracticability($event.target.value)" class="ms-trails__select" aria-label="Filtra per percorribilità">
                            <option value="">Tutte le percorribilità</option>
                            @foreach (CaiTrailPracticability::cases() as $practicabilityOption)
                                <option value="{{ $practicabilityOption->key() }}" @selected($practicabilityOption === $activePracticability)>{{ $practicabilityOption->getLabel() }}</option>
                            @endforeach
                        </select>
                        <select wire:change="applySortOption($event.target.value)" class="ms-trails__select ms-trails__select--sort" aria-label="Ordina">
                            @foreach (CaiTrailSort::cases() as $sortOption)
                                <option value="{{ $sortOption->value }}" @selected($sortOption === $activeSort)>{{ $sortOption->getLabel() }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <p class="ms-trails__count ms-trails__count--mobile">{{ $this->countLabel($rows) }}</p>

                {{-- Desktop --}}
                <div class="ms-trails__table" role="table" aria-label="Sentieri della Sezione">
                    <div class="ms-trails__row ms-trails__row--head" role="row">
                        <span role="columnheader" aria-sort="{{ $ariaSort('numero') }}">
                            <button type="button" wire:click="sortBy('numero')" @class(['ms-trails__sort', 'ms-trails__sort--active' => $activeSort->column() === 'numero'])>N° {{ $sortArrow('numero') }}</button>
                        </span>
                        <span role="columnheader">Nome · da – a</span>
                        <span role="columnheader" class="ms-trails__numeric" aria-sort="{{ $ariaSort('km') }}">
                            <button type="button" wire:click="sortBy('km')" @class(['ms-trails__sort', 'ms-trails__sort--active' => $activeSort->column() === 'km'])>Km {{ $sortArrow('km') }}</button>
                        </span>
                        <span role="columnheader">Diff.</span>
                        <span role="columnheader" aria-sort="{{ $ariaSort('stato') }}">
                            <button type="button" wire:click="sortBy('stato')" @class(['ms-trails__sort', 'ms-trails__sort--active' => $activeSort->column() === 'stato'])>Accatast. {{ $sortArrow('stato') }}</button>
                        </span>
                        <span role="columnheader">Percorribilità</span>
                        <span role="columnheader"></span>
                    </div>

                    <div class="ms-trails__rows">
                        @foreach ($rows as $trail)
                            @php
                                $url = $this->trailUrl($trail);
                                $shared = $this->sharedWith($trail);
                                $updated = SectionTrails::practicabilityDate($trail);
                                $trailEvent = ['map' => $mapName, 'id' => $trail->id];
                            @endphp
                            <div
                                class="ms-trails__row"
                                role="row"
                                wire:key="row-{{ $trail->id }}"
                                data-trail="{{ $trail->id }}"
                                x-bind:class="{ 'ms-trails__row--selected': active === {{ $trail->id }}, 'ms-trails__row--hovered': hovered === {{ $trail->id }} }"
                                x-on:mouseenter="hovered = {{ $trail->id }}; $dispatch('cai-map-highlight', @js($trailEvent))"
                                x-on:mouseleave="hovered = null; $dispatch('cai-map-highlight', @js(['map' => $mapName, 'id' => null]))"
                                x-on:click="active = {{ $trail->id }}; $dispatch('cai-map-select', @js($trailEvent))"
                            >
                                <span role="cell"><x-cai.trail-sign :ref="$trail->ref" /></span>
                                <span role="cell" class="ms-trails__name-cell">
                                    <span @class(['ms-trails__name', 'ms-trails__missing' => blank($trail->name)])>{{ filled($trail->name) ? $trail->name : '—' }}</span>
                                    <span class="ms-trails__route">{{ SectionTrails::route($trail) }}</span>
                                    @if ($shared !== '')
                                        <span class="ms-trails__shared">{!! $sharedIcon !!}Condiviso con {{ $shared }}</span>
                                    @endif
                                </span>
                                <span role="cell" class="ms-trails__numeric ms-trails__strong">{{ SectionTrails::trailKm($trail) }}</span>
                                <span role="cell"><span @class(['ms-trails__difficulty', 'ms-trails__difficulty--missing' => blank($trail->cai_scale)])>{{ filled($trail->cai_scale) ? $trail->cai_scale : '—' }}</span></span>
                                <span role="cell"><span class="ms-cai-trail-status" style="--ms-cai-trail-status-color: {{ $trail->status->color() }}">{{ $trail->status->getLabel() }}</span></span>
                                <span role="cell" class="ms-trails__practicability-cell">
                                    <span class="ms-trails__practicability" style="background: {{ $trail->practicability->background() }}; color: {{ $trail->practicability->foreground() }}">{{ $trail->practicability->getLabel() }}</span>
                                    @if ($updated !== null)
                                        <span class="ms-trails__updated">agg. {{ $updated }}</span>
                                    @endif
                                </span>
                                <span role="cell">
                                    <a href="{{ $url }}" class="ms-trails__open" title="Apri scheda" aria-label="Apri scheda" x-on:click.stop>›</a>
                                </span>
                            </div>
                        @endforeach

                        @if ($rows->isEmpty() && $trails->isNotEmpty())
                            <div class="ms-trails__no-match">
                                Nessun sentiero corrisponde ai filtri selezionati.
                                <button type="button" wire:click="resetFilters" class="ms-trails__reset">Azzera filtri</button>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Mobile --}}
                <ul class="ms-trails__cards" aria-label="Sentieri della Sezione">
                    @foreach ($mobileRows as $trail)
                        @php
                            $url = $this->trailUrl($trail);
                            $shared = $this->sharedWith($trail);
                        @endphp
                        <li wire:key="card-{{ $trail->id }}">
                            <a href="{{ $url }}" class="ms-trails__card">
                                <x-cai.trail-sign :ref="$trail->ref" size="lg" />
                                <span class="ms-trails__card-body">
                                    <span class="ms-trails__card-heading">
                                        <span @class(['ms-trails__card-name', 'ms-trails__missing' => blank($trail->name)])>{{ filled($trail->name) ? $trail->name : '—' }}</span>
                                        <span class="ms-trails__route">{{ SectionTrails::route($trail) }}</span>
                                    </span>
                                    <span class="ms-trails__card-tags">
                                        <span class="ms-cai-trail-status ms-cai-trail-status--sm" style="--ms-cai-trail-status-color: {{ $trail->status->color() }}">{{ $trail->status->getLabel() }}</span>
                                        <span class="ms-trails__practicability ms-trails__practicability--sm" style="background: {{ $trail->practicability->background() }}; color: {{ $trail->practicability->foreground() }}">{{ $trail->practicability->getLabel() }}</span>
                                    </span>
                                    <span class="ms-trails__card-meta">{{ SectionTrails::trailKm($trail) }} km · Diff. {{ filled($trail->cai_scale) ? $trail->cai_scale : '—' }}</span>
                                    @if ($shared !== '')
                                        <span class="ms-trails__shared">Condiviso con {{ $shared }}</span>
                                    @endif
                                </span>
                                <span class="ms-trails__card-chevron" aria-hidden="true">›</span>
                            </a>
                        </li>
                    @endforeach

                    @if ($rows->isEmpty() && $trails->isNotEmpty())
                        <li class="ms-trails__no-match ms-trails__no-match--card">
                            Nessun sentiero corrisponde ai filtri.
                            <button type="button" wire:click="resetFilters" class="ms-trails__reset">Azzera filtri</button>
                        </li>
                    @endif

                    @if ($hiddenCount > 0)
                        <li>
                            <button type="button" wire:click="$set('showAll', true)" class="ms-trails__more">Mostra altri {{ $hiddenCount }}</button>
                        </li>
                    @endif
                </ul>

                <footer class="ms-trails__footer">
                    <span class="ms-trails__count">{{ $this->countLabel($rows) }}</span>
                    <span>Fonte: <a href="https://osm2cai.cai.it" target="_blank" rel="noopener">osm2cai</a> · catasto sentieri CAI</span>
                </footer>
            </section>

            <div class="ms-trails__map-card">
                <x-cai.map :name="$mapName" :lines="$this->lines($rows)" height="var(--ms-trails-map-height)" />
                <div class="ms-trails__map-legend">
                    <span class="ms-trails__map-legend-title">Stato di accatastamento</span>
                    <ul class="ms-trails__map-legend-list" aria-label="Legenda">
                        @foreach (CaiTrailStatus::cases() as $status)
                            <li class="ms-trails__map-legend-item">
                                <span class="ms-trails__map-legend-line" style="background: {{ $status->color() }}"></span>
                                {{ $status->getLabel() }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
