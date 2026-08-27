<?php

declare(strict_types=1);

namespace App\Filament\Resources\FundraisingOpportunities\Pages;

use App\Domain\Fundraising\Models\FundraisingOpportunity;
use App\Filament\Resources\FundraisingOpportunities\FundraisingOpportunityResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

/**
 * "Attive" (default) e "Archivio" sono tab della tabella che delegano il filtro
 * agli scope del model ({@see FundraisingOpportunity::scopeActive()}/
 * `scopeExpired()`, US-501), mai una condizione riscritta qui (§6.6.1).
 */
class ListFundraisingOpportunities extends ListRecords
{
    protected static string $resource = FundraisingOpportunityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    /**
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        return [
            'active' => Tab::make('Attive')->modifyQueryUsing(self::activeQuery(...)),
            'archive' => Tab::make('Archivio')->modifyQueryUsing(self::expiredQuery(...)),
        ];
    }

    /**
     * Estratto in un metodo tipizzato invece di una closure inline perché il
     * parametro `Builder` di `modifyQueryUsing()` non porta il generico del
     * modello collegato (stesso idioma di `TicketForm::activeUsersQuery()`).
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
