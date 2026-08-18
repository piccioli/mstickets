<?php

declare(strict_types=1);

use App\Console\Commands\MailFetchInboundCommand;
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
