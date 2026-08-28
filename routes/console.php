<?php

declare(strict_types=1);

use App\Console\Commands\MailFetchInboundCommand;
use App\Console\Commands\MailRetryFailedCommand;
use App\Console\Commands\MailSendDigestCommand;
use App\Console\Commands\ReportsGenerateMonthlyCommand;
use App\Console\Commands\TicketsArchiveScrumCommand;
use App\Console\Commands\TicketsAutoCloseReleasedCommand;
use App\Console\Commands\TicketsCloseScrumCommand;
use App\Console\Commands\TicketsProgressToTodoCommand;
use App\Console\Commands\TicketsRemindWaitingCommand;
use App\Console\Commands\TicketsRestoreWaitingCommand;
use App\Console\Commands\TimeTrackingAggregateDailyCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// §10.1/§10.2 del PRD: cadenza configurabile da config('mail_pipeline.fetch.schedule_cron')
// (mai un cron letterale qui), WithoutOverlapping obbligatorio, dietro il feature flag
// standard config('orchestrator.features.*') — disattivato di default, non serve affatto
// se in futuro si passa a un provider a webhook (US-301).
// Il timeout di esecuzione è imposto dal comando stesso via set_time_limit()
// (MailFetchInboundCommand::handle()): Illuminate\Console\Scheduling\Event non
// espone un ->timeout() per i comandi in-process di questo Laravel (13).
Schedule::command(MailFetchInboundCommand::class)
    ->cron((string) config('mail_pipeline.fetch.schedule_cron'))
    ->withoutOverlapping()
    ->when(fn (): bool => (bool) config('orchestrator.features.mail_fetch_inbound'));

// §7.5.2 E7 del PRD, US-316: gap del v1 da correggere — il comando esisteva ma non era
// mai schedulato. Cadenza configurabile da config('ticketing.waiting_reminder.schedule_cron'),
// dietro il feature flag già presente da Fase 0 (config('orchestrator.features.tickets_waiting_reminders')).
// Nessun ->timeout(): la query è locale al DB, nessuna chiamata di rete lunga come mail:fetch-inbound.
Schedule::command(TicketsRemindWaitingCommand::class)
    ->cron((string) config('ticketing.waiting_reminder.schedule_cron'))
    ->withoutOverlapping()
    ->when(fn (): bool => (bool) config('orchestrator.features.tickets_waiting_reminders'));

// §7.3.3 del PRD, US-325: reinvio automatico dei messaggi outbound `failed`
// senza intervento manuale da UI. Dietro il feature flag già presente da
// Fase 0 (config('orchestrator.features.mail_retry_failed')), disattivato di
// default. `mail:retry-failed` resta comunque richiamabile manualmente da CLI
// indipendentemente da questo flag.
Schedule::command(MailRetryFailedCommand::class)
    ->cron((string) config('mail_pipeline.retry.schedule_cron'))
    ->withoutOverlapping()
    ->when(fn (): bool => (bool) config('orchestrator.features.mail_retry_failed'));

// §6.5.3/§10.2 del PRD, US-410: genera i report attività del mese precedente per
// ogni owner attivo. Cadenza configurabile da config('reporting.monthly_schedule_cron')
// (mai un cron letterale qui), dietro il feature flag già presente da Fase 0
// (config('orchestrator.features.reports_monthly')), disattivato di default.
// `reports:generate-monthly` resta comunque richiamabile manualmente da CLI
// indipendentemente da questo flag.
Schedule::command(ReportsGenerateMonthlyCommand::class)
    ->cron((string) config('reporting.monthly_schedule_cron'))
    ->withoutOverlapping()
    ->when(fn (): bool => (bool) config('orchestrator.features.reports_monthly'));

// T3 (§6.1.5/§10.2 del PRD, US-610): riporta in "todo" i ticket rimasti "progress"
// a fine giornata. Cadenza configurabile da config('ticketing.progress_to_todo.schedule_cron'),
// dietro il feature flag già presente da Fase 0 (config('orchestrator.features.tickets_progress_to_todo')).
Schedule::command(TicketsProgressToTodoCommand::class)
    ->cron((string) config('ticketing.progress_to_todo.schedule_cron'))
    ->withoutOverlapping()
    ->when(fn (): bool => (bool) config('orchestrator.features.tickets_progress_to_todo'));

