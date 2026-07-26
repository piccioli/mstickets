<?php

declare(strict_types=1);

use App\Domain\Documentation\Enums\DocumentationCategory;
use App\Domain\Documentation\Models\DocumentationPage;
use App\Domain\Fundraising\Enums\FundraisingEvaluationCriterion;
use App\Domain\Fundraising\Models\FundraisingEvaluationScore;
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
use Database\Seeders\DevelopmentSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('it refuses to run in production', function (): void {
    app()->detectEnvironment(fn () => 'production');

    expect(fn () => (new DevelopmentSeeder)->run())->toThrow(RuntimeException::class);
});

test('it seeds a complete development environment', function (): void {
    Storage::fake('public');
    (new RolePermissionSeeder)->run();

    (new DevelopmentSeeder)->run();

    foreach (UserRole::cases() as $role) {
        $user = User::query()->where('email', "{$role->value}@orchestrator.local")->sole();
        expect($user->hasRole($role->value))->toBeTrue();
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

    expect(DocumentationPage::query()->where('category', DocumentationCategory::Internal->value)->exists())->toBeTrue()
        ->and(DocumentationPage::query()->where('category', DocumentationCategory::Customer->value)->exists())->toBeTrue();

    $evaluated = FundraisingOpportunity::query()->whereNotNull('evaluated_at')->sole();
    expect(FundraisingEvaluationScore::query()->where('fundraising_opportunity_id', $evaluated->id)->count())
        ->toBe(count(FundraisingEvaluationCriterion::cases()))
        ->and(FundraisingProject::query()->where('fundraising_opportunity_id', $evaluated->id)->count())->toBe(2);

    $messagesWithAttachments = TicketMessage::query()->get()->filter(fn (TicketMessage $message): bool => $message->getMedia('attachments')->isNotEmpty());
    expect($messagesWithAttachments)->not->toBeEmpty();
});

test('running it twice does not duplicate tickets, tags or documentation', function (): void {
    Storage::fake('public');
    (new RolePermissionSeeder)->run();
    (new DevelopmentSeeder)->run();
    (new DevelopmentSeeder)->run();

    expect(Ticket::query()->count())->toBe(40)
        ->and(Tag::query()->count())->toBe(10)
        ->and(DocumentationPage::query()->count())->toBe(5)
        ->and(User::query()->where('email', UserRole::Admin->value.'@orchestrator.local')->count())->toBe(1);
});
