<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Models\CaiSection;
use App\Domain\Identity\Enums\Permission as PermissionEnum;
use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use App\Domain\Tags\Models\Tag;
use App\Domain\Ticketing\Enums\TicketLogEvent;
use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Models\TicketLog;
use App\Domain\Ticketing\Models\TicketMessage;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

pest()->extend(TestCase::class)->in('Feature', 'Unit');

/**
 * Crea un utente a cui sono concessi esattamente i permessi indicati (creando la riga
 * `permissions` se non esiste ancora). Usata dai test delle policy (US-019) per verificare
 * il deny-by-default senza dover eseguire l'intero RolePermissionSeeder.
 */
function userWithPermissions(PermissionEnum ...$permissions): User
{
    $user = User::factory()->create();

    foreach ($permissions as $permission) {
        Permission::query()->firstOrCreate(['name' => $permission->value, 'guard_name' => 'web']);
    }

    if ($permissions !== []) {
        $user->givePermissionTo(array_map(
            static fn (PermissionEnum $permission): string => $permission->value,
            $permissions,
        ));
    }

    return $user;
}

/**
 * Esegue una Validation Rule (US-102) su un valore isolato e riporta se `$fail()` è
 * stato invocato, senza dover passare da un Validator/richiesta HTTP completa.
 */
function ruleFails(ValidationRule $rule, mixed $value, string $attribute = 'value'): bool
{
    $failed = false;

    $rule->validate($attribute, $value, function () use (&$failed): void {
        $failed = true;
    });

    return $failed;
}

/**
 * Crea un ticket con i soli attributi obbligatori dello schema (`title`,
 * `status_changed_at`), riusato da qualunque test che ha solo bisogno di un ticket
 * "esiste" senza badare al contenuto degli altri campi (macchina a stati, Action, Rule).
 *
 * @param  array<string, mixed>  $attributes
 */
function ticket(array $attributes = []): Ticket
{
    return Ticket::create(array_merge([
        'title' => 'Errore login',
        'status_changed_at' => now(),
    ], $attributes))->fresh();
}

/**
 * Crea un utente con l'email configurata come utente di sistema (§6.2.1): riconosciuto
 * da `User::isSystem()`/attore `TransitionActor::System` senza dover passare dal comando
 * `orchestrator:doctor` in un test.
 */
function systemUser(): User
{
    return User::factory()->create(['email' => config('orchestrator.system_user.email')]);
}

/**
 * Crea un ticket_message pubblico/web con i soli attributi obbligatori dello schema
 * (`ticket_id`, `channel`, `posted_at`), riusato da qualunque test che ha solo bisogno
 * di un messaggio "esiste" a cui allegare file (US-107) senza passare da
 * `PostTicketMessage::run()` (che sanitizza/emette eventi non pertinenti a quei test).
 *
 * @param  array<string, mixed>  $attributes
 */
function ticketMessage(array $attributes = []): TicketMessage
{
    return TicketMessage::create(array_merge([
        'ticket_id' => ticket()->id,
        'channel' => 'web',
        'posted_at' => now(),
    ], $attributes))->fresh();
}

/**
 * Crea un `ticket_log` con i soli attributi obbligatori dello schema (`ticket_id`,
 * `event`, `occurred_at`), riusato dai test di `WorkedTimeCalculator`/
 * `RecalculateWorkedTime` (US-109) per costruire una sequenza di log senza passare
 * da `ChangeTicketStatus` (che applicherebbe anche i guard della macchina a stati,
 * non pertinenti a quei test).
 *
 * @param  array<string, mixed>  $attributes
 */
function ticketLog(Ticket $ticket, array $attributes = []): TicketLog
{
    return TicketLog::create(array_merge([
        'ticket_id' => $ticket->id,
        'event' => TicketLogEvent::StatusChanged,
        'occurred_at' => now(),
    ], $attributes));
}

/**
 * Assegna un ruolo applicativo (Spatie) a uno User già esistente, creando la riga
 * `roles` se non esiste ancora. Usato dai query object di US-111 che distinguono
 * i ticket in base al ruolo del richiedente (es. `AllCustomerTicketsQuery`,
 * `InternalTicketsQuery`), non dai soli permessi diretti come `userWithPermissions()`.
 */
function withRole(User $user, UserRole $role): User
{
    Role::query()->firstOrCreate(['name' => $role->value, 'guard_name' => 'web']);
    $user->assignRole($role->value);

    return $user->fresh();
}

/**
 * Assegna un ruolo applicativo "vuoto" solo per superare il gate d'accesso al
 * pannello Filament (§9.1, US-020), isolando il test sui soli permessi diretti
 * concessi da `userWithPermissions()`. Spostato qui da `TicketResourceTest.php`
 * (US-110) per essere riusato da qualunque test Filament sul dominio Ticketing
 * (es. `TicketsTableFiltersTest.php`, US-112) senza rischiare il fatal error di
 * redeclare se più file lo dichiarassero localmente.
 */
