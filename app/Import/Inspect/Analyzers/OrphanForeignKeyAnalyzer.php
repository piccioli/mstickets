<?php

declare(strict_types=1);

namespace App\Import\Inspect\Analyzers;

final class OrphanForeignKeyAnalyzer
{
    /**
     * Counts how many non-null values in $childValues have no matching id in
     * $parentIds. Generic on purpose: reused for every FK relation in scope.
     *
     * @param  array<int, int|string|null>  $childValues
     * @param  array<int, int|string>  $parentIds
     * @return array{checked:int,orphan_count:int,orphan_values:array<int,int|string>}
     */
    public static function analyze(array $childValues, array $parentIds, int $sampleLimit = 10): array
    {
        $parentSet = array_flip($parentIds);
        $checked = 0;
        $orphanCount = 0;
        $orphanSamples = [];

        foreach ($childValues as $value) {
            if ($value === null) {
                continue;
            }

            $checked++;

            if (array_key_exists($value, $parentSet)) {
                continue;
            }

            $orphanCount++;

            if (count($orphanSamples) < $sampleLimit) {
                $orphanSamples[] = $value;
            }
        }

        return [
            'checked' => $checked,
            'orphan_count' => $orphanCount,
            'orphan_values' => $orphanSamples,
        ];
    }
}
