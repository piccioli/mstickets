<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use App\Domain\Mail\Actions\BuildCustomerTicketDigest;
use App\Domain\Mail\Actions\SendOutboundTicketMail;
use App\Domain\Mail\Enums\NotificationType;
use App\Domain\Mail\Mailables\MailDigestMail;
use App\Domain\Mail\Models\EmailMessage;
use App\Domain\Mail\Support\RecipientLocale;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * E8 (§7.5.2/§10.2 del PRD, US-614): un'email per cliente con attività nelle
 * 24h precedenti su almeno uno dei suoi ticket ({@see BuildCustomerTicketDigest}),
 * mai un'email per cliente senza attività. Riscritto da zero (il v1 lo aveva
 * come dead code con 4 bug noti, nessun codice riusato).
 *
 * Rispetta §10.1: `--dry-run` non scrive/invia nulla, log strutturato,
 * idempotente per costruzione — un digest già inviato OGGI (`created_at >=`
 * inizio giornata corrente) allo stesso cliente blocca un secondo invio alla
 * stessa esecuzione/ri-esecuzione, indipendentemente dalla finestra di
 * attività di 24h scorrevoli usata per il contenuto. Soppressioni/preferenze
 * (E8 disabilitato, US-605) restano un'unica responsabilità di
 * {@see SendOutboundTicketMail::run()}, non duplicate qui.
 */
final class MailSendDigestCommand extends Command
{
    protected $signature = 'mail:send-digest
        {--dry-run : Esamina i clienti con attività senza inviare il digest}';

    protected $description = 'Invia il digest giornaliero di attività ticket ai clienti che lo hanno abilitato (§7.5.2 E8 del PRD).';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $startedAt = now();
        $since = now()->subHours(24);
        $todayStart = today();

        Log::info('mail.send_digest.started', ['dry_run' => $dryRun]);

        // whereHas('roles', ...), non lo scope role() di Spatie: quello lancia
        // RoleDoesNotExist se la riga `roles` non esiste ancora (nessun cliente mai
        // registrato) — stesso idioma già in uso da AllCustomerTicketsQuery.
        $customers = User::query()
            ->whereHas('roles', fn (Builder $query): Builder => $query->where('name', UserRole::Customer->value))
            ->get();

        $examined = 0;
        $sent = 0;
        $skippedNoActivity = 0;
        $skippedAlreadySentToday = 0;

        foreach ($customers as $customer) {
            $examined++;

            if ($this->alreadySentToday($customer, $todayStart)) {
                $skippedAlreadySentToday++;

                continue;
            }

            $entries = BuildCustomerTicketDigest::run($customer, $since);

            if ($entries->isEmpty()) {
                $skippedNoActivity++;

                continue;
            }

            if ($dryRun) {
                $sent++;
                $this->line("[dry-run] digest per {$customer->email}: {$entries->count()} ticket con attività.");

                continue;
            }

            SendOutboundTicketMail::run(
                ticket: null,
                recipient: $customer,
                notificationType: NotificationType::MailDigest,
                subject: __('Your daily ticket activity digest', [], RecipientLocale::resolve($customer)),
                mailableClass: MailDigestMail::class,
                mailableFactory: fn (EmailMessage $outbound): MailDigestMail => new MailDigestMail($entries, $outbound),
            );
            $sent++;
            $this->info("cliente #{$customer->id}: digest inviato ({$entries->count()} ticket con attività).");
        }

        $durationMs = $startedAt->diffInMilliseconds(now());

        Log::info('mail.send_digest.finished', [
            'dry_run' => $dryRun,
            'examined' => $examined,
            'sent' => $sent,
            'skipped_no_activity' => $skippedNoActivity,
            'skipped_already_sent_today' => $skippedAlreadySentToday,
            'duration_ms' => $durationMs,
        ]);

        $this->info(sprintf(
            'Clienti esaminati: %d. Digest inviati: %d. Senza attività: %d. Già inviati oggi: %d.',
            $examined,
            $sent,
            $skippedNoActivity,
            $skippedAlreadySentToday,
        ));

        return self::SUCCESS;
    }

    private function alreadySentToday(User $customer, Carbon $todayStart): bool
    {
        return EmailMessage::query()
            ->where('user_id', $customer->id)
            ->where('mailable_class', MailDigestMail::class)
            ->where('created_at', '>=', $todayStart)
            ->exists();
    }
}
