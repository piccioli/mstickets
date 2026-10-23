<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Enums\CaiHutStatus;
use App\Domain\CaiDirectory\Enums\CaiHutType;
use App\Domain\CaiDirectory\Enums\CaiTrailPracticability;
use App\Domain\CaiDirectory\Enums\CaiTrailStatus;
use App\Domain\CaiDirectory\Import\CaiOsm2caiExtractWriter;
use App\Domain\CaiDirectory\Models\CaiDocument;
use App\Domain\CaiDirectory\Models\CaiHut;
use App\Domain\CaiDirectory\Models\CaiRuntsRegistration;
use App\Domain\CaiDirectory\Models\CaiSection;
use App\Domain\CaiDirectory\Models\CaiTrail;
use App\Domain\Identity\Enums\Permission as PermissionEnum;
use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use App\Domain\Tags\Models\Tag;
use App\Domain\Ticketing\Enums\TicketLogEvent;
use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Models\TicketLog;
use App\Domain\Ticketing\Models\TicketMessage;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
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

/**
 * Scrive un foglio Excel "Sezioni CAI con CF e P.IVA" di fixture (stesso formato del
 * foglio manuale del committente: nome sezione, CF, PIVA, senza `codice_cai`), letto da
 * `CaiTaxCodeFallbackGenerator`/`cai:generate-tax-code-fallback` — il chiamante è
 * responsabile di `unlink()` il file al termine del test.
 *
 * @param  list<list<string>>  $rows
 */
function writeCaiTaxCodeFallbackFixtureXlsx(array $rows): string
{
    $path = tempnam(sys_get_temp_dir(), 'cai-tax-code-fallback-fixture-');

    $writer = new XlsxWriter;
    $writer->openToFile($path);
    $writer->addRow(Row::fromValues(['Sezione / Gruppo Regionale', 'Codice Fiscale', 'Partita IVA']));

    foreach ($rows as $row) {
        $writer->addRow(Row::fromValues($row));
    }

    $writer->close();

    return $path;
}

/**
 * Crea una `CaiRuntsRegistration` di test con un `id_runts` univoco (sequenza incrementale), collegata a una
 * `CaiSection` fresca se `cai_section_id` non è passato esplicitamente.
 *
 * @param  array<string, mixed>  $attributes
 */
function caiRuntsRegistration(array $attributes = []): CaiRuntsRegistration
{
    static $sequence = 0;
    $sequence++;

    if (! array_key_exists('cai_section_id', $attributes)) {
        $attributes['cai_section_id'] = caiSection()->codice_cai;
    }

    return CaiRuntsRegistration::create(array_merge([
        'id_runts' => 'RUNTS-'.$sequence,
        'name' => 'Ente RUNTS '.$sequence,
    ], $attributes))->fresh();
}

/**
 * Crea un `CaiDocument` di test collegato a una `CaiRuntsRegistration` fresca se `cai_runts_registration_id`
 * non è passato esplicitamente. Il file su `Storage::disk('cai-documents')` NON viene scritto qui: i test
 * che ne hanno bisogno lo fanno esplicitamente con `Storage::fake('cai-documents')` + `Storage::disk(...)->put(...)`.
 *
 * @param  array<string, mixed>  $attributes
 */
function caiDocument(array $attributes = []): CaiDocument
{
    static $sequence = 0;
    $sequence++;

    if (! array_key_exists('cai_runts_registration_id', $attributes)) {
        $attributes['cai_runts_registration_id'] = caiRuntsRegistration()->id_runts;
    }

    return CaiDocument::create(array_merge([
        'document_type' => 'bilancio_esercizio',
        'year' => 2024,
        'title' => 'Bilancio di esercizio 2024',
        'file_path' => 'test/bilancio-'.$sequence.'.pdf',
        'file_name' => 'bilancio-'.$sequence.'.pdf',
        'mime_type' => 'application/pdf',
        'size' => 100,
        'hash' => 'hash-'.$sequence,
    ], $attributes))->fresh();
}

/**
 * Rifugio di `cai_huts` (già importato): custodito, in attività, con coordinate in Trentino, salvo override.
 *
 * @param  array<string, mixed>  $attributes
 */
function caiHut(array $attributes = []): CaiHut
{
    static $sequence = 0;
    $sequence++;

    return CaiHut::create(array_merge([
        'id_cai' => 100000 + $sequence,
        'name' => 'Rifugio '.$sequence,
        'type' => CaiHutType::Managed,
        'status' => CaiHutStatus::Active,
        'latitude' => 46.1 + $sequence / 1000,
        'longitude' => 10.8 + $sequence / 1000,
        'altitude' => 2000,
        'municipality' => 'Pinzolo',
        'province' => 'TN',
        'beds' => 20,
        'photos' => [],
    ], $attributes))->fresh();
}

