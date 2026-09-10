<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\CaiDirectory\Actions\SyncCaiRuntsRegistration;
use App\Domain\CaiDirectory\Models\CaiSection;
use App\Filament\Pages\CustomerDashboard;
use Illuminate\Console\Command;
use Throwable;

/**
 * Sincronizza dal vivo i dati RUNTS di UNA sola sezione CAI da riga di comando (Fase 9):
 * stesso identico percorso del bottone "Sincronizza dati RUNTS" della dashboard cliente
 * ({@see CustomerDashboard::syncRuntsDataAction()}, entrambi chiamano
 * {@see SyncCaiRuntsRegistration}), utile per verificare/riprovare una sincronizzazione senza
 * passare dal browser — in particolare quando lo scrape supera il timeout del gateway web
 * (nginx `fastcgi_read_timeout`) prima ancora che PHP abbia finito di rispondere.
 */
class CaiSyncRuntsSectionCommand extends Command
{
    protected $signature = 'cai:sync-runts-section {codice_cai : Il codice CAI (chiave primaria) della sezione da sincronizzare}';

    protected $description = 'Sincronizza dal vivo i dati RUNTS di una sola sezione CAI (stesso percorso del bottone dashboard)';

    public function __construct(private readonly SyncCaiRuntsRegistration $syncer)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $codiceCai = (string) $this->argument('codice_cai');
        $section = CaiSection::query()->find($codiceCai);

        if ($section === null) {
            $this->error("Nessuna sezione CAI con codice \"{$codiceCai}\" trovata in anagrafica.");

            return self::FAILURE;
        }

        try {
            $result = $this->syncer->run($section);
        } catch (Throwable $exception) {
            $this->error("Sincronizzazione fallita — {$exception->getMessage()}");

            return self::FAILURE;
        }

        if (! $result->found) {
            $this->warn("Nessuna registrazione RUNTS trovata per il codice fiscale della sezione {$codiceCai}.");

            return self::SUCCESS;
        }

        $this->info(sprintf(
            'Sincronizzazione completata: registrazione "%s" (%s). %d bilancio/i in analisi.',
            $result->registration?->name,
            $result->registration?->id_runts,
            $result->queuedAnalysisCount,
        ));

        return self::SUCCESS;
    }
}
