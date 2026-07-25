<?php

declare(strict_types=1);

namespace App\Import\Inspect\Analyzers;

final class ChangesKeyAnalyzer
{
    /**
     * @param  array<int, string|null>  $rawValues  raw values of story_logs.changes
     * @return array{total:int,interpretable_count:int,non_interpretable_count:int,key_distribution:array<string,int>}
     */
    public static function analyze(array $rawValues): array
    {
        $interpretable = 0;
        $nonInterpretable = 0;
        $keyDistribution = [];

        foreach ($rawValues as $raw) {
            if ($raw === null || trim($raw) === '') {
                $nonInterpretable++;

                continue;
            }

            $decoded = json_decode($raw, true);

            if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
                $nonInterpretable++;

                continue;
            }

            $interpretable++;

            foreach (array_keys($decoded) as $key) {
                $keyDistribution[(string) $key] = ($keyDistribution[(string) $key] ?? 0) + 1;
            }
        }

        return [
            'total' => count($rawValues),
            'interpretable_count' => $interpretable,
            'non_interpretable_count' => $nonInterpretable,
            'key_distribution' => $keyDistribution,
        ];
    }
}
