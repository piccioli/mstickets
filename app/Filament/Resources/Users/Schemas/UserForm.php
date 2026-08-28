<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Schemas;

use App\Domain\Identity\Enums\CustomerType;
use App\Domain\Identity\Enums\Permission as AppPermission;
use App\Domain\Identity\Enums\Region;
use App\Domain\Identity\Enums\UserRole;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
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
                            ->hiddenLabel()
                            ->live(),

                        Select::make('customer_type')
                            ->label('Tipo cliente')
                            ->options(collect(CustomerType::cases())->mapWithKeys(
                                fn (CustomerType $type): array => [$type->value => $type->getLabel()],
                            ))
                            ->live()
                            ->visible(fn (Get $get): bool => self::customerRoleSelected($get))
                            ->dehydratedWhenHidden()
                            ->dehydrateStateUsing(fn (Get $get, $state) => self::customerRoleSelected($get) ? $state : null),

                        Select::make('region')
                            ->label('Regione')
                            ->options(collect(Region::cases())->mapWithKeys(
                                fn (Region $region): array => [$region->value => $region->label()],
                            ))
                            ->visible(fn (Get $get): bool => self::customerRoleSelected($get) && self::regionRelevant($get))
                            ->dehydratedWhenHidden()
                            ->dehydrateStateUsing(fn (Get $get, $state) => self::customerRoleSelected($get) && self::regionRelevant($get) ? $state : null),
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

    /**
     * `roles` è un `CheckboxList` legato a una relazione BelongsToMany: il suo stato nel
     * form è un array di ID di ruolo, non di nomi. Risolviamo l'ID del ruolo `customer`
     * per verificare se è tra quelli selezionati.
     */
    private static function customerRoleSelected(Get $get): bool
    {
        $customerRoleId = SpatieRole::query()->where('name', UserRole::Customer->value)->value('id');

        if ($customerRoleId === null) {
            return false;
        }

        return in_array($customerRoleId, (array) $get('roles'), false);
    }

    private static function regionRelevant(Get $get): bool
    {
        $customerType = CustomerType::tryFrom((string) $get('customer_type'));

        return in_array($customerType, [CustomerType::Sezione, CustomerType::GruppoRegionale], true);
    }
}
