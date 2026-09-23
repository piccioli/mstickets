<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Actions\UploadCaiDocumentManually;
use App\Domain\CaiDirectory\Enums\CaiDocumentType;
use App\Domain\CaiDirectory\Jobs\AnalyzeCaiFinancialStatementDocument;
use App\Domain\CaiDirectory\Models\CaiDocument;
use App\Domain\CaiDirectory\Models\CaiRuntsRegistration;
use App\Domain\CaiDirectory\Models\CaiSection;
use App\Domain\Identity\Enums\CustomerType;
use App\Domain\Identity\Enums\Permission as PermissionEnum;
use App\Domain\Identity\Enums\Region;
use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\Enums\TicketMessageChannel;
use App\Domain\Ticketing\Models\Ticket;
use App\Filament\Pages\CustomerDashboard;
use App\Filament\Pages\CustomerRegionalSectionsDashboard;
use App\Filament\Resources\CaiSections\CaiSectionResource;
use App\Filament\Resources\Tickets\Pages\CreateTicket;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

/**
 * Checkpoint di fine Fase 9: percorre end-to-end i tre filoni indipendenti confluiti in questo
 * ramo — (1) creazione ticket con Richiesta obbligatoria, niente più "Ticket padre"; (2)
 * sincronizzazione live CAI/RUNTS di una sezione (registrazione + bilancio, analisi asincrona
 * dispatchata); (3) fallback CF/PIVA da foglio manuale + upload manuale di un documento senza
 * registrazione RUNTS + menu Gruppo Regionale scoped alla propria regione. Non ri-testa ogni
 * dettaglio già coperto dai test mirati (fase-9.php): verifica solo che i pezzi funzionino
 * insieme, end-to-end, come li userebbe davvero un tester UAT in un'unica sessione.
 */
uses(RefreshDatabase::class);

beforeEach(function (): void {
    Filament::setCurrentPanel('admin');
    $this->seed(RolePermissionSeeder::class);
});

