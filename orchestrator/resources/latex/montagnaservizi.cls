\NeedsTeXFormat{LaTeX2e}
\ProvidesClass{montagnaservizi}[2026/07/30 v1.0 Montagna Servizi - documenti tecnici]

%% ============================================================
%%  montagnaservizi.cls
%%  Classe LaTeX per documenti tecnici e istituzionali
%%  di Montagna Servizi S.C.P.A. (carta intestata ufficiale).
%%  Motore: pdfLaTeX.   Documentazione: vedi README.md
%% ============================================================

% ---------------- Opzioni di classe ----------------
\newif\ifms@english      \ms@englishfalse
\newif\ifms@bozza        \ms@bozzafalse
\newif\ifms@riservato    \ms@riservatofalse
\newif\ifms@logopiccolo  \ms@logopiccolotrue

\DeclareOption{italiano}{\ms@englishfalse}
\DeclareOption{english}{\ms@englishtrue}
\DeclareOption{bozza}{\ms@bozzatrue}
\DeclareOption{riservato}{\ms@riservatotrue}
\DeclareOption{solofooter}{\ms@logopiccolofalse}
\DeclareOption*{\PassOptionsToClass{\CurrentOption}{article}}
\ExecuteOptions{italiano}
\ProcessOptions\relax
\LoadClass[11pt,a4paper,oneside]{article}

% ---------------- Codifica e lingua ----------------
\RequirePackage[T1]{fontenc}
\RequirePackage[utf8]{inputenc}
\ifms@english
  \RequirePackage[italian,main=english]{babel}
\else
  \RequirePackage[english,main=italian]{babel}
\fi
\RequirePackage{csquotes}

% ---------------- Font (pdfLaTeX) ------------------
% Manrope non e' disponibile in TeX Live: Montserrat e' la sostituzione
% geometrica piu' vicina al wordmark; fallback su Source Sans Pro.
\IfFileExists{montserrat.sty}
  {\RequirePackage[defaultfam,tabular,lining]{montserrat}}
  {\IfFileExists{sourcesanspro.sty}
     {\RequirePackage[default]{sourcesanspro}}
     {\renewcommand{\familydefault}{\sfdefault}}}
\IfFileExists{inconsolata.sty}{\RequirePackage{inconsolata}}{}
\RequirePackage{microtype}

% ---------------- Pacchetti ------------------------
\RequirePackage[table,dvipsnames]{xcolor}
\RequirePackage{graphicx}
\RequirePackage{eso-pic}
\RequirePackage{fancyhdr}
\RequirePackage{lastpage}
\RequirePackage{titlesec}
\RequirePackage{enumitem}
\RequirePackage{array}
\RequirePackage{tabularx}
\RequirePackage{longtable}
\RequirePackage{xltabular}
\RequirePackage{booktabs}
\RequirePackage{multirow}
\RequirePackage{ragged2e}
\RequirePackage{setspace}
\RequirePackage{needspace}
\RequirePackage[most]{tcolorbox}
\RequirePackage{listings}
\RequirePackage{tocloft}
\RequirePackage[hidelinks]{hyperref}

\graphicspath{{.}{assets/}{img/}{immagini/}{figure/}{public/}{resources/}}

% ---------------- Palette (design system) ----------
\definecolor{msverde}{HTML}{1D574B}
\definecolor{msverde900}{HTML}{0F332B}
\definecolor{msverde800}{HTML}{123F35}
\definecolor{msverde500}{HTML}{2A6E5F}
\definecolor{msverde300}{HTML}{7FB0A3}
\definecolor{msverde200}{HTML}{B9D4CC}
\definecolor{msverde100}{HTML}{E3EFEB}
\definecolor{msverde50}{HTML}{F1F7F5}
\definecolor{mslarice}{HTML}{C77E2A}
\definecolor{mslarice700}{HTML}{A9611E}
\definecolor{mslarice100}{HTML}{F8ECD9}
\definecolor{mspietra900}{HTML}{1C201E}
\definecolor{mspietra700}{HTML}{454E4A}
\definecolor{mspietra500}{HTML}{7C857F}
\definecolor{mspietra400}{HTML}{A7AEA9}
\definecolor{mspietra300}{HTML}{CBD1CD}
\definecolor{mspietra200}{HTML}{E4E8E5}
\definecolor{mspietra100}{HTML}{F1F4F2}
\definecolor{mspietra50}{HTML}{F8FAF9}

\color{mspietra900}

