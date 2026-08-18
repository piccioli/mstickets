<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Mail\Actions\SendTicketWaitingReminderMail;
use App\Domain\Mail\Mailables\TicketWaitingReminderMail;
use App\Domain\Mail\Models\EmailMessage;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Support\WorkingDaysCalculator;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * E7 (§7.5.2 del PRD, US-316 — nel v1 il comando esisteva ma non era mai
 * schedulato, gap corretto qui registrandolo in routes/console.php):
 * seleziona i ticket `status=waiting` senza attività rilevante
 * (`ticket_logs`/`ticket_views`) da almeno N giorni lavorativi
 * (`config('ticketing.waiting_reminder.threshold_working_days')`,
 * {@see WorkingDaysCalculator}) e invia {@see TicketWaitingReminderMail} al
 * richiedente, saltando un ticket già ricordato entro la finestra di
 * raffreddamento configurata (`cooldown_days`) per non duplicare il
 * promemoria nello stesso periodo.
 */
final class TicketsRemindWaitingCommand extends Command
{
    protected $signature = 'tickets:remind-waiting';

    protected $description = 'Invia un promemoria al richiedente dei ticket in attesa senza attività da giorni (§7.5.2 E7 del PRD).';

    public function handle(): int
    {
        $thresholdWorkingDays = (int) config('ticketing.waiting_reminder.threshold_working_days');
        $cooldownDays = (int) config('ticketing.waiting_reminder.cooldown_days');

        $tickets = Ticket::query()->where('status', TicketStatus::Waiting)->get();

        $reminded = 0;
        $skippedCooldown = 0;
        $skippedNoRequester = 0;

        foreach ($tickets as $ticket) {
            if (! WorkingDaysCalculator::haveElapsed($this->lastActivityAt($ticket), $thresholdWorkingDays)) {
                continue;
            }

            if ($this->recentlyReminded($ticket, $cooldownDays)) {
                $skippedCooldown++;

                continue;
            }

            if ($ticket->requester === null) {
                $skippedNoRequester++;

                continue;
            }

            SendTicketWaitingReminderMail::run($ticket);
            $reminded++;
        }

        $this->info(sprintf(
            'Ticket in attesa esaminati: %d. Promemoria inviati: %d. Saltati per cooldown: %d. Saltati senza richiedente: %d.',
            $tickets->count(),
            $reminded,
            $skippedCooldown,
            $skippedNoRequester,
        ));

        return self::SUCCESS;
    }

    private function lastActivityAt(Ticket $ticket): Carbon
    {
        $lastLogAt = $ticket->logs()->max('occurred_at');
        $lastViewAt = $ticket->views()->max('last_viewed_at');

        return Collection::make([$lastLogAt, $lastViewAt, $ticket->created_at])
            ->filter()
            ->map(static fn (mixed $value): Carbon => $value instanceof Carbon ? $value : Carbon::parse($value))
            ->max();
    }

    private function recentlyReminded(Ticket $ticket, int $cooldownDays): bool
    {
        return EmailMessage::query()
            ->where('ticket_id', $ticket->id)
            ->where('mailable_class', TicketWaitingReminderMail::class)
            ->where('created_at', '>=', now()->subDays($cooldownDays))
            ->exists();
    }
}
