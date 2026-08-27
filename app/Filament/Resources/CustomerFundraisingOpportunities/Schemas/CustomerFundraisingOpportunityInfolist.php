<?php

declare(strict_types=1);

namespace App\Filament\Resources\CustomerFundraisingOpportunities\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * Sola lettura (§6.6.4, US-508): solo i dati informativi dell'opportunità.
 * Deliberatamente esclusi `responsibleUser`/`creator`/dati di valutazione
 * (US-503/US-504): sono informazioni di lavoro interne allo staff, mai
 * previste da §6.6.4 per la vista cliente.
 */
class CustomerFundraisingOpportunityInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Opportunità')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('name')->label('Nome')->columnSpanFull(),
                        TextEntry::make('official_url')->label('URL ufficiale')->url(fn (?string $state): ?string => $state)->openUrlInNewTab()->placeholder('—'),
                        TextEntry::make('deadline')->label('Scadenza')->date(),
                        TextEntry::make('program_name')->label('Nome programma')->placeholder('—'),
                        TextEntry::make('sponsor')->label('Ente finanziatore')->placeholder('—'),
                        TextEntry::make('endowment_fund')->label('Dotazione del fondo')->money('EUR')->placeholder('—'),
                        TextEntry::make('max_contribution')->label('Contributo massimo')->money('EUR')->placeholder('—'),
                        TextEntry::make('cofinancing_quota')->label('Quota di cofinanziamento')->suffix('%')->placeholder('—'),
                        TextEntry::make('territorial_scope')->label('Ambito territoriale')->badge(),
                        TextEntry::make('beneficiary_requirements')->label('Requisiti beneficiario')->placeholder('—')->columnSpanFull(),
                        TextEntry::make('lead_requirements')->label('Requisiti capofila')->placeholder('—')->columnSpanFull(),
                    ]),
            ]);
    }
}
