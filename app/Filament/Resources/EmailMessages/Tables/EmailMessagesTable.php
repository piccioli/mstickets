<?php

declare(strict_types=1);

namespace App\Filament\Resources\EmailMessages\Tables;

use App\Domain\Mail\Enums\EmailDirection;
use App\Domain\Mail\Enums\EmailStatus;
use App\Domain\Mail\Models\EmailMessage;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EmailMessagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('direction')->label('Direzione')->badge(),
                TextColumn::make('status')->label('Stato')->badge(),
                TextColumn::make('from_email')->label('Mittente')->searchable(),
                TextColumn::make('to')
                    ->label('Destinatari')
                    ->state(fn (EmailMessage $record): string => implode(', ', $record->to ?? []))
                    ->limit(50)
                    ->placeholder('—'),
                TextColumn::make('subject')->label('Oggetto')->searchable()->limit(50)->placeholder('—'),
                TextColumn::make('ticket.title')->label('Ticket')->placeholder('—'),
                TextColumn::make('attempts')->label('Tentativi')->sortable(),
                TextColumn::make('created_at')->label('Creata il')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('direction')
                    ->label('Direzione')
                    ->options(collect(EmailDirection::cases())->mapWithKeys(
                        fn (EmailDirection $direction): array => [$direction->value => $direction->getLabel()],
                    )),
                SelectFilter::make('status')
                    ->label('Stato')
                    ->multiple()
                    ->options(collect(EmailStatus::cases())->mapWithKeys(
                        fn (EmailStatus $status): array => [$status->value => $status->getLabel()],
                    )),
                Filter::make('from_email')
                    ->label('Mittente')
                    ->schema([
                        TextInput::make('value')->label('Contiene'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (blank($data['value'] ?? null)) {
                            return $query;
                        }

                        return $query->where('from_email', 'like', '%'.$data['value'].'%');
                    }),
                Filter::make('recipient')
                    ->label('Destinatario')
                    ->schema([
                        TextInput::make('email')->label('Email')->email(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $email = $data['email'] ?? null;

                        if (blank($email)) {
                            return $query;
                        }

                        return $query->where(
                            fn (Builder $query): Builder => $query
                                ->whereJsonContains('to', $email)
                                ->orWhereJsonContains('cc', $email)
                                ->orWhereJsonContains('bcc', $email),
                        );
                    }),
                SelectFilter::make('ticket_id')
                    ->label('Ticket collegato')
                    ->relationship('ticket', 'title')
                    ->searchable()
                    ->preload(),
                Filter::make('period')
                    ->label('Periodo')
                    ->schema([
                        DatePicker::make('from')->label('Dal'),
                        DatePicker::make('until')->label('Al'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                filled($data['from'] ?? null),
                                fn (Builder $query): Builder => $query->whereDate('created_at', '>=', $data['from']),
                            )
                            ->when(
                                filled($data['until'] ?? null),
                                fn (Builder $query): Builder => $query->whereDate('created_at', '<=', $data['until']),
                            );
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
