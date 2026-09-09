<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\CaiDirectory\Models\CaiSection;
use App\Domain\CaiDirectory\Support\CaiRuntsScraperClient;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Verifica leggera di presenza RUNTS (Fase 9): per ogni `CaiSection` con `tax_code`
 * valorizzato, chiama {@see CaiRuntsScraperClient::checkEntityExists()} (SOLO ricerca, mai lo
 * scrape completo di {@see \App\Domain\CaiDirectory\Actions\ScrapeCaiSection}/`cai:sync-national`)
 * e scrive `runts_registered`/`runts_presence_checked_at`. Un errore su una sezione (es. la nota
 * ambiguità di ricerca-timeout del servizio, design doc §3.3/§7) è loggato e non blocca le altre
 * — stesso principio già in uso da `cai:sync-national`.
 */
class CaiCheckRuntsPresenceCommand extends Command
{
    protected $signature = 'cai:check-runts-presence {--dry-run : Calcola gli esiti senza scriverli}';

    protected $description = 'Verifica se ogni sezione CAI con codice fiscale risulta registrata su RUNTS (solo ricerca, nessuno scrape completo)';

    public function __construct(private readonly CaiRuntsScraperClient $client)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $startedAt = now();

        Log::info('cai.check_runts_presence.started', ['dry_run' => $dryRun]);

        $sections = CaiSection::query()->whereNotNull('tax_code')->get();

        $examined = 0;
        $registered = 0;
        $notRegistered = 0;
        $errors = 0;

        foreach ($sections as $section) {
            $examined++;

            try {
                $found = $this->client->checkEntityExists((string) $section->tax_code);
                $found ? $registered++ : $notRegistered++;

                $this->line("- {$section->codice_cai}: ".($found ? 'presente su RUNTS' : 'non presente su RUNTS'));

                if (! $dryRun) {
                    $section->update([
                        'runts_registered' => $found,
                        'runts_presence_checked_at' => Carbon::now(),
                    ]);
                }
            } catch (Throwable $exception) {
                $errors++;
                Log::warning('cai.check_runts_presence.item_failed', ['codice_cai' => $section->codice_cai, 'error' => $exception->getMessage()]);
                $this->warn("- {$section->codice_cai}: verifica fallita — {$exception->getMessage()}");
            }
        }

        Log::info('cai.check_runts_presence.finished', [
            'dry_run' => $dryRun,
            'examined' => $examined,
            'registered' => $registered,
            'not_registered' => $notRegistered,
            'errors' => $errors,
            'duration_ms' => $startedAt->diffInMilliseconds(now()),
        ]);

        $this->info(sprintf(
            'Verifica presenza RUNTS completata: %d sezioni esaminate, %d presenti, %d non presenti, %d errori.',
            $examined,
            $registered,
            $notRegistered,
            $errors,
        ));

        return self::SUCCESS;
    }
}
