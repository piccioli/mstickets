<?php

declare(strict_types=1);

namespace App\Support\Latex;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use RuntimeException;

final class LatexPdfCompiler
{
    /**
     * Tetto di sicurezza sul numero di passate pdflatex, stessa convenzione
     * usata da latexmk: un documento patologico che non converge mai (numero
     * di pagine che continua a oscillare) si ferma qui invece di compilare
     * all'infinito — l'ultimo PDF prodotto viene restituito comunque (meglio
     * un PDF con un possibile piccolo disallineamento residuo che nessun
     * PDF).
     */
    private const int MAX_PASSES = 5;

    /**
     * Compila un sorgente LaTeX completo in PDF, in una directory temporanea
     * isolata che contiene una copia di montagnaservizi.cls e degli asset
     * (logo): pdflatex risolve \documentclass/\includegraphics solo
     * relativamente alla propria working directory. Compila SEMPRE almeno
     * due volte (indice, riferimenti, numero di pagina — footer della classe
     * usa \pageref{LastPage} — richiedono un secondo passaggio per leggere i
     * dati scritti nel file .aux dal primo), poi continua a ricompilare
     * finché il conteggio pagine riportato da pdflatex stesso (riga
     * "Output written on document.pdf (N pages, ...)" del suo output,
     * intercettata invece di invocare un tool esterno come pdfinfo) non si
     * stabilizza fra due passate consecutive, fino a un massimo di
     * self::MAX_PASSES passate totali. Necessario per documenti lunghi
     * (verificato su un PDF combinato reale di 488 pagine): il conteggio
     * pagine può ancora crescere fra la 1ª e la 2ª passata — tipicamente
     * perché i numeri di pagina dell'indice passano da 2 a 3 cifre a metà
     * documento, cambiando gli a-capo — e una 2ª passata da sola compila
     * comunque su riferimenti/indice ormai disallineati rispetto al
     * documento reale. Se il tetto viene raggiunto SENZA che il conteggio si
     * sia stabilizzato (o se pdflatex non riporta più un conteggio
     * leggibile), il metodo restituisce comunque il PDF dell'ultima passata
     * (non lancia mai un'eccezione per questo) ma scrive un
     * `Log::warning(...)` — così un futuro documento troppo grande per
     * convergere in self::MAX_PASSES passate non fallisce silenziosamente
     * con lo stesso tipo di footer/indice sbagliato che questo fix ha
     * riprodotto e corretto.
     *
     * La directory di lavoro viene sempre rimossa prima del ritorno, sia in
     * caso di successo sia in caso di errore — inclusi eventuali errori
     * durante la preparazione stessa (creazione directory, copia di
     * montagnaservizi.cls/assets, scrittura del sorgente): tutta la
     * preparazione avviene dentro lo stesso blocco try/catch che governa la
     * compilazione, verificando esplicitamente l'esito di ogni operazione
     * File — questi metodi non lanciano eccezioni proprie in caso di
     * fallimento (wrappano mkdir()/copy(), che restituiscono `false`
     * silenziosamente), quindi il controllo va fatto a mano perché il catch
     * scatti davvero. Non deve mai restare traccia di una directory
     * ms-latex-* in sys_get_temp_dir() dopo una chiamata a compile(),
     * qualunque sia l'esito. In caso di successo, il PDF prodotto viene
     * prima estratto in un file temporaneo indipendente (fuori dalla
     * directory di lavoro, con un prefisso diverso da "ms-latex-") di cui il
     * chiamante è responsabile: quel file non viene ripulito da questa
     * classe.
     *
     * @return string path assoluto al PDF compilato (file temporaneo
     *                indipendente dalla directory di lavoro, già rimossa)
     */
    public function compile(string $texSource): string
    {
        $workDir = sys_get_temp_dir().'/ms-latex-'.Str::random(16);

        try {
            if (! File::makeDirectory($workDir, recursive: true)) {
                throw new RuntimeException("Impossibile creare la directory di lavoro temporanea [{$workDir}].");
            }

            if (! File::copy(resource_path('latex/montagnaservizi.cls'), $workDir.'/montagnaservizi.cls')) {
                throw new RuntimeException('Impossibile copiare montagnaservizi.cls nella directory di lavoro.');
            }

            if (! File::copyDirectory(resource_path('latex/assets'), $workDir.'/assets')) {
                throw new RuntimeException('Impossibile copiare gli asset LaTeX nella directory di lavoro.');
            }

            if (File::put($workDir.'/document.tex', $texSource) === false) {
                throw new RuntimeException('Impossibile scrivere il sorgente LaTeX nella directory di lavoro.');
            }

            $previousPageCount = $this->runPdflatex($workDir);
            $pageCount = $this->runPdflatex($workDir);
            $pass = 2;

            while (
                $pageCount !== null
                && $pageCount !== $previousPageCount
                && $pass < self::MAX_PASSES
            ) {
                $previousPageCount = $pageCount;
                $pageCount = $this->runPdflatex($workDir);
                $pass++;
            }

            // Il loop sopra esce per tre motivi possibili: (1) convergenza
            // reale (pageCount === previousPageCount, il caso comune); (2)
            // nessun segnale di convergenza disponibile (pageCount === null,
            // vedi parsePageCount()); (3) tetto self::MAX_PASSES raggiunto
            // MENTRE il conteggio pagine stava ancora cambiando. (2) e (3)
            // sono entrambi "non ho la certezza che il PDF finale sia
            // corretto" — esattamente la categoria di difetto (footer/indice
            // con numeri di pagina sbagliati) che questo fix ha appena
            // riprodotto e corretto sul documento reale di 488 pagine:
            // restituire comunque il PDF (mai bloccare la generazione), ma
            // loggare un warning per rendere il caso osservabile invece di
            // silenzioso — un futuro documento anche più grande di questo
            // potrebbe non convergere nemmeno in 5 passate.
            if ($pageCount === null || $pageCount !== $previousPageCount) {
                Log::warning('pdflatex: conteggio pagine non convergente dopo il numero massimo di passate', [
                    'work_dir' => $workDir,
                    'passes' => $pass,
                    'previous_page_count' => $previousPageCount,
                    'last_page_count' => $pageCount,
                ]);
            }
        } catch (RuntimeException $e) {
            File::deleteDirectory($workDir);
            throw $e;
        }

        $pdfPath = $workDir.'/document.pdf';

        if (! File::exists($pdfPath)) {
            $log = File::exists($workDir.'/document.log')
                ? File::get($workDir.'/document.log')
                : '(nessun file di log prodotto)';
            File::deleteDirectory($workDir);

            throw new RuntimeException(
                "pdflatex non ha prodotto un PDF. Coda del log:\n".self::tail($log),
            );
        }

        $finalPath = tempnam(sys_get_temp_dir(), 'latex-pdf-');

        if ($finalPath === false) {
            File::deleteDirectory($workDir);

            throw new RuntimeException('Impossibile allocare un file temporaneo per il PDF compilato.');
        }

        if (! File::copy($pdfPath, $finalPath)) {
            File::deleteDirectory($workDir);

            throw new RuntimeException('Impossibile copiare il PDF compilato nel file temporaneo finale.');
        }

        File::deleteDirectory($workDir);

        return $finalPath;
    }

