<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\CaiDirectory\Actions\FillCaiSectionFiscalCodesFromFallback;
use App\Domain\Identity\Models\User;
use Illuminate\Console\Command;

/**
 * Wrapper standalone su {@see FillCaiSectionFiscalCodesFromFallback}, per completare
 * CF/PIVA mancanti dal fallback manuale senza dover lanciare l'intero
 * `cai:sync-runts-all` (che in testa esegue già lo stesso fill, ma poi prosegue con la
 * sincronizzazione live RUNTS, un'operazione molto più lenta).
 */
final class CaiFillTaxCodesFromFallbackCommand extends Command
{
    protected $signature = 'cai:fill-tax-codes-from-fallback
        {--dry-run : Non scrive alcuna riga, solo conteggio di quanto verrebbe aggiornato}';

    protected $description = 'Completa CF/PIVA mancanti sulle sezioni CAI dal fallback manuale (resources/data/cai/tax-code-fallback.json)';

    public function __construct(private readonly FillCaiSectionFiscalCodesFromFallback $fillAction)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $result = $this->fillAction->run(User::system(), $dryRun);

        $this->info(sprintf(
            '%d sezioni esaminate, %d aggiornate%s, %d senza corrispondenza nel fallback.',
            $result->read,
            $result->updated,
            $dryRun ? ' (dry-run)' : '',
            $result->skipped,
        ));

        return self::SUCCESS;
    }
}
