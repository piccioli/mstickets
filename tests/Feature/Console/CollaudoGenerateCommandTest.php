<?php

declare(strict_types=1);

use App\Console\Commands\CollaudoGenerateCommand;

it('genera un pdf di collaudo con copertina e sezioni per topic', function () {
    $manifest = [
        'fase' => '0-1',
        'titolo' => 'Fase 0 + Fase 1',
        'parte_1' => [
            'app_url' => 'https://ticket-uat.montagnaservizi.com',
            'mailpit_url' => 'https://mailpit-ticket-uat.montagnaservizi.com',
            'credenziali' => [
                ['ruolo' => 'Admin', 'email' => 'admin@example.test', 'password' => 'password'],
            ],
        ],
        'topics' => [
            [
                'titolo' => 'Autenticazione',
                'test' => [
                    [
                        'id' => 'F0-01',
                        'descrizione' => 'Login con utente admin',
                        'test_automatico' => 'tests/Feature/Filament/AdminAccessTest.php::it can access panel',
                    ],
                ],
            ],
        ],
    ];

    $path = app(CollaudoGenerateCommand::class)->buildPdf('0-1', $manifest);

    expect($path)->toBeFile();
    expect(file_get_contents($path))->toStartWith('%PDF');
});

it('genera un pdf di collaudo correttamente escapato quando il manifest contiene caratteri speciali latex', function () {
    $manifest = [
        'fase' => '0-1',
        'titolo' => 'Fase 0 + Fase 1',
        'parte_1' => [
            'app_url' => 'https://ticket-uat.montagnaservizi.com',
            'mailpit_url' => 'https://mailpit-ticket-uat.montagnaservizi.com',
            'credenziali' => [
                ['ruolo' => 'Admin & Root', 'email' => 'admin_100%@example.test', 'password' => 'pa$$word'],
            ],
        ],
        'topics' => [
            [
                'titolo' => 'Permessi ticket.update.* e #priorità',
                'test' => [
                    [
                        'id' => 'F0-01',
                        'descrizione' => 'Verifica accesso — 100% completo',
                        'test_automatico' => 'tests/Feature/X.php::it works',
                    ],
                ],
            ],
        ],
    ];

    $path = app(CollaudoGenerateCommand::class)->buildPdf('0-1', $manifest);

    expect($path)->toBeFile();
    expect(file_get_contents($path))->toStartWith('%PDF');

    $text = shell_exec('pdftotext -layout '.escapeshellarg($path).' -');

    expect($text)->toContain('Admin & Root');
    expect($text)->toContain('admin_100%@example.test');
    expect($text)->toContain('ticket.update.* e #priorità');
});

it('include nel pdf sintetico la tabella dei test numerati per ogni argomento, non solo il conteggio', function () {
    // Finding Critico della review finale (v0.3.2): il template renderizzava solo
    // "Titolo (N test)" per argomento, perdendo silenziosamente l'elenco dei singoli
    // test numerati che CLAUDE.md (§ "Processo di collaudo") richiede esplicitamente
    // e che CollaudoGenerateCommand::buildPdf() riceve già dal manifest ma non passava
    // mai alla vista in forma tabellare. Questo test estrae il testo reale del PDF
    // compilato (pdftotext) e verifica che l'ID e la descrizione di un test reale del
    // manifest siano effettivamente visibili nell'output, non solo che il PDF esista.
    $manifest = [
        'fase' => '0-1',
        'titolo' => 'Fase 0 + Fase 1',
        'parte_1' => [
            'app_url' => 'https://ticket-uat.montagnaservizi.com',
            'mailpit_url' => 'https://mailpit-ticket-uat.montagnaservizi.com',
            'credenziali' => [
                ['ruolo' => 'Admin', 'email' => 'admin@example.test', 'password' => 'password'],
            ],
        ],
        'topics' => [
            [
                'titolo' => 'Autenticazione',
                'test' => [
                    [
                        'id' => 'F0-01',
                        'descrizione' => 'Login con utente admin',
                        'test_automatico' => 'tests/Feature/Filament/AdminAccessTest.php::it can access panel',
                    ],
                ],
            ],
        ],
    ];

    $path = app(CollaudoGenerateCommand::class)->buildPdf('0-1', $manifest);

    expect($path)->toBeFile();

    $text = shell_exec('pdftotext -layout '.escapeshellarg($path).' -');

    expect($text)->toContain('F0-01');
    expect($text)->toContain('Login con utente admin');
    // La descrizione del test automatico (dopo '::') non va mostrata: la colonna
    // "Test automatico" riporta solo il percorso del file (CollaudoTestReference::file()),
    // stesso comportamento della vecchia vista dompdf rimossa nel Task 4.
    expect($text)->not->toContain('it can access panel');

    // Il percorso può legittimamente andare a capo dopo un '/' (colonna "Test automatico"
    // stretta + \allowbreak inserito apposta dal Command per evitare che \texttt{}, senza
    // alcun punto di interruzione disponibile, sbordi dal margine della pagina perdendo
    // caratteri di coda — bug reale osservato compilando un manifest vero con un percorso
    // più lungo, fix v0.3.2): verificare quindi la presenza dei frammenti significativi,
    // non l'intera stringa contigua (che l'a-capo renderebbe un'asserzione fragile legata
    // alla larghezza esatta della colonna).
    expect($text)->toContain('tests/Feature/Filament');
    expect($text)->toContain('AdminAccessTest.php');
});

it('renderizza verbatim gli url reali di app/mailpit anche se passano da LatexEscaper prima di \msurl (documenta il comportamento attuale, asimmetrico ma sicuro con i caratteri reali)', function () {
    // Finding Minor della review finale: LatexEscaper NON dovrebbe mai escapare un
    // URL (corromperebbe l'argomento di \href{}), ma CollaudoGenerateCommand::buildPdf()
    // fa comunque passare app_url/mailpit_url per LatexEscaper::escape() prima di darli
    // a \msurl{} (che usa la stringa sia come target \href sia come testo visualizzato).
    // Oggi questo è benigno solo perché nessun URL reale di produzione contiene uno dei
    // caratteri della mappa di LatexEscaper (~ ^ \ % & # _ $ { }): questo test usa gli
    // URL reali dei manifest di produzione (docs/collaudo/fase-0-1.php/fase-1a.php) per
    // bloccare questo comportamento, così una futura estensione della mappa che rompesse
    // un URL reale verrebbe scoperta qui invece che silenziosamente in un PDF reale.
    $manifest = [
        'fase' => '0-1',
        'titolo' => 'Fase 0 + Fase 1',
        'parte_1' => [
            'app_url' => 'https://ticket-uat.montagnaservizi.com',
            'mailpit_url' => 'https://mailpit-ticket-uat.montagnaservizi.com',
            'credenziali' => [
                ['ruolo' => 'Admin', 'email' => 'admin@example.test', 'password' => 'password'],
            ],
        ],
        'topics' => [
            [
                'titolo' => 'Autenticazione',
                'test' => [
                    ['id' => 'F0-01', 'descrizione' => 'Login', 'test_automatico' => 'tests/X.php::y'],
                ],
            ],
        ],
    ];

    $path = app(CollaudoGenerateCommand::class)->buildPdf('0-1', $manifest);

    $text = shell_exec('pdftotext -layout '.escapeshellarg($path).' -');

    expect($text)->toContain('https://ticket-uat.montagnaservizi.com');
    expect($text)->toContain('https://mailpit-ticket-uat.montagnaservizi.com');
});
