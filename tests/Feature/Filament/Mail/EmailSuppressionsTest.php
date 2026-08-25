<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\Permission as PermissionEnum;
use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use App\Domain\Mail\Enums\SuppressionReason;
use App\Domain\Mail\Models\EmailSuppression;
use App\Filament\Pages\EmailSuppressions;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Filament::setCurrentPanel('admin');
});

/**
 * Stesso helper di EmailMessageResourceTest.php (US-321/US-322): un ruolo
 * "vuoto" solo per superare il gate d'accesso al pannello (§9.1, US-020),
 * isolando i test sui soli permessi diretti concessi da userWithPermissions().
 */
function grantSuppressionsPanelAccess(User $user): User
{
    Role::query()->firstOrCreate(['name' => UserRole::Developer->value, 'guard_name' => 'web']);
    $user->assignRole(UserRole::Developer->value);

    return $user->fresh();
}

function suppressionFixture(array $attributes = []): EmailSuppression
{
    return EmailSuppression::create(array_merge([
        'email' => 'soppresso@example.com',
        'reason' => SuppressionReason::HardBounce,
    ], $attributes))->fresh();
}

test('a user without email.view is denied access to the suppressions page', function (): void {
    $user = grantSuppressionsPanelAccess(userWithPermissions());

    expect(EmailSuppressions::canAccess())->toBeFalse();
});

test('a user with email.view can access the suppressions page and see the list', function (): void {
    $user = grantSuppressionsPanelAccess(userWithPermissions(PermissionEnum::EmailView));
    $suppression = suppressionFixture();

    $this->actingAs($user);

    expect(EmailSuppressions::canAccess())->toBeTrue();

    Livewire::test(EmailSuppressions::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$suppression]);
});

test('the reason filter narrows the suppressions list', function (): void {
    $user = grantSuppressionsPanelAccess(userWithPermissions(PermissionEnum::EmailView));
    $hardBounce = suppressionFixture(['email' => 'hard@example.com', 'reason' => SuppressionReason::HardBounce]);
    $loopProtection = suppressionFixture(['email' => 'loop@example.com', 'reason' => SuppressionReason::LoopProtection]);

    $this->actingAs($user);

    Livewire::test(EmailSuppressions::class)
        ->filterTable('reason', SuppressionReason::HardBounce->value)
        ->assertCanSeeTableRecords([$hardBounce])
        ->assertCanNotSeeTableRecords([$loopProtection]);
});

test('a user with only email.view does not see the remove action', function (): void {
    $user = grantSuppressionsPanelAccess(userWithPermissions(PermissionEnum::EmailView));
    $suppression = suppressionFixture();

    $this->actingAs($user);

    Livewire::test(EmailSuppressions::class)
        ->assertTableActionHidden('remove', $suppression);
});

test('a user with email.manage can remove a suppression, re-enabling delivery', function (): void {
    $user = grantSuppressionsPanelAccess(userWithPermissions(PermissionEnum::EmailView, PermissionEnum::EmailManage));
    $suppression = suppressionFixture();

    $this->actingAs($user);

    Livewire::test(EmailSuppressions::class)
        ->callTableAction('remove', $suppression)
        ->assertHasNoTableActionErrors();

    expect(EmailSuppression::query()->find($suppression->id))->toBeNull();
});
