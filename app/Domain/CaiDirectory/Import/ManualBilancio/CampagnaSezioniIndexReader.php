<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Import\ManualBilancio;

use InvalidArgumentException;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Reader\XLSX\Reader;

final class CampagnaSezioniIndexReader
{
    private const SHEET_NAME = 'Sezioni';

    /**
     * @return array<string, CampagnaSezioniRow> chiave = codice CAI a 7 cifre
     */
    public static function read(string $xlsxPath): array
    {
        if (! is_file($xlsxPath)) {
            throw new InvalidArgumentException("File indice della campagna non trovato: {$xlsxPath}");
        }

        $reader = new Reader;
        $reader->open($xlsxPath);

        try {
            foreach ($reader->getSheetIterator() as $sheet) {
                if ($sheet->getName() === self::SHEET_NAME) {
                    return self::readSheet($sheet->getRowIterator());
                }
            }
        } finally {
            $reader->close();
        }

        throw new InvalidArgumentException('Foglio "'.self::SHEET_NAME."\" assente nel file indice: {$xlsxPath}");
    }

    /**
     * @param  iterable<Row>  $rows
     * @return array<string, CampagnaSezioniRow>
     */
    private static function readSheet(iterable $rows): array
    {
        $columns = null;
        $result = [];

        foreach ($rows as $row) {
            $cells = array_map(
                static fn ($cell): string => self::stringify($cell->getValue()),
                $row->getCells(),
            );

            if ($columns === null) {
                $columns = ['links' => []];
                foreach ($cells as $index => $header) {
                    match ($header) {
                        'Nome' => $columns['name'] = $index,
                        'Regione' => $columns['region'] = $index,
                        'Bilanci' => $columns['stato'] = $index,
                        'Link al bilancio', 'Link al bilancio 2' => $columns['links'][] = $index,
                        default => null,
                    };
                }

                continue;
            }

            $codice = self::normalizeCode($cells[0] ?? '');
            if ($codice === null) {
                continue;
            }

            $stato = self::cell($cells, $columns['stato'] ?? null);

            $links = [];
            foreach ($columns['links'] as $index) {
                $link = self::cell($cells, $index);
                // Le celle-formula senza valore in cache (array formula) non sono link utilizzabili.
                if ($link !== '' && ! str_starts_with($link, '=')) {
                    $links[] = $link;
                }
            }

            $result[$codice] = new CampagnaSezioniRow(
                codiceCai: $codice,
                name: self::cell($cells, $columns['name'] ?? null),
                region: self::cell($cells, $columns['region'] ?? null),
                statoBilanci: $stato === '' ? null : $stato,
                links: $links,
            );
        }

        return $result;
    }

    /**
     * @param  array<int, string>  $cells
     */
    private static function cell(array $cells, ?int $index): string
    {
        return $index === null ? '' : ($cells[$index] ?? '');
    }

    private static function stringify(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_float($value) && floor($value) === $value) {
            return (string) (int) $value;
        }

        return trim(is_scalar($value) ? (string) $value : '');
    }

    private static function normalizeCode(string $raw): ?string
    {
        $raw = preg_replace('/\.0+$/', '', trim($raw)) ?? '';

        return preg_match('/^\d{7}$/', $raw) === 1 ? $raw : null;
    }
}
