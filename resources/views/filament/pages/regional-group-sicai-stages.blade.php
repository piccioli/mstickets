{{-- Tappe SICAI del GR (Fase 10.9, US-1095), design docs/project/design/fase-10/tappe-sicai-del-gr.dc.html. Stili in
     resources/css/filament/admin/regional-group-sicai-stages.css (classi ms-gr-sicai__*); cartello del codice
     (ms-cai-trail-sign--sicai) in cai-trails.css. La mappa è il widget App\Filament\Widgets\RegionalGroupSicaiStagesMap
     (componente Livewire figlio, sempre reso fuori dagli @if): ricerca e filtri dell'elenco non rispediscono i tracciati.
     Tabelle desktop e card mobile sono rese entrambe, il CSS mostra le une o le altre. --}}
@use('App\Domain\CaiDirectory\Enums\CaiTrailPracticability')
@use('App\Filament\Pages\RegionalGroupSicaiStages')
@use('App\Filament\Widgets\RegionalGroupSicaiStagesMap')
@use('App\Support\Formatting\ItalianNumberFormatter')
@php
    $data = $this->data();
    $region = $this->region();
    $hasStages = $data !== null && $data->stagesCount() > 0;
    $pointsAnchor = RegionalGroupSicaiStages::POINTS_ANCHOR;
