<?php

declare(strict_types=1);

// Manifest di tracciabilità per il collaudo (UAT) di Fase 3 (Sottosistema email —
// US-301..US-326: pipeline inbound IMAP, outbound E1-E9, localizzazione,
// amministrazione email, voce di menu Mailpit, checkpoint end-to-end). Ogni voce
// collega un criterio di accettazione a un test automatico REALMENTE esistente in
// tests/ (verificato da `collaudo:verify-manifest 3`). Fonte delle story:
// scripts/ralph/prd.json (Fase 3). Questo file è puro dato: nessuna logica.

return [
    'fase' => '3',
    'titolo' => 'Fase 3 (Sottosistema email)',
    'parte_1' => [
        'app_url' => 'https://ticket-uat.montagnaservizi.com',
        'mailpit_url' => 'https://mailpit-ticket-uat.montagnaservizi.com',
        'credenziali' => [
            ['ruolo' => 'Admin', 'email' => 'info@montagnaservizi.com', 'password' => 'uat'],
            ['ruolo' => 'Developer', 'email' => 'lorena.sava@montagnaservizi.com', 'password' => 'uat'],
            ['ruolo' => 'Manager', 'email' => 'manager@oc.test', 'password' => 'uat'],
            ['ruolo' => 'Customer', 'email' => 'infosentieroitalia@cai.it', 'password' => 'uat'],
            ['ruolo' => 'Fundraising', 'email' => 'sara.mariani@montagnaservizi.com', 'password' => 'uat'],
        ],
    ],
    'topics' => [
        [
            'titolo' => 'Configurazione IMAP e interfaccia InboundMailTransport (US-301)',
            'test' => [
                [
                    'id' => 'F3-01',
                    'descrizione' => 'Il container risolve InboundMailTransport all\'implementazione Webklex',
                    'test_automatico' => 'tests/Unit/Domain/Mail/WebklexImapTransportTest.php::the container resolves InboundMailTransport to the webklex implementation',
                ],
                [
                    'id' => 'F3-02',
                    'descrizione' => 'La config account IMAP ha la forma richiesta da ClientManager::make()',
                    'test_automatico' => 'tests/Unit/Domain/Mail/MailPipelineConfigTest.php::the imap account config has the shape expected by ClientManager::make()',
                ],
                [
                    'id' => 'F3-03',
                    'descrizione' => 'Ogni ImapFolderRole ha una cartella configurata',
                    'test_automatico' => 'tests/Unit/Domain/Mail/MailPipelineConfigTest.php::every ImapFolderRole has a configured folder name',
                ],
                [
                    'id' => 'F3-04',
                    'descrizione' => 'Il gruppo di notifica staff è derivato da una env comma-separated',
                    'test_automatico' => 'tests/Unit/Domain/Mail/MailPipelineConfigTest.php::the staff notification group is parsed from a comma-separated env value',
                ],
            ],
        ],
        [
            'titolo' => 'Comando mail:fetch-inbound — fetch e archiviazione grezza (US-302)',
            'test' => [
                [
                    'id' => 'F3-05',
                    'descrizione' => 'Il .eml grezzo è archiviato PRIMA di creare la riga email_messages (status=received)',
                    'test_automatico' => 'tests/Feature/Console/MailFetchInboundCommandTest.php::archivia un nuovo messaggio come .eml prima di creare la riga email_messages',
                ],
                [
                    'id' => 'F3-06',
                    'descrizione' => 'Rieseguire il comando sullo stesso stato IMAP non crea duplicati',
                    'test_automatico' => 'tests/Feature/Console/MailFetchInboundCommandTest.php::rieseguire il comando sullo stesso stato IMAP non crea duplicati',
                ],
                [
                    'id' => 'F3-07',
                    'descrizione' => 'IMAP viene sempre disconnesso, anche quando fetch lancia un errore',
                    'test_automatico' => 'tests/Feature/Console/MailFetchInboundCommandTest.php::disconnette sempre IMAP anche quando fetch lancia un errore',
                ],
                [
                    'id' => 'F3-08',
                    'descrizione' => '--limit sovrascrive il default di configurazione',
                    'test_automatico' => 'tests/Feature/Console/MailFetchInboundCommandTest.php::rispetta --limit invece del default di configurazione',
                ],
            ],
        ],
        [
            'titolo' => 'Parsing del messaggio — subject, corpo, charset (US-303)',
            'test' => [
                [
                    'id' => 'F3-09',
                    'descrizione' => 'SubjectNormalizer rimuove i prefissi di risposta/inoltro anche in cascata',
                    'test_automatico' => 'tests/Unit/Domain/Mail/Parsers/SubjectNormalizerTest.php::rimuove prefissi in cascata',
                ],
                [
                    'id' => 'F3-10',
                    'descrizione' => 'EmailBodyParser preferisce il text/plain quando entrambi i corpi sono presenti',
                    'test_automatico' => 'tests/Unit/Domain/Mail/Parsers/EmailBodyParserTest.php::preferisce il text/plain quando entrambi i corpi sono presenti',
                ],
                [
                    'id' => 'F3-11',
                    'descrizione' => 'QuotedTextRemover rimuove una citazione introdotta da "On ... wrote:"',
                    'test_automatico' => 'tests/Unit/Domain/Mail/Parsers/QuotedTextRemoverTest.php::rimuove una citazione introdotta da "On ... wrote:"',
                ],
                [
                    'id' => 'F3-12',
                    'descrizione' => 'body_html è sempre sanitizzato con la stessa allowlist del ticketing',
                    'test_automatico' => 'tests/Unit/Domain/Mail/Parsers/EmailBodyParserTest.php::sanitizza sempre il body_html rimuovendo tag non in allowlist',
                ],
                [
                    'id' => 'F3-13',
                    'descrizione' => 'Un .eml grezzo mancante non lancia un\'eccezione non gestita: il messaggio passa a failed',
                    'test_automatico' => 'tests/Feature/Domain/Mail/Actions/ParseInboundEmailTest.php::un file grezzo mancante non lancia',
                ],
            ],
        ],
        [
            'titolo' => 'Classificazione anti-loop e scarti obbligatori (US-304)',
            'test' => [
                [
                    'id' => 'F3-14',
                    'descrizione' => 'Un DSN (multipart/report, report-type delivery-status) è scartato e non va al ticketing',
                    'test_automatico' => 'tests/Feature/Domain/Mail/Actions/ClassifyInboundEmailTest.php::un DSN (multipart/report, report-type delivery-status) è scartato e non va al ticketing',
                ],
                [
                    'id' => 'F3-15',
                    'descrizione' => 'Un mittente MAILER-DAEMON/postmaster/no-reply/vuoto è scartato, un mittente normale no',
                    'test_automatico' => 'tests/Feature/Domain/Mail/Actions/ClassifyInboundEmailTest.php::un mittente MAILER-DAEMON/postmaster/no-reply/vuoto è scartato',
                ],
                [
                    'id' => 'F3-16',
                    'descrizione' => 'Auto-Submitted diverso da no è scartato',
                    'test_automatico' => 'tests/Feature/Domain/Mail/Actions/ClassifyInboundEmailTest.php::Auto-Submitted diverso da no è scartato',
                ],
                [
                    'id' => 'F3-17',
                    'descrizione' => 'Precedence bulk/list/junk è scartato',
                    'test_automatico' => 'tests/Feature/Domain/Mail/Actions/ClassifyInboundEmailTest.php::Precedence bulk/list/junk è scartato',
                ],
                [
                    'id' => 'F3-18',
                    'descrizione' => 'List-Id presente è scartato come mailing list',
                    'test_automatico' => 'tests/Feature/Domain/Mail/Actions/ClassifyInboundEmailTest.php::List-Id presente è scartato come mailing list',
                ],
                [
                    'id' => 'F3-19',
                    'descrizione' => 'X-Auto-Response-Suppress presente è scartato',
                    'test_automatico' => 'tests/Feature/Domain/Mail/Actions/ClassifyInboundEmailTest.php::X-Auto-Response-Suppress presente è scartato',
                ],
                [
                    'id' => 'F3-20',
                    'descrizione' => 'Oltre la soglia oraria il messaggio è comunque classificato ma il mittente va in loop_protection',
                    'test_automatico' => 'tests/Feature/Domain/Mail/Actions/ClassifyInboundEmailTest.php::oltre la soglia oraria il messaggio è comunque classificato ma il mittente va in loop_protection',
                ],
            ],
        ],
        [
            'titolo' => 'Identificazione del mittente (US-305)',
            'test' => [
                [
                    'id' => 'F3-21',
                    'descrizione' => 'Un mittente che corrisponde esattamente a users.email viene identificato',
                    'test_automatico' => 'tests/Feature/Domain/Mail/Actions/ResolveEmailSenderTest.php::un mittente che corrisponde esattamente a users.email viene identificato',
                ],
                [
                    'id' => 'F3-22',
                    'descrizione' => 'Un mittente con sub-address (plus-addressing) viene identificato rimuovendo il tag',
                    'test_automatico' => 'tests/Feature/Domain/Mail/Actions/ResolveEmailSenderTest.php::un mittente con sub-address (plus-addressing) viene identificato rimuovendo il tag',
                ],
                [
                    'id' => 'F3-23',
                    'descrizione' => 'Nessuna identificazione per solo dominio, anche con mittente sullo stesso dominio',
                    'test_automatico' => 'tests/Feature/Domain/Mail/Actions/ResolveEmailSenderTest.php::un mittente sullo stesso dominio ma senza nessun utente corrispondente non viene mai identificato per solo dominio',
                ],
                [
                    'id' => 'F3-24',
                    'descrizione' => 'Un mittente non identificato va in quarantena, mai scartato',
                    'test_automatico' => 'tests/Feature/Domain/Mail/Actions/ResolveEmailSenderTest.php::un mittente non identificato va in quarantena, mai scartato',
                ],
            ],
        ],
        [
            'titolo' => 'Risoluzione del thread — VERP, In-Reply-To, subject, euristica (US-306)',
            'test' => [
                [
                    'id' => 'F3-25',
                    'descrizione' => 'Livello 1 (VERP): un token ticket+ulid valido nel To risolve il ticket_message e il suo ticket',
                    'test_automatico' => 'tests/Feature/Domain/Mail/Actions/ResolveEmailThreadTest.php::livello 1 (VERP): un token ticket+ulid valido nel To risolve il ticket_message e il suo ticket',
                ],
                [
                    'id' => 'F3-26',
                    'descrizione' => 'Livello 2 (In-Reply-To): un In-Reply-To che referenzia un message_id esistente risolve il ticket',
                    'test_automatico' => 'tests/Feature/Domain/Mail/Actions/ResolveEmailThreadTest.php::livello 2 (In-Reply-To): un In-Reply-To che referenzia un message_id esistente collegato a un ticket risolve quel ticket',
                ],
                [
                    'id' => 'F3-27',
                    'descrizione' => 'Livello 3 (token subject): [#<id>] nel subject normalizzato risolve direttamente il ticket',
                    'test_automatico' => 'tests/Feature/Domain/Mail/Actions/ResolveEmailThreadTest.php::livello 3 (token subject): [#<id>] nel subject normalizzato risolve direttamente il ticket',
                ],
                [
                    'id' => 'F3-28',
                    'descrizione' => 'Livello 4 (euristica): stesso mittente + subject identico + thread aperto di recente, marcato come euristico',
                    'test_automatico' => 'tests/Feature/Domain/Mail/Actions/ResolveEmailThreadTest.php::livello 4 (euristica): stesso mittente + subject normalizzato identico + thread aperto di recente risolve il ticket, marcato esplicitamente come euristico',
                ],
                [
                    'id' => 'F3-29',
                    'descrizione' => 'Un match di livello più affidabile (In-Reply-To) non è mai scavalcato dall\'euristica',
                    'test_automatico' => 'tests/Feature/Domain/Mail/Actions/ResolveEmailThreadTest.php::livello 2: un In-Reply-To valido non viene mai scavalcato',
                ],
                [
                    'id' => 'F3-30',
                    'descrizione' => 'Nessun match sui quattro livelli restituisce una risoluzione vuota (nuovo ticket)',
                    'test_automatico' => 'tests/Feature/Domain/Mail/Actions/ResolveEmailThreadTest.php::nessun match su nessuno dei quattro livelli restituisce una risoluzione vuota (nuovo ticket)',
                ],
            ],
        ],
        [
            'titolo' => 'Applicazione — creazione ticket o nuovo messaggio, notifiche post-commit (US-307)',
            'test' => [
                [
                    'id' => 'F3-31',
                    'descrizione' => 'Mittente identificato senza match di thread crea un nuovo ticket helpdesk con il primo messaggio via email',
                    'test_automatico' => 'tests/Feature/Domain/Mail/Actions/ApplyInboundEmailTest.php::mittente identificato senza match di thread crea un nuovo ticket helpdesk con il primo messaggio via email',
                ],
                [
                    'id' => 'F3-32',
                    'descrizione' => 'Mittente identificato con match di thread accoda un messaggio sul ticket esistente invece di crearne uno nuovo',
                    'test_automatico' => 'tests/Feature/Domain/Mail/Actions/ApplyInboundEmailTest.php::mittente identificato con match di thread accoda un messaggio sul ticket esistente invece di crearne uno nuovo',
                ],
                [
                    'id' => 'F3-33',
                    'descrizione' => 'Un fallimento nella risoluzione del ticket esistente annulla sia il messaggio sia l\'aggiornamento di email_messages (stessa transazione)',
                    'test_automatico' => 'tests/Feature/Domain/Mail/Actions/ApplyInboundEmailTest.php::un fallimento nella risoluzione del ticket esistente annulla sia la creazione del messaggio',
                ],
                [
                    'id' => 'F3-34',
                    'descrizione' => 'Un fallimento nella notifica post-commit non annulla il ticket/messaggio già creati (problema 2 del v1)',
                    'test_automatico' => 'tests/Feature/Domain/Mail/Actions/ApplyInboundEmailTest.php::un fallimento nella notifica post-commit non annulla il ticket/messaggio già creati (problema 2 del v1)',
                ],
                [
                    'id' => 'F3-35',
                    'descrizione' => 'La risposta del richiedente via email applica la transizione T7 (waiting torna a previous_status)',
                    'test_automatico' => 'tests/Feature/Domain/Mail/Actions/ApplyInboundEmailTest.php::la risposta del richiedente via email applica la transizione T7 (waiting torna a previous_status)',
                ],
            ],
        ],
        [
            'titolo' => 'Mittente non riconosciuto — quarantena (US-308)',
            'test' => [
                [
                    'id' => 'F3-36',
                    'descrizione' => 'Mittente non identificato non crea nessun ticket e lascia il messaggio in quarantena, mai scartato',
                    'test_automatico' => 'tests/Feature/Domain/Mail/Actions/ApplyInboundEmailTest.php::mittente non identificato non crea nessun ticket e lascia il messaggio in quarantena',
                ],
                [
                    'id' => 'F3-37',
                    'descrizione' => 'Un mittente sconosciuto senza soppressioni attive emette EmailQuarantined con auto-reply consentito',
                    'test_automatico' => 'tests/Feature/Domain/Mail/Actions/ApplyInboundEmailTest.php::un mittente sconosciuto senza soppressioni attive emette EmailQuarantined con auto-reply consentito',
                ],
                [
                    'id' => 'F3-38',
                    'descrizione' => 'Un mittente già soppresso per rate limit (US-304) emette EmailQuarantined senza consentire l\'auto-reply',
                    'test_automatico' => 'tests/Feature/Domain/Mail/Actions/ApplyInboundEmailTest.php::un mittente sconosciuto già soppresso per rate limit (US-304) emette EmailQuarantined senza consentire',
                ],
                [
                    'id' => 'F3-39',
                    'descrizione' => 'Un messaggio già in quarantena viene riprocessato con successo una volta che il mittente diventa identificabile',
                    'test_automatico' => 'tests/Feature/Domain/Mail/Actions/ApplyInboundEmailTest.php::un messaggio già in quarantena viene riprocessato con successo una volta che il mittente diventa identificabile (US-322)',
                ],
                [
                    'id' => 'F3-40',
                    'descrizione' => 'E9 e una notifica in-app arrivano a ogni destinatario staff risolto quando un messaggio va in quarantena',
                    'test_automatico' => 'tests/Feature/Domain/Mail/Listeners/NotifyStaffOfUnknownSenderTest.php::sends E9 and an in-app notification to every resolved staff recipient',
                ],
            ],
        ],
        [
            'titolo' => 'Allegati inbound (US-309)',
            'test' => [
                [
                    'id' => 'F3-41',
                    'descrizione' => 'Un allegato regolare viene importato nella collection attachments del ticket_message con record stored',
                    'test_automatico' => 'tests/Feature/Domain/Mail/Actions/ImportInboundEmailAttachmentsTest.php::importa un allegato regolare nella collection attachments del ticket_message con record stored',
                ],
                [
                    'id' => 'F3-42',
                    'descrizione' => 'Gli allegati inline sono esclusi per default, nessun record creato',
                    'test_automatico' => 'tests/Feature/Domain/Mail/Actions/ImportInboundEmailAttachmentsTest.php::gli allegati inline sono esclusi per default, nessun record creato',
                ],
                [
                    'id' => 'F3-43',
                    'descrizione' => 'Un allegato di tipo non consentito produce un record rejected_mime, senza fallire gli altri',
                    'test_automatico' => 'tests/Feature/Domain/Mail/Actions/ImportInboundEmailAttachmentsTest.php::un allegato di tipo non consentito produce un record rejected_mime, senza fallire gli altri',
                ],
                [
                    'id' => 'F3-44',
                    'descrizione' => 'Un allegato più grande del limite per singolo file produce un record rejected_size, senza fallire gli altri',
                    'test_automatico' => 'tests/Feature/Domain/Mail/Actions/ImportInboundEmailAttachmentsTest.php::un allegato più grande del limite per singolo file produce un record rejected_size, senza fallire gli altri',
                ],
                [
                    'id' => 'F3-45',
                    'descrizione' => 'Un errore nel salvataggio di un singolo allegato produce un record failed, senza fermare gli altri',
                    'test_automatico' => 'tests/Feature/Domain/Mail/Actions/ImportInboundEmailAttachmentsTest.php::un errore nel salvataggio di un singolo allegato produce un record failed, senza fermare gli altri',
                ],
            ],
        ],
        [
            'titolo' => 'Layout email unico e componenti riusabili (US-310)',
            'test' => [
                [
                    'id' => 'F3-46',
                    'descrizione' => 'Il layout condiviso produce HTML valido, senza errori di parsing, per un Mailable reale',
                    'test_automatico' => 'tests/Feature/Domain/Mail/EmailLayoutTest.php::the shared layout renders well-formed HTML with no parse errors for a real Mailable',
                ],
                [
                    'id' => 'F3-47',
                    'descrizione' => 'L\'email renderizzata contiene tutti i componenti riusabili: header, badge, blocco messaggio, CTA, footer',
                    'test_automatico' => 'tests/Feature/Domain/Mail/EmailLayoutTest.php::the rendered email contains every reusable component: header, badge, message block, CTA, footer',
                ],
                [
                    'id' => 'F3-48',
                    'descrizione' => 'La versione plain-text è generata insieme all\'HTML con lo stesso contenuto',
                    'test_automatico' => 'tests/Feature/Domain/Mail/EmailLayoutTest.php::the plain-text version is generated alongside the HTML and carries the same content',
                ],
                [
                    'id' => 'F3-49',
                    'descrizione' => 'Il footer mostra il link alle preferenze di notifica quando un URL è configurato',
                    'test_automatico' => 'tests/Feature/Domain/Mail/EmailLayoutTest.php::the footer shows the notification preferences link when a URL is configured',
                ],
            ],
        ],
        [
            'titolo' => 'Mailable E1/E2 — conferme di ricezione/apertura ticket (US-311)',
            'test' => [
                [
                    'id' => 'F3-50',
                    'descrizione' => 'E1 viene inviata quando un\'email inbound applica un nuovo ticket',
                    'test_automatico' => 'tests/Feature/Domain/Mail/Listeners/SendTicketReceivedByEmailNotificationTest.php::sends E1 when the inbound email applied a new ticket',
                ],
                [
                    'id' => 'F3-51',
                    'descrizione' => 'E1 non viene inviata quando l\'email inbound si aggancia a un ticket esistente',
                    'test_automatico' => 'tests/Feature/Domain/Mail/Listeners/SendTicketReceivedByEmailNotificationTest.php::does not send E1 when the inbound email applied an existing ticket',
                ],
                [
                    'id' => 'F3-52',
                    'descrizione' => 'E2 viene inviata quando il ticket è creato dal canale web',
                    'test_automatico' => 'tests/Feature/Domain/Mail/Listeners/SendTicketOpenedFromWebMailNotificationTest.php::sends E2 when the ticket was created from the web channel',
                ],
                [
                    'id' => 'F3-53',
                    'descrizione' => 'Ogni Mailable outbound imposta Message-Id e Reply-To VERP dalla riga email_messages',
                    'test_automatico' => 'tests/Feature/Domain/Mail/Mailables/TicketOutboundMailablesTest.php::sets the Message-Id header and the VERP Reply-To from the outbound email_messages row',
                ],
                [
                    'id' => 'F3-54',
                    'descrizione' => 'Nessun invio se il destinatario è in email_suppressions: la riga outbound resta comunque tracciata come suppressed',
                    'test_automatico' => 'tests/Feature/Domain/Mail/Actions/SendOutboundTicketMailTest.php::does not queue the mailable and marks the row suppressed when the recipient email is in email_suppressions',
                ],
            ],
        ],
        [
            'titolo' => 'Mailable E3/E9 — notifica staff (US-312)',
            'test' => [
                [
                    'id' => 'F3-55',
                    'descrizione' => 'E3 viene inviata quando un\'email inbound applica un nuovo ticket per un cliente',
                    'test_automatico' => 'tests/Feature/Domain/Mail/Listeners/NotifyStaffOfNewCustomerTicketFromEmailTest.php::sends E3 when the inbound email applied a new ticket for a customer',
                ],
                [
                    'id' => 'F3-56',
                    'descrizione' => 'E3 viene inviata quando un cliente apre un ticket dal web',
                    'test_automatico' => 'tests/Feature/Domain/Mail/Listeners/NotifyStaffOfNewCustomerTicketFromWebTest.php::sends E3 when a customer opens a ticket from the web',
                ],
                [
                    'id' => 'F3-57',
                    'descrizione' => 'E9 e una notifica in-app arrivano a ogni destinatario staff quando un messaggio va in quarantena',
                    'test_automatico' => 'tests/Feature/Domain/Mail/Listeners/NotifyStaffOfUnknownSenderTest.php::sends E9 and an in-app notification to every resolved staff recipient',
                ],
                [
                    'id' => 'F3-58',
                    'descrizione' => 'Cambiare il gruppo staff in configurazione cambia i destinatari senza toccare Mailable/listener',
                    'test_automatico' => 'tests/Feature/Domain/Mail/Actions/SendNewCustomerTicketStaffMailTest.php::changing the staff group in config changes the recipients without touching the mailable or listener',
                ],
            ],
        ],
        [
            'titolo' => 'Mailable E4 — cambio di stato (US-313)',
            'test' => [
                [
                    'id' => 'F3-59',
                    'descrizione' => 'E4 viene inviata ai destinatari del ticket quando lo stato cambia',
                    'test_automatico' => 'tests/Feature/Domain/Mail/Listeners/SendTicketStatusChangedNotificationTest.php::sends E4 to the ticket recipients when the status changes',
                ],
                [
                    'id' => 'F3-60',
                    'descrizione' => 'Il contenuto mostra un testo diverso per un destinatario cliente rispetto allo staff',
                    'test_automatico' => 'tests/Feature/Domain/Mail/Mailables/TicketStatusChangedMailTest.php::shows different wording for a customer recipient than for a staff recipient',
                ],
                [
                    'id' => 'F3-61',
                    'descrizione' => 'L\'attore dell\'azione è sempre escluso dai destinatari, anche quando la tabella lo indicherebbe',
                    'test_automatico' => 'tests/Feature/Domain/Mail/Actions/SendTicketStatusChangedMailTest.php::excludes the actor even when the table would otherwise notify them',
                ],
                [
                    'id' => 'F3-62',
                    'descrizione' => 'La notifica raggiunge il ruolo atteso per ciascuna transizione della tabella (US-318)',
                    'test_automatico' => 'tests/Feature/Domain/Mail/Actions/SendTicketStatusChangedMailTest.php::sends the notification to the expected role for each table-driven transition',
                ],
            ],
        ],
        [
            'titolo' => 'Mailable E5 — nuovo messaggio sul ticket (US-314)',
            'test' => [
                [
                    'id' => 'F3-63',
                    'descrizione' => 'E5 viene inviata ai destinatari del ticket quando un messaggio pubblico viene pubblicato',
                    'test_automatico' => 'tests/Feature/Domain/Mail/Listeners/SendNewTicketMessageNotificationTest.php::sends E5 to the ticket recipients when a public message is posted',
                ],
                [
                    'id' => 'F3-64',
                    'descrizione' => 'Un messaggio interno non genera mai E5, nemmeno verso lo staff',
                    'test_automatico' => 'tests/Feature/Domain/Mail/Listeners/SendNewTicketMessageNotificationTest.php::does not send E5 when the posted message is internal',
                ],
                [
                    'id' => 'F3-65',
                    'descrizione' => 'I destinatari sono richiedente, assegnatario e tester, escluso l\'autore del messaggio',
                    'test_automatico' => 'tests/Feature/Domain/Mail/Actions/SendNewTicketMessageMailTest.php::notifies requester, assignee and tester but excludes the author of the message',
                ],
            ],
        ],
        [
            'titolo' => 'Mailable E6 — assegnazione (US-315)',
            'test' => [
                [
                    'id' => 'F3-66',
                    'descrizione' => 'E6 viene accodata al nuovo assegnatario quando TicketAssigned viene dispatchato',
                    'test_automatico' => 'tests/Feature/Domain/Mail/Listeners/SendTicketAssignedNotificationTest.php::sends E6 to the new assignee when TicketAssigned is dispatched',
                ],
                [
                    'id' => 'F3-67',
                    'descrizione' => 'Nessuna notifica se il nuovo assegnatario è l\'attore che ha eseguito l\'azione',
                    'test_automatico' => 'tests/Feature/Domain/Mail/Actions/SendTicketAssignedMailTest.php::does not notify anyone when the new assignee performed the action themselves',
                ],
                [
                    'id' => 'F3-68',
                    'descrizione' => 'E6 viene inviata anche al nuovo tester quando TicketTesterAssigned viene dispatchato',
                    'test_automatico' => 'tests/Feature/Domain/Mail/Listeners/SendTicketTesterAssignedNotificationTest.php::sends E6 to the new tester when TicketTesterAssigned is dispatched',
                ],
            ],
        ],
        [
            'titolo' => 'Mailable E7 — reminder ticket in attesa + scheduling (US-316)',
            'test' => [
                [
                    'id' => 'F3-69',
                    'descrizione' => 'Il comando invia il reminder al richiedente di un ticket waiting fermo da almeno la soglia',
                    'test_automatico' => 'tests/Feature/Console/TicketsRemindWaitingCommandTest.php::reminds the requester of a waiting ticket idle for at least the threshold',
                ],
                [
                    'id' => 'F3-70',
                    'descrizione' => 'Un ticket già ricordato di recente non riceve un secondo reminder nella finestra di cooldown',
                    'test_automatico' => 'tests/Feature/Console/TicketsRemindWaitingCommandTest.php::skips a ticket already reminded within the cooldown window',
                ],
            ],
        ],
        [
            'titolo' => 'Preferenze di notifica — applicazione effettiva (US-317)',
            'test' => [
                [
                    'id' => 'F3-71',
                    'descrizione' => 'Un tipo di notifica senza nessuna riga preferenza è consentito per default',
                    'test_automatico' => 'tests/Unit/Domain/Mail/Support/NotificationGateTest.php::allows a notification type with no preference row at all (default enabled)',
                ],
                [
                    'id' => 'F3-72',
                    'descrizione' => 'Un tipo di notifica esplicitamente disabilitato nelle preferenze viene bloccato',
                    'test_automatico' => 'tests/Unit/Domain/Mail/Support/NotificationGateTest.php::blocks a notification type explicitly disabled in preferences',
                ],
            ],
        ],
        [
            'titolo' => 'Regole di destinazione — attore × transizione → destinatari (US-318)',
            'test' => [
                [
                    'id' => 'F3-73',
                    'descrizione' => 'La transizione "new to rejected" risolve al solo richiedente',
                    'test_automatico' => 'tests/Unit/Domain/Mail/Support/NotificationRecipientResolverTest.php::new to rejected resolves to the requester only',
                ],
                [
                    'id' => 'F3-74',
                    'descrizione' => 'Il richiedente è escluso quando è anche lui l\'attore dell\'azione',
                    'test_automatico' => 'tests/Unit/Domain/Mail/Support/NotificationRecipientResolverTest.php::the requester is excluded when they are also the actor',
                ],
                [
                    'id' => 'F3-75',
                    'descrizione' => '"problem" risolve a ogni manager attivo, escludendo l\'attore se è lui stesso un manager',
                    'test_automatico' => 'tests/Unit/Domain/Mail/Support/NotificationRecipientResolverTest.php::problem resolves to every active manager, excluding the actor if they are a manager',
                ],
            ],
        ],
        [
            'titolo' => 'Bounce, DSN e soppressioni (US-319)',
            'test' => [
                [
                    'id' => 'F3-76',
                    'descrizione' => 'Il DSN è correlato all\'email/ticket originale via Message-ID citato nel report',
                    'test_automatico' => 'tests/Feature/Domain/Mail/Actions/ProcessDeliveryStatusNotificationTest.php::il DSN è correlato al ticket',
                ],
                [
                    'id' => 'F3-77',
                    'descrizione' => 'Un hard bounce (Action: failed) sospende permanentemente il destinatario originale',
                    'test_automatico' => 'tests/Feature/Domain/Mail/Actions/ProcessDeliveryStatusNotificationTest.php::un hard bounce (Action: failed) sospende permanentemente il destinatario originale',
                ],
                [
                    'id' => 'F3-78',
                    'descrizione' => 'Un soft bounce sotto soglia incrementa bounce_count senza attivare la sospensione',
                    'test_automatico' => 'tests/Feature/Domain/Mail/Actions/ProcessDeliveryStatusNotificationTest.php::un soft bounce sotto soglia incrementa bounce_count senza attivare la sospensione',
                ],
                [
                    'id' => 'F3-79',
                    'descrizione' => 'Un soft bounce che raggiunge la soglia configurata attiva la sospensione',
                    'test_automatico' => 'tests/Feature/Domain/Mail/Actions/ProcessDeliveryStatusNotificationTest.php::un soft bounce che raggiunge la soglia configurata attiva la sospensione',
                ],
                [
                    'id' => 'F3-80',
                    'descrizione' => 'Un hard bounce correlato aggiorna anche lo stato dell\'email originale a bounced',
                    'test_automatico' => 'tests/Feature/Domain/Mail/Actions/ProcessDeliveryStatusNotificationTest.php::un hard bounce correlato aggiorna anche lo stato',
                ],
                [
                    'id' => 'F3-81',
                    'descrizione' => 'Le soppressioni sono rimovibili da amministrazione, riabilitando l\'invio',
                    'test_automatico' => 'tests/Feature/Filament/Mail/EmailSuppressionsTest.php::a user with email.manage can remove a suppression, re-enabling delivery',
                ],
            ],
        ],
        [
            'titolo' => 'Localizzazione reale delle comunicazioni (US-320)',
            'test' => [
                [
                    'id' => 'F3-82',
                    'descrizione' => 'La lingua risolve a users.locale quando è impostato',
                    'test_automatico' => 'tests/Unit/Domain/Mail/Support/RecipientLocaleTest.php::resolves to users.locale when it is set',
                ],
                [
                    'id' => 'F3-83',
                    'descrizione' => 'Fallback alla locale della prima organizzazione quando users.locale è vuoto',
                    'test_automatico' => 'tests/Unit/Domain/Mail/Support/RecipientLocaleTest.php::falls back to the first organization locale when users.locale is empty',
                ],
                [
                    'id' => 'F3-84',
                    'descrizione' => 'Fallback a config app.locale quando né users.locale né una locale organizzazione sono impostati',
                    'test_automatico' => 'tests/Unit/Domain/Mail/Support/RecipientLocaleTest.php::falls back to config app.locale when neither users.locale nor an organization locale is set',
                ],
                [
                    'id' => 'F3-85',
                    'descrizione' => 'Ogni chiave di traduzione usata dalla pipeline di Fase 3 esiste, non vuota, in italiano e inglese',
                    'test_automatico' => 'tests/Feature/Domain/Mail/LocalizationCompletenessTest.php::every translation key used by the Fase 3 mail pipeline exists, non-empty, in both it.json and en.json',
                ],
                [
                    'id' => 'F3-86',
                    'descrizione' => 'Il subject viene costruito nella locale dell\'assegnatario, non sempre in italiano',
                    'test_automatico' => 'tests/Feature/Domain/Mail/Actions/SendTicketAssignedMailTest.php::builds the subject in the assignee locale, not always Italian (§7.6, US-320)',
                ],
            ],
        ],
        [
            'titolo' => 'Amministrazione email — Registro e dettaglio (US-321)',
            'test' => [
                [
                    'id' => 'F3-87',
                    'descrizione' => 'Un utente senza email.view non accede al registro email',
                    'test_automatico' => 'tests/Feature/Filament/Mail/EmailMessageResourceTest.php::a user without email.view is denied access to the email messages resource',
                ],
                [
                    'id' => 'F3-88',
                    'descrizione' => 'La tabella è filtrabile per direzione',
                    'test_automatico' => 'tests/Feature/Filament/Mail/EmailMessageResourceTest.php::the table is filterable by direction',
                ],
                [
                    'id' => 'F3-89',
                    'descrizione' => 'La tabella è filtrabile per stato',
                    'test_automatico' => 'tests/Feature/Filament/Mail/EmailMessageResourceTest.php::the table is filterable by status',
                ],
                [
                    'id' => 'F3-90',
                    'descrizione' => 'La vista di dettaglio mostra header, corpo, allegati e diagnostica',
                    'test_automatico' => 'tests/Feature/Filament/Mail/EmailMessageResourceTest.php::viewing a message shows headers, body, attachments and diagnostics',
                ],
                [
                    'id' => 'F3-91',
                    'descrizione' => 'La risorsa non ha pagina di creazione né di modifica manuale',
                    'test_automatico' => 'tests/Feature/Filament/Mail/EmailMessageResourceTest.php::the email messages resource has no create or edit page',
                ],
            ],
        ],
        [
            'titolo' => 'Amministrazione email — Azioni e quarantena (US-322)',
            'test' => [
                [
                    'id' => 'F3-92',
                    'descrizione' => 'Un admin può riprocessare un messaggio tramite l\'azione dedicata',
                    'test_automatico' => 'tests/Feature/Filament/Mail/EmailMessageResourceTest.php::an admin can reprocess a message via the action',
                ],
                [
                    'id' => 'F3-93',
                    'descrizione' => 'Un admin può assegnare un mittente a un messaggio in quarantena tramite l\'azione dedicata',
                    'test_automatico' => 'tests/Feature/Filament/Mail/EmailMessageResourceTest.php::an admin can assign a sender to a quarantined message via the action',
                ],
                [
                    'id' => 'F3-94',
                    'descrizione' => 'Un admin può collegare un messaggio a un altro ticket tramite l\'azione dedicata',
                    'test_automatico' => 'tests/Feature/Filament/Mail/EmailMessageResourceTest.php::an admin can link a message to a different ticket via the action',
                ],
                [
                    'id' => 'F3-95',
                    'descrizione' => 'La pagina Quarantena può associare un utente esistente e riprocessare il messaggio',
                    'test_automatico' => 'tests/Feature/Filament/Mail/EmailMessageResourceTest.php::the quarantine page can associate an existing user and reprocess the message',
                ],
                [
                    'id' => 'F3-96',
                    'descrizione' => 'La pagina Quarantena può creare un nuovo utente e riprocessare il messaggio',
                    'test_automatico' => 'tests/Feature/Filament/Mail/EmailMessageResourceTest.php::the quarantine page can create a new user and reprocess the message',
                ],
                [
                    'id' => 'F3-97',
                    'descrizione' => 'Ogni azione amministrativa è tracciata (chi, quando) in email_message_logs',
                    'test_automatico' => 'tests/Feature/Domain/Mail/Actions/EmailAdministrationActionsTest.php::riprocessa rilancia la pipeline da classified e traccia',
                ],
            ],
        ],
        [
            'titolo' => 'Amministrazione email — Soppressioni e metriche (US-323)',
            'test' => [
                [
                    'id' => 'F3-98',
                    'descrizione' => 'L\'elenco soppressioni è filtrabile per motivo',
                    'test_automatico' => 'tests/Feature/Filament/Mail/EmailSuppressionsTest.php::the reason filter narrows the suppressions list',
                ],
                [
                    'id' => 'F3-99',
                    'descrizione' => 'Un admin con email.manage può rimuovere una soppressione, riabilitando l\'invio',
                    'test_automatico' => 'tests/Feature/Filament/Mail/EmailSuppressionsTest.php::a user with email.manage can remove a suppression, re-enabling delivery',
                ],
                [
                    'id' => 'F3-100',
                    'descrizione' => 'Le metriche contano elaborati/scartati/falliti nelle ultime 24h',
                    'test_automatico' => 'tests/Unit/Domain/Mail/Support/EmailPipelineMetricsTest.php::counts processed, discarded and failed messages updated in the last 24h',
                ],
                [
                    'id' => 'F3-101',
                    'descrizione' => 'Il bounce rate è calcolato su invii tentati (bounced + queued), mai su sent',
                    'test_automatico' => 'tests/Unit/Domain/Mail/Support/EmailPipelineMetricsTest.php::computes bounce rate over attempted outbound sends (bounced + queued), never sent',
                ],
            ],
        ],
        [
            'titolo' => 'Voce di menu Email con Mailpit come prima sotto-voce (US-324)',
            'test' => [
                [
                    'id' => 'F3-102',
                    'descrizione' => 'La voce Mailpit è visibile in locale con l\'URL configurato',
                    'test_automatico' => 'tests/Feature/Filament/Mail/MailpitNavigationItemTest.php::the Mailpit item is visible in local with the URL configured',
                ],
                [
                    'id' => 'F3-103',
                    'descrizione' => 'La voce Mailpit è nascosta in produzione anche con l\'URL configurato',
                    'test_automatico' => 'tests/Feature/Filament/Mail/MailpitNavigationItemTest.php::the Mailpit item is hidden in production even with the URL configured',
                ],
                [
                    'id' => 'F3-104',
                    'descrizione' => 'La voce Mailpit è la prima voce di navigazione nel gruppo Email',
                    'test_automatico' => 'tests/Feature/Filament/Mail/MailpitNavigationItemTest.php::the Mailpit item is registered in the Email group as the first navigation item',
                ],
            ],
        ],
        [
            'titolo' => 'Comando mail:retry-failed (US-325)',
            'test' => [
                [
                    'id' => 'F3-105',
                    'descrizione' => 'Il comando riaccoda tutti i messaggi outbound falliti',
                    'test_automatico' => 'tests/Feature/Console/MailRetryFailedCommandTest.php::riaccoda tutti i messaggi outbound falliti',
                ],
                [
                    'id' => 'F3-106',
                    'descrizione' => 'Un destinatario finito in soppressione blocca il reinvio ma il comando prosegue con gli altri',
                    'test_automatico' => 'tests/Feature/Console/MailRetryFailedCommandTest.php::un destinatario in soppressione blocca il reinvio ma il comando prosegue con gli altri',
                ],
                [
                    'id' => 'F3-107',
                    'descrizione' => '--email-message reinvia solo il messaggio indicato',
                    'test_automatico' => 'tests/Feature/Console/MailRetryFailedCommandTest.php::--email-message reinvia solo il messaggio indicato',
                ],
            ],
        ],
        [
            'titolo' => 'Checkpoint di fine fase — verifica end-to-end su dati reali (US-326)',
            'test' => [
                [
                    'id' => 'F3-108',
                    'descrizione' => 'Una risposta via VERP a una notifica accoda un messaggio sul ticket esistente invece di crearne uno nuovo',
                    'test_automatico' => 'tests/Feature/Console/MailFetchInboundPipelineTest.php::una risposta via VERP a una notifica accoda un messaggio sul ticket esistente invece di crearne uno nuovo',
                ],
                [
                    'id' => 'F3-109',
                    'descrizione' => 'Una risposta su un ticket importato dal v1 risolve via token subject anche senza VERP disponibile',
                    'test_automatico' => 'tests/Feature/Console/MailFetchInboundPipelineTest.php::una risposta su un ticket importato dal v1 risolve via token subject anche senza VERP disponibile',
                ],
                [
                    'id' => 'F3-110',
                    'descrizione' => 'Un hard bounce sospende permanentemente il destinatario originale, non crea ticket e non genera auto-reply',
                    'test_automatico' => 'tests/Feature/Console/MailFetchInboundPipelineTest.php::un hard bounce sospende permanentemente il destinatario originale, non crea ticket e non genera auto-reply',
                ],
                [
                    'id' => 'F3-111',
                    'descrizione' => 'Un mittente già in blacklist anti-loop viene scartato e riprocessare lo stesso messaggio non duplica nulla',
                    'test_automatico' => 'tests/Feature/Console/MailFetchInboundPipelineTest.php::un mittente già in blacklist anti-loop viene scartato e riprocessare lo stesso messaggio non duplica nulla',
                ],
                [
                    'id' => 'F3-112',
                    'descrizione' => 'Un mittente sconosciuto va in quarantena e resta ispezionabile in amministrazione (US-321) insieme a un messaggio scartato',
                    'test_automatico' => 'tests/Feature/Console/MailFetchInboundPipelineTest.php::un mittente sconosciuto va in quarantena, resta ispezionabile in amministrazione (US-321) insieme a un messaggio scartato',
                ],
                [
                    'id' => 'F3-113',
                    'descrizione' => 'La conferma di apertura ticket via email arriva nella lingua del richiedente (US-320) attraverso tutta la pipeline end-to-end',
                    'test_automatico' => 'tests/Feature/Console/MailFetchInboundPipelineTest.php::la conferma di apertura ticket via email arriva nella lingua del richiedente (US-320) attraverso tutta la pipeline',
                ],
            ],
        ],
    ],
];
