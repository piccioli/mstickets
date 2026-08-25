<?php

declare(strict_types=1);

namespace App\Filament\Resources\EmailMessages;

use App\Domain\Mail\Models\EmailMessage;
use App\Filament\Resources\EmailMessages\Pages\ListEmailMessages;
use App\Filament\Resources\EmailMessages\Pages\ViewEmailMessage;
use App\Filament\Resources\EmailMessages\Schemas\EmailMessageInfolist;
use App\Filament\Resources\EmailMessages\Tables\EmailMessagesTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Registro email (§7.7, US-321), sola lettura: nessuna pagina di creazione/modifica
 * registrata (solo `index`/`view`). `EmailMessage` ha una Policy propria
 * (`App\Domain\Mail\Policies\EmailMessagePolicy`, deny-by-default via
 * `Permission::EmailView`/`EmailManage`) risolta automaticamente da Laravel, quindi
 * — a differenza di `RoleResource` (modello Spatie senza Policy) — non serve
 * sovrascrivere i metodi `can*` qui: Filament chiama già `Gate::authorize(...)`.
 */
class EmailMessageResource extends Resource
{
    protected static ?string $model = EmailMessage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxStack;

    protected static ?string $modelLabel = 'email';

    protected static ?string $pluralModelLabel = 'Registro';

    protected static ?string $navigationLabel = 'Registro';

    protected static UnitEnum|string|null $navigationGroup = 'Email';

    public static function infolist(Schema $schema): Schema
    {
        return EmailMessageInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EmailMessagesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEmailMessages::route('/'),
            'view' => ViewEmailMessage::route('/{record}'),
        ];
    }
}
