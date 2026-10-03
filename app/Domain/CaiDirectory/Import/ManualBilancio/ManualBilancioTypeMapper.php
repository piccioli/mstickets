<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Import\ManualBilancio;

use App\Domain\CaiDirectory\Enums\CaiDocumentType;

/**
 * Etichetta del file normalizzato → tipo/titolo/anno del `CaiDocument`. Conservativo: solo i match esatti
 * (con `CaiDocumentType::getLabel()` o con i sinonimi qui sotto) ricevono un tipo specifico; tutto il resto
 * è `Altro` con il titolo originale.
 */
final class ManualBilancioTypeMapper
{
    /** @var array<string, CaiDocumentType> chiave già normalizzata ({@see self::normalize()}) */
    private const SYNONYMS = [
        'bilancio completo - mod a mod b e relazione di missione' => CaiDocumentType::CompletoABC,
    ];

    public static function map(string $label): ManualBilancioClassification
    {
        $title = trim($label);

        return new ManualBilancioClassification(
            type: self::resolveType($title),
            title: $title,
            year: str_contains($title, '2026') ? 2026 : 2025,
        );
    }

    private static function resolveType(string $label): CaiDocumentType
    {
        $key = self::normalize($label);

        foreach (CaiDocumentType::cases() as $case) {
            if ($case !== CaiDocumentType::Altro && self::normalize($case->getLabel()) === $key) {
                return $case;
            }
        }

        return self::SYNONYMS[$key] ?? CaiDocumentType::Altro;
    }

    private static function normalize(string $label): string
    {
        return mb_strtolower((string) preg_replace('/\s+/u', ' ', trim($label)));
    }
}
