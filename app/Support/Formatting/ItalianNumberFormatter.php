<?php

declare(strict_types=1);

namespace App\Support\Formatting;

/**
 * Formattazione all'italiana dei numeri mostrati nei badge metrica (Fase 10.1): punto delle migliaia,
 * virgola decimale, euro interi con il simbolo dopo, segno meno tipografico (U+2212). Lo zero resta `0`:
 * il "Non disponibile" di un dato `null` è compito della vista, non di questa classe.
 */
final class ItalianNumberFormatter
{
    public const string MINUS = "\u{2212}";

    /** `1248` → `1.248`, `-1248` → `−1.248`. */
    public static function integer(int|float $value): string
    {
        return self::decimal($value, 0);
    }

    /** `412.63` con 1 decimale → `412,6`. */
    public static function decimal(int|float $value, int $decimals): string
    {
        $rounded = round((float) $value, $decimals);
        $formatted = number_format(abs($rounded), $decimals, ',', '.');

        return $rounded < 0 ? self::MINUS.$formatted : $formatted;
    }

    /** `412.63` → `412,6 km`. */
    public static function kilometers(int|float $value): string
    {
        return self::decimal($value, 1).' km';
    }

    /** Euro interi arrotondati: `186420.4` → `186.420 €`, `-12340` → `−12.340 €`. */
    public static function euro(int|float $value): string
    {
        return self::integer($value).' €';
    }

    /** Euro con segno esplicito anche se positivo: `+14.110 €`, `−12.340 €`; lo zero resta `0 €`. */
    public static function signedEuro(int|float $value): string
    {
        $formatted = self::euro($value);

        return round((float) $value) > 0 ? '+'.$formatted : $formatted;
    }
}
