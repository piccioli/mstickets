<?php

declare(strict_types=1);

namespace App\Import\Stages\Concerns;

use Illuminate\Support\Str;

/**
 * Slug provvisorio ma unique-safe per gli stage che importano entità con
 * vincolo `unique` sullo slug (tags, documentation_pages) e nessuna colonna
 * v1 equivalente. Il ricalcolo definitivo/idempotente dello slug è delegato
 * allo stage `derive` (US-215): qui basta non violare il vincolo unique.
 */
trait GeneratesProvisionalSlugs
{
    /**
     * @param  array<int, string>  $existingSlugs  Slug già assegnati in v2 (aggiornato per riferimento con lo slug appena generato).
     */
    private function uniqueSlug(string $source, array &$existingSlugs): string
    {
        $base = Str::slug($source);
        $base = $base === '' ? 'n-a' : $base;
        $slug = $base;
        $suffix = 1;

        while (in_array($slug, $existingSlugs, true)) {
            $suffix++;
            $slug = "{$base}-{$suffix}";
        }

        $existingSlugs[] = $slug;

        return $slug;
    }
}
