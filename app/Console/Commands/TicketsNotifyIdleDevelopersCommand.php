<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use App\Domain\Mail\Actions\SendOutboundTicketMail;
use App\Domain\Mail\Enums\NotificationType;
use App\Domain\Mail\Mailables\IdleDeveloperNoticeMail;
use App\Domain\Mail\Models\EmailMessage;
use App\Domain\Mail\Support\RecipientLocale;
use App\Domain\Mail\Support\StaffDatabaseNotification;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Queries\ArchivedTicketsQuery;
use App\Domain\Ticketing\Queries\MyTicketsQuery;
use App\Filament\Resources\Tickets\TicketResource;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * E11 (§7.5.2/§10.2 del PRD, US-616): promemoria interno a un developer con
 * ticket assegnati ma nessuno `status = progress` — colma il gap del v1
 * (job ritardato di 30 minuti lanciato da un observer, mai un comando
 * schedulato).
 *
 * "Idle" esclude i ticket già conclusi (`done`/`rejected`, stessa
 * convenzione già in uso da {@see MyTicketsQuery}/
 * {@see ArchivedTicketsQuery}): un developer il
 * cui unico ticket assegnato è già chiuso non ha nulla "in coda" da
 * riprendere.
 *
 * Rispetta §10.1: `--dry-run` non scrive/invia nulla, log strutturato,
 * idempotente per costruzione — un promemoria già inviato OGGI blocca un
 * secondo invio allo stesso developer nella stessa finestra (09:00–15:30,
 * la finestra ricorre una sola volta al giorno), indipendentemente da quante
 * volte il comando gira nei 30 minuti successivi. `window_start`/
 * `window_end` (config('ticketing.idle_developer_notice')) riproducono a
 * livello applicativo lo stesso vincolo orario del v1 ("solo prima delle
 * 15:30"), oltre alla cadenza del cron in routes/console.php.
 */
final class TicketsNotifyIdleDevelopersCommand extends Command
{
    protected $signature = 'tickets:notify-idle-developers
        {--dry-run : Esamina gli sviluppatori idle senza inviare il promemoria}';

    protected $description = 'Invia un promemoria interno agli sviluppatori con ticket assegnati ma nessuno in lavorazione (§7.5.2 E11 del PRD).';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $startedAt = now();
        $todayStart = today();

        Log::info('tickets.notify_idle_developers.started', ['dry_run' => $dryRun]);

        if (! $this->withinWindow($startedAt)) {
            $this->info('Fuori dalla finestra oraria configurata: nessun controllo eseguito.');

            Log::info('tickets.notify_idle_developers.finished', [
                'dry_run' => $dryRun,
                'skipped_outside_window' => true,
                'duration_ms' => $startedAt->diffInMilliseconds(now()),
            ]);

            return self::SUCCESS;
        }

        // whereHas('roles', ...), non lo scope role() di Spatie: quello lancia
        // RoleDoesNotExist se la riga `roles` non esiste ancora — stesso idioma
        // già in uso da MailSendDigestCommand.
        $developers = User::query()
            ->whereHas('roles', fn (Builder $query): Builder => $query->where('name', UserRole::Developer->value))
            ->get();

        $examined = 0;
        $notified = 0;
        $skippedNotIdle = 0;
        $skippedAlreadyNotifiedToday = 0;

        foreach ($developers as $developer) {
            $examined++;

            $idleTickets = $this->idleTicketsFor($developer);

            if ($idleTickets->isEmpty()) {
                $skippedNotIdle++;

                continue;
            }

            if ($this->alreadyNotifiedToday($developer, $todayStart)) {
                $skippedAlreadyNotifiedToday++;

                continue;
            }

            if ($dryRun) {
                $notified++;
                $this->line("[dry-run] promemoria per {$developer->email}: {$idleTickets->count()} ticket in coda.");

                continue;
            }

            SendOutboundTicketMail::run(
                ticket: null,
                recipient: $developer,
                notificationType: NotificationType::IdleDeveloperNotice,
                subject: __('You have tickets waiting to be picked up', [], RecipientLocale::resolve($developer)),
                mailableClass: IdleDeveloperNoticeMail::class,
                mailableFactory: fn (EmailMessage $outbound): IdleDeveloperNoticeMail => new IdleDeveloperNoticeMail($idleTickets, $outbound),
            );

            StaffDatabaseNotification::send(
                recipient: $developer,
                title: 'Ticket in coda da riprendere',
                body: "{$idleTickets->count()} ticket assegnati senza nulla in lavorazione.",
                url: TicketResource::getUrl(),
            );

            $notified++;
            $this->info("developer #{$developer->id}: promemoria inviato ({$idleTickets->count()} ticket in coda).");
        }

        $durationMs = $startedAt->diffInMilliseconds(now());

        Log::info('tickets.notify_idle_developers.finished', [
            'dry_run' => $dryRun,
            'examined' => $examined,
            'notified' => $notified,
            'skipped_not_idle' => $skippedNotIdle,
            'skipped_already_notified_today' => $skippedAlreadyNotifiedToday,
            'duration_ms' => $durationMs,
        ]);

        $this->info(sprintf(
            'Sviluppatori esaminati: %d. Promemoria inviati: %d. Non idle: %d. Già notificati oggi: %d.',
            $examined,
            $notified,
            $skippedNotIdle,
            $skippedAlreadyNotifiedToday,
        ));

        return self::SUCCESS;
    }

    /**
     * @return Collection<int, Ticket>
     */
    private function idleTicketsFor(User $developer): Collection
    {
        $assignedTickets = Ticket::query()
            ->where('assignee_id', $developer->id)
            ->whereNotIn('status', [TicketStatus::Done, TicketStatus::Rejected]);

        if ((clone $assignedTickets)->where('status', TicketStatus::Progress)->exists()) {
            return new Collection;
        }

        return $assignedTickets->get();
    }

    private function alreadyNotifiedToday(User $developer, Carbon $todayStart): bool
    {
        return EmailMessage::query()
            ->where('user_id', $developer->id)
            ->where('mailable_class', IdleDeveloperNoticeMail::class)
            ->where('created_at', '>=', $todayStart)
            ->exists();
    }

    private function withinWindow(Carbon $now): bool
    {
        $current = $now->format('H:i');
        $start = (string) config('ticketing.idle_developer_notice.window_start');
        $end = (string) config('ticketing.idle_developer_notice.window_end');

        return $current >= $start && $current <= $end;
    }
}
