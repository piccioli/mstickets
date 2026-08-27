<?php

declare(strict_types=1);

namespace App\Filament\Resources\CustomerFundraisingProjects\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * Sola lettura (§6.6.4, US-508): solo i dati informativi del progetto e i
 * partner coinvolti. Deliberatamente esclusi `responsibleUser`/`creator`:
 * sono informazioni di lavoro interne allo staff, mai previste da §6.6.4.
 */
class CustomerFundraisingProjectInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Progetto')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('title')->label('Titolo')->columnSpanFull(),
                        TextEntry::make('fundraisingOpportunity.name')->label('Opportunità')->columnSpanFull(),
                        TextEntry::make('status')->label('Stato')->badge(),
                        TextEntry::make('leadUser.name')->label('Capofila')->placeholder('—'),
                        TextEntry::make('requested_amount')->label('Importo richiesto')->money('EUR')->placeholder('—'),
                        TextEntry::make('approved_amount')->label('Importo approvato')->money('EUR')->placeholder('—'),
                        TextEntry::make('submitted_at')->label('Data presentazione')->date()->placeholder('—'),
                        TextEntry::make('decided_at')->label('Data decisione')->date()->placeholder('—'),
                        TextEntry::make('description')->label('Descrizione')->placeholder('—')->columnSpanFull(),
                    ]),

                Section::make('Partner')
                    ->schema([
                        TextEntry::make('partners.name')
                            ->hiddenLabel()
                            ->badge()
                            ->placeholder('Nessun partner'),
                    ]),
            ]);
    }
}