% ---------------- Dati dell'organizzazione ---------
% Sovrascrivibili con \renewcommand nel preambolo del documento.
\newcommand{\msragionesociale}{MONTAGNA SERVIZI S.C.P.A.}
\newcommand{\msindirizzo}{Via Errico Petrella 19 - 20124 - Milano (MI)}
\newcommand{\mssito}{https://montagnaservizi.com}
\newcommand{\mspec}{montagnaserviziscpa@legalmail.it}
\newcommand{\msemail}{info@montagnaservizi.com}
\newcommand{\mspiva}{11790660960}
\newcommand{\mslogofile}{montagna-servizi-logo}

% ---------------- Metadati del documento -----------
\newcommand{\ms@def}[2]{\expandafter\gdef\csname ms@#1\endcsname{#2}}
\def\ms@titolo{}\def\ms@sottotitolo{}\def\ms@committente{}%
\def\ms@autore{}\def\ms@ruolo{}\def\ms@codice{}\def\ms@revisione{}%
\def\ms@data{\today}\def\ms@luogo{Milano}\def\ms@protocollo{}%
\def\ms@oggetto{}\def\ms@destinatario{}\def\ms@modalita{}

\newcommand{\titolodoc}[1]{\ms@def{titolo}{#1}}
\newcommand{\sottotitolo}[1]{\ms@def{sottotitolo}{#1}}
\newcommand{\committente}[1]{\ms@def{committente}{#1}}
\newcommand{\autore}[1]{\ms@def{autore}{#1}}
\newcommand{\ruolo}[1]{\ms@def{ruolo}{#1}}
\newcommand{\codicedoc}[1]{\ms@def{codice}{#1}}
\newcommand{\revisione}[1]{\ms@def{revisione}{#1}}
\newcommand{\datadoc}[1]{\ms@def{data}{#1}}
\newcommand{\luogo}[1]{\ms@def{luogo}{#1}}
\newcommand{\protocollo}[1]{\ms@def{protocollo}{#1}}
\newcommand{\oggetto}[1]{\ms@def{oggetto}{#1}}
\newcommand{\destinatario}[1]{\ms@def{destinatario}{#1}}
\newcommand{\modalita}[1]{\ms@def{modalita}{#1}}

% ---------------- Etichette localizzate ------------
\ifms@english
  \def\ms@l@codice{Document code}\def\ms@l@rev{Revision}%
  \def\ms@l@data{Date}\def\ms@l@luogo{Place}\def\ms@l@committente{Client}%
  \def\ms@l@autore{Author}\def\ms@l@prot{Ref.}\def\ms@l@storico{Revision history}%
  \def\ms@l@pag{Page}\def\ms@l@di{of}\def\ms@l@nota{Note}%
  \def\ms@l@attenzione{Warning}\def\ms@l@requisito{Requirement}%
  \def\ms@l@rif{Normative references}\def\ms@l@oggetto{Subject}%
  \def\ms@l@presenti{Attendees}\def\ms@l@assenti{Absent}%
  \def\ms@l@odg{Agenda}\def\ms@l@delibere{Decisions}\def\ms@l@azioni{Actions}%
  \def\ms@l@totale{Total}\def\ms@l@bozza{DRAFT}\def\ms@l@riservato{CONFIDENTIAL}%
  \def\ms@l@firme{Signatures}\def\ms@l@indice{Contents}%
  \def\ms@l@figure{Figures}\def\ms@l@tabelle{Tables}%
  \def\ms@l@descrizione{Description}\def\ms@l@qta{Qty}%
  \def\ms@l@unit{Unit price}\def\ms@l@importo{Amount}%
  \def\ms@l@versione{Ver.}\def\ms@l@modifiche{Changes}%
  \def\ms@l@resp{Owner}\def\ms@l@scadenza{Due}\def\ms@l@passo{Step}%
  \def\ms@l@saluti{Kind regards,}%
\else
  \def\ms@l@codice{Codice documento}\def\ms@l@rev{Revisione}%
  \def\ms@l@data{Data}\def\ms@l@luogo{Luogo}\def\ms@l@committente{Committente}%
  \def\ms@l@autore{Autore}\def\ms@l@prot{Prot.}\def\ms@l@storico{Storico revisioni}%
  \def\ms@l@pag{Pag.}\def\ms@l@di{di}\def\ms@l@nota{Nota}%
  \def\ms@l@attenzione{Attenzione}\def\ms@l@requisito{Requisito}%
  \def\ms@l@rif{Riferimenti normativi}\def\ms@l@oggetto{Oggetto}%
  \def\ms@l@presenti{Presenti}\def\ms@l@assenti{Assenti}%
  \def\ms@l@odg{Ordine del giorno}\def\ms@l@delibere{Deliberazioni}\def\ms@l@azioni{Azioni}%
  \def\ms@l@totale{Totale}\def\ms@l@bozza{BOZZA}\def\ms@l@riservato{RISERVATO}%
  \def\ms@l@firme{Firme}\def\ms@l@indice{Indice}%
  \def\ms@l@figure{Elenco delle figure}\def\ms@l@tabelle{Elenco delle tabelle}%
  \def\ms@l@descrizione{Descrizione}\def\ms@l@qta{Q.ta}%
  \def\ms@l@unit{Prezzo unit.}\def\ms@l@importo{Importo}%
  \def\ms@l@versione{Ver.}\def\ms@l@modifiche{Modifiche}%
  \def\ms@l@resp{Responsabile}\def\ms@l@scadenza{Scadenza}\def\ms@l@passo{Fase}%
  \def\ms@l@saluti{Cordiali saluti,}%
\fi
\addto\captionsitalian{\renewcommand{\contentsname}{\ms@l@indice}%
  \renewcommand{\listfigurename}{\ms@l@figure}%
  \renewcommand{\listtablename}{\ms@l@tabelle}}

% ---------------- Geometria (ricavata dalla carta intestata) ----
% Logo grande: 134.3pt x 69.7pt, centrato, bordo superiore a 9pt dal
% margine alto del foglio. Footer: prima riga con baseline a 80.3pt
% dal piede del foglio.

\RequirePackage[a4paper,left=25mm,right=25mm,top=32mm,bottom=38mm,
  headheight=14mm,headsep=8mm,footskip=38.8pt]{geometry}

\newlength{\ms@logow}\setlength{\ms@logow}{134.3pt}
\newlength{\ms@logoh}\setlength{\ms@logoh}{69.7pt}

\newcommand{\ms@logogrande}{%
  \IfFileExists{\mslogofile.png}
    {\includegraphics[width=\ms@logow]{\mslogofile}}
    {\parbox{\ms@logow}{\centering\color{msverde}\bfseries\fontsize{20}{22}\selectfont
       Montagna\\Servizi}}}
\newcommand{\ms@logopicc}{%
  \IfFileExists{\mslogofile.png}
    {\includegraphics[width=26mm]{\mslogofile}}
    {\color{msverde}\bfseries\normalsize Montagna Servizi}}

% ---------------- Footer e header ------------------
\newcommand{\ms@footergrande}{%
  \begingroup\color{msverde}\centering\normalfont
  {\fontsize{9}{11}\selectfont\bfseries\msragionesociale\par}%
  {\fontsize{7}{9.8}\selectfont\msindirizzo\par}%
  {\fontsize{7}{9.8}\selectfont\mssito\par}%
  {\fontsize{7}{10.4}\selectfont PEC: \mspec\ - email: \msemail\par}%
  {\fontsize{8}{11.4}\selectfont C.F./P.IVA: \mspiva\par}%
  \endgroup}

\newcommand{\ms@footerpiccolo}{%
  \begingroup\color{msverde}\fontsize{7.5}{10}\selectfont
  \textcolor{msverde200}{\rule{\textwidth}{0.6pt}}\par\vspace{2pt}%
  \hbox to\textwidth{%
    \parbox[t]{0.6\textwidth}{\raggedright\msragionesociale
      \ifx\ms@codice\empty\else\ \textcolor{mspietra500}{\textbar}\ \ms@codice\fi
      \ifx\ms@revisione\empty\else\ \textcolor{mspietra500}{\textbar}\ \ms@l@rev\ \ms@revisione\fi}%
    \hfil
    \parbox[t]{0.35\textwidth}{\raggedleft\ms@l@pag\ \thepage\ \ms@l@di\ \pageref{LastPage}}}%
  \endgroup}

\newcommand{\ms@headerpiccolo}{%
  \begingroup\color{msverde}
  \hbox to\textwidth{%
    \raisebox{-1mm}{\ms@logopicc}\hfil
    \parbox[b]{0.55\textwidth}{\raggedleft\fontsize{8}{10.5}\selectfont
      \ifms@riservato\textbf{\ms@l@riservato}\\[1pt]\fi
      \ifx\ms@titolo\empty\else\textcolor{mspietra700}{\ms@titolo}\fi}}\par
  \vspace{2.5mm}\textcolor{msverde200}{\rule{\textwidth}{0.6pt}}%
  \endgroup}

\fancypagestyle{msprima}{%
  \fancyhf{}\renewcommand{\headrulewidth}{0pt}\renewcommand{\footrulewidth}{0pt}%
  \fancyfoot[C]{\ms@footergrande}}
\fancypagestyle{ms}{%
  \fancyhf{}\renewcommand{\headrulewidth}{0pt}\renewcommand{\footrulewidth}{0pt}%
  \ifms@logopiccolo\fancyhead[L]{\ms@headerpiccolo}\fi
  \fancyfoot[C]{\ms@footerpiccolo}}
\pagestyle{ms}

% Logo grande in testa alla prima pagina + eventuale filigrana.
\newcommand{\ms@logoprimapagina}{%
  \AddToShipoutPictureBG*{%
    \put(\LenToUnit{\dimexpr0.5\paperwidth-0.5\ms@logow\relax},%
         \LenToUnit{\dimexpr\paperheight-9pt-\ms@logoh\relax}){\ms@logogrande}}}

\ifms@bozza
  \AddToShipoutPictureFG{%
    \put(\LenToUnit{0.5\paperwidth},\LenToUnit{0.42\paperheight}){%
      \makebox(0,0){\rotatebox{54}{\color{msverde!14}%
        \fontsize{110}{110}\selectfont\bfseries\ms@l@bozza}}}}
\fi

% ---------------- Titoli di sezione ----------------
\titleformat{\section}{\color{msverde}\bfseries\fontsize{15}{18}\selectfont}
  {\thesection}{0.7em}{}
\titleformat{\subsection}{\color{msverde800}\bfseries\fontsize{12.5}{15}\selectfont}
  {\thesubsection}{0.6em}{}
\titleformat{\subsubsection}{\color{mspietra900}\bfseries\fontsize{11}{13}\selectfont}
  {\thesubsubsection}{0.5em}{}
\titlespacing*{\section}{0pt}{16pt}{6pt}
\titlespacing*{\subsection}{0pt}{12pt}{4pt}
\titlespacing*{\subsubsection}{0pt}{10pt}{3pt}

\setlength{\parindent}{0pt}
\setlength{\parskip}{6pt}
\linespread{1.12}
\setlength{\emergencystretch}{2em}

\renewcommand{\cftsecfont}{\bfseries\color{msverde}}
\renewcommand{\cftsecpagefont}{\bfseries\color{msverde}}
\renewcommand{\cftsecleader}{\bfseries\color{mspietra300}\cftdotfill{\cftsecdotsep}}
\renewcommand{\cftsubsecfont}{\color{mspietra700}}

% ---------------- Elenchi brandizzati --------------
\setlist[itemize,1]{label=\textcolor{msverde}{\raisebox{0.12ex}{\scriptsize$\blacktriangleright$}},
  leftmargin=1.2em,itemsep=3pt,topsep=4pt,parsep=0pt}
\setlist[itemize,2]{label=\textcolor{msverde300}{\textbf{--}},leftmargin=1.1em,itemsep=2pt,topsep=2pt}
\setlist[enumerate,1]{label=\textcolor{msverde}{\bfseries\arabic*.},leftmargin=1.5em,itemsep=3pt,topsep=4pt}
\newlist{passi}{enumerate}{1}
\setlist[passi]{label=\protect\ms@passolabel{\arabic*},leftmargin=2.6em,itemsep=8pt,topsep=6pt,labelsep=0.7em}
\newcommand{\ms@passolabel}[1]{%
  \colorbox{msverde}{\makebox[1.5em][c]{\color{white}\bfseries\small#1}}}

% ---------------- Link ------------------------------
\hypersetup{colorlinks=true,allcolors=msverde500,pdfborder={0 0 0}}
\newcommand{\msurl}[1]{\href{#1}{\nolinkurl{#1}}}

% ---------------- Copertina -------------------------
\newcommand{\copertina}{%
  \thispagestyle{msprima}\ms@logoprimapagina
  \vspace*{14mm}
  \begingroup\raggedright
  \ifms@riservato
    \colorbox{mslarice100}{\textcolor{mslarice700}{\bfseries\footnotesize\,\ms@l@riservato\,}}\par\vspace{6mm}
  \fi
  {\color{msverde}\fontsize{11}{13}\selectfont\bfseries
   \ifx\ms@committente\empty\else\MakeUppercase{\ms@committente}\par\fi}
  \vspace{4mm}
  {\color{mspietra900}\fontsize{30}{34}\selectfont\bfseries\ms@titolo\par}
  \ifx\ms@sottotitolo\empty\else
    \vspace{4mm}{\color{mspietra700}\fontsize{14}{19}\selectfont\ms@sottotitolo\par}\fi
  \vspace{7mm}\textcolor{msverde}{\rule{46mm}{2.4pt}}\par
  \vfill
  \begingroup\fontsize{9.5}{13}\selectfont
  \setlength{\tabcolsep}{0pt}
  \begin{tabular}{@{}p{34mm}p{\dimexpr\textwidth-36mm\relax}@{}}
    \ifx\ms@autore\empty\else
      \textcolor{msverde}{\bfseries\ms@l@autore} & \ms@autore
        \ifx\ms@ruolo\empty\else\ \textcolor{mspietra500}{\textbar}\ \ms@ruolo\fi \\[3pt]\fi
    \ifx\ms@committente\empty\else
      \textcolor{msverde}{\bfseries\ms@l@committente} & \ms@committente \\[3pt]\fi
    \ifx\ms@codice\empty\else
      \textcolor{msverde}{\bfseries\ms@l@codice} & \ms@codice \\[3pt]\fi
    \ifx\ms@revisione\empty\else
      \textcolor{msverde}{\bfseries\ms@l@rev} & \ms@revisione \\[3pt]\fi
    \ifx\ms@protocollo\empty\else
      \textcolor{msverde}{\bfseries\ms@l@prot} & \ms@protocollo \\[3pt]\fi
    \textcolor{msverde}{\bfseries\ms@l@data} & \ms@luogo, \ms@data \\
  \end{tabular}
  \endgroup
  \vspace{6mm}
  \endgroup
  \clearpage}

% ---------------- Blocco titolo (senza copertina) ---
\newcommand{\bloccotitolo}{%
  \thispagestyle{msprima}\ms@logoprimapagina
  \vspace*{9mm}
  \begingroup\raggedright
  \ifx\ms@committente\empty\else
    {\color{msverde}\fontsize{9}{12}\selectfont\bfseries
     \MakeUppercase{\ms@committente}\par}\vspace{2mm}\fi
  {\color{mspietra900}\fontsize{21}{25}\selectfont\bfseries\ms@titolo\par}
  \ifx\ms@sottotitolo\empty\else
    \vspace{2mm}{\color{mspietra700}\fontsize{12}{16}\selectfont\ms@sottotitolo\par}\fi
  \vspace{4mm}
  {\fontsize{8.5}{11}\selectfont\color{mspietra500}
   \ifx\ms@codice\empty\else\ms@l@codice\ \textbf{\ms@codice}\quad\textbar\quad\fi
   \ifx\ms@revisione\empty\else\ms@l@rev\ \textbf{\ms@revisione}\quad\textbar\quad\fi
   \ms@luogo, \ms@data\par}
  \vspace{3mm}\textcolor{msverde}{\rule{\textwidth}{1.6pt}}\par
  \endgroup
  \vspace{2mm}}

% ---------------- Indice ----------------------------
\newcommand{\indicedoc}{%
  \begingroup
  {\color{msverde}\fontsize{15}{18}\selectfont\bfseries\contentsname\par}
  \vspace{-4mm}
  \renewcommand{\contentsname}{}
  \tableofcontents
  \endgroup\vspace{4mm}}
\newcommand{\indicecompleto}{%
  \indicedoc
  \begingroup\fontsize{10}{13}\selectfont
  {\color{msverde}\fontsize{12}{15}\selectfont\bfseries\listfigurename\par}\vspace{-4mm}
  \renewcommand{\listfigurename}{}\listoffigures\par\vspace{4mm}
  {\color{msverde}\fontsize{12}{15}\selectfont\bfseries\listtablename\par}\vspace{-4mm}
  \renewcommand{\listtablename}{}\listoftables
  \endgroup}

% ---------------- Storico revisioni -----------------
% NOTA (fix v0.3.2): tabularx legge l'intero corpo della tabella come un
% argomento verbatim fino al primo `\end{tabularx}' letterale che incontra nel
% flusso di token (meccanismo \TX@get@body). Se `\begin{tabularx}' e
% `\end{tabularx}' provengono da due macro DIVERSE (qui: il codice di apertura
% e chiusura di un \newenvironment), quel `\end{tabularx}' non compare mai
% letteralmente nello stream finché non viene espanso a sua volta, e la
% scansione consuma tutto il resto del documento fino a EOF ("Runaway
% argument? File ended while scanning use of \TX@get@body"). Per questo qui
% (e in \mstabella, \presenze, \azioni, \voci) il corpo è un argomento
% obbligatorio di un \newcommand unico, cosi' `\begin{tabularx}...corpo...
% \end{tabularx}' arriva nello stream in un solo colpo dalla stessa espansione.
\newcommand{\storicorevisioni}[1]{%
  \needspace{4\baselineskip}
  \begingroup
  {\color{msverde}\fontsize{12.5}{15}\selectfont\bfseries\ms@l@storico\par}\vspace{2mm}
  \fontsize{9}{12}\selectfont
  \rowcolors{2}{white}{msverde50}
  \begin{tabularx}{\textwidth}{@{}>{\bfseries}p{14mm}p{22mm}p{34mm}X@{}}
  \toprule
  \textcolor{msverde}{\ms@l@versione} & \textcolor{msverde}{\ms@l@data}
  & \textcolor{msverde}{\ms@l@autore} & \textcolor{msverde}{\ms@l@modifiche}\\
  \midrule
  #1
  \bottomrule
  \end{tabularx}
  \endgroup\vspace{4mm}}
\newcommand{\revrow}[4]{#1 & #2 & #3 & #4 \\}

% ---------------- Tabelle ---------------------------
% \begin{mstabella}{colonne}{didascalia}{label} ... \end{mstabella}
% \mstabella{colonne}{didascalia}{label}{corpo} — vedi nota su \storicorevisioni.
\newcommand{\mstabella}[4]{%
  \begin{table}[htbp]\centering\fontsize{9.5}{12.5}\selectfont
  \caption{#2}\label{#3}
  \rowcolors{2}{white}{msverde50}
  \begin{tabularx}{\textwidth}{#1}\toprule
  #4
  \bottomrule\end{tabularx}\end{table}}
\newcommand{\intestazioneriga}[1]{\rowcolor{white}#1\\\midrule}
\newcommand{\thc}[1]{\textcolor{msverde}{\bfseries#1}}

\setlength{\arrayrulewidth}{0.5pt}
\arrayrulecolor{mspietra300}
\renewcommand{\arraystretch}{1.25}

% ---------------- Figure ----------------------------
\RequirePackage[font=small,labelfont={bf,color=msverde},
  labelsep=period,justification=raggedright,singlelinecheck=false,
  skip=6pt]{caption}
% \msfigura{file}{larghezza}{didascalia}{label}
\newcommand{\msfigura}[4]{%
  \begin{figure}[htbp]\centering
  \fcolorbox{mspietra200}{white}{\includegraphics[width=#2]{#1}}
  \caption{#3}\label{#4}
  \end{figure}}
% segnaposto quando l'immagine non c'e' ancora
\newcommand{\msfiguraplaceholder}[3][60mm]{%
  \begin{figure}[htbp]\centering
  \fcolorbox{mspietra300}{mspietra50}{\parbox[c][#1][c]{\dimexpr\textwidth-2\fboxsep-2\fboxrule\relax}
    {\centering\color{mspietra500}\small #2}}
  \caption{#2}\label{#3}
  \end{figure}}

% ---------------- Box ------------------------------
\tcbset{msbox/.style={
  breakable,enhanced,arc=3pt,
  boxrule=0.6pt,left=10pt,right=10pt,top=8pt,bottom=8pt,
  fontupper=\fontsize{10}{13.5}\selectfont,
  fonttitle=\fontsize{9}{11}\selectfont\bfseries,
  coltitle=msverde,attach title to upper={\par\vspace{4pt}},
  title style={fill=none},
}}
\newtcolorbox{nota}[1][\ms@l@nota]{msbox,colback=msverde50,colframe=msverde200,
  coltitle=msverde,title={#1}}
\newtcolorbox{attenzione}[1][\ms@l@attenzione]{msbox,colback=mslarice100,
  colframe=mslarice,coltitle=mslarice700,title={#1}}
\newtcolorbox{requisito}[1][\ms@l@requisito]{msbox,colback=mspietra50,
  colframe=mspietra300,coltitle=mspietra700,title={#1}}
\newtcolorbox{evidenza}{msbox,colback=msverde,colframe=msverde,
  coltitle=white,fontupper=\fontsize{11}{15}\selectfont\color{white}}

% ---------------- Listing --------------------------
\lstdefinestyle{ms}{
  basicstyle=\ttfamily\fontsize{8.5}{10.5}\selectfont\color{mspietra900},
  backgroundcolor=\color{mspietra50},
  keywordstyle=\color{msverde}\bfseries,
  commentstyle=\color{mspietra500}\itshape,
  stringstyle=\color{mslarice700},
  numberstyle=\ttfamily\tiny\color{mspietra400},
  numbers=left,numbersep=8pt,
  frame=single,rulecolor=\color{mspietra300},framesep=6pt,
  showstringspaces=false,breaklines=true,breakatwhitespace=true,
  tabsize=2,columns=fullflexible,upquote=true,
}
\lstset{style=ms}

% ---------------- Riferimenti normativi -------------
\newenvironment{riferimenti}{%
  \needspace{3\baselineskip}
  {\color{msverde}\fontsize{12.5}{15}\selectfont\bfseries\ms@l@rif\par}\vspace{1mm}
  \begin{list}{}{\setlength{\leftmargin}{34mm}\setlength{\labelwidth}{32mm}%
    \setlength{\labelsep}{2mm}\setlength{\itemsep}{4pt}%
    \renewcommand{\makelabel}[1]{\textcolor{msverde}{\bfseries##1}\hfill}}%
  \fontsize{9.5}{13}\selectfont
}{\end{list}\vspace{3mm}}
\newcommand{\rif}[2]{\item[#1] #2}

% ---------------- Firme -----------------------------
% \begin{firme}[n colonne] \firma{Nome}{Ruolo} & \firma{...}{...} \end{firme}
% NOTA (fix v0.3.2): la colonna p{\dimexpr(\textwidth-(#1-1)*8mm)/#1\relax}
% originale smette di funzionare in modo intermittente quando la tabular vive
% dentro una minipage preceduta da altre tabularx/minipage nello stesso
% documento ("Illegal unit of measure (pt inserted)" durante lo scan del
% preambolo di array/tabular — i registri temporanei di tabularx restano
% "in volo" e interferiscono con un \dimexpr su \textwidth valutato subito
% dopo). Fix: colonne \texttt{X} di tabularx (dividono lo spazio residuo in
% automatico, stesso meccanismo robusto già usato altrove nella classe) al
% posto del calcolo manuale — elimina il \dimexpr e con esso l'interferenza.
% Come \mstabella/\storicorevisioni/\presenze/\azioni/\voci, il corpo è un
% argomento obbligatorio (mai \begin{firme}/\end{firme}): vedi nota sopra su
% \storicorevisioni sul perché tabularx non tollera un \newenvironment.
% \firme[n colonne]{corpo, celle separate da &}
\newcommand{\firme}[2][2]{%
  \needspace{7\baselineskip}\par\vspace{8mm}
  \noindent\begin{minipage}{\textwidth}
  \fontsize{9.5}{12}\selectfont
  \setlength{\tabcolsep}{4mm}
  \begin{tabularx}{\textwidth}{@{}*{#1}{X}@{}}
  #2
  \end{tabularx}
  \end{minipage}\par}
\newcommand{\firma}[2]{%
  \parbox[t]{\linewidth}{\vspace{12mm}%
    \textcolor{mspietra300}{\rule{\linewidth}{0.6pt}}\\[2pt]
    \textbf{#1}\\\textcolor{mspietra500}{#2}}}

% ---------------- Lettera ---------------------------
\newcommand{\bloccolettera}{%
  \thispagestyle{msprima}\ms@logoprimapagina
  \vspace*{9mm}
  \begingroup\fontsize{10}{14}\selectfont
  \ifx\ms@protocollo\empty\else
    {\color{mspietra500}\ms@l@prot\ \textbf{\ms@protocollo}\par}\fi
  \hfill\begin{minipage}[t]{0.5\textwidth}\raggedright
    \bfseries\ms@destinatario
  \end{minipage}\par
  \vspace{10mm}
  {\color{mspietra700}\ms@luogo, \ms@data\par}
  \vspace{6mm}
  \ifx\ms@oggetto\empty\else
    {\color{msverde}\bfseries\ms@l@oggetto:\ \ms@oggetto\par}
    \vspace{2mm}\textcolor{msverde200}{\rule{\textwidth}{0.8pt}}\par\fi
  \endgroup\vspace{4mm}}
\newcommand{\saluti}[1][\ms@l@saluti]{%
  \par\vspace{6mm}\raggedright #1\par}

% ---------------- Verbale ---------------------------
\newcommand{\bloccoverbale}{%
  \begingroup\fontsize{9.5}{13}\selectfont
  \rowcolors{2}{white}{msverde50}
  \begin{tabularx}{\textwidth}{@{}>{\bfseries\color{msverde}}p{32mm}X@{}}
  \toprule
  \ms@l@data & \ms@data \\
  \ms@l@luogo & \ms@luogo\ifx\ms@modalita\empty\else\ ---\ \ms@modalita\fi \\
  \ifx\ms@codice\empty\else\ms@l@codice & \ms@codice \\\fi
  \bottomrule
  \end{tabularx}
  \endgroup\vspace{4mm}}
% \presenze[titolo]{corpo} — vedi nota su \storicorevisioni.
\newcommand{\presenze}[2][\ms@l@presenti]{%
  \needspace{3\baselineskip}
  {\color{msverde}\fontsize{11}{14}\selectfont\bfseries#1\par}\vspace{1mm}
  \begingroup\fontsize{9.5}{13}\selectfont
  \begin{tabularx}{\textwidth}{@{}p{62mm}X@{}}
  #2
  \end{tabularx}\endgroup\vspace{4mm}}
\newcommand{\persona}[2]{\textbf{#1} & \textcolor{mspietra700}{#2}\\}
% \azioni{corpo} — vedi nota su \storicorevisioni.
\newcommand{\azioni}[1]{%
  \needspace{4\baselineskip}
  {\color{msverde}\fontsize{12.5}{15}\selectfont\bfseries\ms@l@azioni\par}\vspace{2mm}
  \begingroup\fontsize{9.5}{12.5}\selectfont
  \rowcolors{2}{white}{msverde50}
  \begin{tabularx}{\textwidth}{@{}p{10mm}Xp{36mm}p{22mm}@{}}\toprule
  \thc{N.} & \thc{\ms@l@azioni} & \thc{\ms@l@resp} & \thc{\ms@l@scadenza}\\\midrule
  #1
  \bottomrule\end{tabularx}\endgroup\vspace{4mm}}
\newcommand{\azione}[4]{#1 & #2 & #3 & #4\\}
\newcommand{\deliberazione}[2]{%
  \needspace{3\baselineskip}
  \begin{evidenza}\textbf{#1}\\[2pt]#2\end{evidenza}}

% ---------------- Preventivo ------------------------
% \voci{corpo} — vedi nota su \storicorevisioni.
\newcommand{\voci}[1]{%
  \needspace{4\baselineskip}
  \begingroup\fontsize{9.5}{12.5}\selectfont
  \rowcolors{2}{white}{msverde50}
  \begin{tabularx}{\textwidth}{@{}Xp{14mm}>{\raggedleft\arraybackslash}p{24mm}>{\raggedleft\arraybackslash}p{24mm}@{}}
  \toprule
  \thc{\ms@l@descrizione} & \thc{\ms@l@qta} & \thc{\ms@l@unit} & \thc{\ms@l@importo}\\
  \midrule
  #1
  \bottomrule\end{tabularx}\endgroup\vspace{3mm}}
\newcommand{\voce}[4]{#1 & #2 & #3 & #4\\}
\newcommand{\totale}[2][\ms@l@totale]{%
  \hfill\begin{minipage}{72mm}
  \colorbox{msverde}{\parbox{\dimexpr72mm-2\fboxsep\relax}{\color{white}\bfseries
    \hbox to\linewidth{\fontsize{10}{13}\selectfont#1\hfil\fontsize{13}{16}\selectfont#2}}}
  \end{minipage}\par\vspace{3mm}}

% ---------------- Tabelle lunghe generate da markdown (v0.3.2) -----
% A differenza di \mstabella (tabularx, una sola pagina, con didascalia
% numerata), pensata per tabelle di lunghezza arbitraria che possono
% spezzarsi su più pagine (manuale di collaudo importato da Markdown): usa
% xltabular (longtable + colonne elastiche tipo tabularx: la sola colonna
% `X' non e' definita per il longtable semplice). xltabular EREDITA da
% tabularx lo stesso meccanismo "leggi tutto il corpo come argomento
% verbatim fino a \end{xltabular}" (vedi nota sopra su \storicorevisioni):
% per questo, a differenza di un longtable puro, anche qui il corpo è un
% argomento obbligatorio di un \newcommand unico, mai un \newenvironment.
% \mdtabella{colonne}{intestazione}{corpo}
\newcommand{\mdtabella}[3]{%
  \begingroup\fontsize{9}{11.5}\selectfont
  \rowcolors{2}{white}{msverde50}
  \begin{xltabular}{\textwidth}{#1}
  \toprule#2\\\midrule\endfirsthead
  \toprule#2\\\midrule\endhead
  \bottomrule\endfoot
  #3
  \end{xltabular}
  \endgroup\vspace{2mm}}

% ---------------- Utility ---------------------------
\newcommand{\eyebrow}[1]{%
  {\color{msverde}\fontsize{9}{12}\selectfont\bfseries\MakeUppercase{#1}\par}\vspace{1mm}}
\newcommand{\msseparatore}{\par\vspace{4mm}\textcolor{msverde200}{\rule{\textwidth}{0.6pt}}\par\vspace{4mm}}
\newcommand{\dato}[2]{\parbox[t]{\linewidth}{%
  \textcolor{msverde}{\fontsize{8.5}{11}\selectfont\bfseries\MakeUppercase{#1}}\\[2pt]
  \fontsize{13}{16}\selectfont\bfseries#2}}

\AtBeginDocument{%
  \raggedbottom
  \ifms@riservato\hypersetup{pdfkeywords={\ms@l@riservato}}\fi
  \hypersetup{pdftitle={\ms@titolo},pdfauthor={\ms@autore},pdfsubject={\ms@sottotitolo}}}

\endinput
