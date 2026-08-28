<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Models\CaiSection;
use App\Domain\Identity\Enums\CustomerType;
use App\Domain\Identity\Enums\Permission as PermissionEnum;
use App\Domain\Identity\Enums\Region;
use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use App\Filament\Pages\CaiSectionRegionalDetail;
use App\Filament\Pages\CustomerDashboard;
use App\Filament\Resources\CaiSections\CaiSectionResource;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

/**
 * Checkpoint di fine Fase 8 (design doc §8, US-808): percorre end-to-end il flusso
 * completo di questa fase — import reale del datapack RUNTS-CAI da un file SQLite
 * (fixture ridotta, stesso meccanismo di {@see CaiImportDatapackCommandTest}, non
 * duplicato: qui la fixture si limita al minimo che serve a esercitare ogni tappa del
 * flusso, non a ri-testare ogni campo/gotcha di mapping già coperto lì) → collegamento
 * automatico per email → consultazione staff (CaiSectionResource) → dashboard cliente
 * Sezione (con e senza match) → dashboard cliente Gruppo Regionale (dettaglio sezione
 * della propria regione, accesso diretto negato su una sezione di un'altra regione).
 */
uses(RefreshDatabase::class);

/**
 * @return array{sqlitePath: string, datapackDir: string}
 */
function makeFase8CheckpointDatapackFixture(): array
{
    $datapackDir = sys_get_temp_dir().'/cai-datapack-fase8-checkpoint-'.uniqid();
    mkdir($datapackDir, 0755, true);
    mkdir($datapackDir.'/attachments/500001', 0755, true);

    $attachmentContent = "%PDF-1.4 fase-8 checkpoint fixture\n";
    file_put_contents($datapackDir.'/attachments/500001/bilancio-2025.pdf', $attachmentContent);

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
            cai_regione TEXT NOT NULL
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
            cai_lon REAL
        )
    SQL);

    $pdo->exec(<<<'SQL'
        CREATE TABLE enti (
            id_runts TEXT PRIMARY KEY,
            codice_fiscale TEXT UNIQUE,
            denominazione TEXT,
            forma_giuridica TEXT,
            natura_giuridica TEXT,
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
            skip_reason TEXT,
            downloaded_at TEXT NOT NULL
        )
    SQL);

    // Sezione con match utente (email coerente col cliente Sezione creato nel test).
    $pdo->prepare('INSERT INTO sezioni_cai (codice_cai, cai_denominazione, cai_codice_fiscale, cai_email, cai_anno_fondazione, cai_regione) VALUES (?, ?, ?, ?, ?, ?)')
        ->execute(['CAI-CHECKPOINT-01', 'Sez. Checkpoint Lombardia', 'CFCHK001', 'sezione.checkpoint@example.com', 1955, 'LOMBARDIA']);

    // Sezione senza alcun match utente.
    $pdo->prepare('INSERT INTO sezioni_cai (codice_cai, cai_denominazione, cai_codice_fiscale, cai_email, cai_regione) VALUES (?, ?, ?, ?, ?)')
        ->execute(['CAI-CHECKPOINT-02', 'Sez. Checkpoint Lazio', 'CFCHK002', 'nomatch.checkpoint@example.com', 'LAZIO']);

    $pdo->prepare('INSERT INTO enti (id_runts, codice_fiscale, denominazione, sede_comune, data_iscrizione, updated_at) VALUES (?, ?, ?, ?, ?, ?)')
        ->execute(['500001', 'CFCHK001', 'Sez. Checkpoint Lombardia', 'Milano', '01/01/2023', '2023-01-01T10:00:00']);

    $pdo->prepare('INSERT INTO bilanci (id, id_runts, anno, totale_oneri, totale_proventi, risultato_esercizio, analyzed_at) VALUES (?, ?, ?, ?, ?, ?, ?)')
        ->execute([1, '500001', 2025, 5000.0, 6000.0, 1000.0, '2025-06-01T10:00:00']);

    $pdo->prepare('INSERT INTO allegati (id, id_runts, documento, codice_pratica, tipo, filename, path, downloaded_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)')
        ->execute([1, '500001', 'BILANCIO 2025', 'B00', 'bilancio_esercizio', 'bilancio-2025.pdf', 'attachments/500001/bilancio-2025.pdf', '2025-06-01T10:00:00']);

    unset($pdo);

    return ['sqlitePath' => $sqlitePath, 'datapackDir' => $datapackDir];
}

beforeEach(function (): void {
    Filament::setCurrentPanel('admin');
    $this->seed(RolePermissionSeeder::class);
});

