<?php

declare(strict_types=1);

namespace App\Import\Inspect\Analyzers;

final class TaggableAnalyzer
{
    /**
     * @param  array<int, string|null>  $taggableTypes
     * @return array{total:int,by_type:array<string,int>,non_documentation_count:int}
     */
    public static function analyze(array $taggableTypes, string $documentationType = 'App\\Models\\Documentation'): array
    {
        $byType = [];
        $nonDocumentation = 0;

        foreach ($taggableTypes as $type) {
            $key = $type ?? '(null)';
            $byType[$key] = ($byType[$key] ?? 0) + 1;

            if ($type !== $documentationType) {
                $nonDocumentation++;
            }
        }

        return [
            'total' => count($taggableTypes),
            'by_type' => $byType,
            'non_documentation_count' => $nonDocumentation,
        ];
    }
}
