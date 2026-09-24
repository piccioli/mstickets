<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Support;

use App\Domain\CaiDirectory\Models\CaiRuntsRegistration;
use App\Domain\CaiDirectory\Models\CaiSection;
use App\Filament\Resources\CaiSections\Schemas\CaiSectionInfolist;

/**
 * Confronto fra i campi sovrapposti di `CaiSection` (fonte: sito web della sezione,
 * scrappato, US-802) e ciascuna `CaiRuntsRegistration` collegata (fonte: RUNTS,
 * stesso datapack) — Fase 9, tab "Differenze" di {@see CaiSectionInfolist}.
 * Confronto puro sui dati GIÀ importati (nessun fetch esterno in tempo reale: design
 * deliberato, vedi CLAUDE.md/prd.json Fase 8, "scraper Python resta fuori scope").
 *
 * Confronto a stringa esatta dopo `trim()` (mai una normalizzazione più aggressiva,
 * es. case-folding): è uno strumento diagnostico per un revisore umano, non una
 * riconciliazione automatica — mostrare i due valori grezzi lascia all'utente
 * giudicare se una differenza (es. di maiuscole) è reale o solo di formattazione.
 */
final class CaiSectionRuntsComparator
{
    /**
     * @return list<array{registration_label: string, field: string, cai_value: ?string, runts_value: ?string, status: string}>
     */
    public static function compare(CaiSection $section): array
    {
        $rows = [];

        foreach ($section->runtsRegistrations as $registration) {
            $label = $registration->name ?? $registration->id_runts;

            foreach (self::fieldPairs($section, $registration) as $field => [$caiValue, $runtsValue]) {
                $normalizedCai = self::normalize($caiValue);
                $normalizedRunts = self::normalize($runtsValue);

                $rows[] = [
                    'registration_label' => $label,
                    'field' => $field,
                    'cai_value' => $normalizedCai,
                    'runts_value' => $normalizedRunts,
                    'status' => self::status($normalizedCai, $normalizedRunts),
                ];
            }
        }

        return $rows;
    }

    /**
     * @return array<string, array{0: ?string, 1: ?string}>
     */
    private static function fieldPairs(CaiSection $section, CaiRuntsRegistration $registration): array
    {
        return [
            'Denominazione' => [$section->name, $registration->name],
            'PEC' => [$section->pec, $registration->pec],
            'Sito web' => [$section->website, $registration->website],
            'Regione' => [$section->region, $registration->region],
            'Indirizzo' => [$section->address, self::runtsAddress($registration)],
        ];
    }

    private static function runtsAddress(CaiRuntsRegistration $registration): ?string
    {
        $streetLine = trim(trim((string) $registration->address).' '.trim((string) $registration->street_number));
        $cityLine = trim(
            trim((string) $registration->postal_code)
            .' '.trim((string) $registration->municipality)
            .(filled($registration->province) ? ' ('.$registration->province.')' : ''),
        );

        $parts = array_filter([$streetLine, $cityLine], fn (string $part): bool => $part !== '');

        return $parts === [] ? null : implode(', ', $parts);
    }

    private static function status(?string $normalizedCai, ?string $normalizedRunts): string
    {
        if ($normalizedCai === null && $normalizedRunts === null) {
            return 'N/D';
        }

        return $normalizedCai === $normalizedRunts ? 'Uguale' : 'Diverso';
    }

    private static function normalize(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
