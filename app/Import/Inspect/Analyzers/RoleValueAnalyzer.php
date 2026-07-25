<?php

declare(strict_types=1);

namespace App\Import\Inspect\Analyzers;

final class RoleValueAnalyzer
{
    /**
     * @param  array<int, string|null>  $rawValues  raw values of users.roles
     * @return array{total:int,null_or_empty_count:int,json_array_count:int,scalar_count:int,distinct_raw:array<string,int>,distinct_roles:array<string,int>}
     */
    public static function analyze(array $rawValues): array
    {
        $distinctRaw = [];
        $distinctRoles = [];
        $jsonArrayCount = 0;
        $scalarCount = 0;
        $nullOrEmptyCount = 0;

        foreach ($rawValues as $raw) {
            $rawKey = $raw === null || $raw === '' ? '(null)' : $raw;
            $distinctRaw[$rawKey] = ($distinctRaw[$rawKey] ?? 0) + 1;

            if ($raw === null || $raw === '') {
                $nullOrEmptyCount++;

                continue;
            }

            $decoded = json_decode($raw, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $jsonArrayCount++;

                foreach ($decoded as $role) {
                    $roleKey = is_string($role) ? $role : (string) json_encode($role);
                    $distinctRoles[$roleKey] = ($distinctRoles[$roleKey] ?? 0) + 1;
                }

                continue;
            }

            $scalarCount++;
            $distinctRoles[$raw] = ($distinctRoles[$raw] ?? 0) + 1;
        }

        return [
            'total' => count($rawValues),
            'null_or_empty_count' => $nullOrEmptyCount,
            'json_array_count' => $jsonArrayCount,
            'scalar_count' => $scalarCount,
            'distinct_raw' => $distinctRaw,
            'distinct_roles' => $distinctRoles,
        ];
    }
}
