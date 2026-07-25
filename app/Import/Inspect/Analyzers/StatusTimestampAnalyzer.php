<?php

declare(strict_types=1);

namespace App\Import\Inspect\Analyzers;

final class StatusTimestampAnalyzer
{
    /**
     * @param  array<int, array{id: int|string, status: string|null, timestamp: string|null}>  $rows
     * @return array{status:string,checked:int,missing_count:int,missing_ids:array<int,int|string>}
     */
    public static function analyze(array $rows, string $status, int $sampleLimit = 10): array
    {
        $checked = 0;
        $missingCount = 0;
        $missingIds = [];

        foreach ($rows as $row) {
            if ($row['status'] !== $status) {
                continue;
            }

            $checked++;

            if ($row['timestamp'] !== null) {
                continue;
            }

            $missingCount++;

            if (count($missingIds) < $sampleLimit) {
                $missingIds[] = $row['id'];
            }
        }

        return [
            'status' => $status,
            'checked' => $checked,
            'missing_count' => $missingCount,
            'missing_ids' => $missingIds,
        ];
    }
}
