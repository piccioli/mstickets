<?php

declare(strict_types=1);

namespace App\Filament\Resources\EmailMessages\Schemas;

use App\Domain\Mail\Models\EmailMessage;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Number;

class EmailMessageInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Intestazione')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('direction')->label('Direzione')->badge(),
                        TextEntry::make('status')->label('Stato')->badge(),
                        TextEntry::make('from_name')->label('Nome mittente')->placeholder('—'),
                        TextEntry::make('from_email')->label('Email mittente'),
                        TextEntry::make('to')
                            ->label('A')
                            ->state(fn (EmailMessage $record): string => implode(', ', $record->to ?? []))
                            ->placeholder('—'),
                        TextEntry::make('cc')
                            ->label('Cc')
                            ->state(fn (EmailMessage $record): string => implode(', ', $record->cc ?? []))
                            ->placeholder('—'),
                        TextEntry::make('bcc')
                            ->label('Ccn')
                            ->state(fn (EmailMessage $record): string => implode(', ', $record->bcc ?? []))
                            ->placeholder('—'),
                        TextEntry::make('subject')->label('Oggetto')->placeholder('—')->columnSpanFull(),
                        TextEntry::make('message_id')->label('Message-ID')->placeholder('—'),
                        TextEntry::make('in_reply_to')->label('In-Reply-To')->placeholder('—'),
                    ]),

                Section::make('Corpo')
                    ->schema([
                        TextEntry::make('body_text')
                            ->label('Testo semplice')
                            ->placeholder('—')
                            ->columnSpanFull(),
                        TextEntry::make('body_html')
                            ->label('HTML (sanitizzato)')
                            ->html()
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ]),

                Section::make('Allegati')
                    ->schema([
                        RepeatableEntry::make('attachments')
                            ->hiddenLabel()
                            ->schema([
                                TextEntry::make('filename')->label('File'),
                                TextEntry::make('mime_type')->label('Tipo')->placeholder('—'),
                                TextEntry::make('size_bytes')
                                    ->label('Dimensione')
                                    ->formatStateUsing(fn (?int $state): string => $state === null ? '—' : Number::fileSize($state)),
                                TextEntry::make('status')->label('Stato')->badge(),
                            ])
                            ->columns(4)
                            ->placeholder('Nessun allegato'),
                    ]),

                Section::make('Ticket e thread collegati')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('ticket.title')->label('Ticket')->placeholder('Nessuno'),
                        TextEntry::make('thread.subject_normalized')->label('Thread')->placeholder('Nessuno'),
                    ]),

                Section::make('Diagnostica')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('attempts')->label('Tentativi'),
                        TextEntry::make('failure_reason')->label('Ultimo errore')->placeholder('—')->columnSpanFull(),
                    ]),
            ]);
    }
}
