# Classe LaTeX Montagna Servizi

Importata dal progetto Claude Design "Template LaTeX Montagna Servizi"
(https://claude.ai/design/p/7e82d898-e948-4dd2-905f-c557482245ca) il 30/07/2026,
poi corretta: la versione originale non compilava su 6 dei 13 costrutti
(vedi CLAUDE.md, sezione "Generazione PDF di collaudo via pdfLaTeX (v0.3.2,
sostituisce dompdf)", per i dettagli dei bug e dei fix). Non ri-sincronizzare
da quel progetto senza riapplicare i fix o verificare che siano stati
applicati anche lì.

Usata da `App\Support\Latex\LatexPdfCompiler` per compilare i PDF di
collaudo generati da `php artisan collaudo:generate`. Motore: pdfLaTeX
(TeX Live), non installato di default: vedi `docker/php/Dockerfile`.
