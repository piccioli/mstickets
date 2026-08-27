<?php

declare(strict_types=1);

namespace App\Filament\Resources\FundraisingProjects;

use App\Domain\Fundraising\Models\FundraisingProject;
use App\Domain\Identity\Enums\Permission;
use App\Filament\Resources\FundraisingOpportunities\FundraisingOpportunityResource;
use App\Filament\Resources\FundraisingProjects\Pages\CreateFundraisingProject;
use App\Filament\Resources\FundraisingProjects\Pages\EditFundraisingProject;
use App\Filament\Resources\FundraisingProjects\Pages\ListFundraisingProjects;
use App\Filament\Resources\FundraisingProjects\RelationManagers\PartnersRelationManager;
use App\Filament\Resources\FundraisingProjects\Schemas\FundraisingProjectForm;
use App\Filament\Resources\FundraisingProjects\Tables\FundraisingProjectsTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

/**
 * Stesso idioma di {@see FundraisingOpportunityResource}
 * (US-502): `FundraisingProjectPolicy::viewAny()` ritorna true anche per un customer
 * (permesso `fundraising.view.involved`, pensato per la vista cliente separata US-508),
 * quindi qui `canViewAny()` è ristretto esplicitamente a `fundraising.view.any` invece
 * di delegare al default Filament->Policy, altrimenti un customer coinvolto vedrebbe la
 * voce di navigazione di questa Resource CRUD riservata allo staff.
 *
 * @extends resource<FundraisingProject>
 */
class FundraisingProjectResource extends Resource
{
    protected static ?string $model = FundraisingProject::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolderOpen;

    protected static UnitEnum|string|null $navigationGroup = 'Fundraising';

    protected static ?string $modelLabel = 'progetto';

    protected static ?string $pluralModelLabel = 'progetti';

    public static function form(Schema $schema): Schema
    {
        return FundraisingProjectForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FundraisingProjectsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            PartnersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFundraisingProjects::route('/'),
            'create' => CreateFundraisingProject::route('/create'),
            'edit' => EditFundraisingProject::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return (bool) Auth::user()?->can(Permission::FundraisingViewAny);
    }
}
