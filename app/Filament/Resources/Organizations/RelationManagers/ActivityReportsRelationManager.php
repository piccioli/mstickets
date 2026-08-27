<?php

declare(strict_types=1);

namespace App\Filament\Resources\Organizations\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Sola lettura: la generazione degli ActivityReport è un servizio dedicato
 * (US-408/US-410), non un'azione manuale da qui — nessuna CreateAction/DeleteAction.
 */
class ActivityReportsRelationManager extends RelationManager
{
    protected static string $relationship = 'activityReports';

    protected static ?string $title = 'Report attività';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('period_type')->label('Tipo periodo'),
                TextColumn::make('year')->label('Anno'),
                TextColumn::make('month')->label('Mese')->placeholder('—'),
                TextColumn::make('pdf_generated_at')->label('PDF generato il')->dateTime()->placeholder('Non ancora generato'),
            ])
            ->defaultSort('year', 'desc')
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
