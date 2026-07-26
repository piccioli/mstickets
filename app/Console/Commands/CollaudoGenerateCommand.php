<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class CollaudoGenerateCommand extends Command
{
    protected $signature = 'collaudo:generate {fase}';

    protected $description = 'Genera il PDF di collaudo per la fase indicata, leggendo il manifest in docs/collaudo/';

    /**
     * File del manuale operativo dettagliato (§ collaudo v2), nell'ordine in cui compaiono nel PDF.
     * Convenzione fissa per ora (non parametrizzata su {fase}): introdotta per la Fase 0-1, non
     * ancora generalizzata alle fasi future — quando servirà, andrà rivista qui.
     *
     * @var list<array{file: string, titolo: string}>
     */
    private const DETAILED_FILES = [
        ['file' => 'README.md', 'titolo' => 'Indice del pacchetto'],
        ['file' => '00-istruzioni-generali.md', 'titolo' => 'Istruzioni generali'],
        ['file' => '01-matrice-tracciabilita.md', 'titolo' => 'Matrice di tracciabilità'],
        ['file' => '02-fase-0.md', 'titolo' => 'Fase 0 (Fondazioni) — Casi di test dettagliati'],
        ['file' => '03-fase-1.md', 'titolo' => 'Fase 1 (Ticketing core) — Casi di test dettagliati'],
        ['file' => '04-registro-esiti.md', 'titolo' => 'Registro degli esiti'],
        ['file' => '05-verbale-collaudo.md', 'titolo' => 'Verbale conclusivo di collaudo'],
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
     * Rende in un unico PDF il manuale operativo dettagliato (README + istruzioni generali +
     * matrice + casi di test di Fase 0/1 + registro esiti + verbale), convertendo ciascun file
     * Markdown in HTML (`Str::markdown`, GitHub-Flavored: tabelle incluse) e separando le sezioni
     * con un'interruzione di pagina.
     */
    public function buildDetailedPdf(string $fase): string
    {
        $sections = array_map(
            static fn (array $entry): array => [
                'titolo' => $entry['titolo'],
                'html' => Str::markdown(
                    self::separateBoldLabelsIntoOwnParagraph(
                        file_get_contents(base_path("docs/collaudo/{$entry['file']}")),
                    ),
                    ['html_input' => 'strip', 'allow_unsafe_links' => false],
                ),
            ],
            self::DETAILED_FILES,
        );

        $pdf = Pdf::loadView('pdf.collaudo-dettagliato', [
            'titolo' => 'Fase 0 (Fondazioni) + Fase 1 (Ticketing core)',
            'generatedAt' => now()->translatedFormat('d/m/Y H:i'),
            'sections' => $sections,
        ]);

        $filename = sprintf('collaudo-dettagliato-fase-%s-%s.pdf', $fase, now()->format('Ymd-His'));
        $disk = Storage::build(['driver' => 'local', 'root' => storage_path('app')]);
        $disk->put("collaudo/{$filename}", $pdf->output());

        return storage_path("app/collaudo/{$filename}");
    }

    /**
     * I casi di test (§ template di collaudo) separano l'etichetta in grassetto dal contenuto con
     * un solo a-capo (`**Obiettivo**\nTesto...`), non una riga vuota. CommonMark tratta un singolo
     * a-capo come un semplice spazio nello stesso paragrafo, fondendo visivamente etichetta e
     * testo. Questo inserisce una riga vuota reale SOLO dopo una riga che è per intero
     * `**Etichetta**` (mai dentro una frase con grassetto inline), lasciando invariati i paragrafi
     * discorsivi genuinamente spezzati su più righe sorgente per leggibilità (che devono continuare
     * a fluire come un unico paragrafo, non riga per riga).
     */
    private static function separateBoldLabelsIntoOwnParagraph(string $markdown): string
    {
        return (string) preg_replace('/^(\*\*[^\n*]+\*\*)\n(?!\n)/m', "$1\n\n", $markdown);
    }

    /**
     * @param  array<string, mixed>  $manifest
     */
    public function buildPdf(string $fase, array $manifest): string
    {
        $pdf = Pdf::loadView('pdf.collaudo', ['manifest' => $manifest]);
        $filename = sprintf('collaudo-fase-%s-%s.pdf', $fase, now()->format('Ymd-His'));
        $disk = Storage::build(['driver' => 'local', 'root' => storage_path('app')]);
        $disk->put("collaudo/{$filename}", $pdf->output());

        return storage_path("app/collaudo/{$filename}");
    }
}
