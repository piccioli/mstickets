<?php

declare(strict_types=1);

namespace App\Import\Inspect\Analyzers;

final class CustomerRequestAnalyzer
{
    /**
     * @param  array<int, array{id: int|string, customer_request: string|null}>  $rows
     * @return array{non_empty_count:int,multi_message_count:int,samples:array<int,array{id:int|string,message_count:int,messages:array<int,string>}>}
     */
    public static function analyze(array $rows, int $sampleLimit = 5): array
    {
        $nonEmpty = 0;
        $multi = 0;
        $samples = [];

        foreach ($rows as $row) {
            $raw = $row['customer_request'];

            if ($raw === null || trim($raw) === '') {
                continue;
            }

            $nonEmpty++;
            $messages = self::parseMessages($raw);

            if (count($messages) > 1) {
                $multi++;

                if (count($samples) < $sampleLimit) {
                    $samples[] = [
                        'id' => $row['id'],
                        'message_count' => count($messages),
                        'messages' => $messages,
                    ];
                }
            }
        }

        return [
            'non_empty_count' => $nonEmpty,
            'multi_message_count' => $multi,
            'samples' => $samples,
        ];
    }

    /**
     * Splits an HTML customer_request blob into distinct messages: one per
     * <li> when the content is a list, otherwise the whole stripped text.
     *
     * @return array<int, string>
     */
    public static function parseMessages(string $html): array
    {
        if (str_contains($html, '<li')) {
            preg_match_all('/<li[^>]*>(.*?)<\/li>/is', $html, $matches);

            $messages = array_map(
                static fn (string $item): string => trim(html_entity_decode(strip_tags($item))),
                $matches[1],
            );

            $messages = array_values(array_filter(
                $messages,
                static fn (string $message): bool => $message !== '',
            ));

            if ($messages !== []) {
                return $messages;
            }
        }

        $plain = trim(html_entity_decode(strip_tags($html)));

        return $plain === '' ? [] : [$plain];
    }
}
