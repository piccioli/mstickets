<?php

declare(strict_types=1);

use App\Support\Latex\LatexPdfCompiler;
use Illuminate\Support\Facades\Process;

it('compiles a minimal valid latex document into a real pdf', function () {
    $tex = <<<'TEX'
    \documentclass{article}
    \begin{document}
    Documento di prova.
    \end{document}
    TEX;

    $path = app(LatexPdfCompiler::class)->compile($tex);

    expect($path)->toBeFile();
    expect(file_get_contents($path))->toStartWith('%PDF');
});

it('compiles a document using the montagnaservizi class and the bundled logo', function () {
    $tex = <<<'TEX'
    \documentclass[italiano]{montagnaservizi}
    \titolodoc{Documento di prova}
    \begin{document}
    \copertina
    Contenuto.
    \end{document}
    TEX;

    $path = app(LatexPdfCompiler::class)->compile($tex);

    expect($path)->toBeFile();
    expect(file_get_contents($path))->toStartWith('%PDF');
});

it('throws with the compiler log tail when the source is invalid', function () {
    $tex = <<<'TEX'
    \documentclass{article}
    \begin{document}
    \undefinedcommandthatdoesnotexist
    \end{document}
    TEX;

    app(LatexPdfCompiler::class)->compile($tex);
})->throws(RuntimeException::class);

it('does not leave temporary compilation directories behind', function () {
    $before = glob(sys_get_temp_dir().'/ms-latex-*') ?: [];

    app(LatexPdfCompiler::class)->compile(<<<'TEX'
    \documentclass{article}
    \begin{document}
    X
    \end{document}
    TEX);

    $after = glob(sys_get_temp_dir().'/ms-latex-*') ?: [];

    expect($after)->toHaveCount(count($before));
});

it('keeps recompiling until the reported page count stabilises across passes', function () {
    // Bug reale trovato inspezionando il PDF combinato di 488 pagine
    // generato da collaudo:generate (task-10-report.md, "Due difetti reali
    // trovati e riprodotti", difetto 2): compile() eseguiva esattamente 2
    // passate pdflatex, ma per quel documento il conteggio pagine cresceva
    // ANCORA tra la 1ª e la 2ª passata (480 -> 488), stabilizzandosi solo
    // dalla 3ª in poi (verificato sperimentalmente compilando a mano più
    // volte lo stesso sorgente). Il documento sintetico qui sotto riproduce
    // deterministicamente lo stesso tipo di non-convergenza, senza dipendere
    // dalla larghezza variabile dei numeri di pagina di un indice reale:
    // legge da un file "pagecount.txt" (scritto dalla passata precedente,
    // nella stessa working directory di compilazione) quante pagine extra
    // aggiungere questa volta — 0 alla 1ª passata (nessun file), poi 1, poi
    // 2 (con un tetto a 2) — cosicché il conteggio pagine osservato da
    // pdflatex segua esattamente la sequenza 1, 2, 3, 3, 3: identica nella
    // forma alla crescita reale osservata (cresce ancora dopo la 2ª
    // passata, si stabilizza solo dalla 3ª/4ª). Un compilatore fermo a 2
    // passate produrrebbe qui un PDF di 2 pagine, sbagliato: il valore
    // vero, verificato manualmente ricompilando lo stesso sorgente più
    // volte nella stessa directory, è 3.
    $tex = <<<'TEX'
    \documentclass{article}
    \newread\prevfile
    \newwrite\outfile
    \newcommand{\prevval}{0}
    \newcounter{extra}

    \IfFileExists{pagecount.txt}{%
    \openin\prevfile=pagecount.txt
    \read\prevfile to \prevvalread
    \closein\prevfile
    \renewcommand{\prevval}{\prevvalread}
    }{}

    \ifnum\prevval>2
    \setcounter{extra}{2}
    \else
    \setcounter{extra}{\prevval}
    \fi

    \begin{document}
    Pagina base.

    \ifcase\value{extra}
    \or
    \newpage
    Pagina extra uno.
    \or
    \newpage
    Pagina extra uno.
    \newpage
    Pagina extra due.
    \fi

    \immediate\openout\outfile=pagecount.txt
    \immediate\write\outfile{\number\numexpr\value{extra}+1\relax}
    \immediate\closeout\outfile
    \end{document}
    TEX;

    $path = app(LatexPdfCompiler::class)->compile($tex);

    expect($path)->toBeFile();

    $pdfinfo = Process::run(['pdfinfo', $path]);

    expect($pdfinfo->successful())->toBeTrue();
    expect($pdfinfo->output())->toMatch('/Pages:\s+3\b/');
});
