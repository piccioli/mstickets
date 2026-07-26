<?php

declare(strict_types=1);

namespace Database\Seeders\Concerns;

use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\Organization;
use App\Domain\Identity\Models\User;
use App\Domain\Reporting\Enums\ActivityReportOwnerKind;
use App\Domain\Reporting\Enums\ActivityReportPeriodType;
use App\Domain\Reporting\Models\ActivityReport;
use App\Domain\Ticketing\Models\Ticket;

/**
 * Coppia di report attività (uno per utente, uno per organizzazione) usata sia da
 * `DevelopmentSeeder` che da `UatSeeder`: a differenza dei metodi "narrativi" degli stessi due
 * seeder (nomi di organizzazioni, titoli di ticket, fundraising — intenzionalmente duplicati per
 * restare indipendenti fra i due ambienti), questa logica non ha alcun contenuto specifico da
 * proteggere: è puro cablaggio di periodi/slice di ticket che driftrebbe silenziosamente se
 * corretto in una copia e non nell'altra.
 */
trait SeedsActivityReports
{
    /**
     * @param  array<string, User>  $roleUsers
     * @param  list<Organization>  $organizations
     * @param  list<Ticket>  $tickets
     */
    private function seedActivityReports(array $roleUsers, array $organizations, array $tickets): void
    {
        $customer = $roleUsers[UserRole::Customer->value];

        $userReport = ActivityReport::query()->firstOrCreate(
            ['owner_kind' => ActivityReportOwnerKind::User, 'owner_user_id' => $customer->id, 'period_type' => ActivityReportPeriodType::Monthly, 'year' => 2026, 'month' => 6],
            ['locale' => 'it'],
        );
        $userReport->tickets()->syncWithoutDetaching(array_map(static fn (Ticket $ticket): int => $ticket->id, array_slice($tickets, 0, 5)));

        $organizationReport = ActivityReport::query()->firstOrCreate(
            ['owner_kind' => ActivityReportOwnerKind::Organization, 'owner_organization_id' => $organizations[0]->id, 'period_type' => ActivityReportPeriodType::Annual, 'year' => 2025],
            ['locale' => 'it'],
        );
        $organizationReport->tickets()->syncWithoutDetaching(array_map(static fn (Ticket $ticket): int => $ticket->id, array_slice($tickets, 5, 5)));
    }
}
