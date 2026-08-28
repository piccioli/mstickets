<?php

declare(strict_types=1);

namespace App\Filament\Resources\CaiSections\Schemas;

use App\Domain\CaiDirectory\Models\CaiDocument;
use App\Domain\CaiDirectory\Models\CaiRuntsRegistration;
use App\Domain\CaiDirectory\Models\CaiSection;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

/**
 * Dettaglio di sola lettura di una sezione CAI (US-804): nessun modo di modificare i dati
 * da qui, l'anagrafica viene aggiornata solo da una nuova esecuzione dell'importer datapack
 * RUNTS-CAI (US-802). Una sezione ha 0..n `runtsRegistrations` (nullable `cai_section_id` su
 * `cai_runts_registrations`, US-801): i tab "Dati RUNTS"/"Bilanci"/"Allegati" usano quindi
 * `RepeatableEntry::state()` invece della relazione grezza, per restare corretti anche
 * quando la sezione non ha alcuna registrazione RUNTS (nessun crash, solo liste vuote con
 * il loro `->placeholder()`), stesso idioma di `TicketInfolist::configure()` per le
 * relazioni filtrate/derivate.
 */
class CaiSectionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('cai_section')
                    ->tabs([
                        Tab::make('Dati CAI')
                            ->schema([self::caiDataSection()]),
                        Tab::make('Dati RUNTS')
                            ->schema([self::runtsSection()]),
                        Tab::make('Bilanci')
                            ->schema([self::financialStatementsSection()]),
                        Tab::make('Allegati')
                            ->schema([self::documentsSection()]),
                        Tab::make('Sottosezioni')
                            ->schema([self::subsectionsSection()]),
                    ]),
            ]);
    }

    private static function caiDataSection(): Section
    {
        return Section::make('Sezione CAI')
            ->columns(2)
            ->schema([
                TextEntry::make('name')->label('Denominazione')->columnSpanFull(),
                TextEntry::make('codice_cai')->label('Codice CAI'),
                TextEntry::make('region')->label('Regione'),
                TextEntry::make('tax_code')->label('Codice fiscale')->placeholder('—'),
                TextEntry::make('vat_number')->label('Partita IVA')->placeholder('—'),
                TextEntry::make('email')->label('Email')->placeholder('—'),
                TextEntry::make('pec')->label('PEC')->placeholder('—'),
                TextEntry::make('phone_office')->label('Telefono sede')->placeholder('—'),
                TextEntry::make('phone')->label('Telefono')->placeholder('—'),
                TextEntry::make('fax')->label('Fax')->placeholder('—'),
                TextEntry::make('website')->label('Sito web')->url(fn (?CaiSection $record): ?string => $record?->website)->openUrlInNewTab()->placeholder('—'),
                TextEntry::make('address')->label('Indirizzo')->placeholder('—'),
                TextEntry::make('postal_address')->label('Recapito postale')->placeholder('—'),
                TextEntry::make('founded_year')->label('Anno di fondazione')->placeholder('—'),
                TextEntry::make('members_count')->label('Numero soci')->placeholder('—'),
                TextEntry::make('office_hours')->label('Orari di apertura')->columnSpanFull()->placeholder('—'),
                TextEntry::make('notices')->label('Avvisi')->columnSpanFull()->placeholder('—'),
                TextEntry::make('user.name')->label('Utente collegato')->placeholder('Nessuno'),
            ]);
    }

    private static function runtsSection(): Section
    {
        return Section::make('Registrazioni RUNTS')
            ->schema([
                RepeatableEntry::make('runtsRegistrations')
                    ->hiddenLabel()
                    ->state(fn (CaiSection $record) => $record->runtsRegistrations)
                    ->schema([
                        TextEntry::make('name')->label('Denominazione RUNTS')->placeholder('—'),
                        TextEntry::make('legal_form')->label('Forma giuridica')->placeholder('—'),
                        TextEntry::make('legal_nature')->label('Natura giuridica')->placeholder('—'),
                        TextEntry::make('registration_date')->label('Data iscrizione')->date()->placeholder('—'),
                        TextEntry::make('register_section')->label('Sezione registro')->placeholder('—'),
                        TextEntry::make('pec')->label('PEC')->placeholder('—'),
                        TextEntry::make('legal_representative')->label('Rappresentante legale')->placeholder('—'),
                        TextEntry::make('municipality')->label('Comune')->placeholder('—'),
                        TextEntry::make('province')->label('Provincia')->placeholder('—'),
                        TextEntry::make('address')->label('Indirizzo')->placeholder('—'),
                        TextEntry::make('official_page_url')
                            ->label('Scheda ufficiale RUNTS')
                            ->url(fn (CaiRuntsRegistration $record): ?string => $record->official_page_url)
                            ->openUrlInNewTab()
                            ->placeholder('—'),
                    ])
                    ->columns(3)
                    ->placeholder('Nessuna registrazione RUNTS collegata'),
            ]);
    }

    private static function financialStatementsSection(): Section
    {
        return Section::make('Bilanci per anno')
            ->schema([
                RepeatableEntry::make('financial_statements')
                    ->hiddenLabel()
                    ->state(fn (CaiSection $record) => $record->runtsRegistrations->flatMap->financialStatements)
                    ->schema([
                        TextEntry::make('year')->label('Anno'),
                        TextEntry::make('total_revenues')->label('Totale ricavi')->money('EUR')->placeholder('—'),
                        TextEntry::make('total_expenses')->label('Totale costi')->money('EUR')->placeholder('—'),
                        TextEntry::make('pre_tax_result')->label('Risultato ante imposte')->money('EUR')->placeholder('—'),
                        TextEntry::make('taxes')->label('Imposte')->money('EUR')->placeholder('—'),
                        TextEntry::make('net_result')->label('Risultato netto')->money('EUR')->placeholder('—'),
                    ])
                    ->columns(3)
                    ->placeholder('Nessun bilancio disponibile'),
            ]);
    }

    private static function documentsSection(): Section
    {
        return Section::make('Documenti')
            ->schema([
                RepeatableEntry::make('documents')
                    ->hiddenLabel()
                    ->state(fn (CaiSection $record) => $record->runtsRegistrations->flatMap->documents)
                    ->schema([
                        TextEntry::make('title')->label('Titolo')->placeholder(fn (CaiDocument $record): string => $record->file_name ?? '—'),
                        TextEntry::make('document_type')->label('Tipo')->badge()->placeholder('—'),
                        TextEntry::make('year')->label('Anno')->placeholder('—'),
                        TextEntry::make('download')
                            ->label('Scarica')
                            ->state('Scarica il file')
                            ->url(fn (CaiDocument $record): string => route('cai-documents.download', $record))
                            ->openUrlInNewTab(),
                    ])
                    ->columns(4)
                    ->placeholder('Nessun allegato disponibile'),
            ]);
    }

    private static function subsectionsSection(): Section
    {
        return Section::make('Sottosezioni')
            ->schema([
                RepeatableEntry::make('subsections')
                    ->hiddenLabel()
                    ->schema([
                        TextEntry::make('name')->label('Denominazione'),
                        TextEntry::make('email')->label('Email')->placeholder('—'),
                        TextEntry::make('phone')->label('Telefono')->placeholder('—'),
                        TextEntry::make('user.name')->label('Utente collegato')->placeholder('Nessuno'),
                    ])
                    ->columns(4)
                    ->placeholder('Nessuna sottosezione collegata'),
            ]);
    }
}
