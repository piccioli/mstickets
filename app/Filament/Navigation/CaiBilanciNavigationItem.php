<?php

declare(strict_types=1);

namespace App\Filament\Navigation;

use App\Filament\Pages\CaiFinancialYear2025;
use App\Filament\Pages\CaiFinancialYearPage;
use App\Filament\Pages\CaiUnparsedFinancialStatements;
use Filament\Navigation\NavigationItem;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

/**
 * Sotto-menu "Bilanci" annidato dentro "Sezioni" (gruppo "Anagrafica CAI"). Filament annida nativamente un
 * solo livello (`$navigationParentItem`), quindi qui l'albero a due livelli è costruito a mano: la voce
 * "Bilanci" (senza URL, ad espansione grazie alla vista sidebar sovrascritta in
 * `resources/views/vendor/filament-panels/components/sidebar/item.blade.php`) porta le proprie voci figlie come
 * `childItems`, e le pagine figlie NON si registrano da sole (`$shouldRegisterNavigation = false`).
 * Per aggiungere "Bilancio 2026": una sottoclasse di {@see CaiFinancialYearPage} e una riga in {@see self::pages()}.
 */
final class CaiBilanciNavigationItem
{
    public static function make(): NavigationItem
    {
        return NavigationItem::make('Bilanci')
            ->group('Anagrafica CAI')
            ->parentItem('Sezioni')
            ->icon(Heroicon::OutlinedDocumentChartBar)
            ->sort(13)
            ->visible(fn (): bool => self::visiblePages() !== [])
            ->childItems(collect(self::pages())
                ->map(fn (string $page, int $index): NavigationItem => NavigationItem::make($page::getNavigationLabel())
                    ->icon($page::getNavigationIcon())
                    ->url(fn (): string => $page::getUrl())
                    ->isActiveWhen(fn (): bool => request()->routeIs($page::getRouteName()))
                    ->visible(fn (): bool => $page::canAccess())
                    ->sort($index))
                ->all());
    }

    /**
     * Ordine di menu delle pagine figlie.
     *
     * @return list<class-string<Page>>
     */
    private static function pages(): array
    {
        return [
            CaiUnparsedFinancialStatements::class,
            CaiFinancialYear2025::class,
        ];
    }

    /**
     * @return list<class-string<Page>>
     */
    private static function visiblePages(): array
    {
        return array_values(array_filter(self::pages(), fn (string $page): bool => $page::canAccess()));
    }
}
