<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Enums\CaiDocumentSource;
use App\Domain\CaiDirectory\Models\CaiDocument;
use App\Domain\CaiDirectory\Models\CaiFinancialStatement;
use App\Domain\Identity\Enums\CustomerType;
use App\Domain\Identity\Enums\Region;
use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use App\Filament\Auth\Pages\Login;
use App\Filament\Pages\Dashboard;
use App\Filament\Pages\RegionalGroupDashboard;
use App\Filament\Pages\RegionalGroupFinancialStatements;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

/**
 * Checkpoint della feature 10.3 (Bilancio consolidato delle Sezioni del GR): percorre il flusso come lo
 * farebbe un tester UAT con un utente GR reale — login, Dashboard V2, click sul badge del conto economico
 * consolidato, pagina aperta sull'esercizio della card (non quello di default), filtro "Solo PDF",
 * download del PDF di una Sezione del GR e rifiuto del PDF di una Sezione di un altro GR. I dettagli
 * (stati, "da verificare", ordinamento, copertura) sono coperti dai test mirati elencati in
 * docs/collaudo/fase-10.php.
 */
uses(RefreshDatabase::class);

beforeEach(function (): void {
    Filament::setCurrentPanel('admin');
    $this->seed(RolePermissionSeeder::class);
    Storage::fake('cai-documents');
});

function fase10ConsolidatedPdf(string $code, int $year, string $path): CaiDocument
{
    Storage::disk('cai-documents')->put($path, '%PDF-1.4 fake content');

    return caiDocument(['title' => 'Rendiconto gestionale', 'cai_runts_registration_id' => null,
        'cai_section_id' => $code, 'year' => $year, 'source' => CaiDocumentSource::Manual,
        'file_path' => $path, 'file_name' => basename($path)]);
}

/** @return list<string> */
function fase10ConsolidatedTableNames(string $html): array
{
    preg_match_all('/ms-consolidated__name">([^<]+)</', $html, $matches);

    return array_values(array_unique(array_map('trim', $matches[1])));
}

test('a Gruppo Regionale user follows the consolidated badge to the statements of the right year, filters the sections with only the PDF and downloads one, never the PDF of another group', function (): void {
    $user = withRole(User::factory()->create(['name' => 'GR Toscana', 'password' => bcrypt('password')]), UserRole::Customer);
    $user->forceFill(['customer_type' => CustomerType::GruppoRegionale, 'region' => Region::Toscana])->save();

    caiSection(['codice_cai' => '9226001', 'name' => 'SEZ. PISA', 'region' => 'TOSCANA']);
    caiSection(['codice_cai' => '9226002', 'name' => 'SEZ. LUCCA', 'region' => 'TOSCANA']);
    caiSection(['codice_cai' => '9226003', 'name' => 'SEZ. SIENA', 'region' => 'TOSCANA']);
    caiSection(['codice_cai' => '9213001', 'name' => 'SEZ. GENOVA', 'region' => 'LIGURIA']);

    // Il conto economico più recente è del 2024, lo stato patrimoniale del 2025: la pagina ha 2025 come
    // esercizio di default, ma il badge del conto economico consolidato deve aprirla sul 2024.
    CaiFinancialStatement::create(['cai_section_id' => '9226001', 'year' => 2024,
        'total_revenues' => 120400, 'total_expenses' => 92847, 'net_result' => 27553]);
    CaiFinancialStatement::create(['cai_section_id' => '9226001', 'year' => 2025,
        'net_equity' => 210500, 'total_assets' => 318200]);
    $lucca = fase10ConsolidatedPdf('9226002', 2024, 'bilanci/lucca-2024.pdf');
    $genova = fase10ConsolidatedPdf('9213001', 2024, 'bilanci/genova-2024.pdf');

    // --- Login ------------------------------------------------------------------------
    Livewire::test(Login::class)
        ->set('data.email', $user->email)
        ->set('data.password', 'password')
        ->call('authenticate')
        ->assertRedirect();

    expect(auth()->id())->toBe($user->id);

    // --- Dashboard V2 → badge del conto economico consolidato --------------------------
    Livewire::test(Dashboard::class)->assertRedirect(RegionalGroupDashboard::getUrl());

    $incomeUrl = RegionalGroupFinancialStatements::getUrl(['esercizio' => 2024]);

    $this->get(RegionalGroupDashboard::getUrl())
        ->assertSuccessful()
        ->assertSeeText('Conto economico consolidato Sezioni')
        ->assertSeeText('Esercizio 2024 · Somma di 1 Sezione')
        ->assertSee('href="'.$incomeUrl.'"', escape: false);

    // --- Pagina sull'esercizio della card ----------------------------------------------
    $this->get($incomeUrl)
        ->assertSuccessful()
        ->assertSeeText('Bilancio consolidato Sezioni')
        ->assertSeeText('GR Toscana · Codice CAI 9226000')
        ->assertSeeText('+27.553 €')
        ->assertSeeText('120.400 €')
        ->assertSeeText('Esercizio 2024')
        ->assertDontSeeText('210.500 €');

    // --- Filtro "Solo PDF" ---------------------------------------------------------------
    $component = Livewire::withQueryParams(['esercizio' => 2024])
        ->test(RegionalGroupFinancialStatements::class)
        ->assertSet('year', 2024)
        ->call('filterStatus', 'solo-pdf')
        ->assertSet('status', 'solo-pdf')
        ->assertSeeText('1 di 3 Sezioni');

    expect(fase10ConsolidatedTableNames($component->html()))->toBe(['SEZ. LUCCA']);

    $pdfUrl = route('cai-documents.download', $lucca);
    $component->assertSee('href="'.$pdfUrl.'" target="_blank"', escape: false);

    // --- Download del PDF di una Sezione del GR, rifiuto di quello di un altro GR -----------
    $this->get($pdfUrl)
        ->assertOk()
        ->assertDownload('lucca-2024.pdf');

    $this->get(route('cai-documents.download', $genova))->assertForbidden();
});
