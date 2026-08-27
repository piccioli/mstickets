<?php

declare(strict_types=1);

namespace App\Filament\Resources\ActivityReports\Tables;

use App\Domain\Reporting\Models\ActivityReport;
use App\Filament\Resources\Organizations\RelationManagers\ActivityReportsRelationManager;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Sola lettura (§6.5.4 del PRD, US-410): nessuna CreateAction/EditAction/
 * DeleteAction, solo l'elenco e il download del PDF già generato — stesso
 * pattern di {@see ActivityReportsRelationManager}
 * ma con owner e periodo leggibili (`ownerName()`/`periodLabel()`, US-408)
 * dato che qui una riga può appartenere a un owner utente o organizzazione,
 * mentre lì l'owner è sempre l'organizzazione della pagina.
 */
class ActivityReportsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('owner')
                    ->label('Titolare')
                    ->state(fn (ActivityReport $record): string => $record->ownerName()),
                TextColumn::make('period')
                    ->label('Periodo')
                    ->state(fn (ActivityReport $record): string => $record->periodLabel()),
                TextColumn::make('pdf_generated_at')
                    ->label('PDF generato il')
                    ->dateTime()
                    ->placeholder('Non ancora generato'),
            ])
            ->defaultSort('year', 'desc')
            ->headerActions([])
            ->recordActions([
                Action::make('downloadPdf')
                    ->label('Scarica PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->visible(fn (ActivityReport $record): bool => $record->pdf_path !== null)
                    ->url(fn (ActivityReport $record): string => route('activity-reports.pdf-download', $record))
                    ->openUrlInNewTab(),
            ])
            ->toolbarActions([]);
    }
}
