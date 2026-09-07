<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Actions;

use App\Domain\CaiDirectory\Import\CaiApiSectionNormalizer;
use App\Domain\CaiDirectory\Import\CaiImportTableResult;
use App\Domain\CaiDirectory\Support\CaiApiClient;
use App\Filament\Pages\CustomerDashboard;
use Illuminate\Support\Collection;
use RuntimeException;

/**
 * Sincronizza dal vivo UNA sola sezione CAI (Fase 9, storia 1, design doc §3.4):
 * entry point del bottone "Sincronizza dati CAI" della dashboard cliente
 * ({@see CustomerDashboard::syncCaiDataAction()}). Recupera
 * l'intero elenco nazionale dall'API CAI (l'endpoint non supporta un filtro per
 * singola sezione — verificato sul prototipo Python, design doc §2) e ne isola la
 * sola sezione richiesta prima di delegare la sincronizzazione vera e propria a
 * {@see SyncCaiSectionAndSubsections}.
 */
final class ScrapeCaiSection
{
    public function __construct(
        private readonly CaiApiClient $apiClient,
        private readonly SyncCaiSectionAndSubsections $syncer,
    ) {}

    /**
     * @return array{cai_sections: CaiImportTableResult, cai_subsections: CaiImportTableResult}
     */
    public function run(string $codiceCai): array
    {
        $rawSections = $this->apiClient->fetchNationalSections();

        $rawSection = Collection::make($rawSections)
            ->first(fn (array $raw): bool => ($raw['code'] ?? null) === $codiceCai);

        if ($rawSection === null) {
            throw new RuntimeException("Nessuna sezione CAI con codice \"{$codiceCai}\" trovata sull'API CAI.");
        }

        $normalized = CaiApiSectionNormalizer::normalizeSection($rawSection);

        return $this->syncer->run($normalized);
    }
}
