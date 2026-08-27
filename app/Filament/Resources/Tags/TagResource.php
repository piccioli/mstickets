<?php

declare(strict_types=1);

namespace App\Filament\Resources\Tags;

use App\Domain\Tags\Models\Tag;
use App\Filament\Resources\Tags\Pages\ListTags;
use App\Filament\Resources\Tags\Tables\TagsTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Policy-backed (`TagPolicy`, già esistente da Fase 0/2): nessun override manuale
 * di `can*()`, stesso pattern di `TicketResource` (US-110). Solo la pagina
 * `index` è registrata (US-403, §6.3): la creazione di una commessa avviene
 * dall'azione "Crea commessa" su un ticket (US-402), non da un form dedicato
 * qui — "nessuna azione di CRUD oltre a quelle già coperte dalla Policy".
 *
 * @extends resource<Tag>
 */
class TagResource extends Resource
{
    protected static ?string $model = Tag::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;

    protected static UnitEnum|string|null $navigationGroup = 'Ticket';

    protected static ?string $modelLabel = 'commessa';

    protected static ?string $pluralModelLabel = 'commesse';

    public static function table(Table $table): Table
    {
        return TagsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTags::route('/'),
        ];
    }
}
