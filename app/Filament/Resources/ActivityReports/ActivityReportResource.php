<?php

declare(strict_types=1);

namespace App\Filament\Resources\ActivityReports;

use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use App\Domain\Reporting\Models\ActivityReport;
use App\Filament\Resources\ActivityReports\Pages\ListActivityReports;
use App\Filament\Resources\ActivityReports\Tables\ActivityReportsTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

/**
 * Sola lettura (§6.5.4 del PRD, US-410): non una dashboard cliente completa
 * (fuori scope, Fase 6), solo l'elenco dei propri report attività con
 * download PDF — nessuna pagina create/edit/delete registrata. Policy-backed
 * (`ActivityReportPolicy`, già esistente da US-408): nessun override manuale
 * di `can*()`. `getEloquentQuery()` incatena SEMPRE
 * `ActivityReport::scopeVisibleTo()` (§9.4), stesso principio di sicurezza
 * già applicato da `TicketResource`/`DocumentationPageResource` — un
 * customer/membro organizzazione con solo `.view.own` vede solo i propri
 * report, mai quelli di un altro owner.
 *
 * @extends resource<ActivityReport>
 */
class ActivityReportResource extends Resource
{
    protected static ?string $model = ActivityReport::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentChartBar;

    protected static ?string $modelLabel = 'report attività';

    protected static ?string $pluralModelLabel = 'report attività';

    /**
     * Gruppo dinamico (US-602): un customer vede questa risorsa sotto "Area
     * cliente" — manager/admin restano sotto "Rendicontazione" come prima.
     */
    public static function getNavigationGroup(): string|UnitEnum|null
    {
        $user = Auth::user();

        return $user instanceof User && $user->hasRole(UserRole::Customer->value)
            ? 'Area cliente'
            : 'Rendicontazione';
    }

    public static function table(Table $table): Table
    {
        return ActivityReportsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListActivityReports::route('/'),
        ];
    }

    /**
     * @return Builder<ActivityReport>
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
