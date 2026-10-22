<?php

declare(strict_types=1);

// Manifest di tracciabilità per il collaudo (UAT) di Fase 10 (Area Sezioni, Gruppi Regionali e Organi
// Tecnici). La fase procede una feature alla volta (docs/project/tasks/fase-10-roadmap.md): il manifest
// cresce con un argomento per feature, e ogni argomento raggruppa i criteri delle sue user story. Ogni voce
// collega un criterio a un test automatico REALMENTE esistente in tests/, verificato da
// `collaudo:verify-manifest 10`. Manuale narrativo e PDF si scrivono a fine fase.

return [
    'fase' => '10',
    'titolo' => 'Fase 10 (Area Sezioni, Gruppi Regionali e Organi Tecnici)',
    'parte_1' => [
        'app_url' => 'https://ticket-uat.montagnaservizi.com',
        'mailpit_url' => 'https://mailpit-ticket-uat.montagnaservizi.com',
        'credenziali' => [
            ['ruolo' => 'Admin', 'email' => 'info@montagnaservizi.com', 'password' => 'uat'],
            ['ruolo' => 'Developer', 'email' => 'lorena.sava@montagnaservizi.com', 'password' => 'uat'],
            ['ruolo' => 'Manager', 'email' => 'manager@oc.test', 'password' => 'uat'],
            ['ruolo' => 'Customer (Sezione)', 'email' => 'pisa@cai.it', 'password' => 'uat'],
            ['ruolo' => 'Customer', 'email' => 'infosentieroitalia@cai.it', 'password' => 'uat'],
        ],
    ],
    'topics' => [
        [
            'titolo' => 'Dashboard V2 della Sezione (10.1) — lettura delle metriche (US-1001)',
            'test' => [
                ['id' => 'F10-01', 'descrizione' => 'Una Sezione completa espone nome, codice, regione, soci, conto economico e stato patrimoniale', 'test_automatico' => 'tests/Feature/Domain/CaiDirectory/Queries/CaiSectionDashboardMetricsTest.php::a complete section exposes identity, members and both statements'],
                ['id' => 'F10-02', 'descrizione' => 'Una Sezione senza bilanci ha conto economico e stato patrimoniale assenti', 'test_automatico' => 'tests/Feature/Domain/CaiDirectory/Queries/CaiSectionDashboardMetricsTest.php::a section without statements has null statements and members'],
                ['id' => 'F10-03', 'descrizione' => 'Un bilancio con un solo valore lascia vuoti gli altri; il risultato netto non si ricalcola', 'test_automatico' => 'tests/Feature/Domain/CaiDirectory/Queries/CaiSectionDashboardMetricsTest.php::a statement with a single value keeps the others null and never recomputes the net result'],
                ['id' => 'F10-04', 'descrizione' => 'Conto economico e stato patrimoniale si scelgono in modo indipendente, anche su esercizi diversi', 'test_automatico' => 'tests/Feature/Domain/CaiDirectory/Queries/CaiSectionDashboardMetricsTest.php::income statement and balance sheet are picked independently on different years'],
                ['id' => 'F10-05', 'descrizione' => 'A parità di anno vince il bilancio collegato all\'iscrizione RUNTS', 'test_automatico' => 'tests/Feature/Domain/CaiDirectory/Queries/CaiSectionDashboardMetricsTest.php::on the same year the statement linked to the RUNTS registration wins'],
                ['id' => 'F10-06', 'descrizione' => 'Un bilancio collegato solo all\'iscrizione RUNTS appartiene alla Sezione', 'test_automatico' => 'tests/Feature/Domain/CaiDirectory/Queries/CaiSectionDashboardMetricsTest.php::a statement linked only through the RUNTS registration belongs to the section'],
                ['id' => 'F10-07', 'descrizione' => 'Una Sottosezione usa nome, codice e soci propri, la regione della Sezione padre e nessun bilancio', 'test_automatico' => 'tests/Feature/Domain/CaiDirectory/Queries/CaiSectionDashboardMetricsTest.php::a subsection uses its own identity and members, the parent region and no statements'],
                ['id' => 'F10-08', 'descrizione' => 'I soci della Sezione non si sommano mai a quelli delle Sottosezioni', 'test_automatico' => 'tests/Feature/Domain/CaiDirectory/Queries/CaiSectionDashboardMetricsTest.php::section members are never summed with the subsections members'],
            ],
        ],
        [
            'titolo' => 'Dashboard V2 della Sezione (10.1) — formati e badge metrica (US-1002)',
            'test' => [
                ['id' => 'F10-09', 'descrizione' => 'Interi con il punto delle migliaia', 'test_automatico' => 'tests/Unit/Support/Formatting/ItalianNumberFormatterTest.php::formats integers with the thousands dot'],
                ['id' => 'F10-10', 'descrizione' => 'Chilometri con un decimale e la virgola', 'test_automatico' => 'tests/Unit/Support/Formatting/ItalianNumberFormatterTest.php::formats kilometers with one decimal and the decimal comma'],
                ['id' => 'F10-11', 'descrizione' => 'Euro interi arrotondati con il simbolo dopo l\'importo', 'test_automatico' => 'tests/Unit/Support/Formatting/ItalianNumberFormatterTest.php::formats euros as rounded integers with the symbol after the amount'],
                ['id' => 'F10-12', 'descrizione' => 'Risultato netto con il segno e il meno tipografico', 'test_automatico' => 'tests/Unit/Support/Formatting/ItalianNumberFormatterTest.php::formats a signed euro amount with the typographic minus'],
                ['id' => 'F10-13', 'descrizione' => 'Zero si mostra come 0, mai come dato mancante e senza segno', 'test_automatico' => 'tests/Unit/Support/Formatting/ItalianNumberFormatterTest.php::shows zero as 0, never as missing and without a sign'],
                ['id' => 'F10-14', 'descrizione' => 'Badge semplice con valore: tutta la card è un link con il numero formattato', 'test_automatico' => 'tests/Feature/View/Components/CaiMetricBadgeTest.php::simple badge with a value is a link showing the formatted number'],
                ['id' => 'F10-15', 'descrizione' => 'Badge semplice in km: un decimale e l\'unità', 'test_automatico' => 'tests/Feature/View/Components/CaiMetricBadgeTest.php::simple badge shows kilometers with one decimal and the unit'],
                ['id' => 'F10-16', 'descrizione' => 'Badge semplice con valore zero: mostra 0, non "Non disponibile"', 'test_automatico' => 'tests/Feature/View/Components/CaiMetricBadgeTest.php::simple badge shows zero as 0, not as unavailable'],
                ['id' => 'F10-17', 'descrizione' => 'Badge semplice senza valore: trattino, "Non disponibile" e link attivo', 'test_automatico' => 'tests/Feature/View/Components/CaiMetricBadgeTest.php::simple badge without a value shows the dash'],
                ['id' => 'F10-18', 'descrizione' => 'Badge bilancio completo: risultato, "Avanzo", esercizio e valori secondari', 'test_automatico' => 'tests/Feature/View/Components/CaiMetricBadgeTest.php::balance badge with all values shows result, surplus label, year and secondary values'],
                ['id' => 'F10-19', 'descrizione' => 'Badge bilancio con risultato negativo: valore rosso ed etichetta "Disavanzo"', 'test_automatico' => 'tests/Feature/View/Components/CaiMetricBadgeTest.php::balance badge with a negative result shows it in red with the deficit label'],
                ['id' => 'F10-20', 'descrizione' => 'Badge bilancio con risultato zero: 0 € come "Avanzo"', 'test_automatico' => 'tests/Feature/View/Components/CaiMetricBadgeTest.php::balance badge with a zero result shows 0 € as a surplus'],
                ['id' => 'F10-21', 'descrizione' => 'Badge bilancio: solo il valore mancante è "Non disponibile"', 'test_automatico' => 'tests/Feature/View/Components/CaiMetricBadgeTest.php::balance badge marks only the single missing value as unavailable'],
                ['id' => 'F10-22', 'descrizione' => 'Badge bilancio senza valore principale: restano i secondari, nessuna etichetta Avanzo/Disavanzo', 'test_automatico' => 'tests/Feature/View/Components/CaiMetricBadgeTest.php::balance badge with a missing main value keeps the secondary values and no pill'],
                ['id' => 'F10-23', 'descrizione' => 'Badge bilancio senza dati: card grigia con titolo, etichetta, trattino e "Non disponibile"', 'test_automatico' => 'tests/Feature/View/Components/CaiMetricBadgeTest.php::balance badge without any data is grey with title, main label, dash'],
                ['id' => 'F10-24', 'descrizione' => 'Stato patrimoniale negativo: segno meno, senza etichetta e senza rosso', 'test_automatico' => 'tests/Feature/View/Components/CaiMetricBadgeTest.php::balance sheet shows a negative net equity with the minus sign, without label and without red'],
                ['id' => 'F10-25', 'descrizione' => 'Stato patrimoniale positivo: senza il segno più', 'test_automatico' => 'tests/Feature/View/Components/CaiMetricBadgeTest.php::balance sheet shows a positive net equity without the plus sign'],
            ],
        ],
        [
            'titolo' => 'Dashboard V2 della Sezione (10.1) — pagine di approfondimento "in arrivo" (US-1003)',
            'test' => [
                ['id' => 'F10-26', 'descrizione' => 'L\'utente Sezione apre Soci, Bilancio, Rifugi, Sentieri e Tappe SICAI: "Questa sezione è in arrivo."', 'test_automatico' => 'tests/Feature/Filament/Pages/SectionComingSoonPagesTest.php::a sezione customer sees the coming soon page'],
                ['id' => 'F10-27', 'descrizione' => 'Un utente staff riceve 403 sulle pagine "in arrivo"', 'test_automatico' => 'tests/Feature/Filament/Pages/SectionComingSoonPagesTest.php::a staff user cannot access the coming soon page'],
                ['id' => 'F10-28', 'descrizione' => 'Un cliente di altro tipo (GR, Organo Tecnico, nessun tipo) riceve 403', 'test_automatico' => 'tests/Feature/Filament/Pages/SectionComingSoonPagesTest.php::a customer of another type cannot access the coming soon page'],
                ['id' => 'F10-29', 'descrizione' => 'Le pagine "in arrivo" non compaiono nella navigazione', 'test_automatico' => 'tests/Feature/Filament/Pages/SectionComingSoonPagesTest.php::the coming soon pages are not in the navigation'],
            ],
        ],
        [
            'titolo' => 'Dashboard V2 della Sezione (10.1) — pagina Dashboard (US-1004)',
            'test' => [
                ['id' => 'F10-30', 'descrizione' => 'Sezione completa: intestazione, bilanci e soci', 'test_automatico' => 'tests/Feature/Filament/Pages/SectionDashboardTest.php::a complete section shows identity, statements and members'],
                ['id' => 'F10-31', 'descrizione' => 'Caso tipico: soci e conto economico presenti, il resto "Non disponibile"', 'test_automatico' => 'tests/Feature/Filament/Pages/SectionDashboardTest.php::the typical section shows members and income statement, the rest is not available'],
                ['id' => 'F10-32', 'descrizione' => 'Sezione vuota: tutti i badge "Non disponibile", mai 0', 'test_automatico' => 'tests/Feature/Filament/Pages/SectionDashboardTest.php::an empty section shows every badge as not available, never 0'],
                ['id' => 'F10-33', 'descrizione' => 'Utente di Sottosezione: nome, codice e soci della Sottosezione, regione della Sezione padre', 'test_automatico' => 'tests/Feature/Filament/Pages/SectionDashboardTest.php::a subsection user sees the subsection name, code and members with the parent region'],
                ['id' => 'F10-34', 'descrizione' => 'Utente senza Sezione collegata: nome utente, avviso e nessun dato', 'test_automatico' => 'tests/Feature/Filament/Pages/SectionDashboardTest.php::a user without a linked section sees its own name, a notice and no data'],
                ['id' => 'F10-35', 'descrizione' => 'Ogni badge porta alla propria pagina di approfondimento', 'test_automatico' => 'tests/Feature/Filament/Pages/SectionDashboardTest.php::the badges link to their detail pages'],
                ['id' => 'F10-36', 'descrizione' => 'La Dashboard è la prima voce del gruppo "Area cliente"', 'test_automatico' => 'tests/Feature/Filament/Pages/SectionDashboardTest.php::a section customer opens the dashboard and finds it first in the Area cliente group'],
                ['id' => 'F10-37', 'descrizione' => 'Un utente staff riceve 403 sulla Dashboard V2', 'test_automatico' => 'tests/Feature/Filament/Pages/SectionDashboardTest.php::a staff user cannot access the section dashboard'],
                ['id' => 'F10-38', 'descrizione' => 'Un cliente di altro tipo riceve 403 sulla Dashboard V2', 'test_automatico' => 'tests/Feature/Filament/Pages/SectionDashboardTest.php::a customer of another type cannot access the section dashboard'],
                ['id' => 'F10-45', 'descrizione' => 'Una Sezione con iscrizione RUNTS collegata risulta iscritta al Terzo Settore', 'test_automatico' => 'tests/Feature/Domain/CaiDirectory/Queries/CaiSectionDashboardMetricsTest.php::a section with a RUNTS registration is registered in the third sector'],
                ['id' => 'F10-46', 'descrizione' => 'Una Sezione trovata dalla verifica di presenza RUNTS risulta iscritta anche senza iscrizione collegata', 'test_automatico' => 'tests/Feature/Domain/CaiDirectory/Queries/CaiSectionDashboardMetricsTest.php::a section found by the RUNTS presence check is registered even without a linked registration'],
                ['id' => 'F10-47', 'descrizione' => 'Ogni altra Sezione (mai verificata, timeout, non presente) risulta non iscritta', 'test_automatico' => 'tests/Feature/Domain/CaiDirectory/Queries/CaiSectionDashboardMetricsTest.php::a section that is not found as registered is not registered, whatever the check outcome'],
                ['id' => 'F10-48', 'descrizione' => 'Una Sottosezione non ha un dato Terzo Settore proprio', 'test_automatico' => 'tests/Feature/Domain/CaiDirectory/Queries/CaiSectionDashboardMetricsTest.php::a subsection has no third sector information'],
                ['id' => 'F10-49', 'descrizione' => 'Il sottotitolo mostra la pillola "Terzo Settore: iscritto" o "non iscritto"', 'test_automatico' => 'tests/Feature/Filament/Pages/SectionDashboardTest.php::the subtitle shows the third sector registration pill'],
                ['id' => 'F10-50', 'descrizione' => 'Utente di Sottosezione o senza Sezione: nessuna pillola Terzo Settore', 'test_automatico' => 'tests/Feature/Filament/Pages/SectionDashboardTest.php::a subsection user and a user without a section see no third sector pill'],
                ['id' => 'F10-51', 'descrizione' => 'Il link di ogni badge riporta il nome della pagina di approfondimento', 'test_automatico' => 'tests/Feature/Filament/Pages/SectionDashboardTest.php::each badge link is labelled with the name of its detail page'],
            ],
        ],
        [
            'titolo' => 'Dashboard V2 della Sezione (10.1) — atterraggio e "Ticket e documenti" (US-1005)',
            'test' => [
                ['id' => 'F10-39', 'descrizione' => 'Dopo il login l\'utente Sezione atterra sulla Dashboard V2', 'test_automatico' => 'tests/Feature/Filament/Pages/DashboardTest.php::a Sezione customer landing on the dashboard is redirected to the Section dashboard V2'],
                ['id' => 'F10-40', 'descrizione' => 'Gli altri clienti (Organo Tecnico, Generico) atterrano ancora sulla dashboard cliente', 'test_automatico' => 'tests/Feature/Filament/Pages/DashboardTest.php::an Organo Tecnico customer landing on the dashboard is still redirected to the customer dashboard'],
                ['id' => 'F10-41', 'descrizione' => 'Per l\'utente Sezione la dashboard cliente diventa "Ticket e documenti", subito dopo la Dashboard', 'test_automatico' => 'tests/Feature/Filament/Pages/CustomerDashboardTest.php::for a Sezione customer the page becomes "Ticket e documenti", right after the Section dashboard'],
                ['id' => 'F10-42', 'descrizione' => 'Per ogni altro cliente etichetta, titolo, icona, gruppo e posizione restano invariati', 'test_automatico' => 'tests/Feature/Filament/Pages/CustomerDashboardTest.php::for every other customer the page keeps the "Dashboard" label, title, icon, group and position'],
            ],
        ],
        [
            'titolo' => 'Dashboard V2 della Sezione (10.1) — percorso end-to-end (US-1006)',
            'test' => [
                ['id' => 'F10-43', 'descrizione' => 'Utente Sezione: login → Dashboard V2 con i valori reali → badge "Soci" → pagina "in arrivo" → "Ticket e documenti"', 'test_automatico' => 'tests/Feature/EndToEnd/Fase10DashboardSezioneEndToEndTest.php::a Sezione user logs in, lands on the Dashboard V2 with real values and follows a badge to the coming soon page'],
                ['id' => 'F10-44', 'descrizione' => 'Utente Gruppo Regionale: atterra sulla propria Dashboard V2 e non apre la Dashboard V2 della Sezione', 'test_automatico' => 'tests/Feature/EndToEnd/Fase10DashboardSezioneEndToEndTest.php::a Gruppo Regionale user lands on the regional group dashboard V2 and cannot open the Section Dashboard V2'],
            ],
        ],
        [
            'titolo' => 'Dashboard V2 del Gruppo Regionale (10.2) — metriche del GR (US-1011)',
            'test' => [
                ['id' => 'F10-52', 'descrizione' => 'Il codice CAI del GR è il prefisso più frequente fra le Sezioni della regione seguito da 000', 'test_automatico' => 'tests/Feature/Domain/CaiDirectory/Queries/CaiRegionalGroupDashboardMetricsTest.php::the regional group code is the most frequent prefix of its region followed by 000'],
                ['id' => 'F10-53', 'descrizione' => 'A parità di frequenza vince il prefisso numericamente più alto', 'test_automatico' => 'tests/Feature/Domain/CaiDirectory/Queries/CaiRegionalGroupDashboardMetricsTest.php::on a tie the numerically highest prefix wins'],
                ['id' => 'F10-54', 'descrizione' => 'Le Sezioni del GR si scelgono per prefisso del codice, qualunque sia la loro regione', 'test_automatico' => 'tests/Feature/Domain/CaiDirectory/Queries/CaiRegionalGroupDashboardMetricsTest.php::the sections of the group are picked by code prefix, whatever their region'],
                ['id' => 'F10-55', 'descrizione' => 'Una regione senza Sezioni non ha codice, conteggi né bilanci', 'test_automatico' => 'tests/Feature/Domain/CaiDirectory/Queries/CaiRegionalGroupDashboardMetricsTest.php::a region without sections has everything null'],
                ['id' => 'F10-56', 'descrizione' => 'I soci sono la somma delle sole Sezioni che li hanno', 'test_automatico' => 'tests/Feature/Domain/CaiDirectory/Queries/CaiRegionalGroupDashboardMetricsTest.php::members are the sum of the sections that have them'],
                ['id' => 'F10-57', 'descrizione' => 'Senza soci in nessuna Sezione il totale resta assente, mai 0', 'test_automatico' => 'tests/Feature/Domain/CaiDirectory/Queries/CaiRegionalGroupDashboardMetricsTest.php::members are null when no section has them'],
                ['id' => 'F10-58', 'descrizione' => 'Il consolidato somma un solo esercizio, il più recente con dati, e solo le Sezioni con dati in quell\'anno', 'test_automatico' => 'tests/Feature/Domain/CaiDirectory/Queries/CaiRegionalGroupDashboardMetricsTest.php::the consolidated income statement sums only the latest year and the sections with data in it'],
                ['id' => 'F10-59', 'descrizione' => 'Un totale consolidato senza alcun valore resta assente, mai 0', 'test_automatico' => 'tests/Feature/Domain/CaiDirectory/Queries/CaiRegionalGroupDashboardMetricsTest.php::a consolidated total without any value stays null, never 0'],
                ['id' => 'F10-60', 'descrizione' => 'A parità di anno per una Sezione vince il bilancio collegato all\'iscrizione RUNTS', 'test_automatico' => 'tests/Feature/Domain/CaiDirectory/Queries/CaiRegionalGroupDashboardMetricsTest.php::on the same year of a section the statement linked to the RUNTS registration wins'],
                ['id' => 'F10-61', 'descrizione' => 'Un bilancio collegato solo all\'iscrizione RUNTS entra nel consolidato', 'test_automatico' => 'tests/Feature/Domain/CaiDirectory/Queries/CaiRegionalGroupDashboardMetricsTest.php::a statement linked only through the RUNTS registration is consolidated'],
                ['id' => 'F10-62', 'descrizione' => 'Conto economico e stato patrimoniale consolidati scelgono l\'esercizio in modo indipendente', 'test_automatico' => 'tests/Feature/Domain/CaiDirectory/Queries/CaiRegionalGroupDashboardMetricsTest.php::income statement and balance sheet pick their year independently'],
                ['id' => 'F10-63', 'descrizione' => 'Le Sottosezioni non contano mai fra le Sezioni del GR', 'test_automatico' => 'tests/Feature/Domain/CaiDirectory/Queries/CaiRegionalGroupDashboardMetricsTest.php::subsections never count as sections of the group'],
                ['id' => 'F10-64', 'descrizione' => 'I bilanci di tutte le Sezioni del GR si leggono con una sola query', 'test_automatico' => 'tests/Feature/Domain/CaiDirectory/Queries/CaiRegionalGroupDashboardMetricsTest.php::the statements of all the sections are loaded with a single query'],
            ],
        ],
        [
            'titolo' => 'Dashboard V2 del Gruppo Regionale (10.2) — nota sotto il valore del badge (US-1012)',
            'test' => [
                ['id' => 'F10-65', 'descrizione' => 'Badge semplice con valore: la nota compare sotto il numero', 'test_automatico' => 'tests/Feature/View/Components/CaiMetricBadgeTest.php::simple badge shows the note under the number when the value is present'],
                ['id' => 'F10-66', 'descrizione' => 'Badge semplice senza valore: nessuna nota', 'test_automatico' => 'tests/Feature/View/Components/CaiMetricBadgeTest.php::simple badge never shows the note when the value is unavailable'],
                ['id' => 'F10-67', 'descrizione' => 'Badge semplice senza nota: HTML invariato rispetto alla 10.1', 'test_automatico' => 'tests/Feature/View/Components/CaiMetricBadgeTest.php::simple badge without the note prop renders no note element'],
                ['id' => 'F10-68', 'descrizione' => 'Badge bilancio: la nota si accoda all\'esercizio ("Esercizio 2025 · Somma di 11 Sezioni")', 'test_automatico' => 'tests/Feature/View/Components/CaiMetricBadgeTest.php::balance badge appends the year note to the fiscal year line'],
                ['id' => 'F10-69', 'descrizione' => 'Badge bilancio senza esercizio: nessuna nota', 'test_automatico' => 'tests/Feature/View/Components/CaiMetricBadgeTest.php::balance badge hides the year note when there is no fiscal year'],
                ['id' => 'F10-70', 'descrizione' => 'Badge bilancio senza nota: riga dell\'esercizio invariata', 'test_automatico' => 'tests/Feature/View/Components/CaiMetricBadgeTest.php::balance badge without the year note keeps the plain fiscal year line'],
            ],
        ],
        [
            'titolo' => 'Dashboard V2 del Gruppo Regionale (10.2) — pagine di approfondimento "in arrivo" (US-1013)',
            'test' => [
                ['id' => 'F10-71', 'descrizione' => 'L\'utente GR apre Soci, Bilancio, Rifugi, Sentieri e Tappe SICAI: "Questa sezione è in arrivo."', 'test_automatico' => 'tests/Feature/Filament/Pages/RegionalGroupComingSoonPagesTest.php::a regional group customer sees the coming soon page'],
                ['id' => 'F10-72', 'descrizione' => 'Un utente staff riceve 403 sulle pagine "in arrivo" del GR', 'test_automatico' => 'tests/Feature/Filament/Pages/RegionalGroupComingSoonPagesTest.php::a staff user cannot access the regional group coming soon page'],
                ['id' => 'F10-73', 'descrizione' => 'Un cliente di altro tipo (Sezione, Organo Tecnico, nessun tipo) riceve 403', 'test_automatico' => 'tests/Feature/Filament/Pages/RegionalGroupComingSoonPagesTest.php::a customer of another type cannot access the regional group coming soon page'],
                ['id' => 'F10-74', 'descrizione' => 'Le pagine "in arrivo" del GR non compaiono nella navigazione', 'test_automatico' => 'tests/Feature/Filament/Pages/RegionalGroupComingSoonPagesTest.php::the regional group coming soon pages are not in the navigation'],
            ],
        ],
        [
            'titolo' => 'Dashboard V2 del Gruppo Regionale (10.2) — pagina Dashboard (US-1014)',
            'test' => [
                ['id' => 'F10-75', 'descrizione' => 'GR con Sezioni: codice del GR, bilancio consolidato con "Somma di N Sezioni" e soci', 'test_automatico' => 'tests/Feature/Filament/Pages/RegionalGroupDashboardTest.php::a regional group with sections shows the GR code, consolidated statements and members'],
                ['id' => 'F10-76', 'descrizione' => 'Bilancio proprio del GR, rifugi, sentieri e tappe SICAI "Non disponibile", mai 0', 'test_automatico' => 'tests/Feature/Filament/Pages/RegionalGroupDashboardTest.php::the GR own statements, huts, trails and SICAI stages are not available, never 0'],
                ['id' => 'F10-77', 'descrizione' => 'GR la cui regione non ha Sezioni: solo la regione, nessun dato', 'test_automatico' => 'tests/Feature/Filament/Pages/RegionalGroupDashboardTest.php::a regional group whose region has no sections shows only the region and no data'],
                ['id' => 'F10-78', 'descrizione' => 'GR senza regione: nome, avviso e tutti i badge "Non disponibile"', 'test_automatico' => 'tests/Feature/Filament/Pages/RegionalGroupDashboardTest.php::a regional group without a region sees its name, a notice and every badge not available'],
                ['id' => 'F10-79', 'descrizione' => 'Ogni badge porta alla propria pagina; "Sezioni" all\'elenco delle Sezioni del GR', 'test_automatico' => 'tests/Feature/Filament/Pages/RegionalGroupDashboardTest.php::the badges link to their detail pages'],
                ['id' => 'F10-80', 'descrizione' => 'La nota usa il singolare con una sola Sezione', 'test_automatico' => 'tests/Feature/Filament/Pages/RegionalGroupDashboardTest.php::the sum note handles the singular'],
                ['id' => 'F10-81', 'descrizione' => 'La Dashboard è la prima voce del gruppo "GR"', 'test_automatico' => 'tests/Feature/Filament/Pages/RegionalGroupDashboardTest.php::a regional group customer opens the dashboard and finds it first in the GR group'],
                ['id' => 'F10-82', 'descrizione' => 'Un utente staff riceve 403 sulla Dashboard V2 del GR', 'test_automatico' => 'tests/Feature/Filament/Pages/RegionalGroupDashboardTest.php::a staff user cannot access the regional group dashboard'],
                ['id' => 'F10-83', 'descrizione' => 'Un cliente di altro tipo riceve 403 sulla Dashboard V2 del GR', 'test_automatico' => 'tests/Feature/Filament/Pages/RegionalGroupDashboardTest.php::a customer of another type cannot access the regional group dashboard'],
            ],
        ],
        [
            'titolo' => 'Dashboard V2 del Gruppo Regionale (10.2) — atterraggio e "Ticket e documenti" (US-1015)',
            'test' => [
                ['id' => 'F10-84', 'descrizione' => 'Dopo il login l\'utente GR atterra sulla Dashboard V2 del GR', 'test_automatico' => 'tests/Feature/Filament/Pages/DashboardTest.php::a Gruppo Regionale customer landing on the dashboard is redirected to the regional group dashboard V2'],
                ['id' => 'F10-85', 'descrizione' => 'Per l\'utente GR la dashboard cliente diventa "Ticket e documenti", subito dopo la Dashboard nel gruppo "GR"', 'test_automatico' => 'tests/Feature/Filament/Pages/CustomerDashboardTest.php::for a Gruppo Regionale customer the page becomes "Ticket e documenti", right after the GR dashboard in the "GR" group'],
            ],
        ],
        [
            'titolo' => 'Dashboard V2 del Gruppo Regionale (10.2) — percorso end-to-end (US-1016)',
            'test' => [
                ['id' => 'F10-86', 'descrizione' => 'Utente GR: login → Dashboard V2 con i valori consolidati → badge "Sezioni" → elenco delle Sezioni del GR → "Ticket e documenti"', 'test_automatico' => 'tests/Feature/EndToEnd/Fase10DashboardGruppoRegionaleEndToEndTest.php::a Gruppo Regionale user logs in, lands on the Dashboard V2 with the consolidated values and follows the Sezioni badge to the sections of the group'],
            ],
        ],
    ],
];
