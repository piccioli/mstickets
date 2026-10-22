{{-- Dashboard V2 della Sezione (Fase 10.1, US-1004). Stili in resources/css/filament/admin/section-dashboard.css. --}}
@use('App\Filament\Pages\SectionFinancialStatements')
@use('App\Filament\Pages\SectionHuts')
@use('App\Filament\Pages\SectionMembers')
@use('App\Filament\Pages\SectionSicaiStages')
@use('App\Filament\Pages\SectionTrails')
@php
    $data = $this->metrics();
    $income = $data?->incomeStatement;
    $balance = $data?->balanceSheet;
    $financialStatementsUrl = SectionFinancialStatements::getUrl();
@endphp
<x-filament-panels::page>
    <div class="ms-section-dashboard">
        <header class="ms-section-dashboard__header">
            <h1 class="ms-section-dashboard__name">{{ $data?->name ?? $this->userName() }}</h1>
            @if ($data !== null)
                <p class="ms-section-dashboard__subtitle">{{ $this->subtitle($data) }}</p>
            @endif
        </header>

        @if ($data === null)
            <p class="ms-section-dashboard__notice" role="status">
                Nessuna Sezione CAI collegata al tuo account: contatta Montagna Servizi.
            </p>
        @endif

        <div class="ms-section-dashboard__balance-grid">
            <x-cai.metric-badge
                variant="bilancio"
                :href="$financialStatementsUrl"
                title="Conto economico"
                main-label="Risultato netto"
                :main-value="$income?->netResult"
                :year="$income?->year"
                :result="true"
                :secondary="[
                    ['label' => 'Ricavi', 'value' => $income?->revenues],
                    ['label' => 'Costi', 'value' => $income?->expenses],
                ]"
            />

            <x-cai.metric-badge
                variant="bilancio"
                :href="$financialStatementsUrl"
                title="Stato patrimoniale"
                main-label="Patrimonio netto"
                :main-value="$balance?->netEquity"
                :year="$balance?->year"
                :secondary="[
                    ['label' => 'Totale attivo', 'value' => $balance?->totalAssets],
                ]"
            />
        </div>

        <div class="ms-section-dashboard__simple-grid">
            <x-cai.metric-badge label="Soci" :value="$data?->members" :href="SectionMembers::getUrl()" />
            <x-cai.metric-badge label="Rifugi" :value="$data?->huts" :href="SectionHuts::getUrl()" />
            <x-cai.metric-badge
                label="Sentieri"
                :value="$data?->trailsKm"
                unit="km"
                :decimals="1"
                :href="SectionTrails::getUrl()"
            />
            <x-cai.metric-badge label="Tappe SICAI" :value="$data?->sicaiStages" :href="SectionSicaiStages::getUrl()" />
        </div>
    </div>
</x-filament-panels::page>
