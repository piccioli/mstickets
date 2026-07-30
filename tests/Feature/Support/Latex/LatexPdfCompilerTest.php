<?php

declare(strict_types=1);

use App\Support\Latex\LatexPdfCompiler;
use Illuminate\Support\Facades\Log;
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

it('logs a warning and still returns a pdf when the page count never stabilises within the pass cap', function () {
    // Finding del reviewer sul fix precedente (task-10-fix-report.md): se il
    // conteggio pagine non si stabilizza MAI entro self::MAX_PASSES passate
    // (documento patologico, o semplicemente più grande/complesso di
    // qualunque cosa vista finora), compile() tornava comunque il PDF
    // dell'ultima passata SENZA alcun segnale — esattamente la stessa classe
    // di difetto (footer/indice con numeri di pagina sbagliati) che il fix
    // precedente ha riprodotto e corretto sul documento reale di 488 pagine,
    // solo spostata "un tetto più in là": un futuro documento ancora più
    // grande di 488 pagine potrebbe non convergere nemmeno in 5 passate, e
    // andrebbe scoperto solo da un'altra ispezione manuale pagina-per-pagina
    // del PDF, come è già successo una volta. Il documento sintetico qui
    // sotto riusa la stessa tecnica basata su "pagecount.txt" del test sopra,
    // ma SENZA alcun tetto sulle pagine extra: il conteggio pagine cresce di
    // 1 a ogni passata (1, 2, 3, 4, 5, ...) e non si stabilizza mai entro le
    // 5 passate massime — verificato manualmente compilando lo stesso
    // sorgente 6 volte di fila nella stessa directory (sequenza osservata:
    // 1, 2, 3, 4, 5, poi un'anomalia dell'\ifcase alla 6ª, irrilevante perché
    // compile() non arriva mai a una 6ª passata).
    Log::spy();

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

    \setcounter{extra}{\prevval}

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
    \or
    \newpage
    Pagina extra uno.
    \newpage
    Pagina extra due.
    \newpage
    Pagina extra tre.
    \or
    \newpage
    Pagina extra uno.
    \newpage
    Pagina extra due.
    \newpage
    Pagina extra tre.
    \newpage
    Pagina extra quattro.
    \fi

    \immediate\openout\outfile=pagecount.txt
    \immediate\write\outfile{\number\numexpr\value{extra}+1\relax}
    \immediate\closeout\outfile
    \end{document}
    TEX;

    $path = app(LatexPdfCompiler::class)->compile($tex);

    expect($path)->toBeFile();
    expect(file_get_contents($path))->toStartWith('%PDF');

    $pdfinfo = Process::run(['pdfinfo', $path]);
    expect($pdfinfo->successful())->toBeTrue();
    expect($pdfinfo->output())->toMatch('/Pages:\s+5\b/');

    Log::shouldHaveReceived('warning')
        ->once()
        ->withArgs(fn (string $message, array $context): bool => str_contains($message, 'non convergente')
            && $context['passes'] === 5
            && $context['previous_page_count'] === 4
            && $context['last_page_count'] === 5
        );
});
