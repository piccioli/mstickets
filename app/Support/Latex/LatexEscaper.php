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
        '↔' => '$\leftrightarrow$',
        '⇒' => '$\Rightarrow$',
        '×' => '$\times$',
        '…' => '\ldots{}',
        // Bug trovato nel Task 6 durante lo sweep degli 8 file reali di
        // docs/collaudo/: questi quattro simboli, come →/⇒/×/… sopra, non
        // sono nel set di glifi coperto da T1 fontenc + inputenc utf8 e
        // pdflatex li rifiuta con "Unicode character ... not set up for use
        // with LaTeX" (fatale, nessun PDF prodotto) se lasciati passare
        // come UTF-8 letterale. ✓ compare nel corpus reale (02-fase-0.md,
        // esito di test riportato testualmente); −/≥/≤ non ancora osservati
        // a valle nel corpus prima che la compilazione fallisse su ✓, ma
        // sono nella stessa classe di simboli matematici Unicode-non-Latin1
        // e vengono mappati preventivamente per lo stesso motivo. \checkmark
        // richiede il pacchetto amssymb, già aggiunto a montagnaservizi.cls
        // in questo stesso task per lo stesso sweep (§ "Elenchi brandizzati"
        // usava già \blacktriangleright senza mai caricare amssymb).
        '✓' => '\checkmark{}',
        '−' => '$-$',
        '≥' => '$\geq$',
        '≤' => '$\leq$',
    ];

    public static function escape(string $text): string
    {
        return strtr($text, self::MAP);
    }
}