/**
 * Dettaglio API rifugi.cai.it come salvato in `snap_cai_huts.payload`.
 *
 * @param  array<string, mixed>  $fields  nome → valore
 * @return array<string, mixed>
 */
function hutApiDetail(int $idCai, string $title, array $fields = []): array
{
    $fields += [
        'branch_id' => '9218001',
        'branch' => 'SAT',
        'type' => 'Rifugio custodito',
        'status' => 'In attività',
        'altitude_geo' => 2491,
        'municipality_geo' => 'San Lorenzo Dorsino',
        'posti_letto_service' => '150',
        'wifi_service' => '0',
        'elettricita_service' => '1',
        'contract_fee_management_property' => '48564,05',
        'contract_end_date_management_property' => '2027-03-31',
    ];

    return [
        'id' => $idCai,
        'id_cai' => $idCai,
        'title' => $title,
        'slug' => str($title)->slug()->value(),
        'geo' => ['type' => 'Point', 'coordinates' => [10.8764, 46.1697]],
        'updated_at' => '2026-09-12T08:30:00.000000Z',
        'fields' => collect($fields)->map(fn ($value, $name): array => ['name' => $name, 'value' => $value, 'data' => null])->values()->all(),
        'media' => [['original_url' => 'https://rifugi.cai.it/media/images/1/tosa.jpg', 'order_column' => 1]],
    ];
}

/**
 * Datapack con le tabelle legacy vuote e `snap_cai_huts` con i dettagli indicati.
 *
 * @param  list<array<string, mixed>>  $details
 */
function makeHutsDatapack(array $details, ?string $sqlite = null): string
{
    if ($sqlite === null) {
        $dir = sys_get_temp_dir().'/snap-huts-'.bin2hex(random_bytes(4));
        mkdir($dir);
        $sqlite = $dir.'/runts-cai.sqlite';
    }

    $pdo = new PDO('sqlite:'.$sqlite);
    $pdo->exec('CREATE TABLE IF NOT EXISTS sezioni_cai (codice_cai TEXT)');
    $pdo->exec('CREATE TABLE IF NOT EXISTS sottosezioni_cai (cai_codice TEXT, cai_sezione_codice TEXT)');
    $pdo->exec('CREATE TABLE IF NOT EXISTS enti (id_runts TEXT)');
    $pdo->exec('CREATE TABLE IF NOT EXISTS bilanci (id INTEGER)');
    $pdo->exec('CREATE TABLE IF NOT EXISTS cariche_sociali (id INTEGER)');
    $pdo->exec('CREATE TABLE IF NOT EXISTS allegati (id INTEGER)');
    $pdo->exec('DROP TABLE IF EXISTS snap_cai_huts');
    $pdo->exec('CREATE TABLE snap_cai_huts (id_cai INTEGER PRIMARY KEY, payload TEXT NOT NULL)');
    $insert = $pdo->prepare('INSERT INTO snap_cai_huts (id_cai, payload) VALUES (?, ?)');

    foreach ($details as $detail) {
        $insert->execute([$detail['id_cai'], json_encode($detail)]);
    }

    return $sqlite;
}

/**
 * Riga di `snap_osm2cai_trails` di fixture (US-1052): sentiero validato e percorribile, con geometria a due punti.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function osm2caiTrailRow(int $id, array $overrides = []): array
{
    return array_merge([
        'id' => $id,
        'osmid' => 7000 + $id,
        'ref' => (string) $id,
        'name' => 'Sentiero '.$id,
        'from_place' => 'Rifugio A',
        'to_place' => 'Passo B',
        'cai_scale' => 'E',
        'length_km' => 5.432,
        'ascent' => 640,
        'descent' => 12,
        'status' => 4,
        'issues_status' => 'percorribile',
        'issues_last_update' => '2025-06-01',
        'validation_date' => '2024-09-15',
        'geometry_geojson' => '{"type":"MultiLineString","coordinates":[[[10.1,43.1],[10.2,43.2]]]}',
    ], $overrides);
}

/**
 * Datapack con le tabelle legacy vuote e le tabelle `snap_osm2cai_*` scritte da `CaiOsm2caiExtractWriter` (US-1052).
 *
 * @param  list<array<string, mixed>>  $trails  righe di {@see osm2caiTrailRow()}
 * @param  list<array{0: int, 1: string}>  $links  coppie (id sentiero, codice di Sezione)
 */
