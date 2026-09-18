<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Support;

/**
 * Legge `resources/data/cai/tax-code-fallback.json` (generato da
 * `cai:generate-tax-code-fallback`, committato nel repo) — usato da
 * `FillCaiSectionFiscalCodesFromFallback` per completare CF/PIVA mancanti in
 * `cai_sections`. File assente (mai generato in questo ambiente) trattato come dataset
 * vuoto, mai un errore: il fallback resta best-effort.
 */
final class CaiTaxCodeFallbackRepository
{
    /**
     * @var array<string, array{name: string, tax_code: ?string, vat_number: ?string}>|null
     */
    private ?array $entries = null;

    public function __construct(private readonly string $path = '') {}

    private function resolvedPath(): string
    {
        return $this->path !== '' ? $this->path : (string) config('cai_directory.tax_code_fallback_path');
    }

    /**
     * @return array{name: string, tax_code: ?string, vat_number: ?string}|null
     */
    public function forCode(string $codiceCai): ?array
    {
        return $this->entries()[$codiceCai] ?? null;
    }

    /**
     * @return array<string, array{name: string, tax_code: ?string, vat_number: ?string}>
     */
    private function entries(): array
    {
        if ($this->entries !== null) {
            return $this->entries;
        }

        $path = $this->resolvedPath();

        if (! is_file($path)) {
            return $this->entries = [];
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        return $this->entries = is_array($decoded) ? $decoded : [];
    }
}
