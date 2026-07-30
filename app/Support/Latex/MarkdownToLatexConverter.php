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
            return $this->convertCodeFenceBlock($lines);
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
            // Il "resto" del blocco non è sempre un paragrafo semplice: nei
            // file reali (es. docs/collaudo/02-fase-0.md) un'etichetta come
            // "**Riferimenti**"/"**Prerequisiti**"/"**Evidenze da
            // acquisire**" è spesso seguita, SENZA riga vuota, da un elenco
            // puntato (o, in altri punti, da una tabella). Trattare
            // incondizionatamente il resto come testo piano lo appiattirebbe
            // in un unico paragrafo con i trattini "- " residui come testo
            // letterale, perdendo la struttura dell'elenco. Delegare a
            // convertBlock() ricorsivamente sul resto fa risolvere il tipo di
            // blocco corretto (lista, tabella, o paragrafo semplice) con la
            // stessa logica di dispatch già usata per l'intero documento.
            $label = $this->inline($first);
            $rest = $this->convertBlock(implode("\n", array_slice($lines, 1)));

            return $label.'\par'."\n".$rest;
        }

        // Un paragrafo può essere seguito, SENZA riga vuota di separazione,
        // da un elenco/tabella/blocco di codice che lo "interrompe" (pattern
        // CommonMark legittimo: `preg_split('/\n{2,}/', ...)` a monte non
        // spezza questo caso in due blocchi separati, perché non c'è alcuna
        // riga vuota tra le due parti). Occorrenze reali confermate in
        // docs/collaudo/00-istruzioni-generali.md ("... 16 nuovi test.\n-
        // **Data di stesura**...") e 03-fase-1.md ("Note aggiuntive sulla
        // matrice...:\n- \"Completato\"...", "Tre ticket seed...:\n- Indice
        // `i=10`..."): senza questo scorrimento, l'INTERO blocco (paragrafo +
        // righe "- ..." dell'elenco) cadeva nel fallback sottostante e
        // veniva reso come unico paragrafo piano, con i trattini dell'elenco
        // lasciati come testo letterale invece che come `\item`. Si cerca la
        // prima riga (dopo la prima) che avvia un altro tipo di blocco, si
        // rende la parte precedente come paragrafo e si delega il resto a
        // una nuova chiamata di convertBlock() (che risolve da sé il tipo
        // corretto, stesso principio già usato per il caso "**Etichetta**").
        foreach ($lines as $index => $line) {
            if ($index === 0) {
                continue;
            }

            if ($this->startsNewBlockType($line)) {
                $paragraph = $this->inline(trim(implode("\n", array_slice($lines, 0, $index))));
                $rest = $this->convertBlock(implode("\n", array_slice($lines, $index)));

                return $paragraph."\n\n".$rest;
            }
        }

        return $this->inline(trim($block));
    }

    private function startsNewBlockType(string $line): bool
    {
        return (bool) preg_match('/^(#{1,6} |```|> |- |\d+\. |\|)/', $line);
    }

    /**
     * Individua il fence di chiusura (```) all'interno di $lines invece di
     * assumere che sia sempre l'ultima riga del blocco: `preg_split('/\n{2,}/',
     * ...)` (a monte, in convert()) spezza i blocchi solo sulle righe vuote,
     * quindi un blocco di codice seguito, SENZA riga vuota, da un paragrafo
     * (pattern reale in docs/collaudo/02-fase-0.md, es. "**Dati di
     * test**\n```sql\n...\n```\nNessun valore...") include quel paragrafo
     * come righe finali dello STESSO blocco. `convertCodeFence()` (assumeva
     * il fence di chiusura = ultima riga) non lo trovava più, lasciava il
     * fence letterale nel listato e fondeva il paragrafo successivo come
     * contenuto di codice. Qui si cerca il fence di chiusura per indice, si
     * passa a `convertCodeFence()` solo le righe del blocco di codice vero
     * (apertura...chiusura) e si delega ricorsivamente a `convertBlock()` la
     * parte residua — stesso principio già usato per l'interruzione di
     * paragrafo da lista/tabella più sotto in questo file.
     *
     * @param  list<string>  $lines
     */
    private function convertCodeFenceBlock(array $lines): string
    {
        $closingIndex = null;
        foreach ($lines as $index => $line) {
            if ($index === 0) {
                continue;
            }

            if (trim($line) === '```') {
                $closingIndex = $index;

                break;
            }
        }

        if ($closingIndex === null) {
            return $this->convertCodeFence($lines);
        }

        $listing = $this->convertCodeFence(array_slice($lines, 0, $closingIndex + 1));
        $trailing = array_slice($lines, $closingIndex + 1);

        if (trim(implode('', $trailing)) === '') {
            return $listing;
        }

        return $listing."\n\n".$this->convertBlock(implode("\n", $trailing));
    }

    /**
     * @param  list<string>  $lines  blocco completo del fence: riga di
     *                               apertura ```lang, contenuto, riga di
     *                               chiusura ``` (già isolato da
     *                               convertCodeFenceBlock())
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
            $this->groupListLines($lines, '/^- \[ \] /'),
        );

        return "\\begin{itemize}\n".implode("\n", $items)."\n\\end{itemize}";
    }

    /**
     * @param  list<string>  $lines
     */
    private function convertBulletList(array $lines): string
    {
        $items = array_map(
            function (string $line): string {
                $indented = (bool) preg_match('/^\s{2,}- /', $line);
                $text = preg_replace('/^\s*- /', '', $line);

                return ($indented ? '  ' : '').'\item '.$this->inline($text);
            },
            $this->groupListLines($lines, '/^\s*- /'),
        );

        return "\\begin{itemize}\n".implode("\n", $items)."\n\\end{itemize}";
    }

    /**
     * @param  list<string>  $lines
     */
    private function convertNumberedList(array $lines): string
    {
        $items = array_map(
            fn (string $l): string => '\item '.$this->inline(preg_replace('/^\d+\. /', '', $l)),
            $this->groupListLines($lines, '/^\d+\. /'),
        );

        return "\\begin{enumerate}\n".implode("\n", $items)."\n\\end{enumerate}";
    }

    /**
     * Raggruppa le righe fisiche di un elenco in elementi logici: una riga
     * che soddisfa $startPattern apre un nuovo elemento, qualunque altra
     * riga non vuota si accoda come continuazione con uno spazio. Pattern
     * reale e frequente in docs/collaudo/02-fase-0.md e 03-fase-1.md (70-100
     * occorrenze ciascuno): un singolo item lungo va a capo manualmente su
     * più righe fisiche indentate, talvolta con un code span che attraversa
     * l'a-capo (es. `` `docker compose exec db\n  psql ...` ``) — senza
     * questo raggruppamento ogni riga di continuazione diventerebbe un
     * `\item` separato e spurio, con un backtick di apertura mai chiuso.
     *
     * @param  list<string>  $lines
     * @return list<string> una riga per elemento logico (start-marker
     *                      incluso, invariato), con le eventuali
     *                      continuazioni già accodate
     */
    private function groupListLines(array $lines, string $startPattern): array
    {
        $items = [];
        foreach ($lines as $line) {
            if (preg_match($startPattern, $line)) {
                $items[] = $line;

                continue;
            }

            $continuation = trim($line);
            if ($continuation === '' || $items === []) {
                continue;
            }

            $items[array_key_last($items)] .= ' '.$continuation;
        }

        return $items;
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

        $expectedCells = count($header);
        $bodyLatex = implode("\n", array_map(
            fn (string $row): string => implode(' & ', array_map(
                fn (string $cell): string => $this->inline($cell),
                $this->normalizeRowCells($this->splitRow($row), $expectedCells),
            )).' \\\\',
            $bodyRows,
        ));

        return "\\mdtabella{{$colSpec}}{{$headerCells}}{%\n{$bodyLatex}\n}";
    }

    /**
     * Adatta una riga già spezzata da splitRow() al numero di colonne
     * dichiarato dall'header, riparando righe "sfilacciate" invece di far
     * fallire l'intera compilazione. Occorrenza reale in
     * docs/collaudo/03-fase-1.md (F1-27, tabella "Procedura di esecuzione"):
     * una cella di risultato atteso contiene un `|` letterale NON
     * sfuggito ("pagina di errore \"403 | Questa azione non è
     * autorizzata.\""), che splitRow() (split ingenuo su `|`, stesso
     * comportamento richiesto dalla spec GFM per le tabelle: un `|` in una
     * cella andrebbe scritto `\|`) spezza in due celle spurie, producendo
     * una riga a 5 colonne contro un header a 4. Il vecchio renderer HTML
     * (dompdf/CommonMark) tollerava silenziosamente questo contenuto
     * "sporco" (un `<td>` di troppo, riga larga ma nessun crash); `\mdtabella` invece è
     * una tabularx a preambolo di colonne fisso: un `&` di troppo o
     * mancante è un errore FATALE di pdflatex ("Extra alignment tab has
     * been changed to \cr", nessun PDF prodotto per l'intero documento
     * combinato). Le celle in eccesso vengono riunite nell'ultima cella
     * attesa (rijoin con " | ", per restituire il testo originale il più
     * fedelmente possibile); le celle mancanti (riga troppo corta, mai
     * osservato nel corpus reale ma difensivo per lo stesso principio)
     * vengono riempite con celle vuote.
     *
     * @param  list<string>  $cells
     * @return list<string>
     */
    private function normalizeRowCells(array $cells, int $expectedCount): array
    {
        if (count($cells) === $expectedCount) {
            return $cells;
        }

        if (count($cells) < $expectedCount) {
            return array_pad($cells, $expectedCount, '');
        }

        $head = array_slice($cells, 0, $expectedCount - 1);
        $tail = implode(' | ', array_slice($cells, $expectedCount - 1));

        return [...$head, $tail];
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
