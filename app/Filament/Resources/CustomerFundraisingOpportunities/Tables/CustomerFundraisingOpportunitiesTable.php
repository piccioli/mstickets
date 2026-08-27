<?php

declare(strict_types=1);

namespace App\Filament\Resources\CustomerFundraisingOpportunities\Tables;

use App\Filament\Resources\FundraisingOpportunities\FundraisingOpportunityResource;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Sola lettura (§6.6.4, US-508): nessuna colonna/filtro interno (responsabile,
 * creatore, valutazione) — solo i dati informativi dell'opportunità, mai le
 * differenze attive/scadute che restano un concetto interno allo staff
 * (l'archivio è un concetto di {@see FundraisingOpportunityResource}).
 */
class CustomerFundraisingOpportunitiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nome')->searchable()->limit(60),
                TextColumn::make('deadline')->label('Scadenza')->date()->sortable(),
                TextColumn::make('territorial_scope')->label('Ambito territoriale')->badge(),
                TextColumn::make('sponsor')->label('Ente finanziatore')->searchable()->toggleable(),
            ])
            ->headerActions([])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([]);
    }
}
