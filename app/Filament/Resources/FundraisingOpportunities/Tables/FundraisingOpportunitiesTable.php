<?php

declare(strict_types=1);

namespace App\Filament\Resources\FundraisingOpportunities\Tables;

use App\Domain\Fundraising\Enums\TerritorialScope;
use App\Domain\Fundraising\Models\FundraisingOpportunity;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FundraisingOpportunitiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nome')->searchable()->limit(60),
                TextColumn::make('deadline')->label('Scadenza')->date()->sortable(),
                TextColumn::make('territorial_scope')->label('Ambito territoriale')->badge(),
                TextColumn::make('sponsor')->label('Ente finanziatore')->searchable()->toggleable(),
                TextColumn::make('responsibleUser.name')->label('Responsabile')->toggleable(),
                TextColumn::make('created_at')->label('Creata il')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('territorial_scope')
                    ->label('Ambito territoriale')
                    ->options(collect(TerritorialScope::cases())->mapWithKeys(
                        fn (TerritorialScope $scope): array => [$scope->value => $scope->getLabel()],
                    )),
                TernaryFilter::make('cofinancing_quota')
                    ->label('Cofinanziamento')
                    ->placeholder('Tutte')
                    ->trueLabel('Con quota di cofinanziamento')
                    ->falseLabel('Senza quota di cofinanziamento')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->whereNotNull('cofinancing_quota'),
                        false: fn (Builder $query): Builder => $query->whereNull('cofinancing_quota'),
                        blank: fn (Builder $query): Builder => $query,
                    ),
                TernaryFilter::make('expired')
                    ->label('Scaduto')
                    ->placeholder('Tutte')
                    ->trueLabel('Scadute')
                    ->falseLabel('Attive')
                    ->queries(
                        true: self::expiredQuery(...),
                        false: self::activeQuery(...),
                        blank: fn (Builder $query): Builder => $query,
                    ),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    /**
     * Estratto in un metodo tipizzato perché il parametro `Builder` di
     * `TernaryFilter::queries()` non porta il generico del modello collegato
     * (stesso idioma di `TicketForm::activeUsersQuery()`).
     *
     * @param  Builder<FundraisingOpportunity>  $query
     * @return Builder<FundraisingOpportunity>
     */
    private static function activeQuery(Builder $query): Builder
    {
        return $query->active();
    }

    /**
     * @param  Builder<FundraisingOpportunity>  $query
     * @return Builder<FundraisingOpportunity>
     */
    private static function expiredQuery(Builder $query): Builder
    {
        return $query->expired();
    }
}
