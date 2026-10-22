{{-- Dashboard V2 del Gruppo Regionale (Fase 10.2, US-1014). Stili in resources/css/filament/admin/section-dashboard.css
     (intestazione e griglie riusate dalla 10.1) + regional-group-dashboard.css (griglie del GR). --}}
@use('App\Filament\Pages\CustomerRegionalSectionsDashboard')
@use('App\Filament\Pages\RegionalGroupDashboard')
@use('App\Filament\Pages\RegionalGroupHuts')
@use('App\Filament\Pages\RegionalGroupMembers')
@use('App\Filament\Pages\RegionalGroupSicaiStages')
@use('App\Filament\Pages\RegionalGroupTrails')
@php
    $region = $this->region();
    $data = $this->metrics();
    $income = $data?->incomeStatement;
    $balance = $data?->balanceSheet;
    $consolidatedIncome = $data?->consolidatedIncomeStatement;
    $consolidatedBalance = $data?->consolidatedBalanceSheet;
    $financialStatementsUrl = RegionalGroupDashboard::financialStatementsUrl();
@endphp
<x-filament-panels::page>
    <div class="ms-section-dashboard ms-regional-group-dashboard">
        <header class="ms-section-dashboard__header">
            <h1 class="ms-section-dashboard__name">{{ $this->userName() }}</h1>
            @if ($region !== null && $data !== null)
                <p class="ms-section-dashboard__subtitle">
                    <span>{{ $this->subtitle($region, $data) }}</span>
                </p>
            @endif
        </header>

        @if ($region === null)
            <p class="ms-section-dashboard__notice" role="status">
                Nessuna regione associata al tuo account: contatta Montagna Servizi.
            </p>
        @endif

        <div class="ms-section-dashboard__balance-grid">
            <x-cai.metric-badge
                variant="bilancio"
                :href="$financialStatementsUrl"
                link-label="Bilancio"
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
                link-label="Bilancio"
                title="Stato patrimoniale"
                main-label="Patrimonio netto"
                :main-value="$balance?->netEquity"
                :year="$balance?->year"
                :secondary="[
                    ['label' => 'Totale attivo', 'value' => $balance?->totalAssets],
                ]"
            />
        </div>

        <div class="ms-section-dashboard__balance-grid">
            <x-cai.metric-badge
                variant="bilancio"
                :href="RegionalGroupDashboard::financialStatementsUrl($consolidatedIncome?->year)"
                link-label="Bilancio"
                title="Conto economico consolidato Sezioni"
                main-label="Risultato netto"
                :main-value="$consolidatedIncome?->netResult"
                :year="$consolidatedIncome?->year"
                :year-note="$consolidatedIncome === null ? null : RegionalGroupDashboard::sumNote($consolidatedIncome->sectionsCount)"
                :result="true"
                :secondary="[
                    ['label' => 'Ricavi', 'value' => $consolidatedIncome?->revenues],
                    ['label' => 'Costi', 'value' => $consolidatedIncome?->expenses],
                ]"
            />

            <x-cai.metric-badge
                variant="bilancio"
                :href="RegionalGroupDashboard::financialStatementsUrl($consolidatedBalance?->year)"
                link-label="Bilancio"
                title="Stato patrimoniale consolidato Sezioni"
                main-label="Patrimonio netto"
                :main-value="$consolidatedBalance?->netEquity"
                :year="$consolidatedBalance?->year"
                :year-note="$consolidatedBalance === null ? null : RegionalGroupDashboard::sumNote($consolidatedBalance->sectionsCount, $consolidatedBalance->totalSections)"
                :secondary="[
                    ['label' => 'Totale attivo', 'value' => $consolidatedBalance?->totalAssets],
                ]"
            />
        </div>

        <div class="ms-regional-group-dashboard__simple-grid">
            <x-cai.metric-badge
                class="ms-regional-group-dashboard__sections"
                label="Sezioni"
                :value="$data?->sectionsCount"
                :href="CustomerRegionalSectionsDashboard::getUrl()"
                link-label="Sezioni del GR"
            />
            <x-cai.metric-badge
                label="Soci"
                :value="$data?->members"
                :note="$data?->membersSectionsCount === null ? null : RegionalGroupDashboard::sumNote($data->membersSectionsCount)"
                :href="RegionalGroupMembers::getUrl()"
                link-label="Soci"
            />
            <x-cai.metric-badge label="Rifugi" :value="$data?->huts" :href="RegionalGroupHuts::getUrl()" link-label="Rifugi del GR" />
            <x-cai.metric-badge
                label="Sentieri"
                :value="$data?->trailsKm"
                unit="km"
                :decimals="1"
                :href="RegionalGroupTrails::getUrl()"
                link-label="Sentieri del GR"
            />
            <x-cai.metric-badge label="Tappe SICAI" :value="$data?->sicaiStages" :href="RegionalGroupSicaiStages::getUrl()" link-label="Tappe SICAI" />
        </div>
    </div>
</x-filament-panels::page>
