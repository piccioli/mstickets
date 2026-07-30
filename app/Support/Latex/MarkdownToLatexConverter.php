<?php

declare(strict_types=1);

namespace App\Support\Latex;

final class MarkdownToLatexConverter
{
    public function convert(string $markdown): string
    {
        $blocks = preg_split('/\n{2,}/', trim($markdown)) ?: [];
        $out = [];

        foreach ($blocks as $block) {
            $out[] = $this->convertBlock($block);
        }

        return implode("\n\n", array_filter($out, static fn (string $b): bool => $b !== ''));
    }

    private function convertBlock(string $block): string
    {
        $lines = explode("\n", $block);
        $first = $lines[0];

        if (preg_match('/^### (.+)$/', $first, $m)) {
            return '\subsubsection{'.$this->inline($m[1]).'}';
        }

        if (preg_match('/^## (.+)$/', $first, $m)) {
            return '\subsection{'.$this->inline($m[1]).'}';
        }

        if (preg_match('/^# (.+)$/', $first, $m)) {
            return '\section{'.$this->inline($m[1]).'}';
        }

        if (trim($block) === '---') {
            return '\msseparatore';
        }

        if (str_starts_with(ltrim($first), '```')) {
            return $this->convertCodeFence($lines);
        }

        if (str_starts_with(ltrim($first), '> ')) {
            return $this->convertBlockquote($lines);
        }

        if (preg_match('/^- \[ \] /', $first)) {
            return $this->convertCheckboxList($lines);
        }

        if (preg_match('/^- /', $first)) {
            return $this->convertBulletList($lines);
        }

        if (preg_match('/^\d+\. /', $first)) {
            return $this->convertNumberedList($lines);
        }

        if (str_starts_with(trim($first), '|')) {
            return $this->convertTable($lines);
        }

        if (preg_match('/^\*\*[^*\n]+\*\*$/', $first) && count($lines) > 1) {
            $label = $this->inline($first);
            $rest = $this->inline(implode(' ', array_slice($lines, 1)));

            return $label.'\par'."\n".$rest;
        }

        return $this->inline(trim($block));
    }

    /**
     * @param  list<string>  $lines
     */
    private function convertCodeFence(array $lines): string
    {
        array_shift($lines); // riga di apertura ```lang
        if (trim(end($lines)) === '```') {
            array_pop($lines);
        }

        return "\\begin{lstlisting}\n".implode("\n", $lines)."\n\\end{lstlisting}";
    }

    /**
     * @param  list<string>  $lines
     */
    private function convertBlockquote(array $lines): string
    {
        $text = implode(' ', array_map(
            static fn (string $l): string => preg_replace('/^> ?/', '', $l),
            $lines,
        ));

        return "\\begin{quote}\n".$this->inline($text)."\n\\end{quote}";
    }

    /**
     * @param  list<string>  $lines
     */
    private function convertCheckboxList(array $lines): string
    {
        $items = array_map(
            fn (string $l): string => '\item $\square$ '.$this->inline(preg_replace('/^- \[ \] /', '', $l)),
            $lines,
        );

        return "\\begin{itemize}\n".implode("\n", $items)."\n\\end{itemize}";
    }

    /**
     * @param  list<string>  $lines
     */
    private function convertBulletList(array $lines): string
    {
        $items = [];
        foreach ($lines as $line) {
            $indented = (bool) preg_match('/^\s{2,}- /', $line);
            $text = preg_replace('/^\s*- /', '', $line);
            $items[] = ($indented ? '  ' : '').'\item '.$this->inline($text);
        }

        return "\\begin{itemize}\n".implode("\n", $items)."\n\\end{itemize}";
    }

    /**
     * @param  list<string>  $lines
     */
    private function convertNumberedList(array $lines): string
    {
        $items = array_map(
            fn (string $l): string => '\item '.$this->inline(preg_replace('/^\d+\. /', '', $l)),
            $lines,
        );

        return "\\begin{enumerate}\n".implode("\n", $items)."\n\\end{enumerate}";
    }

    /**
     * @param  list<string>  $lines
     */
    private function convertTable(array $lines): string
    {
        $rows = array_values(array_filter($lines, static fn (string $l): bool => trim($l) !== ''));
        $header = $this->splitRow($rows[0]);
        $separator = $this->splitRow($rows[1]);
        $bodyRows = array_slice($rows, 2);

        $colSpec = '@{}';
        foreach ($separator as $sep) {
            $colSpec .= str_ends_with(trim($sep), ':')
                ? '>{\raggedleft\arraybackslash}X'
                : 'X';
        }
        $colSpec .= '@{}';

        $headerCells = implode(' & ', array_map(
            fn (string $c): string => '\thc{'.$this->inline($c).'}',
            $header,
        ));

        $bodyLatex = implode("\n", array_map(
            fn (string $row): string => implode(' & ', array_map(
                fn (string $cell): string => $this->inline($cell),
                $this->splitRow($row),
            )).' \\\\',
            $bodyRows,
        ));

        return "\\mdtabella{{$colSpec}}{{$headerCells}}{%\n{$bodyLatex}\n}";
    }