@endphp
<x-filament-panels::page>
    <div class="ms-gr-sicai">
        <div class="ms-gr-sicai__top">
            <a href="{{ $this->dashboardUrl() }}" class="ms-gr-sicai__back">← Dashboard</a>
            <div class="ms-gr-sicai__heading">
                <header class="ms-gr-sicai__title-block">
                    <h1 class="ms-gr-sicai__title">Tappe SICAI</h1>
                    @if ($data !== null && $region !== null)
                        <p class="ms-gr-sicai__subtitle">{{ $this->subtitle($data, $region) }}</p>
                    @endif
                </header>
                <p class="ms-gr-sicai__source">
                    Fonte: <a href="https://sentieroitalia.cai.it" target="_blank" rel="noopener">Sentiero Italia CAI</a>
                    · <a href="https://osm2cai.cai.it" target="_blank" rel="noopener">osm2cai</a>
                    · punti di appoggio: <a href="https://rifugi.cai.it" target="_blank" rel="noopener">rifugi.cai.it</a>
                </p>
            </div>
        </div>

        @if ($data === null)
            <p class="ms-section-dashboard__notice" role="status">
                Nessuna regione associata al tuo account: contatta Montagna Servizi.
            </p>
        @elseif (! $hasStages)
            <p class="ms-section-dashboard__notice" role="status">
                Nessuna tappa del Sentiero Italia CAI per la regione del {{ $data->groupName }}.
            </p>
        @else
            @php
                $summary = $this->summary($data);
                $notPracticable = $data->notPracticable;
                $withDifficulty = $data->withDifficulty;
                $hasProblems = $notPracticable !== [] || $withDifficulty !== [];
            @endphp
            {{-- ---- Riepilogo ---- --}}
            <section class="ms-gr-sicai__block" aria-labelledby="gr-sicai-summary">
                <h2 id="gr-sicai-summary" class="ms-gr-sicai__block-title ms-gr-sicai__desktop">Riepilogo</h2>
                <div class="ms-gr-sicai__summary">
                    <div class="ms-gr-sicai__kpis">
                        <div class="ms-gr-sicai__card ms-gr-sicai__kpi">
                            <span class="ms-gr-sicai__kpi-label">Tappe</span>
                            <span class="ms-gr-sicai__kpi-value">{{ $summary['stages'] }}</span>
                            <span class="ms-gr-sicai__kpi-note"><span class="ms-gr-sicai__desktop">{{ $summary['stagesNote'] }}</span><span class="ms-gr-sicai__mobile">{{ $summary['stagesNoteShort'] }}</span></span>
                        </div>
                        <div class="ms-gr-sicai__card ms-gr-sicai__kpi">
                            <span class="ms-gr-sicai__kpi-label"><span class="ms-gr-sicai__desktop">Km del Sentiero Italia</span><span class="ms-gr-sicai__mobile">Km</span></span>
                            <span class="ms-gr-sicai__kpi-value">{{ $summary['km'] }} <span class="ms-gr-sicai__kpi-unit ms-gr-sicai__desktop">km</span></span>
                            <span class="ms-gr-sicai__kpi-note"><span class="ms-gr-sicai__desktop">{{ $summary['kmNote'] }}</span><span class="ms-gr-sicai__mobile">{{ $summary['kmNoteShort'] }}</span></span>
                        </div>
                        <div class="ms-gr-sicai__card ms-gr-sicai__kpi">
                            <span class="ms-gr-sicai__kpi-label"><span class="ms-gr-sicai__desktop">Dislivello positivo</span><span class="ms-gr-sicai__mobile">Dislivello +</span></span>
                            <span @class(['ms-gr-sicai__kpi-value', 'ms-gr-sicai__kpi-value--empty' => $data->mainWithAscentCount === 0])>{{ $summary['ascent'] }}@if ($data->mainWithAscentCount > 0) <span class="ms-gr-sicai__kpi-unit">m</span>@endif</span>
                            <span class="ms-gr-sicai__kpi-note ms-gr-sicai__desktop">{{ $summary['ascentNote'] }}</span>
                        </div>
                        <a href="#{{ $pointsAnchor }}" class="ms-gr-sicai__card ms-gr-sicai__kpi ms-gr-sicai__kpi--link">
                            <span class="ms-gr-sicai__kpi-label">Punti di appoggio</span>
                            <span @class(['ms-gr-sicai__kpi-value', 'ms-gr-sicai__kpi-value--empty' => $data->restPointsCount === 0])>{{ $summary['points'] }}</span>
                            <span class="ms-gr-sicai__kpi-note ms-gr-sicai__desktop">{{ $summary['pointsNote'] }}</span>
                        </a>
                    </div>

                    <div @class([
                        'ms-gr-sicai__card',
                        'ms-gr-sicai__problems',
                        'ms-gr-sicai__problems--danger' => $notPracticable !== [],
                        'ms-gr-sicai__problems--warning' => $notPracticable === [] && $withDifficulty !== [],
                    ])>
                        <div class="ms-gr-sicai__problems-heading">
                            <span class="ms-gr-sicai__problems-title">Tappe con criticità</span>
                            @if ($hasProblems)
                                <span class="ms-gr-sicai__problems-km ms-gr-sicai__desktop">{{ ItalianNumberFormatter::kilometers($data->problemKm) }} coinvolti</span>
                            @endif
                        </div>
                        @if (! $hasProblems)
                            <p class="ms-gr-sicai__problems-none">Nessuna tappa non percorribile o con difficoltà.</p>
                        @endif
                        @if ($notPracticable !== [])
                            <div class="ms-gr-sicai__problems-group">
                                <span class="ms-gr-sicai__problems-group-title ms-gr-sicai__problems-group-title--danger"><span class="ms-gr-sicai__dot ms-gr-sicai__dot--danger"></span>{{ RegionalGroupSicaiStages::notPracticableTitle(count($notPracticable)) }}</span>
                                <div class="ms-gr-sicai__problem-chips">
                                    @foreach ($notPracticable as $row)
                                        <a href="{{ RegionalGroupSicaiStages::stageUrl($row->stage->cai_trail_id) }}" class="ms-gr-sicai__problem-chip ms-gr-sicai__problem-chip--danger" title="{{ $row->route() }}">
                                            {{ $row->code() }} <span class="ms-gr-sicai__problem-route">{{ $row->route() }}</span><span class="ms-gr-sicai__chevron ms-gr-sicai__mobile" aria-hidden="true">›</span>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                        @if ($withDifficulty !== [])
                            <div class="ms-gr-sicai__problems-group">
                                <span class="ms-gr-sicai__problems-group-title ms-gr-sicai__problems-group-title--warning"><span class="ms-gr-sicai__dot ms-gr-sicai__dot--warning"></span>{{ RegionalGroupSicaiStages::withDifficultyTitle(count($withDifficulty)) }}</span>
                                <div class="ms-gr-sicai__problem-chips ms-gr-sicai__problem-chips--inline">
                                    @foreach ($withDifficulty as $row)
                                        <a href="{{ RegionalGroupSicaiStages::stageUrl($row->stage->cai_trail_id) }}" class="ms-gr-sicai__problem-chip ms-gr-sicai__problem-chip--warning" title="{{ $row->route() }}">{{ $row->code() }}</a>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </section>
        @endif

        {{-- ---- Mappa (sempre resa: componente figlio) ---- --}}
        <section id="gr-sicai-map" @class(['ms-gr-sicai__block', 'ms-gr-sicai__block--hidden' => ! $hasStages]) aria-labelledby="gr-sicai-map-title">
            <div class="ms-gr-sicai__block-heading">
                <h2 id="gr-sicai-map-title" class="ms-gr-sicai__block-title">Mappa</h2>
                <span class="ms-gr-sicai__hint ms-gr-sicai__desktop">Trascina per spostarti · doppio clic o + per zoomare · clic su una tappa o su un punto di appoggio per l’anteprima</span>
            </div>
            @livewire(RegionalGroupSicaiStagesMap::class, [], key('gr-sicai-map'))
        </section>

        @if ($hasStages)
            @php
                $rows = $this->stageRows();
                $chips = $this->practicabilityChips();
                $mobileRows = $this->showAllStages ? $rows : array_slice($rows, 0, RegionalGroupSicaiStages::MOBILE_STAGES);
                $stageIds = $this->stageIdsByCode();
            @endphp
            {{-- ---- Elenco delle tappe ---- --}}
            <section id="gr-sicai-stages" class="ms-gr-sicai__block" aria-labelledby="gr-sicai-stages-title">
                <div class="ms-gr-sicai__block-heading">
                    <h2 id="gr-sicai-stages-title" class="ms-gr-sicai__block-title">Elenco delle tappe</h2>
                    <span class="ms-gr-sicai__hint ms-gr-sicai__desktop">In ordine di codice · clic su una tappa per aprire la scheda</span>
                </div>

                <div class="ms-gr-sicai__card ms-gr-sicai__list">
                    <div class="ms-gr-sicai__toolbar">
                        <input
                            type="search"
                            class="ms-gr-sicai__search"
                            placeholder="Cerca per codice o località"
                            aria-label="Cerca per codice o località"
                            wire:model.live.debounce.300ms="search"
                        >
                        <div class="ms-gr-sicai__chips" role="group" aria-label="Percorribilità">
                            @foreach ($chips as $chip)
                                <button
                                    type="button"
                                    wire:click="filterPracticability(@js($chip['key']))"
                                    aria-pressed="{{ $chip['active'] ? 'true' : 'false' }}"
                                    @class(['ms-gr-sicai__chip', 'is-active' => $chip['active'], 'is-empty' => $chip['count'] === 0])
                                >
                                    <span class="ms-gr-sicai__chip-dot" style="background: {{ $chip['dot'] ?? 'var(--ms-text-strong)' }}"></span>
                                    <span class="ms-gr-sicai__desktop">{{ $chip['label'] }}</span><span class="ms-gr-sicai__mobile">{{ $chip['shortLabel'] }}</span>
                                    <span class="ms-gr-sicai__chip-count">{{ $chip['count'] }}</span>
                                </button>
                            @endforeach
                        </div>
                    </div>
                    <span class="ms-gr-sicai__count ms-gr-sicai__mobile">{{ $this->stagesCountLabel(count($rows)) }}</span>

                    {{-- Desktop --}}
                    <div class="ms-gr-sicai__table" role="table" aria-label="Tappe">
                        <div class="ms-gr-sicai__stage-grid ms-gr-sicai__table-head" role="row">
                            <span role="columnheader">Codice</span>
                            <span role="columnheader">Partenza → arrivo</span>
                            <span role="columnheader" class="ms-gr-sicai__numeric">Km</span>
                            <span role="columnheader" class="ms-gr-sicai__numeric">Salita</span>
                            <span role="columnheader" class="ms-gr-sicai__numeric">Discesa</span>
                            <span role="columnheader">Difficoltà</span>
                            <span role="columnheader">Percorribilità</span>
                            <span role="columnheader" class="ms-gr-sicai__numeric">Punti vicini</span>
                            <span role="columnheader"></span>
                        </div>
                        <div class="ms-gr-sicai__table-body">
                            @foreach ($rows as $row)
                                @php
                                    $practicability = $row->practicability();
                                @endphp
                                <a
                                    href="{{ RegionalGroupSicaiStages::stageUrl($row->stage->cai_trail_id) }}"
                                    role="row"
                                    wire:key="stage-{{ $row->stage->cai_trail_id }}"
                                    @class([
                                        'ms-gr-sicai__stage-grid',
                                        'ms-gr-sicai__stage-row',
                                        'ms-gr-sicai__stage-row--variant' => $row->isVariant,
                                        'ms-gr-sicai__stage-row--critical' => $practicability === CaiTrailPracticability::NotPracticable,
                                    ])
                                >
                                    <span role="cell" class="ms-gr-sicai__code-cell"><x-cai.trail-sign :ref="$row->code()" class="ms-cai-trail-sign--sicai" /></span>
                                    <span role="cell" class="ms-gr-sicai__route-cell">
                                        <span class="ms-gr-sicai__route">{{ $row->route() }}</span>
                                        @if ($row->isVariant)
                                            <span class="ms-gr-sicai__variant">Variante della tappa {{ $row->mainCode }}</span>
                                        @endif
                                    </span>
                                    <span role="cell" class="ms-gr-sicai__numeric ms-gr-sicai__strong">{{ $row->km() ?? '—' }}</span>
                                    <span role="cell" @class(['ms-gr-sicai__numeric', 'ms-gr-sicai__missing' => $row->ascent() === null])>{{ $row->ascent() ?? '—' }}</span>
                                    <span role="cell" @class(['ms-gr-sicai__numeric', 'ms-gr-sicai__missing' => $row->descent() === null])>{{ $row->descent() ?? '—' }}</span>
                                    <span role="cell" @class(['ms-gr-sicai__difficulty', 'ms-gr-sicai__missing' => $row->difficulty() === null])>{{ $row->difficulty() ?? '—' }}</span>
                                    <span role="cell"><span class="ms-gr-sicai__pill" style="background: {{ $practicability->background() }}; color: {{ $practicability->foreground() }}">{{ $practicability->getLabel() }}</span></span>
                                    <span role="cell" @class(['ms-gr-sicai__numeric', 'ms-gr-sicai__strong', 'ms-gr-sicai__missing' => $row->restPointsCount === 0])>{{ $row->restPointsCount }}</span>
                                    <span role="cell" class="ms-gr-sicai__chevron" aria-hidden="true">›</span>
                                </a>
                            @endforeach
                            @if ($rows === [])
                                <div class="ms-gr-sicai__none">
                                    {{ $this->noStagesMessage() }}
                                    <button type="button" class="ms-gr-sicai__link" wire:click="resetFilters">Azzera filtri</button>
                                </div>
                            @endif
                        </div>
                        <div class="ms-gr-sicai__table-foot">
                            <span>{{ $this->stagesCountLabel(count($rows)) }}</span>
                            <span class="ms-gr-sicai__foot-note">Le varianti (suffisso S, N…) seguono la tappa principale e non entrano nei totali del riepilogo.</span>
                        </div>
                    </div>
                </div>

                {{-- Mobile --}}
                <div class="ms-gr-sicai__stage-cards">
                    @foreach ($mobileRows as $row)
                        @php
                                    $practicability = $row->practicability();
                                @endphp
                        <a
                            href="{{ RegionalGroupSicaiStages::stageUrl($row->stage->cai_trail_id) }}"
                            wire:key="stage-card-{{ $row->stage->cai_trail_id }}"
                            @class([
                                'ms-gr-sicai__card',
                                'ms-gr-sicai__stage-card',
                                'ms-gr-sicai__stage-card--variant' => $row->isVariant,
                                'ms-gr-sicai__stage-card--critical' => $practicability === CaiTrailPracticability::NotPracticable,
                            ])
                        >
                            <x-cai.trail-sign :ref="$row->code()" size="lg" class="ms-cai-trail-sign--sicai" />
                            <span class="ms-gr-sicai__stage-card-body">
                                <span class="ms-gr-sicai__stage-card-route">{{ $row->route() }}</span>
                                @if ($row->isVariant)
                                    <span class="ms-gr-sicai__variant">Variante della tappa {{ $row->mainCode }}</span>
                                @endif
                                <span class="ms-gr-sicai__stage-card-facts">{{ $row->km() ?? '—' }} km · +{{ $row->ascent() ?? '—' }} · −{{ $row->descent() ?? '—' }} · {{ $row->difficulty() ?? '—' }}</span>
                                <span class="ms-gr-sicai__stage-card-meta">
                                    <span class="ms-gr-sicai__pill ms-gr-sicai__pill--sm" style="background: {{ $practicability->background() }}; color: {{ $practicability->foreground() }}">{{ $practicability->getLabel() }}</span>
                                    <span @class(['ms-gr-sicai__near', 'ms-gr-sicai__missing' => $row->restPointsCount === 0])>{{ RegionalGroupSicaiStages::nearPointsLabel($row->restPointsCount) }}</span>
                                </span>
                            </span>
                            <span class="ms-gr-sicai__chevron" aria-hidden="true">›</span>
                        </a>
                    @endforeach
                    @if ($rows === [])
                        <p class="ms-gr-sicai__card ms-gr-sicai__none">{{ $this->noStagesMessage() }}</p>
                    @endif
                    @if (count($rows) > count($mobileRows))
                        <button type="button" class="ms-gr-sicai__more" wire:click="$set('showAllStages', true)">Mostra tutte le {{ count($rows) }} tappe</button>
                    @endif
                </div>
            </section>

            {{-- ---- Punti di appoggio ---- --}}
            <section id="{{ $pointsAnchor }}" class="ms-gr-sicai__block" aria-labelledby="gr-sicai-points-title">
                <div class="ms-gr-sicai__block-heading">
                    <h2 id="gr-sicai-points-title" class="ms-gr-sicai__block-title">Punti di appoggio</h2>
                    <span class="ms-gr-sicai__hint ms-gr-sicai__desktop">Strutture SICAI lungo il percorso, per tipo</span>
                </div>

                @if ($data->restPointsCount > 0)
                    @php
                        $groups = $this->pointGroups();
                        $flat = [];
                        foreach ($groups as $group) {
                            foreach ($group['rows'] as $pointRow) {
                                $flat[] = ['type' => $group['type'], 'row' => $pointRow];
                            }
                        }
                        $mobilePoints = $this->showAllPoints ? $flat : array_slice($flat, 0, RegionalGroupSicaiStages::MOBILE_POINTS);
                    @endphp
                    <div class="ms-gr-sicai__card ms-gr-sicai__list ms-gr-sicai__points">
                        <div class="ms-gr-sicai__toolbar">
                            <div class="ms-gr-sicai__chips" role="group" aria-label="Tipo di struttura">
                                @foreach ($this->pointTypeChips() as $chip)
                                    <button
                                        type="button"
                                        wire:click="filterPointType(@js($chip['key']))"
                                        aria-pressed="{{ $chip['active'] ? 'true' : 'false' }}"
                                        @class(['ms-gr-sicai__chip', 'is-active' => $chip['active']])
                                    >{{ $chip['label'] }} <span class="ms-gr-sicai__chip-count">{{ $chip['count'] }}</span></button>
                                @endforeach
                            </div>
                        </div>

                        {{-- Desktop --}}
                        <div class="ms-gr-sicai__table" role="table" aria-label="Punti di appoggio">
                            <div class="ms-gr-sicai__point-grid ms-gr-sicai__table-head" role="row">
                                <span role="columnheader"></span>
                                <span role="columnheader">Nome</span>
                                <span role="columnheader">Comune</span>
                                <span role="columnheader">Gestore</span>
                                <span role="columnheader">Contatti</span>
                                <span role="columnheader">Tappe vicine</span>
                                <span role="columnheader"></span>
                            </div>
                            <div class="ms-gr-sicai__table-body">
                                @foreach ($groups as $group)
                                    <div class="ms-gr-sicai__group-head" wire:key="point-group-{{ $group['type']->key() }}">
                                        <span class="ms-gr-sicai__dot" style="background: {{ $group['type']->color() }}"></span>
                                        <span class="ms-gr-sicai__group-title">{{ $group['type']->plural() }}</span>
                                        <span class="ms-gr-sicai__group-count">{{ count($group['rows']) }}</span>
                                    </div>
                                    @foreach ($group['rows'] as $pointRow)
                                        @include('filament.pages.partials.regional-group-sicai-point', ['type' => $group['type'], 'pointRow' => $pointRow, 'stageIds' => $stageIds, 'layout' => 'row'])
                                    @endforeach
                                @endforeach
                            </div>
                            <div class="ms-gr-sicai__table-foot ms-gr-sicai__table-foot--muted">
                                <span>{{ $this->pointsCountLabel() }}</span>
                                <span>Fonte: Sentiero Italia CAI · rifugi: <a href="https://rifugi.cai.it" target="_blank" rel="noopener">rifugi.cai.it</a></span>
                            </div>
                        </div>
                    </div>

                    {{-- Mobile --}}
                    <div class="ms-gr-sicai__point-cards">
                        @foreach ($mobilePoints as $item)
                            @include('filament.pages.partials.regional-group-sicai-point', ['type' => $item['type'], 'pointRow' => $item['row'], 'stageIds' => $stageIds, 'layout' => 'card'])
                        @endforeach
                        @if (count($flat) > count($mobilePoints))
                            <button type="button" class="ms-gr-sicai__more" wire:click="$set('showAllPoints', true)">Mostra tutti i {{ count($flat) }} punti</button>
                        @endif
                        <p class="ms-gr-sicai__footnote">Fonte: Sentiero Italia CAI · rifugi.cai.it</p>
                    </div>
                @else
                    <div class="ms-gr-sicai__card ms-gr-sicai__empty">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 11l9-7 9 7M5 9.5V20h14V9.5M10 20v-5h4v5"></path></svg>
                        <p class="ms-gr-sicai__empty-title">Nessun punto di appoggio SICAI censito in {{ $region?->label() }}</p>
                        <p class="ms-gr-sicai__empty-text">
                            <span class="ms-gr-sicai__desktop">Nessuna struttura risulta associata alle {{ $data->stagesCount() }} tappe della regione. I punti di appoggio compaiono qui quando vengono inseriti nella banca dati del Sentiero Italia CAI.</span>
                            <span class="ms-gr-sicai__mobile">Compariranno qui quando inseriti nella banca dati del Sentiero Italia CAI.</span>
                        </p>
                        <a href="https://sentieroitalia.cai.it" target="_blank" rel="noopener" class="ms-gr-sicai__link ms-gr-sicai__desktop">Vai a sentieroitalia.cai.it ↗</a>
                    </div>
                @endif
            </section>
        @endif
    </div>
</x-filament-panels::page>
