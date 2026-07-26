<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Helvetica, Arial, sans-serif; font-size: 10px; color: #1a1a1a; line-height: 1.45; }
        h1 { font-size: 20px; margin-bottom: 4px; color: #17a180; }
        h2 { font-size: 15px; margin-top: 20px; border-bottom: 1px solid #ccc; padding-bottom: 4px; }
        h3 { font-size: 12px; margin-top: 16px; color: #17a180; }
        h4 { font-size: 11px; margin-top: 10px; }
        p { margin: 6px 0; }
        table { width: 100%; table-layout: fixed; border-collapse: collapse; margin: 8px 0 12px; font-size: 9px; }
        td, th {
            padding: 3px 5px; border: 1px solid #ddd; text-align: left; vertical-align: top;
            word-wrap: break-word; overflow-wrap: break-word; word-break: break-word;
        }
        th { background-color: #f2f2f2; }
        blockquote { color: #555; font-size: 9px; border-left: 3px solid #ccc; padding-left: 8px; margin: 8px 0; }
        code { background-color: #f5f5f5; padding: 0 2px; }
        .section-break { page-break-before: always; }
        .cover .meta { color: #555; font-size: 10px; }
        .cover .toc { margin-top: 16px; }
    </style>
</head>
<body>
    <div class="cover">
        <h1>Montagna Servizi S.C.p.A.</h1>
        <p><strong>Documento di collaudo — {{ $titolo }}</strong></p>
        <p class="meta">Versione dettagliata (manuale operativo), generata il {{ $generatedAt }}.</p>
        <p class="meta">Pacchetto completo: indice del pacchetto, istruzioni generali, matrice di tracciabilità,
            casi di test dettagliati di Fase 0 e Fase 1, registro degli esiti, verbale conclusivo di collaudo.</p>
        <div class="toc">
            <strong>Contenuto di questo documento, nell'ordine:</strong>
            <ol>
                @foreach ($sections as $section)
                    <li>{{ $section['titolo'] }}</li>
                @endforeach
            </ol>
        </div>
    </div>

    @foreach ($sections as $section)
        <div class="section-break">
            {!! $section['html'] !!}
        </div>
    @endforeach
</body>
</html>
