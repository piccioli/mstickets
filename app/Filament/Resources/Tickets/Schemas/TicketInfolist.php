<?php

declare(strict_types=1);

namespace App\Filament\Resources\Tickets\Schemas;

use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Models\TicketLog;
use App\Domain\Ticketing\Models\TicketMessage;
use App\Filament\Resources\Tickets\Support\TicketFieldAccess;
use App\Filament\Resources\Tickets\Support\TicketLogFormatter;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

/**
 * Vista di dettaglio del ticket (US-110), sola lettura: nessun modo di alterare
 * `status`/campi interni da qui, i cambi di stato passano dalle action di
 * transizione (header actions di `ViewTicket`). Implementata come Infolist
 * (invece di un RelationManager, non ancora una convenzione in questo repo):
 * conversazione e storico usano `RepeatableEntry::state()` con una query
 * esplicita (filtrata da `TicketMessage::scopeVisibleTo()`) invece della relazione
 * grezza, per rispettare la stessa regola di visibilità applicata altrove.
 */
class TicketInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Ticket')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('title')->label('Titolo')->columnSpanFull(),
                        TextEntry::make('status')->label('Stato')->badge(),
                        TextEntry::make('type')->label('Tipo')->badge()
                            ->visible(fn (): bool => TicketFieldAccess::canManageInternalFields()),
                        TextEntry::make('priority')->label('Priorità')->badge()
                            ->visible(fn (): bool => TicketFieldAccess::canManageInternalFields()),
                        TextEntry::make('requester.name')->label('Richiedente')->placeholder('—'),
                        TextEntry::make('assignee.name')->label('Assegnatario')->placeholder('Nessuno')
                            ->visible(fn (): bool => TicketFieldAccess::canManageInternalFields()),
                        TextEntry::make('tester.name')->label('Tester')->placeholder('Nessuno')
                            ->visible(fn (): bool => TicketFieldAccess::canManageInternalFields()),
                        TextEntry::make('description')
                            ->label('Descrizione interna')
                            ->columnSpanFull()
                            ->placeholder('—')
                            ->visible(fn (): bool => TicketFieldAccess::canManageInternalFields()),
                    ]),

                Section::make('Riepilogo')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('worked_minutes')
                            ->label('Ore lavorate')
                            ->formatStateUsing(fn (int $state): string => TicketForm::formatWorkedMinutes($state))
                            ->visible(fn (): bool => TicketFieldAccess::canManageInternalFields()),
                        TextEntry::make('estimated_hours')->label('Ore stimate')->placeholder('—')
                            ->visible(fn (): bool => TicketFieldAccess::canManageInternalFields()),
                        TextEntry::make('created_at')->label('Creato il')->dateTime(),
                        TextEntry::make('updated_at')->label('Aggiornato il')->dateTime(),
                        TextEntry::make('status_changed_at')->label('Ultimo cambio di stato')->dateTime()->placeholder('—'),
                        TextEntry::make('released_at')->label('Rilasciato il')->dateTime()->placeholder('—'),
                        TextEntry::make('done_at')->label('Completato il')->dateTime()->placeholder('—'),
                    ]),

                Section::make('Link ambienti')
                    ->columns(2)
                    ->visible(fn (): bool => TicketFieldAccess::canManageInternalFields())
                    ->schema([
                        TextEntry::make('staging_url')
                            ->label('URL staging')
                            ->url(fn (?Ticket $record): ?string => $record?->staging_url)
                            ->openUrlInNewTab()
                            ->placeholder('—'),
                        TextEntry::make('production_url')
                            ->label('URL produzione')
                            ->url(fn (?Ticket $record): ?string => $record?->production_url)
                            ->openUrlInNewTab()
                            ->placeholder('—'),
                    ]),

                Section::make('Gerarchia')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('parent.title')->label('Ticket padre')->placeholder('Nessuno'),
                        TextEntry::make('children.title')
                            ->label('Ticket figli')
                            ->listWithLineBreaks()
                            ->bulleted()
                            ->placeholder('Nessuno'),
                    ]),

                Section::make('Partecipanti')
                    ->schema([
                        TextEntry::make('participants.name')
                            ->hiddenLabel()
                            ->badge()
                            ->placeholder('Nessun partecipante'),
                    ]),

                Section::make('Conversazione')
                    ->schema([
                        RepeatableEntry::make('messages')
                            ->hiddenLabel()
                            ->state(function (Ticket $record) {
                                $user = Auth::user();

                                if (! $user instanceof User) {
                                    return collect();
                                }

                                return TicketMessage::query()
                                    ->where('ticket_id', $record->id)
                                    ->visibleTo($user)
                                    ->with('author')
                                    ->orderBy('posted_at')
                                    ->get();
                            })
                            ->schema([
                                TextEntry::make('author.name')->label('Autore')->placeholder('—'),
                                TextEntry::make('posted_at')->label('Data')->dateTime(),
                                TextEntry::make('is_legacy_import')
                                    ->label('')
                                    ->badge()
                                    ->color('gray')
                                    ->formatStateUsing(fn (): string => 'Importato da v1')
                                    ->visible(fn (?bool $state): bool => (bool) $state),
                                TextEntry::make('body_html')->hiddenLabel()->html()->columnSpanFull(),
                                TextEntry::make('attachments')
                                    ->hiddenLabel()
                                    ->columnSpanFull()
                                    ->state(fn (TicketMessage $record): string => self::attachmentsHtml($record))
                                    ->html(),
                            ])
                            ->columns(3)
                            ->placeholder('Nessun messaggio'),
                    ]),

                Section::make('Storico')
                    ->visible(fn (): bool => (bool) Auth::user()?->can(Permission::TicketLogView))
                    ->schema([
                        RepeatableEntry::make('logs')
                            ->hiddenLabel()
                            ->state(fn (Ticket $record) => TicketLog::query()
                                ->where('ticket_id', $record->id)
                                ->with('user')
                                ->orderByDesc('occurred_at')
                                ->get())
                            ->schema([
                                TextEntry::make('event')->label('Evento')->badge(),
                                TextEntry::make('diff')
                                    ->hiddenLabel()
                                    ->state(fn (TicketLog $record): string => TicketLogFormatter::describe($record)),
                                TextEntry::make('user.name')->label('Utente')->placeholder('Sistema'),
                                TextEntry::make('occurred_at')->label('Quando')->dateTime(),
                            ])
                            ->columns(4)
                            ->placeholder('Nessun evento'),
                    ]),
            ]);
    }

    private static function attachmentsHtml(TicketMessage $message): string
    {
        $media = $message->getMedia('attachments');

        if ($media->isEmpty()) {
            return '';
        }

        return $media
            ->map(fn ($item): string => sprintf(
                '<a class="fi-link" href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
                e(route('ticket-attachments.download', $item)),
                e($item->file_name),
            ))
            ->implode('<br>');
    }
}
