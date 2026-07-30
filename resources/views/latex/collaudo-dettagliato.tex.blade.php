{{--
    Stessa cautela graffe/echo-raw già documentata in resources/views/latex/collaudo.tex.blade.php
    (Task 4, verificata di nuovo qui con view(...)->render() prima di cablare la vista nel Command):
    $titolo e ogni $section['titolo'] sono GIÀ passati per LatexEscaper::escape() lato Command,
    $section['latex'] è GIÀ LaTeX prodotto da MarkdownToLatexConverter (che a sua volta usa
    LatexEscaper internamente) — tutti e tre vanno emessi con l'echo "raw" di Blade (`{!! !!}`),
    mai con `{{ }}` (farebbe HTML-escape e corromperebbe l'escaping LaTeX già fatto, es. `\&`
    diventerebbe `\&amp;`). `\sottotitolo{{!! $titolo !!}}`/`\section{{!! $section['titolo'] !!}}`
    riusano esattamente lo stesso schema di `\sottotitolo{{!! $titolo !!}}` in collaudo.tex.blade.php:
    la graffa letterale di apertura/chiusura della macro resta fuori dal tag raw `{!! ... !!}`
    (che consuma solo le proprie due graffe come delimitatore), non serve raddoppiarla.
--}}
\documentclass[italiano]{montagnaservizi}
\titolodoc{Documento di collaudo}
\sottotitolo{{!! $titolo !!}}
\begin{document}

\copertina

\indicedoc
\clearpage

@foreach ($sections as $section)
\section{{!! $section['titolo'] !!}}

{!! $section['latex'] !!}

\clearpage
@endforeach

\end{document}
