<?php

declare(strict_types=1);

namespace App\Filament\Resources\CustomerFundraisingProjects;

use App\Domain\Fundraising\Models\FundraisingProject;
use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Models\User;
use App\Filament\Resources\CustomerFundraisingOpportunities\CustomerFundraisingOpportunityResource;
use App\Filament\Resources\CustomerFundraisingProjects\Pages\ListCustomerFundraisingProjects;
use App\Filament\Resources\CustomerFundraisingProjects\Pages\ViewCustomerFundraisingProject;
use App\Filament\Resources\CustomerFundraisingProjects\Schemas\CustomerFundraisingProjectInfolist;
use App\Filament\Resources\CustomerFundraisingProjects\Tables\CustomerFundraisingProjectsTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

/**
 * Vista cliente in sola lettura (§6.6.4, US-508): elenco e dettaglio dei
 * SOLI progetti in cui il customer è coinvolto come capofila o partner
 * ({@see FundraisingProject::scopeInvolvingAsCustomer()} — non responsabile
 * o creatore, ruoli interni allo staff). `canViewAny()` stesso idioma di
 * {@see CustomerFundraisingOpportunityResource}.
 * `getEloquentQuery()` incatena SEMPRE lo scope: un progetto fuori
 * dall'insieme "coinvolto" restituisce 404 sul route-model-binding di
 * Filament, PRIMA che la Policy (più ampia, §6.6.3, usata dallo staff) venga
 * anche solo interrogata — stesso principio a due livelli già applicato da
 * `DocumentationPageResource`/`ActivityReportResource`.
 * Nessuna pagina create/edit/delete registrata: sola lettura reale.
 *
 * @extends resource<FundraisingProject>
 */
class CustomerFundraisingProjectResource extends Resource
{
    protected static ?string $model = FundraisingProject::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolderOpen;

    protected static UnitEnum|string|null $navigationGroup = 'Fundraising';

    protected static ?string $navigationLabel = 'Progetti';

    protected static ?string $modelLabel = 'progetto';

    protected static ?string $pluralModelLabel = 'progetti';

    public static function table(Table $table): Table
    {
        return CustomerFundraisingProjectsTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CustomerFundraisingProjectInfolist::configure($schema);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCustomerFundraisingProjects::route('/'),
            'view' => ViewCustomerFundraisingProject::route('/{record}'),
        ];
    }

    public static function canViewAny(): bool
    {
        $user = Auth::user();

        return $user !== null
            && $user->can(Permission::FundraisingViewInvolved)
            && ! $user->can(Permission::FundraisingViewAny);
    }

    /**
     * @return Builder<FundraisingProject>
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $user = Auth::user();

        if (! $user instanceof User) {
            return $query->whereRaw('1 = 0');
        }

        return $query->involvingAsCustomer($user);
    }
}