test('the full Fase 9 flow works end-to-end: ticket richiesta, live CAI/RUNTS sync with bilancio extraction, CF/PIVA fallback, manual document upload and gruppo regionale menu', function (): void {
    Storage::fake('cai-documents');
    Queue::fake();

    // --- Filone 1: creazione ticket con Richiesta obbligatoria ---------------------------
    $requester = withRole(User::factory()->create(), UserRole::Customer);
    $requester->givePermissionTo(PermissionEnum::TicketCreate->value, PermissionEnum::TicketViewOwn->value);

    $this->actingAs($requester);

    Livewire::test(CreateTicket::class)
        ->fillForm([
            'title' => 'Errore login SSO',
            'richiesta' => '<p>Da ieri non riesco ad accedere al portale con le mie credenziali.</p>',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $ticket = Ticket::query()->sole();
    $firstMessage = $ticket->messages()->sole();
    expect($firstMessage->channel)->toBe(TicketMessageChannel::Web)
        ->and($firstMessage->body_text)->toContain('Da ieri non riesco ad accedere al portale con le mie credenziali.');

    // --- Filone 2: sincronizzazione live CAI/RUNTS di una sezione, con estrazione bilancio ---
    $staff = withRole(User::factory()->create(), UserRole::Developer);
    $staff->givePermissionTo(
        PermissionEnum::CaiDirectoryView->value,
        PermissionEnum::CaiDirectoryUploadDocument->value,
    );

    $section = caiSection(['codice_cai' => 'CHK9-001', 'name' => 'Sez. Checkpoint Fase 9', 'tax_code' => null, 'region' => 'LOMBARDIA']);

    Http::fake([
        'http://cai-runts-scraper:8000/scrape/runts-entity*' => Http::response([
            'found' => true,
            'entity' => [
                'id_runts' => 'CHK9-RUNTS-001', 'codice_fiscale' => '01234567890',
                'denominazione' => 'Sez. Checkpoint Fase 9', 'forma_giuridica' => null,
                'natura_giuridica' => null, 'sede_indirizzo' => null, 'sede_civico' => null,
                'sede_comune' => null, 'sede_provincia' => null, 'sede_regione' => null,
                'sede_cap' => null, 'data_iscrizione' => null, 'sezione_registro' => null,
                'settori_attivita' => null, 'rappresentante_legale' => null, 'sito_web' => null,
                'pec' => null, 'url_dettaglio' => null,
            ],
            'board_members' => [],
            'documents' => [
                [
                    'documento' => 'Bilancio di esercizio 2025', 'codice_pratica' => 'B00',
                    'tipo' => 'bilancio_esercizio', 'anno' => 2025, 'filename' => 'bilancio-2025.pdf',
                    'mime' => 'application/pdf', 'size' => 33, 'hash_sha256' => 'checkpoint-hash',
                    'skip_reason' => null, 'content_base64' => base64_encode('%PDF-1.4 checkpoint fixture'),
                ],
            ],
        ]),
    ]);

    // La sezione non ha ancora un codice fiscale: il fallback CF/PIVA (Filone 3) deve
    // completarlo prima che cai:sync-runts-all possa sincronizzarla.
    $fallbackPath = tempnam(sys_get_temp_dir(), 'cai-checkpoint-fallback-');
    file_put_contents($fallbackPath, json_encode([
        'CHK9-001' => ['name' => $section->name, 'tax_code' => '01234567890', 'vat_number' => null],
    ]));
    config(['cai_directory.tax_code_fallback_path' => $fallbackPath]);

    $this->artisan('cai:sync-runts-all', ['--codes' => 'CHK9-001', '--delay-ms' => 0])
        ->expectsOutputToContain('1 sezione/i completata/e dal foglio manuale')
        ->expectsOutputToContain('1 sezioni esaminate, 1 sincronizzate, 0 non trovate, 0 errori')
        ->assertExitCode(0);

    unlink($fallbackPath);

    $section = $section->fresh();
    expect($section->tax_code)->toBe('01234567890');

    $registration = CaiRuntsRegistration::query()->findOrFail('CHK9-RUNTS-001');
    expect($registration->cai_section_id)->toBe('CHK9-001');

    $syncedDocument = CaiDocument::query()->where('cai_runts_registration_id', 'CHK9-RUNTS-001')->sole();
    expect($syncedDocument->source->value)->toBe('runts');

    Queue::assertPushed(AnalyzeCaiFinancialStatementDocument::class);

    $this->actingAs($staff)
        ->get(CaiSectionResource::getUrl('view', ['record' => $section]))
        ->assertSuccessful()
        ->assertSee('Sez. Checkpoint Fase 9')
        ->assertSee('01234567890')
        ->assertSee('Bilancio di esercizio 2025');

    // --- Filone 3: upload manuale di un documento, senza registrazione, e menu GR ------------
    $manualDocument = UploadCaiDocumentManually::run(
        $staff,
        $section,
        CaiDocumentType::Altro,
        null,
        'Verbale assemblea 2025',
        UploadedFile::fake()->create('verbale.pdf', 5, 'application/pdf'),
    );

    expect($manualDocument->cai_runts_registration_id)->toBeNull()
        ->and($manualDocument->cai_section_id)->toBe('CHK9-001')
        ->and($manualDocument->source->value)->toBe('manual');

    $this->actingAs($staff)
        ->get(CaiSectionResource::getUrl('view', ['record' => $section]))
        ->assertSuccessful()
        ->assertSee('Verbale assemblea 2025')
        ->assertSee('Caricamento manuale');

    // "Sezione" qui è il cliente (User con customer_type=Sezione, Fase 7) della stessa regione
    // del Gruppo Regionale — un concetto distinto dalla CaiSection RUNTS-CAI appena
    // sincronizzata sopra (Fase 8/9), mai confuso con essa.
    $sezioneCustomer = withRole(User::factory()->create(['name' => 'Sez. Cliente Checkpoint']), UserRole::Customer);
    $sezioneCustomer->forceFill(['customer_type' => CustomerType::Sezione, 'region' => Region::Lombardia])->save();

    $groupLeader = withRole(User::factory()->create(), UserRole::Customer);
    $groupLeader->forceFill(['customer_type' => CustomerType::GruppoRegionale, 'region' => Region::Lombardia])->save();

    $this->actingAs($groupLeader);

    expect(CustomerDashboard::getNavigationGroup())->toBe('GR');

    $this->get(CustomerDashboard::getUrl())
        ->assertSuccessful()
        ->assertDontSee('Sezioni del gruppo regionale');

    $this->get(CustomerRegionalSectionsDashboard::getUrl())
        ->assertSuccessful()
        ->assertSee('Sez. Cliente Checkpoint');
});
