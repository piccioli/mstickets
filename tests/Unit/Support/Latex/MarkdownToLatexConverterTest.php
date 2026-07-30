<?php

declare(strict_types=1);

use App\Support\Latex\MarkdownToLatexConverter;

it('converts headers to section commands', function () {
    $out = (new MarkdownToLatexConverter)->convert(
        "# Titolo\n\n## Sotto\n\n### Sottosotto\n"
    );

    expect($out)->toContain('\section{Titolo}');
    expect($out)->toContain('\subsection{Sotto}');
    expect($out)->toContain('\subsubsection{Sottosotto}');
});

it('converts a plain paragraph and escapes latex special characters', function () {
    $out = (new MarkdownToLatexConverter)->convert("Il 100% dei test_case passa & funziona.\n");

    expect($out)->toContain('Il 100\% dei test\_case passa \& funziona.');
});

it('converts bold and inline code within a paragraph', function () {
    $out = (new MarkdownToLatexConverter)->convert(
        "**Obiettivo**\nVerifica che `ticket.update.*` funzioni.\n"
    );

    expect($out)->toContain('\textbf{Obiettivo}');
    expect($out)->toContain('\texttt{ticket.update.*}');
});

it('converts a bullet list', function () {
    $out = (new MarkdownToLatexConverter)->convert(
        "- primo elemento\n- secondo elemento\n"
    );

    expect($out)->toContain('\begin{itemize}');
    expect($out)->toContain('\item primo elemento');
    expect($out)->toContain('\item secondo elemento');
    expect($out)->toContain('\end{itemize}');
});

it('converts a numbered list', function () {
    $out = (new MarkdownToLatexConverter)->convert(
        "1. primo\n2. secondo\n"
    );

    expect($out)->toContain('\begin{enumerate}');
    expect($out)->toContain('\item primo');
    expect($out)->toContain('\end{enumerate}');
});

it('converts a checkbox list using square symbols', function () {
    $out = (new MarkdownToLatexConverter)->convert(
        "- [ ] Collaudo superato\n- [ ] Collaudo non superato\n"
    );

    expect($out)->toContain('$\square$ Collaudo superato');
    expect($out)->toContain('$\square$ Collaudo non superato');
});

it('converts a blockquote to a quote environment, dropping internal markdown links', function () {
    $out = (new MarkdownToLatexConverter)->convert(
        "> Torna a [`README.md`](README.md) · vedi [`00-istruzioni.md`](00-istruzioni.md)\n"
    );

    expect($out)->toContain('\begin{quote}');
    expect($out)->toContain('\texttt{README.md}');
    expect($out)->not->toContain('\href');
});

it('converts an external link to href', function () {
    $out = (new MarkdownToLatexConverter)->convert(
        "Vai su [Montagna Servizi](https://montagnaservizi.com) per informazioni.\n"
    );

    expect($out)->toContain('\href{https://montagnaservizi.com}{Montagna Servizi}');
});

it('converts a horizontal rule to msseparatore', function () {
    $out = (new MarkdownToLatexConverter)->convert("Testo prima\n\n---\n\nTesto dopo\n");

    expect($out)->toContain('\msseparatore');
});

it('converts a fenced code block to lstlisting', function () {
    $out = (new MarkdownToLatexConverter)->convert(
        "```php\nRoute::get('/x', fn () => 1);\n```\n"
    );

    expect($out)->toContain('\begin{lstlisting}');
    expect($out)->toContain("Route::get('/x', fn () => 1);");
    expect($out)->toContain('\end{lstlisting}');
});

it('converts a pipe table into a mdtabella with escaped cell content', function () {
    $markdown = <<<'MD'
    | Passo | Azione | Risultato |
    |------:|--------|-----------|
    | 1 | Fai `x_y` | 100% ok |
    | 2 | Fai altro | 50% ok |
    MD;

    $out = (new MarkdownToLatexConverter)->convert($markdown);

    expect($out)->toContain('\mdtabella{');
    expect($out)->toContain('\thc{Passo}');
    expect($out)->toContain('\thc{Azione}');
    expect($out)->toContain('\thc{Risultato}');
    expect($out)->toContain('\texttt{x\_y}');
    expect($out)->toContain('100\% ok');
});

