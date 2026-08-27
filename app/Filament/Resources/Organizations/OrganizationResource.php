<?php

declare(strict_types=1);

namespace App\Filament\Resources\Organizations;

use App\Domain\Identity\Models\Organization;
use App\Filament\Resources\Organizations\Pages\CreateOrganization;
use App\Filament\Resources\Organizations\Pages\EditOrganization;
use App\Filament\Resources\Organizations\Pages\ListOrganizations;
use App\Filament\Resources\Organizations\RelationManagers\ActivityReportsRelationManager;
use App\Filament\Resources\Organizations\RelationManagers\UsersRelationManager;
use App\Filament\Resources\Organizations\Schemas\OrganizationForm;
use App\Filament\Resources\Organizations\Tables\OrganizationsTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Policy-backed (`OrganizationPolicy`, già esistente da uno stage precedente):
 * nessun override manuale di `can*()`, stesso pattern di `TicketResource`
 * (US-110). I due RelationManager (membri, report attività) sono raggiungibili
 * solo dalla pagina Edit, quindi solo da chi ha `organization.update` (admin):
 * un manager con solo `organization.view` vede l'elenco (`index`) ma non
 * raggiunge mai la gestione membri, coerente con §9.4 "manager vede ma non
 * modifica".
 *
 * @extends resource<Organization>
 */
class OrganizationResource extends Resource
{
    protected static ?string $model = Organization::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static UnitEnum|string|null $navigationGroup = 'Utenti e permessi';

    protected static ?string $modelLabel = 'organizzazione';

    protected static ?string $pluralModelLabel = 'organizzazioni';

    public static function form(Schema $schema): Schema
    {
        return OrganizationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OrganizationsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            UsersRelationManager::class,
            ActivityReportsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrganizations::route('/'),
            'create' => CreateOrganization::route('/create'),
            'edit' => EditOrganization::route('/{record}/edit'),
        ];
    }
}