function grantTicketPanelRole(User $user, UserRole $role = UserRole::Developer): User
{
    Role::query()->firstOrCreate(['name' => $role->value, 'guard_name' => 'web']);
    $user->assignRole($role->value);

    return $user->fresh();
}

/**
 * Crea un Tag con i soli attributi obbligatori dello schema (`name`, `slug`
 * univoco), riusato dai test dei filtri di `TicketsTable` (US-112) senza dover
 * costruire uno slug a mano ad ogni chiamata.
 *
 * @param  array<string, mixed>  $attributes
 */
function tag(array $attributes = []): Tag
{
    $name = $attributes['name'] ?? 'Commessa '.Str::random(8);

    return Tag::create(array_merge([
        'name' => $name,
        'slug' => Str::slug($name).'-'.Str::random(6),
    ], $attributes))->fresh();
}

/**
 * Costruisce una fixture SQLite del datapack RUNTS-CAI (schema §ground-truth US-802),
 * come file PHP riproducibile invece di un binario opaco versionato — nessun precedente
 * nel repo per una fixture SQLite committata (i fixture del dump v1 sono `.sql` testuali
 * caricati sulla connessione `legacy`, un caso diverso). Spostato qui da
 * `CaiImportDatapackCommandTest.php` (US-802) quando è servito anche a un secondo file
 * (verifica del sync scoped a una sola sezione, Fase 9): due sezioni, `9216049` (con
 * match utente, ente/bilancio/carica sociale/allegato collegati) e `9216050` (senza match
 * utente), utile per verificare sia il matching sia lo scoping a una sola sezione.
 *
 * @return array{sqlitePath: string, datapackDir: string}
 */
