{{-- Rifugi di Sezione (Fase 10.4, US-1034), design docs/project/design/fase-10/rifugi-di-sezione.dc.html §1. Stili in
     resources/css/filament/admin/section-huts.css. Una sola mappa (`x-cai.map`, sempre resa) per desktop e mobile: il
     CSS la mette a destra dell'elenco su desktop e sopra su mobile (< 640px). Tabella (desktop) ed elenco di card
     (mobile, primi 12 + "Mostra altri N") sono resi entrambi, il CSS mostra l'uno o l'altro. Righe e marker si
     evidenziano a vicenda con gli eventi `cai-map-*` (vedi il componente). --}}
@use('App\Domain\CaiDirectory\Enums\CaiHutSort')
@use('App\Domain\CaiDirectory\Enums\CaiHutStatus')
@use('App\Domain\CaiDirectory\Enums\CaiHutType')
@use('App\Filament\Pages\SectionHuts')
@php
    $section = $this->section();
    $subsection = $section === null ? $this->subsection() : null;
    $huts = $this->huts();
    $rows = $this->rows();
    $activeTypes = $this->activeTypes();
    $activeStatus = $this->activeStatus();
    $activeSort = $this->activeSort();
    $mobileRows = $this->showAll ? $rows : $rows->take(SectionHuts::MOBILE_PAGE_SIZE);
    $hiddenCount = $rows->count() - $mobileRows->count();
    $lastUpdate = $this->lastUpdate();
    $mapName = SectionHuts::MAP_NAME;
    $placeholder = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 19l6-9 4 5 3-3 5 7z"></path></svg>';
