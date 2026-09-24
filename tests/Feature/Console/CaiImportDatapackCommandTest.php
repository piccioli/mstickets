<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Import\CaiDatapackImporter;
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

test('a scoped import (onlyCaiSectionCode) imports only the requested section, its subsections and its runts data', function (): void {
    Storage::fake('cai-documents');
    $fixture = makeCaiDatapackFixture();

    User::factory()->create(['email' => 'sezione@example.com']);
    User::factory()->create(['email' => 'sub@example.com']);

    app(CaiDatapackImporter::class)
        ->import($fixture['sqlitePath'], dryRun: false, onlyCaiSectionCode: '9216049');

    expect(CaiSection::query()->pluck('codice_cai')->all())->toBe(['9216049'])
        ->and(CaiSubsection::query()->pluck('cai_codice')->all())->toBe(['SUB001'])
        ->and(CaiRuntsRegistration::query()->pluck('id_runts')->all())->toBe(['166339'])
        ->and(CaiFinancialStatement::query()->count())->toBe(1)
        ->and(CaiBoardMember::query()->count())->toBe(1)
        ->and(CaiDocument::query()->count())->toBe(1);
});

test('skipSectionFields imports only the RUNTS-sourced tables, leaving an already-imported CaiSection/CaiSubsection untouched', function (): void {
    Storage::fake('cai-documents');
    $fixture = makeCaiDatapackFixture();

    User::factory()->create(['email' => 'sezione@example.com']);
    User::factory()->create(['email' => 'sub@example.com']);

    // Prima sincronizzazione completa (come farebbe "Sincronizza dati CAI"): crea la
    // sezione di cui il bottone RUNTS-only presume già l'esistenza.
    app(CaiDatapackImporter::class)->import($fixture['sqlitePath'], dryRun: false, onlyCaiSectionCode: '9216049');

    $section = CaiSection::query()->findOrFail('9216049');
    $section->update(['name' => 'Nome modificato manualmente']);
    $sectionUpdatedAt = $section->fresh()->updated_at;

    $registration = CaiRuntsRegistration::query()->findOrFail('166339');
    $registration->update(['name' => 'Nome registrazione modificato manualmente']);

    app(CaiDatapackImporter::class)->import(
        $fixture['sqlitePath'],
        dryRun: false,
        onlyCaiSectionCode: '9216049',
        skipSectionFields: true,
    );

    expect($section->fresh()->name)->toBe('Nome modificato manualmente')
        ->and($section->fresh()->updated_at->equalTo($sectionUpdatedAt))->toBeTrue()
        ->and($registration->fresh()->name)->toBe('Sez. Abbiategrasso')
        ->and(CaiFinancialStatement::query()->count())->toBe(1)
        ->and(CaiBoardMember::query()->count())->toBe(1)
        ->and(CaiDocument::query()->count())->toBe(1);
});
