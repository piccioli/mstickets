<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Domain\CaiDirectory\Queries\CaiRegionalGroupsQuery;
use App\Domain\Identity\Enums\CustomerType;
use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Enums\Region;
use App\Domain\Identity\Models\User;
use App\Filament\Resources\Users\UserResource;
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
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

/**
 * Elenco dei Gruppi Regionali (utenti `CustomerType::GruppoRegionale`) con, per ciascuno, quante sezioni ha la
 * regione e a che punto sono i bilanci dell'anno {@see self::YEAR}. Sola lettura: nessuna anagrafica GR dedicata,
 * l'azione di riga rimanda alla scheda dell'utente. Conteggi in {@see CaiRegionalGroupsQuery} (una sola
 * aggregazione per regione).
 */
class CaiRegionalGroups extends Page implements HasTable
{
    use InteractsWithTable;

    private const YEAR = 2025;

    protected string $view = 'filament-panels::pages.page';

    protected static ?string $title = 'Elenco gruppi regionali';

    protected static ?string $navigationLabel = 'Elenco gruppi regionali';

    protected static string|UnitEnum|null $navigationGroup = 'Anagrafica CAI';

    protected static ?string $navigationParentItem = 'Gruppi regionali';

    protected static ?int $navigationSort = 31;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    public static function canAccess(): bool
    {
        return (bool) Auth::user()?->can(Permission::CaiDirectoryView);
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
            ->query(CaiRegionalGroupsQuery::forYear(self::YEAR))
            ->columns([
                TextColumn::make('name')->label('Gruppo')->searchable()->sortable(),
                TextColumn::make('region')
                    ->label('Regione')
                    ->formatStateUsing(fn (?Region $state): ?string => $state?->label())
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('email')->label('Email')->searchable(),
                TextColumn::make('sections_count')->label('Sezioni')->numeric()->sortable(),
                TextColumn::make('with_financials_count')->label('Con bilancio '.self::YEAR)->numeric()->sortable(),
                TextColumn::make('income_parsed_count')->label('Conto economico interpretato '.self::YEAR)->numeric()->sortable(),
                TextColumn::make('coverage_percent')
                    ->label('Copertura %')
                    ->formatStateUsing(fn (mixed $state): string => $state === null ? '—' : (int) $state.'%')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Utente attivo')
                    ->boolean()
                    ->state(fn (User $record): bool => $record->deactivated_at === null)
                    ->tooltip(fn (User $record): string => $record->deactivated_at === null ? 'Account attivo' : 'Account disattivato'),
            ])
            ->defaultSort('name')
            ->paginated([25, 50, 100])
            ->defaultPaginationPageOption(25)
            ->filters([
                SelectFilter::make('region')
                    ->label('Regione')
                    ->options(static fn (): array => User::query()
                        ->where('customer_type', CustomerType::GruppoRegionale)
                        ->whereNotNull('region')
                        ->pluck('region')
                        ->mapWithKeys(fn (Region $region): array => [$region->value => $region->label()])
                        ->sort()
                        ->all()),
            ])
            ->recordActions([
                Action::make('openUser')
                    ->label('Apri utente')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (User $record): string => UserResource::getUrl('view', ['record' => $record]))
                    ->visible(fn (): bool => (bool) Auth::user()?->can(Permission::UserView)),
            ])
            ->emptyStateHeading('Nessun gruppo regionale')
            ->emptyStateDescription('Non esiste ancora nessun utente di tipo Gruppo Regionale.');
    }
}
