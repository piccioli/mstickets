<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Models\CaiBoardMember;
use App\Domain\CaiDirectory\Models\CaiDocument;
use App\Domain\CaiDirectory\Models\CaiFinancialStatement;
use App\Domain\CaiDirectory\Models\CaiRuntsRegistration;
use App\Domain\CaiDirectory\Models\CaiSection;
use App\Domain\CaiDirectory\Models\CaiSubsection;
use App\Domain\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

/**
 * Costruisce una fixture SQLite del datapack RUNTS-CAI (schema §ground-truth US-802),
 * come file PHP riproducibile invece di un binario opaco versionato — nessun precedente
 * nel repo per una fixture SQLite committata (i fixture del dump v1 sono `.sql` testuali
 * caricati sulla connessione `legacy`, un caso diverso).
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

test('missing datapack file prints an explicit message and fails, no cryptic error', function (): void {
    $this->artisan('cai:import-datapack', ['--path' => '/tmp/does-not-exist-'.uniqid().'.sqlite'])
        ->expectsOutputToContain('File datapack non trovato')
        ->assertFailed()
        ->run();
});

test('--dry-run writes nothing', function (): void {
    Storage::fake('cai-documents');
    $fixture = makeCaiDatapackFixture();

    $this->artisan('cai:import-datapack', ['--path' => $fixture['sqlitePath'], '--dry-run' => true])
        ->assertSuccessful()
        ->run();

    expect(CaiSection::query()->count())->toBe(0)
        ->and(CaiSubsection::query()->count())->toBe(0)
        ->and(CaiRuntsRegistration::query()->count())->toBe(0)
        ->and(CaiFinancialStatement::query()->count())->toBe(0)
        ->and(CaiBoardMember::query()->count())->toBe(0)
        ->and(CaiDocument::query()->count())->toBe(0);

    Storage::disk('cai-documents')->assertDirectoryEmpty('/');
});

test('full import populates all six tables with correctly mapped fields, matches users by email case-insensitively, skips unmatched enti and copies allegati files', function (): void {
    Storage::fake('cai-documents');
    $fixture = makeCaiDatapackFixture();

    $matchedUser = User::factory()->create(['email' => 'sezione@example.com']);
    $subUser = User::factory()->create(['email' => 'sub@example.com']);

    $this->artisan('cai:import-datapack', ['--path' => $fixture['sqlitePath']])
        ->assertSuccessful()
        ->run();

    // cai_sections: match case-insensitive + nessun match -> user_id null, mai un errore.
    expect(CaiSection::query()->count())->toBe(2);

    $matchedSection = CaiSection::query()->findOrFail('9216049');
    expect($matchedSection->name)->toBe('Sez. Abbiategrasso')
        ->and($matchedSection->tax_code)->toBe('CFSEZ001')
        ->and((int) $matchedSection->founded_year)->toBe(1950)
        ->and($matchedSection->user_id)->toBe($matchedUser->id)
        // Indirizzo JSON di geocoding formattato in una riga leggibile (mai il JSON grezzo).
        ->and($matchedSection->address)->toBe('Via Legnano 9, 20081 ABBIATEGRASSO (MI)')
        ->and($matchedSection->postal_address)->toBeNull()
        // Coordinata fuori range decimal(10,7) scartata a null, non fa fallire l'insert.
        ->and($matchedSection->latitude)->toBeNull();

    $unmatchedSection = CaiSection::query()->findOrFail('9216050');
    expect($unmatchedSection->user_id)->toBeNull();

    // cai_subsections: stesso matching case-insensitive.
    expect(CaiSubsection::query()->count())->toBe(1);
    $subsection = CaiSubsection::query()->findOrFail('SUB001');
    expect($subsection->cai_section_id)->toBe('9216049')
        ->and($subsection->user_id)->toBe($subUser->id);

    // cai_runts_registrations: solo gli enti con match su una sezione (166339, 166340), MAI 999999.
    expect(CaiRuntsRegistration::query()->count())->toBe(2)
        ->and(CaiRuntsRegistration::query()->find('999999'))->toBeNull();

    $registration = CaiRuntsRegistration::query()->findOrFail('166339');
    expect($registration->cai_section_id)->toBe('9216049')
        ->and($registration->municipality)->toBe('Milano')
        ->and($registration->registration_date->format('Y-m-d'))->toBe('2023-02-24');

    // Data narrativa ("Iscritto tramite trasmigrazione ... il 07/11/2022"): estratta correttamente.
    $narrativeRegistration = CaiRuntsRegistration::query()->findOrFail('166340');
    expect($narrativeRegistration->registration_date->format('Y-m-d'))->toBe('2022-11-07');

    // cai_financial_statements: solo il bilancio del runts importato, mai quello orfano (999999).
    expect(CaiFinancialStatement::query()->count())->toBe(1);
    $statement = CaiFinancialStatement::query()->sole();
    expect($statement->cai_runts_registration_id)->toBe('166339')
        ->and((int) $statement->year)->toBe(2024)
        ->and((float) $statement->net_result)->toBe(2000.25);

    // cai_board_members.
    expect(CaiBoardMember::query()->count())->toBe(1);
    $boardMember = CaiBoardMember::query()->sole();
    expect($boardMember->role)->toBe('Presidente')
        ->and($boardMember->full_name)->toBe('Mario Rossi')
        ->and($boardMember->valid_from->format('Y-m-d'))->toBe('2023-01-01');

    // cai_documents: solo l'allegato con file reale presente su disco viene importato/copiato.
    expect(CaiDocument::query()->count())->toBe(1);
    $document = CaiDocument::query()->sole();
    expect($document->cai_runts_registration_id)->toBe('166339')
        ->and($document->document_type)->toBe('bilancio_esercizio')
        ->and($document->title)->toBe("BILANCIO D'ESERCIZIO")
        ->and($document->hash)->toBe(hash('sha256', "%PDF-1.4 fixture bilancio content\n"));

    Storage::disk('cai-documents')->assertExists($document->file_path);
    expect(Storage::disk('cai-documents')->get($document->file_path))->toBe("%PDF-1.4 fixture bilancio content\n");
});

test('running the import twice against the same fixture is idempotent (no duplicates, unchanged rows not re-updated)', function (): void {
    Storage::fake('cai-documents');
    $fixture = makeCaiDatapackFixture();

    User::factory()->create(['email' => 'sezione@example.com']);
    User::factory()->create(['email' => 'sub@example.com']);

    $this->artisan('cai:import-datapack', ['--path' => $fixture['sqlitePath']])->assertSuccessful()->run();

    $section = CaiSection::query()->findOrFail('9216049');
    $registration = CaiRuntsRegistration::query()->findOrFail('166339');
    $document = CaiDocument::query()->sole();

    $sectionUpdatedAt = $section->updated_at;
    $registrationUpdatedAt = $registration->updated_at;
    $documentUpdatedAt = $document->updated_at;

    // Il secondo run avviene "più tardi": se qualcosa venisse riscritto senza motivo,
    // updated_at si sposterebbe in avanti — un test fragile su timestamp identici
    // rischierebbe di passare per caso se il secondo run fosse eseguito nello stesso
    // secondo del primo.
    $this->travel(1)->hours();

    $this->artisan('cai:import-datapack', ['--path' => $fixture['sqlitePath']])->assertSuccessful()->run();

    expect(CaiSection::query()->count())->toBe(2)
        ->and(CaiSubsection::query()->count())->toBe(1)
        ->and(CaiRuntsRegistration::query()->count())->toBe(2)
        ->and(CaiFinancialStatement::query()->count())->toBe(1)
        ->and(CaiBoardMember::query()->count())->toBe(1)
        ->and(CaiDocument::query()->count())->toBe(1);

    expect($section->fresh()->updated_at->equalTo($sectionUpdatedAt))->toBeTrue()
        ->and($registration->fresh()->updated_at->equalTo($registrationUpdatedAt))->toBeTrue()
        ->and($document->fresh()->updated_at->equalTo($documentUpdatedAt))->toBeTrue();
});
