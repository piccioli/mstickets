<?php

declare(strict_types=1);

namespace App\Filament\Resources\Tickets\Tables;

use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Models\Ticket;
use App\Filament\Resources\Tickets\Support\TicketFieldAccess;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TicketsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('#')->sortable(),
                TextColumn::make('title')->label('Titolo')->searchable()->limit(50),
                TextColumn::make('status')->label('Stato')->badge()->sortable(),
                TextColumn::make('type')->label('Tipo')->badge()
                    ->visible(fn (): bool => TicketFieldAccess::canManageInternalFields()),
                TextColumn::make('priority')->label('Priorità')->badge()
                    ->visible(fn (): bool => TicketFieldAccess::canManageInternalFields()),
                TextColumn::make('requester.name')->label('Richiedente')->searchable()->placeholder('—'),
                TextColumn::make('assignee.name')->label('Assegnatario')->searchable()->placeholder('Nessuno')
                    ->visible(fn (): bool => TicketFieldAccess::canManageInternalFields()),
                TextColumn::make('created_at')->label('Creato il')->dateTime()->sortable(),
                TextColumn::make('status_changed_at')->label('Giorni in stato')->sortable()
                    ->getStateUsing(fn (Ticket $record): int => (int) $record->status_changed_at->diffInDays(now())),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Stato')
                    ->options(collect(TicketStatus::cases())->mapWithKeys(
                        fn (TicketStatus $status): array => [$status->value => $status->getLabel()],
                    )),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
