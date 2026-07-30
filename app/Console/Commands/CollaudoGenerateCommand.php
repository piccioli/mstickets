<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\Collaudo\CollaudoTestReference;
use App\Support\Latex\LatexEscaper;
use App\Support\Latex\LatexPdfCompiler;
use App\Support\Latex\MarkdownToLatexConverter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

final class CollaudoGenerateCommand extends Command
{
    protected $signature = 'collaudo:generate {fase}';

    protected $description = 'Genera il PDF di collaudo per la fase indicata, leggendo il manifest in docs/collaudo/';

    /**
     * File del manuale operativo dettagliato (§ collaudo v2), nell'ordine in cui compaiono nel PDF.
     * Convenzione fissa per ora (non parametrizzata su {fase}): introdotta per la Fase 0-1, estesa
     * in v0.3.0 con la Fase 1A senza cambiare l'argomento CLI (resta `0-1`) — quando una fase futura
     * richiederà una convenzione diversa, andrà rivista qui.
     *
     * @var list<array{file: string, titolo: string}>
     */
    private const DETAILED_FILES = [
        ['file' => 'README.md', 'titolo' => 'Indice del pacchetto'],
        ['file' => '00-istruzioni-generali.md', 'titolo' => 'Istruzioni generali'],
        ['file' => '01-matrice-tracciabilita.md', 'titolo' => 'Matrice di tracciabilità'],
        ['file' => '02-fase-0.md', 'titolo' => 'Fase 0 (Fondazioni) — Casi di test dettagliati'],
        ['file' => '03-fase-1.md', 'titolo' => 'Fase 1 (Ticketing core) — Casi di test dettagliati'],
        ['file' => '04-fase-1a.md', 'titolo' => 'Fase 1A (Landing, Login, Recupero password) — Casi di test dettagliati'],
        ['file' => '05-registro-esiti.md', 'titolo' => 'Registro degli esiti'],
        ['file' => '06-verbale-collaudo.md', 'titolo' => 'Verbale conclusivo di collaudo'],
    ];

    public function handle(): int
    {
        $fase = (string) $this->argument('fase');

        if ($fase === '0-1' && $this->hasDetailedDocs()) {
            $path = $this->buildDetailedPdf($fase);
            $this->info("PDF dettagliato generato: {$path}");

            return self::SUCCESS;
        }

        $manifestPath = base_path("docs/collaudo/fase-{$fase}.php");

        if (! file_exists($manifestPath)) {
            $this->error("Manifest non trovato: {$manifestPath}");

            return self::FAILURE;
        }

        $manifest = require $manifestPath;
        $path = $this->buildPdf($fase, $manifest);
        $this->info("PDF generato: {$path}");

        return self::SUCCESS;
    }

    private function hasDetailedDocs(): bool
    {
        foreach (self::DETAILED_FILES as $entry) {
            if (! file_exists(base_path("docs/collaudo/{$entry['file']}"))) {
                return false;
            }
        }

        return true;
    }

    /**
     * Rende in un unico PDF LaTeX il manuale operativo dettagliato (README + istruzioni generali +
     * matrice + casi di test di Fase 0/1 + registro esiti + verbale), convertendo ciascun file
     * Markdown in LaTeX (`MarkdownToLatexConverter`) e separando le sezioni con un'interruzione di
     * pagina. Ogni file diventa una `\section{}` (voce reale nell'indice generato da `\indicedoc`),
     * quindi il proprio h1 interno va rimosso prima della conversione (`stripOwnTitle`) per non
     * duplicare il titolo.
     */
    public function buildDetailedPdf(string $fase): string
    {
        $sections = array_map(
            static fn (array $entry): array => [
                'titolo' => LatexEscaper::escape($entry['titolo']),
                'latex' => (new MarkdownToLatexConverter)->convert(
                    self::stripOwnTitle(file_get_contents(base_path("docs/collaudo/{$entry['file']}"))),
                ),
            ],
            self::DETAILED_FILES,
        );

        $tex = view('latex.collaudo-dettagliato', [
            'titolo' => LatexEscaper::escape(
                'Fase 0 (Fondazioni) + Fase 1 (Ticketing core) + Fase 1A (Landing, Login, Recupero password)',
            ),
            'sections' => $sections,
        ])->render();

        $pdfPath = app(LatexPdfCompiler::class)->compile($tex);

        $filename = sprintf('collaudo-dettagliato-fase-%s-%s.pdf', $fase, now()->format('Ymd-His'));
        $disk = Storage::build(['driver' => 'local', 'root' => storage_path('app')]);
        $disk->put("collaudo/{$filename}", file_get_contents($pdfPath));
        File::delete($pdfPath);

        return storage_path("app/collaudo/{$filename}");
    }

