<?php

declare(strict_types=1);

namespace App\Filament\Resources\FundraisingProjects\Tables;

use App\Domain\Fundraising\Enums\FundraisingProjectStatus;
use App\Domain\Fundraising\Models\FundraisingProject;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class FundraisingProjectsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->label('Titolo')->searchable()->limit(60),
                TextColumn::make('status')->label('Stato')->badge(),
                TextColumn::make('leadUser.name')->label('Capofila')->placeholder('—')->toggleable(),
                TextColumn::make('requested_amount')->label('Importo richiesto')->money('EUR')->sortable(),
                TextColumn::make('approved_amount')->label('Importo approvato')->money('EUR')->sortable(),
                TextColumn::make('submitted_at')->label('Data presentazione')->date()->sortable()->toggleable(),
                TextColumn::make('decided_at')->label('Data decisione')->date()->sortable()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Stato')
                    ->options(FundraisingProjectStatus::class),
                SelectFilter::make('lead_user_id')
                    ->label('Capofila')
                    ->relationship('leadUser', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('partner')
                    ->label('Partner')
                    ->relationship('partners', 'name')
                    ->searchable()
                    ->preload(),
                Filter::make('involving')
                    ->label('Coinvolti')
                    ->toggle()
                    ->query(self::involvingQuery(...)),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    /**
     * Estratto in un metodo tipizzato perché il parametro `Builder` di
     * `Filter::query()` non porta il generico del modello collegato, stesso
     * idioma di `FundraisingOpportunitiesTable::activeQuery()`. "Coinvolti"
     * riusa {@see FundraisingProject::scopeInvolving()} (US-506), mai una
     * condizione OR riscritta qui.
     *
     * @param  Builder<FundraisingProject>  $query
     * @return Builder<FundraisingProject>
     */
    private static function involvingQuery(Builder $query): Builder
    {
        $user = Auth::user();

        if ($user === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->involving($user);
    }
}
