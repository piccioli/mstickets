<?php

declare(strict_types=1);

namespace App\Filament\Resources\CaiSections\Tables;

use App\Domain\CaiDirectory\Models\CaiSection;
use App\Filament\Resources\CaiSections\Support\CaiSectionsExporter;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Elenco di sola lettura dell'anagrafica CAI (US-804): unica record action `ViewAction`,
 * nessuna `EditAction`/`DeleteAction` (coerente con `CaiSectionResource::canEdit()/canDelete()`
 * che rifiutano comunque). `cai_sections.region` è una stringa libera, NON castata
 * sull'enum applicativo `App\Domain\Identity\Enums\Region` (quell'enum copre solo le 20
 * regioni italiane, mentre il datapack RUNTS-CAI contiene anche valori come "EXTRA REGIONE"):
 * le opzioni del filtro regione vengono quindi costruite dai valori distinti realmente
 * presenti in tabella, mai dall'enum, per non escludere dati reali.
 */
class CaiSectionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['runtsRegistrations', 'user']))
            ->columns([
                TextColumn::make('name')
                    ->label('Denominazione')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('municipality')
                    ->label('Comune')
                    ->state(fn (CaiSection $record): ?string => $record->runtsRegistrations->first()?->municipality)
                    ->placeholder('—'),
                TextColumn::make('region')
                    ->label('Regione')
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Utente collegato')
                    ->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('region')
                    ->label('Regione')
                    ->options(static fn (): array => CaiSection::query()
                        ->whereNotNull('region')
                        ->distinct()
                        ->orderBy('region')
                        ->pluck('region', 'region')
                        ->all()),
                TernaryFilter::make('user_id')
                    ->label('Con utente collegato')
                    ->placeholder('Tutte')
                    ->trueLabel('Con utente collegato')
                    ->falseLabel('Senza utente collegato')
                    ->queries(
                        true: self::withLinkedUserQuery(...),
                        false: self::withoutLinkedUserQuery(...),
                        blank: fn (Builder $query): Builder => $query,
                    ),
            ])
            ->headerActions([
                Action::make('exportCsv')
                    ->label('Esporta CSV')
                    ->icon(Heroicon::OutlinedDocumentText)
                    ->color('gray')
                    ->action(fn (HasTable $livewire): StreamedResponse => CaiSectionsExporter::csv(self::filteredSections($livewire))),
                Action::make('exportXlsx')
                    ->label('Esporta XLSX')
                    ->icon(Heroicon::OutlinedTableCells)
                    ->color('gray')
                    ->action(fn (HasTable $livewire): StreamedResponse => CaiSectionsExporter::xlsx(self::filteredSections($livewire))),
                Action::make('exportGeoJson')
                    ->label('Esporta GeoJSON')
                    ->icon(Heroicon::OutlinedMap)
                    ->color('gray')
                    ->action(fn (HasTable $livewire): StreamedResponse => CaiSectionsExporter::geoJson(self::filteredSections($livewire))),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    /**
     * Sezioni correntemente filtrate/visibili nella tabella (filtri + ricerca applicati,
     * AC di US-805), non `CaiSection::query()->get()`: `getFilteredSortedTableQuery()` è
     * la stessa query usata da Filament per popolare la pagina corrente, qui eseguita per
     * intero (senza paginazione) al momento del click sull'azione di export.
     *
     * @return Collection<int, CaiSection>
     */
    private static function filteredSections(HasTable $livewire): Collection
    {
        /** @var Builder<CaiSection> $query */
        $query = $livewire->getFilteredSortedTableQuery();

        return $query->with('user')->get();
    }

    /**
     * Estratto in un metodo tipizzato perché il parametro `Builder` di
     * `TernaryFilter::queries()` non porta il generico del modello collegato
     * (stesso idioma di `FundraisingOpportunitiesTable::activeQuery()`).
     *
     * @param  Builder<CaiSection>  $query
     * @return Builder<CaiSection>
     */
    private static function withLinkedUserQuery(Builder $query): Builder
    {
        return $query->whereNotNull('user_id');
    }

    /**
     * @param  Builder<CaiSection>  $query
     * @return Builder<CaiSection>
     */
    private static function withoutLinkedUserQuery(Builder $query): Builder
    {
        return $query->whereNull('user_id');
    }
}
