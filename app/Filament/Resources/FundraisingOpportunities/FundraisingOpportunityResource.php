<?php

declare(strict_types=1);

namespace App\Filament\Resources\FundraisingOpportunities;

use App\Domain\Fundraising\Models\FundraisingOpportunity;
use App\Domain\Identity\Enums\Permission;
use App\Filament\Resources\FundraisingOpportunities\Pages\CreateFundraisingOpportunity;
use App\Filament\Resources\FundraisingOpportunities\Pages\EditFundraisingOpportunity;
use App\Filament\Resources\FundraisingOpportunities\Pages\ListFundraisingOpportunities;
use App\Filament\Resources\FundraisingOpportunities\Schemas\FundraisingOpportunityForm;
use App\Filament\Resources\FundraisingOpportunities\Tables\FundraisingOpportunitiesTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

/**
 * Modulo visibile SOLO ad admin/fundraising (progetto §6.6, mai manager/developer):
 * `FundraisingOpportunityPolicy::viewAny()` ritorna true anche per un customer
 * (permesso `fundraising.view.involved`, US-508 vista cliente separata), quindi
 * qui `canViewAny()` è ristretto esplicitamente a `fundraising.view.any` invece di
 * delegare al default Filament->Policy, stesso idioma di `RoleResource::canViewAny()`.
 * Le altre abilità (create/update/delete) restano sul default Filament->Policy: la
 * Policy le nega già a customer/manager/developer.
 *
 * @extends resource<FundraisingOpportunity>
 */
class FundraisingOpportunityResource extends Resource
{
    protected static ?string $model = FundraisingOpportunity::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static UnitEnum|string|null $navigationGroup = 'Fundraising';

    protected static ?string $modelLabel = 'opportunità';

    protected static ?string $pluralModelLabel = 'opportunità';

    public static function form(Schema $schema): Schema
    {
        return FundraisingOpportunityForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FundraisingOpportunitiesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFundraisingOpportunities::route('/'),
            'create' => CreateFundraisingOpportunity::route('/create'),
            'edit' => EditFundraisingOpportunity::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return (bool) Auth::user()?->can(Permission::FundraisingViewAny);
    }
}