function makeTrailsDatapack(array $trails, array $links, ?string $sqlite = null): string
{
    $sqlite = makeHutsDatapack([], $sqlite);
    $pdo = new PDO('sqlite:'.$sqlite);
    $pdo->exec('DROP TABLE snap_cai_huts');

    foreach ([
        CaiOsm2caiExtractWriter::TRAILS_TABLE => CaiOsm2caiExtractWriter::TRAIL_COLUMNS,
        CaiOsm2caiExtractWriter::TRAIL_SECTIONS_TABLE => CaiOsm2caiExtractWriter::TRAIL_SECTION_COLUMNS,
    ] as $table => $columns) {
        $pdo->exec("DROP TABLE IF EXISTS {$table}");
        $pdo->exec("CREATE TABLE {$table} (".implode(', ', array_map(fn (string $column, string $type): string => "{$column} {$type}", array_keys($columns), $columns)).')');
    }

    $insert = $pdo->prepare('INSERT INTO '.CaiOsm2caiExtractWriter::TRAILS_TABLE.' ('.implode(', ', array_keys(CaiOsm2caiExtractWriter::TRAIL_COLUMNS)).') VALUES ('.implode(', ', array_fill(0, count(CaiOsm2caiExtractWriter::TRAIL_COLUMNS), '?')).')');

    foreach ($trails as $trail) {
        $insert->execute(array_map(fn (string $column): mixed => $trail[$column] ?? null, array_keys(CaiOsm2caiExtractWriter::TRAIL_COLUMNS)));
    }

    $insert = $pdo->prepare('INSERT INTO '.CaiOsm2caiExtractWriter::TRAIL_SECTIONS_TABLE.' (trail_id, section_code) VALUES (?, ?)');

    foreach ($links as [$trailId, $code]) {
        $insert->execute([$trailId, $code]);
    }

    return $sqlite;
}

/**
 * Sentiero osm2cai salvato in `cai_trails` (US-1054), collegato alle Sezioni con i codici indicati: validato,
 * percorribile, 5,4 km, con geometria a due punti.
 *
 * @param  array<string, mixed>  $attributes
 * @param  list<string>  $sectionCodes
 */
function caiTrail(array $attributes = [], array $sectionCodes = []): CaiTrail
{
    static $sequence = 0;
    $sequence++;

    $trail = CaiTrail::create(array_merge([
        'id' => 500000 + $sequence,
        'osmid' => 9000000 + $sequence,
        'ref' => (string) $sequence,
        'name' => 'Sentiero '.$sequence,
        'from_place' => 'Rifugio A',
        'to_place' => 'Passo B',
        'cai_scale' => 'E',
        'length_km' => 5.4,
        'status' => CaiTrailStatus::Validated,
        'issues_status' => 'percorribile',
        'practicability' => CaiTrailPracticability::Practicable,
        'geometry' => ['type' => 'MultiLineString', 'coordinates' => [[[10.1 + $sequence / 1000, 43.1], [10.2, 43.2]]]],
    ], $attributes));

    foreach ($sectionCodes as $code) {
        DB::table('cai_trail_section')->insert(['cai_trail_id' => $trail->id, 'section_code' => $code]);
    }

    return $trail->fresh() ?? $trail;
}

/**
 * Cartella temporanea con i CSV della fixture, `$overrides` nome file → contenuto (null = file assente).
 *
 * @param  array<string, string|null>  $overrides
 */
function osm2caiCsvDirectory(array $overrides): string
{
    $dir = sys_get_temp_dir().'/osm2cai-csv-'.bin2hex(random_bytes(4));
    mkdir($dir);

    foreach (['trails.csv', 'trail_sections.csv', 'regions.csv', 'provinces.csv', 'sectors.csv', 'territory_km.csv', 'trail_territory.csv'] as $file) {
        $content = array_key_exists($file, $overrides) ? $overrides[$file] : file_get_contents(base_path('tests/Fixtures/osm2cai/'.$file));

        if ($content !== null) {
            file_put_contents($dir.'/'.$file, $content);
        }
    }

    return $dir;
}

/**
 * Datapack con le tabelle legacy vuote e tutte le tabelle `snap_osm2cai_*` scritte da `CaiOsm2caiExtractWriter` a
 * partire dai CSV di `tests/Fixtures/osm2cai` (US-1062); `$overrides` come {@see osm2caiCsvDirectory()}.
 *
 * @param  array<string, string|null>  $overrides
 */
function makeTrailTerritoriesDatapack(array $overrides = [], ?string $sqlite = null): string
{
    $sqlite = makeHutsDatapack([], $sqlite);
    $pdo = new PDO('sqlite:'.$sqlite);
    $pdo->exec('DROP TABLE snap_cai_huts');
    (new CaiOsm2caiExtractWriter)->write($pdo, osm2caiCsvDirectory($overrides));

    return $sqlite;
}