    /**
     * Ogni file di docs/collaudo/ apre con un h1 (`# Titolo...`) che duplica il titolo già
     * mostrato nell'indice del documento combinato (colonna "titolo" di DETAILED_FILES): senza
     * questa rimozione, il PDF LaTeX mostrerebbe lo stesso titolo due volte consecutive (una volta
     * come \section{} dell'indice generale, una volta come \section{} convertito dal primo h1 del
     * file). dompdf/HTML non aveva questo problema perché l'h1 diventava semplicemente un
     * sottotitolo visivo dentro la sezione, mai un ingresso nell'indice — qui invece ogni
     * \section{} genera una voce di indice reale (\indicedoc), quindi il duplicato sarebbe
     * visibile due volte anche lì.
     */
    private static function stripOwnTitle(string $markdown): string
    {
        return (string) preg_replace('/^# .+\n+/', '', $markdown, limit: 1);
    }

    /**
     * @param  array<string, mixed>  $manifest
     */
    public function buildPdf(string $fase, array $manifest): string
    {
        $credenziali = array_map(
            static fn (array $cred): array => [
                'ruolo' => LatexEscaper::escape($cred['ruolo']),
                'email' => LatexEscaper::escape($cred['email']),
                'password' => LatexEscaper::escape($cred['password']),
            ],
            $manifest['parte_1']['credenziali'],
        );

        $topics = array_map(
            static fn (array $topic): array => [
                'titolo' => LatexEscaper::escape($topic['titolo']),
                'test' => array_map(
                    static fn (array $test): array => [
                        'id' => LatexEscaper::escape($test['id']),
                        'descrizione' => LatexEscaper::escape($test['descrizione']),
                        // Solo il percorso del file (mai la descrizione dopo '::'), stesso
                        // comportamento della vecchia vista dompdf rimossa nel Task 4: la
                        // colonna "Test automatico" resterebbe altrimenti troppo larga/
                        // ridondante con la colonna "Descrizione" già presente.
                        //
                        // \allowbreak{} dopo ogni '/' (inserito DOPO l'escape, mai prima:
                        // LatexEscaper::escape() non tocca '/', quindi l'ordine non fa
                        // differenza per quel carattere, ma un \allowbreak letterale
                        // inserito PRIMA verrebbe altrimenti mangiato dall'escape del
                        // backslash che lo precede) — verificato end-to-end compilando
                        // davvero un manifest reale (fix v0.3.2): senza, \texttt{} in una
                        // colonna p{} di larghezza fissa non ha alcun punto di interruzione
                        // (un percorso è una singola "parola" senza spazi per l'algoritmo di
                        // wrap dei paragrafi), quindi un percorso più lungo della colonna
                        // (es. "tests/Feature/Filament/Auth/PasswordResetTest.php") non va a
                        // capo ma sborda oltre il margine della cella, con l'effetto
                        // osservato di caratteri di coda persi/sovrapposti nell'estrazione
                        // testo del PDF risultante.
                        'test_automatico' => str_replace(
                            '/',
                            '/\allowbreak{}',
                            LatexEscaper::escape(CollaudoTestReference::file($test['test_automatico'])),
                        ),
                    ],
                    $topic['test'],
                ),
            ],
            $manifest['topics'],
        );

        $tex = view('latex.collaudo', [
            'titolo' => LatexEscaper::escape($manifest['titolo']),
            'appUrl' => LatexEscaper::escape($manifest['parte_1']['app_url']),
            'mailpitUrl' => LatexEscaper::escape($manifest['parte_1']['mailpit_url']),
            'credenziali' => $credenziali,
            'topics' => $topics,
        ])->render();

        $pdfPath = app(LatexPdfCompiler::class)->compile($tex);

        $filename = sprintf('collaudo-fase-%s-%s.pdf', $fase, now()->format('Ymd-His'));
        $disk = Storage::build(['driver' => 'local', 'root' => storage_path('app')]);
        $disk->put("collaudo/{$filename}", file_get_contents($pdfPath));
        File::delete($pdfPath);

        return storage_path("app/collaudo/{$filename}");
    }
}
