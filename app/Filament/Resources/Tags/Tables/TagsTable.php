<?php

declare(strict_types=1);

namespace App\Filament\Resources\Tags\Tables;

use App\Domain\Tags\Models\Tag;
use App\Domain\Ticketing\Enums\TicketStatus;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Table;

class TagsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('estimated_hours')
                    ->label('Ore stimate')
                    ->numeric(decimalPlaces: 2)
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('worked_hours')
                    ->label('Ore lavorate')
                    ->getStateUsing(fn (Tag $record): float => round($record->workedMinutes() / 60, 2)),
                ViewColumn::make('sal')
                    ->label('SAL')
                    ->view('filament.tables.columns.tag-sal-bar')
                    ->getStateUsing(fn (Tag $record): ?float => $record->sal()),
                TextColumn::make('tickets_open_count')
                    ->label('Ticket aperti')
                    ->getStateUsing(fn (Tag $record): int => $record->tickets()
                        ->whereNotIn('status', [TicketStatus::Released, TicketStatus::Done])
                        ->count()),
                TextColumn::make('tickets_closed_count')
                    ->label('Ticket chiusi')
                    ->getStateUsing(fn (Tag $record): int => $record->tickets()
                        ->whereIn('status', [TicketStatus::Released, TicketStatus::Done])
                        ->count()),
                TextColumn::make('is_closed')
                    ->label('Stato')
                    ->badge()
                    ->getStateUsing(fn (Tag $record): string => $record->isClosed() ? 'Chiusa' : 'Aperta')
                    ->color(fn (Tag $record): string => $record->isClosed() ? 'success' : 'warning'),
            ])
            ->defaultSort('name');
    }
}
