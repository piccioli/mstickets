<?php

declare(strict_types=1);

namespace App\Filament\Resources\CustomerFundraisingProjects\Tables;

use App\Filament\Resources\CustomerFundraisingProjects\CustomerFundraisingProjectResource;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Sola lettura (§6.6.4, US-508): l'insieme dei progetti è già ristretto a
 * "coinvolto come capofila o partner" da
 * {@see CustomerFundraisingProjectResource::getEloquentQuery()},
 * qui restano solo le colonne informative, mai responsabile/creatore
 * (concetti interni allo staff, non previsti da §6.6.4).
 */
class CustomerFundraisingProjectsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->label('Titolo')->searchable()->limit(60),
                TextColumn::make('status')->label('Stato')->badge(),
                TextColumn::make('fundraisingOpportunity.name')->label('Opportunità')->limit(40),
                TextColumn::make('leadUser.name')->label('Capofila')->placeholder('—')->toggleable(),
                TextColumn::make('requested_amount')->label('Importo richiesto')->money('EUR')->sortable(),
                TextColumn::make('approved_amount')->label('Importo approvato')->money('EUR')->sortable(),
                TextColumn::make('submitted_at')->label('Data presentazione')->date()->sortable()->toggleable(),
                TextColumn::make('decided_at')->label('Data decisione')->date()->sortable()->toggleable(),
            ])
            ->headerActions([])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([]);
    }
}