function makeCaiDatapackFixture(): array
{
    $datapackDir = sys_get_temp_dir().'/cai-datapack-test-'.uniqid();
    mkdir($datapackDir, 0755, true);
    mkdir($datapackDir.'/attachments/166339', 0755, true);

    $attachmentContent = "%PDF-1.4 fixture bilancio content\n";
    file_put_contents($datapackDir.'/attachments/166339/bilancio-2024.pdf', $attachmentContent);

    $sqlitePath = $datapackDir.'/runts-cai.sqlite';

    $pdo = new PDO("sqlite:{$sqlitePath}");
    $pdo->exec('PRAGMA foreign_keys = OFF');

    $pdo->exec(<<<'SQL'
        CREATE TABLE sezioni_cai (
            codice_cai TEXT PRIMARY KEY,
            cai_denominazione TEXT NOT NULL,
            cai_codice_fiscale TEXT,
            cai_partita_iva TEXT,
            cai_email TEXT,
            cai_pec TEXT,
            cai_telefono_sede TEXT,
            cai_telefono TEXT,
            cai_fax TEXT,
            cai_indirizzo_sede TEXT,
            cai_indirizzo_postale TEXT,
            cai_sito_web TEXT,
            cai_orari TEXT,
            cai_avvisi TEXT,
            cai_anno_fondazione INTEGER,
            cai_soci_ultimo_anno INTEGER,
            cai_lat REAL,
            cai_lon REAL,
            cai_regione TEXT NOT NULL,
            cai_scraped_at TEXT,
            cai_match_note TEXT
        )
    SQL);

    $pdo->exec(<<<'SQL'
        CREATE TABLE sottosezioni_cai (
            cai_codice TEXT PRIMARY KEY,
            cai_sezione_codice TEXT NOT NULL,
            cai_nome TEXT NOT NULL,
            cai_email TEXT,
            cai_telefono_sede TEXT,
            cai_telefono TEXT,
            cai_indirizzo_sede TEXT,
            cai_sito_web TEXT,
            cai_orari TEXT,
            cai_avvisi TEXT,
            cai_anno_fondazione INTEGER,
            cai_soci INTEGER,
            cai_lat REAL,
            cai_lon REAL,
            cai_scraped_at TEXT
        )
    SQL);

    $pdo->exec(<<<'SQL'
        CREATE TABLE enti (
            id_runts TEXT PRIMARY KEY,
            codice_fiscale TEXT UNIQUE,
            denominazione TEXT,
            forma_giuridica TEXT,
            natura_giuridica TEXT,
            sede_stato TEXT,
            sede_indirizzo TEXT,
            sede_civico TEXT,
            sede_comune TEXT,
            sede_provincia TEXT,
            sede_regione TEXT,
            sede_cap TEXT,
            lat REAL,
            lon REAL,
            data_iscrizione TEXT,
            sezione_registro TEXT,
            settori_attivita TEXT,
            rappresentante_legale TEXT,
            sito_web TEXT,
            pec TEXT,
            url_dettaglio TEXT,
            raw_json TEXT,
            updated_at TEXT NOT NULL
        )
    SQL);

    $pdo->exec(<<<'SQL'
        CREATE TABLE bilanci (
            id INTEGER PRIMARY KEY,
            id_runts TEXT NOT NULL,
            anno INTEGER NOT NULL,
            oneri_a_interesse_generale REAL,
            oneri_b_attivita_diverse REAL,
            oneri_c_raccolta_fondi REAL,
            oneri_d_finanziarie_patrimoniali REAL,
            oneri_e_supporto_generale REAL,
            totale_oneri REAL,
            proventi_a_interesse_generale REAL,
            proventi_b_attivita_diverse REAL,
            proventi_c_raccolta_fondi REAL,
            proventi_d_finanziarie_patrimoniali REAL,
            proventi_e_supporto_generale REAL,
            totale_proventi REAL,
            risultato_ante_imposte REAL,
            imposte REAL,
            risultato_esercizio REAL,
            raw_text TEXT,
            allegato_id INTEGER,
            analyzed_at TEXT NOT NULL
        )
    SQL);

    $pdo->exec(<<<'SQL'
        CREATE TABLE cariche_sociali (
            id INTEGER PRIMARY KEY,
            id_runts TEXT NOT NULL,
            ruolo TEXT NOT NULL,
            nome TEXT,
            cognome TEXT,
            codice_fiscale TEXT,
            valid_from TEXT,
            valid_to TEXT,
            updated_at TEXT NOT NULL
        )
    SQL);

    $pdo->exec(<<<'SQL'
        CREATE TABLE allegati (
            id INTEGER PRIMARY KEY,
            id_runts TEXT NOT NULL,
            documento TEXT NOT NULL,
            codice_pratica TEXT NOT NULL,
            tipo TEXT NOT NULL,
            anno INTEGER,
            filename TEXT,
            path TEXT,
            mime TEXT,
            size INTEGER,
            hash_sha256 TEXT,
            url_originale TEXT,
            skip_reason TEXT,
            downloaded_at TEXT NOT NULL
        )
    SQL);

    // Sezione con match utente case-insensitive (email sorgente TUTTA MAIUSCOLA). Indirizzo
    // nella forma reale del datapack (oggetto JSON di geocoding, non testo semplice) e una
    // coordinata fuori range (corruzione nota nel dataset reale, es. codice_cai 9220033):
    // entrambe devono essere gestite senza far fallire l'insert.
    $pdo->prepare('INSERT INTO sezioni_cai (codice_cai, cai_denominazione, cai_codice_fiscale, cai_email, cai_indirizzo_sede, cai_indirizzo_postale, cai_anno_fondazione, cai_soci_ultimo_anno, cai_lat, cai_lon, cai_regione) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
        ->execute([
            '9216049',
            'Sez. Abbiategrasso',
            'CFSEZ001',
            'SEZIONE@EXAMPLE.COM',
            json_encode(['address1' => 'Via Legnano', 'address2' => '', 'number' => '9', 'zip' => '20081', 'city' => 'ABBIATEGRASSO', 'province' => 'MI', 'nation' => '']),
            null,
            1950,
            120,
            25614,
            9.1000000,
            'LOMBARDIA',
        ]);

    // Sezione senza alcun match utente (nessun user con questa email).
    $pdo->prepare('INSERT INTO sezioni_cai (codice_cai, cai_denominazione, cai_codice_fiscale, cai_email, cai_regione) VALUES (?, ?, ?, ?, ?)')
        ->execute(['9216050', 'Sez. Senza Utente', 'CFSEZ002', 'nomatch@example.com', 'LAZIO']);

    // Sottosezione con match utente case-insensitive (email sorgente mista maiuscole/minuscole).
    $pdo->prepare('INSERT INTO sottosezioni_cai (cai_codice, cai_sezione_codice, cai_nome, cai_email, cai_anno_fondazione, cai_soci, cai_lat, cai_lon) VALUES (?, ?, ?, ?, ?, ?, ?, ?)')
        ->execute(['SUB001', '9216049', 'Sottosezione Test', 'Sub@Example.Com', 1980, 30, 45.2000000, 9.2000000]);

    // Ente con match sezione via codice fiscale case-insensitive (sorgente minuscolo),
    // data_iscrizione in formato piano DD/MM/YYYY.
    $pdo->prepare('INSERT INTO enti (id_runts, codice_fiscale, denominazione, forma_giuridica, natura_giuridica, sede_indirizzo, sede_civico, sede_comune, sede_provincia, sede_regione, sede_cap, lat, lon, data_iscrizione, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
        ->execute(['166339', 'cfsez001', 'Sez. Abbiategrasso', 'Associazione', 'Privata', 'Via Roma 1', '1', 'Milano', 'MI', 'LOMBARDIA', '20100', 45.1000000, 9.1000000, '24/02/2023', '2023-02-24T10:00:00']);

    // Ente con match sezione, data_iscrizione narrativa (data da estrarre in coda al testo).
    $pdo->prepare('INSERT INTO enti (id_runts, codice_fiscale, denominazione, sede_regione, data_iscrizione, updated_at) VALUES (?, ?, ?, ?, ?, ?)')
        ->execute(['166340', 'CFSEZ002', 'Sez. Senza Utente', 'LAZIO', 'Iscritto tramite trasmigrazione per scadenza dei termini il 07/11/2022', '2022-11-07T10:00:00']);

    // Ente SENZA alcun match su sezioni_cai: non deve essere importato in cai_runts_registrations.
    $pdo->prepare('INSERT INTO enti (id_runts, codice_fiscale, denominazione, sede_regione, updated_at) VALUES (?, ?, ?, ?, ?)')
        ->execute(['999999', 'CF-UNMATCHED', 'Ente esterno non CAI', 'VENETO', '2020-01-01T10:00:00']);

    // Bilancio per un ente importato.
    $pdo->prepare('INSERT INTO bilanci (id, id_runts, anno, totale_oneri, totale_proventi, risultato_esercizio, analyzed_at) VALUES (?, ?, ?, ?, ?, ?, ?)')
        ->execute([1, '166339', 2024, 10000.50, 12000.75, 2000.25, '2024-06-01T10:00:00']);

    // Bilancio orfano: il suo id_runts (999999) non ha un match di sezione, non deve essere importato.
    $pdo->prepare('INSERT INTO bilanci (id, id_runts, anno, totale_oneri, totale_proventi, risultato_esercizio, analyzed_at) VALUES (?, ?, ?, ?, ?, ?, ?)')
        ->execute([2, '999999', 2023, 500.0, 500.0, 0.0, '2023-06-01T10:00:00']);

    // Carica sociale per un ente importato (cariche_sociali è vuota nel dataset reale odierno,
    // qui una riga sintetica per esercitare comunque il percorso di import).
    $pdo->prepare('INSERT INTO cariche_sociali (id, id_runts, ruolo, nome, cognome, codice_fiscale, valid_from, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)')
        ->execute([1, '166339', 'Presidente', 'Mario', 'Rossi', 'RSSMRA80A01F205X', '01/01/2023', '2023-01-01T10:00:00']);

    // Allegato con file reale presente su disco: deve essere copiato.
    $pdo->prepare('INSERT INTO allegati (id, id_runts, documento, codice_pratica, tipo, anno, filename, path, mime, size, hash_sha256, downloaded_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
        ->execute([
            1,
            '166339',
            "BILANCIO D'ESERCIZIO",
            'B00',
            'bilancio_esercizio',
            2024,
            'bilancio-2024.pdf',
            'attachments/166339/bilancio-2024.pdf',
            'application/pdf',
            strlen($attachmentContent),
            hash('sha256', $attachmentContent),
            '2024-06-02T10:00:00',
        ]);

    // Allegato il cui id_runts (999999) non ha match di sezione: deve essere saltato.
    $pdo->prepare('INSERT INTO allegati (id, id_runts, documento, codice_pratica, tipo, path, downloaded_at) VALUES (?, ?, ?, ?, ?, ?, ?)')
        ->execute([2, '999999', 'STATUTO', 'A01', 'statuto', 'attachments/999999/statuto.pdf', '2024-06-02T10:00:00']);

    // Allegato il cui file fisico manca su disco: deve essere saltato (nessun errore).
    $pdo->prepare('INSERT INTO allegati (id, id_runts, documento, codice_pratica, tipo, filename, path, downloaded_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)')
        ->execute([3, '166339', 'STATUTO', 'A02', 'statuto', 'statuto.pdf', 'attachments/166339/statuto.pdf', '2024-06-02T10:00:00']);

    unset($pdo);

    return ['sqlitePath' => $sqlitePath, 'datapackDir' => $datapackDir];
}

/**
 * Crea una `CaiSection` di test con un `codice_cai` univoco (sequenza incrementale),
 * spostato qui da `CaiSectionResourceTest.php` (US-804) quando è servito anche ai test
 * dello scraper live (Fase 9).
 *
 * @param  array<string, mixed>  $attributes
 */
function caiSection(array $attributes = []): CaiSection
{
    static $sequence = 0;
    $sequence++;

    return CaiSection::create(array_merge([
        'codice_cai' => 'CAI-'.$sequence,
        'name' => 'Sezione CAI '.$sequence,
        'region' => 'LOMBARDIA',
    ], $attributes))->fresh();
}
