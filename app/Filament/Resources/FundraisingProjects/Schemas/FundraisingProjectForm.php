<?php

declare(strict_types=1);

namespace App\Filament\Resources\FundraisingProjects\Schemas;

use App\Domain\Fundraising\Models\FundraisingProject;
use App\Domain\Fundraising\StateMachine\FundraisingProjectStateMachine;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

/**
 * Form condiviso da Create/EditFundraisingProject (US-507, §6.6.3). `status` non è
 * un campo di questo form (mai una transizione libera via Select, stessa disciplina
 * di {@see FundraisingProjectStateMachine},
 * US-506): un progetto nuovo parte sempre da `draft` (default di colonna), mostrato
 * qui in sola lettura come badge, stesso idioma di `TicketForm::statusBadge()`.
 * `created_by` non è compilabile, come in `FundraisingOpportunityForm` (US-502):
 * valorizzato da `CreateFundraisingProject` con l'utente autenticato.
 * La gestione dei partner (aggiungi/rimuovi) vive nel `PartnersRelationManager`
 * sulla pagina Edit, mai in questo form.
 */
class FundraisingProjectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Progetto')
                    ->columns(2)
                    ->schema([
                        TextInput::make('title')
                            ->label('Titolo')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Select::make('fundraising_opportunity_id')
                            ->label('Opportunità')
                            ->relationship('fundraisingOpportunity', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Placeholder::make('status')
                            ->label('Stato')
                            ->content(fn (?FundraisingProject $record): string => $record?->status->getLabel() ?? 'Bozza'),
                        Select::make('lead_user_id')
                            ->label('Capofila')
                            ->relationship('leadUser', 'name')
                            ->searchable()
                            ->preload(),
                        Select::make('responsible_user_id')
                            ->label('Responsabile')
                            ->relationship('responsibleUser', 'name')
                            ->searchable()
                            ->preload(),
                        TextInput::make('requested_amount')
                            ->label('Importo richiesto')
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->prefix('€'),
                        TextInput::make('approved_amount')
                            ->label('Importo approvato')
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->prefix('€'),
                        Placeholder::make('creator')
                            ->label('Creatore')
                            ->content(fn (?FundraisingProject $record): string => $record?->creator->name ?? Auth::user()->name ?? '—'),
                        Textarea::make('description')
                            ->label('Descrizione')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
