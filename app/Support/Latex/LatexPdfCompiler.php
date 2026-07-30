<?php

declare(strict_types=1);

namespace App\Support\Latex;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use RuntimeException;

final class LatexPdfCompiler
{
    /**
     * Compila un sorgente LaTeX completo in PDF, in una directory temporanea
     * isolata che contiene una copia di montagnaservizi.cls e degli asset
     * (logo): pdflatex risolve \documentclass/\includegraphics solo
     * relativamente alla propria working directory. Compila due volte
     * (indice, riferimenti, numero di pagina — footer della classe usa
     * \pageref{LastPage}), coerente con la nota "Compilazione" del README
     * della classe stessa.
     *
     * La directory di lavoro viene sempre rimossa prima del ritorno, sia in
     * caso di successo sia in caso di errore: non deve mai restarne traccia
     * in sys_get_temp_dir() dopo una chiamata a compile(). In caso di
     * successo, il PDF prodotto viene prima estratto in un file temporaneo
     * indipendente (fuori dalla directory di lavoro, con un prefisso
     * diverso da "ms-latex-") di cui il chiamante è responsabile: quel file
     * non viene ripulito da questa classe.
     *
     * @return string path assoluto al PDF compilato (file temporaneo
     *                indipendente dalla directory di lavoro, già rimossa)
     */
    public function compile(string $texSource): string
    {
        $workDir = sys_get_temp_dir().'/ms-latex-'.Str::random(16);
        File::makeDirectory($workDir, recursive: true);

        File::copy(resource_path('latex/montagnaservizi.cls'), $workDir.'/montagnaservizi.cls');
        File::copyDirectory(resource_path('latex/assets'), $workDir.'/assets');
        File::put($workDir.'/document.tex', $texSource);

        try {
            $this->runPdflatex($workDir);
            $this->runPdflatex($workDir);
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

        File::copy($pdfPath, $finalPath);
        File::deleteDirectory($workDir);

        return $finalPath;
    }

    private function runPdflatex(string $workDir): void
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
    }

    private static function tail(string $text, int $lines = 40): string
    {
        $allLines = explode("\n", $text);

        return implode("\n", array_slice($allLines, -$lines));
    }
}