it('merges an unescaped literal pipe inside the last cell back into that cell instead of producing a ragged row', function () {
    // Bug reale trovato nel sweep di Task 6 (Step 6): docs/collaudo/03-fase-1.md
    // (F1-27) ha una cella di risultato atteso con un "|" letterale NON
    // sfuggito ("pagina di errore \"403 | Questa azione non è
    // autorizzata.\""). splitRow() (split ingenuo su "|") produceva quindi
    // una riga a 5 colonne contro un header a 4 — \mdtabella (tabularx a
    // preambolo fisso) falliva la compilazione dell'INTERO documento con
    // "Extra alignment tab has been changed to \cr" (fatale, nessun PDF).
    $markdown = <<<'MD'
    | Passo | Azione | Risultato |
    |------:|--------|-----------|
    | 1 | naviga | pagina di errore "403 | non autorizzato" |
    MD;

    $out = (new MarkdownToLatexConverter)->convert($markdown);

    expect($out)->toContain('pagina di errore "403 | non autorizzato"');
    // Tre "&" (2 separatori di colonna nell'header + 2 nel corpo, una riga
    // a 3 colonne): se la riga fosse rimasta a 4 celle spurie, questo
    // conteggio fallirebbe silenziosamente in un unit test (a differenza
    // del crash reale, che si manifesta solo in pdflatex).
    expect(substr_count($out, ' & '))->toBe(4);
});

it('right-aligns a table column marked with --: in the header separator', function () {
    $markdown = <<<'MD'
    | A | B |
    |---|--:|
    | x | 1 |
    MD;

    $out = (new MarkdownToLatexConverter)->convert($markdown);

    expect($out)->toMatch('/\{@\{\}.*>\{\\\\raggedleft\\\\arraybackslash\}.*@\{\}\}/');
});

it('breaks a bold-only label line from the text that follows onto its own paragraph', function () {
    // Pattern usato in tutti i casi di test di docs/collaudo/02-fase-0.md e
    // 03-fase-1.md: "**Obiettivo**\nTesto..." con un SOLO a-capo (non riga
    // vuota). Senza un \par esplicito qui, LaTeX tratterebbe l'a-capo come
    // uno spazio (stesso comportamento CommonMark "soft break"), fondendo
    // visivamente l'etichetta in grassetto col testo — il problema che il
    // vecchio helper CommonMark-specifico separateBoldLabelsIntoOwnParagraph
    // (rimosso nel Task 6) risolveva per la pipeline dompdf.
    $out = (new MarkdownToLatexConverter)->convert("**Obiettivo**\nTesto che segue subito dopo.");

    expect($out)->toBe("\\textbf{Obiettivo}\\par\nTesto che segue subito dopo.");
});

it('does not insert a paragraph break when bold text is inline within a sentence', function () {
    $out = (new MarkdownToLatexConverter)->convert('Il **grassetto** qui è inline, non un\'etichetta.');

    expect($out)->not->toContain('\par');
});

it('does not corrupt bold or link markup when the surrounding text needs escaping', function () {
    $out = (new MarkdownToLatexConverter)->convert(
        '100% **importante** & vero, vedi [qui](https://x.test/a&b)'
    );

    expect($out)->toBe(
        '100\% \textbf{importante} \& vero, vedi \href{https://x.test/a&b}{qui}'
    );
});

it('joins a hard-wrapped bullet item spanning multiple physical lines, including a code span across the wrap', function () {
    // Pattern reale e frequente (70-100 occorrenze per file) in
    // docs/collaudo/02-fase-0.md e 03-fase-1.md: un singolo item lungo va a
    // capo manualmente su più righe fisiche indentate, qui con un code span
    // che attraversa l'a-capo. Senza raggruppamento, ogni riga di
    // continuazione diventerebbe un \item separato e spurio, con un
    // backtick di apertura mai chiuso.
    $out = (new MarkdownToLatexConverter)->convert(
        "- Accesso al database (locale: `docker compose exec db\n".
        "  psql -U utente database`; per l'ambiente UAT vedi le note).\n".
        '- Secondo elemento, su una sola riga.'
    );

    expect($out)->toBe(
        "\\begin{itemize}\n".
        "\\item Accesso al database (locale: \\texttt{docker compose exec db psql -U utente database}; per l'ambiente UAT vedi le note).\n".
        '\item Secondo elemento, su una sola riga.'."\n".
        '\end{itemize}'
    );
});