test('the full RUNTS-CAI flow works end-to-end: import, email matching, staff consultation, sezione dashboard and regional group scoped detail', function (): void {
    Storage::fake('cai-documents');
    $fixture = makeFase8CheckpointDatapackFixture();

    // Cliente Sezione che farà match con la sezione importata (email coerente).
    $matchedCustomer = User::factory()->create(['email' => 'sezione.checkpoint@example.com']);
    $matchedCustomer->assignRole(UserRole::Customer->value);
    $matchedCustomer->forceFill(['customer_type' => CustomerType::Sezione, 'region' => Region::Lombardia])->save();

    // Cliente Sezione senza alcuna sezione CAI collegata (nessun match nel datapack).
    $unmatchedCustomer = User::factory()->create(['email' => 'sezione.unmatched@example.com']);
    $unmatchedCustomer->assignRole(UserRole::Customer->value);
    $unmatchedCustomer->forceFill(['customer_type' => CustomerType::Sezione, 'region' => Region::Lazio])->save();

    // Cliente Gruppo Regionale della stessa regione della sezione con match.
    $regionalGroupLeader = User::factory()->create();
    $regionalGroupLeader->assignRole(UserRole::Customer->value);
    $regionalGroupLeader->forceFill(['customer_type' => CustomerType::GruppoRegionale, 'region' => Region::Lombardia])->save();

    // 1. Import reale del datapack (fixture ridotta).
    $this->artisan('cai:import-datapack', ['--path' => $fixture['sqlitePath']])
        ->assertSuccessful()
        ->run();

    expect(CaiSection::query()->count())->toBe(2);

    $matchedSection = CaiSection::query()->findOrFail('CAI-CHECKPOINT-01');
    expect($matchedSection->user_id)->toBe($matchedCustomer->id);

    $unmatchedSection = CaiSection::query()->findOrFail('CAI-CHECKPOINT-02');
    expect($unmatchedSection->user_id)->toBeNull();

    // 2. Consultazione staff: la Filament Resource elenca e mostra la sezione importata.
    $staff = User::factory()->create();
    $staff->assignRole(UserRole::Developer->value);
    $staff->givePermissionTo(PermissionEnum::CaiDirectoryView->value);

    $this->actingAs($staff)
        ->get(CaiSectionResource::getUrl('index'))
        ->assertSuccessful()
        ->assertSee('Sez. Checkpoint Lombardia');

    $this->actingAs($staff)
        ->get(CaiSectionResource::getUrl('view', ['record' => $matchedSection]))
        ->assertSuccessful()
        ->assertSee('Sez. Checkpoint Lombardia')
        ->assertSee('1955');

    // 3. Dashboard del cliente Sezione con match: vede i propri dati CAI/RUNTS.
    $this->actingAs($matchedCustomer)
        ->get(CustomerDashboard::getUrl())
        ->assertSuccessful()
        ->assertSee('Sez. Checkpoint Lombardia')
        ->assertDontSee('Nessun dato CAI/RUNTS disponibile per la tua sezione');

    // 4. Dashboard del cliente Sezione senza match: stato vuoto esplicito, mai una
    // card assente silenziosa, e mai i dati di un'altra sezione.
    $this->actingAs($unmatchedCustomer)
        ->get(CustomerDashboard::getUrl())
        ->assertSuccessful()
        ->assertSee('Nessun dato CAI/RUNTS disponibile per la tua sezione')
        ->assertDontSee('Sez. Checkpoint Lombardia');

    // 5. Dashboard del cliente Gruppo Regionale: la card elenca la sezione della
    // propria regione, con link al dettaglio.
    $this->actingAs($regionalGroupLeader)
        ->get(CustomerDashboard::getUrl())
        ->assertSuccessful()
        ->assertSee(CaiSectionRegionalDetail::getUrl(['record' => $matchedCustomer->id]), false);

    // 6. Apertura del dettaglio per la sezione della propria regione: stesso
    // contenuto della dashboard cliente Sezione.
    $this->actingAs($regionalGroupLeader)
        ->get(CaiSectionRegionalDetail::getUrl(['record' => $matchedCustomer->id]))
        ->assertSuccessful()
        ->assertSee('Sez. Checkpoint Lombardia');

    // 7. Tentativo di accesso diretto (URL manipolato) a una sezione di un'altra
    // regione: respinto lato server, non solo assente dal link in UI.
    $this->actingAs($regionalGroupLeader)
        ->get(CaiSectionRegionalDetail::getUrl(['record' => $unmatchedCustomer->id]))
        ->assertForbidden();
});