    /**
     * @return list<string>
     */
    private function splitRow(string $row): array
    {
        $trimmed = trim($row);
        $trimmed = preg_replace('/^\|/', '', $trimmed);
        $trimmed = preg_replace('/\|$/', '', $trimmed);

        return array_map(trim(...), explode('|', $trimmed));
    }

    /**
     * Applica il markdown inline (code span, link, grassetto) a un frammento
     * di testo semplice. Ognuno dei tre viene estratto in un placeholder GIA'
     * come LaTeX finale (contenuto interno già passato da LatexEscaper), PRIMA
     * di escapare il testo circostante: se l'escape del testo semplice
     * avvenisse dopo aver inserito `\textbf{...}`/`\href{...}` letterali,
     * ri-escaperebbe i loro backslash. Estrarre-poi-escapare-il-resto-poi-
     * reinserire evita il problema per costruzione, per tutti e tre i casi
     * allo stesso modo (stesso principio già usato da LatexEscaper::MAP con
     * strtr, qui applicato a livello di markup invece che di singolo carattere).
     *
     * Un caso in più rispetto al singolo carattere: qui i tre costrutti sono
     * ANNIDABILI (es. `[`README.md`](README.md)`, un link il cui testo è a
     * sua volta un code span già estratto in un placeholder al passo
     * precedente — pattern reale in tutti gli 8 file di docs/collaudo/, vedi
     * il test "dropping internal markdown links"). Se il testo catturato da
     * link/grassetto contenesse quel marker di placeholder e venisse passato
     * "as-is" a LatexEscaper::escape() prima di essere reincapsulato in un
     * NUOVO placeholder, il marker resterebbe annidato dentro il *valore* del
     * nuovo placeholder — e lo strtr() finale, essendo una sostituzione in un
     * solo passaggio su $text, non lo risolverebbe mai (non rientra mai nei
     * valori appena sostituiti): il marker letterale (byte `\0`) finirebbe
     * nell'output. resolveInlineSegment() risolve quindi subito, al momento
     * della composizione, ogni marker già noto incorporato in un frammento
     * catturato, cosicché ogni valore finisca nel dizionario $placeholders
     * già completamente risolto (nessun marker annidato al suo interno) —
     * lo strtr() finale resta quindi corretto e sufficiente.
     */
    private function inline(string $text): string
    {
        $placeholders = [];
        $store = function (string $latex) use (&$placeholders): string {
            $key = "\0PH".count($placeholders)."\0";
            $placeholders[$key] = $latex;

            return $key;
        };

        $text = (string) preg_replace_callback(
            '/`([^`]+)`/',
            fn (array $m): string => $store('\texttt{'.LatexEscaper::escape($m[1]).'}'),
            $text,
        );

        $text = (string) preg_replace_callback(
            '/\[([^\]]+)\]\(([^)]+)\)/',
            function (array $m) use (&$placeholders, $store): string {
                $label = trim($m[1], '`');
                $url = $m[2];

                // Un link il cui testo è interamente un placeholder già
                // risolto (label originariamente tra backtick, es.
                // "[`x.md`](x.md)") riusa quel LaTeX finale direttamente,
                // invece di re-incapsularlo in un ulteriore \texttt{} —
                // eviterebbe solo un `\texttt{\texttt{...}}` ridondante, non
                // un bug, ma qui teniamo l'output pulito.
                $labelIsWholePlaceholder = array_key_exists($label, $placeholders);
                $labelLatex = $labelIsWholePlaceholder
                    ? $placeholders[$label]
                    : $this->resolveInlineSegment($label, $placeholders);

                if (str_ends_with($url, '.md') || str_contains($url, '.md#')) {
                    return $store($labelIsWholePlaceholder ? $labelLatex : '\texttt{'.$labelLatex.'}');
                }

                return $store('\href{'.$url.'}{'.$labelLatex.'}');
            },
            $text,
        );

        $text = (string) preg_replace_callback(
            '/\*\*([^*]+)\*\*/',
            fn (array $m): string => $store('\textbf{'.$this->resolveInlineSegment($m[1], $placeholders).'}'),
            $text,
        );

        $text = LatexEscaper::escape($text);

        return strtr($text, $placeholders);
    }

    /**
     * Risolve un frammento di testo catturato da un costrutto inline (label
     * di un link, contenuto di un grassetto) che può incorporare marker di
     * placeholder già prodotti da un passo precedente di `inline()`: divide
     * il frammento sui marker noti, sostituisce ciascun marker col proprio
     * LaTeX finale già risolto e applica LatexEscaper::escape() solo ai
     * segmenti di testo semplice residui. Vedi il commento su `inline()` per
     * il motivo per cui questa risoluzione deve avvenire ORA, alla
     * composizione del nuovo placeholder, e non può essere rimandata allo
     * strtr() finale.
     *
     * @param  array<string, string>  $placeholders
     */
    private function resolveInlineSegment(string $segment, array $placeholders): string
    {
        if ($placeholders === []) {
            return LatexEscaper::escape($segment);
        }

        $parts = preg_split('/(\x00PH\d+\x00)/', $segment, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [$segment];

        return implode('', array_map(
            fn (string $part): string => array_key_exists($part, $placeholders)
                ? $placeholders[$part]
                : LatexEscaper::escape($part),
            $parts,
        ));
    }
}
