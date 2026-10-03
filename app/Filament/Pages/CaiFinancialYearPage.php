<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Domain\CaiDirectory\Models\CaiSection;
use App\Domain\CaiDirectory\Queries\CaiSectionFinancialYearQuery;
use App\Domain\Identity\Enums\Permission;
use App\Filament\Resources\CaiSections\CaiSectionResource;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

/**
 * Lista delle sezioni CAI con lo stato dei bilanci di un anno (consegna del file di conto economico / stato
 * patrimoniale e relativa interpretazione): base astratta parametrica sull'anno, ogni anno è una sottoclasse di
 * poche righe che implementa solo {@see self::year()} (e l'ordine di menu). Tutte le condizioni vivono in
 * {@see CaiSectionFinancialYearQuery}: colonne e filtri non possono divergere.
 */
abstract class CaiFinancialYearPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament-panels::pages.page';

    protected static string|UnitEnum|null $navigationGroup = 'Anagrafica CAI';

    protected static ?string $navigationParentItem = 'Bilanci';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    abstract public static function year(): int;

    public static function canAccess(): bool
    {
        return (bool) Auth::user()?->can(Permission::CaiDirectoryView);
    }

    public static function getNavigationLabel(): string
    {
        return 'Bilancio '.static::year();
    }

    public function getTitle(): string
    {
        return 'Bilancio '.static::year();
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            EmbeddedTable::make(),
        ]);
    }

    public function table(Table $table): Table
    {
        $year = static::year();

        return $table
            ->query(CaiSectionFinancialYearQuery::forYear($year))
            ->columns([
                TextColumn::make('name')->label('Denominazione')->searchable()->sortable(),
                TextColumn::make('region')->label('Regione')->sortable()->placeholder('—'),
                TextColumn::make('codice_cai')->label('Codice CAI')->searchable(),
                self::flagColumn('has_income_statement_file', 'Conto economico (file)', 'File del conto economico presente'),
                self::flagColumn('has_balance_sheet_file', 'Stato patrimoniale (file)', 'File dello stato patrimoniale presente'),
                self::flagColumn('income_statement_parsed', 'Conto economico interpretato', 'Dati del conto economico estratti'),
                self::flagColumn('balance_sheet_parsed', 'Stato patrimoniale interpretato', 'Dati dello stato patrimoniale estratti'),
            ])
            ->defaultSort(fn (Builder $query): Builder => $query->orderBy('region')->orderBy('name'))
            ->paginated([25, 50, 100])
            ->defaultPaginationPageOption(25)
            ->filters([
                SelectFilter::make('region')
                    ->label('Regione')
                    ->options(static fn (): array => CaiSection::query()
                        ->whereNotNull('region')
                        ->distinct()
                        ->orderBy('region')
                        ->pluck('region', 'region')
                        ->all()),
                self::flagFilter('has_income_statement_file', 'Ha file conto economico', CaiSectionFinancialYearQuery::withIncomeStatementFile(...), $year),
                self::flagFilter('has_balance_sheet_file', 'Ha file stato patrimoniale', CaiSectionFinancialYearQuery::withBalanceSheetFile(...), $year),
                self::flagFilter('income_statement_parsed', 'Conto economico interpretato', CaiSectionFinancialYearQuery::withIncomeStatementParsed(...), $year),
                self::flagFilter('balance_sheet_parsed', 'Stato patrimoniale interpretato', CaiSectionFinancialYearQuery::withBalanceSheetParsed(...), $year),
            ])
            ->recordActions([
                Action::make('open')
                    ->label('Apri scheda')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (CaiSection $record): string => CaiSectionResource::getUrl('view', ['record' => $record, 'tab' => 'allegati'])),
            ])
            ->emptyStateHeading('Nessuna sezione corrisponde ai filtri')
            ->emptyStateDescription('Modifica o rimuovi i filtri per vedere altre sezioni.');
    }

    private static function flagColumn(string $name, string $label, string $tooltip): IconColumn
    {
        return IconColumn::make($name)
            ->label($label)
            ->boolean()
            ->tooltip($tooltip);
    }

    /**
     * @param  callable(Builder<CaiSection>, int, bool): Builder<CaiSection>  $scope
     */
    private static function flagFilter(string $name, string $label, callable $scope, int $year): TernaryFilter
    {
        return TernaryFilter::make($name)
            ->label($label)
            ->placeholder('Tutte')
            ->trueLabel('Sì')
            ->falseLabel('No')
            ->queries(
                true: fn (Builder $query): Builder => $scope($query, $year, true),
                false: fn (Builder $query): Builder => $scope($query, $year, false),
                blank: fn (Builder $query): Builder => $query,
            );
    }
}
