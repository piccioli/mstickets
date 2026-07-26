<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

final class CollaudoGenerateCommand extends Command
{
    protected $signature = 'collaudo:generate {fase}';

    protected $description = 'Genera il PDF di collaudo per la fase indicata, leggendo il manifest in docs/collaudo/';

    public function handle(): int
    {
        $fase = (string) $this->argument('fase');
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