// T4 (§6.1.5/§10.2 del PRD, US-610): chiude in "done" i ticket "released" da almeno
// config('ticketing.auto_close_released.threshold_working_days') giorni lavorativi.
// Cadenza configurabile da config('ticketing.auto_close_released.schedule_cron'),
// dietro il feature flag già presente da Fase 0 (config('orchestrator.features.tickets_auto_close_released')).
Schedule::command(TicketsAutoCloseReleasedCommand::class)
    ->cron((string) config('ticketing.auto_close_released.schedule_cron'))
    ->withoutOverlapping()
    ->when(fn (): bool => (bool) config('orchestrator.features.tickets_auto_close_released'));

// T5 (§6.1.5/§10.2 del PRD, US-611): chiude in "done" i ticket "scrum" creati/aggiornati
// oggi. Cadenza configurabile da config('ticketing.close_scrum.schedule_cron'), dietro
// il feature flag già presente da Fase 0 (config('orchestrator.features.tickets_close_scrum')).
Schedule::command(TicketsCloseScrumCommand::class)
    ->cron((string) config('ticketing.close_scrum.schedule_cron'))
    ->withoutOverlapping()
    ->when(fn (): bool => (bool) config('orchestrator.features.tickets_close_scrum'));

// §10.2 del PRD (Q9), US-611: archivia i ticket "scrum" chiusi da abbastanza giorni
// (lettura conservativa del comportamento v1, non recuperabile con certezza dal dump —
// vedi config/ticketing.php). Cadenza configurabile da
// config('ticketing.archive_scrum.schedule_cron'), dietro il feature flag già presente
// da Fase 0 (config('orchestrator.features.tickets_archive_scrum')).
Schedule::command(TicketsArchiveScrumCommand::class)
    ->cron((string) config('ticketing.archive_scrum.schedule_cron'))
    ->withoutOverlapping()
    ->when(fn (): bool => (bool) config('orchestrator.features.tickets_archive_scrum'));

// T6 (§6.1.5/§10.2 del PRD, US-612): ripristina a `previous_status` i ticket "waiting"
// da almeno config('ticketing.restore_waiting.threshold_days') giorni DI CALENDARIO
// (esplicito nel PRD, a differenza di T3/T4). Cadenza configurabile da
// config('ticketing.restore_waiting.schedule_cron'), dietro il feature flag già
// presente da Fase 0 (config('orchestrator.features.tickets_restore_waiting')).
Schedule::command(TicketsRestoreWaitingCommand::class)
    ->cron((string) config('ticketing.restore_waiting.schedule_cron'))
    ->withoutOverlapping()
    ->when(fn (): bool => (bool) config('orchestrator.features.tickets_restore_waiting'));

// §10.2 del PRD, US-613: consolida ogni sera `ticket_work_logs` per i ticket con
// attività nella giornata, colmando il gap del v1 ("il job esiste ma non ha
// alcuna cadenza schedulata"). Cadenza configurabile da
// config('timetracking.aggregate_daily.schedule_cron'), dietro il feature flag
// già presente da Fase 0 (config('orchestrator.features.timetracking_aggregate')).
// `timetracking:recalculate` resta comunque richiamabile manualmente da CLI
// indipendentemente da questo flag.
Schedule::command(TimeTrackingAggregateDailyCommand::class)
    ->cron((string) config('timetracking.aggregate_daily.schedule_cron'))
    ->withoutOverlapping()
    ->when(fn (): bool => (bool) config('orchestrator.features.timetracking_aggregate'));

// E8 (§7.5.2/§10.2 del PRD, US-614): digest giornaliero di attività ticket per i
// clienti che lo hanno abilitato. Cadenza configurabile da
// config('mail_pipeline.digest.schedule_cron'), dietro il feature flag già
// presente da Fase 0 (config('orchestrator.features.mail_digest')).
Schedule::command(MailSendDigestCommand::class)
    ->cron((string) config('mail_pipeline.digest.schedule_cron'))
    ->withoutOverlapping()
    ->when(fn (): bool => (bool) config('orchestrator.features.mail_digest'));
