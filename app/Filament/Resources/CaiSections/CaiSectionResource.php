<?php

declare(strict_types=1);

namespace App\Filament\Resources\CaiSections;

use App\Domain\CaiDirectory\Models\CaiSection;
use App\Domain\Identity\Enums\Permission;
use App\Filament\Resources\CaiSections\Pages\ListCaiSections;
use App\Filament\Resources\CaiSections\Pages\ViewCaiSection;
use App\Filament\Resources\CaiSections\Schemas\CaiSectionInfolist;
use App\Filament\Resources\CaiSections\Tables\CaiSectionsTable;
use App\Filament\Resources\Roles\RoleResource;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

/**
 * Anagrafica CAI (Sezioni/Sottosezioni RUNTS, US-804), sola lettura per lo staff a scopo
 * di segreteria/gestione: nessuna pagina di creazione/modifica/eliminazione registrata, e
 * i metodi can* la negano esplicitamente, stesso pattern di {@see RoleResource}
 * (nessuna Policy dedicata: il modello `CaiSection` viene dall'importer datapack RUNTS-CAI,
 * US-801/US-802, non da un flusso applicativo con owner da confrontare — un unico permesso
 * di catalogo, `Permission::CaiDirectoryView`, gate l'intera risorsa).
 */
class CaiSectionResource extends Resource
{
    protected static ?string $model = CaiSection::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingLibrary;

    protected static ?string $modelLabel = 'sezione CAI';

    protected static ?string $pluralModelLabel = 'anagrafica CAI';

    protected static ?string $navigationLabel = 'Anagrafica CAI';

    protected static UnitEnum|string|null $navigationGroup = 'Anagrafica CAI';

    protected static ?string $recordTitleAttribute = 'name';

    public static function infolist(Schema $schema): Schema
    {
        return CaiSectionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CaiSectionsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCaiSections::route('/'),
            'view' => ViewCaiSection::route('/{record}'),
        ];
    }

    public static function canViewAny(): bool
    {
        return static::canViewDirectory();
    }

    public static function canView(Model $record): bool
    {
        return static::canViewDirectory();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    protected static function canViewDirectory(): bool
    {
        return (bool) Auth::user()?->can(Permission::CaiDirectoryView);
    }
}
