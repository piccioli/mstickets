<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Actions;

use App\Domain\Identity\Models\Organization;
use App\Domain\Identity\Models\User;
use App\Domain\Mail\Support\RecipientLocale;
use App\Domain\Reporting\Enums\ActivityReportOwnerKind;
use App\Domain\Reporting\Models\ActivityReport;
use App\Domain\Reporting\Services\ActivityReportSyncService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Unico punto di ingresso per creare un ActivityReport (US-408, §6.5.2):
 * replica applicativamente il vincolo di unicità già imposto a livello DB
 * (indice unique su owner_kind/owner_user_id/owner_organization_id/
 * period_type/year/month) per fallire con un errore leggibile invece della
 * QueryException SQL grezza, deriva `locale` dall'owner (mai passata dal
 * chiamante — stesso principio di {@see RecipientLocale::resolve()} per
 * l'owner utente, `organizations.locale` diretto per l'owner organizzazione,
 * riusato "già derivata" da US-409), poi invoca subito
 * {@see ActivityReportSyncService::syncTickets()}.
 */
final class CreateActivityReport
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public static function run(array $attributes): ActivityReport
    {
        return DB::transaction(function () use ($attributes): ActivityReport {
            $ownerKind = $attributes['owner_kind'] instanceof ActivityReportOwnerKind
                ? $attributes['owner_kind']
                : ActivityReportOwnerKind::from($attributes['owner_kind']);
            $ownerUserId = $attributes['owner_user_id'] ?? null;
            $ownerOrganizationId = $attributes['owner_organization_id'] ?? null;
            $month = $attributes['month'] ?? null;

            $duplicate = ActivityReport::query()
                ->where('owner_kind', $ownerKind)
                ->where('period_type', $attributes['period_type'])
                ->where('year', $attributes['year'])
                ->when(
                    $ownerUserId !== null,
                    fn ($query) => $query->where('owner_user_id', $ownerUserId),
                    fn ($query) => $query->whereNull('owner_user_id'),
                )
                ->when(
                    $ownerOrganizationId !== null,
                    fn ($query) => $query->where('owner_organization_id', $ownerOrganizationId),
                    fn ($query) => $query->whereNull('owner_organization_id'),
                )
                ->when(
                    $month !== null,
                    fn ($query) => $query->where('month', $month),
                    fn ($query) => $query->whereNull('month'),
                )
                ->exists();

            if ($duplicate) {
                throw new RuntimeException('Esiste già un report attività per questo owner e questo periodo.');
            }

            $locale = match ($ownerKind) {
                ActivityReportOwnerKind::User => RecipientLocale::resolve(User::findOrFail($ownerUserId)),
                ActivityReportOwnerKind::Organization => Organization::findOrFail($ownerOrganizationId)->locale,
            };

            $report = ActivityReport::create([...$attributes, 'locale' => $locale]);

            (new ActivityReportSyncService)->syncTickets($report);

            return $report;
        });
    }
}
