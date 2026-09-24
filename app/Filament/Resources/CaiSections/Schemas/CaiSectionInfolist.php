<?php

declare(strict_types=1);

namespace App\Filament\Resources\CaiSections\Schemas;

use App\Domain\CaiDirectory\Models\CaiDocument;
use App\Domain\CaiDirectory\Models\CaiRuntsRegistration;
use App\Domain\CaiDirectory\Models\CaiSection;
use App\Domain\CaiDirectory\Support\CaiRichTextSanitizer;
use App\Domain\CaiDirectory\Support\CaiSectionRuntsComparator;
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
 *
 * I tab "Bilanci"/"Allegati" uniscono SEMPRE due fonti: quelli collegati a una registrazione
 * RUNTS (`runtsRegistrations->flatMap->...`, la maggioranza) e quelli collegati direttamente
 * alla sezione (`CaiSection::documents()`/`financialStatements()`, upload manuale/Veryfico per
 * una sezione senza presenza RUNTS) — un unico elenco, mai due tab separati.
 *
 * `->persistTabInQueryString('tab')` + `Tab::make('Allegati')->id('allegati')`: dopo un upload
 * manuale (`ViewCaiSection::uploadDocumentAction()`) la pagina va ricaricata per rileggere le
 * relazioni della sezione (un `CaiDocument` appena creato non è nella cache Eloquent già
 * caricata dall'Infolist) — un id esplicito e stabile sul tab "Allegati" permette a
 * quell'azione di ricaricare direttamente su `?tab=allegati`, senza dipendere dallo slug
 * generato automaticamente da Filament (che cambierebbe se l'etichetta del tab cambiasse).
 */
class CaiSectionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('cai_section')
                    ->columnSpanFull()
                    ->persistTabInQueryString('tab')
                    ->tabs([
                        Tab::make('Dati CAI')
                            ->schema([self::caiDataSection()]),
                        Tab::make('Dati RUNTS')
                            ->schema([self::runtsSection()]),
                        Tab::make('Differenze')
                            ->schema([self::differencesSection()]),
                        Tab::make('Bilanci')
                            ->schema([self::financialStatementsSection()]),
                        Tab::make('Allegati')
                            ->id('allegati')
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
                TextEntry::make('cai_directory_url')
                    ->label('Scheda sul sito CAI')
                    ->state('Apri la scheda sul sito CAI')
                    ->url(fn (CaiSection $record): string => self::officialCaiDirectoryUrl($record))
                    ->openUrlInNewTab(),
                TextEntry::make('address')->label('Indirizzo')->placeholder('—'),
                TextEntry::make('postal_address')->label('Recapito postale')->placeholder('—'),
                TextEntry::make('founded_year')->label('Anno di fondazione')->placeholder('—'),
                TextEntry::make('members_count')->label('Numero soci')->placeholder('—'),
                TextEntry::make('office_hours')
                    ->label('Orari di apertura')
                    ->columnSpanFull()
                    ->placeholder('—')
                    ->html()
                    ->formatStateUsing(fn (?string $state): ?string => $state === null ? null : CaiRichTextSanitizer::sanitize(trim($state))),
                TextEntry::make('notices')
                    ->label('Avvisi')
                    ->columnSpanFull()
                    ->placeholder('—')
                    ->html()
                    ->formatStateUsing(fn (?string $state): ?string => $state === null ? null : CaiRichTextSanitizer::sanitize(trim($state))),
                TextEntry::make('cai_last_synced_at')
                    ->label('Ultimo aggiornamento dal sito CAI')
                    ->columnSpanFull()
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('Mai sincronizzato dal vivo'),
                TextEntry::make('user.name')->label('Utente collegato')->placeholder('Nessuno'),
            ]);
    }

    /**
     * URL pubblico della scheda ufficiale di QUESTA sezione sul sito CAI (Fase 9,
     * storia 5): la vera fonte da cui `sezioni_cai`/`cai_sections` è stata costruita
     * (§1 del design doc Fase 8, "Directory ufficiale CAI") — mai il campo `website`
     * (il sito PROPRIO della sezione, un dato diverso e non sempre valorizzato,
     * NULL per ~254 sezioni su 529 nel dataset reale). Costruito da `codice_cai`
     * (sempre presente, è la PK naturale), quindi funziona per OGNI sezione a
     * prescindere da `website`. Pattern URL fornito direttamente dal committente,
     * mai indovinato: `?codice=<codice_cai>` su
     * `cai.it/sezioni-territoriali/sezioni-e-sottosezioni/sezione/`.
     */
    private static function officialCaiDirectoryUrl(CaiSection $record): string
    {
        return 'https://www.cai.it/sezioni-territoriali/sezioni-e-sottosezioni/sezione/?codice='.$record->codice_cai;
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
                        TextEntry::make('runts_last_synced_at')
                            ->label('Ultimo aggiornamento dal vivo')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('Mai sincronizzato dal vivo'),
                    ])
                    ->columns(4)
                    ->placeholder('Nessuna registrazione RUNTS collegata'),
            ]);
    }

    /**
     * Confronto fra i campi CaiSection (fonte: sito web della sezione) e ciascuna
     * registrazione RUNTS collegata (Fase 9): puro strumento diagnostico su dati GIÀ
     * importati ({@see CaiSectionRuntsComparator}), nessun fetch esterno in tempo
     * reale (design deliberato). `RepeatableEntry` FLAT (una riga per campo per
     * registrazione, non annidata) — più semplice da costruire con lo schema
     * Infolist e sufficiente quando (caso tipico) una sezione ha una sola
     * registrazione collegata.
     */
    private static function differencesSection(): Section
    {
        return Section::make('Confronto dati CAI / RUNTS')
            ->schema([
                RepeatableEntry::make('runts_differences')
                    ->hiddenLabel()
                    ->state(fn (CaiSection $record) => CaiSectionRuntsComparator::compare($record))
                    ->schema([
                        TextEntry::make('registration_label')->label('Registrazione RUNTS'),
                        TextEntry::make('field')->label('Campo'),
                        TextEntry::make('cai_value')->label('Valore CAI')->placeholder('—'),
                        TextEntry::make('runts_value')->label('Valore RUNTS')->placeholder('—'),
                        TextEntry::make('status')
                            ->label('Esito')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'Diverso' => 'danger',
                                'Uguale' => 'success',
                                default => 'gray',
                            }),
                    ])
                    ->columns(5)
                    ->placeholder('Nessuna registrazione RUNTS collegata: nessun confronto possibile'),
            ]);
    }

    private static function financialStatementsSection(): Section
    {
        return Section::make('Bilanci per anno')
            ->schema([
                RepeatableEntry::make('financial_statements')
                    ->hiddenLabel()
                    ->state(fn (CaiSection $record) => $record->financialStatements
                        ->merge($record->runtsRegistrations->flatMap->financialStatements)
                        ->sortByDesc('year')
                        ->values())
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
                    ->state(fn (CaiSection $record) => $record->documents
                        ->merge($record->runtsRegistrations->flatMap->documents)
                        ->sortByDesc(fn (CaiDocument $document): int => $document->year ?? -1)
                        ->values())
                    ->schema([
                        TextEntry::make('title')->label('Titolo')->placeholder(fn (CaiDocument $record): string => $record->file_name ?? '—'),
                        TextEntry::make('document_type')->label('Tipo')->badge()->placeholder('—'),
                        TextEntry::make('year')->label('Anno')->placeholder('—'),
                        TextEntry::make('source')->label('Fonte')->badge()->placeholder('—'),
                        TextEntry::make('download')
                            ->label('Scarica')
                            ->state('Scarica il file')
                            ->url(fn (CaiDocument $record): string => route('cai-documents.download', $record))
                            ->openUrlInNewTab(),
                    ])
                    ->columns(5)
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
                        TextEntry::make('cai_last_synced_at')
                            ->label('Ultimo aggiornamento')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('Mai sincronizzato dal vivo'),
                        TextEntry::make('user.name')->label('Utente collegato')->placeholder('Nessuno'),
                    ])
                    ->columns(5)
                    ->placeholder('Nessuna sottosezione collegata'),
            ]);
    }
}
