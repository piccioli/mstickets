<?php

declare(strict_types=1);

use App\Domain\Documentation\Models\DocumentationPage;
use App\Domain\Fundraising\Models\FundraisingOpportunity;
use App\Domain\Fundraising\Models\FundraisingProject;
use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\Organization;
use App\Domain\Identity\Models\User;
use App\Domain\Reporting\Models\ActivityReport;
use App\Domain\Tags\Models\Tag;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Enums\TicketType;
use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Models\TicketMessage;
use Database\Seeders\UatSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

/**
 * Impronta canonica del dataset (nomi/titoli, non id auto-incrementali): usata dai test di
 * determinismo per verificare che due esecuzioni indipendenti del seeder producano
 * esattamente lo stesso contenuto, requisito del collaudo UAT (stesso stato ad ogni deploy).
 */
function uatSeederFingerprint(): string
{
    return md5(json_encode([
        'organizations' => Organization::query()->orderBy('name')->pluck('name')->all(),
        'tags' => Tag::query()->orderBy('slug')->pluck('name')->all(),
        'documentation' => DocumentationPage::query()->orderBy('slug')->pluck('title')->all(),
        'tickets' => Ticket::query()->orderBy('id')->get(['title', 'description', 'status', 'type', 'priority'])
            ->map(fn (Ticket $ticket): array => [
                $ticket->title,
                $ticket->description,
                $ticket->status->value,
                $ticket->type->value,
                $ticket->priority->value,
            ])->all(),
        'fundraising_opportunities' => FundraisingOpportunity::query()->orderBy('name')->pluck('name')->all(),
        'fundraising_projects' => FundraisingProject::query()->orderBy('title')->pluck('title')->all(),
    ]));
}

const UAT_SEEDER_EXPECTED_FINGERPRINT = 'e0465f250189c077c523c12e42db7313';

it('popola un dataset UAT con i 5 utenti di ruolo e rifiuta di girare in produzione', function () {
    (new UatSeeder)->run();

    expect(User::query()->where('email', 'admin@orchestrator.local')->exists())->toBeTrue();
});

it('lancia eccezione se eseguito in ambiente production', function () {
    app()->instance('env', 'production');

    expect(fn () => (new UatSeeder)->run())->toThrow(RuntimeException::class);
});

it('copre tutti i moduli in scope con il volume minimo atteso', function () {
    (new UatSeeder)->run();

    foreach (UserRole::cases() as $role) {
        $user = User::query()->where('email', "{$role->value}@orchestrator.local")->sole();
        expect($user->hasRole($role->value))->toBeTrue();
        expect(Hash::check('password', (string) $user->password))->toBeTrue();
    }

    expect(Organization::query()->count())->toBe(2)
        ->and(Ticket::query()->count())->toBe(40)
        ->and(TicketMessage::query()->count())->toBe(80)
        ->and(Tag::query()->count())->toBe(10)
        ->and(DocumentationPage::query()->count())->toBe(5)
        ->and(ActivityReport::query()->count())->toBe(2)
        ->and(FundraisingOpportunity::query()->count())->toBe(3)
        ->and(FundraisingProject::query()->count())->toBe(2);

    foreach (TicketStatus::cases() as $status) {
        expect(Ticket::query()->where('status', $status->value)->exists())->toBeTrue();
    }

    foreach (TicketType::cases() as $type) {
        expect(Ticket::query()->where('type', $type->value)->exists())->toBeTrue();
    }
});

it('produce un dataset con contenuto identico (deterministico) a due esecuzioni indipendenti', function () {
    (new UatSeeder)->run();

    expect(uatSeederFingerprint())->toBe(UAT_SEEDER_EXPECTED_FINGERPRINT);
});

it('produce di nuovo lo stesso identico contenuto in una seconda esecuzione indipendente', function () {
    (new UatSeeder)->run();

    expect(uatSeederFingerprint())->toBe(UAT_SEEDER_EXPECTED_FINGERPRINT);
});

it('è eseguibile standalone senza il resto della catena DatabaseSeeder (materializza da sé i ruoli)', function () {
    (new UatSeeder)->run();

    expect(User::query()->where('email', UserRole::Admin->value.'@orchestrator.local')->sole()->hasRole(UserRole::Admin->value))->toBeTrue();
});
