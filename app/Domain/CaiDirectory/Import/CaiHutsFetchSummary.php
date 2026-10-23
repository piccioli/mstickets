<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Import;

/**
 * Riepilogo di `cai:fetch-huts` (US-1032): rifugi scaricati, con foto, collegati a una Sezione del datapack
 * (`branch_id` = `sezioni_cai.codice_cai`) e non collegati, raggruppati per `branch_id`.
 */
final readonly class CaiHutsFetchSummary
{
    /**
     * @param  array<string, int>  $unlinkedByBranch  `branch_id` (o "(vuoto)") → numero di rifugi
     */
    public function __construct(
        public int $total,
        public int $withPhotos,
        public int $linked,
        public array $unlinkedByBranch,
    ) {}

    /**
     * @param  array<int, string>  $details  `id_cai` → JSON grezzo
     * @param  list<string>  $sectionCodes
     */
    public static function of(array $details, array $sectionCodes): self
    {
        $sections = array_flip($sectionCodes);
        $withPhotos = 0;
        $linked = 0;
        $unlinked = [];

        foreach ($details as $payload) {
            $detail = json_decode($payload, true);
            $attributes = CaiHutFieldMapper::map(is_array($detail) ? $detail : []);

            if ($attributes['photos'] !== []) {
                $withPhotos++;
            }

            $branch = $attributes['branch_code'];

            if ($branch !== null && isset($sections[$branch])) {
                $linked++;

                continue;
            }

            $key = $branch ?? '(vuoto)';
            $unlinked[$key] = ($unlinked[$key] ?? 0) + 1;
        }

        ksort($unlinked);

        return new self(count($details), $withPhotos, $linked, $unlinked);
    }

    public function unlinked(): int
    {
        return array_sum($this->unlinkedByBranch);
    }
}
