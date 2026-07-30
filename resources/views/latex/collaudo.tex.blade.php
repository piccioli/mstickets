{{--
    Nota (fix v0.3.2, verificato con `view(...)->render()` in uno script tinker-like prima di
    cablare questo template nel Command): i valori interpolati qui sono GIÀ passati per
    `LatexEscaper::escape()` lato Command, quindi vanno emessi con l'echo "raw" di Blade
    (`{!! !!}`), MAI con l'echo normale (`{{ }}`), che applica `e()`/`htmlspecialchars()` e
    corromperebbe l'escaping LaTeX già fatto (es. `\&` prodotto da `LatexEscaper` per un
    `&` originale diventerebbe `\&amp;`, sintatticamente invalido). Inoltre, quando un valore
    va scritto dentro un argomento di macro LaTeX (`\sottotitolo{...}`, `\msurl{...}`,
    `\texttt{...}`), la graffa letterale di apertura/chiusura richiede un raddoppio
    apparente (`{{!! ... !!}}`, non `{!! ... !!}`): il tag raw di Blade è la sequenza
    letterale "{!!"/"!!}" e ne consuma un carattere di graffa come proprio delimitatore, quindi
    serve una graffa letterale IN PIÙ per quella della macro LaTeX stessa. Usare qui la stessa
    identica sequenza `{{{ ... }}}` che si vedrebbe scrivendo `\sottotitolo{{{ $titolo }}}`
    (con `{{ }}` invece di `{!! !!}`) sarebbe SBAGLIATO per un motivo distinto e più subdolo:
    Blade riconosce nativamente `{{{ ... }}}` (tre graffe) come sintassi legacy per l'echo
    "escaped" (stessa `e()` di `{{ }}`, ereditata da Blade 4 ante `{!! !!}`), quindi le tre
    graffe letterali di apertura/chiusura verrebbero interamente consumate dal compilatore
    invece di produrre `{valore}` — verificato empiricamente rendendo la vista con dati di
    prova: il `.tex` prodotto perdeva TUTTE le graffe (`\sottotitoloFase 0 + Fase 1` invece di
    `\sottotitolo{Fase 0 + Fase 1}`).
--}}
\documentclass[italiano]{montagnaservizi}
\titolodoc{Documento di collaudo}
\sottotitolo{{!! $titolo !!}}
\begin{document}

\bloccotitolo

\section*{Parte 1 --- Come eseguire il collaudo}

Applicazione: \msurl{{!! $appUrl !!}}

Mailpit (email di test): \msurl{{!! $mailpitUrl !!}}

\mdtabella{@{}p{30mm}Xp{30mm}@{}}{\thc{Ruolo} & \thc{Email} & \thc{Password}}{%
@foreach ($credenziali as $cred)
{!! $cred['ruolo'] !!} & \texttt{{!! $cred['email'] !!}} & \texttt{{!! $cred['password'] !!}} \\
@endforeach
}

\subsection*{Come accedere a Mailpit}

Le email inviate dall'ambiente UAT non escono realmente: sono intercettate da
Mailpit, raggiungibile all'indirizzo sopra con autenticazione HTTP (utente/
password forniti separatamente dal team, non stampati in questo documento
per non esporli insieme all'URL pubblico).

\subsection*{Come segnalare un problema}

Per ogni test fallito, annotare l'ID del test (es. F1-03), una descrizione di
cosa è successo invece del comportamento atteso, e se possibile uno
screenshot.

\subsection*{Indice degli argomenti}

\begin{enumerate}
@foreach ($topics as $topic)
  \item {!! $topic['titolo'] !!} ({{ count($topic['test']) }} test)
@endforeach
\end{enumerate}

\end{document}