@endphp
<x-filament-panels::page>
    <div class="ms-huts">
        <div class="ms-huts__top">
            <a href="{{ $this->dashboardUrl() }}" class="ms-huts__back">← Dashboard</a>

            <div class="ms-huts__heading">
                <header class="ms-huts__title-block">
                    <h1 class="ms-huts__title">Rifugi di Sezione</h1>
                    <p class="ms-huts__subtitle">{{ $section?->name ?? $subsection?->name ?? auth()->user()?->name }}</p>
                </header>

                <div class="ms-huts__kpis">
                    <div class="ms-huts__kpi">
                        <span class="ms-huts__kpi-label">
                            <span class="ms-huts__kpi-label--long">Rifugi in attività / totale</span>
                            <span class="ms-huts__kpi-label--short">In attività / totale</span>
                        </span>
                        <span class="ms-huts__kpi-value">
                            {{ $this->activeCount() }}
                            @if ($this->totalCount() !== '')
                                <span class="ms-huts__kpi-total">{{ $this->totalCount() }}</span>
                            @endif
                        </span>
                    </div>
                    <div class="ms-huts__kpi">
                        <span class="ms-huts__kpi-label">Posti letto totali</span>
                        <span class="ms-huts__kpi-value">{{ $this->bedsCount() }}</span>
                    </div>
                </div>
            </div>
        </div>

        @if ($section === null)
            <p class="ms-section-dashboard__notice" role="status">
                @if ($subsection !== null)
                    I rifugi sono intestati alle Sezioni: per la Sottosezione non sono disponibili.
                @else
                    Nessuna Sezione CAI collegata al tuo account: contatta Montagna Servizi.
                @endif
            </p>
        @elseif ($huts->isEmpty())
            <div class="ms-huts__empty">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 11l9-7 9 7M5 9.5V20h14V9.5M10 20v-5h4v5"></path></svg>
                <p class="ms-huts__empty-title">Nessun rifugio associato a questa Sezione nella banca dati rifugi.cai.it</p>
                <a href="https://rifugi.cai.it" target="_blank" rel="noopener" class="ms-huts__empty-link">Vai a rifugi.cai.it ↗</a>
            </div>
        @endif

        <div
            @class(['ms-huts__body', 'ms-huts__body--hidden' => $huts->isEmpty()])
            x-data="{ active: null, hovered: null }"
            x-on:cai-map-marker-selected.window="if ($event.detail.map === @js($mapName)) active = $event.detail.id"
            x-on:cai-map-marker-hovered.window="if ($event.detail.map === @js($mapName)) hovered = $event.detail.id"
        >
            <section class="ms-huts__panel" aria-label="Elenco dei rifugi">
                <div class="ms-huts__filters">
                    <div class="ms-huts__chips" role="group" aria-label="Filtra per tipo">
                        <button
                            type="button"
                            wire:click="clearTypes"
                            aria-pressed="{{ $activeTypes === [] ? 'true' : 'false' }}"
                            @class(['ms-huts__chip', 'ms-huts__chip--active' => $activeTypes === []])
                        >
                            <span class="ms-huts__chip-label--long">Tutti i tipi</span>
                            <span class="ms-huts__chip-label--short">Tutti</span>
                        </button>
                        @foreach (CaiHutType::cases() as $type)
                            @php
                                $on = in_array($type, $activeTypes, true);
                            @endphp
                            <button
                                type="button"
                                wire:click="toggleType('{{ $type->key() }}')"
                                aria-pressed="{{ $on ? 'true' : 'false' }}"
                                @class(['ms-huts__chip', 'ms-huts__chip--active' => $on])
                            >
                                <span class="ms-huts__dot" style="background: {{ $type->color() }}"></span>
                                {{ $type->getLabel() }}
                                <span class="ms-huts__chip-count">{{ $this->typeCount($type) }}</span>
                            </button>
                        @endforeach
                    </div>

                    <div class="ms-huts__selects">
                        <select wire:change="filterStatus($event.target.value)" class="ms-huts__select" aria-label="Filtra per stato">
                            <option value="">Tutti gli stati</option>
                            @foreach (CaiHutStatus::cases() as $statusOption)
                                <option value="{{ $statusOption->key() }}" @selected($statusOption === $activeStatus)>{{ $statusOption->getLabel() }}</option>
                            @endforeach
                        </select>
                        <select wire:change="applySortOption($event.target.value)" class="ms-huts__select ms-huts__select--sort" aria-label="Ordina">
                            @foreach (CaiHutSort::cases() as $sortOption)
                                <option value="{{ $sortOption->value }}" @selected($sortOption === $activeSort)>{{ $sortOption->getLabel() }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <p class="ms-huts__count ms-huts__count--mobile">{{ $this->countLabel($rows->count()) }}</p>

                {{-- Desktop --}}
                <div class="ms-huts__table" role="table" aria-label="Rifugi della Sezione">
                    <div class="ms-huts__row ms-huts__row--head" role="row">
                        <span role="columnheader"></span>
                        <span role="columnheader" aria-sort="{{ $activeSort->isByName() ? ($activeSort->isDescending() ? 'descending' : 'ascending') : 'none' }}">
                            <button type="button" wire:click="sortByName" @class(['ms-huts__sort', 'ms-huts__sort--active' => $activeSort->isByName()])>
                                Nome {{ $activeSort->isByName() ? ($activeSort->isDescending() ? '↓' : '↑') : '' }}
                            </button>
                        </span>
                        <span role="columnheader">Tipo</span>
                        <span role="columnheader">Stato</span>
                        <span role="columnheader" class="ms-huts__numeric" aria-sort="{{ $activeSort->isByName() ? 'none' : ($activeSort->isDescending() ? 'descending' : 'ascending') }}">
                            <button type="button" wire:click="sortByAltitude" @class(['ms-huts__sort', 'ms-huts__sort--active' => ! $activeSort->isByName()])>
                                Quota (m) {{ $activeSort->isByName() ? '' : ($activeSort->isDescending() ? '↓' : '↑') }}
                            </button>
                        </span>
                        <span role="columnheader">Comune</span>
                        <span role="columnheader" class="ms-huts__numeric">Posti letto</span>
                    </div>

                    <div class="ms-huts__rows">
                        @foreach ($rows as $hut)
                            @php
                                $url = $this->hutUrl($hut);
                                $photo = SectionHuts::photo($hut);
                                $hutEvent = ['map' => $mapName, 'id' => $hut->id_cai];
                            @endphp
                            <a
                                @if ($url !== null) href="{{ $url }}" @endif
                                class="ms-huts__row"
                                role="row"
                                wire:key="row-{{ $hut->id_cai }}"
                                data-hut="{{ $hut->id_cai }}"
                                x-bind:class="{ 'ms-huts__row--active': active === {{ $hut->id_cai }} || hovered === {{ $hut->id_cai }} }"
                                x-on:mouseenter="hovered = {{ $hut->id_cai }}; $dispatch('cai-map-highlight', @js($hutEvent))"
                                x-on:mouseleave="hovered = null; $dispatch('cai-map-highlight', @js(['map' => $mapName, 'id' => null]))"
                                @if ($url === null) x-on:click="active = {{ $hut->id_cai }}; $dispatch('cai-map-select', @js($hutEvent))" @endif
                            >
                                <span role="cell" class="ms-huts__thumb">
                                    {!! $placeholder !!}
                                    @if ($photo !== null)
                                        <img src="{{ $photo }}" alt="" loading="lazy" onerror="this.remove()">
                                    @endif
                                </span>
                                <span role="cell" class="ms-huts__name-cell">
                                    <span class="ms-huts__name">{{ $hut->name }}</span>
                                    @if (filled($hut->alias))
                                        <span class="ms-huts__alias">{{ $hut->alias }}</span>
                                    @endif
                                </span>
                                <span role="cell" class="ms-huts__type">
                                    <span class="ms-huts__dot" style="background: {{ $hut->type?->color() ?? '#5b6475' }}"></span>
                                    {{ $hut->type?->getLabel() ?? '—' }}
                                </span>
                                <span role="cell">
                                    @if ($hut->status !== null)
                                        <span class="ms-huts__status" style="background: {{ $hut->status->background() }}; color: {{ $hut->status->foreground() }}">{{ $hut->status->getLabel() }}</span>
                                    @else
                                        <span class="ms-huts__missing">—</span>
                                    @endif
                                </span>
                                <span role="cell" class="ms-huts__numeric ms-huts__strong">{{ SectionHuts::altitude($hut) }}</span>
                                <span role="cell" class="ms-huts__place">{{ SectionHuts::place($hut) }}</span>
                                <span role="cell" class="ms-huts__numeric ms-huts__strong">{{ SectionHuts::beds($hut) }}</span>
                            </a>
                        @endforeach

                        @if ($rows->isEmpty() && $huts->isNotEmpty())
                            <div class="ms-huts__no-match">
                                Nessun rifugio corrisponde ai filtri selezionati.
                                <button type="button" wire:click="resetFilters" class="ms-huts__reset">Azzera filtri</button>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Mobile --}}
                <ul class="ms-huts__cards" aria-label="Rifugi della Sezione">
                    @foreach ($mobileRows as $hut)
                        @php
                            $url = $this->hutUrl($hut);
                            $photo = SectionHuts::photo($hut);
                        @endphp
                        <li wire:key="card-{{ $hut->id_cai }}">
                            <a
                                @if ($url !== null) href="{{ $url }}" @endif
                                class="ms-huts__card"
                                @if ($url === null) x-on:click="active = {{ $hut->id_cai }}; $dispatch('cai-map-select', @js(['map' => $mapName, 'id' => $hut->id_cai]))" @endif
                            >
                                <span class="ms-huts__thumb ms-huts__thumb--card">
                                    {!! $placeholder !!}
                                    @if ($photo !== null)
                                        <img src="{{ $photo }}" alt="" loading="lazy" onerror="this.remove()">
                                    @endif
                                </span>
                                <span class="ms-huts__card-body">
                                    <span class="ms-huts__card-name">{{ $hut->name }}</span>
                                    @if (filled($hut->alias))
                                        <span class="ms-huts__alias">{{ $hut->alias }}</span>
                                    @endif
                                    <span class="ms-huts__card-tags">
                                        <span class="ms-huts__type">
                                            <span class="ms-huts__dot" style="background: {{ $hut->type?->color() ?? '#5b6475' }}"></span>
                                            {{ $hut->type?->getLabel() ?? '—' }}
                                        </span>
                                        @if ($hut->status !== null)
                                            <span class="ms-huts__status" style="background: {{ $hut->status->background() }}; color: {{ $hut->status->foreground() }}">{{ $hut->status->getLabel() }}</span>
                                        @endif
                                    </span>
                                    <span class="ms-huts__card-meta">{{ SectionHuts::altitude($hut) }} m · {{ SectionHuts::place($hut) }} · {{ SectionHuts::beds($hut) }} posti</span>
                                </span>
                            </a>
                        </li>
                    @endforeach

                    @if ($rows->isEmpty() && $huts->isNotEmpty())
                        <li class="ms-huts__no-match ms-huts__no-match--card">
                            Nessun rifugio corrisponde ai filtri.
                            <button type="button" wire:click="resetFilters" class="ms-huts__reset">Azzera filtri</button>
                        </li>
                    @endif

                    @if ($hiddenCount > 0)
                        <li>
                            <button type="button" wire:click="$set('showAll', true)" class="ms-huts__more">Mostra altri {{ $hiddenCount }}</button>
                        </li>
                    @endif
                </ul>

                <footer class="ms-huts__footer">
                    <span class="ms-huts__count">{{ $this->countLabel($rows->count()) }}</span>
                    <span>
                        Fonte: <a href="https://rifugi.cai.it" target="_blank" rel="noopener">rifugi.cai.it</a>
                        @if ($lastUpdate !== null)
                            · dati aggiornati al {{ $lastUpdate->locale('it')->translatedFormat('j F Y') }}
                        @endif
                    </span>
                </footer>
            </section>

            <div class="ms-huts__map-card">
                <x-cai.map :name="$mapName" :markers="$this->markers($rows)" height="var(--ms-huts-map-height)" />
                <ul class="ms-huts__legend" aria-label="Legenda">
                    @foreach (CaiHutType::cases() as $type)
                        <li class="ms-huts__legend-item">
                            <span class="ms-huts__legend-marker" style="background: {{ $type->color() }}">{{ $type->letter() }}</span>
                            {{ $type->getLabel() }}
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</x-filament-panels::page>
