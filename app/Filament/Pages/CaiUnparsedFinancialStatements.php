<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Domain\CaiDirectory\Enums\CaiDocumentAnalysisStatus;
use App\Domain\CaiDirectory\Jobs\AnalyzeCaiFinancialStatementDocument;
use App\Domain\CaiDirectory\Models\CaiDocument;
use App\Domain\Identity\Enums\Permission;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;
use UnitEnum;

/**
 * Elenco dei bilanci CAI il cui parsing non ha estratto alcun dato (Fase 9, seguito alla story "sync RUNTS
 * su tutte le sezioni"): pensata per gli amministratori/sviluppatori che devono analizzare/migliorare il
 * parser Python, non per lo staff generico — permesso dedicato più restrittivo di
 * `Permission::CaiDirectoryView` (sola lettura anagrafica).
 *
 * Mostra il SINGOLO `CaiDocument` con `financial_analysis_status = NoDataExtracted` (US "review parser
 * bilanci"), non il record aggregato `cai_financial_statements`: un documento può fallire anche se un
 * altro documento per lo stesso (registrazione, anno) ha prodotto dati buoni e "vince" nel merge — vedi
 * {@see AnalyzeCaiFinancialStatementDocument::recordDocumentAnalysisOutcome()}.
 *
 * "Fonte" è mostrata come testo fisso "RUNTS" (nessuna colonna dedicata): oggi ogni `CaiDocument` viene
 * SEMPRE dalla sincronizzazione RUNTS live, non esiste ancora un secondo percorso (upload manuale, previsto
 * per il futuro) — aggiungere una colonna `source` reale solo quando quel percorso esisterà davvero.
 */
class CaiUnparsedFinancialStatements extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament-panels::pages.page';

    protected static ?string $title = 'Bilanci non interpretati';

    protected static ?string $navigationLabel = 'Bilanci non interpretati';

    protected static string|UnitEnum|null $navigationGroup = 'Anagrafica CAI';

    protected static ?string $navigationParentItem = 'Bilanci';

    protected static ?int $navigationSort = 21;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentMagnifyingGlass;

    public static function canAccess(): bool
    {
        return (bool) Auth::user()?->can(Permission::CaiDirectoryReviewUnparsedDocuments);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            EmbeddedTable::make(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(CaiDocument::query()
                ->where('document_type', 'bilancio_esercizio')
                ->where('financial_analysis_status', CaiDocumentAnalysisStatus::NoDataExtracted)
                ->with('runtsRegistration.section'))
            ->columns([
                TextColumn::make('runtsRegistration.section.name')->label('Sezione')->placeholder('—'),
                TextColumn::make('runtsRegistration.section.codice_cai')->label('Codice CAI')->placeholder('—'),
                TextColumn::make('year')->label('Anno')->sortable(),
                TextColumn::make('source')->label('Fonte')->state('RUNTS'),
                TextColumn::make('title')->label('Documento')->limit(40)->placeholder('—'),
                TextColumn::make('extracted_via_ocr')
                    ->label('OCR')
                    ->badge()
                    ->formatStateUsing(fn (?bool $state): string => $state === true ? 'Sì' : 'No')
                    ->color(fn (?bool $state): string => $state === true ? 'warning' : 'gray'),
            ])
            ->defaultSort('year', 'desc')
            ->recordActions([
                $this->viewRawTextAction(),
                $this->downloadAction(),
            ]);
    }

    private function viewRawTextAction(): Action
    {
        return Action::make('view_raw_text')
            ->label('Testo estratto')
            ->icon('heroicon-o-eye')
            ->modalHeading('Testo grezzo estratto dal PDF (pdfplumber)')
            ->modalContent(fn (CaiDocument $record): HtmlString => new HtmlString(
                '<pre style="white-space: pre-wrap; font-size: 0.8rem;">'
                .e($record->raw_text_excerpt ?? '(nessun testo estratto)')
                .'</pre>',
            ))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Chiudi');
    }

    private function downloadAction(): Action
    {
        return Action::make('download')
            ->label('Scarica PDF')
            ->icon('heroicon-o-arrow-down-tray')
            ->url(fn (CaiDocument $record): string => route('cai-documents.download', $record))
            ->openUrlInNewTab();
    }
}
