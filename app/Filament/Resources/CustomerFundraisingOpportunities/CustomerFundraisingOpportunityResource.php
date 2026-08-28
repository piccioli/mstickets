<?php

declare(strict_types=1);

namespace App\Filament\Resources\CustomerFundraisingOpportunities;

use App\Domain\Fundraising\Models\FundraisingOpportunity;
use App\Domain\Identity\Enums\Permission;
use App\Filament\Resources\CustomerFundraisingOpportunities\Pages\ListCustomerFundraisingOpportunities;
use App\Filament\Resources\CustomerFundraisingOpportunities\Pages\ViewCustomerFundraisingOpportunity;
use App\Filament\Resources\CustomerFundraisingOpportunities\Schemas\CustomerFundraisingOpportunityInfolist;
use App\Filament\Resources\CustomerFundraisingOpportunities\Tables\CustomerFundraisingOpportunitiesTable;
use App\Filament\Resources\FundraisingOpportunities\FundraisingOpportunityResource;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

/**
 * Vista cliente in sola lettura (§6.6.4, US-508): elenco e dettaglio di
 * QUALUNQUE opportunità, nessuna differenza attive/scadute (§6.6.4 non lo
 * richiede, a differenza dell'archivio staff di {@see FundraisingOpportunityResource}).
 * `canViewAny()` è ristretto a "ha `fundraising.view.involved` ma NON
 * `fundraising.view.any`" — nella matrice ruoli attuale (§9.4) è vero solo
 * per `customer`: admin/fundraising vedono la Resource staff, mai questa,
 * evitando due voci di navigazione duplicate per lo stesso dominio.
 * Nessuna pagina create/edit/delete registrata: sola lettura reale, non solo
 * azioni nascoste in UI.
 *
 * @extends resource<FundraisingOpportunity>
 */
class CustomerFundraisingOpportunityResource extends Resource
{
    protected static ?string $model = FundraisingOpportunity::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static UnitEnum|string|null $navigationGroup = 'Area cliente';

    protected static ?string $navigationLabel = 'Opportunità';

    protected static ?string $modelLabel = 'opportunità';

    protected static ?string $pluralModelLabel = 'opportunità';

    public static function table(Table $table): Table
    {
        return CustomerFundraisingOpportunitiesTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CustomerFundraisingOpportunityInfolist::configure($schema);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCustomerFundraisingOpportunities::route('/'),
            'view' => ViewCustomerFundraisingOpportunity::route('/{record}'),
        ];
    }

    public static function canViewAny(): bool
    {
        $user = Auth::user();

        return $user !== null
            && $user->can(Permission::FundraisingViewInvolved)
            && ! $user->can(Permission::FundraisingViewAny);
    }
}