    /**
     * @return int|null il conteggio pagine annunciato da pdflatex per questa
     *                  passata (riga "Output written on document.pdf (N
     *                  pages, ...)" del suo stdout), o null se quella riga
     *                  non è presente nell'output (es. un documento talmente
     *                  piccolo/particolare da non produrla ancora — in quel
     *                  caso il chiamante interrompe il loop di passate
     *                  aggiuntive, non essendoci un segnale di convergenza
     *                  da osservare)
     */
    private function runPdflatex(string $workDir): ?int
    {
        $result = Process::path($workDir)
            ->timeout(120)
            ->run([
                'pdflatex',
                '-interaction=nonstopmode',
                '-halt-on-error',
                'document.tex',
            ]);

        if ($result->failed()) {
            $log = File::exists($workDir.'/document.log')
                ? File::get($workDir.'/document.log')
                : $result->output();

            throw new RuntimeException(
                "pdflatex ha fallito la compilazione. Coda del log:\n".self::tail($log),
            );
        }

        return self::parsePageCount($result->output());
    }

    private static function parsePageCount(string $output): ?int
    {
        if (preg_match('/Output written on \S+\.pdf \((\d+) pages?,/', $output, $matches) === 1) {
            return (int) $matches[1];
        }

        return null;
    }

    private static function tail(string $text, int $lines = 40): string
    {
        $allLines = explode("\n", $text);

        return implode("\n", array_slice($allLines, -$lines));
    }
}
