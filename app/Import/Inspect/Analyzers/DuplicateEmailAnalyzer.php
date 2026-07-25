<?php

declare(strict_types=1);

namespace App\Import\Inspect\Analyzers;

final class DuplicateEmailAnalyzer
{
    /**
     * @param  array<int, array{id: int|string, email: string}>  $rows
     * @return array<int, array{email_lower:string,count:int,ids:array<int,int|string>,examples:array<int,string>}>
     */
    public static function analyze(array $rows): array
    {
        $groups = [];

        foreach ($rows as $row) {
            $lower = mb_strtolower($row['email']);
            $groups[$lower]['ids'][] = $row['id'];
            $groups[$lower]['examples'][] = $row['email'];
        }

        $duplicates = [];

        foreach ($groups as $lower => $group) {
            if (count($group['ids']) <= 1) {
                continue;
            }

            $duplicates[] = [
                'email_lower' => $lower,
                'count' => count($group['ids']),
                'ids' => $group['ids'],
                'examples' => array_values(array_unique($group['examples'])),
            ];
        }

        return $duplicates;
    }
}
