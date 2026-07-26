<?php

declare(strict_types=1);

namespace App\Filament\Resources\Tickets\Tables;

use App\Domain\Identity\Models\Organization;
use App\Domain\Ticketing\Enums\TicketPriority;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Enums\TicketType;
use App\Domain\Ticketing\Models\Ticket;
use App\Filament\Resources\Tickets\Support\TicketFieldAccess;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

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
                    ->multiple()
                    ->options(collect(TicketStatus::cases())->mapWithKeys(
                        fn (TicketStatus $status): array => [$status->value => $status->getLabel()],
                    )),
                SelectFilter::make('type')
                    ->label('Tipo')
                    ->options(collect(TicketType::cases())->mapWithKeys(
                        fn (TicketType $type): array => [$type->value => $type->getLabel()],
                    ))
                    ->hidden(fn (): bool => ! TicketFieldAccess::canManageInternalFields()),
                SelectFilter::make('priority')
                    ->label('Priorità')
                    ->options(collect(TicketPriority::cases())->mapWithKeys(
                        fn (TicketPriority $priority): array => [$priority->value => $priority->getLabel()],
                    ))
                    ->hidden(fn (): bool => ! TicketFieldAccess::canManageInternalFields()),
                SelectFilter::make('assignee_id')
                    ->label('Assegnatario')
                    ->relationship('assignee', 'name')
                    ->searchable()
                    ->preload()
                    ->hidden(fn (): bool => ! TicketFieldAccess::canManageInternalFields()),
                SelectFilter::make('tester_id')
                    ->label('Tester')
                    ->relationship('tester', 'name')
                    ->searchable()
                    ->preload()
                    ->hidden(fn (): bool => ! TicketFieldAccess::canManageInternalFields()),
                SelectFilter::make('requester_id')
                    ->label('Richiedente')
                    ->relationship('requester', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('organization_id')
                    ->label('Organizzazione del richiedente')
                    ->options(fn (): array => Organization::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->query(function (Builder $query, array $data): Builder {
                        if (blank($data['value'] ?? null)) {
                            return $query;
                        }

                        return $query->whereHas(
                            'requester',
                            fn (Builder $query): Builder => $query->whereHas(
                                'organizations',
                                fn (Builder $query): Builder => $query->whereKey($data['value']),
                            ),
                        );
                    })
                    ->hidden(fn (): bool => ! TicketFieldAccess::canManageInternalFields()),
                SelectFilter::make('tags')
                    ->label('Tag')
                    ->relationship('tags', 'name')
                    ->multiple()
                    ->searchable()
                    ->preload(),
                Filter::make('without_tags')
                    ->label('Senza tag')
                    ->query(fn (Builder $query): Builder => $query->whereDoesntHave('tags')),
                Filter::make('multiple_tags')
                    ->label('Con più di un tag')
                    ->query(fn (Builder $query): Builder => $query->has('tags', '>', 1)),
                Filter::make('tag_name_pattern')
                    ->label('Tag per trimestre')
                    ->schema([
                        TextInput::make('pattern')->label('Pattern nel nome del tag'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (blank($data['pattern'] ?? null)) {
                            return $query;
                        }

                        return $query->whereHas(
                            'tags',
                            fn (Builder $query): Builder => $query->where('name', 'like', '%'.$data['pattern'].'%'),
                        );
                    }),
                Filter::make('period')
                    ->label('Periodo')
                    ->schema([
                        Select::make('field')
                            ->label('Campo')
                            ->options([
                                'created_at' => 'Data di creazione',
                                'done_at' => 'Data di completamento',
                            ])
                            ->default('created_at')
                            ->native(false),
                        DatePicker::make('from')->label('Dal'),
                        DatePicker::make('until')->label('Al'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $field = in_array($data['field'] ?? null, ['created_at', 'done_at'], true)
                            ? $data['field']
                            : 'created_at';

                        return $query
                            ->when(
                                filled($data['from'] ?? null),
                                fn (Builder $query): Builder => $query->whereDate($field, '>=', $data['from']),
                            )
                            ->when(
                                filled($data['until'] ?? null),
                                fn (Builder $query): Builder => $query->whereDate($field, '<=', $data['until']),
                            );
                    }),
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
