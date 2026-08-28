<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Models\CaiSection;
use App\Domain\Identity\Enums\Permission as PermissionEnum;
use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use App\Filament\Resources\CaiSections\CaiSectionResource;
use App\Filament\Resources\CaiSections\Pages\ListCaiSections;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Filament::setCurrentPanel('admin');
});

/**
 * Stesso principio di `CaiSectionResourceTest::grantCaiDirectoryPanelAccess()`, ridichiarato
 * qui con un nome diverso: due file Pest non possono dichiarare la stessa funzione globale
 * (entrambi eseguiti nello stesso processo di test).
 */
function grantCaiDirectoryExportAccess(User $user): User
{
    Role::query()->firstOrCreate(['name' => UserRole::Developer->value, 'guard_name' => 'web']);
    $user->assignRole(UserRole::Developer->value);

    return $user->fresh();
}

test('a user with cai-directory.view can export the currently filtered sections as csv', function (): void {
    $user = grantCaiDirectoryExportAccess(userWithPermissions(PermissionEnum::CaiDirectoryView));
    $linkedUser = User::factory()->create(['email' => 'sezione@example.com']);
    $lombardia = CaiSection::create([
        'codice_cai' => 'CAI-LOMB',
        'name' => 'Sezione Lombardia',
        'region' => 'LOMBARDIA',
        'latitude' => 45.4642,
        'longitude' => 9.19,
        'user_id' => $linkedUser->id,
    ]);
    CaiSection::create([
        'codice_cai' => 'CAI-PIEM',
        'name' => 'Sezione Piemonte',
        'region' => 'PIEMONTE',
    ]);

    $this->actingAs($user);

    $test = Livewire::test(ListCaiSections::class)
        ->filterTable('region', 'LOMBARDIA')
        ->callTableAction('exportCsv');

    $download = $test->effects['download'];

    expect($download['name'])->toBe('sezioni-cai.csv')
        ->and($download['contentType'])->toBe('text/csv');

    $rows = array_map('str_getcsv', explode("\n", rtrim(base64_decode((string) $download['content']), "\n")));

    expect($rows[0])->toBe([
        'codice_cai', 'name', 'region', 'address', 'phone', 'email', 'pec',
        'founded_year', 'members_count', 'latitude', 'longitude', 'linked_user_email',
    ])
        ->and($rows)->toHaveCount(2)
        ->and($rows[1][0])->toBe('CAI-LOMB')
        ->and($rows[1][11])->toBe('sezione@example.com');
});

test('a user with cai-directory.view can export the currently filtered sections as geojson', function (): void {
    $user = grantCaiDirectoryExportAccess(userWithPermissions(PermissionEnum::CaiDirectoryView));
    CaiSection::create([
        'codice_cai' => 'CAI-GEO',
        'name' => 'Sezione Geolocalizzata',
        'region' => 'LOMBARDIA',
        'latitude' => 45.4642,
        'longitude' => 9.19,
    ]);
    CaiSection::create([
        'codice_cai' => 'CAI-NOCOORD',
        'name' => 'Sezione Senza Coordinate',
        'region' => 'LOMBARDIA',
    ]);

    $this->actingAs($user);

    $test = Livewire::test(ListCaiSections::class)->callTableAction('exportGeoJson');

    $download = $test->effects['download'];

    expect($download['name'])->toBe('sezioni-cai.geojson')
        ->and($download['contentType'])->toBe('application/geo+json');

    /** @var array{type: string, features: array<int, array<string, mixed>>} $geoJson */
    $geoJson = json_decode(base64_decode((string) $download['content']), associative: true, flags: JSON_THROW_ON_ERROR);

    expect($geoJson['type'])->toBe('FeatureCollection')
        ->and($geoJson['features'])->toHaveCount(2);

    $withCoords = collect($geoJson['features'])->firstWhere('properties.codice_cai', 'CAI-GEO');
    $withoutCoords = collect($geoJson['features'])->firstWhere('properties.codice_cai', 'CAI-NOCOORD');

    expect($withCoords['geometry'])->toBe(['type' => 'Point', 'coordinates' => [9.19, 45.4642]])
        ->and($withoutCoords['geometry'])->toBeNull();
});

test('a user with cai-directory.view can export the currently filtered sections as xlsx', function (): void {
    $user = grantCaiDirectoryExportAccess(userWithPermissions(PermissionEnum::CaiDirectoryView));
    CaiSection::create([
        'codice_cai' => 'CAI-XLSX',
        'name' => 'Sezione Xlsx',
        'region' => 'LOMBARDIA',
    ]);

    $this->actingAs($user);

    $test = Livewire::test(ListCaiSections::class)->callTableAction('exportXlsx');

    $download = $test->effects['download'];

    expect($download['name'])->toBe('sezioni-cai.xlsx')
        ->and($download['contentType'])->toBe('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

    $tempPath = tempnam(sys_get_temp_dir(), 'cai-sections-export-test-');
    file_put_contents($tempPath, base64_decode((string) $download['content']));

    $reader = new XlsxReader;
    $reader->open($tempPath);

    $rows = [];

    foreach ($reader->getSheetIterator() as $sheet) {
        foreach ($sheet->getRowIterator() as $row) {
            $rows[] = $row->toArray();
        }
    }

    $reader->close();
    unlink($tempPath);

    expect($rows)->toHaveCount(2)
        ->and($rows[0][1])->toBe('name')
        ->and($rows[1][0])->toBe('CAI-XLSX')
        ->and($rows[1][1])->toBe('Sezione Xlsx');
});

test('a user without cai-directory.view cannot see the export actions', function (): void {
    $user = grantCaiDirectoryExportAccess(userWithPermissions());
    CaiSection::create(['codice_cai' => 'CAI-DENIED', 'name' => 'Sezione', 'region' => 'LOMBARDIA']);

    $this->actingAs($user);

    $this->get(CaiSectionResource::getUrl('index'))->assertForbidden();
});
