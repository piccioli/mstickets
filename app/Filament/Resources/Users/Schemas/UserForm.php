<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Schemas;

use App\Domain\Identity\Enums\Permission as AppPermission;
use App\Domain\Identity\Enums\UserRole;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Permission as SpatiePermission;
use Spatie\Permission\Models\Role as SpatieRole;

/**
 * Le sezioni "Ruoli"/"Permessi diretti" (§6.7.1, US-021) sono visibili SOLO a chi ha
 * rispettivamente user.assign-roles/user.grant-permissions: un utente che può solo
 * modificare l'anagrafica (user.update) non vede né può alterare ruoli/permessi da qui.
 * Nessuna delle due liste permette di creare un nuovo ruolo/permesso: le opzioni sono
 * sempre quelle già esistenti in tabella (materializzate dal seeder, US-018).
 */
class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Anagrafica')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')->required()->maxLength(255),
                        TextInput::make('email')->email()->required()->maxLength(255),
                        TextInput::make('locale')->maxLength(5)->default('it'),
                    ]),

                Section::make('Ruoli')
                    ->description('Assegna o revoca i ruoli applicativi dell\'utente. L\'elenco dei ruoli disponibili si cambia solo nel seeder, mai da qui.')
                    ->visible(fn (): bool => (bool) Auth::user()?->can(AppPermission::UserAssignRoles))
                    ->schema([
                        CheckboxList::make('roles')
                            ->relationship('roles', 'name', modifyQueryUsing: fn ($query) => $query->orderBy('name'))
                            ->getOptionLabelFromRecordUsing(
                                fn (SpatieRole $record): string => UserRole::tryFrom($record->name)?->getLabel() ?? $record->name,
                            )
                            ->columns(2)
                            ->hiddenLabel(),
                    ]),

                Section::make('Permessi diretti')
                    ->description('Permessi concessi direttamente all\'utente, in aggiunta a quelli già derivati dai ruoli assegnati sopra.')
                    ->visible(fn (): bool => (bool) Auth::user()?->can(AppPermission::UserGrantPermissions))
                    ->schema([
                        CheckboxList::make('permissions')
                            ->relationship('permissions', 'name', modifyQueryUsing: fn ($query) => $query->orderBy('name'))
                            ->getOptionLabelFromRecordUsing(
                                fn (SpatiePermission $record): string => AppPermission::tryFrom($record->name)?->getLabel() ?? $record->name,
                            )
                            ->searchable()
                            ->columns(2)
                            ->hiddenLabel(),
                    ]),
            ]);
    }
}
