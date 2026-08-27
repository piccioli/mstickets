<?php

declare(strict_types=1);

namespace App\Filament\Resources\FundraisingOpportunities\Schemas;

use App\Domain\Fundraising\Enums\TerritorialScope;
use App\Domain\Fundraising\Models\FundraisingOpportunity;
use App\Domain\Fundraising\Policies\FundraisingOpportunityPolicy;
use App\Domain\Identity\Enums\Permission;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

/**
 * Form condiviso da Create/EditFundraisingOpportunity (US-502, §6.6.1).
 * `created_by` non è un campo compilabile: viene mostrato in sola lettura
 * (Placeholder, mai dehydrated) e valorizzato da `CreateFundraisingOpportunity`
 * con l'utente autenticato, mai più modificabile in seguito (AC esplicito).
 * Il tab "Valutazione" (US-504) esiste solo per un record già persistito e per
 * chi ha il permesso `fundraising.evaluate` (distinto da `fundraising.update`,
 * {@see FundraisingOpportunityPolicy::evaluate()}):
 * Tabs/Tab sono componenti di solo layout, non alterano lo state path dei campi,
 * quindi 'name', 'deadline', ecc. restano raggiungibili con lo stesso path di prima.
 */
class FundraisingOpportunityForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('opportunity')
                    ->tabs([
                        Tab::make('Dati opportunità')
                            ->schema([self::detailsSection()]),
                        Tab::make('Valutazione')
                            ->visible(fn (?FundraisingOpportunity $record): bool => $record !== null
                                && (bool) Auth::user()?->can(Permission::FundraisingEvaluate))
                            ->schema(FundraisingEvaluationGridForm::components()),
                    ]),
            ]);
    }

    private static function detailsSection(): Section
    {
        return Section::make('Opportunità')
            ->columns(2)
            ->schema([
                TextInput::make('name')
                    ->label('Nome')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                TextInput::make('official_url')
                    ->label('URL ufficiale')
                    ->url()
                    ->maxLength(255),
                DatePicker::make('deadline')
                    ->label('Scadenza')
                    ->required(),
                TextInput::make('program_name')
                    ->label('Nome programma')
                    ->maxLength(255),
                TextInput::make('sponsor')
                    ->label('Ente finanziatore')
                    ->maxLength(255),
                TextInput::make('endowment_fund')
                    ->label('Dotazione del fondo')
                    ->numeric()
                    ->minValue(0)
                    ->step(0.01)
                    ->prefix('€'),
                TextInput::make('max_contribution')
                    ->label('Contributo massimo')
                    ->numeric()
                    ->minValue(0)
                    ->step(0.01)
                    ->prefix('€'),
                TextInput::make('cofinancing_quota')
                    ->label('Quota di cofinanziamento')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(100)
                    ->step(0.01)
                    ->suffix('%'),
                Select::make('territorial_scope')
                    ->label('Ambito territoriale')
                    ->options(collect(TerritorialScope::cases())->mapWithKeys(
                        fn (TerritorialScope $scope): array => [$scope->value => $scope->getLabel()],
                    ))
                    ->required(),
                Select::make('responsible_user_id')
                    ->label('Responsabile')
                    ->relationship('responsibleUser', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Placeholder::make('creator')
                    ->label('Creatore')
                    ->content(fn (?FundraisingOpportunity $record): string => $record?->creator->name ?? Auth::user()->name ?? '—'),
                Textarea::make('beneficiary_requirements')
                    ->label('Requisiti beneficiario')
                    ->rows(3)
                    ->columnSpanFull(),
                Textarea::make('lead_requirements')
                    ->label('Requisiti capofila')
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }
}
