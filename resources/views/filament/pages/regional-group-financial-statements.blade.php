{{-- Bilancio consolidato delle Sezioni del GR (Fase 10.3, US-1023). Stili in
     resources/css/filament/admin/regional-group-financial-statements.css; le card consolidate sono `x-cai.metric-badge`
     senza `href` (statiche). --}}
@use('App\Domain\CaiDirectory\Enums\CaiSectionStatementStatus')
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
                    @php($flaggedNote = RegionalGroupFinancialStatements::flaggedNote($card['flagged']))
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
        @endif
    </div>
</x-filament-panels::page>
