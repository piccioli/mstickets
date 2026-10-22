{{-- Bilancio consolidato delle Sezioni del GR (Fase 10.3, US-1023). Stili in
     resources/css/filament/admin/regional-group-financial-statements.css; le card consolidate sono `x-cai.metric-badge`
     senza `href` (statiche); i valori della tabella delle Sezioni (US-1024) sono `x-cai.consolidated-value`. La tabella
     (desktop) e l'elenco di card (mobile, < 640px) sono resi entrambi: il CSS mostra l'uno o l'altro. --}}
@use('App\Domain\CaiDirectory\Enums\CaiConsolidatedSortColumn')
@use('App\Domain\CaiDirectory\Enums\CaiSectionStatementStatus')
@use('App\Domain\CaiDirectory\Support\CaiConsolidatedSectionRow')
@use('App\Filament\Pages\RegionalGroupFinancialStatements')
@php
    $data = $this->consolidated();
@endphp
<x-filament-panels::page>
    <div class="ms-consolidated">
        <div class="ms-consolidated__top">
            <a href="{{ $this->dashboardUrl() }}" class="ms-consolidated__back">← Dashboard</a>

            <div class="ms-consolidated__heading">
                <header class="ms-consolidated__title-block">
                    <h1 class="ms-consolidated__title">Bilancio consolidato Sezioni</h1>
                    @if ($data !== null)
                        <p class="ms-consolidated__subtitle">{{ $this->subtitle($data) }}</p>
                    @endif
                </header>

                @if ($data !== null)
                    <div class="ms-consolidated__years">
                        <span class="ms-consolidated__years-label">Esercizio</span>
                        <div class="ms-consolidated__years-group" role="group" aria-label="Esercizio">
                            @foreach ($data->availableYears as $availableYear)
                                <button
                                    type="button"
                                    wire:click="selectYear({{ $availableYear }})"
                                    aria-pressed="{{ $availableYear === $data->year ? 'true' : 'false' }}"
                                    @class(['ms-consolidated__year', 'ms-consolidated__year--active' => $availableYear === $data->year])
                                >{{ $availableYear }}</button>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>

        @if ($data === null)
            <p class="ms-section-dashboard__notice" role="status">
                Nessuna regione associata al tuo account: contatta Montagna Servizi.
            </p>
        @else
            <div class="ms-consolidated__cards">
                @foreach ([
                    [
                        'title' => 'Conto economico consolidato',
                        'mainLabel' => 'Risultato netto',
                        'mainValue' => $data->netResult,
                        'result' => true,
                        'secondary' => [
                            ['label' => 'Ricavi', 'value' => $data->revenues],
                            ['label' => 'Costi', 'value' => $data->expenses],
                        ],
                        'count' => $data->incomeStatementSectionsCount,
                        'flagged' => $data->incomeStatementFlaggedCount,
                    ],
                    [
                        'title' => 'Stato patrimoniale consolidato',
                        'mainLabel' => 'Patrimonio netto',
                        'mainValue' => $data->netEquity,
                        'result' => false,
                        'secondary' => [
                            ['label' => 'Totale attivo', 'value' => $data->totalAssets],
                        ],
                        'count' => $data->balanceSheetSectionsCount,
                        'flagged' => $data->balanceSheetFlaggedCount,
                    ],
                ] as $card)
                    @php
                        $flaggedNote = RegionalGroupFinancialStatements::flaggedNote($card['flagged']);
                    @endphp
                    <div class="ms-consolidated__card">
                        <x-cai.metric-badge
                            variant="bilancio"
                            :title="$card['title']"
                            :main-label="$card['mainLabel']"
                            :main-value="$card['mainValue']"
                            :year="$data->year"
                            :result="$card['result']"
                            :secondary="$card['secondary']"
                        />
                        <p class="ms-consolidated__card-note">
                            <span>{{ RegionalGroupFinancialStatements::sumNote($card['count'], $data->sectionsCount) }}</span>
                            @if ($flaggedNote !== null)
                                <span class="ms-consolidated__flagged">
                                    <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z" />
                                        <path d="M12 9v4M12 17h.01" />
                                    </svg>
                                    {{ $flaggedNote }}
                                </span>
                            @endif
                        </p>
                    </div>
                @endforeach
            </div>

            <section class="ms-consolidated__coverage" aria-label="Copertura">
                <div class="ms-consolidated__coverage-head">
                    <h2 class="ms-consolidated__coverage-title">Copertura</h2>
                    <p class="ms-consolidated__coverage-count">
                        <strong>{{ $data->dataCount }}</strong>
                        {{ RegionalGroupFinancialStatements::coverageLabel($data->sectionsCount) }}
                    </p>
                    <p class="ms-consolidated__coverage-note">{{ RegionalGroupFinancialStatements::coverageNote($data) }}</p>
                </div>

                <div class="ms-consolidated__bar" aria-hidden="true">
                    @foreach (CaiSectionStatementStatus::cases() as $status)
                        @if ($data->countFor($status) > 0)
                            <span
                                class="ms-consolidated__bar-segment ms-consolidated__bar-segment--{{ $status->value }}"
                                style="flex-grow: {{ $data->countFor($status) }}"
                            ></span>
                        @endif
                    @endforeach
                </div>

                <ul class="ms-consolidated__legend">
                    @foreach (CaiSectionStatementStatus::cases() as $status)
                        <li class="ms-consolidated__legend-item">
                            <span class="ms-consolidated__legend-swatch ms-consolidated__bar-segment--{{ $status->value }}"></span>
                            {{ $status->getLabel() }}
                            <strong>{{ $data->countFor($status) }}</strong>
                        </li>
                    @endforeach
                </ul>
            </section>

            @php
                $rows = $this->rows($data);
                $activeStatus = $this->activeStatus();
                $sortColumn = $this->sortColumn();
                $sortDescending = $this->sortDescending();
                $numericColumns = [
                    ['column' => CaiConsolidatedSortColumn::Revenues, 'field' => 'revenues', 'flag' => CaiConsolidatedSectionRow::REVENUES],
                    ['column' => CaiConsolidatedSortColumn::Expenses, 'field' => 'expenses', 'flag' => CaiConsolidatedSectionRow::EXPENSES],
                    ['column' => CaiConsolidatedSortColumn::NetResult, 'field' => 'netResult', 'flag' => null],
                    ['column' => CaiConsolidatedSortColumn::NetEquity, 'field' => 'netEquity', 'flag' => CaiConsolidatedSectionRow::NET_EQUITY],
                    ['column' => CaiConsolidatedSortColumn::TotalAssets, 'field' => 'totalAssets', 'flag' => CaiConsolidatedSectionRow::TOTAL_ASSETS],
                ];
                $mobileValues = [
                    ['label' => 'Ricavi', 'field' => 'revenues', 'flag' => CaiConsolidatedSectionRow::REVENUES],
                    ['label' => 'Costi', 'field' => 'expenses', 'flag' => CaiConsolidatedSectionRow::EXPENSES],
                    ['label' => 'Patrimonio netto', 'field' => 'netEquity', 'flag' => CaiConsolidatedSectionRow::NET_EQUITY],
                    ['label' => 'Totale attivo', 'field' => 'totalAssets', 'flag' => CaiConsolidatedSectionRow::TOTAL_ASSETS],
                ];
            @endphp
            <section class="ms-consolidated__sections" aria-label="Sezioni del GR">
                <div class="ms-consolidated__sections-head">
                    <div class="ms-consolidated__sections-title-block">
                        <h2 class="ms-consolidated__sections-title">Sezioni del GR</h2>
                        <span class="ms-consolidated__sections-count">{{ $this->rowsCountLabel($data, count($rows)) }}</span>
                    </div>

                    <div class="ms-consolidated__filters" role="group" aria-label="Filtra per stato">
                        @foreach ([null, ...CaiSectionStatementStatus::cases()] as $filter)
                            <button
                                type="button"
                                wire:click="filterStatus(@js($filter?->value))"
                                aria-pressed="{{ $filter === $activeStatus ? 'true' : 'false' }}"
                                @class(['ms-consolidated__filter', 'ms-consolidated__filter--active' => $filter === $activeStatus])
                            >
                                {{ $filter?->getLabel() ?? 'Tutte' }}
                                <span class="ms-consolidated__filter-count">{{ $filter === null ? $data->sectionsCount : $data->countFor($filter) }}</span>
                            </button>
                        @endforeach
                    </div>

                    <label class="ms-consolidated__sort-menu">
                        <span class="ms-consolidated__sort-menu-label">Ordina per</span>
                        <select wire:change="applySortOption($event.target.value)" class="ms-consolidated__sort-select">
                            @foreach (RegionalGroupFinancialStatements::sortOptions() as $option => $optionLabel)
                                <option value="{{ $option }}" @selected($option === $this->sortOption())>{{ $optionLabel }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>

                {{-- Desktop --}}
                <div class="ms-consolidated__table" role="table" aria-label="Sezioni del GR">
                    <div class="ms-consolidated__table-row ms-consolidated__table-row--head" role="row">
                        @foreach (CaiConsolidatedSortColumn::cases() as $column)
                            @php
                                $active = $column === $sortColumn;
                            @endphp
                            <div
                                role="columnheader"
                                aria-sort="{{ $active ? ($sortDescending ? 'descending' : 'ascending') : 'none' }}"
                                @class(['ms-consolidated__th', 'ms-consolidated__th--numeric' => $column->isNumeric()])
                            >
                                <button
                                    type="button"
                                    wire:click="sortBy('{{ $column->value }}')"
                                    @class(['ms-consolidated__sort', 'ms-consolidated__sort--active' => $active])
                                >
                                    {{ $column->getLabel() }}
                                    <span class="ms-consolidated__sort-arrow" aria-hidden="true">{{ $active ? ($sortDescending ? '↓' : '↑') : '↕' }}</span>
                                </button>
                            </div>
                        @endforeach
                        <div role="columnheader" class="ms-consolidated__th ms-consolidated__th--pdf">PDF</div>
                    </div>

                    @forelse ($rows as $row)
                        @php
                            $documentUrl = RegionalGroupFinancialStatements::documentUrl($row);
                        @endphp
                        <div class="ms-consolidated__table-row" role="row" wire:key="row-{{ $row->codiceCai }}">
                            <div role="cell" class="ms-consolidated__name">{{ $row->name }}</div>
                            <div role="cell">
                                <span class="ms-consolidated__status ms-consolidated__status--{{ $row->status->value }}">{{ $row->status->getLabel() }}</span>
                            </div>
                            <div role="cell" class="ms-consolidated__source">
                                @if ($row->source === null)
                                    <span class="ms-consolidated__missing">—</span>
                                @else
                                    {{ $row->source->getLabel() }}
                                @endif
                            </div>
                            @foreach ($numericColumns as $numeric)
                                <div role="cell" class="ms-consolidated__number">
                                    <x-cai.consolidated-value
                                        :value="$row->{$numeric['field']}"
                                        :signed="$numeric['flag'] === null"
                                        :flagged="$numeric['flag'] !== null && $row->isFlagged($numeric['flag'])"
                                    />
                                </div>
                            @endforeach
                            <div role="cell" class="ms-consolidated__pdf-cell">
                                @if ($documentUrl !== null)
                                    <a href="{{ $documentUrl }}" target="_blank" rel="noopener" class="ms-consolidated__pdf" title="Apri PDF" aria-label="Apri PDF di {{ $row->name }}">
                                        <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z" />
                                            <path d="M14 2v6h6M9 13h6M9 17h6" />
                                        </svg>
                                    </a>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="ms-consolidated__empty">{{ RegionalGroupFinancialStatements::emptyFilterNote($data->year) }}</p>
                    @endforelse

                    <div class="ms-consolidated__table-row ms-consolidated__table-row--total" role="row">
                        <div role="cell" class="ms-consolidated__total-label">
                            <span class="ms-consolidated__total-title">Totale</span>
                            <span class="ms-consolidated__total-note">{{ RegionalGroupFinancialStatements::totalNote($data->dataCount) }}</span>
                        </div>
                        <div role="cell"></div>
                        <div role="cell"></div>
                        @foreach ($numericColumns as $numeric)
                            <div role="cell" class="ms-consolidated__number">
                                <x-cai.consolidated-value :value="$data->{$numeric['field']}" :signed="$numeric['flag'] === null" />
                            </div>
                        @endforeach
                        <div role="cell"></div>
                    </div>
                </div>

                {{-- Mobile --}}
                <ul class="ms-consolidated__list" aria-label="Sezioni del GR">
                    @forelse ($rows as $row)
                        @php
                            $documentUrl = RegionalGroupFinancialStatements::documentUrl($row);
                        @endphp
                        <li class="ms-consolidated__item" wire:key="item-{{ $row->codiceCai }}">
                            <div class="ms-consolidated__item-head">
                                <div class="ms-consolidated__item-title">
                                    <span class="ms-consolidated__item-name">{{ $row->name }}</span>
                                    @if ($row->source !== null)
                                        <span class="ms-consolidated__item-source">Fonte: {{ $row->source->getLabel() }}</span>
                                    @endif
                                </div>
                                <span class="ms-consolidated__status ms-consolidated__status--{{ $row->status->value }}">{{ $row->status->getLabel() }}</span>
                            </div>

                            @if ($row->status === CaiSectionStatementStatus::Data)
                                <dl class="ms-consolidated__item-values">
                                    @foreach ($mobileValues as $mobileValue)
                                        <div class="ms-consolidated__item-value">
                                            <dt>{{ $mobileValue['label'] }}</dt>
                                            <dd>
                                                <x-cai.consolidated-value :value="$row->{$mobileValue['field']}" :flagged="$row->isFlagged($mobileValue['flag'])" />
                                            </dd>
                                        </div>
                                    @endforeach
                                    <div class="ms-consolidated__item-value ms-consolidated__item-value--wide">
                                        <dt>Risultato netto</dt>
                                        <dd><x-cai.consolidated-value :value="$row->netResult" signed /></dd>
                                    </div>
                                </dl>
                            @elseif ($row->status === CaiSectionStatementStatus::PdfOnly)
                                <p class="ms-consolidated__item-note">Dati non ancora estratti dal PDF.</p>
                            @endif

                            @if ($documentUrl !== null)
                                <a href="{{ $documentUrl }}" target="_blank" rel="noopener" class="ms-consolidated__item-pdf">
                                    <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z" />
                                        <path d="M14 2v6h6M9 13h6M9 17h6" />
                                    </svg>
                                    Apri PDF
                                </a>
                            @endif
                        </li>
                    @empty
                        <li class="ms-consolidated__empty">{{ RegionalGroupFinancialStatements::emptyFilterNote($data->year) }}</li>
                    @endforelse

                    <li class="ms-consolidated__item ms-consolidated__item--total">
                        <div class="ms-consolidated__item-title">
                            <span class="ms-consolidated__item-name">Totale</span>
                            <span class="ms-consolidated__item-source">{{ RegionalGroupFinancialStatements::totalNote($data->dataCount) }}</span>
                        </div>
                        <dl class="ms-consolidated__item-values">
                            @foreach ($mobileValues as $mobileValue)
                                <div class="ms-consolidated__item-value">
                                    <dt>{{ $mobileValue['label'] }}</dt>
                                    <dd><x-cai.consolidated-value :value="$data->{$mobileValue['field']}" /></dd>
                                </div>
                            @endforeach
                            <div class="ms-consolidated__item-value ms-consolidated__item-value--wide">
                                <dt>Risultato netto</dt>
                                <dd><x-cai.consolidated-value :value="$data->netResult" signed /></dd>
                            </div>
                        </dl>
                    </li>
                </ul>
            </section>
        @endif
    </div>
</x-filament-panels::page>
