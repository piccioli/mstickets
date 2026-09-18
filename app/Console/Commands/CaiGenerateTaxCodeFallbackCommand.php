<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\CaiDirectory\Import\CaiTaxCodeFallbackGenerator;
use Illuminate\Console\Command;

/**
 * Rigenera `resources/data/cai/tax-code-fallback.json` a partire dal foglio Excel
 * manuale "Sezioni CAI con CF e P.IVA" (fornito dal committente, mai nel datapack
 * RUNTS-CAI): fonte di fallback per `FillCaiSectionFiscalCodesFromFallback`, usata da
 * `cai:fill-tax-codes-from-fallback` e agganciata a inizio di `cai:sync-runts-all`.
 *
 * Comando volutamente da rilanciare a mano quando arriva un foglio aggiornato: il file
 * xlsx sorgente non fa parte del repo (dato manuale del committente, non un datapack
 * versionato), il JSON generato invece sì — va sempre committato dopo una rigenerazione.
 */
final class CaiGenerateTaxCodeFallbackCommand extends Command
{
    protected $signature = 'cai:generate-tax-code-fallback
        {--path= : Percorso assoluto del foglio Excel "Sezioni CAI con CF e P.IVA"}
        {--output= : Percorso di output del JSON (relativo alla root del progetto, o assoluto; default config("cai_directory.tax_code_fallback_path"))}';

    protected $description = 'Genera il JSON di fallback CF/PIVA per le sezioni CAI a partire da un foglio Excel manuale';

    public function __construct(private readonly CaiTaxCodeFallbackGenerator $generator)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $path = (string) $this->option('path');

        if ($path === '' || ! is_file($path)) {
            $this->error("File Excel non trovato: {$path}");

            return self::FAILURE;
        }

        $result = $this->generator->generate($path);

        $rawOutput = $this->option('output') !== null
            ? (string) $this->option('output')
            : (string) config('cai_directory.tax_code_fallback_path');
        $outputPath = $this->resolveAbsolutePath($rawOutput);
        $this->writeJson($outputPath, $result->entries);

        $this->info(sprintf('%d sezioni CF/PIVA scritte in %s.', count($result->entries), $outputPath));
        $this->info(sprintf('%d righe del foglio senza corrispondenza.', count($result->unmatchedNames)));

        foreach ($result->unmatchedNames as $name) {
            $this->warn("- senza corrispondenza: {$name}");
        }

        return self::SUCCESS;
    }

    /**
     * @param  array<string, array{name: string, tax_code: ?string, vat_number: ?string}>  $entries
     */
    private function writeJson(string $outputPath, array $entries): void
    {
        ksort($entries);

        if (! is_dir(dirname($outputPath))) {
            mkdir(dirname($outputPath), recursive: true);
        }

        file_put_contents(
            $outputPath,
            json_encode($entries, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n",
        );
    }

    private function resolveAbsolutePath(string $rawPath): string
    {
        return str_starts_with($rawPath, '/') ? $rawPath : base_path($rawPath);
    }
}
