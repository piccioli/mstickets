<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\Organization;
use App\Domain\Identity\Models\User;
use App\Domain\Reporting\Actions\CreateActivityReport;
use App\Domain\Reporting\Enums\ActivityReportOwnerKind;
use App\Domain\Reporting\Enums\ActivityReportPeriodType;
use App\Domain\Reporting\Jobs\GenerateActivityReportPdfJob;
use App\Domain\Reporting\Models\ActivityReport;
use App\Domain\Reporting\Services\ActivityReportSyncService;
use App\Domain\Ticketing\Models\Ticket;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Genera il report attività del mese precedente per ogni owner attivo
 * (§6.5.2/§10.2 del PRD, US-410): un owner utente è un cliente con almeno un
 * ticket con `done_at` nel periodo; un owner organizzazione è un ente con
 * almeno un membro con un ticket con `done_at` nel periodo (stessa logica di
 * appartenenza di {@see ActivityReportSyncService}).
 * Rispetta le regole comuni §10.1: `--dry-run` non scrive nulla, log
 * strutturato di inizio/fine/durata/conteggi, idempotente (un report già
 * esistente per owner+periodo viene saltato, mai duplicato o rigenerato), un
 * errore su un singolo owner non interrompe il batch — stesso principio già
 * applicato da {@see DocumentationRegeneratePdfsCommand}. Nessuna scrittura
 * di `ticket_logs`: questo comando non muta ticket, solo `activity_reports`.
 */
final class ReportsGenerateMonthlyCommand extends Command
{
    protected $signature = 'reports:generate-monthly
        {--dry-run : Esamina gli owner attivi senza creare report né accodare PDF}';

    protected $description = 'Genera i report attività del mese precedente per ogni owner attivo (§6.5 del PRD).';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $startedAt = now();
        $period = now()->subMonthNoOverflow();
        $year = $period->year;
        $month = $period->month;
        $start = $period->copy()->startOfMonth();
        $end = $period->copy()->endOfMonth();

        Log::info('reports.generate_monthly.started', [
            'dry_run' => $dryRun,
            'year' => $year,
            'month' => $month,
        ]);

        $ticketRequesterIds = Ticket::query()
            ->whereBetween('done_at', [$start, $end])
            ->whereNotNull('requester_id')
            ->pluck('requester_id')
            ->unique();

        $examined = 0;
        $created = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($this->activeUsers($ticketRequesterIds) as $user) {
            $examined++;
            $this->processOwner(
                attributes: [
                    'owner_kind' => ActivityReportOwnerKind::User,
                    'owner_user_id' => $user->id,
                    'period_type' => ActivityReportPeriodType::Monthly,
                    'year' => $year,
                    'month' => $month,
                ],
                label: "utente #{$user->id} \"{$user->name}\"",
                dryRun: $dryRun,
                created: $created,
                skipped: $skipped,
                errors: $errors,
            );
        }

        foreach ($this->activeOrganizations($ticketRequesterIds) as $organization) {
            $examined++;
            $this->processOwner(
                attributes: [
                    'owner_kind' => ActivityReportOwnerKind::Organization,
                    'owner_organization_id' => $organization->id,
                    'period_type' => ActivityReportPeriodType::Monthly,
                    'year' => $year,
                    'month' => $month,
                ],
                label: "organizzazione #{$organization->id} \"{$organization->name}\"",
                dryRun: $dryRun,
                created: $created,
                skipped: $skipped,
                errors: $errors,
            );
        }

        $durationMs = $startedAt->diffInMilliseconds(now());

        Log::info('reports.generate_monthly.finished', [
            'dry_run' => $dryRun,
            'year' => $year,
            'month' => $month,
            'examined' => $examined,
            'created' => $created,
            'skipped' => $skipped,
            'errors' => $errors,
            'duration_ms' => $durationMs,
        ]);

        $this->info(sprintf(
            'Owner attivi esaminati: %d. Report creati: %d. Saltati: %d. Errori: %d.',
            $examined,
            $created,
            $skipped,
            $errors,
        ));

        return self::SUCCESS;
    }

    /**
     * @param  Collection<int, int>  $ticketRequesterIds
     * @return Collection<int, User>
     */
    private function activeUsers(Collection $ticketRequesterIds): Collection
    {
        return User::query()
            ->role(UserRole::Customer->value)
            ->active()
            ->whereIn('id', $ticketRequesterIds)
            ->get();
    }

    /**
     * @param  Collection<int, int>  $ticketRequesterIds
     * @return Collection<int, Organization>
     */
    private function activeOrganizations(Collection $ticketRequesterIds): Collection
    {
        return Organization::query()
            ->whereHas('users', fn ($query) => $query->whereIn('users.id', $ticketRequesterIds))
            ->get();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function processOwner(
        array $attributes,
        string $label,
        bool $dryRun,
        int &$created,
        int &$skipped,
        int &$errors,
    ): void {
        if ($this->reportAlreadyExists($attributes)) {
            $skipped++;
            $this->line("{$label}: report già esistente per il periodo, saltato.");

            return;
        }

        if ($dryRun) {
            $created++;
            $this->line("[dry-run] {$label}: report da creare.");

            return;
        }

        try {
            $report = CreateActivityReport::run($attributes);
            GenerateActivityReportPdfJob::dispatch($report->id);
            $created++;
            $this->info("{$label}: report creato, PDF accodato.");
        } catch (Throwable $exception) {
            $errors++;
            Log::warning('reports.generate_monthly.item_failed', [
                'attributes' => $attributes,
                'error' => $exception->getMessage(),
            ]);
            $this->warn("{$label}: creazione fallita — {$exception->getMessage()}");
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function reportAlreadyExists(array $attributes): bool
    {
        return ActivityReport::query()
            ->where('owner_kind', $attributes['owner_kind'])
            ->where('period_type', $attributes['period_type'])
            ->where('year', $attributes['year'])
            ->where('month', $attributes['month'])
            ->when(
                isset($attributes['owner_user_id']),
                fn ($query) => $query->where('owner_user_id', $attributes['owner_user_id']),
                fn ($query) => $query->whereNull('owner_user_id'),
            )
            ->when(
                isset($attributes['owner_organization_id']),
                fn ($query) => $query->where('owner_organization_id', $attributes['owner_organization_id']),
                fn ($query) => $query->whereNull('owner_organization_id'),
            )
            ->exists();
    }
}