it('converts a bullet list that interrupts a paragraph without a blank line separator', function () {
    // Bug reale trovato nel sweep di Task 6 (Step 6) passando gli 8 file
    // reali di docs/collaudo/ attraverso il convertitore:
    // docs/collaudo/00-istruzioni-generali.md ha un paragrafo che finisce
    // con "... 16 nuovi test." seguito, SENZA riga vuota, da tre righe "- ".
    // Poiché il blocco (delimitato solo da righe vuote) inizia con testo
    // piano, prima di questo fix l'INTERO blocco — paragrafo incluso i
    // trattini dell'elenco — veniva reso come un unico paragrafo, lasciando
    // "- **Data di stesura**..." come testo letterale invece che come
    // \item di un \begin{itemize}.
    $out = (new MarkdownToLatexConverter)->convert(
        "Primo paragrafo che introduce un elenco:\n".
        "- primo elemento\n".
        '- secondo elemento'
    );

    expect($out)->toBe(
        "Primo paragrafo che introduce un elenco:\n\n".
        "\\begin{itemize}\n".
        "\\item primo elemento\n".
        '\item secondo elemento'."\n".
        '\end{itemize}'
    );
});

it('keeps a bullet list intact when it immediately follows a bold-only label line', function () {
    // Pattern reale in docs/collaudo/02-fase-0.md: "**Riferimenti**" e
    // "**Prerequisiti**" sono seguiti, SENZA riga vuota, da un elenco
    // puntato. Il resto del blocco non è sempre un paragrafo semplice: va
    // fatto risolvere di nuovo come blocco (lista/tabella/paragrafo), non
    // appiattito in testo piano con i trattini letterali.
    $out = (new MarkdownToLatexConverter)->convert(
        "**Riferimenti**\n- primo riferimento\n- secondo riferimento"
    );

    expect($out)->toBe(
        "\\textbf{Riferimenti}\\par\n\\begin{itemize}\n\\item primo riferimento\n\\item secondo riferimento\n\\end{itemize}"
    );
});

it('closes a two-line code fence even when a paragraph follows without a blank line', function () {
    // Bug reale trovato inspezionando il PDF combinato di 488 pagine
    // generato da collaudo:generate (task-10-report.md, "Due difetti reali
    // trovati e riprodotti", difetto 1): 4 occorrenze in
    // docs/collaudo/02-fase-0.md, tutte con la stessa forma — un blocco
    // "**Dati di test**" seguito, SENZA riga vuota, da un fence ```sql di
    // 2 righe di contenuto, seguito a sua volta, ANCORA senza riga vuota,
    // da un paragrafo di chiusura ("Nessun valore per ... (NULL)."). Poiché
    // preg_split('/\n{2,}/', ...) spezza i blocchi solo sulle righe vuote,
    // tutto questo finiva in un unico blocco: la vecchia convertCodeFence()
    // assumeva che il fence di chiusura ``` fosse sempre l'ULTIMA riga del
    // blocco, quindi non lo trovava (l'ultima riga era il paragrafo), non
    // lo rimuoveva, e fondeva sia il ``` letterale sia il paragrafo
    // successivo come contenuto del listato.
    $out = (new MarkdownToLatexConverter)->convert(
        "**Dati di test**\n".
        "```sql\n".
        "insert into activity_reports (owner_kind) values ('user');\n".
        "values ('user', 'monthly', 2026, 7, 'it', now(), now());\n".
        "```\n".
        'Nessun valore per `owner_user_id`: omesso (NULL).'
    );

    expect($out)->not->toContain('```');
    expect($out)->toContain(
        "insert into activity_reports (owner_kind) values ('user');\n".
        "values ('user', 'monthly', 2026, 7, 'it', now(), now());\n".
        '\end{lstlisting}'
    );
    expect($out)->toContain('Nessun valore per \texttt{owner\_user\_id}: omesso (NULL).');
});
