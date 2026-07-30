<?php

declare(strict_types=1);

namespace App\Support\Latex;

final class LatexEscaper
{
    /**
     * L'ordine di questa mappa non ha effetto sul risultato: strtr() con un
     * array fa una singola sostituzione simultanea (i valori sostituiti non
     * vengono ri-scansionati), a differenza di str_replace() concatenati che
     * ri-escaperebbero i backslash appena inseriti da un escape precedente.
     *
     * @var array<string, string>
     */
    private const MAP = [
        '\\' => '\textbackslash{}',
        '%' => '\%',
        '&' => '\&',
        '#' => '\#',
        '_' => '\_',
        '$' => '\$',
        '{' => '\{',
        '}' => '\}',
        '~' => '\textasciitilde{}',
        '^' => '\textasciicircum{}',
        '—' => '---',
        '–' => '--',
        '→' => '$\rightarrow$',
        '⇒' => '$\Rightarrow$',
        '×' => '$\times$',
        '…' => '\ldots{}',
    ];

    public static function escape(string $text): string
    {
        return strtr($text, self::MAP);
    }
}
