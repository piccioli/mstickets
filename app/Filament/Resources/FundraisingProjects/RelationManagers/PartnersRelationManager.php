<?php

declare(strict_types=1);

namespace App\Filament\Resources\FundraisingProjects\RelationManagers;

use App\Domain\Identity\Models\User;
use App\Filament\Resources\Organizations\RelationManagers\UsersRelationManager;
use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Raggiungibile solo dalla pagina Edit (nessuna pagina "view" separata su
 * `FundraisingProjectResource`), quindi solo da chi ha `fundraising.update`
 * (§6.6.3, US-507) — stesso idioma di
 * {@see UsersRelationManager}
 * (US-407).
 */
class PartnersRelationManager extends RelationManager
{
    protected static string $relationship = 'partners';

    protected static ?string $title = 'Partner';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')->label('Nome')->searchable(),
                TextColumn::make('email')->label('Email')->searchable(),
            ])
            ->headerActions([
                AttachAction::make()
                    ->recordSelectSearchColumns(['name', 'email'])
                    // Un utente disattivato non deve comparire come partner selezionabile
                    // (US-608, §6.7.5) — stesso `User::scopeActive()` già usato dai picker
                    // di assegnazione ticket.
                    ->recordSelectOptionsQuery(self::activeUsersQuery(...)),
            ])
            ->recordActions([
                DetachAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DetachBulkAction::make(),
                ]),
            ]);
    }

    /**
     * Estratto in un metodo tipizzato invece di una closure inline: il parametro
     * `Builder` di `recordSelectOptionsQuery()` non porta il generico del modello
     * collegato, stesso motivo di `TicketForm::activeUsersQuery()`.
     *
     * @param  Builder<User>  $query
     * @return Builder<User>
     */
    private static function activeUsersQuery(Builder $query): Builder
    {
        return $query->active();
    }
}
