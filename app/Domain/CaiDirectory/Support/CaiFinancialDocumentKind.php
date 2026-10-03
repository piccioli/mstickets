<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Support;

use App\Domain\CaiDirectory\Enums\CaiDocumentType;
use App\Domain\CaiDirectory\Models\CaiDocument;
use Illuminate\Database\Eloquent\Builder;

/**
 * Unico punto di verità per "questo `CaiDocument` è (o contiene) un conto economico / uno stato
 * patrimoniale": gli elenchi costanti sono applicati sia come scope SQL ({@see self::applyIncomeStatement()}/
 * {@see self::applyBalanceSheet()}) sia come test in PHP ({@see self::isIncomeStatement()}/
 * {@see self::isBalanceSheet()}), così i due modi non possono divergere. Un documento può essere entrambi.
 *
 * I documenti RUNTS (`document_type = bilancio_esercizio`) non hanno un tipo di `CaiDocumentType`: per loro
 * decide solo il titolo. Le parole chiave sono lowercase e ASCII (il confronto è `LOWER(title) LIKE`,
 * portabile sqlite+Postgres).
 */
final class CaiFinancialDocumentKind
{
    /** @var list<string> */
    public const INCOME_STATEMENT_TYPES = [
        CaiDocumentType::ModB->value,
        CaiDocumentType::ModAB->value,
        CaiDocumentType::CompletoABC->value,
        CaiDocumentType::BilancioEconomicoFinanziario->value,
        CaiDocumentType::ModD->value,
    ];

    /** @var list<string> */
    public const INCOME_STATEMENT_KEYWORDS = [
        'conto economico',
        'rendiconto gestionale',
        'rendiconto per cassa',
        'rendiconto finanziario',
        'bilancio consuntivo',
        'bilancio economico',
        'situazione economica',
        'rendiconto economico',
        'patrimoniale ed economico',
    ];

    /** @var list<string> */
    public const BALANCE_SHEET_TYPES = [
        CaiDocumentType::ModA->value,
        CaiDocumentType::ModAB->value,
        CaiDocumentType::CompletoABC->value,
    ];

    /** @var list<string> */
    public const BALANCE_SHEET_KEYWORDS = [
        'stato patrimoniale',
        'situazione patrimoniale',
        'bilancio patrimoniale',
        'patrimoniale',
    ];

    public static function isIncomeStatement(?string $documentType, ?string $title): bool
    {
        return self::matches($documentType, $title, self::INCOME_STATEMENT_TYPES, self::INCOME_STATEMENT_KEYWORDS);
    }

    public static function isBalanceSheet(?string $documentType, ?string $title): bool
    {
        return self::matches($documentType, $title, self::BALANCE_SHEET_TYPES, self::BALANCE_SHEET_KEYWORDS);
    }

    /**
     * @param  Builder<CaiDocument>  $query
     * @return Builder<CaiDocument>
     */
    public static function applyIncomeStatement(Builder $query): Builder
    {
        return self::apply($query, self::INCOME_STATEMENT_TYPES, self::INCOME_STATEMENT_KEYWORDS);
    }

    /**
     * @param  Builder<CaiDocument>  $query
     * @return Builder<CaiDocument>
     */
    public static function applyBalanceSheet(Builder $query): Builder
    {
        return self::apply($query, self::BALANCE_SHEET_TYPES, self::BALANCE_SHEET_KEYWORDS);
    }

    /**
     * @param  list<string>  $types
     * @param  list<string>  $keywords
     */
    private static function matches(?string $documentType, ?string $title, array $types, array $keywords): bool
    {
        if ($documentType !== null && in_array($documentType, $types, true)) {
            return true;
        }

        $haystack = mb_strtolower((string) $title);

        foreach ($keywords as $keyword) {
            if (str_contains($haystack, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  Builder<CaiDocument>  $query
     * @param  list<string>  $types
     * @param  list<string>  $keywords
     * @return Builder<CaiDocument>
     */
    private static function apply(Builder $query, array $types, array $keywords): Builder
    {
        return $query->where(function (Builder $q) use ($types, $keywords): void {
            $q->whereIn('document_type', $types);

            foreach ($keywords as $keyword) {
                $q->orWhereRaw('LOWER(title) LIKE ?', ['%'.$keyword.'%']);
            }
        });
    }
}
