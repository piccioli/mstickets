<?php

declare(strict_types=1);

namespace App\Filament\Resources\DocumentationPages;

use App\Domain\Documentation\Models\DocumentationPage;
use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use App\Filament\Resources\DocumentationPages\Pages\CreateDocumentationPage;
use App\Filament\Resources\DocumentationPages\Pages\EditDocumentationPage;
use App\Filament\Resources\DocumentationPages\Pages\ListDocumentationPages;
use App\Filament\Resources\DocumentationPages\Schemas\DocumentationPageForm;
use App\Filament\Resources\DocumentationPages\Tables\DocumentationPagesTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

/**
 * Policy-backed (`DocumentationPagePolicy`, già esistente da US-404): nessun
 * override manuale di `can*()`, stesso pattern di `TicketResource` (US-110).
 * `getEloquentQuery()` incatena SEMPRE `DocumentationPage::scopeVisibleTo()`
 * (§9.4): protegge sia la tabella sia il binding di rotta per Edit, stesso
 * principio di sicurezza già applicato da `TicketResource`.
 *
 * @extends resource<DocumentationPage>
 */
class DocumentationPageResource extends Resource
{
    protected static ?string $model = DocumentationPage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static ?string $modelLabel = 'pagina di documentazione';

    protected static ?string $pluralModelLabel = 'documentazione';

    /**
     * Gruppo dinamico (US-602): un customer vede questa risorsa sotto "Area
     * cliente" — staff/fundraising restano sotto "Documentazione" come prima.
     */
    public static function getNavigationGroup(): string|UnitEnum|null
    {
        $user = Auth::user();

        return $user instanceof User && $user->hasRole(UserRole::Customer->value)
            ? 'Area cliente'
            : 'Documentazione';
    }

    public static function form(Schema $schema): Schema
    {
        return DocumentationPageForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DocumentationPagesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDocumentationPages::route('/'),
            'create' => CreateDocumentationPage::route('/create'),
            'edit' => EditDocumentationPage::route('/{record}/edit'),
        ];
    }

    /**
     * @return Builder<DocumentationPage>
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $user = Auth::user();

        if (! $user instanceof User) {
            return $query->whereRaw('1 = 0');
        }

        return $query->visibleTo($user);
    }
}
