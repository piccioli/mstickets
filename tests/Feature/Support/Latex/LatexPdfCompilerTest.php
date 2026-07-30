<?php

declare(strict_types=1);

use App\Support\Latex\LatexPdfCompiler;

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
