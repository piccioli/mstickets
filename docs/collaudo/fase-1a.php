<?php

declare(strict_types=1);

// Manifest di tracciabilità per il collaudo (UAT) di Fase 1A (Landing, Login, Recupero
// password — addendum contenuto alla Fase 1, v0.3.0). Ogni voce collega un criterio di
// accettazione a un test automatico REALMENTE esistente in tests/ (verificato da
// `collaudo:verify-manifest 1a`). Manifest separato da `fase-0-1.php` (Fase 0 + Fase 1)
// per non toccare le 130 voci già verificate e stabili. Questo file è puro dato: nessuna
// logica.

return [
    'fase' => '1a',
    'titolo' => 'Fase 1A (Landing, Login, Recupero password)',
    'parte_1' => [
        'app_url' => 'https://ticket-uat.montagnaservizi.com',
        'mailpit_url' => 'https://mailpit-ticket-uat.montagnaservizi.com',
        'credenziali' => [
            ['ruolo' => 'Admin', 'email' => 'admin@orchestrator.local', 'password' => 'password'],
            ['ruolo' => 'Developer', 'email' => 'developer@orchestrator.local', 'password' => 'password'],
            ['ruolo' => 'Manager', 'email' => 'manager@orchestrator.local', 'password' => 'password'],
            ['ruolo' => 'Customer', 'email' => 'customer@orchestrator.local', 'password' => 'password'],
            ['ruolo' => 'Fundraising', 'email' => 'fundraising@orchestrator.local', 'password' => 'password'],
        ],
    ],
    'topics' => [
        [
            'titolo' => 'Landing pubblica',
            'test' => [
                [
                    'id' => 'F1A-01',
                    'descrizione' => 'La landing "/" è raggiungibile da un visitatore anonimo con una sola CTA',
                    'test_automatico' => 'tests/Feature/Http/LandingControllerTest.php::un visitatore anonimo vede la landing pubblica',
                ],
                [
                    'id' => 'F1A-02',
                    'descrizione' => 'Un utente con sessione attiva che visita "/" viene rimandato alla dashboard',
                    'test_automatico' => 'tests/Feature/Http/LandingControllerTest.php::un utente con sessione attiva viene rimandato alla dashboard del pannello',
                ],
            ],
        ],
        [
            'titolo' => 'Login',
            'test' => [
                [
                    'id' => 'F1A-03',
                    'descrizione' => 'Aspetto della pagina di login conforme al design Montagna Servizi',
                    'test_automatico' => 'tests/Feature/Filament/Auth/LoginTest.php::la pagina di login renderizza il layout custom',
                ],
                [
                    'id' => 'F1A-04',
                    'descrizione' => 'Credenziali corrette autenticano e portano alla dashboard',
                    'test_automatico' => 'tests/Feature/Filament/Auth/LoginTest.php::credenziali corrette autenticano e reindirizzano alla dashboard',
                ],
                [
                    'id' => 'F1A-05',
                    'descrizione' => 'Credenziali errate mostrano un messaggio di errore e non autenticano',
                    'test_automatico' => 'tests/Feature/Filament/Auth/LoginTest.php::credenziali errate mostrano un errore e non autenticano',
                ],
                [
                    'id' => 'F1A-06',
                    'descrizione' => 'Il toggle "Mostra/Nascondi password" è presente nel markup (comportamento client verificato manualmente)',
                    'test_automatico' => 'tests/Feature/Filament/Auth/LoginTest.php::la vista contiene il toggle Alpine per mostrare/nascondere la password (nessuna reimplementazione JS del campo)',
                ],
                [
                    'id' => 'F1A-07',
                    'descrizione' => '"Salva per le prossime sessioni" mantiene l\'accesso dopo la chiusura del browser',
                    'test_automatico' => 'tests/Feature/Filament/Auth/LoginTest.php::"salva per le prossime sessioni" valorizza il remember token e mantiene la sessione',
                ],
                [
                    'id' => 'F1A-08',
                    'descrizione' => 'Dopo 5 tentativi di login falliti, il sesto viene bloccato temporaneamente',
                    'test_automatico' => 'tests/Feature/Filament/Auth/LoginTest.php::il sesto tentativo di login consecutivo viene bloccato dal rate limiting nativo',
                ],
            ],
        ],
        [
            'titolo' => 'Recupero password',
            'test' => [
                [
                    'id' => 'F1A-09',
                    'descrizione' => 'Richiesta di reset con un\'email registrata invia il link ed è visibile su Mailpit',
                    'test_automatico' => 'tests/Feature/Filament/Auth/PasswordResetTest.php::richiedere il reset con una email registrata invia la notifica e mostra il pannello "controlla la casella"',
                ],
                [
                    'id' => 'F1A-10',
                    'descrizione' => 'Richiesta di reset con un\'email inesistente non rivela l\'assenza dell\'utente',
                    'test_automatico' => 'tests/Feature/Filament/Auth/PasswordResetTest.php::richiedere il reset con una email inesistente non invia notifiche ma non rivela l\\\'assenza dell\\\'utente',
                ],
                [
                    'id' => 'F1A-11',
                    'descrizione' => '"Invia di nuovo" immediato è bloccato dal throttling nativo (60 secondi)',
                    'test_automatico' => 'tests/Feature/Filament/Auth/PasswordResetTest.php::"invia di nuovo" immediato è bloccato dal throttling nativo del broker password (60s)',
                ],
                [
                    'id' => 'F1A-12',
                    'descrizione' => 'Impostare una nuova password con un token valido, rispettando le regole reali',
                    'test_automatico' => 'tests/Feature/Filament/Auth/PasswordResetTest.php::un token valido permette di impostare una nuova password rispettando le regole reali',
                ],
                [
                    'id' => 'F1A-13',
                    'descrizione' => 'Una password che non rispetta le regole reali viene rifiutata',
                    'test_automatico' => 'tests/Feature/Filament/Auth/PasswordResetTest.php::una password che non rispetta le regole reali (min 8, maiuscola, numero) viene rifiutata',
                ],
                [
                    'id' => 'F1A-14',
                    'descrizione' => 'Un link di reset già usato o inesistente viene rifiutato',
                    'test_automatico' => 'tests/Feature/Filament/Auth/PasswordResetTest.php::un token inesistente o già consumato viene rifiutato con una notifica nativa, nessun reset silenzioso',
                ],
                [
                    'id' => 'F1A-15',
                    'descrizione' => 'Un link di reset scaduto (oltre 60 minuti) viene rifiutato',
                    'test_automatico' => 'tests/Feature/Filament/Auth/PasswordResetTest.php::un token scaduto oltre i 60 minuti configurati viene rifiutato',
                ],
            ],
        ],
        [
            'titolo' => 'Identità visiva e separazione dai temi',
            'test' => [
                [
                    'id' => 'F1A-16',
                    'descrizione' => 'Le pagine pubbliche usano il design system "marketing", il pannello interno resta sul tema teal',
                    'test_automatico' => 'tests/Feature/Http/MarketingAssetsSeparationTest.php::la landing pubblica carica il css marketing, non il tema teal del pannello',
                ],
            ],
        ],
    ],
];
